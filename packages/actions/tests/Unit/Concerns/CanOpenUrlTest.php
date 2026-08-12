<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Action;
use Sylament\Actions\EditAction;
use Sylament\Actions\Tests\Fixtures\Concerns\CanOpenUrlFixture;
use Sylament\Actions\Tests\Fixtures\Concerns\DefaultActionUrlHostFixture;

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

it('keeps the url when only modal content is configured', function (): void {
    expect(Action::make('edit')->url('/edit')->modalHeading('Edit?')->getUrl())->toBe('/edit');
});

it('suppresses the url when a modal is forced through modal()', function (): void {
    expect(Action::make('edit')->url('/edit')->modal()->getUrl())->toBeNull();
});

it('falls through to the host hook when no url of its own resolves', function (): void {
    expect(EditAction::make()->liveComponent(new DefaultActionUrlHostFixture())->getUrl())->toBe('/hosted/edit');
});

it('prefers its own url over the host hook', function (): void {
    expect(EditAction::make()->url('/own')->liveComponent(new DefaultActionUrlHostFixture())->getUrl())->toBe('/own');
});

it('stays null when the host declines to answer for the action', function (): void {
    expect(Action::make('publish')->liveComponent(new DefaultActionUrlHostFixture())->getUrl())->toBeNull();
});

it('suppresses the host hook when a modal is forced', function (): void {
    expect(EditAction::make()->modal()->liveComponent(new DefaultActionUrlHostFixture())->getUrl())->toBeNull();
});

it('evaluates the url and new-tab closures', function (): void {
    expect(new CanOpenUrlFixture()->url(fn (): string => '/dynamic')->getUrl())->toBe('/dynamic')
        ->and(new CanOpenUrlFixture()->openUrlInNewTab(fn (): bool => true)->shouldOpenUrlInNewTab())
        ->toBeTrue();
});
