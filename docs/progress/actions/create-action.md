# CreateAction (actions)

[`Sylament\Actions\CreateAction`](../../../packages/actions/src/CreateAction.php)
— the prebuilt "new record" action a resource's list page puts in its header.
It ships **url mode only**: it sets no `url()` of its own and falls through to
the host's
[`getDefaultActionUrl()`](interacts-with-actions.md#the-panel-level-default-hooks-are-not-on-the-contract)
hook, so a page that has a create page to send it to renders it as a link. A
host that answers `null` gets a trigger whose invocation throws — see the
divergence below.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`actions/src/CreateAction.php`](../../../vendor/filament/actions/src/CreateAction.php)
  — the class this ports
- [`actions/src/Concerns/CanCustomizeProcess.php`](../../../vendor/filament/actions/src/Concerns/CanCustomizeProcess.php)
  — the trait it composes, ported whole (row in
  [README](README.md#features))

Covered by
[`CreateActionTest`](../../../packages/actions/tests/Unit/CreateActionTest.php).

## Surface

Every member Filament's class carries. _Blocked by_ names what has to land
first. _Detail_ carries a **detail** link to a [Details](#details) write-up, a
**diverges** link to [Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`getDefaultName()`](../../../packages/actions/src/CreateAction.php) (`'create'`) | [`CreateAction.php`](../../../vendor/filament/actions/src/CreateAction.php) | done | — | — |
| [`setUp()` label + success-notification title](../../../packages/actions/src/CreateAction.php) | [`CreateAction.php`](../../../vendor/filament/actions/src/CreateAction.php) | done | — | [diverges](#the-strings-are-literals-not-translation-keys) |
| [`CanCustomizeProcess`](../../../packages/actions/src/Concerns/CanCustomizeProcess.php) (`using()` / `process()`) | [`Concerns/CanCustomizeProcess.php`](../../../vendor/filament/actions/src/Concerns/CanCustomizeProcess.php) | done | — | — |
| [URL mode — the host-hook fallthrough](../../../packages/actions/src/Concerns/CanOpenUrl.php) | [`Concerns/CanOpenUrl.php`](../../../vendor/filament/actions/src/Concerns/CanOpenUrl.php) `getUrl()` | done | — | [detail](action.md#geturl-carries-the-host-fallback-the-new-tab-introspection-helper-is-missing) |
| Modal mode — the `action()` body that persists a new record | [`CreateAction.php`](../../../vendor/filament/actions/src/CreateAction.php) | blocked | host schema-resolver hook | [diverges](#modal-mode-is-a-fail-fast-stub-not-a-persistence-body) |
| `modalHeading()` / `modalSubmitActionLabel()` | [`CreateAction.php`](../../../vendor/filament/actions/src/CreateAction.php) | blocked | model-label host hooks | [diverges](#modal-mode-is-a-fail-fast-stub-not-a-persistence-body) |
| [`groupedIcon('heroicons:plus')`](../../../packages/actions/src/CreateAction.php) | [`CreateAction.php`](../../../vendor/filament/actions/src/CreateAction.php) | done | — | [diverges](#icons-are-literal-heroicon-names-with-no-alias-indirection) |
| Create-another (`createAnother()`, `getCreateAnotherAction()`, `preserveFormDataWhenCreatingAnother()`, `forceRenderAfterCreateAnother()`) | [`CreateAction.php`](../../../vendor/filament/actions/src/CreateAction.php) | todo | modal mode | [detail](#the-create-another-family-waits-on-modal-mode) |
| `relationship()` / `getRelationship()` | [`CreateAction.php`](../../../vendor/filament/actions/src/CreateAction.php) | todo | relation managers | [detail](#the-create-another-family-waits-on-modal-mode) |
| `record(null)` (Filament clears the inherited record in `setUp()`) | [`CreateAction.php`](../../../vendor/filament/actions/src/CreateAction.php) | skipped | — | [diverges](#recordnull-has-nothing-to-clear-here) |

## Details

Specifics for rows that are not finished — what is already in, what is left,
and the next step. Delete a subsection when its row flips to **done**.

### The create-another family waits on modal mode

`createAnother()`, `getCreateAnotherAction()`,
`preserveFormDataWhenCreatingAnother()` and `forceRenderAfterCreateAnother()`
are all *modal*-mode surface: they add a second submit button to the action's
modal footer, and the branch that reads them lives inside the `action()` body
Filament runs after the form validates. With
[modal mode a stub](#modal-mode-is-a-fail-fast-stub-not-a-persistence-body)
there is nothing for them to configure, and a partial port would ship setters
that silently do nothing. `relationship()` / `getRelationship()` are the same
story one layer out — they answer *where* the created record is saved, which
only matters once something saves.

**Next step:** they land with modal mode, and `relationship()` with relation
managers.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold.
These outlive the detail sections above.

### Modal mode is a fail-fast stub, not a persistence body

Filament's `CreateAction` works two ways off one class. With a create page to
link to it renders as a URL; without one it opens a modal, fills a form from
`ListRecords::getDefaultActionSchemaResolver()`, and its `action()` body builds
the model, fills it, saves it and reports `success()`. The port ships the first
half only. The second needs a host hook family that does not exist yet — the
schema resolver that supplies the modal's form (tracked on
[`HasSchema`](has-schema.md#getschema-has-no-host-schema-resolver-fallback)) —
so rather than half-wire it, `setUp()` registers an `action()` closure that
throws a `LogicException` naming both exits:

> CreateAction has no URL to open — give it url() or host it on a resource page
> with a create page; the modal arm is not ported yet.

`CanCustomizeProcess` is composed anyway, for API parity: `using()` is where a
migrating user overrides the persistence step, so it exists on the class from
the start even though the throwing body never reaches `process()` — the trait
gains its consumer with modal mode, not a new call site.

The alternative — leaving `action()` unset — mounts the action, runs nothing,
and reports success, which is the silent-fallback shape the repo's rules
forbid. Throwing costs nothing in url mode: an action with a URL renders an
anchor and is never mounted, so the closure is only reached by exactly the
configuration that has no other outcome. `modalHeading()` and
`modalSubmitActionLabel()` are unset for the same reason, plus a second one:
Filament's heading interpolates the *model label*, which needs the
`getDefaultActionModelLabel()` host hook.

### The strings are literals, not translation keys

Filament reads every user-visible string through
`__('filament-actions::create.single…')`. The port sets the English literals
directly — `label('Create')`, `successNotificationTitle('Created')` — because
no translation catalogue is ported yet (the `Translations` row in
[README](README.md#supporting-surface) tracks it). The label is doubly
redundant today, since
[`HasLabel::getLabel()`](action.md#label-translation-and-the-deprecated-disablelabel-alias-are-not-ported)
already derives `Create` from the name; it is written anyway so the string has
one place to become a translation key.

### Icons are literal heroicon names with no alias indirection

Filament resolves every prebuilt action's icon through
`FilamentIcon::resolve(ActionsIconAlias::CREATE_ACTION_GROUPED) ?? Heroicon::Plus`,
so an app can re-skin all create actions at once. `ActionsIconAlias` is
unported (see [README](README.md#supporting-surface)) and support's
`IconManager` is not reachable from inside an action's evaluated closures — the
[Invoker's injected set](../support/component.md) carries no icon manager and
the container alias is private — so the port sets the literal name the alias
falls back to: `groupedIcon('heroicons:plus')`.

The *placement* is Filament's and worth not "fixing": a create action carries a
**grouped** icon only, so it shows a plus inside a dropdown and none as a header
button. The port keeps that split because it can —
[`HasGroupedIcon`](action.md#hasgroupedicon-narrows-its-icon-type-to-string) is
ported, and the grouped trigger reads
`component.getIcon(component.getGroupedIcon)`, i.e. the plain icon wins and the
grouped one is its fallback. Setting `icon()` instead (what
[`EditAction`](edit-action.md#one-plain-icon-replaces-filaments-table-and-grouped-pair)
is forced into by the missing `HasTableIcon`) would put a plus on every trigger
Filament leaves bare.

### `record(null)` has nothing to clear here

Filament's `setUp()` calls `$this->record(null)` so a create action mounted on
a *record* page does not inherit that page's record. The port's
[`InteractsWithRecord`](action.md#record-key-resolution-is-hoisted-to-hasactions)
defaults `$record` to null already and has no
`getDefaultActionRecord()` host fallback to suppress — the hook cluster is
[not ported](has-actions.md#the-getdefaultaction-host-override-hooks-are-not-ported) —
so the call would be a no-op that reads as meaningful. It returns with the
record hook.
