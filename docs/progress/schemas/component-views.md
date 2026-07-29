# Component views (schemas)

The Twig templates in
[`packages/schemas/templates/partials/`](../../../packages/schemas/templates/partials)
— `Actions`, `Html`, `Icon`, `Schema`, `Section`, `Text` — that render the
schemas components through Support's
[`ViewComponent`](../support/component.md). Each ports the HTML that a Filament
schemas component emits from its PHP `toEmbeddedHtml()` (Filament v5 no longer
ships Blade files for these — see the divergence below). This file is a thin
cross-index over the six templates; per-feature specifics live in the
per-component files ([`actions.md`](actions.md), [`icon.md`](icon.md),
[`text.md`](text.md), [`section.md`](section.md), [`schema.md`](schema.md)),
not here.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/Components/Actions.php`](../../../vendor/filament/schemas/src/Components/Actions.php),
  [`Html.php`](../../../vendor/filament/schemas/src/Components/Html.php),
  [`Icon.php`](../../../vendor/filament/schemas/src/Components/Icon.php),
  [`Section.php`](../../../vendor/filament/schemas/src/Components/Section.php),
  [`Text.php`](../../../vendor/filament/schemas/src/Components/Text.php),
  [`Schema.php`](../../../vendor/filament/schemas/src/Schema.php)
  — the `toEmbeddedHtml()` methods each template translates to Twig
- [`schemas/resources/views/components/`](../../../vendor/filament/schemas/resources/views/components)
  — the only Blade views Filament still ships (`fieldset`, `grid`), for the
  still-unported Fieldset and Grid components

## Surface

One row per template. _Ports_ links the Filament source it renders. _Blocked by_
names what has to land first. _Detail_ points to the per-component file for
in-progress features, a **diverges** note here for a structural one, or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Actions/index.html.twig`](../../../packages/schemas/templates/partials/Actions/index.html.twig) | [`Actions::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Actions.php) | in progress | — | [actions.md](actions.md) |
| [`Html/index.html.twig`](../../../packages/schemas/templates/partials/Html/index.html.twig) | [`Html::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Html.php) | done | — | — |
| [`Icon/index.html.twig`](../../../packages/schemas/templates/partials/Icon/index.html.twig) | [`Icon::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Icon.php) | in progress | — | [icon.md](icon.md) |
| [`Schema/index.html.twig`](../../../packages/schemas/templates/partials/Schema/index.html.twig) | [`Schema::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Schema.php) | done | — | [diverges](#the-schema-template-omits-the-livecomponent-wiring-hidden-placeholders-and-grid-liberation) |
| [`Section/index.html.twig`](../../../packages/schemas/templates/partials/Section/index.html.twig) | [`Section::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Section.php) | done | — | [section.md](section.md) |
| [`Text/index.html.twig`](../../../packages/schemas/templates/partials/Text/index.html.twig) | [`Text::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Text.php) | in progress | — | [text.md](text.md) |
| Fieldset template | [`fieldset.blade.php`](../../../vendor/filament/schemas/resources/views/components/fieldset.blade.php) | todo | Fieldset component | — |
| Grid template | [`grid.blade.php`](../../../vendor/filament/schemas/resources/views/components/grid.blade.php) | todo | Grid component | — |

Every template carries a render test in schemas' Integration suite (only the
schemas Kernel registers TwigComponentBundle/StimulusBundle, so these templates
are exercised from here, not from support). Since the schemas↔actions
integration, [the schemas test Kernel](../../../packages/schemas/tests/Fixtures/Kernel.php)
also registers `SylamentActionsBundle`: rendering an `Action`/`ActionGroup`
child — from `Schema/index.html.twig`'s action branch, `Section/index.html.twig`'s
header/footer slots, or `Actions/index.html.twig` — resolves actions' own
`@SylamentActions`-namespaced templates (`Action/Button.html.twig` and
friends), which only that bundle's registration makes loadable:
[`ActionsRenderTest`](../../../packages/schemas/tests/Integration),
`HtmlRenderTest`, `IconComponentRenderTest`, `SchemaRenderTest`,
`SectionRenderTest`, `TextRenderTest`. Architecture/`TemplateConventionsTest`
guards all bundle templates against `html_attr()` / `html_classes()`.

## Details

Write-ups for the rows that are not **done** — what is in, what is left, the next
step. Feature-level state stays in the per-component file each row links.

### Actions template — label and row rendered

`Actions/index.html.twig` renders the outer column, the label heading, and the
visible-actions row with alignment/full-width classes. The sticky Alpine wiring,
the `fi-vertical-align-*` class, and the four label/content slot schemas are
unported; each is tracked against the component in [`actions.md`](actions.md).
Next step: close those component gaps — their render halves land here with them.

### Icon template — subset rendered

`Icon/index.html.twig` renders the icon svg with color, size, and a plain `title`
tooltip. What is left (the sr-only accessible-text fallback and `aria-label`
relocation) is tracked against the component in [`icon.md`](icon.md); the
`title`-vs-Stimulus-tooltip choice is the systematic idiom translation, not a
gap. Next step: close the accessibility gap noted in `icon.md`.

### Text template — plain-text subset rendered

`Text/index.html.twig` renders a `<span>` with size / weight / font-family / color
classes over escaped-or-`Markup` content. Badge, leading/trailing icon,
copyable, and tooltip modes are unported; they are tracked in [`text.md`](text.md)
because they need the corresponding methods on the Text component first. Next
step: port those onto the component and template when the badge / copy-to-clipboard
behaviors are wanted.

### Fieldset and Grid templates — todo

The two real Filament Blade views (`fieldset`, `grid`) have no Twig counterpart
because the Fieldset and Grid components are not ported yet. Each unblocks when
its component lands.

## Divergences

### Filament v5 renders these components from PHP `toEmbeddedHtml()`, not the Blade files the README names

The README's _Ports_ column maps the templates to
`resources/views/components/`, but that directory holds only `fieldset.blade.php`
and `grid.blade.php` — both for still-unported components. `Html`, `Icon`,
`Schema`, `Section`, and `Text` carry no Blade in Filament v5: they emit HTML
from a PHP `toEmbeddedHtml()` method (`ob_start` / string concatenation). The
real source for each of the five Twig templates is therefore that PHP method,
translated to a template rendered through Support's `ViewComponent`. The named
Blade paths are historical references, not live sources; nothing reverses this.
The two actual Blades (`fieldset`, `grid`) stay unported alongside their
components.

### The Schema template omits the LiveComponent wiring, hidden placeholders, and grid liberation

Filament's `Schema::toEmbeddedHtml` emits root state wiring (the client
data-object, partial markers, window listeners), keeps hidden children in the
DOM as placeholder wrappers, and honors `isLiberatedFromContainerGrid()`.
`Schema/index.html.twig` renders none of these: it loops the `visibleChildren` pairs
`Schema::getExtraTemplateData()` precomputes (visible children only — see the
[hidden-placeholder divergence](schema.md#the-schema-renders-visible-children-only-without-hidden-placeholders))
and has no liberated-from-grid branch. What it does render now matches
Filament: an `Action`/`ActionGroup` child (flagged `isAction` in the pair) goes
into a bare `div.sy-sc-action` wrapper instead of the grid-cell +
`sy-sc-component` wrappers, and `Schema::render()` returns `null` when nothing
is visible — Filament's `''`-on-empty contract. The remaining gaps are genuine,
not the client-runtime idiom translation — they reverse when the
live-component-state epic and grid liberation land.

_See also: the global [rename table](../renames.md) — the Blade/`toEmbeddedHtml`
→ Twig render, `Htmlable` → `Twig\Markup`, the client-runtime and attribute-bag
translations are systematic idiom translations recorded there, not per-template
divergences._
