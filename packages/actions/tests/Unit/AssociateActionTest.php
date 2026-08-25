<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit;

use Sylament\Actions\AssociateAction;
use Sylament\Forms\Components\Select;

/*
 * The chrome, the footer action and the record select, none of which need a
 * table or a database. The associate itself is pinned end to end by
 * `AssociateDissociateActionsTest` in the **tables** suite, where a real
 * relation-scoped host and a real Doctrine connection exist.
 */

it("is named associate and carries Filament's chrome", function (): void {
    $action = AssociateAction::make();

    expect(AssociateAction::getDefaultName())->toBe('associate')
        ->and($action->getName())->toBe('associate')
        ->and($action->getLabel())->toBe('Associate')
        ->and($action->getModalHeading())->toBe('Associate')
        ->and(AssociateAction::make()->recordClass('App\\Entity\\BlogPost')->getModalHeading())->toBe('Associate Blog Post')
        ->and($action->getModalSubmitAction()?->getLabel())->toBe('Associate')
        ->and($action->getModalWidth())->toBe('lg')
        ->and($action->getColor())->toBe('gray')
        ->and($action->getIcon())->toBeNull()
        ->and($action->isConfirmationRequired())->toBeFalse()
        ->and($action->getSuccessNotificationTitle())->toBe('Associated')
        ->and($action->canAssociateAnother())->toBeTrue()
        ->and($action->isRecordSelectPreloaded())->toBeFalse()
        ->and($action->isMultiple())->toBeFalse()
        ->and($action->getRecordSelectSearchColumns())->toBeNull()
        ->and($action->isSearchForcedCaseInsensitive())->toBeNull();
});

it('keeps an explicit name over the default', function (): void {
    expect(AssociateAction::make('associateSong')->getName())->toBe('associateSong');
});

it('carries the associate-another footer action and drops it on associateAnother(false)', function (): void {
    $footer = AssociateAction::make()->getExtraModalFooterActions();

    expect($footer)->toHaveCount(1)
        ->and($footer['associateAnother']->getLabel())->toBe('Associate & associate another')
        ->and($footer['associateAnother']->getArguments())->toBe(['another' => true])
        ->and(AssociateAction::make()->associateAnother(false)->getExtraModalFooterActions())->toBe([]);
});

it('builds a hidden-label, required record select that honours multiple() and the search columns', function (): void {
    $select = AssociateAction::make()->multiple()->recordSelectSearchColumns(['name', 'slug'])->getRecordSelect();

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
    $select = AssociateAction::make()->recordSelect(fn (Select $select): Select => $select->label('Topic'))->getRecordSelect();

    expect($select->getLabel())->toBe('Topic');
});

it('evaluates multiple() rather than reading the raw property, unlike the vendor', function (): void {
    expect(AssociateAction::make()->multiple(fn (): bool => false)->isMultiple())->toBeFalse()
        ->and(AssociateAction::make()->multiple(fn (): bool => true)->isMultiple())->toBeTrue();
});

it('refuses to associate without a table declaring a relationship', function (): void {
    expect(fn (): mixed => AssociateAction::make()->getRecordSelect()->getSearchResults('x'))
        ->toThrow('associates records through a relationship, which only a table declaring relationship() can supply');
});
