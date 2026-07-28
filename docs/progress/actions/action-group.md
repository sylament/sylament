# ActionGroup (actions)

`Sylament\Actions\ActionGroup` — groups a list of `Action`/`ActionGroup` children
behind a single trigger, so a table row or a toolbar can collapse many actions
into one control. It composes `Sylament\Support\Components\ViewComponent`
(Twig-backed rendering, the same base `Action` extends) and folds in its own
actions-package concern, `BelongsToGroup` — shared with `Action` (both now
compose it), written up here rather than split into a third detail file, since
its whole surface is a two-method back-reference pair. It also reuses four
already-folded `Action` concerns unchanged — `CanBeOutlined`, `HasGroupedIcon`,
`HasLabel`, and `HasSize`, tracked
in [`action.md`](action.md) — and five support-level traits (`HasColor`,
`HasExtraAttributes`, `HasIcon`, `HasIconSize`, `HasTooltip`) that stay tracked
in the [`support`](../support/README.md) package and appear here only as
reference rows. The model core (the children list, the eager name-keyed flat
map, and the group back-reference wiring), the dropdown trigger config,
visibility composition, context propagation to children (`liveComponent()`,
`getRecord()` fallback, default-template push onto `Action` children), and the
rendering of both extra-attribute bags (the shared trigger bag plus the
group-only `extraDropdownAttributes()` family) have landed. Still to come:
propagating dropdown placement and trigger-view defaults to nested groups (see
_Blocked by_ below).

**Status:** in progress — the dropdown-rendering base (model, visibility,
triggers, and `render()` producing the complete dropdown or inline row) is
complete; the remaining rows are deferred (nested-group propagation and
rendering, additional trigger kinds, teleport/auto-placement, `Arrayable`,
table/bulk integration) — see the [status vocabulary](../README.md#statuses).

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
| [`CanBeOutlined`](../../../packages/actions/src/Concerns/CanBeOutlined.php) (shared with `Action`; consumed by the button trigger only, Filament-same) | [`Concerns/CanBeOutlined.php`](../../../vendor/filament/actions/src/Concerns/CanBeOutlined.php) | done | — | - |
| [`HasGroupedIcon`](../../../packages/actions/src/Concerns/HasGroupedIcon.php) (shared with `Action`) | [`Concerns/HasGroupedIcon.php`](../../../vendor/filament/actions/src/Concerns/HasGroupedIcon.php) | in progress | submenu rendering | [detail](#hasgroupedicon-is-api-only-on-the-group) |
| [`HasLabel`](../../../packages/actions/src/Concerns/HasLabel.php) (shared with `Action`) | [`Concerns/HasLabel.php`](../../../vendor/filament/actions/src/Concerns/HasLabel.php) | done | — | - |
| [`HasSize`](../../../packages/actions/src/Concerns/HasSize.php) (shared with `Action`) | [`Concerns/HasSize.php`](../../../vendor/filament/actions/src/Concerns/HasSize.php) | done | — | - |
| [`HasColor`](../../../packages/support/src/Components/Concerns/HasColor.php) (support) | [`Concerns/HasColor.php`](../../../vendor/filament/support/src/Concerns/HasColor.php) | done | — | - |
| [`HasExtraAttributes`](../../../packages/support/src/Components/Concerns/HasExtraAttributes.php) (support; rendered onto the trigger root) | [`Concerns/HasExtraAttributes.php`](../../../vendor/filament/support/src/Concerns/HasExtraAttributes.php) | done | — | [diverges](#extra-attribute-bags-flatten-through-toscalarattributes-before-reaching-the-ux-component-attribute-bag) |
| `extraDropdownAttributes()` family (`getExtraDropdownAttributes()`/`getExtraDropdownAttributeBag()`/`hasExtraDropdownAttributes()`; rendered onto the outer dropdown wrapper) | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`extraDropdownAttributes()`) | done | — | [diverges](#extra-attribute-bags-flatten-through-toscalarattributes-before-reaching-the-ux-component-attribute-bag) |
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
| Rendering (`toEmbeddedHtml()`) | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`toEmbeddedHtml()`) | done | — | [diverges](#rendering-refuses-any-visible-nested-group-eagerly-instead-of-filaments-flattenembed-behavior), [diverges](#dropdownfalse-wraps-its-inline-row-in-a-bare-divsysy-ac-group-unlike-filaments-unwrapped-concatenation) |
| Badge/button-group/grouped/link triggers | [`View/Concerns/CanGenerate*Html.php`](../../../vendor/filament/support/src/View/Concerns) | todo | — | - |
| Submenu rendering (nested non-dropdown groups) | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`toEmbeddedHtml()` action-list branch) | todo | — | - |
| Dropdown teleport & auto-placement | [`Concerns/HasDropdown.php`](../../../vendor/filament/actions/src/Concerns/HasDropdown.php) (`dropdownTeleport()`/`dropdownAutoPlacement()`) | todo | — | - |
| `toArray()`/`fromArray()` (`Arrayable`) | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`toArray()`/`fromArray()`) | todo | — | - |
| Table and bulk concerns | [`Concerns/BelongsToTable.php`](../../../vendor/filament/actions/src/Concerns/BelongsToTable.php), `hasNonBulkAction()` | blocked | tables package, BulkAction | [detail](#table-and-bulk-concerns-are-blocked-on-the-tables-and-bulkaction-ports) |

## Details

### `HasGroupedIcon` is API-only on the group

The trait itself is the same file `Action` already composes (folded into
[`action.md`](action.md)); composing it onto `ActionGroup` pins the fluent API
(`groupedIcon()`/`getGroupedIcon()`/`hasGroupedIcon()` round-trip and are
unit-tested on the group). Nothing renders it yet, matching where Filament
consumes a *group's* grouped icon: only `toDropdownItemHtml()` — the shape a
group takes when it nests inside another group's dropdown — reads it, and
submenu rendering is unbuilt here (see the "Submenu rendering" row). Wire the
grouped icon into the dropdown-item trigger in the same change that lands
submenus.

### Table and bulk concerns are blocked on the tables and BulkAction ports

Filament's `ActionGroup` composes `BelongsToTable`, and exposes
`hasNonBulkAction()` (iterates `getFlatActions()` calling `$action->isBulk()`).
`BelongsToSchemaComponent` — Filament's other composed `ActionGroup` concern —
already landed (see the [package README](README.md#features)); `BelongsToTable`
has no Sylament target yet: there is no `tables`-package integration point
(`BelongsToTable`'s host) and no `BulkAction` class (`isBulk()`'s only caller)
in the port. **Blocked** on both landing; add `BelongsToTable` (or its Sylament
equivalent) and `hasNonBulkAction()` in the same change that ports whichever
comes first.

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

### Extra attribute bags flatten through `toScalarAttributes()` before reaching the ux component attribute bag

Both group bags now render, in the Filament-parallel places: the trigger bag
(`HasExtraAttributes`, shared support trait) lands on the trigger root — both
[`icon-button-trigger.html.twig`](../../../packages/actions/templates/components/action-group/icon-button-trigger.html.twig)
and
[`button-trigger.html.twig`](../../../packages/actions/templates/components/action-group/button-trigger.html.twig)
merge it into the composed support component, mirroring how Filament's five
`to*TriggerHtml()` methods each merge `$this->getExtraAttributes()` onto the
trigger element
([`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php)
lines 631/648/670/688/706) — and the dropdown bag
(`extraDropdownAttributes()`/`getExtraDropdownAttributes()`/
`getExtraDropdownAttributeBag()`/`hasExtraDropdownAttributes()`, duplicated
onto [`ActionGroup`](../../../packages/actions/src/ActionGroup.php) in
support's exact `HasExtraAttributes` shape from Filament's hand-written pair,
`ActionGroup.php:759-789`) lands on the outer dropdown wrapper (Filament: the
`fi-dropdown` root, ours: the `sy sy-dropdown` root) via
[`action-group.html.twig`](../../../packages/actions/templates/components/action-group.html.twig).

The one mechanical divergence: all three templates were converted from the
`<twig:>` HTML syntax to the `{% component %}` tag (the HTML syntax cannot
spread a computed attribute map), and each merges the bag as
`getExtra…AttributeBag.toScalarAttributes` rather than the raw
`getExtra…Attributes` array. That flattening seam is load-bearing, not
cosmetic: support's `ComponentAttributeBag` normalizes `class` to a token
**array** and passes `null` values through, and ux-twig-component's
`ComponentAttributes` fatals on both (`LogicException` for a non-scalar value,
`RuntimeException` for `null` — `vendor/symfony/ux-twig-component/src/ComponentAttributes.php`).
`toScalarAttributes()` joins class tokens to a string and drops
`null`/`false`, so a caller's `class` token-merges with the component root's
own classes through `attributes.defaults()` instead of crashing. Filament has
no such seam because its Blade side renders its own attribute bag directly.

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
[action.md](action.md#the-view-switch-renders-through-twig-templates-not-tohtml-fast-paths)
for the `Action`-side detail.

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

### Rendering refuses any visible nested group eagerly, instead of Filament's flatten/embed behavior

Filament's `toEmbeddedHtml()` has no guard against nesting at all: a nested
`ActionGroup` with `hasDropdown() === false` gets flattened into its own
`fi-dropdown-list` section inside the parent's panel (a visual divider, not a
real submenu), and a nested `ActionGroup` that still has a dropdown is embedded
directly via its own `toHtml()` call — Filament happily renders a dropdown
inside a dropdown. Neither behavior is ported yet (tracked by the "Submenu
rendering" and nested-group-propagation rows above); the port's
[`ActionGroup::render()`](../../../packages/actions/src/ActionGroup.php)
instead fails fast for both cases alike — any direct child that is itself a
visible `ActionGroup` throws
`LogicException('Submenu rendering is not supported yet: an ActionGroup
cannot be rendered inside another ActionGroup.')` — so a caller gets a loud,
immediate error instead of silently wrong markup.

Task 9's plan drafted this guard as a self-check inside `render()`
(`if ($this->getGroup() !== null) { throw ...; }`), fired lazily only when a
nested group's own `render()` happens to be invoked. That shape doesn't
survive contact with Twig: a nested group is reached via
`{{ renderComponent(action) }}` inside the parent's own
`action-group.html.twig`, so the throw would occur while the parent's template
is still executing inside `Twig\Template::yield()`. Twig's own
`yield()` (`vendor/twig/twig/src/Template.php`) catches any `\Throwable` that
isn't already a `Twig\Error\Error` and rewraps it as a `Twig\Error\RuntimeError`
carrying the original as `getPrevious()` — confirmed by running the brief's own
test unmodified, which failed with exactly that wrapped type. The implemented
guard instead checks eagerly, in `render()` itself, before any Twig rendering
starts: `array_any($this->getActions(), fn (Action|ActionGroup $action):
bool => $action instanceof self && $action->isVisible())`. Checking
`getActions()` (not the raw `$actions` property) and gating on `isVisible()`
mirrors exactly what the template's own `|filter(action => action.isVisible)`
would have reached — a *hidden* nested group is not an error, since it would
never have been rendered anyway. One behavior change from the drafted shape:
the original would also refuse a nested group rendered **directly** in
isolation (`$nestedGroup->render($twig)` called on its own, bypassing its
parent's template) purely because `getGroup() !== null`, even though nothing
about rendering it stand-alone is actually unsupported; the implemented guard
only refuses when a *visible* `ActionGroup` is discovered as one of the
rendering group's own children, which is the one case that is genuinely
unbuilt. Reversible the moment submenu/nested-dropdown rendering lands: replace
the guard with the real flatten/embed branching this divergence describes.

One more consequence of that eager placement: the `LogicException` contract
above holds only when `render()` is called directly from PHP, the way
[`ActionGroupRenderTest`](../../../packages/actions/tests/Integration/ActionGroupRenderTest.php)'s
own `renderGroup()` helper does. A host template that instead embeds the group
via the `renderComponent(group)` Twig function — the normal path for any
`ActionGroup` nested inside another template's own rendering — has that
`render()` call happen while Twig's `Template::yield()` is executing the
*host* template, so the same `\Throwable`-catching behavior cited above
applies to it too: the exception arrives at that caller wrapped in a
`Twig\Error\RuntimeError`, with the original `LogicException` reachable via
`getPrevious()`, not as a bare `LogicException`. Nothing under
`packages/actions/tests` exercises that second call shape today.

### `dropdown(false)` wraps its inline row in a bare `div.sy.sy-ac-group`, unlike Filament's unwrapped concatenation

Filament's non-dropdown branch of `toEmbeddedHtml()`
([`ActionGroup.php:484-489`](../../../vendor/filament/actions/src/ActionGroup.php))
returns `collect($this->getActions())->filter(...)->map(fn ($action) =>
$action->toHtml())->implode('')` — the visible children's markup concatenated
directly, with no wrapper element of any kind. The port's
[`action-group.html.twig`](../../../packages/actions/templates/components/action-group.html.twig)
instead wraps the same loop in `<div class="sy sy-ac-group">…</div>` before
the `{% else %}` branch's children. Unlike Filament's bare concatenation, the
wrapper is a real layout hook: actions'
[`action-group.css`](../../../packages/actions/assets/src/styles/components/action-group.css)
gives `.sy-ac-group` `@apply inline-flex items-center gap-2`, so the inline
row's children lay out horizontally with spacing — a rule Filament's
unwrapped output has no element to carry. Reversible by dropping the wrapper
(and its CSS rule) and emitting the loop bare the moment parity with
Filament's unwrapped shape matters more than the flex layout.
