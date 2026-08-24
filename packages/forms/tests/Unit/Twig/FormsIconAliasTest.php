<?php

declare(strict_types=1);

use Sylament\Forms\Twig\FormsIconAlias;

it('pins the alias literals the option fields resolve their icons through', function (): void {
    expect(FormsIconAlias::COMPONENTS_CHECKBOX_LIST_SEARCH_FIELD)->toBe('components.checkbox-list.search-field')
        ->and(FormsIconAlias::COMPONENTS_TOGGLE_BUTTONS_BOOLEAN_TRUE)->toBe('components.toggle-buttons.boolean.true')
        ->and(FormsIconAlias::COMPONENTS_TOGGLE_BUTTONS_BOOLEAN_FALSE)->toBe('components.toggle-buttons.boolean.false');
});

it('pins the alias literals the select resolves its option-action icons through', function (): void {
    expect(FormsIconAlias::COMPONENTS_SELECT_ACTIONS_CREATE_OPTION)->toBe('components.select.actions.create-option')
        ->and(FormsIconAlias::COMPONENTS_SELECT_ACTIONS_EDIT_OPTION)->toBe('components.select.actions.edit-option');
});

it('pins the alias literals the repeater resolves its chrome-action icons through', function (): void {
    expect(FormsIconAlias::COMPONENTS_REPEATER_ACTIONS_COLLAPSE)->toBe('components.repeater.actions.collapse')
        ->and(FormsIconAlias::COMPONENTS_REPEATER_ACTIONS_EXPAND)->toBe('components.repeater.actions.expand')
        ->and(FormsIconAlias::COMPONENTS_REPEATER_ACTIONS_REORDER)->toBe('components.repeater.actions.reorder');
});
