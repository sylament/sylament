<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit;

use Sylament\Actions\ViewAction;

it('names itself view', function (): void {
    expect(ViewAction::make()->getName())->toBe('view')
        ->and(ViewAction::getDefaultName())->toBe('view');
});

it('keeps an explicit name over the default', function (): void {
    expect(ViewAction::make('viewArticle')->getName())->toBe('viewArticle');
});

it('carries the view chrome', function (): void {
    $action = ViewAction::make();

    expect($action->getLabel())->toBe('View')
        ->and($action->getColor())->toBe('gray')
        ->and($action->getIcon())->toBe('heroicons:eye-20-solid')
        ->and($action->getGroupedIcon())->toBe('heroicons:eye-20-solid')
        ->and($action->getModalSubmitAction())->toBeNull()
        ->and($action->getModalCancelAction()?->getLabel())->toBe('Close')
        ->and($action->isSchemaDisabled())->toBeTrue();
});

it('interpolates the record title into the modal heading', function (): void {
    $record = new class {
        public string $name = 'Ada';
    };

    expect(ViewAction::make()->record($record)->recordTitleAttribute('name')->getModalHeading())->toBe('View Ada')
        ->and(ViewAction::make()->getModalHeading())->toBe('View');
});

it('has no action closure, so there is nothing to submit', function (): void {
    expect(ViewAction::make()->getAction())->toBeNull();
});

it('sets no url of its own, so a hostless action has none', function (): void {
    expect(ViewAction::make()->getUrl())->toBeNull();
});
