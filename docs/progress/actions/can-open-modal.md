---
description: The trait that configures an action's modal, the confirmation flag that tunes its defaults, and the ActionModal template that renders it.
ports:
  - vendor/filament/actions/src/Concerns/CanOpenModal.php
  - vendor/filament/actions/src/Concerns/CanRequireConfirmation.php
  - vendor/filament/actions/resources/views/action-modal.blade.php
owns:
  - packages/actions/src/Concerns/CanOpenModal.php
  - packages/actions/src/Concerns/CanRequireConfirmation.php
  - packages/actions/templates/components/ActionModal/
---
# CanOpenModal (actions)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Modal rendering ([`ActionModal/index.html.twig`](../../../packages/actions/templates/components/ActionModal/index.html.twig)) | [`action-modal.blade.php`](../../../vendor/filament/actions/resources/views/action-modal.blade.php) | in progress | — | [detail](#the-action-modal-template-only-renders-part-of-the-full-action-modal), [diverges](#the-action-modal-is-an-anonymous-twig-component-not-an-included-view), [diverges](#the-modal-footer-renders-real-action-instances), [diverges](#the-action-modal-renders-the-record-errors-that-name-no-field) |
| [`CanOpenModal`](../../../packages/actions/src/Concerns/CanOpenModal.php) | [`Concerns/CanOpenModal.php`](../../../vendor/filament/actions/src/Concerns/CanOpenModal.php) | in progress | — | [detail](#canopenmodals-unported-remainder), [diverges](#getmodalicons-confirmation-fallback-bypasses-icon-alias-resolution) |
| [`CanRequireConfirmation`](../../../packages/actions/src/Concerns/CanRequireConfirmation.php) | [`Concerns/CanRequireConfirmation.php`](../../../vendor/filament/actions/src/Concerns/CanRequireConfirmation.php) | done | — | [diverges](#confirmation-is-a-flag-not-a-modal-config-layer) |

## Details

### CanOpenModal's unported remainder

**Ported and test-covered** (`tests/Unit/Concerns/CanOpenModalTest.php`): the seven content-config properties and their getters — `modalHeading` (falls back to `getLabel()`), `modalDescription` (falls back to Filament's confirmation string, `'Are you sure you would like to do this?'`, when `isConfirmationRequired()` — the fallback that makes confirmation imply a modal through the general predicate), `modalIcon` (falls back to `heroicons:exclamation-triangle` only when `isConfirmationRequired()`), `modalIconColor` (falls back to `getColor() ?? 'primary'`), `modalSubmitActionLabel` (`'Confirm'`/`'Submit'` by confirmation state), `modalCancelActionLabel` (`'Cancel'`), and `modalWidth` (Filament's own split: `'md'` when `isConfirmationRequired()`, `'4xl'` otherwise, so a confirmation stays a small box and a form modal gets room for two columns).
These are the exact fields [`ActionModal/index.html.twig`](../../../packages/actions/templates/components/ActionModal/index.html.twig) reads to drive `<twig:SylamentSupport:Modal>`, and `ActionModalRenderTest` exercises the full mount → render path through `RecordActionHost`.
The **presence/visibility surface** is ported too, Filament-exact: `modal()` / `modalHidden()` / `hasModal()` / `hasCustomModalPresence()` / `shouldOpenModal(?Closure $checkForSchemaUsing = null)` plus the `getCustomModalHeading()` / `hasCustomModalHeading()` / `hasModalDescription()` predicates it reads — [`HasActions::mountedActionShouldOpenModal()`](modals.md#the-modal-open-gate-is-null-safe) now delegates to `shouldOpenModal()`, so an action carrying only `modalHeading()` mounts and opens its modal instead of auto-running.
The **custom body content** group is ported too: `modalContent()` / `modalContentFooter()` / `getModalContent()` / `getModalContentFooter()` / `hasModalContent()` / `hasModalContentFooter()` (Filament's `View|Htmlable` typed as `Markup`, the systematic translation; the deprecated `modalFooter()` alias is not carried, per the no-deprecation-shims policy), with both feeding `shouldOpenModal()`'s content arms and rendering in Filament's order inside the modal's content slot ([`ActionModal/index.html.twig`](../../../packages/actions/templates/components/ActionModal/index.html.twig)).
The `checkForSchemaUsing` slot is now supplied: the host passes `$this->mountedActionHasSchema(...)`, Filament-exact, so an action whose only modal content is its `schema()` opens a modal on that alone.
The **footer-action family** is ported: `modalFooterActions()` (full override, prepared and keyed by name) / `extraModalFooterActions()` (slotted between submit and cancel, `ActionGroup`-aware) / `registerModalActions()` / `modalSubmitAction()` / `modalCancelAction()` (the `Action|bool|Closure` customization hooks — a closure receives the default via the `action` named injection; `false` removes the button) / `getModalFooterActions()` / `getVisibleModalFooterActions()` / `getModalSubmitAction()` (string click handler from `getLiveComponentCallMountedActionName()`, color primary-for-gray-or-unset) / `getModalCancelAction()` (`close()` + gray) / `getExtraModalFooterActions()` / `getModalActions()` (group-flattening) / `getModalAction()` / `prepareModalAction()` / `prepareModalActionGroup()` / `makeModalAction()` (`Action::make($name)->button()`), each memoized in the `cached*` properties Filament uses.
Scoped deltas, each deliberate: `getModalFooterActions()` carries Filament's `isWizard()` early return (a wizard renders its own footer row, so the modal must not render a second one — landed with the [wizard arm](has-schema.md#the-wizard-arm)) and its center-alignment `array_reverse` — applied only in the composed submit/extras/cancel branch, matching both `Alignment::Center` and the `'center'` string, so a confirmation modal renders cancel-first; `getModalSubmitAction()` now carries Filament's `->submit($hasFormWrapper ? $name : null)->action($hasFormWrapper ? null : $name)` branch in full (landed with `HasSchema` / `CanSubmitForm`), and matches `null` alongside `'gray'` to primary (Filament's `getColor()` never returns null there; ours does for an unset color — observably identical); `makeModalSubmitAction()` (the `callParent` factory) is ported, with no constructor yet; `prepareModalAction()` now stamps `->parentAction(...)` (narrowed by an `instanceof Action` check the composing class needs) and still drops `->table(...)` (tables), and translates `->livewire(...)` to `->liveComponent($this->getLiveComponentOrNull())`; the `=== false` removal check is `$action instanceof Action`, so a nonsense `modalSubmitAction(true)` nulls the button instead of fataling.
The footer renders through [`ActionModal/index.html.twig`](../../../packages/actions/templates/components/ActionModal/index.html.twig)'s `getVisibleModalFooterActions()` loop as real `Action` buttons.

The **chrome group** is ported, Filament-exact in names, signatures, and effective defaults, and covered by `CanOpenModalTest`: `slideOver()` / `isModalSlideOver()`, `slideOverPosition()` / `getModalSlideOverPosition()` (typed against support's new [`SlideOverPosition`](../../../packages/support/src/Enums/SlideOverPosition.php) enum), `stickyModalHeader()` / `stickyModalFooter()` and their `isModalHeaderSticky()` / `isModalFooterSticky()` getters (each falling back to `isModalSlideOver()`, so a slide-over sticks both panes by default), `modalAlignment()` / `getModalAlignment()` (explicit, else `Center` on confirmation or an `xs`/`sm` width, else `Start`), `modalFooterActionsAlignment()` / `getModalFooterActionsAlignment()` (explicit, else `Center` on confirmation, else `null`), `modalCloseButton()` / `hasModalCloseButton()`, `closeModalByClickingAway()` / `isModalClosedByClickingAway()`, `closeModalByEscaping()` / `isModalClosedByEscaping()`, `modalAutofocus()` / `isModalAutofocused()`, and `modalClickThrough()` / `isModalClickThrough()`.
Every one of them reaches the support `Modal` primitive through [`ActionModal/index.html.twig`](../../../packages/actions/templates/components/ActionModal/index.html.twig).
The one scoped delta: Filament's last four getters fall back to static `ModalComponent` properties, and the port writes the same values as literal `?? true` fallbacks because that global-defaults seam is deferred — those four `?? true` expressions in `hasModalCloseButton()` / `isModalClosedByClickingAway()` / `isModalClosedByEscaping()` / `isModalAutofocused()` are the seam's insertion points when it lands (tracked on support's [`ModalComponent` static-config row](../support/modal.md#modalcomponent-static-config)).

**Missing vs Filament** — Filament's trait is ~800 lines; the port is now ~690.
Absent entirely, with no property, setter, or getter:

- **Deprecated aliases**: `centerModal()`, `modalButton()`, `modalSubheading()`, `extraModalActions()`, `makeExtraModalAction()` are not carried over — expected; Sylament has no deprecation-shim policy and these all have live equivalents above (themselves unported).
- `getLiveComponentCallMountedActionName()` (the systematic Livewire → LiveComponent translation of `getLivewireCallMountedActionName()`) is ported: the trait's base returns `null` and `Action` overrides it to `'callMountedAction'`, exactly as Filament does — the submit footer action's string click handler reads it.

**Type-shape narrowing** (not a functional gap, just less general): `modalIcon` is `string|Closure|null` here vs Filament's `string|BackedEnum|Htmlable|Closure|null` (no `Heroicon` enum or `Markup`-typed icon exists in Sylament yet); `modalWidth` is a bare string vs Filament's `Width` enum (no `Width` enum ported to Sylament yet — every width value here is just whatever string a template's CSS happens to key on, e.g. `sy-modal-md`).
The *defaults* are Filament's, not the shape: `Width::Medium`/`Width::FourExtraLarge` ([vendor `CanOpenModal.php:653-664`](../../../vendor/filament/actions/src/Concerns/CanOpenModal.php)) become `'md'`/`'4xl'`, which support's `modal.css` already knows.
Answering `'md'` unconditionally, as the port once did, squeezed a four-tab article form into a `max-w-md` box — the kind of defect only a browser finds, since every assertion about a width passes either way.

`makeModalSubmitAction(string $name, ?array $arguments = null)` is **ported**, Filament's body exactly: `makeModalAction($name)->callParent($this->getLiveComponentCallMountedActionName())->arguments($arguments)->color('gray')`.
It builds a footer action that calls its **parent** action instead of mounting itself, and the trigger templates already rank its `callParent` handler above `shouldClose()`.
Nothing in the port constructs one yet, and the reason is worth naming precisely, because it is not a wizard thing: Filament's four call sites are `CreateAction` (`createAnother`), `AttachAction` (`attachAnother`), `AssociateAction` (`associateAnother`), and forms' `Select` (`createAnother` again) — the documented "create another / attach another" pattern ([`actions/docs/02-modals.md`](../../../vendor/filament/actions/docs/02-modals.md)), where an extra footer button re-runs the same action with an `['another' => true]` argument flag instead of closing the modal.
Every one of those is a **prebuilt action**, and none is ported.
So the first consumer arrives with the prebuilt record actions (or with forms' `Select`), not with `HasWizard`.
Filament's `makeExtraModalAction()` is the deprecated alias of this same method, which is why the deprecated-aliases bullet above lists it.

`prepareModalAction()` now also stamps `->parentAction($this instanceof Action ? $this : null)` alongside the schema-container/schema-component/live-component bindings, so every action an action declares — footer actions, `getModalAction()` targets — knows its parent.
That single line is what makes the host's parent-modal descent addressable from the action side and what lets [`getContext()`](action.md#getcontext-drops-the-record-model-gate) recognize a child frame.
The `instanceof` narrowing exists because the trait is also composed by non-`Action` classes; Filament passes `$this` unconditionally from a class that is always an `Action`.

**Next step**: the four `?? true` literals become reads of the `ModalComponent`-equivalent global-defaults seam if and when that lands.
What is left beyond those is one scoped delta from the list above — `prepareModalAction()`/ `prepareModalActionGroup()` still drop Filament's `->table($this->getTable())` binding.
No longer blocked, now that [`BelongsToTable`](belongs-to-table.md) ships: it lands with the table's own action rendering, which is the first thing that binds a table to an action at all.

### The action-modal template only renders part of the full action modal

**In:** a `<twig:SylamentSupport:Modal>` call driven entirely by [`CanOpenModal`](../../../packages/actions/src/Concerns/CanOpenModal.php)'s getters — Filament's whole binding list minus the deferred `alert` and the `wire:*` keys.
Content config (`heading`, `description`, `icon`, `iconColor`, `width`) and the full chrome (`alignment`, `slideOver` + `slideOverPosition`, `stickyHeader` / `stickyFooter`, `closeButton`, `closeByClickingAway`, `closeByEscaping`, `autofocus`, `clickThrough`, both extra attribute bags) pass straight through, the `content` block renders `getModalContent()` then `getModalContentFooter()`, and the footer renders `getVisibleModalFooterActions()` as real `Action` instances with `getModalFooterActionsAlignment()` as their alignment.
One thing it renders is not on Filament's list at all: the mounted schema's own record errors, [above the schema](#the-action-modal-renders-the-record-errors-that-name-no-field).

The whole thing sits inside a **dismissal wrapper**: a `<div>` binding the support controller's `sylament--support--modal:dismissed` event to `live_action('unmountAction', { cancelParentActions: action.getParentActionsToCancelOnClose })`, so a client-side close (ESC, click-away, close button, a `close()` action) unmounts that frame server-side — cancelling as far up the stack as the action asked — and the next live re-render does not resurrect a modal the user already closed — Filament's `x-on:modal-closed` equivalent.
It is a wrapper element rather than a binding on the component tag because a caller `data-action` would replace the dialog's own Stimulus wiring inside `attributes.defaults()`; the event bubbles from the dialog, so the wrapper catches it.
Its id is name-scoped where the dialog's is index-scoped — [why](modals.md#a-frame-carries-two-identities-an-index-scoped-dialog-and-a-name-scoped-wrapper).

Rendered by the host's `<twig:SylamentActions:Modals />` tag (see the `RecordActionHost` / `ActionHost` Twig fixtures), once per mounted frame, with the top frame gated on `shouldOpenModal()` — Filament's own loop and gate.
Each instance takes its `id` and its `open` state from the host ([per-frame ids](modals.md#a-frame-carries-two-identities-an-index-scoped-dialog-and-a-name-scoped-wrapper), [server-computed open state](modals.md#the-stack-is-choreographed-declaratively-not-by-client-events)), plus `overlaid` for every frame that is not the top one.
Covered by [`ActionModalRenderTest`](../../../packages/actions/tests/Integration/Components/ActionModalRenderTest.php), which asserts, through a full `mountAction` → render cycle: the modal class, heading, submit/cancel labels and click wiring, the confirmation icon `<svg>`, extra footer actions slotted between submit and cancel, content + content-footer placement, the chrome pass-through, the confirmation modal's centered cancel-first footer, and the dismissal wrapper's `unmountAction` binding — and by [`ModalsRenderTest`](../../../packages/actions/tests/Integration/Components/ModalsRenderTest.php) for the stacked cases: one dialog per frame with distinct ids, per-frame open and overlaid state, per-frame `cancelParentActions`, and the modal-less top frame that renders nothing.

**Missing vs Filament:** `action-modal.blade.php` is a general-purpose action modal, not a confirmation-only one, and most of that generality has no Sylament counterpart yet:

- **No render-hook brackets around the content slots.**
  The template fills the support Modal's `content` block with `getModalContent()`, then the mounted action's schema, then `getModalContentFooter()` — Filament's exact order (`action-modal.blade.php:63-84`), with the schema arriving as a `schema` prop from [`Modals`](has-actions.md) rather than being resolved in-template.
  What has no counterpart is the four render-hook pairs bracketing those three (`MODAL_CUSTOM_CONTENT_{BEFORE,AFTER}`, `MODAL_SCHEMA_{BEFORE,AFTER}`, `MODAL_CUSTOM_CONTENT_FOOTER_{BEFORE,AFTER}`) — no render-hook facility exists.
- **No `alert` mode.**
  Filament threads `alert` through to the modal's `role="alertdialog"`; neither `CanOpenModal` nor [`Modal/index.html.twig`](../../../packages/support/templates/components/Modal/index.html.twig) carries it (tracked on support's [breadth-fill row](../support/modal.md#deferred-filament-modal-surface-breadth-fill)).
  Every other chrome prop Filament threads now passes through; two bindings differ deliberately: `returnsFocus` stays at its `true` default where Filament hardcodes `focus-trap-returns-focus="false"` ([why](../support/modal.md#returnsfocus-stays-true-where-filaments-action-modal-hardcodes-false)), and the modal `id` plus the per-frame `open` value are Sylament's server-state open signal rather than Filament's event-only open.
- **No render hooks.**
  Filament brackets custom content and the schema in four `FilamentView::renderHook()` pairs; Sylament has no render-hook facility, so none of that extensibility point exists (a broader gap than this template alone, noted here because it is one of the things the Filament view relies on).

The **form-wrapper wiring is ported**: the template derives `submitHandler = action.hasFormWrapper ? action.getLiveComponentCallMountedActionName : null` and passes it to support's `Modal`, which swaps the window element to `<form>` and binds the submit event ([support/modal.md](../support/modal.md#the-form-wrapper-carries-live_action-not-wiresubmit)).
Filament couples the two the same way (`action-modal.blade.php:27`).
The one translation is the binding itself — `live_action('…:prevent', …, 'submit')` where Filament writes `wire:submit.prevent`.

**Next step:** what is left on this row is `alert` and the render hooks.
Each lands with the concern named beside it; the support `Modal` component takes its matching prop in the same change.

## Divergences

### `getModalIcon()`'s confirmation fallback bypasses icon-alias resolution

Filament resolves the confirmation-modal icon through `FilamentIcon::resolve(ActionsIconAlias::MODAL_CONFIRMATION) ?? Heroicon::OutlinedExclamationTriangle` — a registry lookup with a typed-enum fallback.
The port hardcodes the literal string `'heroicons:exclamation-triangle'` because Sylament's `ActionsIconAlias` equivalent doesn't exist yet (`actions/README.md` lists `ActionsIconAlias` as `todo`); the icon-string format itself (`heroicons:name`) matches the convention `IconManager` and other components already use (e.g. `support/templates/components/Section/index.html.twig`'s `heroicons:chevron-up-20-solid`), so only the alias indirection is missing, not the icon convention.
Reversed once an `ActionsIconAlias`-equivalent registry lands in `actions` and `getModalIcon()` is rewired to resolve through it with the literal as its ultimate fallback.

### Confirmation is a flag, not a modal-config layer

`CanRequireConfirmation` itself is a verbatim, line-for-line port — same `$isConfirmationRequired` prop, same `requiresConfirmation()` setter, same `isConfirmationRequired()` evaluate-and-cache getter — and is fully covered by `CanRequireConfirmationTest`.
The divergence sits one layer up, in `CanOpenModal`: in Filament "a confirmation IS a modal", so `CanOpenModal` carries the full modal surface (sticky header/footer, slide-over + position, click-through, close button, closed-by-clicking-away/escaping, autofocus, custom width/alignment, arbitrary modal/footer actions with an `ActionGroup`-aware action list, `modalContent`/`modalContentFooter`, `hasModal()`/`shouldOpenModal()` presence logic, wizard suppression, icon alias resolution) and `isConfirmationRequired()` only tweaks defaults inside that machinery (alignment, width, icon, submit label, description).
Sylament's `CanOpenModal` now carries the presence layer (`modal()` / `modalHidden()` / `shouldOpenModal()`), and confirmation flows through it exactly as in Filament: `isConfirmationRequired()` no longer gates the modal directly — it feeds `getModalDescription()`'s confirmation-string fallback, and a filled description is what `shouldOpenModal()` reads ([`HasActions::mountedActionShouldOpenModal()`](modals.md#the-modal-open-gate-is-null-safe) delegates to it).
What remains divergent is the still-unported modal surface confirmation only *tweaks* in Filament (footer action composition, slide-over, sticky panes, click-through/escape/autofocus toggles, `modalContent`/`modalContentFooter`, alignment/width defaults keyed off confirmation) — `CanOpenModal`'s own gap, recorded on the [CanOpenModal detail row](#canopenmodals-unported-remainder), not this trait's.

### The action modal renders the record errors that name no field

Filament's modal renders its schema and nothing about the schema's errors beyond what each field renders under itself — Laravel rules are declared per field, so there is no record-level violation to show.
Here a mounted schema's `getData()` validates the mapped record as well ([record-sourced constraints](../schemas/can-be-validated.md#record-sourced-constraints-are-validated-on-the-mapped-object)), and a violation that names no field the modal declares — a class-level constraint, an application `UniqueEntity` whose `errorPath` the modal lacks, a `before()` hook that moved a key half — folds onto the mounted schema's own state path, which belongs to no input.
Without an outlet for it, that is a 422 with the modal open and nothing on screen, in every application.

So [`ActionModal/index.html.twig`](../../../packages/actions/templates/components/ActionModal/index.html.twig), inside its `schema is not null` branch and before `renderComponent(schema)`, reads `schema.getLiveComponent.getErrorsForSchemaStatePath(schema.getStatePath)` and, when that is not empty, renders `<ul class="sy-ac-modal-errors" data-record-errors>` with one `<li>` per message — the modal twin of panels' [record-level error outlet](../panels/create-record.md).
Its one rule lives in `assets/src/styles/components/action-modal.css`, imported from actions' `styles/index.css`, and is `@apply`-identical to `.sy-pa-form-errors`.
It is not a `{% block %}`, so a caller cannot replace it by name the way a page replaces panels' `form_errors` block.
Pinned by [`ActionModalRecordErrorsTest`](../../../packages/actions/tests/Integration/Components/ActionModalRecordErrorsTest.php) over a real 422 — the list inside `.sy-modal`, before the first field — and its absence while the schema's own path carries nothing.

### The action modal is an anonymous Twig component, not an included view

Filament's `action-modal.blade.php` is a plain view: `Action::renderModal()` calls `view('filament-actions::action-modal', ['action' => $this])`, and `components/modals.blade.php` renders the result.
The port started the same way — an `{% include … with { action, id } only %}` from [`Modals`](../../../packages/actions/templates/components/Modals/index.html.twig) — and is now [`ActionModal/index.html.twig`](../../../packages/actions/templates/components/ActionModal/index.html.twig), invoked as `<twig:SylamentActions:ActionModal :action="action" :id="…" />`.
Every other rendered surface in the repo that a template invokes is a `<twig:>` component; an include was the odd one out, and `{% props action, id %}` states the contract the include's `with {}` could only imply — a missing prop now fails by name.

One thing is genuinely lost: `only`. ux-twig-component's `{% component %}` tag accepts it, but the `<twig:>` HTML syntax has no equivalent (its pre-lexer never emits the flag), so the component inherits the caller's render context where the include was strictly isolated.
Inert here — the template declares both props and references nothing else — but it is a real reduction in isolation, and the reason to keep the props list explicit rather than leaning on inherited variables.

### The modal footer renders real Action instances

Filament's `getModalSubmitAction()` / `getModalCancelAction()` build real `Action` objects (colored, labeled, wired to `submit`/`action`/`close`) that the modal view renders through the normal action-rendering pipeline — and so does Sylament: [`ActionModal/index.html.twig`](../../../packages/actions/templates/components/ActionModal/index.html.twig) loops `getVisibleModalFooterActions()` through `renderComponent()`, so every footer button is a `makeModalAction()`-built `Action` rendering via `Action/Button.html.twig` (submit reaches `live_action('callMountedAction')` through its string click handler; cancel closes the dialog client-side via `data-action="sylament--support--modal#dismiss"` from `close()`, and the dismissal wrapper turns that into the `unmountAction` round-trip — see the [click-precedence divergence](has-action.md#string-click-handlers-are-live-action-names-and-close-dismisses-client-side)).
A third shape exists but has no constructor yet: `makeModalSubmitAction()` builds a footer action whose `callParent()` handler calls the **parent** action's `callMountedAction`, ranked above `close()` in the same precedence chain — Filament's `createAnother` / `attachAnother` pattern, which arrives here with the prebuilt actions ([detail](#canopenmodals-unported-remainder)).
One limitation moved rather than vanished: an array-of-shades color (a value `getColor(): string|array<string>|null` explicitly allows) still cannot be represented in the rendered classes — that is now the general button-template limitation (`Action/Button.html.twig` coerces iterable/empty colors to gray, and `getModalSubmitAction()` maps gray/unset to primary), not a hardcoded-footer artifact.
