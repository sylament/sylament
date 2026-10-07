---
description: The labelled, bordered schemas layout component that lays its children out in two columns under a `<legend>`.
ports:
  - vendor/filament/schemas/src/Components/Fieldset.php
owns:
  - packages/schemas/src/Components/Fieldset.php
  - packages/schemas/templates/partials/Fieldset/
---
# Fieldset (schemas)

[`Sylament\Schemas\Components\Fieldset`](../../../packages/schemas/src/Components/Fieldset.php) is the labelled, bordered layout component: a `<legend>` over children laid out in two columns.
It carries only the label, the containment flag and the required marker — the chrome itself is support's [`<twig:SylamentSupport:Fieldset>`](../../../packages/support/templates/components/Fieldset/index.html.twig) component, which the schemas partial bridges onto.
Like `Grid` and [`Group`](group.md) it builds no grid of its own; `setUp()`'s `columns(2)` lands on the child [`Schema`](schema.md), which resolves the actual layout.

It composes the shared [`EntanglesStateWithSingularRelationship`](entangles-state-with-singular-relationship.md) concern and implements its `CanEntangleWithSingularRelationships` contract, so `Fieldset::make()->relationship('profile')` binds its children to a to-one Doctrine association.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make()` / label forwarding](../../../packages/schemas/src/Components/Fieldset.php) | [`Fieldset::make()` / `__construct()`](../../../vendor/filament/schemas/src/Components/Fieldset.php) | done | — | — |
| [`setUp()` → `columns(2)`](../../../packages/schemas/src/Components/Fieldset.php) | [`Fieldset::setUp()`](../../../vendor/filament/schemas/src/Components/Fieldset.php) | done | — | — |
| [`contained()` / `isContained()`](../../../packages/support/src/Components/Concerns/CanBeContained.php) | [`Support\Concerns\CanBeContained`](../../../vendor/filament/support/src/Concerns/CanBeContained.php) | done | — | — |
| [`hiddenLabel()` / `isLabelHidden()`](../../../packages/schemas/src/Components/Concerns/HasLabel.php) | [`Components/Concerns/HasLabel.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasLabel.php) | done | — | [has-label.md](has-label.md) |
| [`markAsRequired()` / `isMarkedAsRequired()`](../../../packages/schemas/src/Components/Concerns/CanBeMarkedAsRequired.php) | [`forms/Components/Concerns/CanBeMarkedAsRequired.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeMarkedAsRequired.php) | done | — | [can-be-marked-as-required.md](can-be-marked-as-required.md) |
| [`isRequired()`](../../../packages/schemas/src/Components/Fieldset.php) | [`Fieldset::isRequired()`](../../../vendor/filament/schemas/src/Components/Fieldset.php) | done | — | [semantics](#isrequired-is-a-constant-false-so-the-asterisk-is-opt-in) |
| [Twig render (support chrome + child schema)](../../../packages/schemas/templates/partials/Fieldset/index.html.twig) | [`Fieldset::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Fieldset.php) | done | — | [diverges](#the-chrome-lives-in-a-support-twig-component) |
| [`id` + extra attributes on the root](../../../packages/schemas/templates/partials/Fieldset/index.html.twig) | [`Fieldset::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Fieldset.php) | done | — | — |

## Semantics

### `isRequired()` is a constant `false` so the asterisk is opt-in

`Fieldset::isRequired()` returns `false` unconditionally.
It exists only to feed [`CanBeMarkedAsRequired`](can-be-marked-as-required.md)'s `?? $this->isRequired()` fallback, which the trait leaves to the using class.
A reader re-deriving the component would reasonably expect a fieldset to inherit required-ness from its children — that a fieldset holding one required field would mark itself.
It does not, on either side of the port: the legend takes its asterisk only after an explicit `markAsRequired()`.

## Divergences

### The chrome lives in a support Twig component

`Fieldset::toEmbeddedHtml()` builds the `<fieldset>`, the `<legend>` and the required `<sup>` inline in the schemas component, even though Filament also ships `x-filament::fieldset` in support.
The `filament-schemas::components.fieldset` Blade renders through that support component, and Filament uses it only when an application publishes its own override of the view.
Sylament keeps only the support component: the schemas partial [`Fieldset/index.html.twig`](../../../packages/schemas/templates/partials/Fieldset/index.html.twig) is a bridge that maps the component's getters onto the four props (`label`, `labelHidden`, `required`, `contained`) and passes the child schema as the slot — the same split [`Section`](section.md) already uses.

The consequence to know when reading the markup: the `sy-fieldset*` classes and the whole element structure belong to **support**, while `sy-sc-fieldset` is schemas'.
Three layers decide the final `class`, in this order — the [`ComponentAttributeBag::merge()`](../../../packages/support/src/Twig/ComponentAttributeBag.php) call in the partial appends a caller's `extraAttributes(['class' => …])` after `sy-sc-fieldset`, `toScalarAttributes()` flattens the token list to a string so the `<twig:>` spread is legal, and ux-twig-component's own `ComponentAttributes::defaults()` concatenates support's defaults in front.
A fieldset with `extraAttributes(['class' => 'custom'])` therefore renders `class="sy sy-fieldset sy-sc-fieldset custom"`.

`id` cannot ride that same merge.
`merge()` lets the **receiving** bag win for a non-appendable attribute, and the receiver here is the caller's extra attributes — so passing `id` as a merge default would hand `extraAttributes(['id' => …])` the win, where Filament's `->merge(['id' => $id])->merge($this->getExtraAttributes())` lets `getId()` win (Laravel's `merge()` ends `array_merge($attributeDefaults, $attributes)`, so the receiver holding the id overrides the extras it is passed).
The partial therefore spreads `{id: component.getId}` over the flattened map **last**, which is the same last-source-wins placement [`Section`](section.md)'s `attrs()` call uses and which [`Grid`](grid.md) and [`Group`](group.md) repeat.
