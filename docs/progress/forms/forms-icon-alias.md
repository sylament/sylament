---
description: The overridable icon-slot names the forms package exposes, as `FormsIconAlias` constants.
ports:
  - vendor/filament/forms/src/View/FormsIconAlias.php
owns:
  - packages/forms/src/Twig/FormsIconAlias.php
---
# FormsIconAlias (forms)

[`Sylament\Forms\Twig\FormsIconAlias`](../../../packages/forms/src/Twig/FormsIconAlias.php) ports Filament's [`View\FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php); the `View\` → `Twig\` segment is the Blade → Twig translation recorded in [translations.md](../translations.md#namespace-segments).

An alias is a name an application can register an icon override for under [`sylament.icons`](../support/icon-manager.md); it does nothing until something resolves it — usually a template passing it to an alias prop, sometimes PHP calling `IconManager::resolve()` — which is why the constant set grows with the surface that renders the icons rather than up front.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`COMPONENTS_CHECKBOX_LIST_SEARCH_FIELD`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [renames](../renames.md#icon-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_TOGGLE_BUTTONS_BOOLEAN_FALSE`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [renames](../renames.md#icon-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_TOGGLE_BUTTONS_BOOLEAN_TRUE`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [renames](../renames.md#icon-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_SELECT_ACTIONS_CREATE_OPTION`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [renames](../renames.md#icon-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_SELECT_ACTIONS_EDIT_OPTION`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [renames](../renames.md#icon-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_REPEATER_ACTIONS_COLLAPSE`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [renames](../renames.md#icon-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_REPEATER_ACTIONS_EXPAND`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [renames](../renames.md#icon-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_REPEATER_ACTIONS_REORDER`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [renames](../renames.md#icon-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_REPEATER_ACTIONS_CLONE`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [renames](../renames.md#icon-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_REPEATER_ACTIONS_DELETE`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [renames](../renames.md#icon-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_REPEATER_ACTIONS_MOVE_UP`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [renames](../renames.md#icon-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_REPEATER_ACTIONS_MOVE_DOWN`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [renames](../renames.md#icon-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_DRAG_CROP`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [renames](../renames.md#icon-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_DRAG_MOVE`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [renames](../renames.md#icon-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_FLIP_HORIZONTAL`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [renames](../renames.md#icon-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_FLIP_VERTICAL`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [renames](../renames.md#icon-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_MOVE_DOWN`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [renames](../renames.md#icon-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_MOVE_LEFT`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [renames](../renames.md#icon-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_MOVE_RIGHT`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [renames](../renames.md#icon-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_MOVE_UP`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [renames](../renames.md#icon-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_ROTATE_LEFT`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [renames](../renames.md#icon-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_ROTATE_RIGHT`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [renames](../renames.md#icon-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_ZOOM_100`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [renames](../renames.md#icon-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_ZOOM_IN`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [renames](../renames.md#icon-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_ZOOM_OUT`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [renames](../renames.md#icon-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_KEY_VALUE_ACTIONS_DELETE`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [renames](../renames.md#icon-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_KEY_VALUE_ACTIONS_REORDER`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [renames](../renames.md#icon-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_TEXT_INPUT_ACTIONS_COPY`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [renames](../renames.md#icon-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_TEXT_INPUT_ACTIONS_HIDE_PASSWORD`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [renames](../renames.md#icon-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_TEXT_INPUT_ACTIONS_SHOW_PASSWORD`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [renames](../renames.md#icon-alias-values-drop-filaments-package-prefix) |
| The builder's item-action constants (`COMPONENTS_BUILDER_ACTIONS_*`) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | blocked | the `Builder` field | [detail](#the-builder-rich-editor-and-modal-table-select-constants-wait-on-their-fields) |
| The rich editor's panel-button constants (`COMPONENTS_RICH_EDITOR_PANELS_*`) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | blocked | the `RichEditor` field | [detail](#the-builder-rich-editor-and-modal-table-select-constants-wait-on-their-fields) |
| `COMPONENTS_MODAL_TABLE_SELECT_ACTIONS_SELECT` | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | blocked | the `ModalTableSelect` field | [detail](#the-builder-rich-editor-and-modal-table-select-constants-wait-on-their-fields) |

## Details

### The builder, rich-editor and modal-table-select constants wait on their fields

Filament declares every overridable icon slot in the whole field catalog up front.
Sylament declares the constants of the fields it builds: the two option fields, the select's option actions, the repeater's actions, the file-upload editor's toolbar buttons, the key-value field's row actions and the text input's affix actions.
The constants it leaves out name icons of fields that are not built here at all: the builder's item actions, the rich editor's panel buttons and the modal table select's select action.

An alias is only overridable once something reads it, so each constant lands in the same change as the field that renders its icon — the growth order [`SupportIconAlias`](../support/support-icon-alias.md#an-alias-constant-lands-with-the-template-that-reads-it), [`TablesIconAlias`](../tables/tables-bundle.md#an-alias-lands-with-the-component-that-reads-it) and [`NotificationsIconAlias`](../notifications/notifications-icon-alias.md#database_modal_empty_state-waits-for-the-panel-that-renders-it) all follow.

## Semantics

### Most constants resolve in PHP, because their icons belong to actions

Three constants reach their icon through a template.
The boolean pair is what `ToggleButtons`' two templates pass to the `Icon` tag's `alias` prop, resolving [at render rather than at `boolean()` call time](toggle-buttons.md#the-boolean-icons-resolve-their-alias-at-render-not-at-call-time).
The checkbox list's search box passes its constant to the input wrapper's `prefixIconAlias` prop ([the wrapper it opens itself](checkbox-list.md#the-search-box-opens-its-own-input-wrapper-as-vendors-does)).

The select, repeater, key-value and text-input constants name the icons of `Action`s, and an action's icon has no alias prop to pass one through.
So each owner resolves them itself, through an `IconManager` taken by a `#[Required]` setter, at the moment it builds the action: [`Select`](select.md#the-option-action-strings-are-untranslated-literals) in `resolveOptionActionIcon()`, and `Repeater`, `KeyValue` and the three [`TextInput` affix actions](text-input.md) in a private `resolveActionIcon()` of their own.
Panels resolves one in PHP too, in [`Dashboard::getNavigationIcon()`](../panels/dashboard.md), through a closure that takes the `IconManager` by injection.

The file-upload editor's constants travel as the `alias` entries of [`FileUpload::getImageEditorActions()`](file-upload.md), which the partial hands to `<twig:SylamentSupport:Button>`'s `iconAlias` prop, so they resolve at render like the template three.
