---
description: The prebuilt read-only record action, a modal that fills the record's schema, disables it and offers only a Close button.
ports:
  - vendor/filament/actions/src/ViewAction.php
  - vendor/filament/actions/resources/lang/en/view.php
owns:
  - packages/actions/src/ViewAction.php
---
# ViewAction (actions)

[`Sylament\Actions\ViewAction`](../../../packages/actions/src/ViewAction.php) is the prebuilt per-record read-only action: a grey eye trigger that opens a modal, fills a schema from the bound record, disables it and offers a single **Close** button.
"Cannot submit" is the *absence* of a submit button and of an `action()` body, not a guard, as in Filament.

The URL arm lives on the panels side rather than on this class: [`Resources\Pages\Page::getDefaultActionUrl()`](../../../packages/panels/src/Resources/Pages/Page.php) answers `$resource->getUrl('view', …)` for a `ViewAction` on a resource whose `hasPage('view')`.
Panels ships no `ViewRecord` page to register under that name, so the action opens its modal; a resource that registers a view page gets the link without a change to this class.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`getDefaultName()`](../../../packages/actions/src/ViewAction.php) (`'view'`) | [`ViewAction.php`](../../../vendor/filament/actions/src/ViewAction.php) | done | — | — |
| [`setUp()` label, `modalHeading()`, `defaultColor('gray')`](../../../packages/actions/src/ViewAction.php) | [`ViewAction.php`](../../../vendor/filament/actions/src/ViewAction.php) | done | — | [diverges](#the-strings-are-literals-not-translation-keys) |
| [`modalSubmitAction(false)` + the `Close` cancel action](../../../packages/actions/src/ViewAction.php) | [`ViewAction.php`](../../../vendor/filament/actions/src/ViewAction.php) | done | — | [semantics](#the-footer-is-a-close-button-because-nothing-else-is-there) |
| [`disabledSchema()` + `fillForm()` from the record](../../../packages/actions/src/ViewAction.php) | [`ViewAction.php`](../../../vendor/filament/actions/src/ViewAction.php) | done | — | — |
| [`mutateRecordDataUsing()`](../../../packages/actions/src/Concerns/CanMutateRecordData.php) | [`ViewAction.php`](../../../vendor/filament/actions/src/ViewAction.php) | done | — | [diverges](edit-action.md#mutaterecorddatausing-is-one-shared-trait-not-three-copies) |
| [`icon('heroicons:eye-20-solid')` + `groupedIcon(...)`](../../../packages/actions/src/ViewAction.php) | [`ViewAction.php`](../../../vendor/filament/actions/src/ViewAction.php) `tableIcon()` + `groupedIcon()` | done | — | [diverges](#the-icon-is-a-literal-name-and-a-plain-icon-stands-in-for-tableicon) |
| Modal content — Filament's view modal renders the resource's **infolist** | [`ViewAction.php`](../../../vendor/filament/actions/src/ViewAction.php), [`Resources/Pages/ViewRecord.php`](../../../vendor/filament/filament/src/Resources/Pages/ViewRecord.php) | in progress | — | [detail](#the-modal-renders-the-form-disabled-where-filament-renders-an-infolist) |

## Details

### The modal renders the form disabled where Filament renders an infolist

`ViewAction` declares no schema of its own.
What it shows is whatever [`HasSchema::getSchema()`](has-schema.md#the-host-resolver-answers-only-when-the-action-declares-nothing) resolves: the action's own `schema([...])` when a caller declares one, and otherwise the host's `getDefaultActionSchemaResolver()`.
On a resource page that resolver answers `$resource->infolist($resource->form($schema))`, and `Resource::infolist()` hands the schema back unchanged by default, so a view modal shows the resource's **form** unless the resource's `infolist()` adds to it.
`disabledSchema()` then calls `Schema::disabled()` on the result, and every component reads its container's flag, so the whole tree renders read-only in one pass.
The fill is the [`EditAction`](edit-action.md#the-modal-arm-maps-the-validated-state-onto-the-bound-record) closure verbatim, with the same two-width record guards behind it, and the record is bound at resolve time by the host exactly as it is for an edit.

Filament shows an infolist there instead: a read-only tree of entries rather than a disabled tree of inputs, because a Filament resource declares `infolist()` beside `form()` and its infolists package supplies the entries.
What exists is the hook: `infolist()` on the resource and the resolver that calls it.
What is missing is anything to put in it, because `sylament/infolists` ships no entry components, and the panels `ViewRecord` page that renders the infolist on a page of its own.
Nothing in `ViewAction` names a form, so neither changes this class.

## Semantics

### The footer is a Close button because nothing else is there

`modalSubmitAction(false)` makes `getModalSubmitAction()` answer `null`, because the evaluated `false` is not an `Action`, so the `submit` key never enters `getModalFooterActions()`; `modalCancelAction(fn (Action $action): Action => $action->label('Close'))` relabels the one that remains.
There is no guard anywhere against *calling* the action: `getAction()` is `null`, so a mounted `ViewAction` that somehow reached `callMountedAction()` evaluates nothing and reports the default status.
Filament is identical, and the port keeps it: a throw would be a divergence with no failure mode behind it.

## Divergences

### The strings are literals, not translation keys

`label('View')`, the `View :label` modal heading and the cancel action's `Close` are the English literals read from [`resources/lang/en/view.php`](../../../vendor/filament/actions/resources/lang/en/view.php), where Filament reads `__('filament-actions::view.single…')`, the same call [create-action.md](create-action.md#the-strings-are-literals-not-translation-keys) records for the create side.

The heading's `:label` interpolation becomes string interpolation over [`Action::getRecordTitle()`](interacts-with-record.md), so it is a closure rather than a literal, with the same bare-verb fallback [`EditAction`](edit-action.md#the-strings-are-literals-not-translation-keys) carries: `View` where Filament would render a trailing-space `View `.

### The icon is a literal name, and a plain `icon()` stands in for `tableIcon()`

Filament resolves both icons through `FilamentIcon::resolve(ActionsIconAlias::VIEW_ACTION) ?? Heroicon::Eye`; the port sets the literal `heroicons:eye-20-solid` the alias falls back to, for the reason [create-action.md](create-action.md#icons-are-literal-heroicon-names-with-no-alias-indirection) records, including why the name spells the mini-solid set rather than the bare `eye`.

The placement diverges the way [`EditAction`](edit-action.md#one-plain-icon-replaces-filaments-table-and-grouped-pair)'s does: Filament sets `tableIcon()` and `groupedIcon()`, and the port has no `HasTableIcon`, so it sets `icon()` and `groupedIcon()` instead.
Both of Filament's are the same glyph here, so the only visible delta is an eye on a header-mounted view action where Filament shows none.
