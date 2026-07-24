<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Tests\Fixtures\Concerns\CanOpenUrlFixture;

it('has no url by default', function (): void {
    expect(new CanOpenUrlFixture()->getUrl())->toBeNull()
        ->and(new CanOpenUrlFixture()->hasUrl())->toBeFalse();
});

it('stores a url', function (): void {
    expect(new CanOpenUrlFixture()->url('/edit')->getUrl())->toBe('/edit')
        ->and(new CanOpenUrlFixture()->url('/edit')->hasUrl())->toBeTrue();
});

it('does not open in a new tab by default', function (): void {
    expect(new CanOpenUrlFixture()->url('/edit')->shouldOpenUrlInNewTab())->toBeFalse();
});

it('opens in a new tab via openUrlInNewTab()', function (): void {
    expect(new CanOpenUrlFixture()->openUrlInNewTab()->shouldOpenUrlInNewTab())->toBeTrue();
});

it('opens in a new tab via the url() second argument', function (): void {
    expect(new CanOpenUrlFixture()->url('/edit', true)->shouldOpenUrlInNewTab())->toBeTrue();
});

it('evaluates the url and new-tab closures', function (): void {
    expect(new CanOpenUrlFixture()->url(fn (): string => '/dynamic')->getUrl())->toBe('/dynamic')
        ->and(new CanOpenUrlFixture()->openUrlInNewTab(fn (): bool => true)->shouldOpenUrlInNewTab())
        ->toBeTrue();
});
