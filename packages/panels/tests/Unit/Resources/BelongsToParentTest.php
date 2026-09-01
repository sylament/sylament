<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\CurrentPanel;
use Sylament\Panels\PanelRegistry;
use Sylament\Panels\Resources\ParentResourceRegistration;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Resources\ResourceRegistry;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Chapter;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Leaf;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Line;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Tome;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Verse;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\NestedPanelProvider;
use Sylament\Panels\Tests\Fixtures\Resources\ChapterResource;
use Sylament\Panels\Tests\Fixtures\Resources\LeafResource;
use Sylament\Panels\Tests\Fixtures\Resources\LineResource;
use Sylament\Panels\Tests\Fixtures\Resources\TomeResource;
use Sylament\Panels\Tests\Fixtures\Resources\VerseResource;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\State\PropertyAccessor;
use Sylament\Schemas\State\RecordTypeReader;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

/**
 * The five nested fixture resources — plus whatever a case hands in — wired the
 * way the container wires them, over one entity manager and a registry holding
 * every one of them, which is what a parent lookup resolves through.
 *
 * @param array<Resource> $extraResources
 */
function panelsParentRegistry(EntityManagerInterface $entityManager, array $extraResources = []): ResourceRegistry
{
    $resources = [
        ChapterResource::class => new ChapterResource(),
        VerseResource::class => new VerseResource(),
        LineResource::class => new LineResource(),
        TomeResource::class => new TomeResource(),
        LeafResource::class => new LeafResource(),
    ];

    foreach ($extraResources as $extraResource) {
        $resources[$extraResource::class] = $extraResource;
    }

    $registry = new ResourceRegistry(new ServiceLocator(array_map(
        fn (Resource $resource): Closure => fn (): Resource => $resource,
        $resources,
    )));

    $panels = new PanelRegistry([new NestedPanelProvider()]);
    $propertyAccessor = new PropertyAccessor(PropertyAccess::createPropertyAccessor(), new RecordTypeReader());
    $urlGenerator = new UrlGenerator(new RouteCollection(), new RequestContext());

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

it('reads no registration off a resource that names no parent', function (): void {
    $registry = panelsParentRegistry(EntityManagerFactory::create());

    expect($registry->get(ChapterResource::class)->getParentResource())->toBeNull()
        ->and($registry->get(ChapterResource::class)->getParentResourceRegistration())->toBeNull();
});

it('refuses a parent relationship on a resource that names no parent', function (): void {
    panelsParentRegistry(EntityManagerFactory::create())->get(ChapterResource::class)->getParentRelationshipName();
})->throws(LogicException::class, 'names no parent resource');

it('registers the declared parent and memoizes the registration', function (): void {
    $verse = panelsParentRegistry(EntityManagerFactory::create())->get(VerseResource::class);
    $registration = $verse->getParentResourceRegistration();

    expect($verse->getParentResource())->toBe(ChapterResource::class)
        ->and($registration)->toBeInstanceOf(ParentResourceRegistration::class)
        ->and($registration?->parentResource)->toBe(ChapterResource::class)
        ->and($registration?->childResource)->toBe(VerseResource::class)
        ->and($registration?->getRelationshipName())->toBeNull()
        ->and($verse->getParentResourceRegistration())->toBe($registration);
});

it('derives the parent relationship and route parameter from the mapping', function (): void {
    $registry = panelsParentRegistry(EntityManagerFactory::create());

    expect($registry->get(VerseResource::class)->getParentRelationshipName())->toBe('verses')
        ->and($registry->get(VerseResource::class)->getParentRouteParameterName())->toBe('chapter')
        ->and($registry->get(LineResource::class)->getParentRelationshipName())->toBe('lines')
        ->and($registry->get(LineResource::class)->getParentRouteParameterName())->toBe('verse');
});

it('refuses to derive a relationship when the parent record targets the child twice', function (): void {
    panelsParentRegistry(EntityManagerFactory::create())->get(LeafResource::class)->getParentRelationshipName();
})->throws(
    LogicException::class,
    sprintf('cannot derive its parent relationship from [%s]: 2 association(s) target [%s]', Tome::class, Leaf::class),
);

it('resolves an ambiguous parent through a registration that names the relationship', function (): void {
    $entityManager = EntityManagerFactory::create();

    $resource = new class extends Resource {
        protected static ?string $recordClass = Leaf::class;

        protected static ?string $parentResource = TomeResource::class;

        public function getParentResourceRegistration(): ?ParentResourceRegistration
        {
            return parent::getParentResourceRegistration()?->relationship('appendixLeaves');
        }
    };

    panelsParentRegistry($entityManager, [$resource]);

    expect($resource->getParentRelationshipName())->toBe('appendixLeaves')
        ->and($resource->getParentRouteParameterName())->toBe('appendixTome');
});

it('attaches a record to its parent through the owning side', function (): void {
    $entityManager = EntityManagerFactory::create();
    $registry = panelsParentRegistry($entityManager);

    $chapter = new Chapter()->setTitle('Genesis');
    EntityManagerFactory::seed($entityManager, $chapter);

    $chapter = $entityManager->getRepository(Chapter::class)->findOneBy(['title' => 'Genesis']);
    assert($chapter instanceof Chapter);

    $verse = new Verse()->setHeading('In the beginning');
    $registry->get(VerseResource::class)->attachRecordToParent($verse, $chapter);

    expect($verse->getChapter())->toBe($chapter);

    EntityManagerFactory::seed($entityManager, $verse);

    $stored = $entityManager->getRepository(Verse::class)->findOneBy(['heading' => 'In the beginning']);

    expect($stored)->toBeInstanceOf(Verse::class)
        ->and($stored?->getChapter()?->getId())->toBe($chapter->getId());
});

it('scopes the record query to one parent record under the record alias', function (): void {
    $entityManager = EntityManagerFactory::create();
    $registry = panelsParentRegistry($entityManager);

    $genesis = new Chapter()->setTitle('Genesis');
    $exodus = new Chapter()->setTitle('Exodus');

    $first = new Verse()->setHeading('First')->setChapter($genesis);
    $second = new Verse()->setHeading('Second')->setChapter($genesis);
    $foreign = new Verse()->setHeading('Foreign')->setChapter($exodus);

    EntityManagerFactory::seed($entityManager, $genesis, $exodus, $first, $second, $foreign);

    $genesis = $entityManager->getRepository(Chapter::class)->findOneBy(['title' => 'Genesis']);
    assert($genesis instanceof Chapter);

    $query = $registry->get(VerseResource::class)->getParentScopedRecordQuery($genesis);

    /** @var list<Verse> $verses */
    $verses = $query->getQuery()->getResult();
    $headings = array_map(fn (Verse $verse): string => $verse->getHeading(), $verses);
    sort($headings);

    expect($query->getRootAliases())->toBe(['record'])
        ->and($query->getDQL())->toContain('record.chapter = :sylament_relationship_owner')
        ->and($headings)->toBe(['First', 'Second']);
});

it('scopes a depth-two record query to its own parent', function (): void {
    $entityManager = EntityManagerFactory::create();
    $registry = panelsParentRegistry($entityManager);

    $chapter = new Chapter()->setTitle('Genesis');
    $verse = new Verse()->setHeading('First')->setChapter($chapter);
    $other = new Verse()->setHeading('Second')->setChapter($chapter);

    EntityManagerFactory::seed(
        $entityManager,
        $chapter,
        $verse,
        $other,
        new Line()->setContent('Kept')->setVerse($verse),
        new Line()->setContent('Dropped')->setVerse($other),
    );

    $verse = $entityManager->getRepository(Verse::class)->findOneBy(['heading' => 'First']);
    assert($verse instanceof Verse);

    /** @var list<Line> $lines */
    $lines = $registry->get(LineResource::class)->getParentScopedRecordQuery($verse)->getQuery()->getResult();

    expect(array_map(fn (Line $line): string => $line->getContent(), $lines))->toBe(['Kept']);
});
