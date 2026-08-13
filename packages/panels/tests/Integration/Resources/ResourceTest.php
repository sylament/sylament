<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\CurrentPanel;
use Sylament\Panels\PanelRegistry;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Resources\ResourceRegistry;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Article;
use Sylament\Panels\Tests\Fixtures\Resources\ArticleResource;
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

    expect($entityManager)->toBeInstanceOf(EntityManagerInterface::class)
        ->and($urlGenerator)->toBeInstanceOf(UrlGeneratorInterface::class)
        ->and($currentPanel)->toBeInstanceOf(CurrentPanel::class)
        ->and($panels)->toBeInstanceOf(PanelRegistry::class)
        ->and($propertyAccessor)->toBeInstanceOf(PropertyAccessor::class);

    /**
     * @var EntityManagerInterface $entityManager
     * @var UrlGeneratorInterface  $urlGenerator
     * @var CurrentPanel           $currentPanel
     * @var PanelRegistry          $panels
     * @var PropertyAccessor       $propertyAccessor
     */
    $resource->setResourceServices($entityManager, $urlGenerator, $currentPanel, $panels, $propertyAccessor);

    return $resource;
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
