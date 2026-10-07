---
description: '`ActionGroup`, which collapses actions behind one dropdown trigger or an inline row, its `BulkActionGroup` preset, and `BelongsToGroup` and `HasDropdown`.'
ports:
  - vendor/filament/actions/src/ActionGroup.php
  - vendor/filament/actions/src/BulkActionGroup.php
  - vendor/filament/actions/src/Concerns/BelongsToGroup.php
  - vendor/filament/actions/src/Concerns/HasDropdown.php
owns:
  - packages/actions/assets/src/styles/components/action-group.css
  - packages/actions/src/ActionGroup.php
  - packages/actions/src/BulkActionGroup.php
  - packages/actions/src/Concerns/BelongsToGroup.php
  - packages/actions/src/Concerns/HasDropdown.php
  - packages/actions/templates/partials/ActionGroup/
---
# ActionGroup (actions)

`Sylament\Actions\ActionGroup` extends `Sylament\Support\Components\ViewComponent`, the Twig-backed base `Action` extends too.
[`BulkActionGroup`](../../../packages/actions/src/BulkActionGroup.php) is a preset: it adds no member beyond a `setUp()` of defaults and one extra-attribute override, and it is not itself bulk, only its children are ([bulk-action.md](bulk-action.md) covers that side).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Construction, `make()` and `actions()` / `getActions()` / `getFlatActions()` | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) | done | — | — |
| [`BelongsToGroup`](../../../packages/actions/src/Concerns/BelongsToGroup.php): `group()`, `getGroup()`, `getRootGroup()` | [`Concerns/BelongsToGroup.php`](../../../vendor/filament/actions/src/Concerns/BelongsToGroup.php) | done | — | [diverges](#groups-parameter-defaults-to-null) |
| [`BelongsToTable`](../../../packages/actions/src/Concerns/BelongsToTable.php) (shared with `Action`) | [`Concerns/BelongsToTable.php`](../../../vendor/filament/actions/src/Concerns/BelongsToTable.php) | done | — | [belongs-to-table.md](belongs-to-table.md) |
| `getClone()` / `cloneActions()` | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) | done | — | [diverges](#getclone-rebuilds-the-flat-map-from-the-cloned-members-rather-than-cloning-it-separately) |
| `getLabel()` default | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) | done | — | [diverges](#getlabel-falls-back-to-a-plain-string-not-a-translated-label) |
| [`CanBeOutlined`](../../../packages/actions/src/Concerns/CanBeOutlined.php) (shared with `Action`; read by the button trigger only, as in Filament) | [`Concerns/CanBeOutlined.php`](../../../vendor/filament/actions/src/Concerns/CanBeOutlined.php) | done | — | [action.md](action.md) |
| [`HasGroupedIcon`](../../../packages/actions/src/Concerns/HasGroupedIcon.php) (shared with `Action`) | [`Concerns/HasGroupedIcon.php`](../../../vendor/filament/actions/src/Concerns/HasGroupedIcon.php) | done | — | [action.md](action.md) |
| [`HasLabel`](../../../packages/actions/src/Concerns/HasLabel.php) (shared with `Action`) | [`Concerns/HasLabel.php`](../../../vendor/filament/actions/src/Concerns/HasLabel.php) | done | — | [action.md](action.md) |
| [`HasSize`](../../../packages/actions/src/Concerns/HasSize.php) (shared with `Action`) | [`Concerns/HasSize.php`](../../../vendor/filament/actions/src/Concerns/HasSize.php) | done | — | [action.md](action.md) |
| [`HasColor`](../../../packages/support/src/Components/Concerns/HasColor.php) (support) | [`Concerns/HasColor.php`](../../../vendor/filament/support/src/Concerns/HasColor.php) | done | — | — |
| [`HasExtraAttributes`](../../../packages/support/src/Components/Concerns/HasExtraAttributes.php) (support; rendered onto the trigger root) | [`Concerns/HasExtraAttributes.php`](../../../vendor/filament/support/src/Concerns/HasExtraAttributes.php) | done | — | [diverges](#extra-attribute-bags-flatten-through-toscalarattributes-before-reaching-the-ux-component-attribute-bag) |
| `extraDropdownAttributes()`, `getExtraDropdownAttributes()`, `getExtraDropdownAttributeBag()`, `hasExtraDropdownAttributes()` (rendered onto the dropdown root) | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) | done | — | [diverges](#extra-attribute-bags-flatten-through-toscalarattributes-before-reaching-the-ux-component-attribute-bag) |
| [`HasIcon`](../../../packages/support/src/Components/Concerns/HasIcon.php) (support) | [`Concerns/HasIcon.php`](../../../vendor/filament/support/src/Concerns/HasIcon.php) | done | — | [diverges](#trigger-defaults-diverge-from-filament-gray-over-primary-and-the-ellipsis-icon-is-per-template) |
| [`HasIconSize`](../../../packages/support/src/Components/Concerns/HasIconSize.php) (support) | [`Concerns/HasIconSize.php`](../../../vendor/filament/support/src/Concerns/HasIconSize.php) | done | — | — |
| [`HasTooltip`](../../../packages/support/src/Components/Concerns/HasTooltip.php) (support) | [`Concerns/HasTooltip.php`](../../../vendor/filament/support/src/Concerns/HasTooltip.php) | done | — | — |
| [`HasIconPosition`](../../../packages/support/src/Components/Concerns/HasIconPosition.php) (support) on `ActionGroup` | [`Concerns/HasIconPosition.php`](../../../vendor/filament/support/src/Concerns/HasIconPosition.php) | todo | — | [detail](#the-groups-serialized-key-list-drops-iconposition-and-vendors-view) |
| [`HasBadge`](../../../packages/support/src/Components/Concerns/HasBadge.php) / [`HasBadgeTooltip`](../../../packages/support/src/Components/Concerns/HasBadgeTooltip.php) (support) on `ActionGroup` | [`Concerns/HasBadge.php`](../../../vendor/filament/support/src/Concerns/HasBadge.php) | todo | — | [detail](#the-grouped-trigger-drops-the-badge-and-filaments-default-ellipsis-icon) |
| [`HasDropdown`](../../../packages/actions/src/Concerns/HasDropdown.php) | [`Concerns/HasDropdown.php`](../../../vendor/filament/actions/src/Concerns/HasDropdown.php) | done | — | [diverges](#dropdownwidth-takes-a-css-length-not-a-width-enum-or-a-class) |
| Dropdown teleport (`dropdownTeleport()` / `defaultDropdownTeleport()` / `hasDropdownTeleport()`) | [`Concerns/HasDropdown.php`](../../../vendor/filament/actions/src/Concerns/HasDropdown.php) | done | — | [semantics](#dropdownteleport-is-a-positioning-strategy-not-a-dom-move-in-either-codebase) |
| `dropdownAutoPlacement()` | [`Concerns/HasDropdown.php`](../../../vendor/filament/actions/src/Concerns/HasDropdown.php) | todo | — | [detail](#dropdownautoplacement-waits-for-the-controllers-hands-off-placement-mode) |
| Visibility composition (`isHidden()` over the children) | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`isHidden()` / `resolveIsHidden()`) | done | — | [diverges](#no-ishiddeningroup-or-visibility-caching) |
| Context propagation to children (`liveComponent()`, `getRecord()` fallback, default-template push onto children) | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`getActions()`) | done | — | [diverges](#livecomponent-pushes-the-host-onto-every-child-on-top-of-the-lazy-getgroup-fallback) · [diverges](#actiongroupgetrecord-restates-the-traits-group-fallback-to-satisfy-phpstan) · [diverges](#the-child-template-split-keys-off-hasdropdown-where-filament-keys-off-isbuttongroup) |
| Closure dependency resolution (`resolveDefaultClosureDependencyForEvaluationByName()` / `…ByType()`) | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) | in progress | — | [detail](#the-groups-closure-resolver-lacks-the-table-mountedactions-and-operation-arms) · [diverges](action.md#getset-resolve-by-type-as-well-as-by-name) |
| Trigger-template-default propagation to nested groups (`defaultTriggerTemplate()`) | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`getActions()`, `defaultTriggerView()`) | done | — | [diverges](#the-child-template-split-keys-off-hasdropdown-where-filament-keys-off-isbuttongroup) |
| Grouped item template for `Action` children | [`components/dropdown/list/item.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) | done | — | [action.md](action.md) |
| [`GroupedTrigger.html.twig`](../../../packages/actions/templates/partials/ActionGroup/GroupedTrigger.html.twig) | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`toGroupedTriggerHtml()`) | in progress | — | [detail](#the-grouped-trigger-drops-the-badge-and-filaments-default-ellipsis-icon) |
| Trigger machinery (`triggerTemplate()`, `defaultTriggerTemplate()`, `getTriggerTemplate()`, `iconButton()` / `isIconButton()`, `button()` / `isButton()`) | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`triggerView()`, `getTriggerView()`, `toTriggerHtml()`) | done | — | [diverges](#trigger-defaults-diverge-from-filament-gray-over-primary-and-the-ellipsis-icon-is-per-template) |
| `grouped()` | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) | todo | — | — |
| Rendering, including [`getDropdownActionLists()`](../../../packages/actions/src/ActionGroup.php): flattened sections and embedded submenus | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`toEmbeddedHtml()`) | done | — | [diverges](#the-dropdown-plan-is-a-php-method-the-template-loops-not-an-inline-output-buffer) · [diverges](#dropdownfalse-wraps-its-inline-row-in-a-bare-divsysy-ac-group-unlike-filaments-unwrapped-concatenation) · [diverges](#nested-groups-default-to-right-start-where-filament-writes-right-top) |
| [`LinkTrigger.html.twig`](../../../packages/actions/templates/partials/ActionGroup/LinkTrigger.html.twig), with `link()` / `isLink()` | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`toLinkTriggerHtml()`, `link()` / `isLink()`) | in progress | — | [detail](#the-link-trigger-carries-every-ported-prop-and-drops-the-badge) |
| Badge and button-group triggers (`badge()` / `isBadge()`, `buttonGroup()` / `isButtonGroup()`) | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`toBadgeTriggerHtml()`, `BUTTON_GROUP_VIEW`) | todo | — | [detail](#the-button-group-trigger-is-unported-which-is-why-hasdropdown-stands-in-for-isbuttongroup) |
| `toArray()` / `fromArray()` | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) | done | — | [diverges](#the-groups-serialized-enum-values-flatten-to-scalars-and-a-scalableicon-crosses-unflattened) · [semantics](#fromarrays-hasdropdownflip-fallback-is-false-where-the-property-defaults-to-true) · [semantics](#fromarray-restores-only-the-four-built-in-trigger-templates) · [semantics](#the-restore-side-recurses-through-selffromarray-and-filters-child-keys-to-strings) |
| `toArray()` / `fromArray()` `view` key | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) | todo | — | [detail](#the-groups-serialized-key-list-drops-iconposition-and-vendors-view) |
| `hasNonBulkAction()` | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) | todo | — | [detail](#hasnonbulkaction-waits-for-the-table-toolbars-non-bulk-partition) |
| [`BulkActionGroup`](../../../packages/actions/src/BulkActionGroup.php) `setUp()` defaults | [`BulkActionGroup.php`](../../../vendor/filament/actions/src/BulkActionGroup.php) | done | — | [diverges](#bulkactiongroup-sets-its-icon-explicitly-and-keeps-the-label-a-literal) |
| [`BulkActionGroup::getExtraDropdownAttributes()`](../../../packages/actions/src/BulkActionGroup.php), the bulk-only marker | [`BulkActionGroup.php`](../../../vendor/filament/actions/src/BulkActionGroup.php) | done | — | [diverges](bulk-action.md#bulk-only-visibility-is-a-data-marker-the-table-controller-flips-not-an-alpine-expression) |
| `labeledFrom()` on the group, and `labeledFrom('sm')` in the bulk preset | [`Concerns/CanBeLabeledFrom.php`](../../../vendor/filament/actions/src/Concerns/CanBeLabeledFrom.php) | blocked | `CanBeLabeledFrom` | — |

## Details

### The grouped trigger drops the badge and Filament's default ellipsis icon

[`GroupedTrigger.html.twig`](../../../packages/actions/templates/partials/ActionGroup/GroupedTrigger.html.twig) is the shape a nested group takes as a row inside its parent's dropdown panel, the port of Filament's `toGroupedTriggerHtml()`.
It composes support's [`DropdownItem`](../../../packages/support/templates/components/DropdownItem/index.html.twig) with the icon (`group.getIcon(group.getGroupedIcon)`), the icon size, the colour, the tooltip as `title`, the label and the extra-attribute bag, classed `sy-ac`, the same composition the sibling [`Action/Grouped.html.twig`](../../../packages/actions/templates/partials/Action/Grouped.html.twig) uses for a plain action, so a submenu row and an action row line up.

What is missing is the badge.
Filament threads `badge` / `badgeColor` / `badgeTooltip` through `generateDropdownItemHtml()` from the group's `HasBadge` and `HasBadgeTooltip`; support ships both concerns ([`HasBadge.php`](../../../packages/support/src/Components/Concerns/HasBadge.php)), but `ActionGroup` composes neither, so there is no badge to pass.

The default icon differs on purpose: Filament's `ActionGroup::getIcon()` override falls back to `FilamentIcon::resolve(ActionsIconAlias::ACTION_GROUP) ?? Heroicon::EllipsisVertical`, so *every* Filament trigger renders an icon, while the port leaves support's `HasIcon::getIcon(?default)` un-overridden and each template supplies its own default, so a submenu row with neither `icon()` nor `groupedIcon()` set renders label-only.
That is the [per-template ellipsis choice](#trigger-defaults-diverge-from-filament-gray-over-primary-and-the-ellipsis-icon-is-per-template) the icon-button and button triggers make; reverse it by passing `ActionGroup::DEFAULT_ICON` as the fallback here, or by overriding `getIcon()` once for every template.

Filament has no submenu **affordance** to port on this element, no chevron, and the port drops its `fi-ac-grouped-group` marker class, so a submenu row is not distinguishable from a plain action row by CSS.
Add the marker class first if a chevron is ever wanted.

### The link trigger carries every ported prop and drops the badge

[`LinkTrigger.html.twig`](../../../packages/actions/templates/partials/ActionGroup/LinkTrigger.html.twig) is the port of Filament's `toLinkTriggerHtml()`, selected by `link()` (or a raw `triggerTemplate(ActionGroup::LINK_TEMPLATE)`) and reported by `isLink()`, the same setter and predicate pair `button()` / `isButton()` and `iconButton()` / `isIconButton()` carry.
The constant is therefore wired in three places: the pair, the [restore whitelist](#fromarray-restores-only-the-four-built-in-trigger-templates), and the template file itself.
It composes support's [`Link`](../../../packages/support/templates/components/Link/index.html.twig) with `tag="button"`, because the primitive defaults to `'a'` and a dropdown trigger is never an anchor, and threads through every prop Filament passes that the port has: `icon`, `iconSize`, `color`, `size`, `labelSrOnly` (from `isLabelHidden()`), the tooltip as `title`, the label as content, and the extra attribute bag.

Two of Filament's arguments have nothing to map to.
**`badge` / `badgeColor`**: `ActionGroup` composes no `HasBadge`, the same gap [the grouped trigger carries](#the-grouped-trigger-drops-the-badge-and-filaments-default-ellipsis-icon).
**`iconPosition`**: `ActionGroup` composes no `HasIconPosition`, so there is no getter to read, the group's `toArray()` [drops the key](#the-groups-serialized-key-list-drops-iconposition-and-vendors-view) for the same reason, and the `Link` primitive's own `iconPosition = 'before'` default stands.

Its prop list is deliberately wider than [`ButtonTrigger.html.twig`](../../../packages/actions/templates/partials/ActionGroup/ButtonTrigger.html.twig)'s, which passes only `icon` / `color` / `outlined` / `size` and ignores a group's `iconSize`, `hiddenLabel()` and `tooltip()` although Filament's `toButtonTriggerHtml()` threads all three.
That is the button trigger's gap, not a convention worth copying; widen it when a caller trips over it.

### The button-group trigger is unported, which is why `hasDropdown()` stands in for `isButtonGroup()`

Filament ships six trigger views, `BADGE_VIEW`, `BUTTON_VIEW`, `BUTTON_GROUP_VIEW`, `GROUPED_VIEW`, `ICON_BUTTON_VIEW` and `LINK_VIEW` ([`ActionGroup.php:70-80`](../../../vendor/filament/actions/src/ActionGroup.php)), with `buttonGroup()` / `isButtonGroup()` selecting a **non-dropdown row of buttons** (`div.fi-btn-group`), distinct from `button()`, which is a dropdown whose trigger looks like a button.
The port has `BUTTON_TEMPLATE`, `ICON_BUTTON_TEMPLATE`, `GROUPED_TEMPLATE` and [`LINK_TEMPLATE`](#the-link-trigger-carries-every-ported-prop-and-drops-the-badge); badge and **button-group** have none, which is why `isButtonGroup()` has nothing to be ported as and [`hasDropdown()` stands in for it](#the-child-template-split-keys-off-hasdropdown-where-filament-keys-off-isbuttongroup).
The port's inline-row shape is `dropdown(false)`, which Filament also supports and which is the closer analogue to `fi-btn-group` in practice.

The CSS half of the button-group trigger exists: [`ToggleButtons::grouped()`](../forms/toggle-buttons.md#the-grouped-labels-must-stay-direct-children-of-the-group) is a button group, and support's [`button.css`](../support/button.md) carries the `.sy-btn-group` rule this trigger needs too, so a `BUTTON_GROUP_TEMPLATE` adds no CSS of its own.
**Next step:** port the remaining `CanGenerate*Html` trigger shapes; adding `BUTTON_GROUP_TEMPLATE` is the point at which the `isButtonGroup()` predicate becomes portable and the stand-in can be retired.

### `dropdownAutoPlacement()` waits for the controller's hands-off placement mode

Filament's `dropdownAutoPlacement()` sets `dropdownPlacement('auto-placement')` and `dropdownFlip(false)`.
The placement value is a [hands-off mode the positioning controller does not have](../support/dropdown.md#auto-placement-when-no-explicit-placement-is-given), so the setter would name a placement Floating UI takes literally.
It lands with that mode.

### The group's closure resolver lacks the `table`, `mountedActions` and operation arms

[`ActionGroup::resolveDefaultClosureDependencyForEvaluationByName()`](../../../packages/actions/src/ActionGroup.php) resolves `record`, `recordClass` (Filament's `model`, which vendor's group carries at `ActionGroup.php:402` and which resolves the group's own [`getRecordClass()`](#actiongroupgetrecord-restates-the-traits-group-fallback-to-satisfy-phpstan)), `liveComponent`, `schema`, `schemaComponent` / `component`, `schemaGet` / `get`, `schemaComponentState` / `state` and `schemaState`.
Its `get` arm does not chain `skipComponentsChildContainersWhileSearching(false)`, as vendor's does not (`ActionGroup.php:408`), unlike `Action`'s.
Like Filament's group, it has no `set` arm.

Three of vendor's arms are missing: `mountedActions`, `schemaOperation` / `context` / `operation` (vendor's `ActionGroup.php:407`, the container's operation falling back to the component's), and `table` (`ActionGroup.php:411`).
None waits on a prerequisite: the group composes `BelongsToTable` and `BelongsToSchemaComponent`, and [`Action`'s resolver](action.md) already carries a `table` arm.

By type it resolves `Get::class`, which [diverges](action.md#getset-resolve-by-type-as-well-as-by-name): Filament resolves nothing by type here but the record, and the by-type record arm stays out for [the reason it does on `Action`](action.md#the-by-type-resolver-has-no-record-arm).

### The group's serialized key list drops `iconPosition` and vendor's `view`

[`toArray()`](../../../packages/actions/src/ActionGroup.php) writes every key Filament's writes except two.
**`iconPosition`**: Filament's `ActionGroup` composes `HasIconPosition` and this one does not, so there is no `getIconPosition()` to call; the sibling [`Action`](action.md#the-serialized-key-list-drops-alpineclickhandler-and-shouldposttourl) composes it and serializes it, and the key returns with the concern.
**`view`**: Filament's group serializes its own custom view as `hasView() ? getView() : null`, and its `fromArray()` restores it behind `isViewSafe()`.
The port's group inherits `template()` and `getTemplate()` from `ViewComponent`, but `toArray()` writes no key for its own template and `fromArray()` reads none, so a group restored from its array renders through the default `ActionGroup/index.html.twig`.
Adding it means a `template` key and a restore whitelist beside the [trigger-template one](#fromarray-restores-only-the-four-built-in-trigger-templates).
Vendor's `triggerView` is the key that survives, renamed `triggerTemplate` by the Blade to Twig translation in [`renames.md`](../renames.md).

### `hasNonBulkAction()` waits for the table toolbar's non-bulk partition

Filament's `ActionGroup::hasNonBulkAction()` iterates `getFlatActions()` calling `$action->isBulk()`.
Everything it reads exists: `BelongsToTable` ships ([belongs-to-table.md](belongs-to-table.md)) and so does [`Action::isBulk()`](bulk-action.md).
Its only reader is Filament's table toolbar, which decides whether any toolbar action is non-bulk (`tables/resources/views/index.blade.php:77-99`, skipping a `BulkActionGroup` and asking an `ActionGroup` through this method).
The port's toolbar in [`Table/index.html.twig`](../../../packages/tables/templates/partials/Table/index.html.twig) computes no such partition, and a declared member arrives with its consumer, so the method lands with that partition in [`tables`](../tables/README.md).

## Semantics

### `dropdownTeleport()` is a positioning strategy, not a DOM move, in either codebase

The name says teleport and the Blade side reinforces it: Filament's dropdown view spells the flag as an `x-float` modifier, `{{ $teleport ? '.teleport' : '' }}`, and Alpine's own `x-teleport` directive moves an element to another parent.
The `@awcodes/alpine-floating-ui` plugin behind `x-float` does no such thing.
Its modifier parser reads, in the compiled [`support/dist/index.js`](../../../vendor/filament/support/dist/index.js), `r.includes("teleport")&&(t.strategy="fixed")`: the whole effect is Floating UI's `strategy` option flipping from `absolute` to `fixed`.
The panel stays where it is in the DOM.

The port carries the name and that mechanism.
[`dropdownTeleport()`](../../../packages/actions/src/Concerns/HasDropdown.php) threads a `teleport` prop to `<twig:SylamentSupport:Dropdown>`, which passes it as a Stimulus value to the [dropdown controller](../../../packages/support/assets/src/controllers/dropdown_controller.ts); the controller asks Floating UI for `strategy: "fixed"` and writes `position: fixed` inline over the stylesheet's `absolute`, in the order [support/dropdown.md](../support/dropdown.md) records.
What that buys is the reason the flag exists: a fixed-position element's containing block is the viewport, so an ancestor's `overflow: hidden` does not clip it.
That is why the [always-`relative` root](../support/dropdown.md#the-root-is-a-positioned-shrink-to-fit-box-where-filaments-carries-no-rule-at-all) costs nothing here: the fixed panel ignores its offsetParent entirely.
The notification toast opts in, through [its re-view's default](../notifications/notification.md#the-re-view-defaults-a-group-to-teleport-where-filaments-toast-groups-clip); the scrolling table container is the [next candidate](../tables/table.md) and is not stamped.

The property pair is vendor-exact and the `??` between them is the whole of it: `$hasDropdownTeleport` and `$hasDefaultDropdownTeleport` are both **nullable** and default to `null`, so `hasDropdownTeleport()` falls through to the default only when nothing explicit was set.
A `?:` chain in its place would read an explicit `dropdownTeleport(false)` as absent and hand the decision back to whichever host stamped a default, the one way a caller can be overruled by a *default*.

The precondition a reader will not think to check is that `fixed` is only viewport-relative while **no** ancestor establishes a containing block for fixed descendants: any resting `transform`, `filter`, `perspective`, `contain: paint` or `will-change` naming one of them re-anchors it to that ancestor and the clipping comes back.
Filament inherits the same constraint, unstated.
The toast is clean: `.sy-no-notification` transitions `scale` and `translate`, from `@starting-style` values on entry and to `.sy-closing` values on exit, but at rest both are `none`, so nothing is established once the entry transition has finished.

### `fromArray()`'s `hasDropdownFlip` fallback is `false` where the property defaults to `true`

Reading [`$static->dropdownFlip((bool) ($data['hasDropdownFlip'] ?? false))`](../../../packages/actions/src/ActionGroup.php) next to `$static->dropdown((bool) ($data['hasDropdown'] ?? true))`, the obvious conclusion is that each fallback restates its property's default.
Only the second one does: `HasDropdown` declares **both** `$hasDropdown` and `$hasDropdownFlip` as `true`, so a payload that omits the flip key restores a group with flipping *off*.
This is copied from Filament, whose `fromArray()` and `HasDropdown` disagree the same way, so it is parity rather than a slip, and it is unreachable through the pair itself, because `toArray()` always writes the key.
Only a hand-built or truncated payload can hit it.
Corrected here, a group would round-trip differently from its Filament counterpart; the divergence is not worth that.

### `fromArray()` restores only the four built-in trigger templates

Same trust boundary as [`Action`'s template whitelist](action.md#fromarray-restores-only-the-five-built-in-templates), and the same shape: a restored `triggerTemplate` reaches `{% include %}` in [`ActionGroup/index.html.twig`](../../../packages/actions/templates/partials/ActionGroup/index.html.twig), so [`fromArray()`](../../../packages/actions/src/ActionGroup.php) admits it only when it is one of the group's own `ICON_BUTTON_TEMPLATE` / `BUTTON_TEMPLATE` / `GROUPED_TEMPLATE` / `LINK_TEMPLATE` constants.
Anything else is dropped and `getTriggerTemplate()` falls through its `triggerTemplate → defaultTriggerTemplate → ICON_BUTTON_TEMPLATE` cascade, so a tampered value lands on whatever the group's *context* asked for, which for a nested group is the `GROUPED_TEMPLATE` its parent stamped.
The whitelist is written out rather than derived, so adding a trigger kind means editing both.

### The restore side recurses through `self::fromArray()` and filters child keys to strings

Filament's `Action` and `ActionGroup` both implement Laravel's `Illuminate\Contracts\Support\Arrayable`, which is what lets `collect($this->getActions())->toArray()` serialize the children by walking a contract; there is no Symfony counterpart to adopt, so `toArray()` / `fromArray()` are plain public methods and the group maps its children by union type (`Action|self`) instead.

Two shapes on the restore side are load-bearing and read as noise.
The nested group recurses through `self::fromArray()` where Filament writes `ActionGroup::fromArray()`, not by choice: PHP-CS-Fixer's `self_accessor` rule rewrites the class name inside its own class, and the consequence is that the call **forwards** late static binding (a subclass restores nested groups as itself, where Filament's non-forwarding call resets them to the base).
And each child payload passes through `array_filter($action, is_string(...), ARRAY_FILTER_USE_KEY)` before the discriminator reads it: `$data['actions']` is `mixed`, so its entries narrow only to `array<int|string, mixed>`, and dropping the non-string keys is what proves the declared `array<string, mixed>` at level max.
It is lossless, because every read inside `fromArray()` is by string key, so removing it would trade a silent behaviour change for a static-analysis failure.

## Divergences

### `group()`'s parameter defaults to `null`

The port's `group(?ActionGroup $group = null): static` gives its parameter a default of `null` where Filament's is required, so `->group()` clears the association without spelling out the `null` argument.
Every caller that passes a value is unaffected.

### `getLabel()` falls back to a plain string, not a translated label

Filament's `getLabel()` is `$this->evaluate($this->label) ?? __('filament-actions::group.trigger.label')`, then runs a string result through `__()` again when `$shouldTranslateLabel` is set, the same translation layer `Action`'s own `HasLabel` drops (no translator is wired into the repo; see [action.md](action.md#label-translation-and-the-deprecated-disablelabel-alias-are-not-ported)).
The port's [`getLabel()`](../../../packages/actions/src/ActionGroup.php) is `$this->evaluate($this->label) ?? 'Actions'`, a plain literal.
The override also means `ActionGroup` never runs the `HasLabel` trait's own `getLabel()`, which derives a default from `getName()`: PHP resolves the class-defined method over the trait's, and a group has no name for that default to derive from anyway.
It reverses when a translation layer lands, as the linked `Action` divergence does.

### Extra attribute bags flatten through `toScalarAttributes()` before reaching the ux component attribute bag

Both group bags render in the Filament-parallel places.
The trigger bag (`HasExtraAttributes`, the shared support trait) lands on the trigger root: [`IconButtonTrigger.html.twig`](../../../packages/actions/templates/partials/ActionGroup/IconButtonTrigger.html.twig), [`ButtonTrigger.html.twig`](../../../packages/actions/templates/partials/ActionGroup/ButtonTrigger.html.twig), [`GroupedTrigger.html.twig`](../../../packages/actions/templates/partials/ActionGroup/GroupedTrigger.html.twig) and [`LinkTrigger.html.twig`](../../../packages/actions/templates/partials/ActionGroup/LinkTrigger.html.twig) spread it into the composed support component, mirroring how Filament's `to*TriggerHtml()` methods each merge `$this->getExtraAttributes()` onto the trigger element ([`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) lines 631/648/670/688/706).
The dropdown bag (`extraDropdownAttributes()` / `getExtraDropdownAttributes()` / `getExtraDropdownAttributeBag()` / `hasExtraDropdownAttributes()`, written onto [`ActionGroup`](../../../packages/actions/src/ActionGroup.php) in support's `HasExtraAttributes` shape from Filament's hand-written family at `ActionGroup.php:759-789`) lands on the dropdown root (Filament's `fi-dropdown`, the port's `sy sy-dropdown`) through [`ActionGroup/index.html.twig`](../../../packages/actions/templates/partials/ActionGroup/index.html.twig).

Each template spreads the bag as `getExtra…AttributeBag.toScalarAttributes` (`{{ ...attributes }}` inside the `<twig:>` tag) rather than the raw `getExtra…Attributes` array.
That flattening seam is load-bearing: support's `ComponentAttributeBag` normalizes `class` to a token **array**, and ux-twig-component's `ComponentAttributes::defaults()` merges a `class` default by string interpolation (`"{$default} {$value}"`, `vendor/symfony/ux-twig-component/src/ComponentAttributes.php:130`), which turns an array into the literal `Array` with a PHP warning.
`toScalarAttributes()` joins class tokens to a string and drops `null` and `false`, so a caller's `class` token-merges with the component root's own classes through `attributes.defaults()`.
Filament has no such seam because its Blade side renders its own attribute bag directly.

### `BulkActionGroup` sets its icon explicitly and keeps the label a literal

Filament's `BulkActionGroup::setUp()` reads its label from the `filament-tables::` translation namespace and its icon from `FilamentIcon::resolve(TablesIconAlias::ACTIONS_OPEN_BULK_ACTIONS) ?? Heroicon::EllipsisVertical`, both of which reach **into the tables package from actions**.
Neither crosses here.
The label is the literal `'Bulk actions'`, matching every other prebuilt action's [literal chrome](delete-action.md#the-strings-are-literals-not-translation-keys); and the icon is set to `ActionGroup::DEFAULT_ICON` directly, because the alias would need a registry entry whose only reader would be this line, and an alias lands with its reader.

Setting the icon at all is the load-bearing part, and it is **not** redundant with the constant's other use: only [`IconButtonTrigger.html.twig`](../../../packages/actions/templates/partials/ActionGroup/IconButtonTrigger.html.twig) applies `DEFAULT_ICON` as a fallback (`group.getIcon(constant(…))`), while [`ButtonTrigger.html.twig`](../../../packages/actions/templates/partials/ActionGroup/ButtonTrigger.html.twig) reads `group.getIcon` bare, and this group is a **button** group, so without the explicit call it would render icon-less.

The trigger is set through `defaultTriggerTemplate(static::BUTTON_TEMPLATE)`, not through the `button()` fluent method vendor calls.
The two are indistinguishable to a consumer, because both slots are written before any chained call, but they differ for a **nested** group: [`stampTriggerTemplates()`](../../../packages/actions/src/ActionGroup.php) pushes the parent's choice onto a child group through `defaultTriggerTemplate()`, so a bulk group inside another group's dropdown becomes a grouped trigger when its own preset used the default slot, and would stay a stray button had it used the user slot `button()` writes.
And `static::BUTTON_TEMPLATE` here is `ActionGroup`'s constant, the trigger partial that receives a `group` variable, never `Action::BUTTON_TEMPLATE`, which is an action's own partial and would fail on an undefined `component` inside the group's `{% include … only %}`.

### `dropdownWidth()` takes a CSS length, not a `Width` enum or a class

Filament's `HasDropdown` types `dropdownWidth(Width | string | Closure | null)`, and `toEmbeddedHtml()` turns the value into a **class** on the panel: `fi-width-{$width->value}` for a `Width` case, the raw string otherwise.
The port's [`dropdownWidth(string|Closure|null)`](../../../packages/actions/src/Concerns/HasDropdown.php) takes a CSS length string, which flows into the panel's `--sy-dropdown-width` variable through support's [`Dropdown`](../../../packages/support/templates/components/Dropdown/index.html.twig).
Support has a [`Width`](../../../packages/support/src/Enums/Width.php) enum, but the setter does not accept it, so a migrating `dropdownWidth(Width::Small)` call fails on the type and a class-string value renders as an invalid length.
It reverses by mapping a `Width` case to a length in the getter.

### No `isHiddenInGroup()` or visibility caching

Filament's `CanBeHidden` gives `Action` two visibility methods, `isHidden()` and a distinct `isHiddenInGroup()`, both backed by `$cachedIsHidden` / `$cachedIsHiddenInGroup` and a `hasVisibilityCache` / `$cachedVisibilityRecordKey` pair that invalidates the cache only when the acting record changes; `ActionGroup::resolveIsHidden()` iterates `getActions()` calling `$action->isHiddenInGroup()`, not `isHidden()`.
The port's [`ActionGroup::isHidden()`](../../../packages/actions/src/ActionGroup.php) has neither: no `isHiddenInGroup()` exists on `Action`, children are checked through their ordinary `isVisible()`, and nothing is cached, so every call re-evaluates the `isHidden` / `isVisible` closures.
It reverses by porting `isHiddenInGroup()` and the caching pair together, most naturally alongside the `tables` package's row rendering, which is what Filament's per-record cache invalidation serves.

### `liveComponent()` pushes the host onto every child on top of the lazy `getGroup()` fallback

Filament's group has no `livewire()` override: a child resolves its host lazily through [`BelongsToLivewire::getLivewire()`](../../../vendor/filament/actions/src/Concerns/BelongsToLivewire.php), whose chain ends in `getGroup()?->getLivewire()`.
The port carries the same lazy chain, ending in `getGroup()?->getLiveComponentOrNull()` ([`BelongsToLiveComponent`](../../../packages/actions/src/Concerns/BelongsToLiveComponent.php)), and [`ActionGroup::liveComponent()`](../../../packages/actions/src/ActionGroup.php) also overrides the setter to call `$action->liveComponent(...)` on every child immediately.
The eager push overwrites a host a child was given explicitly; a child added after the call still resolves the host through the lazy fallback.

### `ActionGroup::getRecord()` restates the trait's group fallback to satisfy PHPStan

[`InteractsWithRecord::getRecord()`](../../../packages/actions/src/Concerns/InteractsWithRecord.php) falls back to `getGroup()?->getRecord()` when its own `$record` evaluates to `null`, guarded by `method_exists($this, 'getGroup')` so the trait stays safe for a class that composes it standalone (the `InteractsWithRecordFixture` test fixture).
`ActionGroup` composes `BelongsToGroup` alongside `InteractsWithRecord`, so PHPStan (level max) proves that guard always evaluates to `true` in its context, `function.alreadyNarrowedType`.
Rather than suppress it, the group overrides `getRecord()` with what the guarded trait method reduces to: evaluate, return `ensureCorrectRecordType()` on an `is_object() || filled()` own record, otherwise `ensureCorrectRecordType($this->getGroup()?->getRecord())`.
Both guard calls are part of the equivalence: the trait runs the same two, matching Filament, which guards its own record *and* its parent-group hop (`InteractsWithRecord.php:117-123`).
`Action`'s override carries the [full chain](action.md#the-record-chain-reaches-the-schema-the-component-and-the-host), the same split Filament gets from gating that chain on `$this instanceof Action`: a group never reaches past its own group in either codebase.
Relaxing the guard's shape enough that PHPStan cannot prove it either way would add a type purely to dodge the checker, so it is not done.

`getRecordClass()` splits the same three ways, and there the group's body is a real third variant: the trait stops at the resolved record's class, `ActionGroup` adds the parent-group hop after it, and `Action` carries the whole walk.

### `getClone()` rebuilds the flat map from the cloned members rather than cloning it separately

Filament's `cloneActions()` runs `array_map(fn ($action) => $action->getClone()->group($this), …)` **twice**, once over `$this->actions` and once over `$this->flatActions`.
Two independent passes over overlapping objects means the clone's flat map holds a *different* set of clones than its children list: `$clone->getFlatActions()['edit']` and the `edit` inside `$clone->getActions()` are two objects, and a record or table stamped on one is invisible to the other.
That matters here, because the flat map is what a host resolves a mounted action by name from while the children list is what renders, so a cloned group would render one object and mount another.

The port clones the children once and re-runs the [`actions()`](../../../packages/actions/src/ActionGroup.php) setter over the result: `actions()` re-parents each member (`$action->group($this)`) and rebuilds the flat map with the recursion into nested groups that `make()` performs, so the clone's map is built *from* its own children and the two agree by construction.
A nested group deep-clones through the same `getClone()`, so its own map is rebuilt from its own clones one level down.
Filament's public shape does not move, because `getClone()` and the protected `cloneActions()` keep their names and signatures, and the two-pass version is not reproduced because it has no behaviour worth preserving.

### Trigger defaults diverge from Filament: gray over primary, and the ellipsis icon is per-template

Two defaults baked into the trigger templates depart from Filament's `ActionGroup` on purpose.

**Colour.**
Filament's [`CanGenerateButtonHtml`](../../../vendor/filament/support/src/View/Concerns/CanGenerateButtonHtml.php) and [`CanGenerateIconButtonHtml`](../../../vendor/filament/support/src/View/Concerns/CanGenerateIconButtonHtml.php) both coerce an unset colour to `'primary'` (`$color ??= 'primary';`), so an unconfigured Filament trigger renders primary-coloured.
The port's [`IconButtonTrigger.html.twig`](../../../packages/actions/templates/partials/ActionGroup/IconButtonTrigger.html.twig), [`ButtonTrigger.html.twig`](../../../packages/actions/templates/partials/ActionGroup/ButtonTrigger.html.twig) and [`LinkTrigger.html.twig`](../../../packages/actions/templates/partials/ActionGroup/LinkTrigger.html.twig) pass `group.getColor ?? 'gray'` instead, overriding those components' own `color = 'primary'` prop default.
`'gray'` renders no colour class: the colour policies of support's `IconButton`, `Button` and `Link` implement `HasDefaultGrayColor`, for which [`ColorManager::getComponentClasses()`](../../../packages/support/src/Colors/ColorManager.php) returns an empty list.
The convention is the group's own: `Action`'s [`Action/Button.html.twig`](../../../packages/actions/templates/partials/Action/Button.html.twig) passes `component.getColor ?? 'primary'`, as Filament does, while [`Action/Grouped.html.twig`](../../../packages/actions/templates/partials/Action/Grouped.html.twig) and the group's `GroupedTrigger.html.twig` pass the colour bare to `DropdownItem`.

**Icon.**
Filament's [`ActionGroup::getIcon()`](../../../vendor/filament/actions/src/ActionGroup.php) overrides `HasIcon::getIcon` (aliased `getBaseIcon`) unconditionally, `$this->getBaseIcon() ?? FilamentIcon::resolve(...) ?? Heroicon::EllipsisVertical`, so every one of its `to*TriggerHtml()` methods, the plain button trigger included, renders some icon.
The port leaves [`HasIcon::getIcon(?default)`](../../../packages/support/src/Components/Concerns/HasIcon.php) un-overridden; only `IconButtonTrigger.html.twig` passes `ActionGroup::DEFAULT_ICON` as that `$default` argument, so a `button()`-mode group with no icon set renders no icon at all, unlike Filament's button trigger.

Either point reverses independently: drop the `?? 'gray'` fallback, or override `getIcon()` on `ActionGroup`, when an unconfigured trigger should match Filament's primary and always-ellipsis defaults.

### The dropdown plan is a PHP method the template loops, not an inline output buffer

Filament builds its dropdown's sections inline inside `toEmbeddedHtml()`: a `foreach` accumulates `$singleActions`, flushes them into `$actionLists` whenever a non-dropdown subgroup needs to splice its children in as their own section, and then an `ob_start()` block prints one `fi-dropdown-list` per list.
Sylament has no `toEmbeddedHtml()`, because rendering goes through Twig, so the plan moves into a getter, [`getDropdownActionLists(): array<array<Action|ActionGroup>>`](../../../packages/actions/src/ActionGroup.php), and [`ActionGroup/index.html.twig`](../../../packages/actions/templates/partials/ActionGroup/index.html.twig) loops it into `div.sy-dropdown-list` sections.
The branching is Filament's, line for line:

- A visible plain action, or a visible subgroup that **has** its own dropdown, joins the current run of single actions.
  A dropdown-bearing subgroup therefore renders as one item in the parent's panel whose markup is that subgroup's own complete dropdown, a real dropdown-in-dropdown, positioned [`right-start`](#nested-groups-default-to-right-start-where-filament-writes-right-top).
- A visible subgroup **without** a dropdown flushes the accumulated run into its own section and then contributes its visible children as a fresh section.
  Two adjacent sections are what draws the divider, support's `.sy-dropdown-list + .sy-dropdown-list` rule, so a flattened subgroup reads as an untitled group of rows rather than a submenu.

One thing the port does that Filament does not: it **re-stamps** the flattened children's trigger templates as grouped.
Filament gets this for free because its stamping keys off `isButtonGroup()`, which is false for a plain `dropdown(false)` subgroup, so its children are already `GROUPED_VIEW`.
The port's [keys off `hasDropdown()`](#the-child-template-split-keys-off-hasdropdown-where-filament-keys-off-isbuttongroup), which is exactly the flag that made the subgroup flatten, so its children arrive stamped as standalone buttons and would render as buttons inside a dropdown panel.
`getDropdownActionLists()` runs them back through `stampTriggerTemplates($children, true)`.
That call writes only the *default* slot, so an explicit `template()` / `triggerTemplate()` on a child still wins, and the visibility filter is applied before the re-stamp so a hidden child is never touched.

### Nested groups default to `right-start` where Filament writes `right-top`

Filament's `actions()` stamps `$action->defaultDropdownPlacement('right-top')` on every nested `ActionGroup`, so a submenu opens to the side rather than below.
The port stamps `'right-start'` in the same place, and the difference is a **correction, not a preference**: the value is passed straight through to Floating UI, whose `Placement` type is `Side | AlignedPlacement`, where `AlignedPlacement` is the template literal `${Side}-${Alignment}` and `Alignment = 'start' | 'end'` (`@floating-ui/utils`'s `floating-ui.utils.d.ts`, the bundled copy this repo ships).
`right-top` is not a member of that union, because `top` is not an alignment, so Floating UI does not resolve it as "right, top-aligned"; `right-start` is the typed spelling of what Filament visibly means.
Only the default is stamped, so an explicit `dropdownPlacement()` on the child still wins.
Revisit only if Floating UI adds `-top` / `-bottom` aliases.

### The child template split keys off `hasDropdown()` where Filament keys off `isButtonGroup()`

Filament decides which template to stamp on a group's children with `$this->isButtonGroup() ? BUTTON_VIEW : GROUPED_VIEW`, that is, "am I a row of buttons, or a dropdown panel?".
The port uses `$this->hasDropdown() ? GROUPED_TEMPLATE : BUTTON_TEMPLATE`, because [`BUTTON_GROUP_VIEW` has no port](#the-button-group-trigger-is-unported-which-is-why-hasdropdown-stands-in-for-isbuttongroup) and `isButtonGroup()` would therefore be a predicate over a template that does not exist.
Like Filament's, `getActions()` always stamps a concrete default template, never `null`, and only into the default slot, so an explicit `template()` on a child wins.
The two agree on both shapes that matter, a dropdown group stamps grouped and an inline row stamps buttons, and differ on exactly one input: Filament's plain `dropdown(false)` group stamps its children `GROUPED_VIEW` (dropdown-item styling, concatenated inline), where the port stamps `BUTTON_TEMPLATE`.
That is the better-looking default for a standalone inline row, and it is the reason the flatten branch has to [re-stamp](#the-dropdown-plan-is-a-php-method-the-template-loops-not-an-inline-output-buffer).
The same rule applies one level down through `defaultTriggerTemplate()` on a nested group, mirroring Filament's `defaultTriggerView()`.
Retire the stand-in when the button-group trigger lands.

### `dropdown(false)` wraps its inline row in a bare `div.sy.sy-ac-group`, unlike Filament's unwrapped concatenation

Filament's non-dropdown branch of `toEmbeddedHtml()` ([`ActionGroup.php:484-489`](../../../vendor/filament/actions/src/ActionGroup.php)) returns `collect($this->getActions())->filter(...)->map(fn ($action) => $action->toHtml())->implode('')`, the visible children's markup concatenated directly, with no wrapper element.
The port's [`ActionGroup/index.html.twig`](../../../packages/actions/templates/partials/ActionGroup/index.html.twig) wraps the same loop in `<div class="sy sy-ac-group">…</div>` in its `{% else %}` branch.
The wrapper is a layout hook: actions' [`action-group.css`](../../../packages/actions/assets/src/styles/components/action-group.css) gives `.sy-ac-group` `@apply inline-flex items-center gap-2`, so the inline row's children lay out horizontally with spacing, a rule Filament's unwrapped output has no element to carry.
It reverses by dropping the wrapper and its CSS rule and emitting the loop bare, if parity with Filament's unwrapped shape ever matters more than the flex layout.

### The group's serialized enum values flatten to scalars, and a `ScalableIcon` crosses unflattened

The same wire-format choice `Action` makes, minus the key the group does not compose: [`ActionGroup::toArray()`](../../../packages/actions/src/ActionGroup.php) writes `iconSize` and `size` as `->value` when the getter returned an `IconSize` / `Size` instance where Filament writes the instance itself, and it keeps a `BackedEnum` implementing [`ScalableIcon`](../../../packages/support/src/Contracts/ScalableIcon.php) **as the enum** where Filament resolves it to a string at serialization time through `getIconForSize($this->getIconSize() ?? IconSize::Medium)`.
Only a plain `BackedEnum` icon flattens to its value.
There is no `iconPosition` key to flatten here, because the group composes no `HasIconPosition` ([detail](#the-groups-serialized-key-list-drops-iconposition-and-vendors-view)).

The reasoning, the round-trip consequences and the render-size argument are written up once, on the class that carries all four keys: [`action.md`](action.md#the-serialized-enum-values-flatten-to-scalars-and-a-scalableicon-crosses-unflattened).
