<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\CreateAction;
use Sylament\Actions\EditAction;
use Sylament\Actions\ViewAction;
use Sylament\Panels\CurrentPanel;
use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Panels\PanelRegistry;
use Sylament\Panels\Resources\Pages\PageRegistration;
use Sylament\Panels\Resources\RelationManagers\RelationManager;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Resources\ResourceRegistry;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Project;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Task;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\MrrPanelProvider;
use Sylament\Panels\Tests\Fixtures\Resources\MrrProjectResource;
use Sylament\Panels\Tests\Fixtures\Resources\MrrTaskResource;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateMrrTask;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditMrrTask;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\State\PropertyAccessor;
use Sylament\Schemas\State\RecordTypeReader;
use Sylament\Tables\Table;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/*
 * Every host here is declared in the test file rather than under
 * `tests/Fixtures/`: none carries `#[AsLiveComponent]`, and the Kernel's PSR-4
 * glob would autoconfigure them onto the relation-manager tag, where the pass'
 * throw would kill every Integration boot. `FlatTaskResource` stays out of the
 * glob for the same reason — a resource nesting under nothing, so the
 * parentless arm of the URL hoist has something to answer for.
 */

/**
 * The related resource in its unnested shape: the same records, reached through
 * pages of its own rather than under a parent.
 */
final class FlatTaskResource extends Resource
{
    protected static ?string $recordClass = Task::class;

    protected static ?string $slug = 'flat-tasks';

    public function getPages(): array
    {
        return [
            'create' => new PageRegistration(CreateMrrTask::class, '/create'),
            'edit' => new PageRegistration(EditMrrTask::class, '/{record}/edit'),
        ];
    }
}

abstract class RelationshipTableProbe extends RelationManager
{
    public ?object $owner = null;

    public function getOwnerRecord(): object
    {
        return $this->owner ??= new Project();
    }
}

final class NestedRelatedProbe extends RelationshipTableProbe
{
    protected static string $relationship = 'tasks';

    protected static ?string $relatedResource = MrrTaskResource::class;

    public function table(Table $table): Table
    {
        return $table->headerActions([CreateAction::make()]);
    }
}

final class FlatRelatedProbe extends RelationshipTableProbe
{
    protected static string $relationship = 'tasks';

    protected static ?string $relatedResource = FlatTaskResource::class;
}

final class RelatedlessProbe extends RelationshipTableProbe
{
    protected static string $relationship = 'blogPosts';
}

final class TitledProbe extends RelationshipTableProbe
{
    protected static string $relationship = 'tasks';

    protected static ?string $relationshipTitle = 'Checklist';

    protected static ?string $relatedResource = MrrTaskResource::class;
}

final class NamelessRelatedProbe extends RelationshipTableProbe
{
    protected static ?string $relatedResource = MrrTaskResource::class;
}

/**
 * The route paths `PanelRouteLoader` mounts the manage-related-records panel
 * at, plus the unnested resource's own, so a URL generated here is the one the
 * loader would answer.
 */
const MRR_UNIT_ROUTES = [
    'sylament.mrr.resources.projects.index' => '/mrr/projects',
    'sylament.mrr.resources.projects.edit' => '/mrr/projects/{record}/edit',
    'sylament.mrr.resources.projects.tasks' => '/mrr/projects/{record}/tasks',
    'sylament.mrr.resources.projects.tasks.create' => '/mrr/projects/{project}/tasks/create',
    'sylament.mrr.resources.projects.tasks.edit' => '/mrr/projects/{project}/tasks/{record}/edit',
    'sylament.mrr.resources.flat-tasks.create' => '/mrr/flat-tasks/create',
    'sylament.mrr.resources.flat-tasks.edit' => '/mrr/flat-tasks/{record}/edit',
];

function mrrUnitRegistry(EntityManagerInterface $entityManager): ResourceRegistry
{
    $resources = [
        MrrProjectResource::class => new MrrProjectResource(),
        MrrTaskResource::class => new MrrTaskResource(),
        FlatTaskResource::class => new FlatTaskResource(),
    ];

    $registry = new ResourceRegistry(new ServiceLocator(array_map(
        static fn (Resource $resource): Closure => static fn (): Resource => $resource,
        $resources,
    )));

    $routes = new RouteCollection();

    foreach (MRR_UNIT_ROUTES as $name => $path) {
        $routes->add($name, new Route($path));
    }

    // Outside a kernel no request names the current panel, so the rig makes the
    // manage panel the default — a URL minted here names its routes.
    $panels = new PanelRegistry([
        new class extends PanelProvider {
            public function panel(Panel $panel): Panel
            {
                return new MrrPanelProvider()->panel($panel)->default();
            }
        },
    ]);
    $propertyAccessor = new PropertyAccessor(
        PropertyAccess::createPropertyAccessor(),
        new RecordTypeReader(),
    );

    foreach ($resources as $resource) {
        $resource->setResourceServices(
            $entityManager,
            new UrlGenerator($routes, new RequestContext()),
            new CurrentPanel(new RequestStack(), $panels),
            $panels,
            $propertyAccessor,
            new AssociationReader($entityManager, $propertyAccessor),
            $registry,
        );
    }

    return $registry;
}

/**
 * @template T of RelationshipTableProbe
 *
 * @param class-string<T> $probeClass
 *
 * @return T
 */
function mrrUnitProbe(EntityManagerInterface $entityManager, string $probeClass, ?object $owner = null): RelationshipTableProbe
{
    $probe = new $probeClass();
    $probe->setResourceRegistry(mrrUnitRegistry($entityManager));
    $probe->owner = $owner;

    return $probe;
}

/**
 * One project with one task under it, flushed so each carries the identifier a
 * URL keys it by.
 *
 * @return array{EntityManagerInterface, Project, Task}
 */
function mrrUnitRecords(): array
{
    $entityManager = EntityManagerFactory::create();

    $project = new Project()->setName('Apollo');
    $task = new Task()->setTitle('Alpha')->setPosition(1)->setProject($project);

    $entityManager->persist($project);
    $entityManager->persist($task);
    $entityManager->flush();

    return [$entityManager, $project, $task];
}

it('refuses a host that names no relationship, related resource or not', function (): void {
    expect(NamelessRelatedProbe::getRelationshipName(...))
        ->toThrow(LogicException::class, NamelessRelatedProbe::class)
        ->and(NestedRelatedProbe::getRelationshipName())->toBe('tasks');
});

it('prefers the declared relationship title over both fallbacks', function (): void {
    expect(mrrUnitProbe(EntityManagerFactory::create(), TitledProbe::class)->getRelationshipTitle())->toBe('Checklist');
});

it('titles the tier with the related resource plural label', function (): void {
    expect(mrrUnitProbe(EntityManagerFactory::create(), NestedRelatedProbe::class)->getRelationshipTitle())->toBe('Tasks');
});

it('humanizes the relationship name when nothing else names the tier', function (): void {
    expect(mrrUnitProbe(EntityManagerFactory::create(), RelatedlessProbe::class)->getRelationshipTitle())->toBe('Blog Posts');
});

it('delegates the form and the infolist to the related resource', function (): void {
    $probe = mrrUnitProbe(EntityManagerFactory::create(), NestedRelatedProbe::class);
    $components = $probe->form(Schema::make())->getComponents();

    expect($components)->toHaveCount(1)
        ->and($components[0]->getName())->toBe('title')
        // The related resource leaves the infolist untouched, so the same schema
        // comes back — the delegation, not a second tree.
        ->and($probe->infolist($schema = Schema::make()))->toBe($schema);
});

it('leaves the schemas alone without a related resource', function (): void {
    $probe = mrrUnitProbe(EntityManagerFactory::create(), RelatedlessProbe::class);

    expect($probe->form($form = Schema::make()))->toBe($form)
        ->and($probe->infolist($infolist = Schema::make()))->toBe($infolist);
});

it('stamps the related resource onto the table and applies the host hook on top', function (): void {
    $table = mrrUnitProbe(EntityManagerFactory::create(), NestedRelatedProbe::class)->getTable();

    expect(array_keys($table->getColumns()))->toBe(['title', 'position'])
        ->and($table->getRecordLabel())->toBe('task')
        ->and($table->getPluralRecordLabel())->toBe('tasks')
        ->and($table->getRecordActions())->toHaveCount(2)
        // The related resource's table() ran first and the host's on top of it,
        // so both sets of declarations survive.
        ->and($table->getHeaderActions())->toHaveCount(1);
});

it('leaves the relationship table unstamped without a related resource', function (): void {
    $table = mrrUnitProbe(EntityManagerFactory::create(), RelatedlessProbe::class)->getTable();

    expect($table->getColumns())->toBe([])
        ->and($table->getRecordActions())->toBe([]);
});

it('mints the related create url under the owner record', function (): void {
    [$entityManager, $project] = mrrUnitRecords();

    expect(mrrUnitProbe($entityManager, NestedRelatedProbe::class, $project)->getDefaultActionUrl(CreateAction::make()))
        ->toBe(sprintf('http://localhost/mrr/projects/%s/tasks/create', $project->getId()));
});

it('mints the related edit url for the record an action carries', function (): void {
    [$entityManager, $project, $task] = mrrUnitRecords();

    expect(mrrUnitProbe($entityManager, NestedRelatedProbe::class, $project)->getDefaultActionUrl(EditAction::make()->record($task)))
        ->toBe(sprintf('http://localhost/mrr/projects/%s/tasks/%s/edit', $project->getId(), $task->getId()));
});

it('carries no owner into the url of a related resource nesting under nothing', function (): void {
    [$entityManager, $project, $task] = mrrUnitRecords();

    $probe = mrrUnitProbe($entityManager, FlatRelatedProbe::class, $project);

    expect($probe->getDefaultActionUrl(CreateAction::make()))->toBe('http://localhost/mrr/flat-tasks/create')
        ->and($probe->getDefaultActionUrl(EditAction::make()->record($task)))
        ->toBe(sprintf('http://localhost/mrr/flat-tasks/%s/edit', $task->getId()));
});

it('answers no url at all without a related resource', function (): void {
    [$entityManager, $project, $task] = mrrUnitRecords();

    $probe = mrrUnitProbe($entityManager, RelatedlessProbe::class, $project);

    expect($probe->getDefaultActionUrl(CreateAction::make()))->toBeNull()
        ->and($probe->getDefaultActionUrl(EditAction::make()->record($task)))->toBeNull();
});

it('answers no url for an action naming another record class', function (): void {
    [$entityManager, $project] = mrrUnitRecords();

    // The action carries a project, which the related resource does not speak
    // for — its edit page would key a record it cannot answer.
    expect(mrrUnitProbe($entityManager, NestedRelatedProbe::class, $project)->getDefaultActionUrl(EditAction::make()->record($project)))
        ->toBeNull();
});

it('answers no url for a page the related resource does not register', function (): void {
    [$entityManager, $project, $task] = mrrUnitRecords();

    // The related resource registers create and edit and nothing else, so the
    // view arm has no page to point at.
    expect(mrrUnitProbe($entityManager, NestedRelatedProbe::class, $project)->getDefaultActionUrl(ViewAction::make()->record($task)))
        ->toBeNull();
});
