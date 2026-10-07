---
description: The boxed schemas layout component with a heading header, collapse, compact, divided, secondary and aside variants, and six auxiliary slots.
ports:
  - vendor/filament/schemas/src/Components/Section.php
  - vendor/filament/schemas/src/Components/Concerns/CanBeCollapsed.php
  - vendor/filament/schemas/src/Components/Concerns/CanBeCompact.php
  - vendor/filament/schemas/src/Components/Concerns/CanBeDivided.php
  - vendor/filament/schemas/src/Components/Concerns/CanBeSecondary.php
  - vendor/filament/schemas/src/Components/Concerns/HasDescription.php
  - vendor/filament/schemas/src/Components/Concerns/HasFooterActions.php
  - vendor/filament/schemas/src/Components/Concerns/HasFooterActionsAlignment.php
  - vendor/filament/schemas/src/Components/Concerns/HasHeaderActions.php
  - vendor/filament/schemas/src/Components/Concerns/HasHeading.php
owns:
  - packages/schemas/src/Components/Concerns/CanBeAside.php
  - packages/schemas/src/Components/Concerns/CanBeCollapsed.php
  - packages/schemas/src/Components/Concerns/CanBeCompact.php
  - packages/schemas/src/Components/Concerns/CanBeDivided.php
  - packages/schemas/src/Components/Concerns/CanBeSecondary.php
  - packages/schemas/src/Components/Concerns/HasContentBefore.php
  - packages/schemas/src/Components/Concerns/HasDescription.php
  - packages/schemas/src/Components/Concerns/HasFooterActions.php
  - packages/schemas/src/Components/Concerns/HasHeaderActions.php
  - packages/schemas/src/Components/Concerns/HasHeading.php
  - packages/schemas/src/Components/Section.php
  - packages/schemas/templates/partials/Section/
---
# Section (schemas)

[`Sylament\Schemas\Components\Section`](../../../packages/schemas/src/Components/Section.php) extends the schemas base [`Component`](component.md) and ports Filament's [`Section`](../../../vendor/filament/schemas/src/Components/Section.php): a box with a heading, description and label header, an icon, collapse, compact, divided, secondary and aside variants, and six auxiliary slots (after-header, footer, before/after label, above/below content).
The single-use concerns Filament composes onto `Section` are rows here, beside the extracted `CanBeAside` and `HasContentBefore`.
The shared ones have their own files: the label is [`HasLabel`](has-label.md), the icon is support's [`HasIcon`](../support/has-icon.md), [`HasIconColor`](../support/has-icon-color.md) and [`HasIconSize`](../support/has-icon-size.md), the contained flag is support's [`CanBeContained`](../support/can-be-contained.md), and binding the box's children to a to-one Doctrine association is [`EntanglesStateWithSingularRelationship`](entangles-state-with-singular-relationship.md), whose `CanEntangleWithSingularRelationships` contract `Section` implements.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make()`](../../../packages/schemas/src/Components/Section.php) | [`Section::make()`](../../../vendor/filament/schemas/src/Components/Section.php) | done | — | — |
| Array-heading constructor shorthand | [`Section::__construct()`](../../../vendor/filament/schemas/src/Components/Section.php) | skipped | — | [diverges](#the-array-heading-constructor-shorthand-is-dropped) |
| [Slot setters + key constants](../../../packages/schemas/src/Components/Section.php) | [`Section::afterHeader()` …](../../../vendor/filament/schemas/src/Components/Section.php) | done | — | [semantics](#slot-setters-replace-the-seeded-header-and-footer-actions) |
| [`makeChildSchema()` end-alignment](../../../packages/schemas/src/Components/Section.php) | [`Section::makeChildSchema()`](../../../vendor/filament/schemas/src/Components/Section.php) | done | — | — |
| [`configureChildSchema()` inline + embedded](../../../packages/schemas/src/Components/Section.php) | [`Section::configureChildSchema()`](../../../vendor/filament/schemas/src/Components/Section.php) | done | — | — |
| [`configureChildSchema()` action size/template branch](../../../packages/schemas/src/Components/Section.php) | [`Section::configureChildSchema()`](../../../vendor/filament/schemas/src/Components/Section.php) | done | — | [semantics](#slot-setters-replace-the-seeded-header-and-footer-actions) |
| [Key derivation from heading slug](../../../packages/schemas/src/Components/Section.php) | [`Section::setUp()`](../../../vendor/filament/schemas/src/Components/Section.php) | in progress | — | [detail](#the-heading-derived-key-omits-the-state-path-segment) |
| [Header actions (`HasHeaderActions` + `setUp` seed)](../../../packages/schemas/src/Components/Concerns/HasHeaderActions.php) | [`Concerns/HasHeaderActions.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasHeaderActions.php) | done | — | — |
| [Footer actions + alignment (`HasFooterActions`)](../../../packages/schemas/src/Components/Concerns/HasFooterActions.php) | [`Concerns/HasFooterActions.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasFooterActions.php) | done | — | — |
| Extra Alpine attributes (`HasExtraAlpineAttributes`) | [`support/Concerns/HasExtraAlpineAttributes.php`](../../../vendor/filament/support/src/Concerns/HasExtraAlpineAttributes.php) | skipped | — | [diverges](#extra-alpine-attribute-passthrough-is-not-ported) · [diverges](../support/component-attribute-bag.md#extra-alpine-attributes-are-not-ported) |
| [`canConcealComponents()`](../../../packages/schemas/src/Components/Section.php) | [`Contracts/CanConcealComponents`](../../../vendor/filament/schemas/src/Components/Contracts/CanConcealComponents.php), deprecated | done | — | [diverges](can-be-concealed.md#the-port-keeps-the-concealing-contract-that-filament-deprecates) |
| [`getHeadingsCount()`](../../../packages/schemas/src/Components/Section.php) | [`Section::getHeadingsCount()`](../../../vendor/filament/schemas/src/Components/Section.php) | done | — | — |
| [Rendering](../../../packages/schemas/templates/partials/Section/index.html.twig) | [`Section::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Section.php) | done | — | [diverges](#a-hidden-label-stays-in-the-dom-as-screen-reader-only-text) |
| [`CanBeCollapsed`](../../../packages/schemas/src/Components/Concerns/CanBeCollapsed.php) | [`Concerns/CanBeCollapsed.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeCollapsed.php) | done | — | — |
| [`CanBeCompact`](../../../packages/schemas/src/Components/Concerns/CanBeCompact.php) | [`Concerns/CanBeCompact.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeCompact.php) | done | — | — |
| [`CanBeDivided`](../../../packages/schemas/src/Components/Concerns/CanBeDivided.php) | [`Concerns/CanBeDivided.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeDivided.php) | done | — | — |
| [`CanBeSecondary`](../../../packages/schemas/src/Components/Concerns/CanBeSecondary.php) | [`Concerns/CanBeSecondary.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeSecondary.php) | done | — | — |
| [`HasHeading`](../../../packages/schemas/src/Components/Concerns/HasHeading.php) | [`Concerns/HasHeading.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasHeading.php) | done | — | — |
| [`HasDescription`](../../../packages/schemas/src/Components/Concerns/HasDescription.php) | [`Concerns/HasDescription.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasDescription.php) | done | — | — |
| [`CanBeAside`](../../../packages/schemas/src/Components/Concerns/CanBeAside.php) | [`Section::aside()` (inline)](../../../vendor/filament/schemas/src/Components/Section.php) | done | — | [diverges](#aside-is-extracted-into-a-canbeaside-concern) |
| [`HasContentBefore`](../../../packages/schemas/src/Components/Concerns/HasContentBefore.php) | [`Section::formBefore()` (inline)](../../../vendor/filament/schemas/src/Components/Section.php) | done | — | [diverges](#formbefore-becomes-contentbefore-extracted-into-a-hascontentbefore-concern) |

## Details

### The heading-derived key omits the state-path segment

Filament builds the auto key as `Str::slug(Str::transliterate($heading, strict: true)) . '::' . (filled($statePath) ? "{$statePath}::section" : 'section')`.
Sylament emits `AsciiSlugger()->slug($heading)->lower() . '::section'` with no state-path segment, so two same-heading sections at different state paths collide on one key.
The `AsciiSlugger` swap is the standard string-helper translation; the missing `{$statePath}::` segment is a gap, since components carry `getStatePath()` and [`Wizard`](wizard.md#the-key-keeps-filaments-state-path-segment-which-section-drops) and `Step` already build their keys with it.
**Next step:** read `getStatePath()` in the key closure as Filament does, and pin a keyed section under a `statePath()` in `SectionTest`.

## Semantics

### Slot setters replace the seeded header and footer actions

`setUp()` seeds the after-header slot with a closure returning `getHeaderActions()` and the footer slot with a closure building an alignment-matched `Schema` of `getFooterActions()`.
Slot setters replace, so a user `->afterHeader(...)` or `->footer(...)` call discards the seeded actions, as in Filament.
`configureChildSchema()` defaults actions in the four label/content slots to `Size::Small` and the link trigger template (`Action::LINK_TEMPLATE`, Filament's `LINK_VIEW`), and action groups there to the small size only; header and footer slot actions keep the button default, and an explicit `->button()` on a slot action still wins.

## Divergences

### The array-heading constructor shorthand is dropped

Filament's `__construct`/`make()` accept `array` and branch `is_array($heading) ? $this->components($heading) : $this->heading($heading)`, so `Section::make([...components])` doubles as a components shorthand.
Sylament types the argument `string|Markup|Closure|null` and always calls `heading()`, so callers pass children through `->components()` explicitly.
**Provisional:** re-adding the `array` arm (and `array` in the signature) restores parity if a consumer wants the shorthand.

### A hidden label stays in the DOM as screen-reader-only text

Filament's `Section::toEmbeddedHtml()` renders the label whenever it is filled and never reads `isLabelHidden()`, so `hiddenLabel()` has no effect on a Filament section.
The port honours it: the template classes the label `sy-sc-section-label-hidden`, which [`section.css`](styles.md#section-css-adds-the-sr-only-hidden-label-rule) makes `sr-only`, so the label leaves the layout but stays in the markup for assistive technology.

### `aside()` is extracted into a CanBeAside concern

Filament declares `$isAside` and `aside()`/`isAside()` inline on `Section.php`.
Sylament moves the same tri-state property (`bool|Closure|null`, default `null`, `isAside()` returning `false` when unset) and both methods into [`Concerns/CanBeAside.php`](../../../packages/schemas/src/Components/Concerns/CanBeAside.php), following the house preference for a focused `CanBe*` trait over widening the class, so the layout-only flag is reusable by other components.
The semantics are unchanged.

### `formBefore()` becomes `contentBefore()`, extracted into a HasContentBefore concern

Filament keeps `$isFormBefore` + `formBefore()`/`isFormBefore()` inline on `Section` (default `false`), driving the `fi-section-has-content-before` class.
Sylament extracts them into [`Concerns/HasContentBefore.php`](../../../packages/schemas/src/Components/Concerns/HasContentBefore.php) and renames the surface to `contentBefore()`/`hasContentBefore()` so the API matches the effect: the CSS marker it toggles reads `content-before`, and Filament's own support-layer section component names the prop `contentBefore`.
The rename is recorded in [renames.md](../renames.md).

### Extra Alpine attribute passthrough is not ported

Filament composes `HasExtraAlpineAttributes`, letting a caller attach arbitrary `x-*`/Alpine attributes onto the section's outer element.
Sylament omits it: there is no Alpine, and the collapse wiring the section needs is driven by the `sylament--support--section` Stimulus controller in the support Section component rather than inline `x-data`.
A userland JS-attribute passthrough would reverse this, targeting plain or Stimulus attributes rather than Alpine.
