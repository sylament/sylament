# Component views (schemas)

The Twig templates in
[`packages/schemas/templates/partials/`](../../../packages/schemas/templates/partials)
— `Actions`, `EmbeddedSchema`, `Fieldset`, `Flex`, `Grid`, `Group`, `Html`,
`Icon`, `LiveComponent`, `Schema`,
`Section`, `Step`, `Tab`, `Tabs`, `Text`, `Wizard` — that render the
schemas components through Support's
[`ViewComponent`](../support/component.md). Each ports the HTML that a Filament
schemas component emits from its PHP `toEmbeddedHtml()` (Filament v5 no longer
ships Blade files for these — see the divergence below). This file is a thin
cross-index over those templates; per-feature specifics live in the
per-component files ([`actions.md`](actions.md), [`fieldset.md`](fieldset.md),
[`flex.md`](flex.md),
[`grid.md`](grid.md), [`group.md`](group.md), [`icon.md`](icon.md),
[`live-component.md`](live-component.md), [`embedded-schema.md`](embedded-schema.md),
[`tab.md`](tab.md), [`tabs.md`](tabs.md),
[`text.md`](text.md), [`section.md`](section.md), [`schema.md`](schema.md),
[`wizard.md`](wizard.md), [`step.md`](step.md)),
not here.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/Components/Actions.php`](../../../vendor/filament/schemas/src/Components/Actions.php),
  [`Fieldset.php`](../../../vendor/filament/schemas/src/Components/Fieldset.php),
  [`Flex.php`](../../../vendor/filament/schemas/src/Components/Flex.php),
  [`Grid.php`](../../../vendor/filament/schemas/src/Components/Grid.php),
  [`Group.php`](../../../vendor/filament/schemas/src/Components/Group.php),
  [`Html.php`](../../../vendor/filament/schemas/src/Components/Html.php),
  [`Icon.php`](../../../vendor/filament/schemas/src/Components/Icon.php),
  [`Section.php`](../../../vendor/filament/schemas/src/Components/Section.php),
  [`Tabs.php`](../../../vendor/filament/schemas/src/Components/Tabs.php),
  [`Tabs/Tab.php`](../../../vendor/filament/schemas/src/Components/Tabs/Tab.php),
  [`Text.php`](../../../vendor/filament/schemas/src/Components/Text.php),
  [`Livewire.php`](../../../vendor/filament/schemas/src/Components/Livewire.php),
  [`EmbeddedSchema.php`](../../../vendor/filament/schemas/src/Components/EmbeddedSchema.php),
  [`Schema.php`](../../../vendor/filament/schemas/src/Schema.php)
  — the `toEmbeddedHtml()` methods each template translates to Twig
- [`schemas/resources/views/components/`](../../../vendor/filament/schemas/resources/views/components)
  — the only Blade views Filament still ships (`fieldset`, `grid`); both are
  ported now ([`fieldset.md`](fieldset.md), [`grid.md`](grid.md))

## Surface

One row per template. _Ports_ links the Filament source it renders. _Blocked by_
names what has to land first. _Detail_ points to the per-component file for
in-progress features, a **diverges** note here for a structural one, or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Actions/index.html.twig`](../../../packages/schemas/templates/partials/Actions/index.html.twig) | [`Actions::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Actions.php) | in progress | — | [actions.md](actions.md) |
| [`EmbeddedSchema/index.html.twig`](../../../packages/schemas/templates/partials/EmbeddedSchema/index.html.twig) | [`EmbeddedSchema::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/EmbeddedSchema.php) | done | — | [embedded-schema.md](embedded-schema.md#the-partial-renders-the-schema-object-not-the-name) |
| [`Fieldset/index.html.twig`](../../../packages/schemas/templates/partials/Fieldset/index.html.twig) | [`Fieldset::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Fieldset.php) | done | — | [fieldset.md](fieldset.md) |
| [`Flex/index.html.twig`](../../../packages/schemas/templates/partials/Flex/index.html.twig) | [`Flex::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Flex.php) | done | — | [flex.md](flex.md) |
| [`Grid/index.html.twig`](../../../packages/schemas/templates/partials/Grid/index.html.twig) | [`Grid::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Grid.php) | done | — | [grid.md](grid.md) |
| [`Group/index.html.twig`](../../../packages/schemas/templates/partials/Group/index.html.twig) | [`Group::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Group.php) | done | — | [group.md](group.md) |
| [`Html/index.html.twig`](../../../packages/schemas/templates/partials/Html/index.html.twig) | [`Html::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Html.php) | done | — | — |
| [`Icon/index.html.twig`](../../../packages/schemas/templates/partials/Icon/index.html.twig) | [`Icon::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Icon.php) | in progress | — | [icon.md](icon.md) |
| [`LiveComponent/index.html.twig`](../../../packages/schemas/templates/partials/LiveComponent/index.html.twig) | [`Livewire::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Livewire.php) | done | — | [live-component.md](live-component.md#the-mount-is-a-twig-frame-not-a-php-call) |
| [`Schema/index.html.twig`](../../../packages/schemas/templates/partials/Schema/index.html.twig) | [`Schema::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Schema.php) | done | — | [diverges](#the-schema-template-omits-the-livecomponent-wiring-hidden-placeholders-and-grid-liberation) |
| [`Section/index.html.twig`](../../../packages/schemas/templates/partials/Section/index.html.twig) | [`Section::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Section.php) | done | — | [section.md](section.md) |
| [`Step/index.html.twig`](../../../packages/schemas/templates/partials/Step/index.html.twig) | [`Wizard\Step::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Wizard/Step.php) | done | — | [step.md](step.md) |
| [`Text/index.html.twig`](../../../packages/schemas/templates/partials/Text/index.html.twig) | [`Text::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Text.php) | in progress | — | [text.md](text.md) |
| [`Tabs/index.html.twig`](../../../packages/schemas/templates/partials/Tabs/index.html.twig) | [`Tabs::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Tabs.php) | done | — | [tabs.md](tabs.md) |
| [`Tab/index.html.twig`](../../../packages/schemas/templates/partials/Tab/index.html.twig) | [`Tab::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Tabs/Tab.php) | done | — | [tab.md](tab.md) |
| [`Wizard/index.html.twig`](../../../packages/schemas/templates/partials/Wizard/index.html.twig) | [`Wizard::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Wizard.php) | in progress | the `sylament--schemas--wizard` controller | [wizard.md](wizard.md) |

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
`SectionRenderTest`, `TextRenderTest`. The `html_attr()` / `html_classes()` ban
is guarded across every bundle by Twig-CS-Fixer's `ForbiddenFunctionRule`, wired
in [`.twig-cs-fixer.dist.php`](../../../.twig-cs-fixer.dist.php).

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

## Divergences

### Filament v5 renders these components from PHP `toEmbeddedHtml()`, not the Blade files the README names

The README's _Ports_ column maps the templates to
`resources/views/components/`, but that directory holds only `fieldset.blade.php`
and `grid.blade.php`. `Html`, `Icon`, `Schema`, `Section`, and `Text` carry no
Blade in Filament v5: they emit HTML from a PHP `toEmbeddedHtml()` method
(`ob_start` / string concatenation). The real source for each of the five Twig
templates is therefore that PHP method, translated to a template rendered
through Support's `ViewComponent`. The named Blade paths are historical
references, not live sources; nothing reverses this. The two surviving Blades are
no exception — `Fieldset` and `Grid` both render from `toEmbeddedHtml()` too, and
their Twig counterparts port that, not the Blade beside it
([`fieldset.md`](fieldset.md), [`grid.md`](grid.md)).

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
not the client-runtime idiom translation — they reverse once live component
state wiring and grid liberation are built.

_See also: the global [rename table](../renames.md) — the Blade/`toEmbeddedHtml`
→ Twig render, `Htmlable` → `Twig\Markup`, the client-runtime and attribute-bag
translations are systematic idiom translations recorded there, not per-template
divergences._
