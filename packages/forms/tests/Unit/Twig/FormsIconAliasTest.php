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

it('pins the alias literals the file-upload editor resolves its toolbar icons through', function (): void {
    expect(FormsIconAlias::COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_DRAG_CROP)->toBe('components.file-upload.editor.actions.drag-crop')
        ->and(FormsIconAlias::COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_DRAG_MOVE)->toBe('components.file-upload.editor.actions.drag-move')
        ->and(FormsIconAlias::COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_FLIP_HORIZONTAL)->toBe('components.file-upload.editor.actions.flip-horizontal')
        ->and(FormsIconAlias::COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_FLIP_VERTICAL)->toBe('components.file-upload.editor.actions.flip-vertical')
        ->and(FormsIconAlias::COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_MOVE_DOWN)->toBe('components.file-upload.editor.actions.move-down')
        ->and(FormsIconAlias::COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_MOVE_LEFT)->toBe('components.file-upload.editor.actions.move-left')
        ->and(FormsIconAlias::COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_MOVE_RIGHT)->toBe('components.file-upload.editor.actions.move-right')
        ->and(FormsIconAlias::COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_MOVE_UP)->toBe('components.file-upload.editor.actions.move-up')
        ->and(FormsIconAlias::COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_ROTATE_LEFT)->toBe('components.file-upload.editor.actions.rotate-left')
        ->and(FormsIconAlias::COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_ROTATE_RIGHT)->toBe('components.file-upload.editor.actions.rotate-right')
        ->and(FormsIconAlias::COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_ZOOM_100)->toBe('components.file-upload.editor.actions.zoom-100')
        ->and(FormsIconAlias::COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_ZOOM_IN)->toBe('components.file-upload.editor.actions.zoom-in')
        ->and(FormsIconAlias::COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_ZOOM_OUT)->toBe('components.file-upload.editor.actions.zoom-out');
});
