# Section (schemas)

This file tracks Section's own surface only — the inherited base-class surface
(`hidden()`, `key()`, `meta()`, state, …) is tracked in
[component.md](component.md).

Reference:

- [`vendor/filament/schemas/src/Components/Section.php`](../../../vendor/filament/schemas/src/Components/Section.php)
  — class *and* schemas-level markup: since v5 the schemas section view is
  inlined in `Section::toEmbeddedHtml()`; there is no schemas section Blade file
- [`vendor/filament/support/resources/views/components/section/index.blade.php`](../../../vendor/filament/support/resources/views/components/section/index.blade.php)
- [`vendor/filament/support/resources/css/components/section.css`](../../../vendor/filament/support/resources/css/components/section.css)
- [`vendor/filament/schemas/resources/css/components/section.css`](../../../vendor/filament/schemas/resources/css/components/section.css)

## Missing

- [ ] `hasContentEl` restructure: pass `hasContentEl: false` and put
  `sy-section-content` on the child schema root, mirroring Filament — makes
  `divided()` visually functional (today it only emits the class; the divider
  CSS targets children of `.sy-section-content`, which currently wraps the
  schema in one box) — blocked on
  [extra-attributes](../infrastructure.md#extra-attributes)
- [ ] `afterHeader()` / `footer()` keyed slot schemas — blocked on
  [schema-slot-apis](../infrastructure.md#schema-slot-apis)
- [ ] `beforeLabel()` / `afterLabel()` / `aboveContent()` / `belowContent()`
  slots — blocked on [schema-slot-apis](../infrastructure.md#schema-slot-apis)
- [ ] `headerActions()` / `footerActions()` + footer alignment — blocked on
  [actions-package](../infrastructure.md#actions-package)
- [ ] Collapse: `CanBeCollapsed` (`collapsible()`, `collapsed()`,
  `persistCollapsed()`), template props, `CanConcealComponents` contract,
  chevron disclosure button (v5 ARIA contract — see the stimulus anchor),
  `hasHeader` gains the `collapsible && !aside` term — blocked on
  [stimulus-controllers](../infrastructure.md#stimulus-controllers),
  [icon-system](../infrastructure.md#icon-system),
  [keys](../infrastructure.md#keys)
- [ ] Header icon rendering (`HasIcon`/`HasIconColor`/`HasIconSize`; `hasHeader`
  gains the icon term) — blocked on
  [icon-system](../infrastructure.md#icon-system)
- [ ] Auto `key()` from the heading slug in `setUp()` — blocked on
  [keys](../infrastructure.md#keys)
- [ ] Heading level cascade (`getHeadingsCount()` returning 1 when a heading is
  present, else 0) — blocked on
  [heading-cascade](../infrastructure.md#heading-cascade)
- [ ] Wrapper `id` + extra-attribute merging on `sy-sc-section` — blocked on
  [extra-attributes](../infrastructure.md#extra-attributes)
- [ ] Landmark ARIA (new in v5): name the `<section>` via `aria-labelledby`
  (heading id, else label id) and `aria-describedby` (description id), emitting
  `-heading` / `-description` / `-label` / `-content` id suffixes off the
  component id, each guarded so no reference dangles — blocked on
  [extra-attributes](../infrastructure.md#extra-attributes)
- [ ] Extra Stimulus attributes (Filament: `HasExtraAlpineAttributes`) — blocked
  on [stimulus-controllers](../infrastructure.md#stimulus-controllers)
- [ ] `translateLabel()` — blocked on
  [translation](../infrastructure.md#translation)
- [ ] `relationship()` (`EntanglesStateWithSingularRelationship`) — revisit when
  the forms package exists (Doctrine equivalent of Eloquent entangling).

## Divergences

- **Two-layer Twig rendering kept.** Filament v5 inlines the schemas section
  markup into `Section::toEmbeddedHtml()` (dropping the Blade component layer
  for render performance — an overhead Twig does not have); we keep v4's view
  layering — the schemas template wrapping the support Section component — and
  emit the same DOM.
- **`hiddenLabel()` is honored.** Filament's section markup ignores
  `isLabelHidden()`; we render the label with `sr-only` semantics — the meaning
  the flag has on Filament's own fieldset and field-wrapper. Labels stay in the
  markup for assistive technology.
- **`formBefore()` → `contentBefore()`** (`HasContentBefore` trait) — rename
  recorded in [../renames.md](../renames.md).
- **`Section::make([...])` array shorthand not ported.** `make()` takes a
  heading only; use `Section::make()->components([…])`. Filament's
  array-first-argument overload trades signature clarity for brevity.
- **`aside()` lives in a `CanBeAside` trait** (Filament defines it directly on
  `Section`) — keeps the one-concern-per-trait house style.
- **`.sy-section-content-ctn` adds `dark:text-white`** (no Filament
  counterpart in the section CSS). Filament inherits its base text color from
  the panels layout (`.fi-body` sets `text-gray-950 dark:text-white`); we have
  no layout layer yet, so the section CSS compensates locally — dark mode only,
  light mode inherits the host app. Revisit when a panels/layout layer owns
  base typography.
