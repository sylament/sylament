# ActionGroup (actions)

`Sylament\Actions\ActionGroup` — groups a list of `Action`/`ActionGroup` children
behind a single trigger, so a table row or a toolbar can collapse many actions
into one control. It composes `Sylament\Support\Components\ViewComponent`
(Twig-backed rendering, the same base `Action` extends) and folds in its own
actions-package concern, `BelongsToGroup` — shared with `Action` (both now
compose it), written up here rather than split into a third detail file, since
its whole surface is a two-method back-reference pair. It also reuses two
already-folded `Action` concerns unchanged — `HasLabel` and `HasSize`, tracked
in [`action.md`](action.md) — and five support-level traits (`HasColor`,
`HasExtraAttributes`, `HasIcon`, `HasIconSize`, `HasTooltip`) that stay tracked
in the [`support`](../support/README.md) package and appear here only as
reference rows. The model core (the children list, the eager name-keyed flat
map, and the group back-reference wiring), the dropdown trigger config,
visibility composition, and context propagation to children (`liveComponent()`,
`getRecord()` fallback, default-template push onto `Action` children) have
landed across this epic's first six tasks. Still to come: propagating dropdown
placement and trigger-view defaults to nested groups, and the render pipeline
(see _Blocked by_ below).

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`actions/src/ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php)
  — the base class and the ~24 traits (plus the `Arrayable`/`HasEmbeddedView`
  interfaces) it composes
- [`actions/src/Concerns/BelongsToGroup.php`](../../../vendor/filament/actions/src/Concerns/BelongsToGroup.php)
  — the back-reference concern audited below
- [`actions/src/Concerns/HasDropdown.php`](../../../vendor/filament/actions/src/Concerns/HasDropdown.php)
  — the trigger-config concern, audited below

## Surface

Every point of this component and its own folded concern. _Blocked by_ names
a later task in this same epic or an unbuilt package. _Detail_ links the
write-up in [Details](#details) (deleted once the row is **done**) and/or the
permanent note in [Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Construction, `make()` & `actions()`/`getActions()`/`getFlatActions()` | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) | done | — | [diverges](#make-drops-di-construction) |
| [`BelongsToGroup`](../../../packages/actions/src/Concerns/BelongsToGroup.php) | [`Concerns/BelongsToGroup.php`](../../../vendor/filament/actions/src/Concerns/BelongsToGroup.php) | done | — | [diverges](#getrootgroup-is-not-ported) |
| `getLabel()` default | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`getLabel()`) | done | — | [diverges](#getlabel-falls-back-to-a-plain-string-not-a-translated-label) |
| [`HasLabel`](../../../packages/actions/src/Concerns/HasLabel.php) (shared with `Action`) | [`Concerns/HasLabel.php`](../../../vendor/filament/actions/src/Concerns/HasLabel.php) | done | — | - |
| [`HasSize`](../../../packages/actions/src/Concerns/HasSize.php) (shared with `Action`) | [`Concerns/HasSize.php`](../../../vendor/filament/actions/src/Concerns/HasSize.php) | done | — | - |
| [`HasColor`](../../../packages/support/src/Components/Concerns/HasColor.php) (support) | [`Concerns/HasColor.php`](../../../vendor/filament/support/src/Concerns/HasColor.php) | done | — | - |
| [`HasExtraAttributes`](../../../packages/support/src/Components/Concerns/HasExtraAttributes.php) (support) | [`Concerns/HasExtraAttributes.php`](../../../vendor/filament/support/src/Concerns/HasExtraAttributes.php) | done | — | - |
| [`HasIcon`](../../../packages/support/src/Components/Concerns/HasIcon.php) (support) | [`Concerns/HasIcon.php`](../../../vendor/filament/support/src/Concerns/HasIcon.php) | done | — | - |
| [`HasIconSize`](../../../packages/support/src/Components/Concerns/HasIconSize.php) (support) | [`Concerns/HasIconSize.php`](../../../vendor/filament/support/src/Concerns/HasIconSize.php) | done | — | - |
| [`HasTooltip`](../../../packages/support/src/Components/Concerns/HasTooltip.php) (support) | [`Concerns/HasTooltip.php`](../../../vendor/filament/support/src/Concerns/HasTooltip.php) | done | — | - |
| `HasDropdown` | [`Concerns/HasDropdown.php`](../../../vendor/filament/actions/src/Concerns/HasDropdown.php) | done | — | [diverges](#dropdownwidth-is-a-css-string-dropdownteleport-and-dropdownautoplacement-are-deferred) |
| Visibility composition (`isHidden()` group-iteration) | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`isHidden()`/`resolveIsHidden()`) | done | — | [diverges](#no-ishiddeningroup-or-visibility-caching) |
| Context propagation to children (`liveComponent()`, `getRecord()` fallback, default-template push onto `Action` children) | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`liveComponent()`/`getActions()`) | done | — | [diverges](#livecomponent-propagates-eagerly-unlike-filaments-lazy-getgroup-fallback), [diverges](#getrecord-is-duplicated-onto-action-and-actiongroup-to-satisfy-phpstan), [diverges](#getactions-always-pushes-a-concrete-default-template-never-null) |
| Dropdown-placement propagation to nested groups (`defaultDropdownPlacement('right-top')`) | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`actions()`) | todo | submenu rendering | - |
| Trigger-view-default propagation to nested groups (`defaultTriggerView()`) | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`getActions()`) | todo | submenu rendering | - |
| Grouped item template | [`components/dropdown/list/item.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) | done | — | - |
| Trigger machinery | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`getTriggerView()`/`toTriggerHtml()`) | done | — | [diverges](#trigger-defaults-diverge-from-filament-gray-over-primary-and-the-ellipsis-icon-is-per-template) |
| Rendering (`toEmbeddedHtml()`) | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`toEmbeddedHtml()`) | todo | Task 9 | - |
| Badge/button-group/grouped/link triggers | [`View/Concerns/CanGenerate*Html.php`](../../../vendor/filament/support/src/View/Concerns) | todo | — | - |
| Submenu rendering (nested non-dropdown groups) | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`toEmbeddedHtml()` action-list branch) | todo | — | - |
| Dropdown teleport & auto-placement | [`Concerns/HasDropdown.php`](../../../vendor/filament/actions/src/Concerns/HasDropdown.php) (`dropdownTeleport()`/`dropdownAutoPlacement()`) | todo | — | - |
| `toArray()`/`fromArray()` (`Arrayable`) | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`toArray()`/`fromArray()`) | todo | — | - |
| Table and bulk concerns | [`Concerns/BelongsToTable.php`](../../../vendor/filament/actions/src/Concerns/BelongsToTable.php), `hasNonBulkAction()` | blocked | tables package, BulkAction | [detail](#table-and-bulk-concerns-are-blocked-on-the-tables-and-bulkaction-ports) |

## Details

### Table and bulk concerns are blocked on the tables and BulkAction ports

Filament's `ActionGroup` composes `BelongsToTable` and `BelongsToSchemaComponent`,
and exposes `hasNonBulkAction()` (iterates `getFlatActions()` calling
`$action->isBulk()`). None of this has a Sylament target yet: there is no
`tables`-package integration point (`BelongsToTable`'s host) and no `BulkAction`
class (`isBulk()`'s only caller) in the port. **Blocked** on both landing; add
`BelongsToTable`/`BelongsToSchemaComponent` (or their Sylament equivalents) and
`hasNonBulkAction()` in the same change that ports whichever comes first.

## Divergences

### `make()` drops DI construction

Filament's `make(array $actions): static` resolves through
`app(static::class, ['actions' => $actions])` — container construction, so an
`ActionGroup` subclass can declare extra constructor-injected dependencies
beyond `$actions`. The port's
[`make()`](../../../packages/actions/src/ActionGroup.php) is
`new static($actions); $group->configure(); return $group;` — plain
instantiation, the same `app()->make()` → Symfony DI translation `Action::make()`
already made (see
[action.md](action.md#make-drops-getdefaultname-and-di-construction)), because
`ActionGroup`'s constructor needs no injected services. Reversible the moment a
subclass needs one, by switching to `Invoker`/container construction.

### `getRootGroup()` is not ported

Filament's `Concerns\BelongsToGroup` carries a third method,
`getRootGroup(): ?ActionGroup`, that walks `getGroup()` up through nested
parents and returns the outermost one. The port's
[`BelongsToGroup`](../../../packages/actions/src/Concerns/BelongsToGroup.php)
has only `group()`/`getGroup()` — no root-walking helper, and no test exercises
one because none exists. The port's `group(?ActionGroup $group = null): static`
also gives its parameter a default of `null` (Filament's is required); harmless
since `null` is the effective value every caller passes today, and it lets
`->group()` clear the association without spelling out the `null` argument.
Reversible: port `getRootGroup()` the moment a consumer needs to walk to the
top of a nested-group chain in one jump. Task 6's `liveComponent()`/`getRecord()`
propagation turned out not to need it — each level's own `getGroup()` call is
enough for the fallback to cascade one hop at a time through nested groups; a
submenu or trigger feature that needs the outermost group directly is the more
likely trigger now.

### `getLabel()` falls back to a plain string, not a translated label

Filament's `getLabel()` is
`$this->evaluate($this->label) ?? __('filament-actions::group.trigger.label')`,
then runs a string result through `__()` again when `$shouldTranslateLabel` is
set — the same translation layer `Action`'s own `HasLabel` already drops (no
translator wired into the repo; see
[action.md](action.md#label-translation-and-the-deprecated-disablelabel-alias-are-not-ported)).
The port's [`getLabel()`](../../../packages/actions/src/ActionGroup.php) is
`$this->evaluate($this->label) ?? 'Actions'` — a plain literal. The override
also means `ActionGroup` never runs the `HasLabel` trait's own `getLabel()`
(which derives a name-based default via `getName()`) — PHP resolves the
class-defined method over the trait's, no aliasing needed, and a group has no
name for that default to derive from anyway. Reverses if/when a translation
layer lands, same as the linked `Action` divergence.

### `dropdownWidth` is a CSS string; `dropdownTeleport` and `dropdownAutoPlacement` are deferred

Filament's `HasDropdown` carries a `Width` enum and types `dropdownWidth(Width|Closure|null)`.
The port's [`dropdownWidth(string|Closure|null)`](../../../packages/actions/src/Concerns/HasDropdown.php)
takes a CSS length string directly — `support` has no `Width` enum, and the value flows
into a `--sy-dropdown-width` CSS variable. `dropdownTeleport()` and `dropdownAutoPlacement()`
are not ported; they configure template-only features deferred with the dropdown rendering
tasks (Task 9's template integration and later dropdown UX features).

### No `isHiddenInGroup()` or visibility caching

Filament's `CanBeHidden` gives `Action` two visibility methods — `isHidden()`
and a distinct `isHiddenInGroup()` — both backed by
`$cachedIsHidden`/`$cachedIsHiddenInGroup` and a
`hasVisibilityCache`/`$cachedVisibilityRecordKey` pair that invalidates the
cache only when the acting record changes; `ActionGroup::resolveIsHidden()`
iterates `getActions()` calling `$action->isHiddenInGroup()`, not `isHidden()`.
The port's [`ActionGroup::isHidden()`](../../../packages/actions/src/ActionGroup.php)
has neither: no `isHiddenInGroup()` exists on `Action` at all — children are
checked via their ordinary `isVisible()` — and nothing is cached, so every call
re-evaluates the `isHidden`/`isVisible` closures fresh. Reversible: port
`isHiddenInGroup()` plus the caching pair together, most naturally alongside
the `tables` package's row rendering, which is what Filament's per-record cache
invalidation exists to serve.

### `liveComponent()` propagates eagerly, unlike Filament's lazy `getGroup()` fallback

Filament resolves `Action::getLivewire()` through a lazy chain that bottoms out
in `getGroup()?->getLivewire()`, walked only when the getter is called —
[`Concerns/BelongsToLivewire.php`](../../../vendor/filament/actions/src/Concerns/BelongsToLivewire.php).
The port's `BelongsToLiveComponent::getLiveComponent()` has no such fallback
(it throws when unset), so
[`ActionGroup::liveComponent()`](../../../packages/actions/src/ActionGroup.php)
instead propagates eagerly — overriding the setter to walk `$this->actions`
and call `$action->liveComponent(...)` on every child immediately, rather than
resolving it lazily on read the way this task's `getRecord()` fallback does
(see the next divergence). One consequence: an action added to a group via a
second `actions()` call *after* `liveComponent()` was already set will not
pick up the live component — the push only fires at `liveComponent()`-call
time. Reversible: fold `getLiveComponent()` into the same lazy
`getGroup()?->getLiveComponent()` shape the moment a use case needs it.

### `getActions()` always pushes a concrete default template, never `null`

Filament's `getActions()` always pushes a concrete view —
`$action->defaultView($this->isButtonGroup() ? $action::BUTTON_VIEW :
$action::GROUPED_VIEW)` — never `null`. An earlier draft of this port's
[`getActions()`](../../../packages/actions/src/ActionGroup.php) pushed `null`
in the non-grouped branch instead, and relied on a bespoke
`Action::getTemplate()` override as a third precedence layer beneath
`ViewComponent`'s normal `template()` ?? `defaultTemplate()` pair, to keep a
button-action baseline alive underneath a cleared default. Corrected to match
Filament's own shape: `Action::BUTTON_TEMPLATE`
(`@SylamentActions/components/button-action.html.twig`) is pushed in the
non-grouped branch instead of `null`, `Action::GROUPED_TEMPLATE` in the grouped
one — always a concrete value — and the `getTemplate()` override is gone.
`Action`'s own baseline now lives in
`protected string|Closure|null $defaultTemplate = self::BUTTON_TEMPLATE;`, a
plain property default rather than Filament's `setUp()`-time
`$this->defaultView(static::BUTTON_VIEW)` call — matching this repo's existing
convention of a `ViewComponent` subclass overriding the property directly (the
same shape the old, since-removed hardcoded `$template` default used), rather
than introducing a `setUp()` override for a single static literal. See
[action.md](action.md#one-unified-template-replaces-the-view-switch) for the
`Action`-side detail.

### `getRecord()` is duplicated onto `Action` and `ActionGroup` to satisfy PHPStan

[`InteractsWithRecord::getRecord()`](../../../packages/actions/src/Concerns/InteractsWithRecord.php)
falls back to `getGroup()?->getRecord()` when its own `$record` evaluates to
`null`, guarded by `method_exists($this, 'getGroup')` so the trait stays safe
for a class that composes it standalone (the `InteractsWithRecordFixture` test
fixture). Both `Action` and `ActionGroup` compose `BelongsToGroup` alongside
`InteractsWithRecord`, so PHPStan (level max) proves that guard always
evaluates to `true` in either class's context — `function.alreadyNarrowedType`.
Rather than suppress it, both classes override `getRecord()` directly with the
same one-line, unguarded body (`$this->evaluate($this->record) ??
$this->getGroup()?->getRecord()`), which is what the guarded trait method
reduces to for a class that always has `getGroup()`. The trait's own guarded
version is unreached by `src/` code today and stays solely for a future class
that mixes in `InteractsWithRecord` without `BelongsToGroup`. Reversible only
by relaxing the guard's shape (e.g. an interface `BelongsToGroup` doesn't
currently pair with) enough that PHPStan can no longer prove it either way —
not attempted here since it would add a type purely to dodge the checker.

### Trigger defaults diverge from Filament: gray over primary, and the ellipsis icon is per-template

Two defaults baked into the new trigger templates depart from Filament's
`ActionGroup` on purpose:

- **Color.** Filament's
  [`CanGenerateButtonHtml`](../../../vendor/filament/support/src/View/Concerns/CanGenerateButtonHtml.php)
  and
  [`CanGenerateIconButtonHtml`](../../../vendor/filament/support/src/View/Concerns/CanGenerateIconButtonHtml.php)
  both coerce an unset color to `'primary'` (`$color ??= 'primary';`), so an
  unconfigured Filament trigger renders primary-colored. The port's
  [`icon-button-trigger.html.twig`](../../../packages/actions/templates/components/action-group/icon-button-trigger.html.twig)
  and
  [`button-trigger.html.twig`](../../../packages/actions/templates/components/action-group/button-trigger.html.twig)
  instead force `group.getColor() ?? 'gray'` — `'gray'` being the value
  support's [`IconButton`](../../../packages/support/templates/components/IconButton/index.html.twig)
  and [`Button`](../../../packages/support/templates/components/Button/index.html.twig)
  templates treat as "no color class"
  (`colorName = color is iterable or color is empty ? 'gray' : color`),
  overriding those components' own `color = 'primary'` prop default. This
  follows the same gray-when-unset convention `Action`'s own
  [`button-action.html.twig`](../../../packages/actions/templates/components/button-action.html.twig)
  already renders (an undocumented divergence there too; noted here since
  Task 8 is the first time the convention crosses into a template that
  delegates to a color-defaulting component instead of building its own
  markup).
  [`grouped-action.html.twig`](../../../packages/actions/templates/components/grouped-action.html.twig)
  has no color handling at all — dropdown-list rows render uniformly
  regardless of the wrapped action's color.
- **Icon.** Filament's
  [`ActionGroup::getIcon()`](../../../vendor/filament/actions/src/ActionGroup.php)
  overrides `HasIcon::getIcon` (aliased `getBaseIcon`) unconditionally —
  `$this->getBaseIcon() ?? FilamentIcon::resolve(...) ??
  Heroicon::EllipsisVertical` — so every one of its five `to*TriggerHtml()`
  methods, including the plain button trigger, always renders some icon. The
  port leaves
  [`HasIcon::getIcon(?default)`](../../../packages/support/src/Components/Concerns/HasIcon.php)
  un-overridden; only `icon-button-trigger.html.twig` passes
  `ActionGroup::DEFAULT_ICON` as that `$default` argument —
  `button-trigger.html.twig` calls `group.getIcon` bare, so a `button()`-mode
  group with no icon set renders no icon at all, unlike Filament's button
  trigger.

Reversible for either point independently: drop the `?? 'gray'` fallback, or
override `getIcon()` on `ActionGroup`, the moment product direction wants an
unconfigured trigger to match Filament's primary/always-ellipsis defaults.
