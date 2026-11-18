<?php

declare(strict_types=1);

use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Resources\ResourceRegistry;
use Sylament\Tests\Fixtures\Panels\Resources\ArticleResource;
use Symfony\Component\DependencyInjection\ServiceLocator;

it('turns a registered class-string into its container-built resource, its services injected', function (): void {
    $registry = $this::getContainer()->get(ResourceRegistry::class);

    expect($registry->has(ArticleResource::class))->toBeTrue()
        ->and($registry->get(ArticleResource::class))->toBeInstanceOf(ArticleResource::class)
        ->and($registry->get(ArticleResource::class)->getUrl(isAbsolute: false))->toBe('/admin/articles');
});

it('refuses a class-string no service answers, naming the fix', function (): void {
    $unregistered = new class extends Resource {}::class;
    $registry = $this::getContainer()->get(ResourceRegistry::class);

    expect($registry->has($unregistered))->toBeFalse()
        ->and(fn (): Resource => $registry->get($unregistered))
        ->toThrow(LogicException::class, "The resource {$unregistered} is not a registered service — register it in the application (or bundle) service configuration so the panels compiler pass can see it.");
});

it('refuses a locator answering with something other than a resource', function (): void {
    $registry = new ResourceRegistry(new ServiceLocator([ArticleResource::class => static fn (): stdClass => new stdClass()]));

    expect(fn (): Resource => $registry->get(ArticleResource::class))
        ->toThrow(LogicException::class, 'The resource locator returned a non-resource service for ' . ArticleResource::class . '.');
});
