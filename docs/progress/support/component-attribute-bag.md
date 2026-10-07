---
description: The `ComponentAttributeBag` HTML attribute container and the `attrs()`, `classes()`, `styles()`, `grid()` and `gridColumn()` Twig functions built on it.
ports:
  - vendor/filament/support/src/View/ComponentAttributeBag.php
  - vendor/filament/support/resources/css/components/grid.css
owns:
  - packages/support/assets/src/styles/components/grid.css
  - packages/support/src/Twig/ComponentAttributeBag.php
  - packages/support/src/Twig/Extension/AttributeExtension.php
  - packages/support/src/Twig/Extension/GridExtension.php
---
# ComponentAttributeBag (support)

`Sylament\Support\Twig\ComponentAttributeBag` is the HTML attribute container behind the `attrs()` / `classes()` / `styles()` Twig functions ([`AttributeExtension`](../../../packages/support/src/Twig/Extension/AttributeExtension.php)), Sylament's replacement for Twig's `html_attr()`.
It ports Filament's `ComponentAttributeBag`, but where Filament subclasses Laravel's `Illuminate\View\ComponentAttributeBag` to reuse Blade's echo-and-escape rendering, Sylament has no Laravel bag to inherit, so it reimplements only the slice its templates need: normalize-on-construct, defaults-first `merge()`, and its own `html_attr()`-faithful renderer.
Filament's three rendering-time helpers are Twig functions here rather than bag methods: `grid()` and `gridColumn()` on [`GridExtension`](../../../packages/support/src/Twig/Extension/GridExtension.php), whose classes [`grid.css`](../../../packages/support/assets/src/styles/components/grid.css) styles, and `color()` as `colorAttributes()` on [`ColorExtension`](color-manager.md).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Construct + normalize (class → token list, style → declaration list)](../../../packages/support/src/Twig/ComponentAttributeBag.php) | [`ComponentAttributeBag::__construct`](../../../vendor/filament/support/src/View/ComponentAttributeBag.php) | done | — | [diverges](#the-bag-is-immutable-and-normalizes-on-construction) |
| [`merge()` defaults-first, class/style append](../../../packages/support/src/Twig/ComponentAttributeBag.php) | [`ComponentAttributeBag::merge`](../../../vendor/filament/support/src/View/ComponentAttributeBag.php) | done | — | [diverges](#merge-drops-the-escape-and-appendable-value-machinery-and-does-not-deduplicate) |
| [`toHtml()` / `toScalarAttributes()`: the `html_attr()` renderer](../../../packages/support/src/Twig/ComponentAttributeBag.php) | Laravel base render path (`__toString`) | done | — | [diverges](#the-bag-renders-its-own-html_attr-faithful-output) |
| [`attrs()` / `classes()` / `styles()` Twig functions](../../../packages/support/src/Twig/Extension/AttributeExtension.php) | [`ComponentAttributeBag::class`](../../../vendor/filament/support/src/View/ComponentAttributeBag.php), [`::style`](../../../vendor/filament/support/src/View/ComponentAttributeBag.php) | done | — | [diverges](#fluent-class-and-style-are-replaced-by-the-attrs-classes-and-styles-twig-functions) |
| [`colorAttributes()`](../../../packages/support/src/Twig/Extension/ColorExtension.php) | [`ComponentAttributeBag::color`](../../../vendor/filament/support/src/View/ComponentAttributeBag.php) | done | — | [diverges](#color-is-the-colorattributes-twig-function-not-a-bag-method) |
| [`grid()`](../../../packages/support/src/Twig/Extension/GridExtension.php) | [`ComponentAttributeBag::grid`](../../../vendor/filament/support/src/View/ComponentAttributeBag.php) | done | — | [diverges](#grid-and-gridcolumn-are-twig-functions-not-bag-methods) · [diverges](#the-ported-grid--gridcolumn-also-drop-blank-breakpoint-keys) · [diverges](#the-sy-grid-cols-marker-class-is-emitted-unconditionally) |
| [`gridColumn()`](../../../packages/support/src/Twig/Extension/GridExtension.php) | [`ComponentAttributeBag::gridColumn`](../../../vendor/filament/support/src/View/ComponentAttributeBag.php) | done | — | [diverges](#grid-and-gridcolumn-are-twig-functions-not-bag-methods) · [diverges](#the-ported-grid--gridcolumn-also-drop-blank-breakpoint-keys) |
| [`grid.css`](../../../packages/support/assets/src/styles/components/grid.css) | [`components/grid.css`](../../../vendor/filament/support/resources/css/components/grid.css) | done | — | — |
| Laravel base API (`get`/`has`/`only`/`except`/`filter`/`ArrayAccess`/…) + macros (`__call`/`hasMacro`) | [Laravel base](../../../vendor/laravel/framework/src/Illuminate/View/ComponentAttributeBag.php) | skipped | — | [diverges](#the-laravel-base-bag-and-macros-are-not-inherited) |

## Divergences

### `color()` is the `colorAttributes()` Twig function, not a bag method

Filament's `color()` mutates the bag, appending `fi-*` classes for a named palette or per-shade `style` declarations for an array one, and reaches the registry through the `FilamentColor` facade.
Sylament has no fluent mutators on the bag and no facades, so the same behaviour is [`ColorExtension::colorAttributes(policy, color)`](../../../packages/support/src/Twig/Extension/ColorExtension.php), which returns `{class: [...], style: [...]}` for a template to spread rather than mutating anything.
It has the same two arms, the same `ColorManager` calls (`getComponentClasses()` for a name, `getComponentCustomStyles()` for an array) and the same empty result for a blank colour.

Two knock-on differences the signature does not show.
The policy arrives as a class-string rather than an instance, because a Twig template cannot call a static factory, which is why the outlined button is [a subclass](color-policies.md#the-outlined-button-is-a-subclass-not-a-constructor-flag) rather than constructor state.
And because the styles come back as a value, the template merges them into `attributes` itself, which is where the [`defaults()` replaces `style`](#fluent-class-and-style-are-replaced-by-the-attrs-classes-and-styles-twig-functions) trap below bites: `attributes.without('style').defaults({style: styles(ours, attributes.all.style ?? null)})` is the shape every palette-emitting template uses.

### `grid()` and `gridColumn()` are Twig functions, not bag methods

Filament defines `grid()` and `gridColumn()` as methods on its own bag and mirrors them as macros on Laravel's base `ComponentAttributeBag` in `SupportServiceProvider::packageBooted()`, so any Blade view calls `$attributes->grid(...)`.
Macros are a rejected pattern here (see [Macroable is not ported](component.md#macroable-is-not-ported)), and a Twig template has no `$attributes` receiver to call a method on, so both are `grid()` / `gridColumn()` Twig functions on [`GridExtension`](../../../packages/support/src/Twig/Extension/GridExtension.php), returning the same `['class' => …, 'style' => …]` shape for `attrs()` to spread.
The behaviour follows Filament's, including the `!` → `n` / `@` → `c` breakpoint sanitization; the receiver is what changes.

### The ported `grid()` / `gridColumn()` also drop blank breakpoint *keys*

Filament's two methods filter their breakpoint maps by value only (`array_filter($columns)`).
The [`GridExtension`](../../../packages/support/src/Twig/Extension/GridExtension.php) port adds a second pass over the keys (`array_filter(…, filled(...), ARRAY_FILTER_USE_KEY)`), because every non-`default` key is interpolated straight into a class name, so a blank key would emit a garbage `<whitespace>:sy-grid-*` class.
Filament interpolates the same way and has no guard; the port hardens the input rather than reproducing that.

### The `sy-grid-cols` marker class is emitted unconditionally

Filament's `grid()` emits the base grid-cols class only when the `default` breakpoint resolves to more than one column (`($columns['default'] > 1) ? 'fi-grid-cols' : ''`).
Sylament's [`grid()`](../../../packages/support/src/Twig/Extension/GridExtension.php) Twig function always appends `sy-grid-cols` for the `default` breakpoint.
`grid.css` styles only the breakpoint variants, but forms' [`fused-group.css`](../../../packages/forms/assets/src/styles/components/fused-group.css), like Filament's schemas `fused-group.css`, styles the bare marker with `divide-x divide-y-0` and horizontal corner rounding.
A fused group whose child schema has one default column therefore gets the side-by-side divider treatment where Filament keeps the stacked `divide-y` one.

### The Laravel base bag and macros are not inherited

Filament's `ComponentAttributeBag extends Illuminate\View\ComponentAttributeBag`, inheriting the whole Laravel attribute API (`get`, `has`, `only`, `except`, `filter`, `whereStartsWith`, `ArrayAccess`, `IteratorAggregate`, `wire:` handling, `__toString`) plus the Macroable `__call`/`hasMacro` extension point.
Sylament has no Laravel to inherit from, and reimplements only the operations its own templates exercise.
The macro path is dropped for the reason it is on [`Component`](component.md#macroable-is-not-ported): no runtime method injection PHPStan and IDEs cannot see; the extension points are the Twig functions and subclassing.
The rest of the Laravel surface is added method by method if a template needs it, not ported wholesale.
`View\` → `Twig\`, which puts the bag in `Sylament\Support\Twig\`, is a [namespace translation](../translations.md#namespace-segments).

### The bag is immutable and normalizes on construction

Filament's bag stores raw attribute values and normalizes class and style lazily inside `class()`/`style()`/`merge()`.
Sylament normalizes once in the constructor: `class` becomes a flat token list and `style` a declaration list (through the public `toTokenList()`/`toDeclarationList()` and the private `splitStyleDeclarations()`), and the instance is never mutated in place: `merge()` returns a new bag.
This keeps one canonical shape (`getAttributes()` always returns lists for class and style) that both the renderer and the `classes()` Twig function read, and lets the style splitter respect semicolons inside `url(...)` and quotes that a naive `explode(';')` would corrupt.

### `merge()` drops the escape and appendable-value machinery, and does not deduplicate

Filament's `merge($attributeDefaults, $escape = true)` eagerly escapes default values with `e()`, understands `AppendableAttributeValue`, and de-duplicates the combined class and style string with `array_unique`.
Sylament's `merge()` takes only the defaults array: escaping is deferred to render time (`toHtml()`), there is no `AppendableAttributeValue` (the `attrs()` merge covers the append case), and class and style tokens are concatenated (`[...$default, ...$value]`) without `array_unique`, so a duplicate token survives to output.
The defaults-first, existing-wins-for-scalar-keys ordering is preserved.

Dropping the escape parameter settles a question upstream: there is no per-source escaping policy here, so two attribute-passthrough setters cannot differ by escaping.
Filament ships two that look as though they do, [`HasExtraInputAttributes`](../forms/has-extra-input-attributes.md) and [`Support\Concerns\HasExtraAlpineAttributes`](../../../vendor/filament/support/src/Concerns/HasExtraAlpineAttributes.php), whose `extraAlpineAttributes()` carries `x-on:*` / `x-bind:*` handlers, but both merge with `escape: false`, so the traits are identical code under two names, separated only by where each composer merges them (Filament's `TextInput` slots the Alpine bag between the extras and the field's own attributes, a precedence choice, not a capability).
Against this bag the last difference goes too: a ported `extraAlpineAttributes()` landing on the same element would accumulate through the same `merge()` and render through the same `toHtml()`, and so emit byte-identical output to `extraInputAttributes()`.
That argument settles the trait's row, written up [below](#extra-alpine-attributes-are-not-ported).

### Extra Alpine attributes are not ported

Filament composes [`HasExtraAlpineAttributes`](../../../vendor/filament/support/src/Concerns/HasExtraAlpineAttributes.php) onto layout components and fields alike, letting a caller hang arbitrary `x-*` attributes on the rendered element.
There is no Alpine here, and the client wiring a component needs is its Stimulus controller, so the trait is skipped; `extraAttributes()` still passes anything a caller wants onto the same element.

The second reason is this bag's, and it is why the decision lives here rather than on any one component: against it the trait is a [synonym](#merge-drops-the-escape-and-appendable-value-machinery-and-does-not-deduplicate).
It merges with `escape: false` as [`HasExtraInputAttributes`](../forms/has-extra-input-attributes.md) does, so a ported `extraAlpineAttributes()` landing on the same element would accumulate through the same `merge()` and render through the same `toHtml()`, byte for byte.
Porting it would ship a second name for one behaviour, whether or not the toolkit ever grows an Alpine-shaped surface to justify the name.
Every consumer's skipped row links this heading; schemas' [`Section`](../schemas/section.md), [`Tabs`](../schemas/tabs.md) and [`Wizard`](../schemas/wizard.md) also link a divergence of their own, where the trait is one part of a wider Alpine-only surface.
Nothing reverses it.

### The bag renders its own `html_attr()`-faithful output

Filament never renders attributes from this class: Blade echoes the bag and Laravel's base `__toString` handles escaping.
With no Blade echo path, Sylament adds `toScalarAttributes()` and `toHtml()` (no Filament-class counterpart), which reproduce Twig's `html_attr()` semantics: `BackedEnum` → backing value, `aria-*` booleans → literal `"true"`/`"false"`, `data-*` `true` → `"true"` and non-scalar `data-*` → JSON, class joined on spaces and style on `; `, generic iterables joined on spaces (dropping `null`/`false`, keeping a lone `true` as an empty value), and hostile attribute names entity-escaped with the `html_attr_relaxed` strategy so a key cannot break out to inject a handler.

Two escaping details the renderer owns, both with no Filament counterpart:

- **Names.**
  `escapeAttributeName()` reimplements Twig's `html_attr_relaxed` escaper: every character outside the attribute-name-safe set becomes an inert entity, legitimate names pass through byte-identical, and a name that is not valid UTF-8 (where `preg_replace_callback`'s `/u` pattern returns `null`) falls back to escaping byte by byte rather than emitting the name raw.
  Inside that callback the ASCII test is `ord($char[0])` below `0x80`, deliberately a byte check on the lead byte and never a string-length helper, because PHP-CS-Fixer's `mb_str_functions` rule would rewrite `strlen()` into `mb_strlen()` and silently reclassify multi-byte characters.
- **Values.**
  `htmlspecialchars()` runs with `ENT_QUOTES | ENT_SUBSTITUTE`, so invalid UTF-8 in a value is substituted rather than collapsing the whole value to an empty string the way the default flags would.

`Htmlable` → `Twig\Markup` is the systematic escaping-contract [translation](../translations.md#idiom) this renderer implements.

### Fluent `class()` and `style()` are replaced by the `attrs()`, `classes()` and `styles()` Twig functions

Filament templates call `$attributes->class([...])` / `->style([...])` to append tokens fluently.
Sylament exposes no such mutators on the bag; templates compose attributes through [`AttributeExtension`](../../../packages/support/src/Twig/Extension/AttributeExtension.php) instead: `attrs(...sources)` merges source maps left to right (class and style appending, later scalars winning), and `classes(...)`/`styles(...)` build a joined token and declaration string for the one sink (ux-twig-component's `attributes.defaults()`) that string-casts its value.
All three delegate to the same `ComponentAttributeBag` normalization, so there is one class-token and one declaration-list implementation rather than a fluent PHP path and a separate Twig path.

Three contracts of those functions that the PHP signatures cannot state:

- **`attrs()` skips falsy sources and throws on any other string.**
  `null`, `false` and `''` are skipped so a template can pass the implicit else of a ternary (`cond ? {…}`); every other string source raises, because a bare string is never a valid attribute map and swallowing it would drop attributes without a trace.
  Class values are accepted directly as strings, conditional maps or nested lists, so no `classes()` wrapper is needed inside `attrs()`.
- **`classes()` returns a string, not a token-list object.**
  Its one sink string-casts whatever it gets, and PHP 8.5 deprecates wrapping an object in an `ArrayIterator` as an attribute value, so returning a joined string keeps that path clean.
  `attrs()` re-splits such a string on `class`, so the same value is still usable there.
- **`styles()` exists because `attributes.defaults()` appends `class` but replaces `style`.**
  ux-twig-component's bag concatenates only `class`, `data-controller` and `data-action` ([`ComponentAttributes::defaults()`](../../../vendor/symfony/ux-twig-component/src/ComponentAttributes.php)), so a caller-supplied `style` would overwrite a component's own, wiping the inline colour palette while leaving `sy-color` on the element.
  The templates that emit a palette therefore call `attributes.without('style').defaults({style: styles(ours, attributes.all.style ?? null)})`: theirs first, the caller's second, so the caller still wins per property the way CSS ordering says it should.
