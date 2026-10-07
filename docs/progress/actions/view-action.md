---
description: 'The prebuilt per-record read-only action: a grey eye trigger that opens a modal, fills a form from the bound record, disables it and offers a single… (draft)'
ports:
  - vendor/filament/actions/src/ViewAction.php
  - vendor/filament/actions/resources/lang/en/view.php
owns:
  - packages/actions/src/ViewAction.php
---
# ViewAction (actions)

[`Sylament\Actions\ViewAction`](../../../packages/actions/src/ViewAction.php) — the prebuilt per-record read-only action: a grey eye trigger that opens a modal, fills a form from the bound record, disables it and offers a single **Close** button.
Filament's class is sixty lines and three members, and the port is the same shape — "cannot submit" is the *absence* of a submit button and of an `action()` body, not a guard.

It is modal-only here today, but not for want of the URL arm: that arm lives on the panels side rather than on this class, and it already exists — [`Resources\Pages\Page::getDefaultActionUrl()`](../../../packages/panels/src/Resources/Pages/Page.php) answers `$resource->getUrl('view', …)` for a `ViewAction` on a resource whose `hasPage('view')`.
What is missing is the page: no resource can register one until panels ships a `ViewRecord` page, so the arm never fires.
This class does not change when it does.

Covered by [`ViewActionTest`](../../../packages/actions/tests/Unit/ViewActionTest.php) (the chrome, the heading interpolation and the absent action closure) and the Integration [`ViewActionTest`](../../../packages/actions/tests/Integration/ViewActionTest.php) (the fill from the mount context's record, the disabled schema, the Close-only footer, the rendered `disabled` field against a writable `EditAction` control, and that dismissing the modal writes nothing).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`getDefaultName()`](../../../packages/actions/src/ViewAction.php) (`'view'`) | [`ViewAction.php`](../../../vendor/filament/actions/src/ViewAction.php) | done | — | — |
| [`setUp()` label, `modalHeading()`, `defaultColor('gray')`](../../../packages/actions/src/ViewAction.php) | [`ViewAction.php`](../../../vendor/filament/actions/src/ViewAction.php) | done | — | [diverges](#the-strings-are-literals-not-translation-keys) |
| [`modalSubmitAction(false)` + the `Close` cancel action](../../../packages/actions/src/ViewAction.php) | [`ViewAction.php`](../../../vendor/filament/actions/src/ViewAction.php) | done | — | [diverges](#the-footer-is-a-close-button-because-nothing-else-is-there) |
| [`disabledSchema()` + `fillForm()` from the record](../../../packages/actions/src/ViewAction.php) | [`ViewAction.php`](../../../vendor/filament/actions/src/ViewAction.php) | done | — | [detail](#the-modal-renders-the-form-disabled-where-filament-renders-an-infolist) |
| [`mutateRecordDataUsing()`](../../../packages/actions/src/Concerns/CanMutateRecordData.php) | [`ViewAction.php`](../../../vendor/filament/actions/src/ViewAction.php) | done | — | [diverges](#mutaterecorddatausing-is-one-shared-trait-not-three-copies) |
| [`icon('heroicons:eye-20-solid')` + `groupedIcon(...)`](../../../packages/actions/src/ViewAction.php) | [`ViewAction.php`](../../../vendor/filament/actions/src/ViewAction.php) `tableIcon()` + `groupedIcon()` | done | — | [diverges](#the-icon-is-a-literal-name-and-a-plain-icon-stands-in-for-tableicon) |
| Modal content — Filament's view modal renders the resource's **infolist** | [`ViewAction.php`](../../../vendor/filament/actions/src/ViewAction.php), [`Resources/Pages/ViewRecord.php`](../../../vendor/filament/filament/src/Resources/Pages/ViewRecord.php) | in progress | infolists and a panels `ViewRecord` page | [detail](#the-modal-renders-the-form-disabled-where-filament-renders-an-infolist) |

## Details

### The modal renders the form disabled where Filament renders an infolist

`ViewAction` declares no schema of its own.
What it shows is whatever [`HasSchema::getSchema()`](has-schema.md#the-host-resolver-answers-only-when-the-action-declares-nothing) resolves — the action's own `schema([...])` when a caller declares one, and otherwise the host's `getDefaultActionSchemaResolver()`, which on a resource page answers that resource's **form**.
`disabledSchema()` then calls `Schema::disabled()` on the result, and every component reads its container's flag, so the whole tree renders read-only in one pass.
The fill is the [`EditAction`](edit-action.md#the-modal-arm-maps-the-validated-state-onto-the-bound-record) closure verbatim, with the same two-width record guards behind it, and the record is bound at resolve time by the host exactly as it is for an edit.

Filament shows an infolist there instead — a read-only tree of entries rather than a disabled tree of inputs — because a Filament resource declares `infolist()` beside `form()`.
The port has no infolists yet, so the resolver has nothing else to answer.
What changes when they land is the **resolver's answer**, not this class: nothing in `ViewAction` names a form.

**Next step:** infolists and the panels `ViewRecord` page, at which point a resource that declares `infolist()` has it answered here.

## Divergences

### The footer is a Close button because nothing else is there

`modalSubmitAction(false)` makes `getModalSubmitAction()` answer `null` — the evaluated `false` is not an `Action`, so the `submit` key never enters `getModalFooterActions()` — and `modalCancelAction(fn (Action $action): Action => $action->label('Close'))` relabels the one that remains.
There is no guard anywhere against *calling* the action: `getAction()` is `null`, so a mounted `ViewAction` that somehow reached `callMountedAction()` evaluates nothing and reports the default status.
Filament is identical and the port keeps it — a throw would be a divergence with no failure mode behind it.
The divergence recorded here is therefore only that this is stated rather than enforced.

### The strings are literals, not translation keys

`label('View')`, the `View :label` modal heading and the cancel action's `Close` are the English literals read from [`resources/lang/en/view.php`](../../../vendor/filament/actions/resources/lang/en/view.php), where Filament reads `__('filament-actions::view.single…')` — the same call [create-action.md](create-action.md#the-strings-are-literals-not-translation-keys) records for the create side, and the `Translations` row in [README](README.md#supporting-surface) tracks the catalogue.

The heading's `:label` interpolation becomes string interpolation over [`Action::getRecordTitle()`](interacts-with-record.md#the-record-chain-reaches-the-schema-the-component-and-the-host), so it is a closure rather than a literal, with the same bare-verb fallback [`EditAction`](edit-action.md#the-strings-are-literals-not-translation-keys) carries: `View` where Filament would render a trailing-space `View `.

### The icon is a literal name, and a plain `icon()` stands in for `tableIcon()`

Filament resolves both icons through `FilamentIcon::resolve(ActionsIconAlias::VIEW_ACTION) ?? Heroicon::Eye`; the port sets the literal `heroicons:eye-20-solid` the alias falls back to, for the reason [create-action.md](create-action.md#icons-are-literal-heroicon-names-with-no-alias-indirection) records — including why the name spells the mini-solid set rather than the bare `eye`.
`ActionsIconAlias` (unported, tracked in [README](README.md#supporting-surface)) gains a second action's worth of indirection with this class.

The placement diverges the way [`EditAction`](edit-action.md#one-plain-icon-replaces-filaments-table-and-grouped-pair)'s does: Filament sets `tableIcon()` and `groupedIcon()`, the port has no `HasTableIcon` ([README](README.md#features) tracks it), so it sets `icon()` and `groupedIcon()` instead.
Both of Filament's are the same glyph here, so the only visible delta is an eye on a header-mounted view action where Filament shows none.

### `mutateRecordDataUsing()` is one shared trait, not three copies

Filament declares the property and the setter on this class, on `EditAction` and on `ReplicateAction`; the port composes one [`CanMutateRecordData`](../../../packages/actions/src/Concerns/CanMutateRecordData.php) onto all three.
The trait's row in [README](README.md#features) carries the reasoning, and [edit-action.md](edit-action.md#mutaterecorddatausing-is-one-shared-trait-not-three-copies) states it in one paragraph.
No member changed name, so `renames.md` is untouched by it.
