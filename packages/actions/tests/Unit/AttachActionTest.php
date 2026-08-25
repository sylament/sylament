<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit;

use Sylament\Actions\AttachAction;
use Sylament\Forms\Components\Select;

/*
 * The chrome, the footer arm and the record select's shape, none of which need a
 * table or a database. The attach itself — the candidate query, the search, the
 * submit and the smuggled-key refusal — is pinned end to end by
 * `AttachDetachActionsTest` in the **tables** suite, where a relationship host
 * and a real Doctrine connection exist.
 */

it('names itself attach', function (): void {
    expect(AttachAction::make()->getName())->toBe('attach')
        ->and(AttachAction::getDefaultName())->toBe('attach');
});

it('keeps an explicit name over the default', function (): void {
    expect(AttachAction::make('attachTopic')->getName())->toBe('attachTopic');
});

it("carries Filament's attach chrome", function (): void {
    $action = AttachAction::make();

    expect($action->getLabel())->toBe('Attach')
        ->and($action->getModalHeading())->toBe('Attach')
        ->and(AttachAction::make()->recordClass('App\\Entity\\BlogPost')->getModalHeading())->toBe('Attach Blog Post')
        ->and($action->getModalSubmitActionLabel())->toBe('Attach')
        ->and($action->getModalWidth())->toBe('lg')
        ->and($action->getColor())->toBe('gray')
        ->and($action->getIcon())->toBeNull()
        ->and($action->isConfirmationRequired())->toBeFalse()
        ->and($action->getSuccessNotificationTitle())->toBe('Attached');
});

it('defaults every arm of the record select', function (): void {
    $action = AttachAction::make();

    expect($action->canAttachAnother())->toBeTrue()
        ->and($action->isRecordSelectPreloaded())->toBeFalse()
        ->and($action->isMultiple())->toBeFalse()
        ->and($action->getRecordSelectSearchColumns())->toBeNull()
        ->and($action->isSearchForcedCaseInsensitive())->toBeNull();
});

it('carries the attach-another footer action and drops it on attachAnother(false)', function (): void {
    $footer = AttachAction::make()->getExtraModalFooterActions();
    $another = $footer['attachAnother'] ?? null;

    expect($footer)->toHaveCount(1)
        ->and($another?->getLabel())->toBe('Attach & attach another')
        ->and($another?->getArguments())->toBe(['another' => true])
        ->and(AttachAction::make()->attachAnother(false)->getExtraModalFooterActions())->toBe([]);
});

it('builds a hidden-label, required record select that honours multiple() and the search columns', function (): void {
    $select = AttachAction::make()->multiple()->recordSelectSearchColumns(['name', 'slug'])->getRecordSelect();

    expect($select)->toBeInstanceOf(Select::class)
        ->and($select->getName())->toBe('recordId')
        ->and($select->getLabel())->toBe('Record')
        ->and($select->isLabelHidden())->toBeTrue()
        ->and($select->isRequired())->toBeTrue()
        ->and($select->isMultiple())->toBeTrue()
        ->and($select->isSearchable())->toBeTrue()
        ->and($select->getSearchColumns())->toBe(['name', 'slug']);
});

it('lets recordSelect() reshape the select', function (): void {
    $select = AttachAction::make()->recordSelect(fn (Select $select): Select => $select->label('Topic'))->getRecordSelect();

    expect($select->getLabel())->toBe('Topic');
});

it('reads the search columns and the case-insensitivity flag back', function (): void {
    $action = AttachAction::make()
        ->recordSelectSearchColumns(fn (): array => ['title'])
        ->forceSearchCaseInsensitive()
        ->preloadRecordSelect()
        ->multiple();

    expect($action->getRecordSelectSearchColumns())->toBe(['title'])
        ->and($action->isSearchForcedCaseInsensitive())->toBeTrue()
        ->and($action->isRecordSelectPreloaded())->toBeTrue()
        ->and($action->isMultiple())->toBeTrue();
});

it('refuses to attach without a table declaring a relationship', function (): void {
    expect(fn (): mixed => AttachAction::make()->getRecordSelect()->getSearchResults('x'))
        ->toThrow('attaches records to a relationship, which only a table declaring relationship() can supply');
});
