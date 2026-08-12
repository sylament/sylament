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
