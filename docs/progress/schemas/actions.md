# Actions (schemas)

`Sylament\Schemas\Components\Actions` — the layout component that renders a row
of action buttons anywhere a schema child can go, with an optional label heading
above the row. It extends the schemas base [`Component`](component.md) and ports
Filament's
[`Actions`](../../../vendor/filament/schemas/src/Components/Actions.php).

The required `make()` argument feeds `actions()`, whose whole body is
`$this->components($actions)` — the actions become the **default child schema's
components**, not `registerActions()` entries, so name resolution for them runs
through the Schema-side `getAction()` traversal rather than the component-level
`HasActions` cache (Filament-exact). `isHidden()` treats an all-invisible child
set as hidden, so a grid cell holding only hidden actions collapses instead of
leaving an empty box.

Shared concerns keep their own rows elsewhere: the label surface is the schemas
`HasLabel` concern (package [README](README.md)), the alignment surface is
support's `HasAlignment`. Filament additionally composes support's
`HasVerticalAlignment`, which Sylament has not ported — tracked as a blocked row
below.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/Components/Actions.php`](../../../vendor/filament/schemas/src/Components/Actions.php)
  — the component, its slots, sticky mode, and the `toEmbeddedHtml()` render
- [`schemas/resources/css/components/actions.css`](../../../vendor/filament/schemas/resources/css/components/actions.css)
  — the `.fi-sc-actions` column/label/sticky/vertical-alignment styling
- [`actions/resources/css/actions.css`](../../../vendor/filament/actions/resources/css/actions.css)
  — the `.fi-ac` row styling the Sylament row container ports

## Surface

Every point of this component. _Blocked by_ names what has to land first.
_Detail_ carries either or both: **detail** → the write-up in
[Details](#details), deleted once the row is **done**; **diverges** → the
permanent note in [Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make(actions)`](../../../packages/schemas/src/Components/Actions.php) | [`Actions::make()`](../../../vendor/filament/schemas/src/Components/Actions.php) | done | — | — |
| [`actions()` into the default child schema](../../../packages/schemas/src/Components/Actions.php) | [`Actions::actions()`](../../../vendor/filament/schemas/src/Components/Actions.php) | done | — | — |
| [`isHidden()` all-actions-hidden collapse](../../../packages/schemas/src/Components/Actions.php) | [`Actions::isHidden()`](../../../vendor/filament/schemas/src/Components/Actions.php) | done | — | — |
| [`fullWidth()` / `isFullWidth()`](../../../packages/schemas/src/Components/Actions.php) | [`Actions::fullWidth()`](../../../vendor/filament/schemas/src/Components/Actions.php) | done | — | — |
| [Rendering](../../../packages/schemas/templates/partials/Actions/index.html.twig) | [`Actions::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Actions.php) | done | — | [diverges](#the-actions-row-container-class-is-sy-sc-actions-ctn-not-a-fi-ac-translation) |
| `sticky()` / `isSticky()` | [`Actions::sticky()`](../../../vendor/filament/schemas/src/Components/Actions.php) | blocked | Stimulus sticky controller | [detail](#sticky-mode-is-not-ported) |
| Vertical alignment (`HasVerticalAlignment`) | [`support/Concerns/HasVerticalAlignment.php`](../../../vendor/filament/support/src/Concerns/HasVerticalAlignment.php) | blocked | support `HasVerticalAlignment` | [detail](#vertical-alignment-is-not-ported) |
| Label/content slots + slot-action downsizing | [`Actions::beforeLabel()` …](../../../vendor/filament/schemas/src/Components/Actions.php) | todo | — | [detail](#the-four-labelcontent-slots-are-not-ported-yet) |

## Details

Specifics for rows that are not finished — what is already in, what is left, and
the next step. Delete a subsection when its row flips to **done**; the table row
and any divergence it earned stay behind.

### Sticky mode is not ported

Filament's `sticky()` pins the actions row to the viewport bottom while the
component is scrolled out of view: Alpine `filamentActionsSchemaComponent()`
(`x-intersect:enter.half` / `x-intersect:leave` toggling, a ResizeObserver
pinning the row's width) plus the `.fi-sticky` CSS variant fixing `.fi-ac`.
Sylament ships neither the flag nor the styling — porting needs a new Stimulus
controller (IntersectionObserver + ResizeObserver) in the support/schemas
assets; the `.sy-sticky` CSS variant lands with it. Next step: port when that
controller exists.

### Vertical alignment is not ported

Filament composes support's `HasVerticalAlignment` and emits
`fi-vertical-align-{start|center|end}` on the outer column div
(justify-start/center/end). Sylament's support package has no
`HasVerticalAlignment` concern yet. Next step: port the concern into support,
compose it here, and emit `sy-vertical-align-*` plus the matching CSS.

### The four label/content slots are not ported yet

Filament's `Actions` ships the same four auxiliary slots as `Section`
(`before_label`, `after_label`, `above_content`, `below_content`): the setter
quartet, `makeChildSchema()` end-aligning the after-label slot, and
`configureChildSchema()` making the slot schemas inline + embedded and
downsizing slot actions to `Size::Small` + `LINK_VIEW`.
[`Section`](section.md) already ports this exact shape, so the port here is
mechanical Section parity: constants, setters, the two child-schema hooks, and the
template rendering the slots around the label and row. Deferred for now; next
step is mirroring Section's implementation when the slots are wanted.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold. Say
what would reverse the decision when it is provisional.

### The actions row container class is `sy-sc-actions-ctn`, not a `fi-ac` translation

Filament classes the row container `fi-ac` (styled from the **actions**
package's stylesheet) and the trigger buttons `fi-ac-<view>-action`. Sylament
already spent the systematic translation of `fi-ac`: bare `sy-ac` is the action
trigger marker (`sy sy-btn sy-ac`) and doubles as the actions package code
prefix in the [styling contract](../../internals/styling.md#why-do-templates-carry-only-semantic-classes), so the row container takes
the schemas-namespaced `sy-sc-actions-ctn` — emitted only by this component's
template. The row CSS itself ports `.fi-ac` verbatim under the new name (base
gap, wrap + centering when not full-width, the `fi-align-end`/`fi-align-right`
`flex-row-reverse` order flip, the full-width auto-fit grid) and lives in
schemas' [`actions.css`](../../../packages/schemas/assets/src/styles/components/actions.css)
next to its only emitter — see the
[styles divergence](styles.md#actions-css-folds-in-the-actions-package-row-stylesheet-and-drops-the-sticky-and-vertical-alignment-variants).
Nothing reverses this while `sy-ac` stays the trigger marker.
