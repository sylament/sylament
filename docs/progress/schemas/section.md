# Section (schemas)

`Sylament\Schemas\Components\Section` — the boxed layout component with a
heading/description/label header, an icon, collapse/compact/divided/secondary/
aside variants, and six auxiliary slots (after-header, footer, before/after
label, above/below content). It extends the schemas base
[`Component`](component.md) and ports Filament's
[`Section`](../../../vendor/filament/schemas/src/Components/Section.php).

The single-use concerns Filament composes onto `Section` — `CanBeCollapsed`,
`CanBeCompact`, `CanBeDivided`, `CanBeSecondary`, `HasHeading`, `HasDescription`,
`HasHeaderActions`, `HasFooterActions` (with its `HasFooterActionsAlignment`),
plus the extracted `CanBeAside` and `HasContentBefore` — are tracked as rows here
rather than in files of their own. Shared concerns keep their own rows in the
[package README](README.md): the label surface is the `HasLabel` concern (also
used by forms' `Field`), the icon surface is the support `HasIcon`/`HasIconColor`/
`HasIconSize` trio, and the contained flag is support's `CanBeContained`.

`setUp()` seeds the after-header slot with a closure returning
`getHeaderActions()` and the footer slot with a closure building an
alignment-matched `Schema` of `getFooterActions()`. Slot setters replace, so a
user `->afterHeader(...)`/`->footer(...)` call discards the seeded actions —
Filament-exact, test-pinned. `configureChildSchema()` defaults actions in the
four label/content slots to `Size::Small` and the link trigger template
(`Action::LINK_TEMPLATE`, Filament's `LINK_VIEW`) and action groups to the
small size only; header/footer slot actions keep the button default —
Filament-exact, and an explicit `->button()` on a slot action still wins.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/Components/Section.php`](../../../vendor/filament/schemas/src/Components/Section.php)
  — the component, its slots, key derivation, and the `toEmbeddedHtml()` render
- [`schemas/src/Components/Concerns/`](../../../vendor/filament/schemas/src/Components/Concerns)
  — the collapse/compact/divided/secondary/heading/description concerns folded below
- [`support/src/Concerns/HasExtraAlpineAttributes.php`](../../../vendor/filament/support/src/Concerns/HasExtraAlpineAttributes.php)
  — the Alpine passthrough Filament composes but Sylament omits

## Surface

Every point of this component and its folded concerns. _Blocked by_ names what
has to land first. _Detail_ carries either or both: **detail** → the write-up in
[Details](#details), deleted once the row is **done**; **diverges** → the
permanent note in [Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make()` + final constructor](../../../packages/schemas/src/Components/Section.php) | [`Section::make()`](../../../vendor/filament/schemas/src/Components/Section.php) | done | — | — |
| [Array-heading constructor shorthand](../../../packages/schemas/src/Components/Section.php) | [`Section::__construct()`](../../../vendor/filament/schemas/src/Components/Section.php) | skipped | — | [diverges](#the-array-heading-constructor-shorthand-is-dropped) |
| [Slot setters + key constants](../../../packages/schemas/src/Components/Section.php) | [`Section::afterHeader()` …](../../../vendor/filament/schemas/src/Components/Section.php) | done | — | — |
| [`makeChildSchema()` end-alignment](../../../packages/schemas/src/Components/Section.php) | [`Section::makeChildSchema()`](../../../vendor/filament/schemas/src/Components/Section.php) | done | — | — |
| [`configureChildSchema()` inline + embedded](../../../packages/schemas/src/Components/Section.php) | [`Section::configureChildSchema()`](../../../vendor/filament/schemas/src/Components/Section.php) | done | — | — |
| [`configureChildSchema()` action size/template branch](../../../packages/schemas/src/Components/Section.php) | [`Section::configureChildSchema()`](../../../vendor/filament/schemas/src/Components/Section.php) | done | — | — |
| [Key derivation from heading slug](../../../packages/schemas/src/Components/Section.php) | [`Section::setUp()`](../../../vendor/filament/schemas/src/Components/Section.php) | done | — | [diverges](#the-heading-derived-key-omits-the-state-path-segment) |
| [Header actions (`HasHeaderActions` + `setUp` seed)](../../../packages/schemas/src/Components/Concerns/HasHeaderActions.php) | [`Concerns/HasHeaderActions.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasHeaderActions.php) | done | — | — |
| [Footer actions + alignment (`HasFooterActions`)](../../../packages/schemas/src/Components/Concerns/HasFooterActions.php) | [`Concerns/HasFooterActions.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasFooterActions.php) | done | — | — |
| Singular-relationship entanglement (`EntanglesStateWithSingularRelationship`) | [`Concerns/EntanglesStateWithSingularRelationship.php`](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | blocked | schemas state / Doctrine layer | [detail](#singular-relationship-state-entanglement-is-not-ported) |
| Extra Alpine attributes (`HasExtraAlpineAttributes`) | [`support/Concerns/HasExtraAlpineAttributes.php`](../../../vendor/filament/support/src/Concerns/HasExtraAlpineAttributes.php) | skipped | — | [diverges](#extra-alpine-attribute-passthrough-is-not-ported) |
| [`canConcealComponents()`](../../../packages/schemas/src/Components/Section.php) | [`Section::canConcealComponents()`](../../../vendor/filament/schemas/src/Components/Section.php) | done | — | — |
| [`getHeadingsCount()`](../../../packages/schemas/src/Components/Section.php) | [`Section::getHeadingsCount()`](../../../vendor/filament/schemas/src/Components/Section.php) | done | — | — |
| [Rendering](../../../packages/schemas/templates/components/Section.html.twig) | [`Section::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Section.php) | done | — | — |
| [`CanBeCollapsed`](../../../packages/schemas/src/Components/Concerns/CanBeCollapsed.php) | [`Concerns/CanBeCollapsed.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeCollapsed.php) | done | — | — |
| [`CanBeCompact`](../../../packages/schemas/src/Components/Concerns/CanBeCompact.php) | [`Concerns/CanBeCompact.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeCompact.php) | done | — | — |
| [`CanBeDivided`](../../../packages/schemas/src/Components/Concerns/CanBeDivided.php) | [`Concerns/CanBeDivided.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeDivided.php) | done | — | — |
| [`CanBeSecondary`](../../../packages/schemas/src/Components/Concerns/CanBeSecondary.php) | [`Concerns/CanBeSecondary.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeSecondary.php) | done | — | — |
| [`HasHeading`](../../../packages/schemas/src/Components/Concerns/HasHeading.php) | [`Concerns/HasHeading.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasHeading.php) | done | — | — |
| [`HasDescription`](../../../packages/schemas/src/Components/Concerns/HasDescription.php) | [`Concerns/HasDescription.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasDescription.php) | done | — | — |
| [`CanBeAside`](../../../packages/schemas/src/Components/Concerns/CanBeAside.php) | [`Section::aside()` (inline)](../../../vendor/filament/schemas/src/Components/Section.php) | done | — | [diverges](#aside-is-extracted-into-a-canbeaside-concern) |
| [`HasContentBefore`](../../../packages/schemas/src/Components/Concerns/HasContentBefore.php) | [`Section::formBefore()` (inline)](../../../vendor/filament/schemas/src/Components/Section.php) | done | — | [diverges](#formbefore-becomes-contentbefore-extracted-into-a-hascontentbefore-concern) |

## Details

Specifics for rows that are not finished — what is already in, what is left, and
the next step. Delete a subsection when its row flips to **done**; the table row
and any divergence it earned stay behind.

### Singular-relationship state entanglement is not ported

Filament's `Section` uses `EntanglesStateWithSingularRelationship` and implements
`CanEntangleWithSingularRelationships`, letting the box bind its children to a
singular Doctrine/Eloquent relationship. Sylament implements only
`CanConcealComponents`. **Blocked** on the schemas state loop and the Doctrine
relationship layer, neither of which exists yet; it returns once `HasState` and
relationship entanglement land.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold. Say
what would reverse the decision when it is provisional.

### The array-heading constructor shorthand is dropped

Filament's `__construct`/`make()` accept `array` and branch
`is_array($heading) ? $this->components($heading) : $this->heading($heading)`, so
`Section::make([...components])` doubles as a components shorthand. Sylament types
the argument `string|Markup|Closure|null` and always calls `heading()`, so the
array form is unavailable — callers pass children through `->components()`
explicitly. **Provisional:** re-adding the `array` arm (and `array` in the
signature) restores parity if a consumer wants the shorthand.

### The heading-derived key omits the state-path segment

Filament builds the auto key as
`Str::slug(Str::transliterate($heading, strict: true)) . '::' . (filled($statePath) ? "{$statePath}::section" : 'section')`.
Sylament emits `AsciiSlugger()->slug($heading)->lower() . '::section'`, dropping
the state-path segment because the state layer is not ported — the transliteration
swap to Symfony's `AsciiSlugger` is the standard string-helper translation, but
the missing `{$statePath}::` prefix is a genuine behavioral gap: two same-heading
sections at different state paths would collide on key today. Reverses when
`HasState` lands and `getStatePath()` becomes meaningful.

### `aside()` is extracted into a CanBeAside concern

Filament declares `$isAside` and `aside()`/`isAside()` inline on `Section.php`.
Sylament moves the identical tri-state property (`bool|Closure|null`, default
`null`, `isAside()` returning `false` when unset) and both methods into
[`Concerns/CanBeAside.php`](../../../packages/schemas/src/Components/Concerns/CanBeAside.php),
following the house preference for a focused `CanBe*` trait over widening the
class so the layout-only flag is reusable by future components. Pure relocation —
the semantics are unchanged.

### `formBefore()` becomes `contentBefore()`, extracted into a HasContentBefore concern

Filament keeps `$isFormBefore` + `formBefore()`/`isFormBefore()` inline on
`Section` (default `false`), driving the `fi-section-has-content-before` class.
Sylament extracts them into
[`Concerns/HasContentBefore.php`](../../../packages/schemas/src/Components/Concerns/HasContentBefore.php)
and renames the surface to `contentBefore()`/`hasContentBefore()` so the API
matches the effect — Filament's `form` naming is a stale artifact, since the CSS
marker it toggles already reads `content-before`. This is one of the sanctioned
renames where the better name already exists in Filament's own codebase.

_See also: the [rename table](../renames.md) — the `formBefore()` →
`contentBefore()` rename is recorded there, alongside the systematic
`toEmbeddedHtml()` → Twig template, `Htmlable` → `Markup`, `Str::slug` →
`AsciiSlugger`, and Alpine/Livewire → Stimulus translations this component leans
on._

### Extra Alpine attribute passthrough is not ported

Filament composes `HasExtraAlpineAttributes`, letting a caller attach arbitrary
`x-*`/Alpine attributes onto the section's outer element. Sylament omits it:
there is no Alpine, and the collapse wiring the section needs is driven by the
`sylament--support--section` Stimulus controller in the support Section component
rather than inline `x-data`. **Skipped**, not blocked — reversible if a userland
JS-attribute passthrough is ever wanted, but it would target plain/Stimulus
attributes, not Alpine.
