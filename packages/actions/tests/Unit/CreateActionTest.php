<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit;

use LogicException;
use Sylament\Actions\CreateAction;

it('names itself create', function (): void {
    expect(CreateAction::make()->getName())->toBe('create')
        ->and(CreateAction::getDefaultName())->toBe('create');
});

it('keeps an explicit name over the default', function (): void {
    expect(CreateAction::make('createArticle')->getName())->toBe('createArticle');
});

it('carries the create chrome', function (): void {
    $action = CreateAction::make();

    expect($action->getLabel())->toBe('Create')
        ->and($action->getSuccessNotificationTitle())->toBe('Created');
});

it('carries a grouped icon only, leaving the plain trigger iconless', function (): void {
    $action = CreateAction::make();

    expect($action->getGroupedIcon())->toBe('heroicons:plus-20-solid')
        ->and($action->getIcon())->toBeNull();
});

it('sets no url of its own, so a hostless action has none', function (): void {
    expect(CreateAction::make()->getUrl())->toBeNull();
});

it('takes an explicit url', function (): void {
    expect(CreateAction::make()->url('/articles/new')->getUrl())->toBe('/articles/new');
});

it('fails fast when invoked with no url and no host answer', function (): void {
    expect(fn (): mixed => CreateAction::make()->call())
        ->toThrow(LogicException::class, 'CreateAction has no URL to open');
});
