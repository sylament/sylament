---
description: The trait that configures an action's modal, the confirmation flag that tunes its defaults, and the ActionModal template that renders it.
ports:
  - vendor/filament/actions/src/Concerns/CanOpenModal.php
  - vendor/filament/actions/src/Concerns/CanRequireConfirmation.php
  - vendor/filament/actions/resources/views/action-modal.blade.php
owns:
  - packages/actions/assets/src/styles/components/action-modal.css
  - packages/actions/src/Concerns/CanOpenModal.php
  - packages/actions/src/Concerns/CanRequireConfirmation.php
  - packages/actions/templates/components/ActionModal/
---
# CanOpenModal (actions)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Modal rendering ([`ActionModal/index.html.twig`](../../../packages/actions/templates/components/ActionModal/index.html.twig)) | [`action-modal.blade.php`](../../../vendor/filament/actions/resources/views/action-modal.blade.php) | in progress | — | [detail](#the-action-modal-template-only-renders-part-of-the-full-action-modal) · [diverges](#the-action-modal-is-an-anonymous-twig-component-not-an-included-view) · [diverges](#a-client-side-close-unmounts-the-frame-through-a-wrapper-element) · [diverges](#the-modal-footer-renders-real-action-instances) · [diverges](#the-action-modal-renders-the-record-errors-that-name-no-field) · [diverges](../support/modal.md#returnsfocus-stays-true-where-filaments-action-modal-hardcodes-false) · [diverges](../support/modal.md#the-form-wrapper-carries-live_action-not-wiresubmit) |
| [`CanOpenModal`](../../../packages/actions/src/Concerns/CanOpenModal.php) (the members not rowed below) | [`Concerns/CanOpenModal.php`](../../../vendor/filament/actions/src/Concerns/CanOpenModal.php) | done | — | [diverges](#getmodalicons-confirmation-fallback-bypasses-icon-alias-resolution) · [diverges](#modalicon-and-modalwidth-take-strings-where-filament-also-takes-enums) · [diverges](../support/modal.md#the-chrome-defaults-are-literal-constants-not-modalcomponent-statics) |
| `unsavedChangesAlert()` / `hasUnsavedChangesAlert()` | [`Concerns/CanOpenModal.php`](../../../vendor/filament/actions/src/Concerns/CanOpenModal.php) | todo | — | — |
| The `->table()` binding in [`prepareModalAction()`](../../../packages/actions/src/Concerns/CanOpenModal.php) / `prepareModalActionGroup()` | [`Concerns/CanOpenModal.php`](../../../vendor/filament/actions/src/Concerns/CanOpenModal.php) | todo | — | [detail](#preparemodalaction-drops-filaments-table-binding) |
| `centerModal()` / `modalActions()` / `extraModalActions()` / `modalButton()` / `modalFooter()` / `modalSubheading()` / `makeExtraModalAction()` (deprecated) | [`Concerns/CanOpenModal.php`](../../../vendor/filament/actions/src/Concerns/CanOpenModal.php) | skipped | — | [diverges](#the-deprecated-modal-aliases-are-not-ported) |
| [`CanRequireConfirmation`](../../../packages/actions/src/Concerns/CanRequireConfirmation.php) | [`Concerns/CanRequireConfirmation.php`](../../../vendor/filament/actions/src/Concerns/CanRequireConfirmation.php) | done | — | — |

## Details

### The action-modal template only renders part of the full action modal

The template passes Filament's whole binding list to `<twig:SylamentSupport:Modal>` except two parts.
Filament threads `alert` (`$action->isConfirmationRequired()`, `action-modal.blade.php:6,37`) through to the modal's `role="alertdialog"`; neither `CanOpenModal`'s template nor [`Modal/index.html.twig`](../../../packages/support/templates/components/Modal/index.html.twig) carries it (support's [breadth-fill row](../support/modal.md#deferred-filament-modal-surface-breadth-fill)).
And Filament brackets the custom content, the schema and the content footer in four `FilamentView::renderHook()` pairs (`MODAL_CUSTOM_CONTENT_{BEFORE,AFTER}`, `MODAL_SCHEMA_{BEFORE,AFTER}`, `MODAL_CUSTOM_CONTENT_FOOTER_{BEFORE,AFTER}`, `action-modal.blade.php:63-81`); Sylament has no render-hook facility.
The three slots themselves render in Filament's order, with the schema arriving as a `schema` prop from [`Modals`](modals.md) rather than being resolved in-template.
Each missing part lands with the facility named beside it, and the support `Modal` component takes its matching prop in the same change.

### `prepareModalAction()` drops Filament's table binding

Filament's `prepareModalAction()` and `prepareModalActionGroup()` end with `->table($this->getTable())` (`vendor/filament/actions/src/Concerns/CanOpenModal.php:475`, `:488`), so a footer or modal action inherits its parent's table.
The port's two methods bind the schema container, schema component, live component and record (and `prepareModalAction()` the parent action), but not the table (`packages/actions/src/Concerns/CanOpenModal.php:546-583`).
Nothing blocks it: `BelongsToTable` gives `Action` its `table()` / `getTable()` pair ([`BelongsToTable.php`](../../../packages/actions/src/Concerns/BelongsToTable.php)), and tables already bind their record and toolbar actions to themselves ([`HasRecordActions.php:49`](../../../packages/tables/src/Table/Concerns/HasRecordActions.php)).

## Divergences

### `getModalIcon()`'s confirmation fallback bypasses icon-alias resolution

Filament resolves the confirmation-modal icon through `FilamentIcon::resolve(ActionsIconAlias::MODAL_CONFIRMATION) ?? Heroicon::OutlinedExclamationTriangle`, a registry lookup with a typed-enum fallback.
The port returns the literal `'heroicons:exclamation-triangle'`, for the reason [create-action.md](create-action.md#icons-are-literal-heroicon-names-with-no-alias-indirection) records: `ActionsIconAlias` is unported, and support's icon registry is not reachable from an action's evaluated closures.
The icon-string format (`heroicons:name`) is the one `IconManager` and other components use, so only the alias indirection is missing.
Reversed once an `ActionsIconAlias`-equivalent registry lands in `actions` and `getModalIcon()` resolves through it with the literal as its last fallback.

### `modalIcon()` and `modalWidth()` take strings where Filament also takes enums

Filament types `modalIcon` as `string | BackedEnum | Htmlable | Closure | null` and `modalWidth` as `Width | string | Closure | null` (`vendor/filament/actions/src/Concerns/CanOpenModal.php:80,98`); the port takes `string|Closure|null` for both.
No `Heroicon` enum or `Markup`-typed icon exists in Sylament, and support's `Width` enum exists but the support `Modal` keys its width on the bare string, a back-fill [deferred there](../support/modal.md#the-default-width-is-md-not-sm).
The defaults are Filament's: `Width::Medium` on confirmation and `Width::FourExtraLarge` otherwise (`:662-673`) become `'md'` and `'4xl'`, so a confirmation stays a small box and a form modal gets room for two columns.

### The deprecated modal aliases are not ported

Filament's `@deprecated` forwards on `CanOpenModal` are `centerModal()` (use `modalAlignment(Alignment::Center)`), `modalActions()` (use `modalFooterActions()`), `extraModalActions()` (use `extraModalFooterActions()`), `modalButton()` (use `modalSubmitActionLabel()`), `modalFooter()` (use `modalContentFooter()`), `modalSubheading()` (use `modalDescription()`) and `makeExtraModalAction()` (use `makeModalSubmitAction()`).
The port ships only the replacements, as it ships no deprecated surface.

### The action modal renders the record errors that name no field

Filament's modal renders its schema and nothing about the schema's errors beyond what each field renders under itself: Laravel rules are declared per field, so there is no record-level violation to show.
Here a mounted schema's `getData()` validates the mapped record as well ([record-sourced constraints](../schemas/can-be-validated.md#record-sourced-constraints-are-validated-on-the-mapped-object)), and a violation that names no field the modal declares (a class-level constraint, an application `UniqueEntity` whose `errorPath` the modal lacks, a `before()` hook that moved a key half) folds onto the mounted schema's own state path, which belongs to no input.
Without an outlet for it, that is a 422 with the modal open and nothing on screen.

So [`ActionModal/index.html.twig`](../../../packages/actions/templates/components/ActionModal/index.html.twig), inside its `schema is not null` branch and before `renderComponent(schema)`, reads `schema.getLiveComponent.getErrorsForSchemaStatePath(schema.getStatePath)` and, when that is not empty, renders `<ul class="sy-ac-modal-errors" data-record-errors>` with one `<li>` per message, the modal twin of panels' [record-level error outlet](../panels/create-record.md).
Its one rule lives in `assets/src/styles/components/action-modal.css`, imported from actions' `styles/index.css`, and is `@apply`-identical to `.sy-pa-form-errors`.
It is not a `{% block %}`, so a caller cannot replace it by name the way a page replaces panels' `form_errors` block.

### The action modal is an anonymous Twig component, not an included view

Filament's `action-modal.blade.php` is a plain view: `Action::renderModal()` calls `view('filament-actions::action-modal', ['action' => $this])`, and `components/modals.blade.php` renders the result.
The port's [`ActionModal/index.html.twig`](../../../packages/actions/templates/components/ActionModal/index.html.twig) is a component, invoked from [`Modals`](../../../packages/actions/templates/components/Modals/index.html.twig) as `<twig:SylamentActions:ActionModal :action="action" :id="…" />`.
Every other rendered surface a template invokes is a `<twig:>` component, and `{% props action, id, schema = null, open = true, overlaid = false %}` states the contract an include's `with {}` could only imply, so a missing prop fails by name.

One thing is lost: `only`.
ux-twig-component's `{% component %}` tag accepts it, but the `<twig:>` HTML syntax has no equivalent, so the component inherits the caller's render context where an include can be strictly isolated.
It is inert here, because the template declares every prop it reads, and it is the reason to keep the props list explicit rather than leaning on inherited variables.

### A client-side close unmounts the frame through a wrapper element

Filament's modal carries `x-on:modal-closed`, which calls `$wire.unmountAction(…)` with `getParentActionsToCancelOnClose()` when the event's id is the modal's (`action-modal.blade.php:29-33,61`).
The port wraps the support `Modal` in a `<div>` that binds the support controller's `sylament--support--modal:dismissed` event to `live_action('unmountAction', { cancelParentActions: action.getParentActionsToCancelOnClose })`, so a client-side close (ESC, click-away, the close button, a `close()` action) unmounts that frame server-side, cancelling as far up the stack as the action asked, and the next live re-render does not resurrect a modal the user already closed.
It is a wrapper rather than a binding on the component tag because a caller `data-action` would replace the dialog's own Stimulus wiring inside `attributes.defaults()`; the event bubbles from the dialog, so the wrapper catches it.
Its id is name-scoped where the dialog's is index-scoped ([why](modals.md#a-frame-carries-two-identities-an-index-scoped-dialog-and-a-name-scoped-wrapper)).

### The modal footer renders real Action instances

Filament's `getModalSubmitAction()` / `getModalCancelAction()` build real `Action` objects that the modal view renders through the normal action-rendering pipeline, and so does Sylament: [`ActionModal/index.html.twig`](../../../packages/actions/templates/components/ActionModal/index.html.twig) passes `getVisibleModalFooterActions()` to the support `Modal`, which renders each through `renderComponent()`, so every footer button is a `makeModalAction()`-built `Action` rendering via `Action/Button.html.twig`.
Submit reaches `live_action('callMountedAction')` through its string click handler; cancel closes the dialog client-side via `data-action="sylament--support--modal#dismiss"` from `close()`, and the [dismissal wrapper](#a-client-side-close-unmounts-the-frame-through-a-wrapper-element) turns that into the `unmountAction` round-trip (see the [click-precedence divergence](has-action.md#string-click-handlers-are-live-action-names-and-close-dismisses-client-side)).
A third shape, `makeModalSubmitAction()`, builds a footer action whose `callParent()` handler calls the **parent** action's `callMountedAction`, ranked above `close()` in the same precedence chain; it is Filament's create-another pattern, built by `CreateAction`, `AttachAction`, `AssociateAction` and forms' `Select`, as in Filament.
`getModalSubmitAction()` matches `null` alongside `'gray'`, where Filament matches `'gray'` alone, because the port's `getColor()` answers `null` for an unset color.
