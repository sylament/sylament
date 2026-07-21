# Section (schemas)

Reference:

- [`vendor/filament/schemas/src/Components/Section.php`](../../../vendor/filament/schemas/src/Components/Section.php)
- [`vendor/filament/schemas/resources/views/components/section.blade.php`](../../../vendor/filament/schemas/resources/views/components/section.blade.php)
- [`vendor/filament/support/resources/views/components/section/index.blade.php`](../../../vendor/filament/support/resources/views/components/section/index.blade.php)
- [`vendor/filament/support/resources/css/components/section.css`](../../../vendor/filament/support/resources/css/components/section.css)
- [`vendor/filament/schemas/resources/css/components/section.css`](../../../vendor/filament/schemas/resources/css/components/section.css)

## Missing

- [ ] Drop the dead `icon` / `iconColor` / `iconSize` props from the support
  template until [icon-system](../infrastructure.md#icon-system) lands (keep the
  TODO comment).
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
  chevron button, `hasHeader` gains the `collapsible` term — blocked on
  [stimulus-controllers](../infrastructure.md#stimulus-controllers),
  [icon-system](../infrastructure.md#icon-system),
  [keys](../infrastructure.md#keys)
- [ ] Header icon rendering (`HasIcon`/`HasIconColor`/`HasIconSize`; `hasHeader`
  gains the icon term) — blocked on
  [icon-system](../infrastructure.md#icon-system)
- [ ] Auto `key()` from the heading slug in `setUp()` — blocked on
  [keys](../infrastructure.md#keys)
- [ ] Heading level cascade (`getHeadingsCount(): 1`) — blocked on
  [heading-cascade](../infrastructure.md#heading-cascade)
- [ ] Wrapper `id` + extra-attribute merging on `sy-sc-section` — blocked on
  [extra-attributes](../infrastructure.md#extra-attributes)
- [ ] Extra Stimulus attributes (Filament: `HasExtraAlpineAttributes`) — blocked
  on [stimulus-controllers](../infrastructure.md#stimulus-controllers)
- [ ] `columnSpan()` participation in parent grids — blocked on
  [column-span](../infrastructure.md#column-span)
- [ ] `translateLabel()` — blocked on
  [translation](../infrastructure.md#translation)
- [ ] `relationship()` (`EntanglesStateWithSingularRelationship`) — revisit when
  the forms package exists (Doctrine equivalent of Eloquent entangling).

## Divergences

- **`hiddenLabel()` is honored.** Filament's section view ignores
  `isLabelHidden()`; we render the label with `sr-only` semantics — the meaning
  the flag has on Filament's own fieldset and field-wrapper. Labels stay in the
  markup for assistive technology.
- **`formBefore()` → `contentBefore()`** (`HasContentBefore` trait) — rename
  recorded in CLAUDE.md; adopts the name Filament's own support-layer prop uses.
- **`Section::make([...])` array shorthand not ported.** `make()` takes a
  heading only; use `Section::make()->components([…])`. Filament's
  array-first-argument overload trades signature clarity for brevity.
- **`aside()` lives in a `CanBeAside` trait** (Filament defines it directly on
  `Section`) — keeps the one-concern-per-trait house style.
