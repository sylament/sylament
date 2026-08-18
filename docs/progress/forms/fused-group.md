# FusedGroup (forms)

[`Sylament\Forms\Components\FusedGroup`](../../../packages/forms/src/Components/FusedGroup.php)
— the container that fuses several controls into one bordered box under a single
label: a ring and a rounded corner pair on the child schema, a divider between
the children, and every child's own wrapper chrome switched off. It extends the
schemas [`Component`](../schemas/component.md) rather than
[`Field`](field.md) — it holds no state of its own and binds no path — yet it
renders **as** a field: the group's label, required mark, helper text, error list
and ten slots all come from forms'
[`FieldWrapper`](field.md), with the group itself handed in as the `field`.

It lives in **forms**, not schemas, which is the one structural departure from
Filament — see [below](#fusedgroup-lives-in-forms-not-schemas).

The seam that switches the children off is schemas'
[`Schema::fieldWrapperView()`](../schemas/schema.md), pure data there; forms
honors exactly one value, the `PLAIN_FIELD_WRAPPER_VIEW` constant this class
declares, and `FieldWrapper` branches on it.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/Components/FusedGroup.php`](../../../vendor/filament/schemas/src/Components/FusedGroup.php)
  — the class: the ten slots, the two child-schema hooks, the error collection
  loop and `isRequired()`
- [`schemas/src/Concerns/HasFieldWrapper.php`](../../../vendor/filament/schemas/src/Concerns/HasFieldWrapper.php)
  — the container-tier seam ported into schemas
- [`forms/resources/views/components/plain-field-wrapper.blade.php`](../../../vendor/filament/forms/resources/views/components/plain-field-wrapper.blade.php)
  — the view the seam points at, which becomes a branch of our one wrapper
- [`schemas/resources/css/components/fused-group.css`](../../../vendor/filament/schemas/resources/css/components/fused-group.css)
  — the stylesheet, ported whole

## Surface

Every point of this component. _Blocked by_ names what has to land first.
_Detail_ carries either or both: **semantics** → the permanent note in
[Semantics](#semantics); **diverges** → the permanent note in
[Divergences](#divergences) — or a pointer to the file that owns the row.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make(array\|Closure $schema = [])`](../../../packages/forms/src/Components/FusedGroup.php) | [`FusedGroup::make()`](../../../vendor/filament/schemas/src/Components/FusedGroup.php) | done | — | — |
| [`setUp()` → `gap(false)`](../../../packages/forms/src/Components/FusedGroup.php) | [`FusedGroup::setUp()`](../../../vendor/filament/schemas/src/Components/FusedGroup.php) | done | — | — |
| [`label()` / `hiddenLabel()`](../../../packages/schemas/src/Components/Concerns/HasLabel.php) | [`Components\Concerns\HasLabel`](../../../vendor/filament/schemas/src/Components/Concerns/HasLabel.php) | done | — | [has-label.md](../schemas/has-label.md) |
| [`helperText()`](../../../packages/forms/src/Components/Concerns/HasHelperText.php) | [`Components\Concerns\HasHelperText`](../../../vendor/filament/forms/src/Components/Concerns/HasHelperText.php) | done | — | [field.md](field.md) |
| [`extraFieldWrapperAttributes()`](../../../packages/forms/src/Components/Concerns/HasExtraFieldWrapperAttributes.php) | [`Components\Concerns\HasExtraFieldWrapperAttributes`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraFieldWrapperAttributes.php) | done | — | [field.md](field.md) |
| [the ten slot setters `aboveLabel()` … `belowErrorMessage()`](../../../packages/forms/src/Components/FusedGroup.php) | [`FusedGroup::aboveLabel()` …](../../../vendor/filament/schemas/src/Components/FusedGroup.php) | done | — | — |
| [`makeChildSchema()` — plain view on every child schema, `alignEnd()` on the after-label slot](../../../packages/forms/src/Components/FusedGroup.php) | [`FusedGroup::makeChildSchema()`](../../../vendor/filament/schemas/src/Components/FusedGroup.php) | done | — | [semantics](#a-pre-built-schema-handed-in-as-a-component-never-meets-the-plain-view-hook) |
| [`configureChildSchema()` — inline + embedded + small actions on the six inline slots](../../../packages/forms/src/Components/FusedGroup.php) | [`FusedGroup::configureChildSchema()`](../../../vendor/filament/schemas/src/Components/FusedGroup.php) | done | — | — |
| [`isRequired()` — any default-slot child field is required-marked](../../../packages/forms/src/Components/FusedGroup.php) | [`FusedGroup::isRequired()`](../../../vendor/filament/schemas/src/Components/FusedGroup.php) | done | — | — |
| [`getErrorMessages()` — the first path-bearing child field carrying any](../../../packages/forms/src/Components/FusedGroup.php) | [the collection loop in `toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/FusedGroup.php) | done | — | [diverges](#the-error-collection-keeps-the-first-failing-field-and-nothing-else) |
| [`FusedGroup/index.html.twig` — the group root inside `FieldWrapper`](../../../packages/forms/templates/partials/FusedGroup/index.html.twig) | [the wrapper rendering in `toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/FusedGroup.php) | done | — | [diverges](#the-group-renders-through-fieldwrapper-instead-of-a-second-copy-of-it) · [diverges](#the-group-root-carries-no-aria-describedby) |
| [`fused-group.css` — the ring, the dividers and the corner rules](../../../packages/forms/assets/src/styles/components/fused-group.css) | [`components/fused-group.css`](../../../vendor/filament/schemas/resources/css/components/fused-group.css) | done | — | [semantics](#the-corner-rules-reach-through-two-elements-the-schema-partial-nests) |
| [`Schema::fieldWrapperView()` (the seam) + `PLAIN_FIELD_WRAPPER_VIEW`](../../../packages/schemas/src/Concerns/HasFieldWrapper.php) | [`Concerns\HasFieldWrapper`](../../../vendor/filament/schemas/src/Concerns/HasFieldWrapper.php) | done | — | [diverges](#only-the-plain-wrapper-view-is-honored) |
| Component-tier `HasFieldWrapper` (own → container → default) | [`Components\Concerns\HasFieldWrapper`](../../../vendor/filament/schemas/src/Components/Concerns/HasFieldWrapper.php) | todo | — | [diverges](#the-component-tier-hasfieldwrapper-twin-is-not-ported) |
| `EntanglesStateWithSingularRelationship` / `CanEntangleWithSingularRelationships` | [`FusedGroup implements CanEntangleWithSingularRelationships`](../../../vendor/filament/schemas/src/Components/FusedGroup.php) | todo | — | [entangles-state-with-singular-relationship.md](../schemas/entangles-state-with-singular-relationship.md) |
| `HasNestedRecursiveValidationRules` arm of the error collection | [the `{$statePath}.*` branch](../../../vendor/filament/schemas/src/Components/FusedGroup.php) | skipped | — | [diverges](#the-error-collection-keeps-the-first-failing-field-and-nothing-else) |

Coverage:
[`FusedGroupTest`](../../../packages/forms/tests/Integration/Components/FusedGroupTest.php)
— the group root as the wrapper's content under one label, the child schema as
its only immediate child, `role`/`id`/`aria-labelledby`, the children on the
plain branch (screen-reader labels, no label column, no content column, wrappers
intact), the gap drop, the required mark from a required child, a child
violation surfacing once at group level with `aria-invalid`, the plain view on
the default **and** slot schemas, `alignEnd()` on the after-label slot, the six
inline slots against the four that stay block, and a slot component and helper
text rendering. The regression pin that a field **outside** a group still gets
the full wrapper lives in the same file.
[`HasFieldWrapperTest`](../../../packages/schemas/tests/Unit/Concerns/HasFieldWrapperTest.php)
pins the seam itself.

## Semantics

### A pre-built `Schema` handed in as a component never meets the plain-view hook

`makeChildSchema()` is what stamps `PLAIN_FIELD_WRAPPER_VIEW` onto a child
schema, and
[`HasChildComponents::getChildSchema()`](../../../packages/schemas/src/Components/Concerns/HasChildComponents.php)
only calls it when it is **building** the schema — for the `default` key too,
which is why a direct child's `getContainer()` carries the flag at all. Hand a
slot an already-built `Schema` instance rather than a component array and that
branch is skipped: the instance is re-parented and configured, never made, so it
keeps whatever wrapper view it already had (normally none) and its fields render
full chrome inside the box. Filament behaves identically — its
`makeChildSchema()` sits in the same position — so this is faithful rather than a
gap, and the fix if it ever bites is the same in both codebases: pass components,
not a `Schema`.

### The corner rules reach through two elements the schema partial nests

Vendor's first/last-corner selectors are `& > :first-child .fi-input-wrp`, a
**descendant** match under a direct child. Our
[`Schema` partial](../../../packages/schemas/templates/partials/Schema/index.html.twig)
puts two elements where Filament puts one — a `gridColumn()` div wrapping a
`.sy-sc-component` div — between `.sy-sc` and the field. The translation is
still literal: `:first-child` still names the grid-column div, `divide-*` still
splits the direct children, and the descendant part still reaches the wrapper
through the extra level. Nothing had to be re-selected, which is the only reason
the stylesheet could be ported unchanged.

## Divergences

### FusedGroup lives in forms, not schemas

Filament ships `Filament\Schemas\Components\FusedGroup`, and that class imports
three **forms** concerns — `CanBeMarkedAsRequired`,
`HasExtraFieldWrapperAttributes` and `HasHelperText` — plus `Forms\Components\Field`
for its error walk. That is the upward import from schemas into forms this port
cannot reproduce: `forms` depends on `schemas`, and the CI split publishes each
package standalone, so a `sylament/schemas` reaching a forms symbol breaks the
moment it is installed alone. The same layering argument moved
[`CanBeMarkedAsRequired` down](../renames.md#namespace-segments); here it moves
the component **up** instead, to the package that already owns the field-wrapper
chrome the group renders. Recorded in
[renames.md](../renames.md#namespace-segments); the class name, its methods and
its behaviour are unchanged, and the CSS class follows the package
(`fi-sc-fused-group` → `sy-fo-fused-group`) rather than keeping a `-sc-` segment
naming a package that no longer holds it.

### The group renders through `FieldWrapper` instead of a second copy of it

Filament's `toEmbeddedHtml()` inlines the whole field-wrapper markup a second
time — label column, content column, error list — because it needs to pass its
own error variables in, and only falls back to rendering the actual wrapper view
when a custom one is set. Ours renders the group root inside
`<twig:SylamentForms:FieldWrapper :field="component">` and stops: our wrapper
already reads everything a group supplies (`getLabel`, `isLabelHidden`, `getId`,
`isMarkedAsRequired`, `getErrorMessages`, `getExtraFieldWrapperAttributes` and
the ten slot schemas) off the `field` it is handed, so the errors arrive through
`getErrorMessages()` like any field's and no second copy is needed. The visible
consequence is that the group's chrome cannot drift from a field's — one
template renders both — and the cost is that the group inherits the wrapper's
error-list shape rather than Filament's message/messages split, which the next
note covers.

### The error collection keeps the first failing field and nothing else

Filament's loop tracks three things per child — a single message, a message
list, and whether HTML messages are allowed — collapses a one-element list back
to the scalar, and checks a second `"{$statePath}.*"` key for a child
implementing `HasNestedRecursiveValidationRules`. None of that has a referent
here: our wrapper
[shows every message for a path in one `<ul>`](field.md#error-display-shows-every-message-for-the-path),
there is no HTML-messages switch, and `HasNestedRecursiveValidationRules` is
unported. So `getErrorMessages()` walks the default-slot child fields in order
and returns the first non-empty message list it finds — Filament's `break`
semantics, minus the shapes our wrapper has no use for. The group therefore
shows one field's errors at a time, exactly as Filament's does.

### The group root carries no `aria-describedby`

Filament points the group at `{$groupId}-error` and puts that id on the error
element it renders itself. Ours renders the error list through `FieldWrapper`,
whose `<ul data-validation-error>` carries no id — no field template emits one —
so a `aria-describedby` here would reference nothing at all, which is worse than
its absence. `aria-invalid` is emitted, and the group's label is still wired
through `aria-labelledby`. Give the wrapper's error list an id and this becomes
a two-line addition on both sides.

### Only the plain wrapper view is honored

Filament's seam is a view **name**: any Blade view a user publishes can be named
through `fieldWrapperView()`, and `toEmbeddedHtml()` resolves it at render time.
Ours stores the string and interprets exactly one value —
`FusedGroup::PLAIN_FIELD_WRAPPER_VIEW` — which forms'
[`FieldWrapper`](../../../packages/forms/templates/components/FieldWrapper/index.html.twig)
branches on. The value is Filament's `filament-forms::plain-field-wrapper` with
the vendor prefix swapped, and it is an opaque token: nothing resolves it as a
template path, so a user template name put there today does nothing rather than
half-working. Schemas stores it and interprets nothing, which is what keeps the
seam legal across the package boundary. A real custom-wrapper capability wants a
Twig component name and a resolution step in `FieldWrapper`; it is wanted for
parity and unbuilt.

### The Component-tier `HasFieldWrapper` twin is not ported

Filament carries the same trait twice: on the container (`Concerns\`), where a
schema declares the view its fields use, and on the component
(`Components\Concerns\`), whose `getFieldWrapperView()` resolves own → container
→ the framework default. Only the container tier ships here, because that is the
tier `FusedGroup` writes to and the only one anything reads: our wrapper asks
`field.getContainer().getFieldWrapperView()` directly, so a per-**component**
override has no consumer to serve yet. Porting the twin is a small addition once
one exists — the resolution order is the whole of it.
