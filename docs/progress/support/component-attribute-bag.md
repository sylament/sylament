# ComponentAttributeBag (support)

`Sylament\Support\Twig\ComponentAttributeBag` — the HTML attribute container
behind the `attrs()` / `classes()` Twig functions
([`AttributeExtension`](../../../packages/support/src/Twig/Extension/AttributeExtension.php)),
Sylament's replacement for Twig's `html_attr()`. It ports Filament's
`ComponentAttributeBag`, but where Filament subclasses Laravel's
`Illuminate\View\ComponentAttributeBag` to reuse Blade's echo-and-escape
rendering, Sylament has no Laravel bag to inherit — so it reimplements only the
slice its templates need: normalize-on-construct, defaults-first `merge()`, and
its own `html_attr()`-faithful renderer. Rendering-time helpers that depend on
unported systems (`color()`, `grid()`, `gridColumn()`) are not yet here, so the
item is partial.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/src/View/ComponentAttributeBag.php`](../../../vendor/filament/support/src/View/ComponentAttributeBag.php)
  — Filament's optimized subclass (the overrides + `color`/`grid`/`gridColumn`)
- [`laravel/framework/.../View/ComponentAttributeBag.php`](../../../vendor/laravel/framework/src/Illuminate/View/ComponentAttributeBag.php)
  — the Laravel base Filament extends and Sylament does not inherit

## Surface

Every point of this component. _Blocked by_ names what has to land first.
_Detail_ carries only two links, either or both: **detail** → the write-up in
[Details](#details), deleted once the row is **done**; **diverges** → the
permanent note in [Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Construct + normalize (class → token list, style → declaration list) | [`ComponentAttributeBag::__construct`](../../../vendor/filament/support/src/View/ComponentAttributeBag.php) | done | — | [diverges](#the-bag-is-immutable-and-normalizes-on-construction) |
| `merge()` defaults-first, class/style append | [`ComponentAttributeBag::merge`](../../../vendor/filament/support/src/View/ComponentAttributeBag.php) | done | — | [diverges](#merge-drops-the-escape-and-appendable-value-machinery-and-does-not-deduplicate) |
| `toHtml()` / `toScalarAttributes()` — the `html_attr()` renderer | Laravel base render path (`__toString`) | done | — | [diverges](#the-bag-renders-its-own-html_attr-faithful-output) |
| `class()` / `style()` fluent mutators | [`ComponentAttributeBag::class`](../../../vendor/filament/support/src/View/ComponentAttributeBag.php), [`::style`](../../../vendor/filament/support/src/View/ComponentAttributeBag.php) | skipped | — | [diverges](#fluent-class-and-style-are-replaced-by-the-attrs-and-classes-twig-functions) |
| `color()` | [`ComponentAttributeBag::color`](../../../vendor/filament/support/src/View/ComponentAttributeBag.php) | blocked | ColorManager / FilamentColor facade | [detail](#colorgridgridcolumn-are-not-ported) |
| `grid()` | [`ComponentAttributeBag::grid`](../../../vendor/filament/support/src/View/ComponentAttributeBag.php) | todo | — | [detail](#colorgridgridcolumn-are-not-ported) |
| `gridColumn()` | [`ComponentAttributeBag::gridColumn`](../../../vendor/filament/support/src/View/ComponentAttributeBag.php) | todo | — | [detail](#colorgridgridcolumn-are-not-ported) |
| Laravel base API (`get`/`has`/`only`/`except`/`filter`/`ArrayAccess`/…) + macros (`__call`/`hasMacro`) | [Laravel base](../../../vendor/laravel/framework/src/Illuminate/View/ComponentAttributeBag.php) | skipped | — | [diverges](#the-laravel-base-bag-and-macros-are-not-inherited) |

## Details

Specifics for rows that are not finished — what is already in, what is left, and
what the next step is.

### `color()`/`grid()`/`gridColumn()` are not ported

The three rendering helpers Filament macro-registers onto the bag are absent.

- **`color()`** turns a color name into `fi-*` classes / custom-property styles
  via the `FilamentColor` facade and `HasColor` component contract. Both the
  color manager and the facade are still `todo` in the [package
  README](README.md), so this is **blocked** on them — porting `color()` before
  its color source exists would have nothing to resolve against and nothing to
  test.
- **`grid()`** and **`gridColumn()`** depend only on
  [`GridDirection`](../../../packages/support/src/Enums/GridDirection.php) (ported)
  plus `sy-grid*` CSS classes and `--cols-*` / `--col-*` custom properties. They
  are **todo** — unblocked and not started, unbuilt because no consumer renders
  a grid yet (the schemas `Grid`/`Section` column layout that will call them is
  unported). Next step: port both alongside the first grid-rendering component,
  translating `fi-grid*` → `sy-sc-grid*` and keeping the breakpoint
  `!`→`n` / `@`→`c` custom-property naming.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold.

### The Laravel base bag and macros are not inherited

Filament's `ComponentAttributeBag extends Illuminate\View\ComponentAttributeBag`,
inheriting the whole Laravel attribute API (`get`, `has`, `only`, `except`,
`filter`, `whereStartsWith`, `ArrayAccess`, `IteratorAggregate`, `wire:`
handling, `__toString`) plus the Macroable `__call`/`hasMacro` extension point.
Sylament has no Laravel to inherit from, and reimplements only the operations its
own templates exercise. The macro path is dropped for the same reason it is on
[`Component`](component.md#divergences) — no runtime method injection PHPStan and
IDEs cannot see; the extension points are the Twig functions and subclassing. The
rest of the Laravel surface is re-added method-by-method if a template turns out
to need it, not ported wholesale.

_See also: the global [rename table](../renames.md) — `View\` → `Twig\`
(Laravel's `ComponentAttributeBag` living in `Sylament\Support\Twig\`) is a
systematic translation recorded there, not a per-item divergence._

### The bag is immutable and normalizes on construction

Filament's bag stores raw attribute values and normalizes class/style lazily
inside `class()`/`style()`/`merge()`. Sylament normalizes once in the constructor:
`class` becomes a flat token list and `style` a declaration list (via the public
`toTokenList()` and private `toDeclarationList()`/`splitStyleDeclarations()`), and
the instance is never mutated in place — `merge()` returns a new bag. This keeps
one canonical shape (`getAttributes()` always returns lists for class/style) that
both the renderer and the `classes()` Twig function read, and lets the
style splitter respect semicolons inside `url(...)`/quotes that a naive
`explode(';')` would corrupt.

### `merge()` drops the escape and appendable-value machinery, and does not deduplicate

Filament's `merge($attributeDefaults, $escape = true)` eagerly escapes default
values with `e()`, understands `AppendableAttributeValue`, and de-duplicates the
combined class/style string with `array_unique`. Sylament's `merge()` takes only
the defaults array: escaping is deferred to render time (`toHtml()`), there is no
`AppendableAttributeValue` (the Twig `attrs()` merge covers the append case), and
class/style tokens are concatenated (`[...$default, ...$value]`) **without**
`array_unique` — a duplicate token survives to output. The defaults-first,
existing-wins-for-scalar-keys ordering is preserved.

### The bag renders its own `html_attr()`-faithful output

Filament never renders attributes from this class — Blade echoes the bag and
Laravel's base `__toString` handles escaping. With no Blade echo path, Sylament
adds `toScalarAttributes()` + `toHtml()` (no Filament-class counterpart) that
reproduce Twig's `html_attr()` semantics precisely: `BackedEnum` → backing value,
`aria-*` booleans → literal `"true"`/`"false"`, `data-*` `true` → `"true"` and
non-scalar `data-*` → JSON, class joined on spaces / style on `; `, generic
iterables joined on spaces (dropping `null`/`false`, keeping a lone `true` as an
empty value), and hostile attribute names entity-escaped with the
`html_attr_relaxed` strategy so a key cannot break out to inject a handler.

Two escaping details the renderer owns, both with no Filament counterpart:

- **Names.** `escapeAttributeName()` reimplements Twig's `html_attr_relaxed`
  escaper — every character outside the attribute-name-safe set becomes an inert
  entity, legitimate names pass through byte-identical, and a name that is not
  valid UTF-8 (where `preg_replace_callback`'s `/u` pattern returns `null`) falls
  back to escaping byte by byte rather than emitting the name raw. Inside that
  callback the ASCII test is `ord($char[0]) < 0x80` — deliberately a **byte**
  check on the lead byte, never a string-length helper, because PHP-CS-Fixer's
  `mb_str_functions` rule would rewrite `strlen()` into `mb_strlen()` and silently
  reclassify multi-byte characters.
- **Values.** `htmlspecialchars()` runs with `ENT_QUOTES | ENT_SUBSTITUTE`, so
  invalid UTF-8 in a value is substituted rather than collapsing the whole value
  to an empty string the way the default flags would.

_See also: the [rename table](../renames.md) — Blade→Twig (`view()` →
`template()`) and `Htmlable`→`Markup` are the systematic escaping-contract
translation this renderer implements._

### Fluent `class()` and `style()` are replaced by the `attrs()` and `classes()` Twig functions

Filament templates call `$attributes->class([...])` / `->style([...])` to append
tokens fluently. Sylament exposes no such mutators on the bag; templates compose
attributes through
[`AttributeExtension`](../../../packages/support/src/Twig/Extension/AttributeExtension.php)
instead — `attrs(...sources)` merges source maps left-to-right (class/style
appending, later scalars winning) and `classes(...)` builds a joined token string
for the one sink (ux-twig-component's `attributes.defaults()`) that string-casts
its value. Both delegate to the same `ComponentAttributeBag` normalization, so
there is one class-token implementation rather than a fluent PHP path and a
separate Twig path.

Two contracts of those functions that the PHP signatures cannot state:

- **`attrs()` skips falsy sources and throws on any other string.** `null`,
  `false`, and `''` are silently skipped so a template can pass the implicit else
  of a ternary (`cond ? {…}`); every other string source raises, because a bare
  string is never a valid attribute map and swallowing it would drop attributes
  without a trace. Class values are accepted directly as strings, conditional
  maps, or nested lists — no `classes()` wrapper is needed inside `attrs()`.
- **`classes()` returns a string, not a token-list object.** Its one sink
  string-casts whatever it gets, and PHP 8.5 deprecates wrapping an object in an
  `ArrayIterator` as an attribute value, so returning a joined string keeps that
  path clean. `attrs()` re-splits such a string on `class`, so the same value is
  still usable there.
