<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;
use Sylament\Panels\CurrentPanel;
use Sylament\Panels\Pages\Enums\SubNavigationPosition;
use Sylament\Panels\PanelRegistry;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Resources\ResourceRegistry;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Article;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Catalogue;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Colophon;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Edition;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Imprint;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Stamp;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Doctrine\RecordingMiddleware;
use Sylament\Panels\Tests\Fixtures\Resources\ArticleResource;
use Sylament\Panels\Tests\Fixtures\Resources\CatalogueResource;
use Sylament\Panels\Tests\Fixtures\Resources\ChapterResource;
use Sylament\Panels\Tests\Fixtures\Resources\ColophonResource;
use Sylament\Panels\Tests\Fixtures\Resources\EditionResource;
use Sylament\Panels\Tests\Fixtures\Resources\ImprintByLabelResource;
use Sylament\Panels\Tests\Fixtures\Resources\ImprintResource;
use Sylament\Panels\Tests\Fixtures\Resources\StampResource;
use Sylament\Panels\Tests\Fixtures\Resources\VerseResource;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\State\PropertyAccessor;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

function panelsArticleResource(): ArticleResource
{
    $registry = test()->getContainer()->get(ResourceRegistry::class);

    return $registry->get(ArticleResource::class);
}

/**
 * Hands a hand-built resource the set `ResourceRegistry` would have autowired,
 * so a one-off subclass can be exercised without registering a fixture service.
 */
function panelsWireResource(Resource $resource): Resource
{
    $container = test()->getContainer();

    $entityManager = $container->get(EntityManagerInterface::class);
    $urlGenerator = $container->get(UrlGeneratorInterface::class);
    $currentPanel = $container->get(CurrentPanel::class);
    $panels = $container->get(PanelRegistry::class);
    $propertyAccessor = $container->get(PropertyAccessor::class);
    $associationReader = $container->get(AssociationReader::class);
    $resources = $container->get(ResourceRegistry::class);

    expect($entityManager)->toBeInstanceOf(EntityManagerInterface::class)
        ->and($urlGenerator)->toBeInstanceOf(UrlGeneratorInterface::class)
        ->and($currentPanel)->toBeInstanceOf(CurrentPanel::class)
        ->and($panels)->toBeInstanceOf(PanelRegistry::class)
        ->and($propertyAccessor)->toBeInstanceOf(PropertyAccessor::class)
        ->and($associationReader)->toBeInstanceOf(AssociationReader::class)
        ->and($resources)->toBeInstanceOf(ResourceRegistry::class);

    /**
     * @var EntityManagerInterface $entityManager
     * @var UrlGeneratorInterface  $urlGenerator
     * @var CurrentPanel           $currentPanel
     * @var PanelRegistry          $panels
     * @var PropertyAccessor       $propertyAccessor
     * @var AssociationReader      $associationReader
     * @var ResourceRegistry       $resources
     */
    $resource->setResourceServices($entityManager, $urlGenerator, $currentPanel, $panels, $propertyAccessor, $associationReader, $resources);

    return $resource;
}

/**
 * @param class-string<Resource> $class
 */
function compositeSeamResource(string $class): Resource
{
    $registry = test()->getContainer()->get(ResourceRegistry::class);

    return $registry->get($class);
}

/**
 * The composite fixture family, seeded and then detached — `seed()` clears the
 * identity map, so a record a case reads back is hydrated from the database,
 * which is the only way the converting code column reads upper case.
 */
function compositeSeamSeed(EntityManagerInterface $entityManager): void
{
    $catalogue = new Catalogue()->setTitle('First');

    EntityManagerFactory::seed(
        $entityManager,
        $catalogue,
        new Catalogue()->setTitle('Second'),
        new Stamp()->setCatalogue($catalogue)->setCode('A/B C')->setCaption('Alpha'),
        new Stamp()->setCatalogue($catalogue)->setCode('en-gb')->setCaption('Beta'),
        new Colophon()->setCatalogue($catalogue)->setNote('Note'),
        new Imprint()->setHouse('www.example.com')->setYear('/blog 2026')->setLabel('Escaped')->setCatalogue($catalogue),
        new Imprint()->setHouse('plain')->setYear('about')->setLabel('Plain'),
        new Imprint()->setHouse('a~b')->setYear('c=d ž')->setLabel('Exotic'),
        new Edition()->setId(5)->setLocale('en-GB')->setTitle('Fifth'),
    );
}

/**
 * @param class-string         $class
 * @param array<string, mixed> $criteria
 */
function compositeSeamRecord(EntityManagerInterface $entityManager, string $class, array $criteria): object
{
    $record = $entityManager->getRepository($class)->findOneBy($criteria);

    expect($record)->toBeInstanceOf($class);
    assert(is_object($record));

    return $record;
}

it('reads a record title through the injected property accessor', function (): void {
    $article = new Article()->setTitle('Hello')->setSlug('hello');

    expect(panelsArticleResource()->getRecordTitle($article))->toBe('Hello');
});

it('falls back to the record label when the title attribute is unreadable', function (): void {
    expect(panelsArticleResource()->getRecordTitle(new stdClass()))->toBe('article');
});

it('falls back to the record label when the title attribute reads blank', function (): void {
    $resource = panelsArticleResource();

    // A boolean is filled to `blank()`, so only the cast the guard applies first
    // turns `false` into the empty string that routes to the label.
    $falseTitled = new class {
        public bool $title = false;
    };

    expect($resource->getRecordTitle(new Article()->setTitle('')))->toBe('article')
        ->and($resource->getRecordTitle(new Article()->setTitle('   ')))->toBe('article')
        ->and($resource->getRecordTitle($falseTitled))->toBe('article');
});

it('reads the record route key off the single identifier', function (): void {
    $resource = panelsArticleResource();
    $entityManager = $resource->getRecordQuery()->getEntityManager();
    $article = new Article()->setTitle('Hello')->setSlug('hello');

    $entityManager->persist($article);
    $entityManager->flush();

    expect($resource->getRecordRouteKey($article))->toBe((string) $article->getId())
        ->and($resource->resolveRecordRouteBinding((int) $article->getId()))->toBe($article)
        ->and($resource->resolveRecordRouteBinding(404))->toBeNull();
});

it('keys and resolves a record by the declared route key name', function (): void {
    $resource = panelsWireResource(new class extends Resource {
        protected static ?string $recordClass = Article::class;

        protected static ?string $recordRouteKeyName = 'slug';
    });

    $entityManager = $resource->getRecordQuery()->getEntityManager();
    $article = new Article()->setTitle('Hello')->setSlug('hello-world');

    $entityManager->persist($article);
    $entityManager->flush();

    expect($resource->getRecordRouteKey($article))->toBe('hello-world')
        ->and($resource->resolveRecordRouteBinding('hello-world'))->toBe($article)
        ->and($resource->resolveRecordRouteBinding('no-such-slug'))->toBeNull();
});

it('refuses to key a record the resource does not speak for', function (): void {
    panelsArticleResource()->getRecordRouteKey(new stdClass());
})->throws(LogicException::class, 'it cannot key a [stdClass]');

/**
 * The registry a resource is injected with holds that very resource, so setter
 * injection behind the lazy locator is the only thing keeping the pair out of a
 * circular reference — this case is the proof it does.
 */
it('injects the resource registry a resource is itself registered in', function (): void {
    $registry = $this::getContainer()->get(ResourceRegistry::class);

    expect($registry)->toBeInstanceOf(ResourceRegistry::class);
    assert($registry instanceof ResourceRegistry);

    $verse = $registry->get(VerseResource::class);

    expect($verse)->toBeInstanceOf(VerseResource::class)
        ->and($verse->getParentResourceRegistration()?->parentResource)->toBe(ChapterResource::class)
        ->and($verse->getParentRelationshipName())->toBe('verses');
});

it('positions the sub-navigation band by its own static, else by the panel', function (): void {
    $positioned = new class extends Resource {
        protected static ?string $recordClass = Article::class;

        protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::End;
    };

    // The fixture panel names no position of its own, so a resource naming none
    // either answers the panel's own default.
    expect(panelsWireResource($positioned)->getSubNavigationPosition())->toBe(SubNavigationPosition::End)
        ->and(panelsArticleResource()->getSubNavigationPosition())->toBe(SubNavigationPosition::Start);
});

it('mints and resolves a scalar pair identifier through the escaped key', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    compositeSeamSeed($entityManager);

    $resource = compositeSeamResource(ImprintResource::class);
    $escaped = compositeSeamRecord($entityManager, Imprint::class, ['label' => 'Escaped']);
    $plain = compositeSeamRecord($entityManager, Imprint::class, ['label' => 'Plain']);
    $exotic = compositeSeamRecord($entityManager, Imprint::class, ['label' => 'Exotic']);

    expect($resource->getRecordRouteKey($escaped))->toBe('www=2Eexample=2Ecom~=2Fblog=202026')
        ->and($resource->getRecordRouteKey($plain))->toBe('plain~about')
        ->and($resource->getRecordRouteKey($exotic))->toBe('a=7Eb~c=3Dd=20=C5=BE')
        ->and($resource->resolveRecordRouteBinding('www=2Eexample=2Ecom~=2Fblog=202026'))->toBe($escaped)
        ->and($resource->resolveRecordRouteBinding('plain~about'))->toBe($plain)
        ->and($resource->resolveRecordRouteBinding('a=7Eb~c=3Dd=20=C5=BE'))->toBe($exotic);
});

it('mints a derived identity from the value the record holds, not the column', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    compositeSeamSeed($entityManager);

    $resource = compositeSeamResource(StampResource::class);
    $alpha = compositeSeamRecord($entityManager, Stamp::class, ['caption' => 'Alpha']);
    $beta = compositeSeamRecord($entityManager, Stamp::class, ['caption' => 'Beta']);

    expect($resource->getRecordRouteKey($alpha))->toBe('1~A=2FB=20C')
        ->and($resource->getRecordRouteKey($beta))->toBe('1~EN-GB')
        ->and($resource->resolveRecordRouteBinding('1~A=2FB=20C'))->toBe($alpha)
        ->and($resource->resolveRecordRouteBinding('1~EN-GB'))->toBe($beta)
        ->and($resource->resolveRecordRouteBinding('1~a=2Fb=20c'))->toBeNull();
});

it('mints and resolves a single derived identity', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    compositeSeamSeed($entityManager);

    $resource = compositeSeamResource(ColophonResource::class);
    $colophon = compositeSeamRecord($entityManager, Colophon::class, ['note' => 'Note']);

    expect($resource->getRecordRouteKey($colophon))->toBe('1')
        ->and($resource->resolveRecordRouteBinding('1'))->toBe($colophon)
        ->and($resource->resolveRecordRouteBinding('01'))->toBeNull();
});

it('mints and resolves an identifier one of whose fields is named id', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    compositeSeamSeed($entityManager);

    $resource = compositeSeamResource(EditionResource::class);
    $edition = compositeSeamRecord($entityManager, Edition::class, ['title' => 'Fifth']);

    // `5` is the partial key a literal `id` reading would spell, and it names
    // no record.
    expect($resource->getRecordRouteKey($edition))->toBe('5~en-GB')
        ->and($resource->resolveRecordRouteBinding('5~en-GB'))->toBe($edition)
        ->and($resource->resolveRecordRouteBinding('5'))->toBeNull();
});

it('answers null for a key a composite resource mints no record for', function (int|string $key): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    compositeSeamSeed($entityManager);

    expect(compositeSeamResource(ImprintResource::class)->resolveRecordRouteBinding($key))->toBeNull();
})->with([
    'one component where the identifier takes two' => 'plain',
    'three components where the identifier takes two' => 'plain~about~more',
    'an empty trailing component' => 'plain~',
    'an empty leading component' => '~about',
    'the empty string' => '',
    'a lower-case escape' => 'a=7eb~c=3Dd=20=C5=BE',
    'an escaped byte the grammar leaves alone' => '=70lain~about',
    'an int key, cast to a single component' => 404,
]);

it('answers null for a spelling of a single identifier no record mints', function (string $key): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    compositeSeamSeed($entityManager);

    expect(compositeSeamResource(CatalogueResource::class)->resolveRecordRouteBinding($key))->toBeNull();
})->with([
    'a zero-padded spelling' => '01',
    'a numeric head with a tail' => '1abc',
    'a non-numeric spelling' => 'abc',
    'a leading space' => ' 1',
    'a trailing space' => '1 ',
    'a trailing newline' => "1\n",
    'a signed spelling' => '+1',
    'a decimal spelling' => '1.0',
    'a value past the integer range' => '99999999999999999999',
]);

it('resolves the canonical spelling of a single identifier, as a string and as an int', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    compositeSeamSeed($entityManager);

    $resource = compositeSeamResource(CatalogueResource::class);
    $catalogue = compositeSeamRecord($entityManager, Catalogue::class, ['title' => 'First']);

    expect($resource->getRecordRouteKey($catalogue))->toBe('1')
        ->and($resource->resolveRecordRouteBinding('1'))->toBe($catalogue)
        ->and($resource->resolveRecordRouteBinding(1))->toBe($catalogue);
});

it('runs no statement for a key that names no record', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $recorder = $this::getContainer()->get(RecordingMiddleware::class);
    assert($recorder instanceof RecordingMiddleware);
    compositeSeamSeed($entityManager);

    $resource = compositeSeamResource(ImprintResource::class);

    $recorder->reset();

    expect($resource->resolveRecordRouteBinding('plain'))->toBeNull()
        ->and($recorder->statements())->toBe([]);

    $recorder->reset();

    expect($resource->resolveRecordRouteBinding('plain~about'))->toBeInstanceOf(Imprint::class)
        ->and($recorder->statements())->toHaveCount(1);

    // The single-identifier arm is the one M1 moved, and it is the one SQLite
    // would answer null for either way: `05` aliases the record under a raw
    // bind and `abc` matches nothing at all, so the pin is that neither
    // reaches the database.
    $catalogues = compositeSeamResource(CatalogueResource::class);

    $recorder->reset();

    expect($catalogues->resolveRecordRouteBinding('05'))->toBeNull()
        ->and($recorder->statements())->toBe([]);

    $recorder->reset();

    expect($catalogues->resolveRecordRouteBinding('abc'))->toBeNull()
        ->and($recorder->statements())->toBe([]);

    $recorder->reset();

    expect($catalogues->resolveRecordRouteBinding('1'))->toBeInstanceOf(Catalogue::class)
        ->and($recorder->statements())->toHaveCount(1);
});

it('answers the codec refusal for a record whose identifier is unset', function (): void {
    compositeSeamResource(CatalogueResource::class)->getRecordRouteKey(new Catalogue());
})->throws(LogicException::class, 'Object records must expose a readable non-blank identifier; [Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Catalogue] has none set.');

it('answers the codec refusal for an identifier component that is blank', function (): void {
    compositeSeamResource(ImprintResource::class)->getRecordRouteKey(new Imprint());
})->throws(LogicException::class, 'The identifier of [Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Imprint] is not a printable value.');

it('keys a record by the class the resource declares, not the class the record is', function (): void {
    $resource = compositeSeamResource(ImprintResource::class);
    $double = new CompositeSeamImprintDouble()->setHouse('plain')->setYear('about');

    expect($resource->getRecordRouteKey($double))->toBe('plain~about');
});

it('resolves a composite record only under the parent scope the modifier narrows to', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    compositeSeamSeed($entityManager);

    $resource = compositeSeamResource(StampResource::class);
    $alpha = compositeSeamRecord($entityManager, Stamp::class, ['caption' => 'Alpha']);
    $own = compositeSeamRecord($entityManager, Catalogue::class, ['title' => 'First']);
    $foreign = compositeSeamRecord($entityManager, Catalogue::class, ['title' => 'Second']);

    expect($resource->resolveRecordRouteBinding('1~A=2FB=20C', fn (QueryBuilder $query): QueryBuilder => $resource->getParentScopedRecordQuery($own)))->toBe($alpha)
        ->and($resource->resolveRecordRouteBinding('1~A=2FB=20C', fn (QueryBuilder $query): QueryBuilder => $resource->getParentScopedRecordQuery($foreign)))->toBeNull();
});

it('decodes against the declared class even when the modifier re-roots the query', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    compositeSeamSeed($entityManager);

    compositeSeamResource(CatalogueResource::class)->resolveRecordRouteBinding(
        '1',
        fn (QueryBuilder $query): QueryBuilder => $query->getEntityManager()->createQueryBuilder()->select('foreign')->from(Imprint::class, 'foreign'),
    );
})->throws(QueryException::class, 'has no field or association named id');

it('keys and resolves a composite record by a declared route key name, without decoding it', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    compositeSeamSeed($entityManager);

    $resource = panelsWireResource(new ImprintByLabelResource());
    $plain = compositeSeamRecord($entityManager, Imprint::class, ['label' => 'Plain']);

    expect($resource->getRecordRouteKey($plain))->toBe('Plain')
        ->and($resource->resolveRecordRouteBinding('Plain'))->toBe($plain)
        ->and($resource->resolveRecordRouteBinding('plain~about'))->toBeNull();
});

/**
 * An unmapped subclass of a fixture entity: Doctrine describes no metadata for
 * it, so it is keyable only through the class the resource declares.
 */
final class CompositeSeamImprintDouble extends Imprint
{
}
