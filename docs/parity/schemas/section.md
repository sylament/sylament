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
- [ ] Collapse-button loading state (Filament: `wire:loading.attr="disabled"`)
  — blocked on [live-component-state](../infrastructure.md#live-component-state)
- [ ] `reset-schema-component-state` wiring: the schemas template passing
  `liveComponentId`/`schemaKey` into the support section (the controller's
  inert reset listener activates once they exist), plus the server-side
  dispatcher (`Schema::dispatchClientSideStateReset()`) — blocked on
  [live-component-state](../infrastructure.md#live-component-state)
- [ ] Extra Stimulus attributes (Filament: `HasExtraAlpineAttributes`) — blocked
  on [stimulus-controllers](../infrastructure.md#stimulus-controllers)
- [ ] `translateLabel()`, and translating the collapse button's
  expand/collapse `aria-label` strings (hardcoded English defaults in the
  support section props today) — blocked on
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
  through `Section::getExtraTemplateData()`; the icon flows as the support
  component's raw `icon`/`iconColor`/`iconSize` props, same as Filament's
  support section view.
- **Extra attribute values are escaped at render.** The wrapper emits extras
  through `attrs()`, which escapes names and values; Filament merges with
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
- **Unknown `iconSize()` strings degrade to the helper's `Medium` default**
  (the support component's own default stays `Large`). Filament lets an
  unrecognized size string reach `generate_icon_html`'s `IconSize` parameter
  and type-error; our `generateIconHtml` Twig function `tryFrom()`s it and
  falls back.
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
- **Collapse is a Stimulus controller, not inline Alpine.** Filament inlines
  the whole behavior as Alpine expressions in the section markup; we ship
  `sylament--support--section` (support assets workspace,
  `section_controller.ts`). Live state is the `sy-collapsed` class on the
  `<section>` (DOM as source of truth); server config flows through Stimulus
  values; window events arrive via `@window` action descriptors. The DOM event
  contract is preserved verbatim (`collapse-section` / `expand-section` /
  `open-section` / `toggle-section` with `detail.id` matching and element-id
  fallback, element-level `expand`, `reset-schema-component-state`) — with one
  translated detail key: the reset event matches on `detail.liveComponentId`
  where Filament reads `detail.livewireId`, safe because that event's
  dispatcher is framework-internal (our future
  `Schema::dispatchClientSideStateReset()`), not user code. The localStorage
  key/format (`section-<id>-isCollapsed`, JSON booleans) stays
  Alpine-`$persist`-compatible, so persisted preferences survive a Filament
  migration.
- **The server renders the initial collapse state.** Filament renders neutral
  markup and `x-cloak`s until Alpine hydrates; we emit `sy-collapsed` plus the
  matching button ARIA server-side (no flash, correct with JS off). Only the
  persisted case — where the truth lives in localStorage — cloaks the content
  container with `sy-cloak`, and that rule is gated on
  `@media (scripting: enabled)`: with JS off the content stays reachable,
  where Filament's `x-cloak` without Alpine hides it permanently. Pre-2023
  browsers with JS on trade the cloak for a possible one-time state flash.
- **Chevron is the IconButton Twig component without `label`.** IconButton's
  `label` also emits `title`, which the controller would leave stale on
  toggle; the `aria-label` is passed as a plain attribute instead — matching
  Filament's raw button, which has no `title` either.
- **The reset listener markup is emitted conditionally.** Filament always
  renders the `reset-schema-component-state` listener (unless persisting); we
  emit the action descriptor only when `liveComponentId` and `schemaKey` are
  both present — behavior-identical, since the handler no-ops without them.
