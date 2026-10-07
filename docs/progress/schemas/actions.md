---
description: The schemas layout component that renders a row of action buttons anywhere a schema child can go, with an optional label above the row.
ports:
  - vendor/filament/schemas/src/Components/Actions.php
  - vendor/filament/support/src/Concerns/HasVerticalAlignment.php
owns:
  - packages/schemas/src/Components/Actions.php
  - packages/schemas/templates/partials/Actions/
---
# Actions (schemas)

`Sylament\Schemas\Components\Actions` extends the schemas base [`Component`](component.md) and ports Filament's [`Actions`](../../../vendor/filament/schemas/src/Components/Actions.php).
Its label surface is the schemas [`HasLabel`](has-label.md) concern, and its alignment surface is support's [`HasAlignment`](../support/has-alignment.md).
Its row stylesheet is schemas' `actions.css`, documented in [styles.md](styles.md#actions-css-folds-in-the-actions-package-row-stylesheet-and-drops-the-sticky-and-vertical-alignment-variants).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make(actions)`](../../../packages/schemas/src/Components/Actions.php) | [`Actions::make()`](../../../vendor/filament/schemas/src/Components/Actions.php) | done | — | — |
| [`actions()` into the default child schema](../../../packages/schemas/src/Components/Actions.php) | [`Actions::actions()`](../../../vendor/filament/schemas/src/Components/Actions.php) | done | — | [semantics](#the-actions-are-the-default-child-schemas-components-not-registered-actions) |
| [`isHidden()` all-actions-hidden collapse](../../../packages/schemas/src/Components/Actions.php) | [`Actions::isHidden()`](../../../vendor/filament/schemas/src/Components/Actions.php) | done | — | — |
| [`fullWidth()` / `isFullWidth()`](../../../packages/schemas/src/Components/Actions.php) | [`Actions::fullWidth()`](../../../vendor/filament/schemas/src/Components/Actions.php) | done | — | — |
| [Rendering](../../../packages/schemas/templates/partials/Actions/index.html.twig) | [`Actions::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Actions.php) | done | — | [diverges](#the-actions-row-container-class-is-sy-sc-actions-ctn-not-a-fi-ac-translation) |
| `sticky()` / `isSticky()` | [`Actions::sticky()`](../../../vendor/filament/schemas/src/Components/Actions.php) | blocked | Stimulus sticky controller | [detail](#sticky-mode-is-not-ported) |
| Vertical alignment (`HasVerticalAlignment`) | [`support/Concerns/HasVerticalAlignment.php`](../../../vendor/filament/support/src/Concerns/HasVerticalAlignment.php) | todo | — | [detail](#actions-does-not-compose-hasverticalalignment) |
| Label/content slots + slot-action downsizing | [`Actions::beforeLabel()` …](../../../vendor/filament/schemas/src/Components/Actions.php) | todo | — | [detail](#the-four-labelcontent-slots-are-not-ported-yet) |

## Details

### Sticky mode is not ported

Filament's `sticky()` pins the actions row to the viewport bottom while the component is scrolled out of view: Alpine `filamentActionsSchemaComponent()` (`x-intersect:enter.half` / `x-intersect:leave` toggling, a ResizeObserver pinning the row's width) plus the `.fi-sticky` CSS variant fixing `.fi-ac`.
Sylament ships neither the flag nor the styling.
Porting it needs a Stimulus controller (IntersectionObserver + ResizeObserver) in the support or schemas assets, and the `.sy-sticky` CSS variant lands with it.

### Actions does not compose `HasVerticalAlignment`

Filament composes support's `HasVerticalAlignment` and emits `fi-vertical-align-{start|center|end}` on the outer column div (justify-start/center/end).
Support's [`HasVerticalAlignment`](../support/has-vertical-alignment.md) is ported, but `Actions` composes only `HasAlignment` and `HasLabel`, and its template emits no vertical-alignment class.
Porting it composes the concern here, emits `getVerticalAlignmentClass()` on the outer div, and adds the matching `sy-vertical-align-*` rules to `actions.css`.

### The four label/content slots are not ported yet

Filament's `Actions` ships the same four auxiliary slots as `Section` (`before_label`, `after_label`, `above_content`, `below_content`): the setter quartet, `makeChildSchema()` end-aligning the after-label slot, and `configureChildSchema()` making the slot schemas inline + embedded and downsizing slot actions to `Size::Small` + `LINK_VIEW`.
[`Section`](section.md) ports this exact shape, spelling the link view `Action::LINK_TEMPLATE`, so the port here is Section parity: constants, setters, the two child-schema hooks, and the template rendering the slots around the label and row.

## Semantics

### The actions are the default child schema's components, not registered actions

The required `make()` argument feeds `actions()`, whose whole body is `$this->components($actions)`, as in Filament.
The actions become the default child schema's components rather than `registerActions()` entries, so name resolution for them runs through the schema-side `getAction()` traversal rather than the component-level `HasActions` cache.

## Divergences

### The actions row container class is `sy-sc-actions-ctn`, not a `fi-ac` translation

Filament classes the row container `fi-ac` (styled from the **actions** package's stylesheet) and the trigger buttons `fi-ac-<view>-action`.
Bare `sy-ac` is the action trigger marker (`sy sy-btn sy-ac`) and doubles as the actions package code prefix in the [styling contract](../../internals/styling.md#why-do-templates-carry-only-semantic-classes), so the row container takes the schemas-namespaced `sy-sc-actions-ctn`, emitted only by this component's template.
The row CSS that styles it is [styles.md](styles.md#actions-css-folds-in-the-actions-package-row-stylesheet-and-drops-the-sticky-and-vertical-alignment-variants)'s.
Nothing reverses this while `sy-ac` stays the trigger marker.
