<?php

declare(strict_types=1);

use Sylament\Panels\Resources\ResourceRegistry;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Article;
use Sylament\Panels\Tests\Fixtures\Resources\ArticleResource;
use Sylament\Panels\Tests\Fixtures\Resources\ResourceRegistryHost;

function panelsResourceRegistry(): ResourceRegistry
{
    $host = test()->getContainer()->get(ResourceRegistryHost::class);

    expect($host)->toBeInstanceOf(ResourceRegistryHost::class);

    /** @var ResourceRegistryHost $host */
    return $host->registry;
}

it('collects tagged resources into the class-keyed registry', function (): void {
    $registry = panelsResourceRegistry();

    expect($registry->has(ArticleResource::class))->toBeTrue()
        ->and($registry->get(ArticleResource::class))->toBeInstanceOf(ArticleResource::class);
});

it('resolves the record query against the fixture entity manager', function (): void {
    $resource = panelsResourceRegistry()->get(ArticleResource::class);

    expect($resource->getRecordQuery()->getRootEntities())->toBe([Article::class]);
});

it('reads a record title through the injected property accessor', function (): void {
    $resource = panelsResourceRegistry()->get(ArticleResource::class);
    $article = new Article()->setTitle('Hello')->setSlug('hello');

    expect($resource->getRecordTitle($article))->toBe('Hello');
});

it('falls back to the record label when the title attribute is unreadable', function (): void {
    $resource = panelsResourceRegistry()->get(ArticleResource::class);

    expect($resource->getRecordTitle(new stdClass()))->toBe('article');
});

it('reads the record route key off the single identifier', function (): void {
    $resource = panelsResourceRegistry()->get(ArticleResource::class);
    $entityManager = $resource->getRecordQuery()->getEntityManager();
    $article = new Article()->setTitle('Hello')->setSlug('hello');

    $entityManager->persist($article);
    $entityManager->flush();

    expect($resource->getRecordRouteKey($article))->toBe((string) $article->getId())
        ->and($resource->resolveRecordRouteBinding((int) $article->getId()))->toBe($article)
        ->and($resource->resolveRecordRouteBinding(404))->toBeNull();
});
