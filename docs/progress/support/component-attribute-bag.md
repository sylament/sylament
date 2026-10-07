# ComponentAttributeBag (support)

`Sylament\Support\Twig\ComponentAttributeBag` — the HTML attribute container behind the `attrs()` / `classes()` / `styles()` Twig functions ([`AttributeExtension`](../../../packages/support/src/Twig/Extension/AttributeExtension.php)), Sylament's replacement for Twig's `html_attr()`.
It ports Filament's `ComponentAttributeBag`, but where Filament subclasses Laravel's `Illuminate\View\ComponentAttributeBag` to reuse Blade's echo-and-escape rendering, Sylament has no Laravel bag to inherit — so it reimplements only the slice its templates need: normalize-on-construct, defaults-first `merge()`, and its own `html_attr()`-faithful renderer.
Filament's three rendering-time helpers are all here, as Twig functions rather than bag methods: `grid()`/`gridColumn()` in `GridExtension`, and `color()` — the last row to settle, with the colour registry — as `colorAttributes()` in `ColorExtension`.

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/src/View/ComponentAttributeBag.php`](../../../vendor/filament/support/src/View/ComponentAttributeBag.php) — Filament's optimized subclass (the overrides + `color`/`grid`/`gridColumn`)
- [`laravel/framework/.../View/ComponentAttributeBag.php`](../../../vendor/laravel/framework/src/Illuminate/View/ComponentAttributeBag.php) — the Laravel base Filament extends and Sylament does not inherit

## Surface

Every point of this component.
_Blocked by_ names what has to land first.
_Detail_ carries one link today — **diverges** → the permanent note in [Divergences](#divergences).
Every row is settled, so there is no Details section; one comes back with the first unfinished row.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Construct + normalize (class → token list, style → declaration list) | [`ComponentAttributeBag::__construct`](../../../vendor/filament/support/src/View/ComponentAttributeBag.php) | done | — | [diverges](#the-bag-is-immutable-and-normalizes-on-construction) |
| `merge()` defaults-first, class/style append | [`ComponentAttributeBag::merge`](../../../vendor/filament/support/src/View/ComponentAttributeBag.php) | done | — | [diverges](#merge-drops-the-escape-and-appendable-value-machinery-and-does-not-deduplicate) |
| `toHtml()` / `toScalarAttributes()` — the `html_attr()` renderer | Laravel base render path (`__toString`) | done | — | [diverges](#the-bag-renders-its-own-html_attr-faithful-output) |
| `class()` / `style()` fluent mutators | [`ComponentAttributeBag::class`](../../../vendor/filament/support/src/View/ComponentAttributeBag.php), [`::style`](../../../vendor/filament/support/src/View/ComponentAttributeBag.php) | skipped | — | [diverges](#fluent-class-and-style-are-replaced-by-the-attrs-classes-and-styles-twig-functions) |
| `color()` | [`ComponentAttributeBag::color`](../../../vendor/filament/support/src/View/ComponentAttributeBag.php) | done | — | [diverges](#color-is-the-colorattributes-twig-function-not-a-bag-method) |
| `grid()` | [`ComponentAttributeBag::grid`](../../../vendor/filament/support/src/View/ComponentAttributeBag.php) | done | — | [diverges](#grid-and-gridcolumn-are-twig-functions-not-bag-methods) |
| `gridColumn()` | [`ComponentAttributeBag::gridColumn`](../../../vendor/filament/support/src/View/ComponentAttributeBag.php) | done | — | [diverges](#grid-and-gridcolumn-are-twig-functions-not-bag-methods) |
| Laravel base API (`get`/`has`/`only`/`except`/`filter`/`ArrayAccess`/…) + macros (`__call`/`hasMacro`) | [Laravel base](../../../vendor/laravel/framework/src/Illuminate/View/ComponentAttributeBag.php) | skipped | — | [diverges](#the-laravel-base-bag-and-macros-are-not-inherited) |

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold.

### `color()` is the `colorAttributes()` Twig function, not a bag method

Filament's `color()` mutates the bag — appending `fi-*` classes for a named palette, or per-shade `style` declarations for an array one — and reaches the registry through the `FilamentColor` facade.
Sylament has no fluent mutators on the bag and no facades, so the same behaviour is [`ColorExtension::colorAttributes(policy, color)`](../../../packages/support/src/Twig/Extension/ColorExtension.php), which **returns** `{class: [...], style: [...]}` for a template to spread rather than mutating anything.
Same two arms, same `ColorManager` calls (`getComponentClasses()` for a name, `getComponentCustomStyles()` for an array), same empty result for a blank colour.

Two knock-on differences the signature does not show.
The policy arrives as a **class-string** rather than an instance, because a Twig template cannot call a static factory — which is why the outlined button is [a subclass](color-policies.md#the-outlined-button-is-a-subclass-not-a-constructor-flag) rather than constructor state.
And because the styles come back as a value, the template has to merge them into `attributes` itself, which is where the [`defaults()` replaces `style`](#fluent-class-and-style-are-replaced-by-the-attrs-classes-and-styles-twig-functions) trap below bites: `attributes.without('style').defaults({style: styles(ours, attributes.all.style ?? null)})` is the shape every palette-emitting template uses.

### `grid()` and `gridColumn()` are Twig functions, not bag methods

Filament reaches these through `ComponentAttributeBag::macro()` registered in `SupportServiceProvider::packageBooted()`, so a Blade view calls `$attributes->grid(...)`.
Macros are a rejected pattern here (see [Macroable is not ported](component.md#macroable-is-not-ported)), and a Twig template has no `$attributes` receiver to call a method on — so both landed as `grid()` / `gridColumn()` Twig functions on [`GridExtension`](../../../packages/support/src/Twig/Extension/GridExtension.php), returning the same `['class' => …, 'style' => …]` shape for `attrs()` to spread.
The behaviour is a faithful port including the `!`→`n` / `@`→`c` breakpoint sanitization; the receiver is what changed.
Their one behavioural divergence — also filtering blank breakpoint *keys* — is recorded in [support-bundle.md](support-bundle.md#the-ported-grid--gridcolumn-also-drop-blank-breakpoint-keys).

### The Laravel base bag and macros are not inherited

Filament's `ComponentAttributeBag extends Illuminate\View\ComponentAttributeBag`, inheriting the whole Laravel attribute API (`get`, `has`, `only`, `except`, `filter`, `whereStartsWith`, `ArrayAccess`, `IteratorAggregate`, `wire:` handling, `__toString`) plus the Macroable `__call`/`hasMacro` extension point.
Sylament has no Laravel to inherit from, and reimplements only the operations its own templates exercise.
The macro path is dropped for the same reason it is on [`Component`](component.md#divergences) — no runtime method injection PHPStan and IDEs cannot see; the extension points are the Twig functions and subclassing.
The rest of the Laravel surface is re-added method-by-method if a template turns out to need it, not ported wholesale.

_See also: the global [rename table](../renames.md) — `View\` → `Twig\` (Laravel's `ComponentAttributeBag` living in `Sylament\Support\Twig\`) is a systematic translation recorded there, not a per-item divergence._

### The bag is immutable and normalizes on construction

Filament's bag stores raw attribute values and normalizes class/style lazily inside `class()`/`style()`/`merge()`.
Sylament normalizes once in the constructor: `class` becomes a flat token list and `style` a declaration list (via the public `toTokenList()`/`toDeclarationList()` and private `splitStyleDeclarations()`), and the instance is never mutated in place — `merge()` returns a new bag.
This keeps one canonical shape (`getAttributes()` always returns lists for class/style) that both the renderer and the `classes()` Twig function read, and lets the style splitter respect semicolons inside `url(...)`/quotes that a naive `explode(';')` would corrupt.

### `merge()` drops the escape and appendable-value machinery, and does not deduplicate

Filament's `merge($attributeDefaults, $escape = true)` eagerly escapes default values with `e()`, understands `AppendableAttributeValue`, and de-duplicates the combined class/style string with `array_unique`.
Sylament's `merge()` takes only the defaults array: escaping is deferred to render time (`toHtml()`), there is no `AppendableAttributeValue` (the Twig `attrs()` merge covers the append case), and class/style tokens are concatenated (`[...$default, ...$value]`) **without** `array_unique` — a duplicate token survives to output.
The defaults-first, existing-wins-for-scalar-keys ordering is preserved.

Dropping the escape parameter costs nothing and settles a question upstream: **there is no per-source escaping policy here, so two attribute-passthrough setters cannot differ by escaping.**
Filament ships two that look as though they do — [`HasExtraInputAttributes`](../forms/has-extra-input-attributes.md) and [`Support\Concerns\HasExtraAlpineAttributes`](../../../vendor/filament/support/src/Concerns/HasExtraAlpineAttributes.php), whose `extraAlpineAttributes()` carries `x-on:*` / `x-bind:*` handlers — but both merge with `escape: false`, so even there the traits are identical code under two names, separated only by where each composer merges them (Filament's `TextInput` slots the Alpine bag between the extras and the field's own attributes, which is a precedence choice, not a capability).
Against this bag the last difference goes too: a ported `extraAlpineAttributes()` landing on the same element would accumulate through the same `merge()` and render through the same `toHtml()`, and so emit **byte-identical** output to `extraInputAttributes()`.
That is the argument that settled the trait's row — the port would be shipping a synonym — and it is written up as a divergence of its own [below](#extra-alpine-attributes-are-not-ported).

### Extra Alpine attributes are not ported

Filament composes [`HasExtraAlpineAttributes`](../../../vendor/filament/support/src/Concerns/HasExtraAlpineAttributes.php) onto layout components and fields alike, letting a caller hang arbitrary `x-*` attributes on the rendered element.
There is no Alpine here and the client wiring a component needs is its Stimulus controller, so the trait is **skipped**; `extraAttributes()` still passes anything a caller wants onto the same element.

The second reason is this bag's, and it is why the decision is filed here rather than on any one component: against it the trait is a [synonym](#merge-drops-the-escape-and-appendable-value-machinery-and-does-not-deduplicate).
It merges with `escape: false` exactly as [`HasExtraInputAttributes`](../forms/has-extra-input-attributes.md) does, so a ported `extraAlpineAttributes()` landing on the same element would accumulate through the same `merge()` and render through the same `toHtml()`, byte for byte.
Porting it would ship a second name for one behaviour, and that holds whether or not the toolkit ever grows an Alpine-shaped surface to justify the name.

Every consumer's row links this heading, support's own [`HasExtraAlpineAttributes` row](README.md) included.
Four of them link a divergence of their own beside it, each written component by component before the decision had one home: [`Section`](../schemas/section.md), [`Tabs`](../schemas/tabs.md), [`Wizard`](../schemas/wizard.md) and the [date pickers](../forms/date-time-picker.md), where the trait is one half of a wider Alpine-and-published-view gap.
The fields settled since — [`TextInput`](../forms/text-input.md), [`Textarea`](../forms/textarea.md), [`Toggle`](../forms/toggle.md), [`KeyValue`](../forms/key-value.md) and [`TagsInput`](../forms/tags-input.md) — carry this one alone.
Nothing reverses it.

### The bag renders its own `html_attr()`-faithful output

Filament never renders attributes from this class — Blade echoes the bag and Laravel's base `__toString` handles escaping.
With no Blade echo path, Sylament adds `toScalarAttributes()` + `toHtml()` (no Filament-class counterpart) that reproduce Twig's `html_attr()` semantics precisely: `BackedEnum` → backing value, `aria-*` booleans → literal `"true"`/`"false"`, `data-*` `true` → `"true"` and non-scalar `data-*` → JSON, class joined on spaces / style on `; `, generic iterables joined on spaces (dropping `null`/`false`, keeping a lone `true` as an empty value), and hostile attribute names entity-escaped with the `html_attr_relaxed` strategy so a key cannot break out to inject a handler.

Two escaping details the renderer owns, both with no Filament counterpart:

- **Names.**
  `escapeAttributeName()` reimplements Twig's `html_attr_relaxed` escaper — every character outside the attribute-name-safe set becomes an inert entity, legitimate names pass through byte-identical, and a name that is not valid UTF-8 (where `preg_replace_callback`'s `/u` pattern returns `null`) falls back to escaping byte by byte rather than emitting the name raw.
  Inside that callback the ASCII test is `ord($char[0]) < 0x80` — deliberately a **byte** check on the lead byte, never a string-length helper, because PHP-CS-Fixer's `mb_str_functions` rule would rewrite `strlen()` into `mb_strlen()` and silently reclassify multi-byte characters.
- **Values.**
  `htmlspecialchars()` runs with `ENT_QUOTES | ENT_SUBSTITUTE`, so invalid UTF-8 in a value is substituted rather than collapsing the whole value to an empty string the way the default flags would.

_See also: the [rename table](../renames.md) — Blade→Twig (`view()` → `template()`) and `Htmlable`→`Markup` are the systematic escaping-contract translation this renderer implements._

### Fluent `class()` and `style()` are replaced by the `attrs()`, `classes()` and `styles()` Twig functions

Filament templates call `$attributes->class([...])` / `->style([...])` to append tokens fluently.
Sylament exposes no such mutators on the bag; templates compose attributes through [`AttributeExtension`](../../../packages/support/src/Twig/Extension/AttributeExtension.php) instead — `attrs(...sources)` merges source maps left-to-right (class/style appending, later scalars winning), and `classes(...)`/`styles(...)` build a joined token and declaration string for the one sink (ux-twig-component's `attributes.defaults()`) that string-casts its value.
All three delegate to the same `ComponentAttributeBag` normalization, so there is one class-token and one declaration-list implementation rather than a fluent PHP path and a separate Twig path.

Three contracts of those functions that the PHP signatures cannot state:

- **`attrs()` skips falsy sources and throws on any other string.**
  `null`, `false`, and `''` are silently skipped so a template can pass the implicit else of a ternary (`cond ? {…}`); every other string source raises, because a bare string is never a valid attribute map and swallowing it would drop attributes without a trace.
  Class values are accepted directly as strings, conditional maps, or nested lists — no `classes()` wrapper is needed inside `attrs()`.
- **`classes()` returns a string, not a token-list object.**
  Its one sink string-casts whatever it gets, and PHP 8.5 deprecates wrapping an object in an `ArrayIterator` as an attribute value, so returning a joined string keeps that path clean.
  `attrs()` re-splits such a string on `class`, so the same value is still usable there.
- **`styles()` exists because `attributes.defaults()` appends `class` but replaces `style`.** ux-twig-component's bag concatenates only `class`, `data-controller` and `data-action` ([`ComponentAttributes::defaults()`](../../../vendor/symfony/ux-twig-component/src/ComponentAttributes.php)), so a caller-supplied `style` would overwrite a component's own — silently wiping the inline colour palette while leaving `sy-color` on the element.
  The templates that emit a palette therefore call `attributes.without('style').defaults({style: styles(ours, attributes.all.style ?? null)})`: ours first, the caller's second, so the caller still wins per property the way CSS ordering says it should.
