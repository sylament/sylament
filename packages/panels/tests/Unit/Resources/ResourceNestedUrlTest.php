<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Sylament\Panels\CurrentPanel;
use Sylament\Panels\Panel;
use Sylament\Panels\PanelRegistry;
use Sylament\Panels\Resources\Pages\PageRegistration;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Resources\ResourceRegistry;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Anthology;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Chapter;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Line;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Poem;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Verse;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\NestedPanelProvider;
use Sylament\Panels\Tests\Fixtures\Resources\AnthologyResource;
use Sylament\Panels\Tests\Fixtures\Resources\ChapterResource;
use Sylament\Panels\Tests\Fixtures\Resources\LineResource;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditChapter;
use Sylament\Panels\Tests\Fixtures\Resources\PoemResource;
use Sylament\Panels\Tests\Fixtures\Resources\VerseResource;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\State\PropertyAccessor;
use Sylament\Schemas\State\RecordTypeReader;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/*
 * The routes here are hand-built rather than loaded: nesting the paths under
 * their parents is the routing task's, and pinning the generated URL now is
 * what lets that task be judged against a shape already agreed on.
 */

/**
 * A parent whose pages register the child relationship under its own key — the
 * first arm an index URL tries.
 */
final class RelationPageParentResource extends Resource
{
    protected static ?string $recordClass = Chapter::class;

    protected static ?string $slug = 'relation-page-parents';

    public function getPages(): array
    {
        return [
            'verses' => new PageRegistration(EditChapter::class, '/{record}/verses'),
            'edit' => new PageRegistration(EditChapter::class, '/{record}/edit'),
        ];
    }
}

final class RelationPageChildResource extends Resource
{
    protected static ?string $recordClass = Verse::class;

    protected static ?string $parentResource = RelationPageParentResource::class;
}

/**
 * A parent with a record page but none keyed by the relationship: the view page
 * is where its children are shown.
 */
final class ViewPageParentResource extends Resource
{
    protected static ?string $recordClass = Chapter::class;

    protected static ?string $slug = 'view-page-parents';

    public function getPages(): array
    {
        return [
            'view' => new PageRegistration(EditChapter::class, '/{record}'),
            'edit' => new PageRegistration(EditChapter::class, '/{record}/edit'),
        ];
    }
}

final class ViewPageChildResource extends Resource
{
    protected static ?string $recordClass = Verse::class;

    protected static ?string $parentResource = ViewPageParentResource::class;
}

/**
 * A parent with an index page and no record page at all — the last arm, which
 * drops the parent key it has nowhere to put.
 */
final class IndexPageParentResource extends Resource
{
    protected static ?string $recordClass = Chapter::class;

    protected static ?string $slug = 'index-page-parents';

    public function getPages(): array
    {
        return [
            'index' => new PageRegistration(EditChapter::class, '/'),
        ];
    }
}

final class IndexPageChildResource extends Resource
{
    protected static ?string $recordClass = Verse::class;

    protected static ?string $parentResource = IndexPageParentResource::class;
}

function panelsNestedUrlRoutes(): RouteCollection
{
    $routes = new RouteCollection();

    foreach ([
        'sylament.nest.resources.chapters.index' => '/nest/chapters',
        'sylament.nest.resources.chapters.edit' => '/nest/chapters/{record}/edit',
        'sylament.nest.resources.chapters.verses.create' => '/nest/chapters/{chapter}/verses/create',
        'sylament.nest.resources.chapters.verses.edit' => '/nest/chapters/{chapter}/verses/{record}/edit',
        'sylament.nest.resources.chapters.verses.lines.index' => '/nest/chapters/{chapter}/verses/{verse}/lines',
        'sylament.nest.resources.chapters.verses.lines.edit' => '/nest/chapters/{chapter}/verses/{verse}/lines/{record}/edit',
        'sylament.nest.resources.anthologies.poems.edit' => '/nest/anthologies/{anthologies}/poems/{record}/edit',
        'sylament.nest.resources.relation-page-parents.verses' => '/nest/relation-page-parents/{record}/verses',
        'sylament.nest.resources.relation-page-parents.edit' => '/nest/relation-page-parents/{record}/edit',
        'sylament.nest.resources.view-page-parents.view' => '/nest/view-page-parents/{record}',
        'sylament.nest.resources.view-page-parents.edit' => '/nest/view-page-parents/{record}/edit',
        'sylament.nest.resources.index-page-parents.index' => '/nest/index-page-parents',
    ] as $name => $path) {
        $routes->add($name, new Route($path));
    }

    return $routes;
}

function panelsNestedUrlRegistry(EntityManagerInterface $entityManager): ResourceRegistry
{
    $resources = [
        ChapterResource::class => new ChapterResource(),
        VerseResource::class => new VerseResource(),
        LineResource::class => new LineResource(),
        AnthologyResource::class => new AnthologyResource(),
        PoemResource::class => new PoemResource(),
        RelationPageParentResource::class => new RelationPageParentResource(),
        RelationPageChildResource::class => new RelationPageChildResource(),
        ViewPageParentResource::class => new ViewPageParentResource(),
        ViewPageChildResource::class => new ViewPageChildResource(),
        IndexPageParentResource::class => new IndexPageParentResource(),
        IndexPageChildResource::class => new IndexPageChildResource(),
    ];

    $registry = new ResourceRegistry(new ServiceLocator(array_map(
        fn (Resource $resource): Closure => fn (): Resource => $resource,
        $resources,
    )));

    $panels = new PanelRegistry([new NestedPanelProvider()]);
    $propertyAccessor = new PropertyAccessor(PropertyAccess::createPropertyAccessor(), new RecordTypeReader());
    $urlGenerator = new UrlGenerator(panelsNestedUrlRoutes(), new RequestContext());

    foreach ($resources as $resource) {
        $resource->setResourceServices(
            $entityManager,
            $urlGenerator,
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
 * One chapter, one of its verses and one of that verse's lines, flushed so each
 * carries the identifier a URL keys it by.
 *
 * @return array{Chapter, Verse, Line}
 */
function panelsNestedUrlRecords(EntityManagerInterface $entityManager): array
{
    $chapter = new Chapter()->setTitle('Genesis');
    $verse = new Verse()->setHeading('In the beginning')->setChapter($chapter);
    $line = new Line()->setContent('Let there be light')->setVerse($verse);

    $entityManager->persist($chapter);
    $entityManager->persist($verse);
    $entityManager->persist($line);
    $entityManager->flush();

    return [$chapter, $verse, $line];
}

it('composes a nested route base name off the parent and the relationship', function (): void {
    $panel = Panel::make()->id('nest');
    $registry = panelsNestedUrlRegistry(EntityManagerFactory::create());

    expect($registry->get(ChapterResource::class)->getRouteBaseName($panel))->toBe('sylament.nest.resources.chapters')
        ->and($registry->get(VerseResource::class)->getRouteBaseName($panel))->toBe('sylament.nest.resources.chapters.verses')
        ->and($registry->get(LineResource::class)->getRouteBaseName($panel))->toBe('sylament.nest.resources.chapters.verses.lines');
});

it('keys every ancestor record a caller passes as an object', function (): void {
    $panel = Panel::make()->id('nest');
    $entityManager = EntityManagerFactory::create();
    $registry = panelsNestedUrlRegistry($entityManager);
    [$chapter, $verse] = panelsNestedUrlRecords($entityManager);

    expect($registry->get(VerseResource::class)->getUrl('edit', ['record' => $verse, 'chapter' => $chapter], isAbsolute: false, panel: $panel))
        ->toBe(sprintf('/nest/chapters/%s/verses/%s/edit', $chapter->getId(), $verse->getId()));
});

it('passes string keys through untouched', function (): void {
    $panel = Panel::make()->id('nest');
    $registry = panelsNestedUrlRegistry(EntityManagerFactory::create());

    expect($registry->get(VerseResource::class)->getUrl('edit', ['record' => '7', 'chapter' => '3'], isAbsolute: false, panel: $panel))
        ->toBe('/nest/chapters/3/verses/7/edit');
});

it('refuses to key an ancestor record of the wrong class', function (): void {
    $panel = Panel::make()->id('nest');
    $entityManager = EntityManagerFactory::create();
    $registry = panelsNestedUrlRegistry($entityManager);
    [, $verse] = panelsNestedUrlRecords($entityManager);

    $registry->get(VerseResource::class)->getUrl('edit', ['record' => $verse, 'chapter' => $verse], isAbsolute: false, panel: $panel);
})->throws(LogicException::class, 'it cannot key a');

it('guesses the parent key off the record it was handed', function (): void {
    $panel = Panel::make()->id('nest');
    $entityManager = EntityManagerFactory::create();
    $registry = panelsNestedUrlRegistry($entityManager);
    [$chapter, $verse] = panelsNestedUrlRecords($entityManager);

    expect($registry->get(VerseResource::class)->getUrl('edit', ['record' => $verse], isAbsolute: false, panel: $panel, shouldGuessMissingParameters: true))
        ->toBe(sprintf('/nest/chapters/%s/verses/%s/edit', $chapter->getId(), $verse->getId()));
});

it('guesses both ancestors of a depth-two record', function (): void {
    $panel = Panel::make()->id('nest');
    $entityManager = EntityManagerFactory::create();
    $registry = panelsNestedUrlRegistry($entityManager);
    [$chapter, $verse, $line] = panelsNestedUrlRecords($entityManager);

    expect($registry->get(LineResource::class)->getUrl('edit', ['record' => $line], isAbsolute: false, panel: $panel, shouldGuessMissingParameters: true))
        ->toBe(sprintf('/nest/chapters/%s/verses/%s/lines/%s/edit', $chapter->getId(), $verse->getId(), $line->getId()));
});

it('guesses the remaining ancestor from an ancestor the caller passed', function (): void {
    $panel = Panel::make()->id('nest');
    $entityManager = EntityManagerFactory::create();
    $registry = panelsNestedUrlRegistry($entityManager);
    [$chapter, $verse] = panelsNestedUrlRecords($entityManager);

    expect($registry->get(LineResource::class)->getUrl('index', ['verse' => $verse], isAbsolute: false, panel: $panel, shouldGuessMissingParameters: true))
        ->toBe(sprintf('/nest/chapters/%s/verses/%s/lines', $chapter->getId(), $verse->getId()));
});

it('guesses past an ancestor the caller passed as a key rather than a record', function (): void {
    $panel = Panel::make()->id('nest');
    $entityManager = EntityManagerFactory::create();
    $registry = panelsNestedUrlRegistry($entityManager);
    [$chapter, $verse, $line] = panelsNestedUrlRecords($entityManager);

    expect($registry->get(LineResource::class)->getUrl('edit', ['record' => $line, 'verse' => (string) $verse->getId()], isAbsolute: false, panel: $panel, shouldGuessMissingParameters: true))
        ->toBe(sprintf('/nest/chapters/%s/verses/%s/lines/%s/edit', $chapter->getId(), $verse->getId(), $line->getId()));
});

it('cannot guess a many-to-many ancestor, leaving the generator to refuse it', function (): void {
    $panel = Panel::make()->id('nest');
    $entityManager = EntityManagerFactory::create();
    $registry = panelsNestedUrlRegistry($entityManager);

    $anthology = new Anthology()->setTitle('Georgics');
    $poem = new Poem()->setTitle('Book I')->addAnthology($anthology);

    $entityManager->persist($anthology);
    $entityManager->persist($poem);
    $entityManager->flush();

    $registry->get(PoemResource::class)->getUrl('edit', ['record' => $poem], isAbsolute: false, panel: $panel, shouldGuessMissingParameters: true);
})->throws(MissingMandatoryParametersException::class);

it('leaves a missing ancestor to the generator when there is no record to guess from', function (): void {
    $panel = Panel::make()->id('nest');
    $registry = panelsNestedUrlRegistry(EntityManagerFactory::create());

    $registry->get(VerseResource::class)->getUrl('edit', ['record' => '7'], isAbsolute: false, panel: $panel, shouldGuessMissingParameters: true);
})->throws(MissingMandatoryParametersException::class);

it('does not guess at all unless asked to', function (): void {
    $panel = Panel::make()->id('nest');
    $entityManager = EntityManagerFactory::create();
    $registry = panelsNestedUrlRegistry($entityManager);
    [, $verse] = panelsNestedUrlRecords($entityManager);

    $registry->get(VerseResource::class)->getUrl('edit', ['record' => $verse], isAbsolute: false, panel: $panel);
})->throws(MissingMandatoryParametersException::class);

it('sends a nested index url to the parent page keyed by the relationship', function (): void {
    $panel = Panel::make()->id('nest');
    $entityManager = EntityManagerFactory::create();
    $registry = panelsNestedUrlRegistry($entityManager);
    [$chapter] = panelsNestedUrlRecords($entityManager);

    expect($registry->get(RelationPageChildResource::class)->getIndexUrl(['chapter' => $chapter], isAbsolute: false, panel: $panel))
        ->toBe(sprintf('/nest/relation-page-parents/%s/verses', $chapter->getId()));
});

it('falls back to the parent view page', function (): void {
    $panel = Panel::make()->id('nest');
    $entityManager = EntityManagerFactory::create();
    $registry = panelsNestedUrlRegistry($entityManager);
    [$chapter] = panelsNestedUrlRecords($entityManager);

    expect($registry->get(ViewPageChildResource::class)->getIndexUrl(['chapter' => $chapter], isAbsolute: false, panel: $panel))
        ->toBe(sprintf('/nest/view-page-parents/%s', $chapter->getId()));
});

it('falls back to the parent edit page', function (): void {
    $panel = Panel::make()->id('nest');
    $entityManager = EntityManagerFactory::create();
    $registry = panelsNestedUrlRegistry($entityManager);
    [$chapter] = panelsNestedUrlRecords($entityManager);

    expect($registry->get(VerseResource::class)->getIndexUrl(['chapter' => $chapter], isAbsolute: false, panel: $panel))
        ->toBe(sprintf('/nest/chapters/%s/edit', $chapter->getId()));
});

it('falls back to the parent index page, which takes no parent key', function (): void {
    $panel = Panel::make()->id('nest');
    $entityManager = EntityManagerFactory::create();
    $registry = panelsNestedUrlRegistry($entityManager);
    [$chapter] = panelsNestedUrlRecords($entityManager);

    expect($registry->get(IndexPageChildResource::class)->getIndexUrl(['chapter' => $chapter], isAbsolute: false, panel: $panel))
        ->toBe('/nest/index-page-parents');
});

it('names the record parameter when a nested index url is asked for without its parent', function (): void {
    $panel = Panel::make()->id('nest');
    $registry = panelsNestedUrlRegistry(EntityManagerFactory::create());

    // The parent key is left out rather than passed as null, so the generator
    // refuses a *missing* record rather than an invalid one — the difference
    // between a legible failure and an opaque one.
    expect(fn (): string => $registry->get(VerseResource::class)->getIndexUrl(isAbsolute: false, panel: $panel))
        ->toThrow(MissingMandatoryParametersException::class, 'record');
});

it('spends a scalar parent key on the parent page rather than trailing it as a query string', function (): void {
    $panel = Panel::make()->id('nest');
    $registry = panelsNestedUrlRegistry(EntityManagerFactory::create());

    expect($registry->get(VerseResource::class)->getIndexUrl(['chapter' => '3'], isAbsolute: false, panel: $panel))
        ->toBe('/nest/chapters/3/edit')
        ->and($registry->get(IndexPageChildResource::class)->getIndexUrl(['chapter' => '3'], isAbsolute: false, panel: $panel))
        ->toBe('/nest/index-page-parents');
});

it('resolves a record binding through the query the modifier returns', function (): void {
    $entityManager = EntityManagerFactory::create();
    $registry = panelsNestedUrlRegistry($entityManager);
    [$chapter, $verse] = panelsNestedUrlRecords($entityManager);

    $foreignChapter = new Chapter()->setTitle('Exodus');
    $foreignVerse = new Verse()->setHeading('Foreign')->setChapter($foreignChapter);

    EntityManagerFactory::seed($entityManager, $foreignChapter, $foreignVerse);

    $resource = $registry->get(VerseResource::class);
    $scope = fn (QueryBuilder $query): QueryBuilder => $resource->getParentScopedRecordQuery($chapter);

    expect($resource->resolveRecordRouteBinding((int) $verse->getId(), $scope)?->getId())->toBe($verse->getId())
        ->and($resource->resolveRecordRouteBinding((int) $foreignVerse->getId(), $scope))->toBeNull()
        ->and($resource->resolveRecordRouteBinding((int) $foreignVerse->getId())?->getId())->toBe($foreignVerse->getId());
});

it('keeps the record query when the modifier returns nothing', function (): void {
    $entityManager = EntityManagerFactory::create();
    $registry = panelsNestedUrlRegistry($entityManager);
    [, $verse] = panelsNestedUrlRecords($entityManager);

    $resource = $registry->get(VerseResource::class);
    $binding = $resource->resolveRecordRouteBinding((int) $verse->getId(), function (QueryBuilder $query): void {
        $query->setMaxResults(1);
    });

    expect($binding?->getId())->toBe($verse->getId());
});

it('refuses a modifier that answers with something other than a query', function (): void {
    $entityManager = EntityManagerFactory::create();
    $registry = panelsNestedUrlRegistry($entityManager);
    [, $verse] = panelsNestedUrlRecords($entityManager);

    $registry->get(VerseResource::class)->resolveRecordRouteBinding((int) $verse->getId(), fn (): string => 'not a query');
})->throws(LogicException::class, 'must return a query builder or null');
