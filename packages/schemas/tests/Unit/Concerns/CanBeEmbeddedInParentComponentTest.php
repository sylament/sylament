<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Concerns;

use Sylament\Schemas\Tests\Fixtures\Concerns\CanBeEmbeddedInParentComponentFixture;
use Sylament\Support\Service\Invoker;
use Symfony\Component\DependencyInjection\Container;

beforeEach(function (): void {
    Invoker::setInstance(new Invoker(new Container()));
});

afterEach(function (): void {
    Invoker::unsetInstance();
});

it('is not embedded in its parent component by default', function (): void {
    expect(CanBeEmbeddedInParentComponentFixture::make()->isEmbeddedInParentComponent())->toBeFalse();
});

it('is embedded once embeddedInParentComponent() is set', function (): void {
    expect(CanBeEmbeddedInParentComponentFixture::make()->embeddedInParentComponent()->isEmbeddedInParentComponent())
        ->toBeTrue();
});

it('accepts a literal false condition', function (): void {
    expect(CanBeEmbeddedInParentComponentFixture::make()->embeddedInParentComponent(false)->isEmbeddedInParentComponent())
        ->toBeFalse();
});

it('evaluates the embedded condition with the schema injected', function (): void {
    $fixture = CanBeEmbeddedInParentComponentFixture::make();

    $received = null;
    $fixture->embeddedInParentComponent(function ($schema) use (&$received): bool {
        $received = $schema;

        return true;
    });

    expect($fixture->isEmbeddedInParentComponent())->toBeTrue()
        ->and($received)->toBe($fixture);
});
