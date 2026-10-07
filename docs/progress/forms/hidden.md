# Hidden (forms)

`Sylament\Forms\Components\Hidden` — the field that holds a value without showing one.
Extends [`Field`](../../../packages/forms/src/Components/Field.php) and adds exactly two things over it: a `setUp()` that spans its grid column `'hidden'`, and a template that renders one `<input type="hidden">` and nothing else.
No wrapper, no label, no error message, no stylesheet.

Its whole API is `Hidden::make('token')` — everything else it exposes is `Field`'s.

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`forms/src/Components/Hidden.php`](../../../vendor/filament/forms/src/Components/Hidden.php) — the whole class: `setUp()`, `toEmbeddedHtml()`, and the published-view path
- [`forms/docs/21-hidden.md`](../../../vendor/filament/forms/docs/21-hidden.md) — the user-facing docs, one example and one warning

## Surface

Every point this field adds over `Field`.
`Field`'s own surface is tracked in [`field.md`](field.md), not here.
_Blocked by_ names what has to land first.
_Detail_ links a permanent note in [Semantics](#semantics) / [Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Construction &amp; rendering (`Hidden::make('token')`) | [`Components/Hidden.php`](../../../vendor/filament/forms/src/Components/Hidden.php) | done | — | — |
| `columnSpan(['default' => 'hidden'])` collapsing the schema slot | [`Hidden::setUp()`](../../../vendor/filament/forms/src/Components/Hidden.php) | done | — | [semantics](#the-slot-collapse-is-the-whole-styling-story) |
| Embedded view → Twig template, rendered with no field wrapper | [`Hidden::toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/Hidden.php) | done | — | [semantics](#nothing-wraps-the-input-and-nothing-should) |
| `id` / `data-model` binding | [`Hidden::toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/Hidden.php) | done | — | — |
| State rendered server-side into `value` | [`Hidden::toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/Hidden.php) | done | — | [diverges](#the-value-attribute-is-rendered-server-side-and-never-omitted) |
| `extraAttributes()` merged onto the input | [`Hidden::toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/Hidden.php) | done | — | — |
| No `name` attribute | [`Hidden::toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/Hidden.php) | done | — | [semantics](#the-input-carries-no-name-attribute) |
| `getPublishedViewOverrideCheckPath()` | [`Components/Hidden.php`](../../../vendor/filament/forms/src/Components/Hidden.php) | skipped | — | [diverges](toggle-buttons.md#there-is-nothing-to-publish-a-view-override-against) |

Coverage: the bare input and the absence of any wrapper, label or error element; the `type` / `id` / `data-model` attributes; the empty `value` attribute an unset field renders; a `default()` round-tripping through `value` off a hydrated schema; the `'hidden'` column span both on the component and as `sy-hidden` on the rendered schema slot; extra attributes reaching the input and appending to its class list; and the absent `name` attribute — by [`HiddenTest`](../../../packages/forms/tests/Integration/Components/HiddenTest.php).

## Semantics

Behaviour that is faithful to Filament but easy to misread here.

### Nothing wraps the input, and nothing should

Every other field's template opens [`FieldWrapper`](../../../packages/forms/templates/components/FieldWrapper/index.html.twig); this one does not, so a `Hidden` renders no `sy-fo-field` div, no `<label>` and no error message — `label()`, `helperText()`, `hint()` and the ten wrapper slots are inherited from `Field` and silently render nothing.
That is Filament-exact: `Hidden::toEmbeddedHtml()` returns `'<input ' . … . ' />'` and never calls `wrapEmbeddedHtml()`, the base helper that wraps every other embedded field.

The reason it is not an oversight in either codebase: the wrapper exists to show a label and an error, and a field the user cannot see has nothing to show.
Validation still runs — a `Hidden` is an ordinary field to [`validateSchema()`](../../../packages/schemas/src/Concerns/HasSchemas.php), which walks `getValidationConstraints()` and reads nothing about layout — but a violation on it has no element of its own to surface in, which is a reason to keep constraints off hidden fields rather than a reason to grow this template a wrapper.

One consequence changes where a setter lands.
`extraAttributes()` targets the **wrapper** element on every other field ([precedence](field.md#extra-attribute-precedence-is-fixed-by-source-order)); with no wrapper here it targets the input itself, which is again Filament-exact — `toEmbeddedHtml()` merges `getExtraAttributes()` straight into the input's bag.
`extraInputAttributes()` is not available at all: neither class composes [`HasExtraInputAttributes`](has-extra-input-attributes.md), so `extraAttributes()` is the only hatch, and it is the one that reaches the control.

### The slot collapse is the whole styling story

`setUp()` calls `columnSpan(['default' => 'hidden'])`, which [`GridExtension::getGridColumnAttributes()`](../../../packages/support/src/Twig/Extension/GridExtension.php) turns into `sy-hidden` on the **parent grid column** — the slot div schemas' own template emits around each child — so the schema collapses the gap the field would otherwise leave.
Nothing hides the input itself: `type="hidden"` already does that.

So `sy-fo-hidden` is a hook, not a style, and forms ships **no** `hidden.css`.
That is Filament's arrangement too — `fi-fo-hidden` appears in no stylesheet under [`forms/resources/css/`](../../../vendor/filament/forms/resources/css/) — and it is why this field earns no row in [`styles.md`](styles.md): there is no vendor stylesheet to diff against.
Reading the missing file as a gap is the mistake this note exists to prevent.

### The input carries no `name` attribute

Every single-control sibling emits a `name` beside `id` — `component.getName` on `TextInput`, `Textarea`, `Select`, `Checkbox` and `Toggle`, `component.getId` on the option fields, which need one shared name per radio group.
This one emits none, matching Filament, whose `toEmbeddedHtml()` merges `id`, `type` and the state binding and stops.
State reaches the host through `data-model` and the live component, exactly as it does for a `TextInput` — `name` only matters to a native form submit, which no field in this port relies on.
Adding it back would be a divergence, not a fix.

### A hidden field is not a private one

Filament's docs carry the warning verbatim, and it survives the port unchanged: the value is still editable by anyone who opens the browser's developer tools.
`Hidden` is for carrying a value the form needs, not for keeping one from the user — do not put sensitive or read-only data in it.
The state arrives back through the same `data-model` write path as any other field's, so the server must treat it as user input.

## Divergences

Deliberate deviations from Filament, and the reason each holds.

### The `value` attribute is rendered server-side, and never omitted

Filament emits only `wire:model` and lets Livewire fill the input once it boots.
The port renders `value: component.getState ?? ''`, so a server-rendered edit page carries the stored value before any JavaScript runs — the same class of addition as the [`checked` attribute](checkbox.md#the-checked-attribute-is-rendered-server-side) on `Checkbox` and the `value` on [`TextInput`](../../../packages/forms/templates/partials/TextInput/index.html.twig).

The `?? ''` is the load-bearing half.
`getState()` is null for an unset field, and a **missing** `value` attribute is not the same as `value=""` to idiomorph, which will not reset a user-typed value against an attribute that isn't there.
Dropping the coalesce would leave a stale client value in place across a re-render on exactly the field whose value nobody can see.
