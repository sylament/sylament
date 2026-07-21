# Section component — remaining work

Tracks parity of `Sylament\Schemas\Components\Section` against Filament's Section.

Reference implementation:
- `vendor/filament/schemas/src/Components/Section.php`
- `vendor/filament/schemas/resources/views/components/section.blade.php`
- `vendor/filament/support/resources/views/components/section/index.blade.php`

Done so far: heading, description, default child schema, header/content/footer markup
(`packages/support/templates/components/Section/index.html.twig`), and nearly all of
Filament's section CSS (`packages/support/assets/src/styles/components/section.css`)
including states the PHP/template side can't produce yet.

## Bugs / divergences in what's already there

- [x] **XSS: unescaped output.** Fixed: `markup()` Twig helper (support
  `MarkupExtension`) wraps block content in `Twig\Markup` at the props defaults;
  all `|raw` removed, so prop strings escape and `Markup` renders raw.
  `renderComponent()`/`renderSchema()` now return `?Markup` (`is_safe` dropped) so
  component HTML survives prop/variable indirection. PHP API widened to
  `string|Markup|Closure|null` (`HasHeading`, `HasDescription`, `Section::make()`)
  per the `Htmlable` → `Twig\Markup` convention now recorded in CLAUDE.md.
- [x] **`headingTag` prop declared but ignored** — fixed: template renders
  `<{{ headingTag }}>`, default `'h2'` (Filament's default). Long-term: heading
  level cascade (below). Also fixed alongside: trim-aware presence checks
  (empty/whitespace heading, description, slots no longer render empty
  containers).
- [ ] **`hasContentEl` wiring inverts Filament's structure.** Filament passes
  `:has-content-el="false"` and puts `fi-section-content` directly on the child
  schema's root; we render the schema inside an extra `.sy-section-content` wrapper.
  Breaks the already-ported `divided` CSS (`.sy-section-content > *` targets each
  schema child in Filament; here its only child is the schema's `.sy-sc` div, so
  dividers/per-row padding never apply). Blocked on schema roots accepting extra
  classes (`extraAttributes`).

## Missing features

### Cheap — template + CSS ready, PHP API missing

- [ ] Boolean variant concerns + pass props through `Section.html.twig`:
  - [ ] `compact()` — `CanBeCompact` (schemas)
  - [ ] `divided()` — `CanBeDivided` (schemas)
  - [ ] `secondary()` — `CanBeSecondary` (schemas)
  - [ ] `contained()` — `CanBeContained` (support-level concern in Filament)
  - [ ] `aside()` / `isAside()` (method on Section itself in Filament)
- [ ] Constructor array shorthand: `Section::make([Component, …])` routes to
  `components()` when first arg is an array.
- [ ] `formBefore()` / content-before: template half done (`contentBefore` prop
  emits `sy-section-has-content-before`); the `formBefore()` PHP method is still
  missing.

### Heading semantics

- [ ] Heading level cascade — Filament `HasHeadings`: `getHeadingLevel()` /
  `getHeadingTag()` (h2, h3, … capped to `p`), `getChildSchemaRootHeadingLevel()`,
  Schema tracks a root heading level; Section overrides `getHeadingsCount(): 1`.

### Slot schemas

`HasChildComponents` already supports keys and the `makeChildSchema` /
`configureChildSchema` hooks — the pattern fits; the pieces on top are missing:

- [ ] `afterHeader()` (`AFTER_HEADER_SCHEMA_KEY`, aligned end) and `footer()`
  (`FOOTER_SCHEMA_KEY`) slot methods + template wiring
- [ ] `label()` (`HasLabel`) rendered above the section box, with `beforeLabel()` /
  `afterLabel()` slots
- [ ] `aboveContent()` / `belowContent()` slots
- [ ] Schema-side support these need: `inline()`, `embeddedInParentComponent()`,
  alignment factories (`Schema::start/center/end/between`), `modifyActionsUsing()` /
  `modifyActionGroupsUsing()`

### Collapse — needs new infrastructure

CSS already ported (`sy-collapsible`, `sy-collapsed`, `sy-section-collapse-btn`).

- [ ] `CanBeCollapsed` concern: `collapsible()`, `collapsed()`, `persistCollapsed()`
- [ ] Template props: `collapsible`, `collapsed`, `collapseId`, `persistCollapsed`
- [ ] Stimulus controller (Filament uses Alpine): toggle state, header
  click-to-toggle, window events (`collapse-section`, `expand-section`,
  `toggle-section`, `open-section`), `aria-expanded`, persistence, LiveComponent
  state-reset equivalent
- [ ] Collapse chevron button — depends on an icon-button support component
- [ ] `CanConcealComponents` contract + `canConcealComponents()`
- [ ] Auto `key()` from heading slug in `setUp()` — blocked on component model
  having a key concept (persistence identity)

### Icon — needs new infrastructure

- [ ] `HasIcon` / `HasIconColor` / `HasIconSize` concerns (support-level in Filament)
- [ ] Render in header (template has `{# TODO: add section icon #}`; `icon`,
  `iconColor`, `iconSize` props declared but unused) — blocked on a support icon
  system (Filament: `generate_icon_html` + icon aliases). CSS already in place.

### Actions — blocked on `actions` package

- [ ] `HasHeaderActions` (`headerActions()` → afterHeader slot)
- [ ] `HasFooterActions` (`footerActions()`, `footerActionsAlignment()` → footer slot)

### Deferred (tracked, not planned yet)

- `EntanglesStateWithSingularRelationship` / `relationship()` — forms/Doctrine
  territory
- `HasExtraAlpineAttributes` — Stimulus equivalent TBD
- Outer wrapper `id` + extra-attribute merging on `sy-sc-section` — blocked on
  `HasId` / `HasExtraAttributes` in the component model
- `columnSpan()` — Section can't size itself inside a parent grid yet
  (schema-wide gap, not Section-specific)
