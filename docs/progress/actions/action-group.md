# ActionGroup (actions)

`Sylament\Actions\ActionGroup` — groups a list of `Action`/`ActionGroup` children
behind a single trigger, so a table row or a toolbar can collapse many actions
into one control. It composes `Sylament\Support\Components\ViewComponent`
(Twig-backed rendering, the same base `Action` extends) and folds in its own
actions-package concern, `BelongsToGroup` — shared with `Action` (both now
compose it), written up here rather than split into a third detail file, since
its whole surface is the back-reference pair plus the root walk. `BelongsToTable`
is shared the same way but earns
[its own file](belongs-to-table.md). It also reuses four
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
group-only `extraDropdownAttributes()` family) have landed. Submenus have
landed too: `getDropdownActionLists()` ports Filament's dropdown rendering
plan, so a nested group either flattens into its own divider-separated section
or embeds as a real dropdown-in-dropdown, with placement and trigger-template
defaults propagated to it.

**Status:** in progress — the dropdown-rendering base (model, visibility,
triggers, submenus, and `render()` producing the complete dropdown or inline
row) is complete, as is the per-row layer a table needs (the table binding, the
root-group walk, and the deep clone), as is serialization through
`toArray()`/`fromArray()`; the remaining rows are deferred
(additional trigger kinds, auto-placement, bulk actions)
— see the [status vocabulary](../README.md#statuses).

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
write-up in [Details](#details) (deleted once the row is **done**), the
permanent faithful-but-subtle note in [Semantics](#semantics), and/or the
permanent note in [Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Construction, `make()` & `actions()`/`getActions()`/`getFlatActions()` | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) | done | — | — |
| [`BelongsToGroup`](../../../packages/actions/src/Concerns/BelongsToGroup.php) incl. `getRootGroup()` | [`Concerns/BelongsToGroup.php`](../../../vendor/filament/actions/src/Concerns/BelongsToGroup.php) | done | — | `getRootGroup()` is a verbatim port of the walk up `getGroup()` to the outermost group (`null` for an ungrouped action). It ships ahead of its consumer: Filament resolves a mounted **table** action and stamps the row's record on `getRootGroup()` rather than on the member it matched, so one record covers the whole group and [`InteractsWithRecord::getRecord()`](#getrecord-is-duplicated-onto-action-and-actiongroup-to-satisfy-phpstan)'s group fallback carries it back down to every member. Covered by [`BelongsToGroupTest`](../../../packages/actions/tests/Unit/Concerns/BelongsToGroupTest.php). Also [diverges](#groups-parameter-defaults-to-null) |
| [`BelongsToTable`](../../../packages/actions/src/Concerns/BelongsToTable.php) (shared with `Action`) | [`Concerns/BelongsToTable.php`](../../../vendor/filament/actions/src/Concerns/BelongsToTable.php) | done | — | [belongs-to-table](belongs-to-table.md) |
| `getClone()` / `cloneActions()` | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`getClone()`/`cloneActions()`) | done | — | [diverges](#getclone-rebuilds-the-flat-map-from-the-cloned-members-rather-than-cloning-it-separately) |
| `getLabel()` default | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`getLabel()`) | done | — | [diverges](#getlabel-falls-back-to-a-plain-string-not-a-translated-label) |
| [`CanBeOutlined`](../../../packages/actions/src/Concerns/CanBeOutlined.php) (shared with `Action`; consumed by the button trigger only, Filament-same) | [`Concerns/CanBeOutlined.php`](../../../vendor/filament/actions/src/Concerns/CanBeOutlined.php) | done | — | - |
| [`HasGroupedIcon`](../../../packages/actions/src/Concerns/HasGroupedIcon.php) (shared with `Action`) | [`Concerns/HasGroupedIcon.php`](../../../vendor/filament/actions/src/Concerns/HasGroupedIcon.php) | done | — | Consumed by [`GroupedTrigger.html.twig`](../../../packages/actions/templates/partials/ActionGroup/GroupedTrigger.html.twig) as `group.getIcon(group.getGroupedIcon)` — the shape a group takes inside another group's dropdown, exactly where Filament's `toGroupedTriggerHtml()` reads it |
| [`HasLabel`](../../../packages/actions/src/Concerns/HasLabel.php) (shared with `Action`) | [`Concerns/HasLabel.php`](../../../vendor/filament/actions/src/Concerns/HasLabel.php) | done | — | - |
| [`HasSize`](../../../packages/actions/src/Concerns/HasSize.php) (shared with `Action`) | [`Concerns/HasSize.php`](../../../vendor/filament/actions/src/Concerns/HasSize.php) | done | — | - |
| [`HasColor`](../../../packages/support/src/Components/Concerns/HasColor.php) (support) | [`Concerns/HasColor.php`](../../../vendor/filament/support/src/Concerns/HasColor.php) | done | — | - |
| [`HasExtraAttributes`](../../../packages/support/src/Components/Concerns/HasExtraAttributes.php) (support; rendered onto the trigger root) | [`Concerns/HasExtraAttributes.php`](../../../vendor/filament/support/src/Concerns/HasExtraAttributes.php) | done | — | [diverges](#extra-attribute-bags-flatten-through-toscalarattributes-before-reaching-the-ux-component-attribute-bag) |
| `extraDropdownAttributes()` family (`getExtraDropdownAttributes()`/`getExtraDropdownAttributeBag()`/`hasExtraDropdownAttributes()`; rendered onto the outer dropdown wrapper) | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`extraDropdownAttributes()`) | done | — | [diverges](#extra-attribute-bags-flatten-through-toscalarattributes-before-reaching-the-ux-component-attribute-bag) |
| [`HasIcon`](../../../packages/support/src/Components/Concerns/HasIcon.php) (support) | [`Concerns/HasIcon.php`](../../../vendor/filament/support/src/Concerns/HasIcon.php) | done | — | - |
| [`HasIconSize`](../../../packages/support/src/Components/Concerns/HasIconSize.php) (support) | [`Concerns/HasIconSize.php`](../../../vendor/filament/support/src/Concerns/HasIconSize.php) | done | — | - |
| [`HasTooltip`](../../../packages/support/src/Components/Concerns/HasTooltip.php) (support) | [`Concerns/HasTooltip.php`](../../../vendor/filament/support/src/Concerns/HasTooltip.php) | done | — | - |
| `HasDropdown` | [`Concerns/HasDropdown.php`](../../../vendor/filament/actions/src/Concerns/HasDropdown.php) | done | — | [diverges](#dropdownwidth-is-a-css-string-and-dropdownautoplacement-is-deferred) |
| Visibility composition (`isHidden()` group-iteration) | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`isHidden()`/`resolveIsHidden()`) | done | — | [diverges](#no-ishiddeningroup-or-visibility-caching) |
| Context propagation to children (`liveComponent()`, `getRecord()` fallback, default-template push onto `Action` children) | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`liveComponent()`/`getActions()`) | done | — | [diverges](#livecomponent-propagates-eagerly-unlike-filaments-lazy-getgroup-fallback), [diverges](#getrecord-is-duplicated-onto-action-and-actiongroup-to-satisfy-phpstan), [diverges](#getactions-always-pushes-a-concrete-default-template-never-null) |
| Closure dependency resolution (`resolveDefaultClosureDependencyForEvaluationByName()` / `…ByType()`) | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) | in progress | tables (`table`), the Action→schema record hop | By name: `record`, `liveComponent`, `schema`, `schemaComponent`/`component`, `schemaGet`/`get`, `schemaComponentState`/`state`, `schemaState` — Filament's `model`, `mountedActions` and `table` are unported, and it has no `set` arm either (upstream gives the group `get` only). By type: `Get::class`, which [diverges](action.md#getset-resolve-by-type-as-well-as-by-name) — Filament resolves nothing by type here but the record. The record arm waits on the same `getRecord()` schema walk as `Action`'s |
| Trigger-template-default propagation to nested groups (`defaultTriggerTemplate()`) | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`getActions()`, `defaultTriggerView()`) | done | — | [diverges](#the-child-template-split-keys-off-hasdropdown-where-filament-keys-off-isbuttongroup) |
| Grouped item template | [`components/dropdown/list/item.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) | done | — | - |
| Grouped **trigger** template ([`action-group/GroupedTrigger.html.twig`](../../../packages/actions/templates/partials/ActionGroup/GroupedTrigger.html.twig)) | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`toGroupedTriggerHtml()`) | in progress | badge support | [detail](#the-grouped-trigger-drops-the-badge-and-filaments-default-ellipsis-icon) |
| Trigger machinery | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`getTriggerView()`/`toTriggerHtml()`) | done | — | [diverges](#trigger-defaults-diverge-from-filament-gray-over-primary-and-the-ellipsis-icon-is-per-template) |
| Rendering (`toEmbeddedHtml()`) incl. [`getDropdownActionLists()`](../../../packages/actions/src/ActionGroup.php) — flattened sections and embedded submenus | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`toEmbeddedHtml()`) | done | — | [diverges](#the-dropdown-plan-is-a-php-method-the-template-loops-not-an-inline-output-buffer), [diverges](#dropdownfalse-wraps-its-inline-row-in-a-bare-divsysy-ac-group-unlike-filaments-unwrapped-concatenation) |
| Link **trigger** template ([`action-group/LinkTrigger.html.twig`](../../../packages/actions/templates/partials/ActionGroup/LinkTrigger.html.twig)), with `link()` / `isLink()` | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`toLinkTriggerHtml()`, `link()`/`isLink()`) | in progress | badge support | [detail](#the-link-trigger-carries-every-ported-prop-and-drops-the-badge) |
| Badge/button-group triggers | [`View/Concerns/CanGenerate*Html.php`](../../../vendor/filament/support/src/View/Concerns) | todo | — | [detail](#the-button-group-trigger-is-unported-which-is-why-hasdropdown-stands-in-for-isbuttongroup) |
| Dropdown teleport (`dropdownTeleport()` / `defaultDropdownTeleport()`) | [`Concerns/HasDropdown.php`](../../../vendor/filament/actions/src/Concerns/HasDropdown.php) (`dropdownTeleport()`/`defaultDropdownTeleport()`/`hasDropdownTeleport()`) | done | — | [semantics](#dropdownteleport-is-a-positioning-strategy-not-a-dom-move-in-either-codebase) |
| Dropdown auto-placement | [`Concerns/HasDropdown.php`](../../../vendor/filament/actions/src/Concerns/HasDropdown.php) (`dropdownAutoPlacement()`) | todo | — | [diverges](#dropdownwidth-is-a-css-string-and-dropdownautoplacement-is-deferred) |
| `toArray()` / `fromArray()` — the 17 ported keys | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`toArray()`/`fromArray()`) | done | — | [diverges](#the-groups-serialized-enum-values-flatten-to-scalars-and-a-scalableicon-crosses-unflattened) · [semantics](#the-groups-serialized-key-list-drops-iconposition-and-vendors-view) · [semantics](#fromarrays-hasdropdownflip-fallback-is-false-where-the-property-defaults-to-true) · [semantics](#fromarray-restores-only-the-four-built-in-trigger-templates) |
| `hasNonBulkAction()` | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`hasNonBulkAction()`) | blocked | BulkAction | [detail](#hasnonbulkaction-is-blocked-on-the-bulkaction-port) |

## Details

### The grouped trigger drops the badge and Filament's default ellipsis icon

[`GroupedTrigger.html.twig`](../../../packages/actions/templates/partials/ActionGroup/GroupedTrigger.html.twig)
is the shape a nested group takes as a row inside its parent's dropdown panel —
the port of Filament's `toGroupedTriggerHtml()`. It renders the icon
(`group.getIcon(group.getGroupedIcon)`), the label in a
`span.sy-dropdown-item-label`, the tooltip as `title`, and the extra-attribute
bag, on a `<button type="button">` classed
`sy sy-dropdown-item sy-ac` — matching what the sibling
[`Action/Grouped.html.twig`](../../../packages/actions/templates/partials/Action/Grouped.html.twig)
emits for a plain action, so a submenu row and an action row line up.

Two gaps. **Badge**: Filament threads `badge` / `badgeColor` / `badgeTooltip`
through `generateDropdownItemHtml()`; no `HasBadge` concern exists in the port
at all, so the whole family is absent here and everywhere else — this row
returns with it. **Default icon**: Filament's `ActionGroup::getIcon()` override
falls back to `FilamentIcon::resolve(ActionsIconAlias::ACTION_GROUP) ??
Heroicon::EllipsisVertical`, so *every* Filament trigger always renders an icon;
the port leaves support's `HasIcon::getIcon(?default)` un-overridden and each
template supplies its own default, so a submenu row with neither `icon()` nor
`groupedIcon()` set renders label-only. That is the same
[per-template ellipsis choice](#trigger-defaults-diverge-from-filament-gray-over-primary-and-the-ellipsis-icon-is-per-template)
the icon-button and button triggers already make, extended to this fourth
template; reverse it by passing `ActionGroup::DEFAULT_ICON` as the fallback
here, or by overriding `getIcon()` once for all four.

Filament also has no submenu **affordance** to port on this element — no
chevron — and the port drops its `fi-ac-grouped-group` marker class, so a
submenu row is not distinguishable from a plain action row by CSS today. Add
the marker class first if a chevron is ever wanted.

### The link trigger carries every ported prop and drops the badge

[`LinkTrigger.html.twig`](../../../packages/actions/templates/partials/ActionGroup/LinkTrigger.html.twig)
is the port of Filament's `toLinkTriggerHtml()`, selected by `link()` (or a
raw `triggerTemplate(ActionGroup::LINK_TEMPLATE)`) and reported by `isLink()` —
the same setter/predicate pair `button()`/`isButton()` and
`iconButton()`/`isIconButton()` already carry, which is why the constant is
wired in four places rather than one: the pair, the
[restore whitelist](#fromarray-restores-only-the-four-built-in-trigger-templates),
and the template file itself. It composes support's
[`Link`](../../../packages/support/templates/components/Link/index.html.twig)
with `tag="button"` — the primitive defaults to `'a'`, and a dropdown trigger is
never an anchor — and threads through every prop Filament passes that the port
has: `icon`, `iconSize`, `color`, `size`, `labelSrOnly` (from
`isLabelHidden()`), the tooltip as `title`, the label as content, and the extra
attribute bag.

Two of Filament's arguments have nothing to map to. **`badge` / `badgeColor`**:
no `HasBadge` concern exists anywhere in the port, the same gap
[the grouped trigger carries](#the-grouped-trigger-drops-the-badge-and-filaments-default-ellipsis-icon).
**`iconPosition`**: `ActionGroup` composes no `HasIconPosition`, so there is no
getter to read — the group's `toArray()`
[drops the key](#the-groups-serialized-key-list-drops-iconposition-and-vendors-view)
for the same reason, and the `Link` primitive's own `iconPosition = 'before'`
default stands.

Its prop list is deliberately wider than
[`ButtonTrigger.html.twig`](../../../packages/actions/templates/partials/ActionGroup/ButtonTrigger.html.twig)'s,
which passes only `icon` / `color` / `outlined` / `size` and silently ignores a
group's `iconSize`, `hiddenLabel()` and `tooltip()` even though Filament's
`toButtonTriggerHtml()` threads all three. That is the button trigger's gap, not
a convention worth copying; widen it when a caller trips over it.

### The button-group trigger is unported, which is why `hasDropdown()` stands in for `isButtonGroup()`

Filament ships six trigger views —
`BADGE_VIEW`, `BUTTON_VIEW`, `BUTTON_GROUP_VIEW`, `GROUPED_VIEW`,
`ICON_BUTTON_VIEW`, `LINK_VIEW`
([`ActionGroup.php:70-80`](../../../vendor/filament/actions/src/ActionGroup.php)) —
with `buttonGroup()` / `isButtonGroup()`
selecting a **non-dropdown row of buttons** (`div.fi-btn-group`), distinct from
`button()`, which is a dropdown whose trigger happens to look like a button.
The port has `BUTTON_TEMPLATE`, `ICON_BUTTON_TEMPLATE`, `GROUPED_TEMPLATE` and
now [`LINK_TEMPLATE`](#the-link-trigger-carries-every-ported-prop-and-drops-the-badge);
badge and **button-group** have none, which is why
`isButtonGroup()` has nothing to be ported as and
[`hasDropdown()` stands in for it](#the-child-template-split-keys-off-hasdropdown-where-filament-keys-off-isbuttongroup).
The port's inline-row shape is `dropdown(false)`, which Filament also supports
and which is the closer analogue to `fi-btn-group` in practice. **Next step:**
port the remaining `CanGenerate*Html` trigger shapes; adding
`BUTTON_GROUP_TEMPLATE` is the point at which the `isButtonGroup()` predicate
becomes portable and the stand-in can be retired.

### `hasNonBulkAction()` is blocked on the BulkAction port

Filament's `ActionGroup::hasNonBulkAction()` iterates `getFlatActions()`
calling `$action->isBulk()`. The port has no `BulkAction` class and no
`isBulk()` predicate on `Action`, so the method has nothing to ask — this is
the group-side half of the same gap
[`getContext()`'s missing `bulk` flag](action.md#getcontext-drops-the-record-model-gate-and-the-bulk-flag)
tracks. **Blocked** on `BulkAction`; port both together. The other half of
this row's former blocker is gone: `BelongsToTable` now ships
([belongs-to-table.md](belongs-to-table.md)).

## Semantics

Faithful ports whose behaviour a reader would plausibly misread from the source.
Permanent — these outlive the detail sections above.

### `dropdownTeleport()` is a positioning strategy, not a DOM move, in either codebase

The name says teleport and the Blade side reinforces it — Filament's dropdown view
spells the flag as an `x-float` modifier, `{{ $teleport ? '.teleport' : '' }}`, and
Alpine's own `x-teleport` directive moves an element to another parent. The
`@awcodes/alpine-floating-ui` plugin behind `x-float` does no such thing. Its
modifier parser reads, in the compiled
[`support/dist/index.js`](../../../vendor/filament/support/dist/index.js),
`r.includes("teleport")&&(t.strategy="fixed")` — the whole effect is Floating UI's
`strategy` option flipping from `absolute` to `fixed`. The panel stays exactly where
it was in the DOM.

The port carries the name and that mechanism.
[`dropdownTeleport()`](../../../packages/actions/src/Concerns/HasDropdown.php) threads
a `teleport` prop to `<twig:SylamentSupport:Dropdown>`, which passes it as a Stimulus
value to the
[dropdown controller](../../../packages/support/assets/src/controllers/dropdown_controller.ts);
the controller asks Floating UI for `strategy: "fixed"` and writes `position: fixed`
inline over the stylesheet's `absolute`. What that buys is the reason the flag exists:
a fixed-position element's containing block is the viewport, so an ancestor's
`overflow: hidden` no longer clips it. That is why the
[always-`relative` root](../support/dropdown.md#the-root-is-a-positioned-shrink-to-fit-box-where-filaments-carries-no-rule-at-all)
costs nothing here — the fixed panel ignores its offsetParent entirely. Only the
notification toast opts in today, through
[its re-view's default](../notifications/notification.md#the-re-view-defaults-a-group-to-teleport-where-filaments-toast-groups-clip);
the scrolling table container is the [next candidate](../tables/table.md) and is not
stamped yet.

The property pair is vendor-exact and the `??` between them is the whole of it:
`$hasDropdownTeleport` and `$hasDefaultDropdownTeleport` are both **nullable** and
default to `null`, so `hasDropdownTeleport()` falls through to the default only when
nothing explicit was set. A `?:` chain in its place would read an explicit
`dropdownTeleport(false)` as absent and hand the decision back to whichever host
stamped a default — the one way a caller can be overruled by a *default*, which is
what a default is not.

The precondition a reader will not think to check is that `fixed` is only viewport-relative
while **no** ancestor establishes a containing block for fixed descendants — any resting
`transform`, `filter`, `perspective`, `contain: paint` or `will-change` naming one of them
re-anchors it to that ancestor and the clipping comes back. Filament inherits the same
constraint, unstated. The toast is clean today: `.sy-no-notification` transitions
`scale`/`translate` and gives them `@starting-style` values, but at rest both are `none`,
so nothing is established once the entry transition has finished.

### The group's serialized key list drops `iconPosition` and vendor's `view`

[`toArray()`](../../../packages/actions/src/ActionGroup.php) writes 17 keys
where Filament's writes 19, and each of the two gaps is a getter that does not
exist on this side rather than a value judged not worth carrying.
**`iconPosition`** — Filament's `ActionGroup` composes `HasIconPosition` and
this one does not (the trigger templates render the group's icon in a fixed
position), so there is no `getIconPosition()` to call; the sibling
[`Action`](action.md#the-serialized-key-list-drops-alpineclickhandler-and-shouldposttourl)
*does* compose it and *does* serialize it, which is what makes the group's
omission look like a slip. **`view`** — Filament's group serializes its own
custom template as `hasView() ? getView() : null`, the escape hatch for a group
rendered through a user-supplied view; that surface is not ported, and
`fromArray()` never reads a `view` key, so there is nothing on the restore side
for the value to land in.
Vendor's `triggerView` is the key that *did* survive, renamed `triggerTemplate`
by the systematic Blade → Twig translation in [`renames.md`](../renames.md).

Neither class implements an interface for the pair. Filament's `Action` and
`ActionGroup` both implement Laravel's `Illuminate\Contracts\Support\Arrayable`,
which is what lets `collect($this->getActions())->toArray()` serialize the
children by walking a contract; there is no Symfony counterpart to adopt, so
`toArray()`/`fromArray()` are plain public methods and the group maps its
children by union type (`Action|self`) instead.

Two shapes on the restore side are load-bearing and read as noise. The nested
group recurses through `self::fromArray()` where Filament writes
`ActionGroup::fromArray()` — not a choice, since PHP-CS-Fixer's `self_accessor`
rule rewrites the class name inside its own class, and the consequence is that
the call **forwards** late static binding (a subclass restores nested groups as
itself, where Filament's non-forwarding call resets them to the base). And each
child payload passes through
`array_filter($action, is_string(...), ARRAY_FILTER_USE_KEY)` before the
discriminator reads it: `$data['actions']` is `mixed`, so its entries narrow
only to `array<int|string, mixed>`, and dropping the non-string keys is what
proves the declared `array<string, mixed>` at level max. It is lossless —
every read inside `fromArray()` is by string key — so removing it would trade a
silent behaviour change for a static-analysis failure.

### `fromArray()`'s `hasDropdownFlip` fallback is `false` where the property defaults to `true`

Reading
[`$static->dropdownFlip($data['hasDropdownFlip'] ?? false)`](../../../packages/actions/src/ActionGroup.php)
next to `$static->dropdown($data['hasDropdown'] ?? true)`, the obvious conclusion
is that each fallback restates its property's default. Only the second one does:
`HasDropdown` declares **both** `$hasDropdown` and `$hasDropdownFlip` as `true`,
so a payload that omits the flip key restores a group with flipping *off*.
This is copied from Filament, whose `fromArray()` and `HasDropdown` disagree the
same way, so it is parity rather than a slip — and it is unreachable through the
pair itself, since `toArray()` always writes the key. Only a hand-built or
truncated payload can hit it. Corrected here, a group would round-trip
differently from its Filament counterpart; the divergence is not worth that.

### `fromArray()` restores only the four built-in trigger templates

Same trust boundary as
[`Action`'s template whitelist](action.md#fromarray-restores-only-the-five-built-in-templates),
and the same shape: a restored `triggerTemplate` reaches `{% include %}` in
[`ActionGroup/index.html.twig`](../../../packages/actions/templates/partials/ActionGroup/index.html.twig),
so [`fromArray()`](../../../packages/actions/src/ActionGroup.php) admits it only
when it is one of the group's own `ICON_BUTTON_TEMPLATE` / `BUTTON_TEMPLATE` /
`GROUPED_TEMPLATE` / `LINK_TEMPLATE` constants. Anything else is dropped and
`getTriggerTemplate()` falls through its
`triggerTemplate → defaultTriggerTemplate → ICON_BUTTON_TEMPLATE` cascade — so
a tampered value lands on whatever the group's *context* asked for, which for a
nested group is the `GROUPED_TEMPLATE` its parent stamped. The whitelist grows
with the constant list; it is written out rather than derived, so adding a
trigger kind means editing both.

## Divergences

### `group()`'s parameter defaults to `null`

The port's `group(?ActionGroup $group = null): static` gives its parameter a
default of `null` where Filament's is required; harmless since `null` is the
effective value every caller passes today, and it lets `->group()` clear the
association without spelling out the `null` argument.

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
[`IconButtonTrigger.html.twig`](../../../packages/actions/templates/partials/ActionGroup/IconButtonTrigger.html.twig)
and
[`ButtonTrigger.html.twig`](../../../packages/actions/templates/partials/ActionGroup/ButtonTrigger.html.twig)
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
[`ActionGroup/index.html.twig`](../../../packages/actions/templates/partials/ActionGroup/index.html.twig).

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

### `dropdownWidth` is a CSS string and `dropdownAutoPlacement` is deferred

Filament's `HasDropdown` carries a `Width` enum and types `dropdownWidth(Width|Closure|null)`.
The port's [`dropdownWidth(string|Closure|null)`](../../../packages/actions/src/Concerns/HasDropdown.php)
takes a CSS length string directly — `support` has no `Width` enum, and the value flows
into a `--sy-dropdown-width` CSS variable. `dropdownAutoPlacement()` is not ported: it sets
`dropdownPlacement('auto-placement')` and `dropdownFlip(false)`, and the placement value is a
[hands-off mode the positioning controller does not have](../support/dropdown.md#auto-placement-when-no-explicit-placement-is-given),
so the setter would name a placement Floating UI would take literally. It lands with that row.

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
(`@SylamentActions/partials/Action/Button.html.twig`) is pushed in the
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

### `getClone()` rebuilds the flat map from the cloned members rather than cloning it separately

Filament's `cloneActions()` runs `array_map(fn ($action) => $action->getClone()->group($this), …)`
**twice** — once over `$this->actions` and once over `$this->flatActions`. Two
independent passes over overlapping objects means the clone's flat map holds a
*different* set of clones than its children list: `$clone->getFlatActions()['edit']`
and the `edit` inside `$clone->getActions()` are two objects, and a record or
table stamped on one is invisible to the other. That matters directly here,
because the flat map is what a host resolves a mounted action by name from
while the children list is what renders — so a cloned group would render one
object and mount another.

The port clones the children once and re-runs the existing
[`actions()`](../../../packages/actions/src/ActionGroup.php) setter over the
result: `actions()` already re-parents each member (`$action->group($this)`)
and already rebuilds the flat map with the recursion into nested groups that
`make()` performs, so the clone's map is built *from* its own children and the
two agree by construction. A nested group deep-clones through the same
`getClone()`, so its own map is rebuilt from its own clones one level down.
Nothing about Filament's public shape moves — `getClone()` and the protected
`cloneActions()` keep their names and signatures — and the two-pass version is
not reproduced because it has no behaviour worth preserving. Covered by
`ActionGroupTest`'s two clone cases (flat, and nested).

### Trigger defaults diverge from Filament: gray over primary, and the ellipsis icon is per-template

Two defaults baked into the new trigger templates depart from Filament's
`ActionGroup` on purpose:

- **Color.** Filament's
  [`CanGenerateButtonHtml`](../../../vendor/filament/support/src/View/Concerns/CanGenerateButtonHtml.php)
  and
  [`CanGenerateIconButtonHtml`](../../../vendor/filament/support/src/View/Concerns/CanGenerateIconButtonHtml.php)
  both coerce an unset color to `'primary'` (`$color ??= 'primary';`), so an
  unconfigured Filament trigger renders primary-colored. The port's
  [`IconButtonTrigger.html.twig`](../../../packages/actions/templates/partials/ActionGroup/IconButtonTrigger.html.twig)
  and
  [`ButtonTrigger.html.twig`](../../../packages/actions/templates/partials/ActionGroup/ButtonTrigger.html.twig)
  instead force `group.getColor() ?? 'gray'` — `'gray'` being the value
  support's [`IconButton`](../../../packages/support/templates/components/IconButton/index.html.twig)
  and [`Button`](../../../packages/support/templates/components/Button/index.html.twig)
  templates treat as "no color class" — since the colour registry that is no
  longer a template ternary but `HasDefaultGrayColor` on the policy, which makes
  [`ColorManager::getComponentClasses()`](../../../packages/support/src/Colors/ColorManager.php)
  return an empty list for the gray pair — overriding those components' own
  `color = 'primary'` prop default. This
  follows the same gray-when-unset convention `Action`'s own
  [`Action/Button.html.twig`](../../../packages/actions/templates/partials/Action/Button.html.twig)
  already renders (an undocumented divergence there too; noted here since
  Task 8 is the first time the convention crosses into a template that
  delegates to a color-defaulting component instead of building its own
  markup).
  [`Action/Grouped.html.twig`](../../../packages/actions/templates/partials/Action/Grouped.html.twig)
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
  un-overridden; only `IconButtonTrigger.html.twig` passes
  `ActionGroup::DEFAULT_ICON` as that `$default` argument —
  `ButtonTrigger.html.twig` calls `group.getIcon` bare, so a `button()`-mode
  group with no icon set renders no icon at all, unlike Filament's button
  trigger.

Reversible for either point independently: drop the `?? 'gray'` fallback, or
override `getIcon()` on `ActionGroup`, the moment product direction wants an
unconfigured trigger to match Filament's primary/always-ellipsis defaults.

### The dropdown plan is a PHP method the template loops, not an inline output buffer

Filament builds its dropdown's sections inline inside `toEmbeddedHtml()`: a
`foreach` accumulates `$singleActions`, flushes them into `$actionLists`
whenever a non-dropdown subgroup needs to splice its children in as their own
section, and then an `ob_start()` block prints one `fi-dropdown-list` per list.
Sylament has no `toEmbeddedHtml()` — rendering goes through Twig — so the plan
moves into a getter,
[`getDropdownActionLists(): array<array<Action|ActionGroup>>`](../../../packages/actions/src/ActionGroup.php),
and [`ActionGroup/index.html.twig`](../../../packages/actions/templates/partials/ActionGroup/index.html.twig)
loops it into `div.sy-dropdown-list` sections. The branching is Filament's,
line for line:

- A visible plain action, or a visible subgroup that **has** its own dropdown,
  joins the current run of single actions. A dropdown-bearing subgroup
  therefore renders as one item in the parent's panel whose markup is that
  subgroup's own complete dropdown — a real dropdown-in-dropdown, positioned
  [`right-start`](#nested-groups-default-to-right-start-where-filament-writes-right-top).
- A visible subgroup **without** a dropdown flushes the accumulated run into
  its own section and then contributes its visible children as a fresh section.
  Two adjacent sections are what draws the divider — support's
  `.sy-dropdown-list + .sy-dropdown-list` rule — so a flattened subgroup reads
  as a titled-less group of rows rather than a submenu.

One thing the port has to do that Filament does not: **re-stamp** the flattened
children's trigger templates as grouped. Filament gets this for free because
its stamping keys off `isButtonGroup()`, which is false for a plain
`dropdown(false)` subgroup, so its children were already `GROUPED_VIEW`. Ours
[keys off `hasDropdown()`](#the-child-template-split-keys-off-hasdropdown-where-filament-keys-off-isbuttongroup),
which is exactly the flag that made the subgroup flatten — so its children
arrive stamped as standalone buttons and would render as buttons inside a
dropdown panel. `getDropdownActionLists()` runs them back through
`stampTriggerTemplates($children, grouped: true)`. That call writes only the
*default* slot, so an explicit `template()` / `triggerTemplate()` on a child
still wins, and the visibility filter is applied before the re-stamp so a
hidden child is never touched.

An earlier version of this port had no flatten/embed branch at all and instead
threw `LogicException('Submenu rendering is not supported yet…')` from an
overridden `render()`. That override and its import are gone; nothing in
`ActionGroup::render()` throws any more, so the caveat that used to live here —
that a `renderComponent(group)` call site would receive the exception rewrapped
by `Twig\Template::yield()` as a `Twig\Error\RuntimeError` — no longer applies
to anything. It is recorded only so a reader who remembers the guard knows it
was removed deliberately rather than lost.

### Nested groups default to `right-start` where Filament writes `right-top`

Filament's `actions()` stamps `$action->defaultDropdownPlacement('right-top')`
on every nested `ActionGroup`, so a submenu opens to the side rather than
below. The port stamps `'right-start'` in the same place, and the difference is
a **correction, not a preference**: the value is passed straight through to
Floating UI, whose `Placement` type is `Side | AlignedPlacement`, where
`AlignedPlacement` is the template literal `${Side}-${Alignment}` and
`Alignment = 'start' | 'end'`
(`@floating-ui/utils`'s `floating-ui.utils.d.ts`, the bundled copy this repo
ships). `right-top` is not a member of that union — `top` is not an alignment —
so Floating UI does not resolve it as "right, top-aligned"; `right-start` is
the typed spelling of what Filament visibly means. Only the default is stamped,
so an explicit `dropdownPlacement()` on the child still wins. Revisit only if
Floating UI ever adds `-top`/`-bottom` aliases.

### The child template split keys off `hasDropdown()` where Filament keys off `isButtonGroup()`

Filament decides which template to stamp on a group's children with
`$this->isButtonGroup() ? BUTTON_VIEW : GROUPED_VIEW` — i.e. "am I a row of
buttons, or a dropdown panel?". The port uses
`$this->hasDropdown() ? GROUPED_TEMPLATE : BUTTON_TEMPLATE`, because
[`BUTTON_GROUP_VIEW` has no port](#the-button-group-trigger-is-unported-which-is-why-hasdropdown-stands-in-for-isbuttongroup)
and `isButtonGroup()` would therefore be a predicate over a template that does
not exist. The two agree on both shapes that matter — a dropdown group stamps
grouped, an inline row stamps buttons — and differ on exactly one input:
Filament's plain `dropdown(false)` group stamps its children `GROUPED_VIEW`
(dropdown-item styling, concatenated inline), where ours stamps
`BUTTON_TEMPLATE`. Ours is the better-looking default for a standalone inline
row, and it is the reason the flatten branch has to
[re-stamp](#the-dropdown-plan-is-a-php-method-the-template-loops-not-an-inline-output-buffer).
The same rule applies one level down through `defaultTriggerTemplate()` on a
nested group, mirroring Filament's `defaultTriggerView()`. Retire the stand-in
when the button-group trigger lands.

### `dropdown(false)` wraps its inline row in a bare `div.sy.sy-ac-group`, unlike Filament's unwrapped concatenation

Filament's non-dropdown branch of `toEmbeddedHtml()`
([`ActionGroup.php:484-489`](../../../vendor/filament/actions/src/ActionGroup.php))
returns `collect($this->getActions())->filter(...)->map(fn ($action) =>
$action->toHtml())->implode('')` — the visible children's markup concatenated
directly, with no wrapper element of any kind. The port's
[`ActionGroup/index.html.twig`](../../../packages/actions/templates/partials/ActionGroup/index.html.twig)
instead wraps the same loop in `<div class="sy sy-ac-group">…</div>` before
the `{% else %}` branch's children. Unlike Filament's bare concatenation, the
wrapper is a real layout hook: actions'
[`action-group.css`](../../../packages/actions/assets/src/styles/components/action-group.css)
gives `.sy-ac-group` `@apply inline-flex items-center gap-2`, so the inline
row's children lay out horizontally with spacing — a rule Filament's
unwrapped output has no element to carry. Reversible by dropping the wrapper
(and its CSS rule) and emitting the loop bare the moment parity with
Filament's unwrapped shape matters more than the flex layout.

### The group's serialized enum values flatten to scalars, and a `ScalableIcon` crosses unflattened

The same wire-format choice `Action` makes, minus the key the group does not
compose: [`ActionGroup::toArray()`](../../../packages/actions/src/ActionGroup.php)
writes `iconSize` and `size` as `->value` when the getter returned an
`IconSize` / `Size` instance where Filament writes the instance itself, and it
keeps a `BackedEnum` implementing
[`ScalableIcon`](../../../packages/support/src/Contracts/ScalableIcon.php)
**as the enum** where Filament resolves it to a string at serialization time
through `getIconForSize($this->getIconSize() ?? IconSize::Medium)`. Only a plain
`BackedEnum` flattens to its value. There is no `iconPosition` key to flatten
here — the group composes no `HasIconPosition`
([semantics](#the-groups-serialized-key-list-drops-iconposition-and-vendors-view)) —
so this is three keys on the group side against `Action`'s four.

The reasoning, the round-trip consequences and the render-size argument are
written up once, on the class that carries all four:
[`action.md`](action.md#the-serialized-enum-values-flatten-to-scalars-and-a-scalableicon-crosses-unflattened).
