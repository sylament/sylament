<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Concerns;

use Sylament\Schemas\Tests\Fixtures\Concerns\CanBeInlineFixture;

it('is not inline by default', function (): void {
    expect(CanBeInlineFixture::make()->isInline())->toBeFalse();
});

it('is inline once inline() is set', function (): void {
    expect(CanBeInlineFixture::make()->inline()->isInline())->toBeTrue();
});

it('accepts a literal false condition', function (): void {
    expect(CanBeInlineFixture::make()->inline(false)->isInline())->toBeFalse();
});

it('evaluates the inline condition with the schema injected', function (): void {
    $fixture = CanBeInlineFixture::make();

    $received = null;
    $fixture->inline(function ($schema) use (&$received): bool {
        $received = $schema;

        return true;
    });

    expect($fixture->isInline())->toBeTrue()
        ->and($received)->toBe($fixture);
});
