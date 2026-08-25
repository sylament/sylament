# EditAction (actions)

[`Sylament\Actions\EditAction`](../../../packages/actions/src/EditAction.php)
— the prebuilt per-record edit action a table row or a record page carries.
Like [`CreateAction`](create-action.md) it ships **url mode only**: it sets no
`url()`, so its URL comes from the host's
[`getDefaultActionUrl()`](interacts-with-actions.md#the-panel-level-default-hooks-are-not-on-the-contract)
hook, which is handed the action (and through it the row's record) and answers
per action type.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`actions/src/EditAction.php`](../../../vendor/filament/actions/src/EditAction.php)
  — the class this ports
- [`actions/src/Concerns/CanCustomizeProcess.php`](../../../vendor/filament/actions/src/Concerns/CanCustomizeProcess.php)
  — the trait it composes, ported whole (row in
  [README](README.md#features))

Covered by
[`EditActionTest`](../../../packages/actions/tests/Unit/EditActionTest.php) and,
for the rendered anchor, the Integration
[`CanOpenUrlTest`](../../../packages/actions/tests/Integration/Concerns/CanOpenUrlTest.php)
against the
[`DefaultActionUrlHostFixture`](../../../packages/actions/tests/Fixtures/Concerns/DefaultActionUrlHostFixture.php).

## Surface

Every member Filament's class carries. _Blocked by_ names what has to land
first. _Detail_ carries a **diverges** link to [Divergences](#divergences), a
**detail** link into a neighbouring file's write-up, or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`getDefaultName()`](../../../packages/actions/src/EditAction.php) (`'edit'`) | [`EditAction.php`](../../../vendor/filament/actions/src/EditAction.php) | done | — | — |
| [`setUp()` label, success-notification title, `defaultColor('primary')`](../../../packages/actions/src/EditAction.php) | [`EditAction.php`](../../../vendor/filament/actions/src/EditAction.php) | done | — | [diverges](#the-strings-are-literals-not-translation-keys) |
| [`icon('heroicons:pencil-square-20-solid')`](../../../packages/actions/src/EditAction.php) | [`EditAction.php`](../../../vendor/filament/actions/src/EditAction.php) `tableIcon()` + `groupedIcon()` | done | — | [diverges](#one-plain-icon-replaces-filaments-table-and-grouped-pair) |
| [`CanCustomizeProcess`](../../../packages/actions/src/Concerns/CanCustomizeProcess.php) (`using()` / `process()`) | [`Concerns/CanCustomizeProcess.php`](../../../vendor/filament/actions/src/Concerns/CanCustomizeProcess.php) | done | — | — |
| [URL mode — the host-hook fallthrough](../../../packages/actions/src/Concerns/CanOpenUrl.php) | [`Concerns/CanOpenUrl.php`](../../../vendor/filament/actions/src/Concerns/CanOpenUrl.php) `getUrl()` | done | — | [detail](action.md#geturl-carries-the-host-fallback-the-new-tab-introspection-helper-is-missing) |
| Modal mode — `fillForm()` from the record and the `action()` body that updates it | [`EditAction.php`](../../../vendor/filament/actions/src/EditAction.php) | blocked | host schema-resolver hook | [diverges](#modal-mode-is-a-fail-fast-stub-not-an-update-body) |
| `modalHeading()` / `modalSubmitActionLabel()` | [`EditAction.php`](../../../vendor/filament/actions/src/EditAction.php) | blocked | record-title host hook | [diverges](#modal-mode-is-a-fail-fast-stub-not-an-update-body) |
| `mutateRecordDataUsing()` | [`EditAction.php`](../../../vendor/filament/actions/src/EditAction.php) | todo | modal mode | [diverges](#modal-mode-is-a-fail-fast-stub-not-an-update-body) |

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold.

### Modal mode is a fail-fast stub, not an update body

The split [`CreateAction`](create-action.md#the-modal-arm-persists-through-the-schemas-record-class)
carried until its own modal arm landed: url mode is complete, and the modal arm
— a `fillForm()` that reads the record's declared paths into the form plus an
`action()` body that writes them back — has not been written here yet.
`setUp()` therefore still registers an `action()` closure throwing a
`LogicException`:

> EditAction has no URL to open — give it url() or host it on a resource page
> with an edit page; the modal arm is not ported yet.

`CanCustomizeProcess` is composed anyway: `using()` is API surface a migrating
user reaches for, and it gains its consumer when modal mode lands — which is
what it did on
[`CreateAction`](create-action.md#the-modal-arm-persists-through-the-schemas-record-class).

`mutateRecordDataUsing()` is modal-mode-only surface (it rewrites the data
between the record and the form) and lands with it. `modalHeading()` is unset
for a second reason too: Filament interpolates the *record title*, which needs
the `getDefaultActionRecordTitle()` host hook.

### One plain icon replaces Filament's table-and-grouped pair

Filament sets `tableIcon()` and `groupedIcon()` — never a plain `icon()` — so an
edit action shows a pencil in a table row and in a dropdown, but not as a page
header button. The port has no `HasTableIcon`
([README](README.md#features) tracks it), so it sets `icon()` instead, which
the grouped trigger picks up as its fallback
(`component.getIcon(component.getGroupedIcon)`) and every other trigger renders
directly. The visible delta is a pencil on a header-mounted edit action where
Filament shows none — accepted until `HasTableIcon` lands, since the port's
`Action` has no table/non-table rendering split to key off yet. The name is the
literal `heroicons:pencil-square-20-solid` rather than Filament's
`ActionsIconAlias::EDIT_ACTION` indirection, for the reason
[create-action.md](create-action.md#icons-are-literal-heroicon-names-with-no-alias-indirection)
records.

### The strings are literals, not translation keys

`label('Edit')` and `successNotificationTitle('Saved')` are English literals
where Filament reads `__('filament-actions::edit.single…')`, the same call
[create-action.md](create-action.md#the-strings-are-literals-not-translation-keys)
records for the create side. Note the success title is `Saved`, not `Edited` —
that is Filament's own wording, kept.
