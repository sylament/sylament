# Fieldset (schemas)

[`Sylament\Schemas\Components\Fieldset`](../../../packages/schemas/src/Components/Fieldset.php) is the labelled, bordered layout component: a two-column [`Grid`](grid.md) with a `<legend>` on it.
It carries only the label, the containment flag and the required marker — the chrome itself is support's [`<twig:SylamentSupport:Fieldset>`](../../../packages/support/templates/components/Fieldset/index.html.twig) component, which the schemas partial bridges onto.
Like `Grid` and [`Group`](group.md) it builds no grid of its own; `setUp()`'s `columns(2)` lands on the child [`Schema`](schema.md), which resolves the actual layout.

It composes the shared [`EntanglesStateWithSingularRelationship`](entangles-state-with-singular-relationship.md) concern and implements its `CanEntangleWithSingularRelationships` contract, so `Fieldset::make()->relationship('profile')` binds its children to a to-one Doctrine association; that concern's file carries the status of what is and is not ported.

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/Components/Fieldset.php`](../../../vendor/filament/schemas/src/Components/Fieldset.php) — the label constructor, `setUp()`'s `columns(2)`, the constant `isRequired()` and the `toEmbeddedHtml()` render path
- [`support/resources/views/components/fieldset.blade.php`](../../../vendor/filament/support/resources/views/components/fieldset.blade.php) — the `<fieldset>` / `<legend>` chrome the component renders through

## Surface

Every point of this component.
_Blocked by_ names what has to land first.
_Detail_ carries a **diverges** link to the permanent note in [Divergences](#divergences), a **semantics** link to [Semantics](#semantics), or `—`.

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

Covered by [`FieldsetTest`](../../../packages/schemas/tests/Unit/Components/FieldsetTest.php) (the label arms, the default and overridden columns, containment, label hiding, the constant `isRequired()`, the four `markAsRequired()` arms and `configureUsing()`) and [`FieldsetComponentRenderTest`](../../../packages/schemas/tests/Integration/Components/FieldsetComponentRenderTest.php), whose last two cases are the attribute-precedence gates: a caller's own `class` must not displace the `sy-sc-fieldset` hook, and a caller's own `id` must not displace `getId()`.
The support component on its own is covered by [`FieldsetRenderTest`](../../../packages/schemas/tests/Integration/Components/FieldsetRenderTest.php).

## Semantics

Faithful-in-spirit behaviour a reader would plausibly misread from the source.
Permanent — these outlive the detail sections above.

### `isRequired()` is a constant `false` so the asterisk is opt-in

`Fieldset::isRequired()` returns `false` unconditionally.
It exists only to feed [`CanBeMarkedAsRequired`](can-be-marked-as-required.md)'s `?? $this->isRequired()` fallback, which the trait leaves to the using class.
A reader re-deriving the component would reasonably expect a fieldset to inherit required-ness from its children — that a fieldset holding one required field would mark itself.
It does not, on either side of the port: the legend takes its asterisk only after an explicit `markAsRequired()`.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold.

### The chrome lives in a support Twig component

`Fieldset::toEmbeddedHtml()` builds the `<fieldset>`, the `<legend>` and the required `<sup>` inline in the schemas component, even though Filament also ships `x-filament::fieldset` in support and the (unused in v5) `filament-schemas::components.fieldset` Blade renders through it.
Sylament keeps only the support component: the schemas partial [`Fieldset/index.html.twig`](../../../packages/schemas/templates/partials/Fieldset/index.html.twig) is a bridge that maps the component's getters onto the four props (`label`, `labelHidden`, `required`, `contained`) and passes the child schema as the slot — the same split [`Section`](section.md) already uses.

The consequence to know when reading the markup: the `sy-fieldset*` classes and the whole element structure belong to **support**, while `sy-sc-fieldset` is schemas'.
Three layers decide the final `class`, in this order — the [`ComponentAttributeBag::merge()`](../../../packages/support/src/Twig/ComponentAttributeBag.php) call in the partial appends a caller's `extraAttributes(['class' => …])` after `sy-sc-fieldset`, `toScalarAttributes()` flattens the token list to a string so the `<twig:>` spread is legal, and ux-twig-component's own `ComponentAttributes::defaults()` concatenates support's defaults in front.
A fieldset with `extraAttributes(['class' => 'custom'])` therefore renders `class="sy sy-fieldset sy-sc-fieldset custom"`.

`id` cannot ride that same merge.
`merge()` lets the **receiving** bag win for a non-appendable attribute, and the receiver here is the caller's extra attributes — so passing `id` as a merge default would hand `extraAttributes(['id' => …])` the win, where Filament's `->merge(['id' => $id])->merge($this->getExtraAttributes())` lets `getId()` win (Laravel's `merge()` ends `array_merge($attributeDefaults, $attributes)`, so the receiver holding the id overrides the extras it is passed).
The partial therefore spreads `{id: component.getId}` over the flattened map **last**, which is the same last-source-wins placement [`Section`](section.md)'s `attrs()` call uses and which [`Grid`](grid.md) and [`Group`](group.md) repeat.

_See also: the global [rename table](../renames.md) — `make()` arguments moving from constructors to setters, the `toEmbeddedHtml()` → Twig-template render idiom, and the `Htmlable` → `Twig\Markup` translation are systematic translations recorded there, not per-component divergences._
