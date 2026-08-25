<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit;

use Sylament\Actions\DetachAction;

/*
 * The confirming chrome alone. The detach itself is pinned end to end by
 * `AttachDetachActionsTest` in the **tables** suite, where a relationship host
 * and a real Doctrine connection exist.
 */

it('names itself detach', function (): void {
    expect(DetachAction::make()->getName())->toBe('detach')
        ->and(DetachAction::getDefaultName())->toBe('detach');
});

it('keeps an explicit name over the default', function (): void {
    expect(DetachAction::make('detachTopic')->getName())->toBe('detachTopic');
});

it("confirms with Filament's detach chrome", function (): void {
    $action = DetachAction::make();
    $record = new class {
        public string $name = 'Alpha';
    };

    expect($action->getLabel())->toBe('Detach')
        ->and($action->getModalHeading())->toBe('Detach')
        ->and(DetachAction::make()->record($record)->recordTitleAttribute('name')->getModalHeading())->toBe('Detach Alpha')
        ->and($action->getModalSubmitActionLabel())->toBe('Detach')
        ->and($action->getColor())->toBe('danger')
        ->and($action->getIcon())->toBe('heroicons:x-mark-20-solid')
        ->and($action->getModalIcon())->toBe('heroicons:x-mark')
        ->and($action->isConfirmationRequired())->toBeTrue()
        ->and($action->getModalWidth())->toBe('md')
        ->and($action->getSuccessNotificationTitle())->toBe('Detached');
});

it('refuses to detach without a table declaring a relationship', function (): void {
    $action = DetachAction::make()->record(new class {
        public string $name = 'Alpha';
    });

    expect(fn (): mixed => $action->call())
        ->toThrow('detaches a record from a relationship, which only a table declaring relationship() can supply');
});
