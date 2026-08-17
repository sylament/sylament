<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit;

use LogicException;
use Sylament\Actions\EditAction;
use Sylament\Actions\Tests\Fixtures\Concerns\DefaultActionUrlHostFixture;

it('names itself edit', function (): void {
    expect(EditAction::make()->getName())->toBe('edit')
        ->and(EditAction::getDefaultName())->toBe('edit');
});

it('keeps an explicit name over the default', function (): void {
    expect(EditAction::make('editArticle')->getName())->toBe('editArticle');
});

it('carries the edit chrome', function (): void {
    $action = EditAction::make();

    expect($action->getLabel())->toBe('Edit')
        ->and($action->getColor())->toBe('primary')
        ->and($action->getIcon())->toBe('heroicons:pencil-square-20-solid')
        ->and($action->getSuccessNotificationTitle())->toBe('Saved');
});

it('lets a caller override the default color', function (): void {
    expect(EditAction::make()->color('gray')->getColor())->toBe('gray');
});

it('sets no url of its own, so a hostless action has none', function (): void {
    expect(EditAction::make()->getUrl())->toBeNull();
});

it('falls through to the host url hook when it has no url of its own', function (): void {
    expect(new DefaultActionUrlHostFixture()->getAction('edit')?->getUrl())->toBe('/hosted/edit');
});

it('prefers its own url over the host hook', function (): void {
    $host = new DefaultActionUrlHostFixture();

    expect($host->getAction('edit')?->url('/own')->getUrl())->toBe('/own');
});

it('fails fast when invoked with no url and no host answer', function (): void {
    expect(fn (): mixed => EditAction::make()->call())
        ->toThrow(LogicException::class, 'EditAction has no URL to open');
});
