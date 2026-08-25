<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit;

use Sylament\Actions\DissociateAction;

/*
 * The confirmation chrome, which needs neither a table nor a database. The
 * dissociate itself — and the three mapping refusals it propagates — is pinned
 * end to end by `AssociateDissociateActionsTest` in the **tables** suite.
 */

it("is named dissociate and confirms with Filament's chrome", function (): void {
    $action = DissociateAction::make();
    $record = new class {
        public string $name = 'Alpha';
    };

    expect(DissociateAction::getDefaultName())->toBe('dissociate')
        ->and($action->getName())->toBe('dissociate')
        ->and($action->getLabel())->toBe('Dissociate')
        ->and($action->getModalHeading())->toBe('Dissociate')
        ->and(DissociateAction::make()->record($record)->recordTitleAttribute('name')->getModalHeading())->toBe('Dissociate Alpha')
        ->and($action->getModalSubmitAction()?->getLabel())->toBe('Dissociate')
        ->and($action->getColor())->toBe('danger')
        ->and($action->getIcon())->toBe('heroicons:x-mark-20-solid')
        ->and($action->getModalIcon())->toBe('heroicons:x-mark')
        ->and($action->isConfirmationRequired())->toBeTrue()
        ->and($action->getModalWidth())->toBe('md')
        ->and($action->getSuccessNotificationTitle())->toBe('Dissociated');
});

it('keeps an explicit name over the default', function (): void {
    expect(DissociateAction::make('unlink')->getName())->toBe('unlink');
});

it('refuses to dissociate without a table declaring a relationship', function (): void {
    $action = DissociateAction::make()->record(new class {
        public string $name = 'Alpha';
    });

    expect(fn (): mixed => $action->call())
        ->toThrow('dissociates a record through a relationship, which only a table declaring relationship() can supply');
});
