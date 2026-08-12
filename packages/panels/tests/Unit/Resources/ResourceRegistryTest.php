<?php

declare(strict_types=1);

use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Resources\ResourceRegistry;
use Sylament\Panels\Tests\Fixtures\Resources\ArticleResource;
use Symfony\Component\DependencyInjection\ServiceLocator;

it('answers has() from the locator', function (): void {
    $registry = new ResourceRegistry(new ServiceLocator([
        ArticleResource::class => static fn (): ArticleResource => new ArticleResource(),
    ]));

    expect($registry->has(ArticleResource::class))->toBeTrue();
});

it('resolves a registered resource through the locator', function (): void {
    $resource = new ArticleResource();

    $registry = new ResourceRegistry(new ServiceLocator([
        ArticleResource::class => static fn (): ArticleResource => $resource,
    ]));

    expect($registry->get(ArticleResource::class))->toBe($resource);
});

it('throws naming the missing registration', function (): void {
    new ResourceRegistry(new ServiceLocator([]))->get(ArticleResource::class);
})->throws(LogicException::class, 'is not a registered service');

it('throws when the locator answers with a non-resource', function (): void {
    /** @var class-string<Resource> $id */
    $id = stdClass::class;

    new ResourceRegistry(new ServiceLocator([
        stdClass::class => static fn (): stdClass => new stdClass(),
    ]))->get($id);
})->throws(LogicException::class, 'returned a non-resource service');
