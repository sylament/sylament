---
description: The overridable icon slots the forms package exposes, ported from Filament's `View\FormsIconAlias` (the `View\` → `Twig\` segment is the systematic… (draft)
ports:
  - vendor/filament/forms/src/View/FormsIconAlias.php
owns:
  - packages/forms/src/Twig/
---
# FormsIconAlias (forms)

[`Sylament\Forms\Twig\FormsIconAlias`](../../../packages/forms/src/Twig/FormsIconAlias.php) — the overridable icon slots the forms package exposes, ported from Filament's [`View\FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) (the `View\` → `Twig\` segment is the systematic Blade → Twig translation, recorded in [translations.md](../translations.md#namespace-segments)).

An alias is a name an application can register an icon override for under [`sylament.icons`](../support/icon-manager.md); it does nothing until something resolves it — usually a template passing it to `generateIconHtml()`, sometimes PHP calling `IconManager::resolve()` — which is why the constant set grows with the surface that renders the icons rather than up front.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`COMPONENTS_CHECKBOX_LIST_SEARCH_FIELD`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_TOGGLE_BUTTONS_BOOLEAN_FALSE`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_TOGGLE_BUTTONS_BOOLEAN_TRUE`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_SELECT_ACTIONS_CREATE_OPTION`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_SELECT_ACTIONS_EDIT_OPTION`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_REPEATER_ACTIONS_COLLAPSE`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_REPEATER_ACTIONS_EXPAND`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_REPEATER_ACTIONS_REORDER`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_REPEATER_ACTIONS_CLONE`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_REPEATER_ACTIONS_DELETE`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_REPEATER_ACTIONS_MOVE_UP`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_REPEATER_ACTIONS_MOVE_DOWN`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_DRAG_CROP`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_DRAG_MOVE`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_FLIP_HORIZONTAL`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_FLIP_VERTICAL`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_MOVE_DOWN`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_MOVE_LEFT`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_MOVE_RIGHT`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_MOVE_UP`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_ROTATE_LEFT`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_ROTATE_RIGHT`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_ZOOM_100`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_ZOOM_IN`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_ZOOM_OUT`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_KEY_VALUE_ACTIONS_DELETE`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_KEY_VALUE_ACTIONS_REORDER`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_TEXT_INPUT_ACTIONS_COPY`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_TEXT_INPUT_ACTIONS_HIDE_PASSWORD`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_TEXT_INPUT_ACTIONS_SHOW_PASSWORD`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| The other 11 constants | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | todo | — | [detail](#the-alias-set-is-thirty-constants-of-filaments-41) |

The thirty values are pinned by [`FormsIconAliasTest`](../../../packages/forms/tests/Unit/Twig/FormsIconAliasTest.php), which is what makes the de-prefixing below a decision rather than a typo.
Three of the thirty reach their icon through a template.
Two are the boolean pair, which `ToggleButtons`' two templates pass to the `Icon` tag's `alias` prop, resolving [at render rather than at `boolean()` call time](toggle-buttons.md#the-boolean-icons-resolve-their-alias-at-render-not-at-call-time), and an override registered under `sylament.icons` is pinned replacing one of them by [`ToggleButtonsTest`](../../../packages/forms/tests/Integration/Components/ToggleButtonsTest.php).
The third has one too, since the input wrapper landed: the checkbox list's search box passes it to the wrapper's `prefixIconAlias` prop ([the one wrapper a template opens by hand](checkbox-list.md#the-search-box-is-the-one-wrapper-a-template-opens-by-hand)), so all three resolve against real markup.

The rest resolve in PHP rather than through a template.
The two select constants were the first **forms** aliases to do so, a shape panels reached first with [`Dashboard::getNavigationIcon()`](../panels/dashboard.md), which resolves `PanelsIconAlias::PAGES_DASHBOARD_NAVIGATION_ITEM` through an injected `IconManager` of its own.
Here an `Action`'s icon has no alias prop to pass one through, so [`Select` resolves them itself](select.md#the-option-action-strings-are-untranslated-literals) through an `IconManager` taken by a `#[Required]` setter, at the moment it builds each option action.
An override registered for the create-option alias is pinned replacing the rendered icon by [`SelectCreateOptionTest`](../../../packages/forms/tests/Integration/Components/SelectCreateOptionTest.php), which is the second constant to have its override exercised against real markup rather than only its value asserted.

The repeater's seven take that same route, for the same reason — every one of its actions is built in PHP.
`Repeater` resolves them through an `IconManager` taken by the same `#[Required]` setter, in a private `resolveActionIcon()` each factory calls as it mints its action ([the chrome actions](repeater.md#collapse-state-is-the-clients-once-the-controller-connects) and [the drag handle](repeater.md#the-drag-reorder-round-trip-accepts-the-clients-order) are where the first three land; the item actions — clone, delete, move up, move down — followed once their four aliases existed to resolve).

The file-upload editor's thirteen take that route as well: they resolve in PHP through [`FileUpload::getImageEditorActions()`](file-upload.md), whose `alias` entries the partial hands to `<twig:SylamentSupport:Button>`'s `iconAlias` prop, so an override registered under `sylament.icons` replaces the toolbar icon at render, pinned by [`FileUploadTest`](../../../packages/forms/tests/Integration/Components/FileUploadTest.php).

## Details

### The alias set is thirty constants of Filament's 41

Filament declares every overridable icon slot in the whole field catalog up front.
Sylament declares the three belonging to the two option fields, the select's two option actions, all seven of the repeater's actions, the file-upload editor's thirteen toolbar buttons, the key-value field's two row actions and the text input's three affix actions.
The remaining 11 name icons of fields that are not built here at all: the builder's seven item actions and the rich editor's four panel buttons.

**Next step:** neither group has one of its own — each waits on its field.
An alias is only overridable once something reads it, so each constant lands in the same change as the field or feature that renders its icon — the growth order [`SupportIconAlias`](../support/support-icon-alias.md#pagination-rtl-aliases), [`TablesIconAlias`](../tables/tables-bundle.md#the-alias-set-is-fifteen-constants-of-filaments-23) and [`NotificationsIconAlias`](../notifications/notifications-icon-alias.md#the-sixth-constant-waits-for-the-panel-that-renders-it) all follow.

## Divergences

### The alias values drop Filament's package prefix

Filament's values are namespaced by package — `forms::components.checkbox-list.search-field`, `forms::components.toggle-buttons.boolean.true`, and so on.
Sylament's drop the `forms::` segment: `components.checkbox-list.search-field`, `components.toggle-buttons.boolean.true`, `components.toggle-buttons.boolean.false`, `components.select.actions.create-option`, `components.select.actions.edit-option`, `components.repeater.actions.collapse`, `components.repeater.actions.expand`, `components.repeater.actions.reorder`, `components.repeater.actions.clone`, `components.repeater.actions.delete`, `components.repeater.actions.move-up`, `components.repeater.actions.move-down`, `components.key-value.actions.delete`, `components.key-value.actions.reorder`, `components.text-input.actions.copy`, `components.text-input.actions.hide-password`, `components.text-input.actions.show-password`, and the thirteen `components.file-upload.editor.actions.*` values the editor toolbar reads.

Filament is inconsistent about this — `SupportIconAlias`'s values are bare (`modal.close-button`, `section.collapse-button`) while every other package namespaces its own — and Sylament follows the unprefixed form throughout, so an application registering overrides under [`sylament.icons`](../support/icon-manager.md) reads one scheme rather than two.
The **constant** names are untouched, which is how both codebases write them at every call site, so the difference is invisible to anyone who does not hardcode the string.
Same decision as [`NotificationsIconAlias`](../notifications/notifications-icon-alias.md#the-alias-values-drop-filaments-package-prefix), [`PanelsIconAlias`](../panels/layout-templates.md) and tables' [`EMPTY_STATE`](../tables/tables-bundle.md#the-empty-state-alias-value-drops-filaments-package-prefix); all four are in the [rename table](../renames.md#renames).

Only the `forms::` prefix is dropped.
The `components.` segment Filament's forms keys carry beneath it stays, so these values are one segment deeper than the bare `<component>.<part>` form the other packages ship (`notification.danger`, `table.empty-state`).
That is the whole delta from Filament's string, and it keeps the constant's value derivable from Filament's by deleting the prefix — nothing else is re-spelled.
