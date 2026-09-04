<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\CurrentPanel;
use Sylament\Panels\GlobalSearch\GlobalSearchResults;
use Sylament\Panels\GlobalSearch\Providers\DefaultGlobalSearchProvider;
use Sylament\Panels\PanelRegistry;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Resources\ResourceRegistry;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Article;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Note;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Resources\ArticleResource;
use Sylament\Panels\Tests\Fixtures\Resources\NoteResource;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/*
 * The default panel of the fixture kernel registers two searchable resources —
 * articles first, notes second — and `NoteResource` carries a
 * `$globalSearchSort` of -1, so the sort rather than the registration order is
 * what decides which category comes first.
 */

/**
 * The provider is built from container-resolved collaborators rather than
 * fetched by class: nothing injects `GlobalSearchProviderRegistry` until the
 * live component lands, so the container drops the whole private, unreferenced
 * chain — registry, its service locator and the provider inside it.
 */
function defaultGlobalSearchProvider(): DefaultGlobalSearchProvider
{
    $container = test()->getContainer();

    $currentPanel = $container->get(CurrentPanel::class);
    $panels = $container->get(PanelRegistry::class);
    $resources = $container->get(ResourceRegistry::class);

    assert($currentPanel instanceof CurrentPanel);
    assert($panels instanceof PanelRegistry);
    assert($resources instanceof ResourceRegistry);

    return new DefaultGlobalSearchProvider($currentPanel, $panels, $resources);
}

function globalSearchProviderEntityManager(): EntityManagerInterface
{
    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    expect($entityManager)->toBeInstanceOf(EntityManagerInterface::class);
    assert($entityManager instanceof EntityManagerInterface);

    return $entityManager;
}

function globalSearchProviderArticle(string $title, string $slug): Article
{
    return new Article()->setTitle($title)->setSlug($slug)->setStatus('draft');
}

function globalSearchProviderNote(string $title): Note
{
    $note = new Note();
    $note->setTitle($title);

    return $note;
}

/**
 * @param class-string<Resource> $class
 */
function globalSearchProviderCategoryName(string $class): string
{
    $registry = test()->getContainer()->get(ResourceRegistry::class);

    expect($registry)->toBeInstanceOf(ResourceRegistry::class);
    assert($registry instanceof ResourceRegistry);

    return $registry->get($class)->getPluralRecordLabel();
}

it('groups the results of every searchable resource under its plural record label', function (): void {
    EntityManagerFactory::seed(
        globalSearchProviderEntityManager(),
        globalSearchProviderArticle('Alpha post', 'alpha-post'),
        globalSearchProviderArticle('Beta post', 'beta-post'),
        globalSearchProviderNote('Alpha note'),
    );

    $categories = defaultGlobalSearchProvider()->getResults('alpha')->getCategories();

    expect(array_keys($categories))
        // Notes sort ahead of articles even though the panel registers articles
        // first, because `NoteResource` declares the lower `$globalSearchSort`.
        ->toBe([
            globalSearchProviderCategoryName(NoteResource::class),
            globalSearchProviderCategoryName(ArticleResource::class),
        ])
        ->and($categories[globalSearchProviderCategoryName(NoteResource::class)])->toHaveCount(1)
        ->and($categories[globalSearchProviderCategoryName(ArticleResource::class)])->toHaveCount(1);
});

it('skips a resource the search found nothing in', function (): void {
    EntityManagerFactory::seed(
        globalSearchProviderEntityManager(),
        globalSearchProviderArticle('Alpha post', 'alpha-post'),
        globalSearchProviderNote('Alpha note'),
    );

    $categories = defaultGlobalSearchProvider()->getResults('post')->getCategories();

    expect(array_keys($categories))->toBe([globalSearchProviderCategoryName(ArticleResource::class)]);
});

it('answers an empty result set for a term nothing matches', function (): void {
    EntityManagerFactory::seed(
        globalSearchProviderEntityManager(),
        globalSearchProviderArticle('Alpha post', 'alpha-post'),
    );

    $results = defaultGlobalSearchProvider()->getResults('nothing-matches-this');

    expect($results)->toBeInstanceOf(GlobalSearchResults::class)
        ->and($results->getCategories())->toBe([]);
});

it('reads its resources off the panel the request carries', function (): void {
    EntityManagerFactory::seed(
        globalSearchProviderEntityManager(),
        globalSearchProviderArticle('Alpha post', 'alpha-post'),
    );

    $stack = $this::getContainer()->get(RequestStack::class);
    $stack->push(Request::create('/probe'));

    $stack->getCurrentRequest()?->attributes->set('_sylament_panel', 'probe');

    // The probe panel's two resources name no record title attribute between
    // them — so nothing on that panel is searchable at all.
    expect(defaultGlobalSearchProvider()->getResults('alpha')->getCategories())->toBe([]);
});
