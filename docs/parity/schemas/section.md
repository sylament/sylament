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

- [ ] `headerActions()` / `footerActions()` + footer alignment (including the
  `setUp()` seeding of the `after_header` / `footer` slots and the slot-action
  defaults in `configureChildSchema()`) — blocked on
  [actions-package](../infrastructure.md#actions-package)
- [ ] Collapse: `CanBeCollapsed` (`collapsible()`, `collapsed()`,
  `persistCollapsed()`), template props, `CanConcealComponents` contract,
  chevron disclosure button (v5 ARIA contract — see the stimulus anchor),
  `hasHeader` gains the `collapsible && !aside` term — blocked on
  [stimulus-controllers](../infrastructure.md#stimulus-controllers),
  [icon-system](../infrastructure.md#icon-system)
- [ ] Header icon rendering (`HasIcon`/`HasIconColor`/`HasIconSize`; `hasHeader`
  gains the icon term) — blocked on
  [icon-system](../infrastructure.md#icon-system)
- [ ] Heading level cascade (`getHeadingsCount()` returning 1 when a heading is
  present, else 0) — blocked on
  [heading-cascade](../infrastructure.md#heading-cascade)
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
  emit the same DOM. Consequence: the support component carries landmark-ARIA
  props Filament's support section view lacks (`contentId`, `descriptionId`,
  `headingId`, `labelledById`) because in v5 that markup exists only in the
  inlined schemas layer, whose role our support component plays. The render-time
  values (ARIA ids, the child schema carrying `sy-section-content` — injected by
  replacing the child schema's `extraAttributes()`, as Filament does) flow
  through `Section::getExtraTemplateData()`.
- **Extra attribute values are escaped at render.** The wrapper emits extras
  through `html_attr()`, which escapes names and values; Filament merges with
  `escape: false` and renders raw, so its `extraAttributes()` security caveat
  ("never pass unsanitized user input") is relaxed to values here.
- **An `extraAttributes()` id survives an unset component id.** Filament seeds
  its wrapper bag with `['id' => null]`, whose existing-wins merge clobbers a
  user-supplied extras id; we only emit the component id when set, so the extras
  id renders. A set component id — custom or key-derived (any section with a
  heading has one) — beats the extras id in both implementations.
- **Auto-key slugging via `AsciiSlugger`.** Filament slugs the heading with
  Laravel's `Str::slug(Str::transliterate(..., strict: true))` (bundled
  portable-ascii, environment-independent); we use `symfony/string`'s
  `AsciiSlugger` (intl-backed transliteration when available). Identical for
  ordinary and accented headings; symbol runs become `-` separators where
  Laravel strips them (`<em>Hi</em>` → `em-hi-em` vs `emhiem`).
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
