<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\CurrentPanel;
use Sylament\Panels\PanelRegistry;
use Sylament\Panels\Resources\Pages\PageRegistration;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Resources\ResourceRegistry;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Project;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Task;
use Sylament\Panels\Tests\Fixtures\Resources\MrrTaskResource;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateMrrTask;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditMrrProject;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditMrrTask;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ManageMrrProjectTasks;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\State\PropertyAccessor;
use Sylament\Schemas\State\RecordTypeReader;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * Kernel reboot is disabled throughout, for the reason every other page suite
 * disables it: the fixture database lives in memory on a container service, so
 * a reboot between two requests would hand the second one a freshly-migrated,
 * empty one.
 *
 * The two resources at the bottom are declared here rather than under
 * `tests/Fixtures/`: they mount no routes of their own and exist only to key a
 * manage page wrongly, which the Kernel's PSR-4 glob would register as a real
 * resource of the panel.
 */

/**
 * Two projects, two tasks on the first and one on the second — so a scoping
 * miss shows up as a third row.
 *
 * @return array{KernelBrowser, EntityManagerInterface, Project, Project}
 */
function manageRelatedClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    $project = new Project()->setName('Apollo');
    $other = new Project()->setName('Gemini');

    $entityManager->persist($project);
    $entityManager->persist($other);
    $entityManager->persist(new Task()->setTitle('Alpha')->setPosition(1)->setProject($project));
    $entityManager->persist(new Task()->setTitle('Beta')->setPosition(2)->setProject($project));
    $entityManager->persist(new Task()->setTitle('Delta')->setPosition(1)->setProject($other));
    $entityManager->flush();

    return [$client, $entityManager, $project, $other];
}

function manageRelatedTaskKey(EntityManagerInterface $entityManager, string $title): string
{
    $task = $entityManager->getRepository(Task::class)->findOneBy(['title' => $title]);

    expect($task)->toBeInstanceOf(Task::class);

    return (string) $task?->getId();
}

/**
 * A resource read outside a kernel request resolves the *default* panel, so a
 * case reading one in-process stands a request carrying this panel up first.
 */
function pushManagePanelRequest(): void
{
    $request = new Request();
    $request->attributes->set('_sylament_panel', 'mrr');

    test()->getContainer()->get(RequestStack::class)->push($request);
}

it('serves the manage page with the related resource table', function (): void {
    [$client, , $project] = manageRelatedClient();

    $crawler = $client->request('GET', sprintf('/mrr/projects/%s/tasks', $project->getId()));

    $rows = $crawler->filter('tbody .sy-ta-row');
    $rowText = implode(' ', $rows->each(fn (Crawler $row): string => $row->text()));

    // The columns are the related resource's, stamped on by the trait; the rows
    // are the owner's tasks alone — the other project's `Delta` is the scoping
    // miss.
    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('.sy-pa-page.sy-pa-resource-manage-related-records-page')->count())->toBe(1)
        ->and($crawler->filter('h1.sy-pa-header-heading')->text())->toBe('Manage Apollo Tasks')
        ->and($crawler->filter('.sy-pa-resource-manage-related-records-page > .sy-pa-page-header-main-ctn > .sy-pa-page-main > .sy-pa-page-content > .sy-ta')->count())->toBe(1)
        ->and($rows->count())->toBe(2)
        ->and($rowText)->toContain('Alpha')
        ->and($rowText)->toContain('Beta')
        ->and($rowText)->not->toContain('Delta');
});

it('ends the trail with the relationship title', function (): void {
    [$client, , $project] = manageRelatedClient();

    $crawler = $client->request('GET', sprintf('/mrr/projects/%s/tasks', $project->getId()));
    $items = $crawler->filter('.sy-breadcrumbs .sy-breadcrumbs-item');

    expect($items->last()->text())->toBe('Tasks')
        ->and($items->last()->filter('[aria-current="page"]')->count())->toBe(1);
});

it('links the header create action at the related resource create page', function (): void {
    [$client, , $project] = manageRelatedClient();

    $crawler = $client->request('GET', sprintf('/mrr/projects/%s/tasks', $project->getId()));
    $create = $crawler->filter('.sy-ta-header-actions a');

    expect($create->count())->toBe(1)
        ->and($create->attr('href'))->toEndWith(sprintf('/mrr/projects/%s/tasks/create', $project->getId()))
        ->and($create->attr('data-live-action-param'))->toBeNull();
});

it('links each row at the related resource edit page under the same owner', function (): void {
    [$client, $entityManager, $project] = manageRelatedClient();

    $crawler = $client->request('GET', sprintf('/mrr/projects/%s/tasks', $project->getId()));
    $rows = $crawler->filter('tbody .sy-ta-row');

    expect($rows->count())->toBe(2);

    foreach ($rows as $row) {
        $rowCrawler = new Crawler($row);
        $key = manageRelatedTaskKey($entityManager, $rowCrawler->filter('.sy-ta-cell')->first()->text());
        $actions = $rowCrawler->filter('.sy-ta-actions');

        // Exactly one anchor per row: the hoisted url arm answers for the edit
        // action and declines for the delete, which keeps its mounting binding.
        expect($actions->filter('a')->count())->toBe(1)
            ->and($actions->filter('a')->attr('href'))->toEndWith(sprintf('/mrr/projects/%s/tasks/%s/edit', $project->getId(), $key))
            ->and($actions->filter('button')->attr('data-live-action-param'))->toBe('mountAction');
    }
});

it('deletes a listed record through a table-context action', function (): void {
    [$client, $entityManager, $project] = manageRelatedClient();

    $taskKey = manageRelatedTaskKey($entityManager, 'Beta');

    $component = $this->createLiveComponent(ManageMrrProjectTasks::class, [
        'record' => (string) $project->getId(),
        'panelId' => 'mrr',
    ], client: $client)
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'recordKey' => $taskKey]])
        ->call('callMountedAction');

    $entityManager->clear();

    expect($component->component()->mountedActions)->toBe([])
        ->and($entityManager->find(Task::class, (int) $taskKey))->toBeNull();
});

it('refuses a table-context action naming a record of another owner', function (): void {
    [$client, $entityManager, $project] = manageRelatedClient();

    $foreignKey = manageRelatedTaskKey($entityManager, 'Delta');

    $component = $this->createLiveComponent(ManageMrrProjectTasks::class, [
        'record' => (string) $project->getId(),
        'panelId' => 'mrr',
    ], client: $client)
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'recordKey' => $foreignKey]])
        ->call('callMountedAction');

    $entityManager->clear();

    // The descriptor's key carries no checksum, so the relationship-scoped table
    // is what refuses a task of the other project: the frame never resolves, so
    // it unmounts unopened and the record it named is still there.
    expect($component->component()->mountedActions)->toBe([])
        ->and($entityManager->find(Task::class, (int) $foreignKey))->toBeInstanceOf(Task::class);
});

it('keys only the owner records the relationship holds', function (): void {
    [$client, $entityManager, $project] = manageRelatedClient();

    $host = $this->createLiveComponent(ManageMrrProjectTasks::class, [
        'record' => (string) $project->getId(),
        'panelId' => 'mrr',
    ], client: $client)->component();

    // The refusal above, at the seam it happens on — a mounted frame's key is
    // looked up through the relationship-scoped table and nowhere else.
    expect($host->getTableRecord(manageRelatedTaskKey($entityManager, 'Delta')))->toBeNull()
        ->and($host->getTableRecord(manageRelatedTaskKey($entityManager, 'Alpha')))->toBeInstanceOf(Task::class);
});

it('draws the record band on both pages, active on the one it is asked from', function (string $path, string $activeLabel): void {
    [$client, , $project] = manageRelatedClient();

    $crawler = $client->request('GET', sprintf($path, $project->getId()));
    $items = $crawler->filter('.sy-pa-sub-navigation-sidebar-ctn a');
    $active = $crawler->filter('.sy-pa-sub-navigation-sidebar-ctn a[aria-current="page"]');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($items->count())->toBe(2)
        ->and($items->eq(0)->text())->toBe('Edit Mrr Project')
        ->and($items->eq(1)->text())->toBe('Tasks')
        ->and($active->count())->toBe(1)
        ->and($active->text())->toBe($activeLabel);
})->with([
    ['/mrr/projects/%s/edit', 'Edit Mrr Project'],
    ['/mrr/projects/%s/tasks', 'Tasks'],
]);

it('lists a nested resource through the manage page keyed by its relationship', function (): void {
    [, , $project] = manageRelatedClient();

    pushManagePanelRequest();

    $resource = test()->getContainer()->get(ResourceRegistry::class)->get(MrrTaskResource::class);

    // Arm 1: the parent registers a page under the kebab-cased relationship
    // name, so that page — never an index of the nested resource's own — is
    // what its records are listed through.
    expect($resource->getIndexUrl(['project' => $project]))
        ->toBe(sprintf('http://localhost/mrr/projects/%s/tasks', $project->getId()));
});

it('falls past a manage page the parent keys by anything else', function (): void {
    [, , $project] = manageRelatedClient();

    pushManagePanelRequest();

    $registry = misKeyedRegistry();

    // The manage page is registered under `manage-tasks` rather than `tasks`, so
    // arm 1 finds nothing and the parent's own record page answers instead.
    expect($registry->get(MisKeyedTaskResource::class)->getIndexUrl(['project' => $project]))
        ->toBe(sprintf('http://localhost/mrr/projects/%s/edit', $project->getId()));
});

/**
 * The same nesting with the manage page keyed wrongly. It reuses the panel's
 * own slugs, so every URL it mints names a route the loader really mounted.
 */
final class MisKeyedProjectResource extends Resource
{
    protected static ?string $recordClass = Project::class;

    protected static ?string $slug = 'projects';

    protected static ?string $recordTitleAttribute = 'name';

    public function getPages(): array
    {
        return [
            'edit' => new PageRegistration(EditMrrProject::class, '/{record}/edit'),
            'manage-tasks' => new PageRegistration(ManageMrrProjectTasks::class, '/{record}/tasks'),
        ];
    }
}

final class MisKeyedTaskResource extends Resource
{
    protected static ?string $recordClass = Task::class;

    protected static ?string $slug = 'tasks';

    protected static ?string $parentResource = MisKeyedProjectResource::class;

    public function getPages(): array
    {
        return [
            'create' => new PageRegistration(CreateMrrTask::class, '/create'),
            'edit' => new PageRegistration(EditMrrTask::class, '/{record}/edit'),
        ];
    }
}

function misKeyedRegistry(): ResourceRegistry
{
    $container = test()->getContainer();

    $resources = [
        MisKeyedProjectResource::class => new MisKeyedProjectResource(),
        MisKeyedTaskResource::class => new MisKeyedTaskResource(),
    ];

    $registry = new ResourceRegistry(new ServiceLocator(array_map(
        static fn (Resource $resource): Closure => static fn (): Resource => $resource,
        $resources,
    )));

    $propertyAccessor = new PropertyAccessor(PropertyAccess::createPropertyAccessor(), new RecordTypeReader());
    $entityManager = $container->get(EntityManagerInterface::class);

    foreach ($resources as $resource) {
        $resource->setResourceServices(
            $entityManager,
            $container->get(UrlGeneratorInterface::class),
            $container->get(CurrentPanel::class),
            $container->get(PanelRegistry::class),
            $propertyAccessor,
            new AssociationReader($entityManager, $propertyAccessor),
            $registry,
        );
    }

    return $registry;
}
