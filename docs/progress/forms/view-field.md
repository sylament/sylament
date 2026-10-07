---
description: 'The escape-hatch field whose entire body is a Twig template the user writes, with a fail-fast guard for a missing template.'
ports:
  - vendor/filament/forms/src/Components/ViewField.php
  - vendor/filament/support/src/Components/ViewComponent.php
  - vendor/filament/forms/docs/22-custom-fields.md
owns:
  - packages/forms/src/Components/ViewField.php
---
# ViewField (forms)

`Sylament\Forms\Components\ViewField` — the escape hatch: a field whose entire body is a Twig template the user writes.
It adds no state handling, no chrome and no options of its own; everything a consumer calls on it comes from [`Field`](../../../packages/forms/src/Components/Field.php) and, above it, support's [`ViewComponent`](../support/view-component.md) — `template()`, `templateData()` and `defaultTemplate()`.

Filament's class is literally `class ViewField extends Field {}`.
Ours carries one method, a fail-fast `getTemplate()` override, for a reason recorded [below](#the-missing-template-throw-lives-on-viewfield-rather-than-the-base).

`view()` → `template()` and `viewData()` → `templateData()` are the systematic Blade → Twig translation, not renames — the [idiom table](../translations.md#idiom) owns that row, and this file does not re-decide it.
`Field`'s own surface is tracked in [`field.md`](field.md) and the inherited template surface in [`support/view-component.md`](../support/view-component.md); the table lists what this field adds.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Construction (`ViewField::make('bio')->template(…)`) | [`Components/ViewField.php`](../../../vendor/filament/forms/src/Components/ViewField.php) | done | — | — |
| `template()` / `templateData()` ⟵ `view()` / `viewData()` | [`support/Components/ViewComponent.php`](../../../vendor/filament/support/src/Components/ViewComponent.php) | done | — | [detail](../support/view-component.md) |
| The template receives the field as `component` | [`$viewIdentifier = 'field'`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | [diverges](../support/view-component.md#the-instance-is-always-exposed-as-component) |
| The template opens its own field wrapper | [`forms/docs/22-custom-fields.md`](../../../vendor/filament/forms/docs/22-custom-fields.md) | done | — | [semantics](#the-wrapper-is-the-templates-to-open) |
| Fail-fast on a missing template | [`ViewComponent::getView()`](../../../vendor/filament/support/src/Components/ViewComponent.php) | done | — | [diverges](#the-missing-template-throw-lives-on-viewfield-rather-than-the-base) |
| `extractPublicMethods()` — `$get` / `$record` / `$operation` in template scope | [`support/Components/ViewComponent.php`](../../../vendor/filament/support/src/Components/ViewComponent.php) | skipped | — | [diverges](../support/view-component.md#extractpublicmethods-is-not-ported) |

## Semantics

### The wrapper is the template's to open

A `ViewField` renders **only** what its template renders.
Nothing wraps it: no label, no error message, no `sy-fo-field` div appears unless the template opens [`FieldWrapper`](../../../packages/forms/templates/components/FieldWrapper/index.html.twig) itself.
That is Filament's contract translated a tag at a time — its custom-field docs open every example with the wrapper component precisely because the field does not — and it is why the shipped fields' templates all start with that tag rather than inheriting one.

The fixture is the shape to copy:

```twig
<twig:SylamentForms:FieldWrapper :field="component">
    <twig:SylamentSupport:InputWrapper :disabled="component.isDisabled" :valid="component.getErrorMessages is empty">
        <input {{ attrs({
            class: 'sy-fo-input',
            type: 'text',
            id: component.getId,
            'data-model': component.getDataModel,
            value: component.getState ?? '',
        }) }}>
    </twig:SylamentSupport:InputWrapper>
</twig:SylamentForms:FieldWrapper>
```

The [input wrapper](../support/input-wrapper.md) is the template's to open too, and for the same reason — it is what paints the ring, background and focus state a text control does not carry itself, so a `sy-fo-input` outside one renders as bare text on the page background.
The second fixture, [`Unwrapped`](../../../packages/forms/tests/Fixtures/Twig/templates/partials/ViewFieldFixture/Unwrapped.html.twig), opens neither wrapper on purpose: a custom field that wants no chrome at all is a valid thing to want, and it is the case that proves nothing is inherited.

The tag names the wrapper directly where Filament's example resolves it through `$getFieldWrapperView()`.
Schemas' [`HasFieldWrapper`](../schemas/schema.md) ships as an opaque marker forms reads for exactly one value — the plain branch a [`FusedGroup`](fused-group.md#only-the-plain-wrapper-view-is-honored) puts on its children — not as a template name a custom field could point anywhere.
Swapping a wrapper means writing a different tag in the template, which is what the indirection buys anyway.

Both halves matter.
`:field="component"` is how the wrapper reaches the label, the hint, the error messages and the ten slots — the field is in scope as `component`, never as `field` ([why](../support/view-component.md#the-instance-is-always-exposed-as-component)) — and `data-model` is what binds the control to the host's state bag, the same attribute [`TextInput`](../../../packages/forms/templates/partials/TextInput/index.html.twig) emits.
A template that omits the wrapper renders a naked control and is a valid thing to want; a template that omits `data-model` renders a control that silently never writes back.

Filament's docs add a warning that survives the port unchanged: a form field is not a live component, so public properties and methods declared on a `ViewField` subclass are not addressable from the client.
A `stimulus_action('live', …)` in the template resolves against the enclosing `live` controller — the **host** component, the one `getLiveComponentOrNull()` returns — exactly as [`Action`'s templates](../../../packages/actions/templates/partials/Action/Button.html.twig) do, so a `#[LiveAction]` for a custom field goes on the host.

## Divergences

### The missing-template throw lives on `ViewField` rather than the base

Filament's `ViewComponent::getView()` throws `LogicException('Class [X] extends [ViewComponent] but does not have a [$view] property defined.')`, which is the only reason its `ViewField` can be an empty class.
Support's `getTemplate()` deliberately does **not**: a null template there is a valid "render nothing", recorded as [its own divergence](../support/view-component.md#two-level-template-resolution-with-null-vs-throw-tail-difference) and pinned by support's own tests.
Moving the throw up into the base would break that contract for every other component in the toolkit.

So the guard lands here instead, on the one component whose template is entirely the user's to supply and where rendering nothing is never what was meant:

```php
public function getTemplate(): string
{
    return parent::getTemplate() ?? throw new LogicException(sprintf(
        'The [%s] field has no template. Call template() on it, or declare a $template property on a subclass.',
        static::class,
    ));
}
```

The return type narrows to `string`, so a caller does not null-check what cannot be null.
`static::class` names the subclass, so a user's own `ViewField` descendant reports itself rather than this class.
The behaviour a consumer sees is Filament's — a loud failure naming the class and the fix — reached from the opposite direction, and the alternative was a field that renders as an empty string with no error anywhere, which is the failure mode the house rule against silent fallbacks exists to prevent.
