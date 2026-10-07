---
description: Cross-index of the schemas component templates under packages/schemas/templates/partials/ and the Filament render methods each one ports.
ports:
  - vendor/filament/schemas/src/Components/Actions.php
  - vendor/filament/schemas/src/Components/EmbeddedSchema.php
  - vendor/filament/schemas/src/Components/Fieldset.php
  - vendor/filament/schemas/src/Components/Flex.php
  - vendor/filament/schemas/src/Components/Grid.php
  - vendor/filament/schemas/src/Components/Group.php
  - vendor/filament/schemas/src/Components/Html.php
  - vendor/filament/schemas/src/Components/Icon.php
  - vendor/filament/schemas/src/Components/Livewire.php
  - vendor/filament/schemas/src/Schema.php
  - vendor/filament/schemas/src/Components/Section.php
  - vendor/filament/schemas/src/Components/Wizard/Step.php
  - vendor/filament/schemas/src/Components/Text.php
  - vendor/filament/schemas/src/Components/Tabs.php
  - vendor/filament/schemas/src/Components/Tabs/Tab.php
  - vendor/filament/schemas/src/Components/Wizard.php
owns:
  - packages/schemas/templates/partials/Html/
---
# Component views (schemas)

The Twig templates in [`packages/schemas/templates/partials/`](../../../packages/schemas/templates/partials) render the schemas components through support's [`ViewComponent`](../support/view-component.md).
Each row's status follows the per-component file it links, which holds the feature-level reasoning: [`actions.md`](actions.md), [`embedded-schema.md`](embedded-schema.md), [`fieldset.md`](fieldset.md), [`flex.md`](flex.md), [`grid.md`](grid.md), [`group.md`](group.md), [`html.md`](html.md), [`icon.md`](icon.md), [`live-component.md`](live-component.md), [`schema.md`](schema.md), [`section.md`](section.md), [`step.md`](step.md), [`tab.md`](tab.md), [`tabs.md`](tabs.md), [`text.md`](text.md) and [`wizard.md`](wizard.md).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Actions/index.html.twig`](../../../packages/schemas/templates/partials/Actions/index.html.twig) | [`Actions::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Actions.php) | in progress | — | [actions.md](actions.md#details) |
| [`EmbeddedSchema/index.html.twig`](../../../packages/schemas/templates/partials/EmbeddedSchema/index.html.twig) | [`EmbeddedSchema::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/EmbeddedSchema.php) | done | — | [embedded-schema.md](embedded-schema.md#the-partial-renders-the-schema-object-not-the-name) |
| [`Fieldset/index.html.twig`](../../../packages/schemas/templates/partials/Fieldset/index.html.twig) | [`Fieldset::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Fieldset.php) | done | — | [fieldset.md](fieldset.md) |
| [`Flex/index.html.twig`](../../../packages/schemas/templates/partials/Flex/index.html.twig) | [`Flex::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Flex.php) | done | — | [flex.md](flex.md) |
| [`Grid/index.html.twig`](../../../packages/schemas/templates/partials/Grid/index.html.twig) | [`Grid::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Grid.php) | done | — | [grid.md](grid.md) |
| [`Group/index.html.twig`](../../../packages/schemas/templates/partials/Group/index.html.twig) | [`Group::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Group.php) | done | — | [group.md](group.md) |
| [`Html/index.html.twig`](../../../packages/schemas/templates/partials/Html/index.html.twig) | [`Html::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Html.php) | done | — | [html.md](html.md) |
| [`Icon/index.html.twig`](../../../packages/schemas/templates/partials/Icon/index.html.twig) | [`Icon::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Icon.php) | in progress | — | [icon.md](icon.md#details) |
| [`LiveComponent/index.html.twig`](../../../packages/schemas/templates/partials/LiveComponent/index.html.twig) | [`Livewire::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Livewire.php) | done | — | [live-component.md](live-component.md#the-mount-is-a-twig-frame-not-a-php-call) |
| [`Schema/index.html.twig`](../../../packages/schemas/templates/partials/Schema/index.html.twig) | [`Schema::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Schema.php) | done | — | [schema.md](schema.md#the-root-schema-drops-livewire-partial-render-and-alpine-wiring) · [schema.md](schema.md#the-schema-renders-visible-children-only-without-hidden-placeholders) |
| [`Section/index.html.twig`](../../../packages/schemas/templates/partials/Section/index.html.twig) | [`Section::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Section.php) | done | — | [section.md](section.md) |
| [`Step/index.html.twig`](../../../packages/schemas/templates/partials/Step/index.html.twig) | [`Wizard\Step::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Wizard/Step.php) | done | — | [step.md](step.md) |
| [`Text/index.html.twig`](../../../packages/schemas/templates/partials/Text/index.html.twig) | [`Text::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Text.php) | in progress | — | [text.md](text.md#details) |
| [`Tabs/index.html.twig`](../../../packages/schemas/templates/partials/Tabs/index.html.twig) | [`Tabs::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Tabs.php) | done | — | [tabs.md](tabs.md) |
| [`Tab/index.html.twig`](../../../packages/schemas/templates/partials/Tab/index.html.twig) | [`Tab::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Tabs/Tab.php) | done | — | [tab.md](tab.md) |
| [`Wizard/index.html.twig`](../../../packages/schemas/templates/partials/Wizard/index.html.twig) | [`Wizard::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Wizard.php) | done | — | [wizard.md](wizard.md) |

## Semantics

### Each template ports a PHP `toEmbeddedHtml()`, not a Blade view

Filament's schemas components emit their HTML from a PHP `toEmbeddedHtml()` method, and each Twig template here translates that method.
Filament's `resources/views/components/` holds only `fieldset.blade.php` and `grid.blade.php`, which back `Fieldset`'s and `Grid`'s `publishedViewOverrideCheckPath` and `MorphToSelect`'s view; `Fieldset` and `Grid` render from `toEmbeddedHtml()` too, and their templates port that method, not the Blade beside it.
