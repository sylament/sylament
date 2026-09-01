<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\GlobalSearch\GlobalSearchResult;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Resources\ResourceRegistry;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Article;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Author;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Chapter;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Note;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Verse;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Resources\ArticleResource;
use Sylament\Panels\Tests\Fixtures\Resources\LimitedSearchResource;
use Sylament\Panels\Tests\Fixtures\Resources\NoteResource;
use Sylament\Panels\Tests\Fixtures\Resources\ScopedSearchResource;
use Sylament\Panels\Tests\Fixtures\Resources\UntitledResource;
use Sylament\Panels\Tests\Fixtures\Resources\VerseResource;

/*
 * Every flow here reads through the container: the fixture database is an
 * in-memory SQLite on a container service, so a resource resolved from the
 * registry queries the very rows the test seeded through that same service.
 */

/**
 * @param class-string<Resource> $class
 */
function panelsGlobalSearchResource(string $class): Resource
{
    $registry = test()->getContainer()->get(ResourceRegistry::class);

    expect($registry)->toBeInstanceOf(ResourceRegistry::class);
    assert($registry instanceof ResourceRegistry);

    return $registry->get($class);
}

function panelsGlobalSearchEntityManager(): EntityManagerInterface
{
    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    expect($entityManager)->toBeInstanceOf(EntityManagerInterface::class);
    assert($entityManager instanceof EntityManagerInterface);

    return $entityManager;
}

function panelsGlobalSearchArticle(string $title, string $slug, string $status = 'draft'): Article
{
    return new Article()->setTitle($title)->setSlug($slug)->setStatus($status);
}

function panelsGlobalSearchNote(string $title): Note
{
    $note = new Note();
    $note->setTitle($title);

    return $note;
}

/**
 * The titles a search answered with, sorted so a test pins the set rather than
 * the database's undefined ordering.
 *
 * @param list<GlobalSearchResult> $results
 *
 * @return list<string>
 */
function panelsGlobalSearchTitles(array $results): array
{
    $titles = array_map(fn (GlobalSearchResult $result): string => (string) $result->title, $results);

    sort($titles);

    return $titles;
}

it('searches the record title attribute by default', function (): void {
    EntityManagerFactory::seed(
        panelsGlobalSearchEntityManager(),
        panelsGlobalSearchNote('Alpha note'),
        panelsGlobalSearchNote('Beta note'),
    );

    $resource = panelsGlobalSearchResource(NoteResource::class);
    $results = $resource->getGlobalSearchResults('alpha');

    expect($resource->canGloballySearch())->toBeTrue()
        ->and($resource->getGloballySearchableAttributes())->toBe(['title'])
        ->and(panelsGlobalSearchTitles($results))->toBe(['Alpha note'])
        // The resource registers an index page and nothing else, so the result
        // URL ladder falls all the way through to the index URL.
        ->and($results[0]->url)->toEndWith('/admin/notes');
});

it('is not globally searchable without a record title attribute', function (): void {
    $resource = panelsGlobalSearchResource(UntitledResource::class);

    expect($resource->canGloballySearch())->toBeFalse()
        ->and($resource->getGloballySearchableAttributes())->toBe([]);
});

it('requires every word but matches any searchable attribute', function (): void {
    EntityManagerFactory::seed(
        panelsGlobalSearchEntityManager(),
        panelsGlobalSearchArticle('Alpha post', 'alpha-post'),
        panelsGlobalSearchArticle('Beta post', 'beta-post'),
    );

    $resource = panelsGlobalSearchResource(ArticleResource::class);

    expect(panelsGlobalSearchTitles($resource->getGlobalSearchResults('alpha post')))->toBe(['Alpha post'])
        ->and(panelsGlobalSearchTitles($resource->getGlobalSearchResults('post')))->toBe(['Alpha post', 'Beta post'])
        ->and(panelsGlobalSearchTitles($resource->getGlobalSearchResults('beta-post')))->toBe(['Beta post'])
        ->and($resource->getGlobalSearchResults('alpha beta'))->toBe([]);
});

it('searches across an association dot-path', function (): void {
    $author = new Author()->setName('Ada Lovelace');

    EntityManagerFactory::seed(
        panelsGlobalSearchEntityManager(),
        $author,
        panelsGlobalSearchArticle('Doctrine associations', 'doctrine-associations')->setAuthor($author),
        panelsGlobalSearchArticle('Beta post', 'beta-post'),
    );

    $results = panelsGlobalSearchResource(ArticleResource::class)->getGlobalSearchResults('lovelace');

    expect(panelsGlobalSearchTitles($results))->toBe(['Doctrine associations']);
});

it('builds results with the title, the edit-page url, details and record-bound actions', function (): void {
    $entityManager = panelsGlobalSearchEntityManager();

    EntityManagerFactory::seed($entityManager, panelsGlobalSearchArticle('Alpha post', 'alpha-post'));

    $results = panelsGlobalSearchResource(ArticleResource::class)->getGlobalSearchResults('alpha');
    $article = $entityManager->getRepository(Article::class)->findOneBy(['slug' => 'alpha-post']);

    expect($results)->toHaveCount(1)
        ->and($article)->toBeInstanceOf(Article::class);

    $result = $results[0];
    $actions = array_values($result->getVisibleActions());

    expect($result)->toBeInstanceOf(GlobalSearchResult::class)
        ->and((string) $result->title)->toBe('Alpha post')
        ->and($result->url)->toEndWith('/admin/articles/' . $article?->getId() . '/edit')
        ->and($result->details)->toBe(['Status' => 'draft'])
        ->and($result->hasDetailLabels())->toBeTrue()
        ->and($actions)->toHaveCount(1)
        ->and($actions[0]->getName())->toBe('preview')
        ->and($actions[0]->getRecord())->toBe($article);
});

it('caps the results at the resource limit', function (): void {
    EntityManagerFactory::seed(
        panelsGlobalSearchEntityManager(),
        panelsGlobalSearchArticle('Alpha post', 'alpha-post'),
        panelsGlobalSearchArticle('Beta post', 'beta-post'),
        panelsGlobalSearchArticle('Gamma post', 'gamma-post'),
    );

    $resource = panelsGlobalSearchResource(LimitedSearchResource::class);

    expect($resource->getGlobalSearchResultsLimit())->toBe(2)
        ->and($resource->getGlobalSearchResults('post'))->toHaveCount(2);
});

it('lets modifyGlobalSearchQuery narrow the query', function (): void {
    EntityManagerFactory::seed(
        panelsGlobalSearchEntityManager(),
        panelsGlobalSearchArticle('Alpha post', 'alpha-post', status: 'published'),
        panelsGlobalSearchArticle('Beta post', 'beta-post'),
        panelsGlobalSearchArticle('Gamma post', 'gamma-post'),
    );

    $results = panelsGlobalSearchResource(ScopedSearchResource::class)->getGlobalSearchResults('post');

    expect(panelsGlobalSearchTitles($results))->toBe(['Alpha post']);
});

/**
 * A nested resource is searchable and still answers nothing: every one of its
 * pages needs parent keys a result carries none of, so the blank URL drops the
 * record from the results the provider groups.
 */
it('yields no global search results for a nested resource', function (): void {
    $chapter = new Chapter()->setTitle('Genesis');
    $verse = new Verse()->setHeading('Alpha verse')->setChapter($chapter);

    EntityManagerFactory::seed(panelsGlobalSearchEntityManager(), $chapter, $verse);

    $resource = panelsGlobalSearchResource(VerseResource::class);

    expect($resource->canGloballySearch())->toBeTrue()
        ->and($resource->getGloballySearchableAttributes())->toBe(['heading'])
        ->and($resource->getGlobalSearchResultUrl($verse))->toBeNull()
        ->and($resource->getGlobalSearchResults('alpha'))->toBe([]);
});

it('carries the split, case and sort switches off its static properties', function (): void {
    $resource = panelsGlobalSearchResource(ArticleResource::class);

    expect($resource->shouldSplitGlobalSearchTerms())->toBeTrue()
        ->and($resource->isGlobalSearchForcedCaseInsensitive())->toBeNull()
        ->and($resource->getGlobalSearchSort())->toBeNull()
        ->and($resource->getGlobalSearchRecordQuery()->getDQL())->toBe($resource->getRecordQuery()->getDQL());
});
