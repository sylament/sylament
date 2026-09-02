# helpers.php (support)

`packages/support/src/helpers.php` — the `Sylament\Support`-namespaced global
functions (composer `files` autoload, imported with `use function`). It ports
Filament's own `helpers.php` **and** the Laravel global helpers Filament's code
leans on but never defines (`blank()`, `filled()`, `Arr::wrap()`, `Arr::only()`,
`Arr::except()`, `Str::plural()`) — Sylament has no Laravel runtime, so they are
reimplemented here. Today the file holds those six Laravel foundations plus
`sanitize_url()` and `sanitize_css_color()`, two `Str` macros Filament declares
in its service provider rather than in `helpers.php`, `get_record_label()`,
the record-vocabulary port of Filament's `get_model_label()`, and
`diff_for_humans()`, the port of the one Carbon call the toolkit needs; every
function in it is pure, and that is now an invariant — see
[the divergence below](#generate_icon_html-moved-to-the-twig-extension).
The rest of Filament's `helpers.php` surface is not built yet.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/src/helpers.php`](../../../vendor/filament/support/src/helpers.php)
  — the Filament helper set (the sixteen functions tracked below)
- [`support/src/SupportServiceProvider.php`](../../../vendor/filament/support/src/SupportServiceProvider.php)
  — `packageBooted()`'s `Str` macros, two of which (`sanitizeUrl`,
  `sanitizeCssColor`) land here as functions
- [`Illuminate/Support/helpers.php`](../../../vendor/laravel/framework/src/Illuminate/Support/helpers.php)
  — Laravel's `blank()` / `filled()`, which Filament's file consumes
- [`Illuminate/Collections/Arr.php`](../../../vendor/laravel/framework/src/Illuminate/Collections/Arr.php)
  — Laravel's `Arr::wrap()`, `Arr::only()` and `Arr::except()`, ported as the
  free functions `wrap()`, `only()` and `except()`

## Surface

Every function this file defines, then the Filament helpers still to port.
_Blocked by_ names what has to land first. _Detail_ carries **detail** → the
write-up in [Details](#details), and/or **diverges** → the permanent note in
[Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`blank()`](../../../packages/support/src/helpers.php) | [`Illuminate/Support/helpers.php`](../../../vendor/laravel/framework/src/Illuminate/Support/helpers.php) | done | — | [diverges](#blank-gives-markup-text-semantics-despite-its-countable-count) |
| [`filled()`](../../../packages/support/src/helpers.php) | [`Illuminate/Support/helpers.php`](../../../vendor/laravel/framework/src/Illuminate/Support/helpers.php) | done | — | — |
| [`wrap()`](../../../packages/support/src/helpers.php) | [`Illuminate/Collections/Arr.php`](../../../vendor/laravel/framework/src/Illuminate/Collections/Arr.php) | done | — | [diverges](#wrap-and-the-laravel-globals-are-reimplemented-not-forwarded) |
| [`sanitize_url()`](../../../packages/support/src/helpers.php) | [`Str::sanitizeUrl` in `SupportServiceProvider.php`](../../../vendor/filament/support/src/SupportServiceProvider.php) | done | — | [diverges](#sanitize_url-is-a-helper-because-the-macro-target-does-not-exist-here) |
| [`sanitize_css_color()`](../../../packages/support/src/helpers.php) | [`Str::sanitizeCssColor` in `SupportServiceProvider.php`](../../../vendor/filament/support/src/SupportServiceProvider.php) | done | — | [diverges](#sanitize_css_color-is-the-second-macro-that-lands-as-a-helper) |
| [`diff_for_humans()`](../../../packages/support/src/helpers.php) | [`CarbonInterval::forHumans()`](../../../vendor/nesbot/carbon/src/Carbon/CarbonInterval.php) at `$parts = 1` | done | — | [diverges](#diff_for_humans-is-a-carbon-port-not-a-carbon-dependency) |
| [`get_record_label()`](../../../packages/support/src/helpers.php) | [`get_model_label()`](../../../vendor/filament/support/src/helpers.php) | done | — | [diverges](#get_record_label-is-a-rename-and-it-is-what-put-symfonystring-in-supports-manifest) |
| [`only()`](../../../packages/support/src/helpers.php) | [`Illuminate/Collections/Arr.php`](../../../vendor/laravel/framework/src/Illuminate/Collections/Arr.php) | done | — | [diverges](#wrap-and-the-laravel-globals-are-reimplemented-not-forwarded) |
| [`except()`](../../../packages/support/src/helpers.php) | [`Illuminate/Collections/Arr.php`](../../../vendor/laravel/framework/src/Illuminate/Collections/Arr.php) | done | — | [diverges](#wrap-and-the-laravel-globals-are-reimplemented-not-forwarded) |
| [`plural()`](../../../packages/support/src/helpers.php) | [`Str::plural()`](../../../vendor/laravel/framework/src/Illuminate/Support/Str.php) | done | — | [diverges](#wrap-and-the-laravel-globals-are-reimplemented-not-forwarded) |
| `generate_icon_html()` — lives on [`IconExtension`](../../../packages/support/src/Twig/Extension/IconExtension.php) | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | done | — | [diverges](#generate_icon_html-moved-to-the-twig-extension) · [diverges](#decorative-icon-defaults-rely-on-ux-icons-not-on-bundled-icon-sets) |
| `format_money()` | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | todo | — | — |
| `format_number()` | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | todo | — | — |
| `locale_has_pluralization()` | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | todo | — | the port pluralises English unconditionally through [`plural()`](../../../packages/support/src/helpers.php); the guard lands with i18n |
| `get_component_color_classes()` | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | todo | — | — |
| `prepare_inherited_attributes()` | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | todo | — | — |
| `is_slot_empty()` | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | todo | — | — |
| `is_app_url()` | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | todo | — | — |
| `generate_href_html()` | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | todo | — | — |
| `generate_loading_indicator_html()` | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | todo | — | — |
| `generate_search_column_expression()` | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | todo | — | — |
| `generate_search_term_expression()` | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | todo | — | — |
| `original_request()` | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | todo | — | — |
| `discover_app_classes()` | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | todo | — | — |
| `get_color_css_variables()` | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | todo | — | — |

## Details

Specifics for the rows that are not finished. The eleven `done` rows are covered
by [`tests/Unit/HelpersTest.php`](../../../packages/support/tests/Unit/HelpersTest.php)
(`blank`/`filled` valueless-vs-meaningful data sets, the Markup text-semantics
case, the `wrap` cases, the `sanitize_url` refusal set, the `get_record_label`
derivations, the `only`/`except` key sets, the `plural()` last-word cases, the
`sanitize_css_color` accept/refuse pair and the `diff_for_humans` interval table)
and, for the icon row,
[`tests/Unit/Twig/Extension/IconExtensionTest.php`](../../../packages/support/tests/Unit/Twig/Extension/IconExtensionTest.php)
plus [its Integration counterpart](../../../packages/support/tests/Integration/Twig/Extension/IconExtensionTest.php)
against the real ux-icons renderer; they carry no write-up here.

### The rest of Filament's `helpers.php`

Fourteen functions remain **todo**. They split into clusters that will port only
once their dependency lands, so none is built piecemeal now:

- **Money / number formatting** — `format_money()`, `format_number()` are
  `@deprecated` in Filament (thin `Illuminate\Support\Number` wrappers); port
  their non-deprecated replacement, not these, if at all.
- **Doctrine / query** — `generate_search_column_expression()`,
  `generate_search_term_expression()` are Eloquent/Query
  builder helpers that wait on the tables layer and a Doctrine translation.
- **Laravel-runtime bound** — `locale_has_pluralization()`, `is_app_url()`,
  `original_request()`, `discover_app_classes()`, `prepare_inherited_attributes()`
  lean on Laravel's translator, request, container, or `Str` helpers with no
  Sylament consumer yet.
- **Color / view** — `get_component_color_classes()`, `get_color_css_variables()`
  wait on the color manager; `generate_href_html()`, `generate_loading_indicator_html()`
  wait on the SPA/loading-indicator surface (both still Alpine/Livewire-shaped in
  Filament).
- **Slot** — `is_slot_empty()` is the next obvious helper port once a component
  needs Twig-slot emptiness; it maps to `ComponentSlot::hasActualContent()`,
  which has no direct Twig equivalent yet.

Next step: port each in the same change as its first Sylament consumer, adding a
row-flip here when it lands.

## Divergences

### `generate_icon_html()` moved to the Twig extension

Filament defines it as a global function that reaches the icon registry through
the `FilamentIcon` facade. Ported literally, that made
[`IconManager`](icon-manager.md) a static instance for one reason: a free
function has no `$this`, so the registry could only arrive globally. The single
caller in `src/` was always
[`IconExtension`](../../../packages/support/src/Twig/Extension/IconExtension.php),
a service — so the body now lives there with the manager constructor-injected,
and the free function is gone. `IconManager` is a plain service again.

Nothing consumer-facing moves: templates call the Twig function
`generateIconHtml`, whose name and arguments are unchanged. Only a PHP caller of
`Sylament\Support\generate_icon_html()` would notice, and there was never one
outside this repo's own tests. The knock-on is that registering icon aliases is
now service-only — `$container->get(IconManager::class)->register([...])`
instead of `IconManager::getInstance()`, which is what a Symfony app would reach
for anyway.

This leaves every function in `helpers.php` pure. Keep it that way: a helper
that needs a service is a sign it belongs on the service, and the next Filament
helper that reaches for a facade (`get_component_color_classes()` will) gets the
same treatment rather than a second static instance.

### Decorative-icon defaults rely on ux-icons, not on bundled icon sets

An unnamed icon must end up decorative on **both** render branches, and only one
of those branches is ours: the image branch, where `generateIconHtml()`
merges `alt=""` unless the caller set `alt`, `aria-label`, or `aria-labelledby`.
The SVG branch is not — Filament gets its `aria-hidden` from the icon sets it
ships, while Sylament renders through [`IconManager`](icon-manager.md) onto
ux-icons, which adds `aria-hidden` itself at render time when no textual
alternative is present
([`IconRenderer`](../../../vendor/symfony/ux-icons/src/IconRenderer.php)). Same
outcome, different owner: swapping the icon renderer would silently take half of
this guarantee with it, so a replacement has to be checked for that behavior.

### `blank()` gives `Markup` text semantics despite its `Countable` count

Laravel checks `Countable` before stringifying, so a whitespace-only value that
is also `Countable` counts by element and reports **filled**. `Twig\Markup` is
`Countable` (it counts its characters), so under Laravel's order a
`Markup('   ')` would be filled. Sylament special-cases `Markup` **before** the
`Countable` branch and trims its string form, so it keeps the text semantics
Laravel's non-`Countable` `HtmlString` gets — a whitespace-only `Markup` is
blank. Other `Countable + Stringable` objects (Doctrine collections) keep
Laravel's count-first semantics.

### `blank()` trims with `mb_trim`, so unicode whitespace counts as blank

Laravel's `blank()` trims with byte `trim()`, so an NBSP- or em-space-only
string is filled. Sylament trims with `mb_trim` (PHP 8.4+; the package requires
`php: ^8.5`), so `"\u{00A0}\u{2007}"` and other unicode-whitespace-only strings
count as blank — matching the human "no visible content" reading a UI toolkit
wants.

### No `Model`-to-filled short-circuit

Laravel's `blank()` short-circuits `instanceof Model → filled` before the
`Countable` check. There is no Doctrine equivalent, so Sylament omits it: a
`Countable`-empty entity therefore reports **blank** rather than being forced
filled by an entity-type check.

_See also:_ the `Htmlable → Twig\Markup` and `Eloquent → Doctrine` translations
in the global [rename table](../renames.md).

### The `@phpstan-assert` tags model these as null-checks only

`blank()` and `filled()` carry `@phpstan-assert-if-false !null $value` and
`@phpstan-assert-if-true !null $value`, which Laravel's originals do not — the
tags are what let a caller narrow a `mixed` past `null` without a second check.
The cost is that PHPStan reads the tag as the **whole** contract: to the
analyser `blank()` answers true for `null` and nothing else, though the body
also answers true for `''`, `'   '`, `[]` and an empty `Countable`. So a call on
a value already narrowed past `null` is *impossible* — `Call to function
Sylament\Support\blank() with bool|float|int|string will always evaluate to
false`, identifier `function.impossibleType` — and at level max that is an
error, not a hint.

The rule that follows: **test `blank()` before any other narrowing, never
after.** `!is_scalar($key) || blank($key)` is red where
`blank($key) || !is_scalar($key)` is green, and the two agree at runtime because
`blank()` already accepts any type. The incident that named this is
[Task 9's unmasking](../tables/has-table.md#a-host-trait-is-analysed-only-through-a-class-that-composes-it):
panels' `ListRecords` became the first analysed class to compose `HasTable`,
`HasFilters` and `HasActions`, and among the nineteen errors that surfaced were
both shapes of this one — two guards written in the wrong order, and two calls
on a value an over-defensive `?? null` was the only reason to think nullable.
A helper call PHPStan has proved constant is a signal to reorder the guard (or
to delete the defensiveness that made the call look necessary), not to reach for
a suppression.

### `sanitize_url()` is a helper because the macro target does not exist here

Filament declares it as a `Str::macro` (and a `Stringable::macro` forwarding to
it) in
[`SupportServiceProvider::packageBooted()`](../../../vendor/filament/support/src/SupportServiceProvider.php),
so it is neither a `helpers.php` function nor a class method. Macro-based
extension is a rejected pattern here
([why](component.md#macroable-is-not-ported)) and there is no `Str` to extend, so
the body lands in this file: it is pure, it takes only its arguments, and its
first consumer — forms' `FileUpload`, describing an uploaded file's public URL —
imports it with `use function`; tables' `ImageColumn` will be the second, for the
same reason. The `Stringable` forwarder has no analog and is dropped.

The body is otherwise verbatim, including the three comments Filament carries:
they state *why* each of the four refusals exists (which obfuscation each one
catches), which the code cannot, and this is a sink whose callers embed the
result in an `href`. Two mechanical adjustments: `preg_match()` results are
compared `=== 1` rather than used as booleans, and `strtolower()` becomes
`mb_strtolower()` to match the rest of the codebase (schemes matched by the
regex are ASCII, so the two agree). One behavioural addition: a `null` from
`preg_replace_callback()` — a PCRE failure — is a **refusal** rather than a
crash, because a sanitizer that cannot decode its input must not pass it
through.

### `get_record_label()` is a rename, and it is what put `symfony/string` in support's manifest

The name follows the toolkit-wide
[collapse of the model vocabulary onto record](../renames.md#the-model-vocabulary-collapses-onto-record);
the body is Filament's, translated helper for helper —
`str($model)->classBasename()->kebab()->replace('-', ' ')` becomes
`u($class)->afterLast('\\')->kebab()->replace('-', ' ')`. The `class-string<Model>`
parameter type loses its bound with Eloquent: any class-string is a record class
here, and the function never loads one.

It has to be `kebab()` rather than a regex. `panels`' `Resource::getRecordLabel()`
derived the same label the same way through its own copy of the chain, and the
two had to agree byte for byte or a resource page and a modal action would
disagree about what the record is called —
`AbstractUnicodeString::kebab()`'s acronym handling (`PDFDocument` → `pdf
document`, not `p-d-f document`) is not something a hand-rolled expression
reproduces by accident. `Resource::getRecordLabel()` delegates here rather than
keeping `panels`' own copy of the chain, leaving one definition.

The consequence is a manifest line: support requires `symfony/string` from M4 · 2
on — for this one function until M4 · 8, when
[`plural()`](../../../packages/support/src/helpers.php) became its second
consumer through `EnglishInflector`. It is the placement-by-source rule doing
its job — this is Filament-**support**'s helper, and both `actions` and `panels`
read it, so it cannot move up into either — and the constraint is the `^8.1`
the five packages that already declare it carry, under the blanket `symfony/*`
approval.

### `wrap()` and the Laravel globals are reimplemented, not forwarded

`blank()`, `filled()`, `wrap()`, `only()`, `except()` and `plural()` are **not**
Filament `helpers.php` functions — they are Laravel globals (`blank()` /
`filled()`) and `Arr::wrap()` / `Arr::only()` / `Arr::except()` / `Str::plural()`
that
Filament's code consumes without defining: `blank()`/`filled()` in
`helpers.php` itself, and the three `Arr` methods across the packages this port
reads — `filament/support` alone calls all three (`Arr::only` in
`Assets\AssetManager`, `Arr::except` in `Services\RelationshipJoiner`, six
`Arr::wrap` sites), and `filament/actions` reads them too. Because Sylament
ships no Laravel runtime, they are reimplemented here in the
`Sylament\Support` namespace so the ported Filament helpers (and every
downstream component) have them. `wrap()` is a
free-function port of the static `Arr::wrap()` method, kept minimal (the
`null → []`, array-passthrough, scalar-to-single-element contract) — no
`Collection` or `Arrayable` handling, since nothing in the port needs it. Its one
job here is guarding the `foreach (wrap($this->evaluate(…)))` shape the schemas
and actions concerns use: a closure that returns `null` or a single item would
otherwise silently empty the loop or fatal it.

`only()` and `except()` are the same trade one layer up — one-line
`array_intersect_key()` / `array_diff_key()` bodies over `array_flip($keys)`,
without the `Collection`, `Arrayable` or dotted-path arms Laravel's methods
carry, because their two consumers ask for neither: `CreateAction`'s
`preserveFormDataWhenCreatingAnother()` keeps a flat list of field names, and
`ReplicateAction`'s `excludeAttributes()` drops one.

`plural()` is that trade a third time, and the one whose body is not a
one-liner: Laravel's `Str::plural` runs the **last word** of a phrase through an
inflector and leaves the rest alone, so `blog post` pluralises to `blog posts`
rather than `blog posts` by accident. symfony/string's `EnglishInflector` is the
inflector — `pluralize()` returns *candidates* rather than one answer (`person`
→ `['persons', 'people']`), and the port takes the last, which is the rule
panels' `Resource` had already settled on when it carried a private copy of this
body. That copy is gone: two implementations of one inflection would drift, and
the helpers rule says the port lives here. Laravel's `$count`/`$prependCount`
arms are not ported (nothing asks), and neither is its
`locale_has_pluralization()` guard — i18n is deferred, so the port pluralises
English unconditionally.

### `sanitize_css_color()` is the second macro that lands as a helper

Filament declares it as a `Str::macro` beside `sanitizeUrl` in
[`SupportServiceProvider::packageBooted()`](../../../vendor/filament/support/src/SupportServiceProvider.php),
so it lands here for the same reason
[`sanitize_url()` does](#sanitize_url-is-a-helper-because-the-macro-target-does-not-exist-here):
it is pure, it takes only its argument, and there is no `Str` to extend. The
three accept branches are verbatim — hex `#rgb`/`#rgba`/`#rrggbb`/`#rrggbbaa`, a
bare `[a-z]+` keyword, and the ten functional notations (`rgb`, `rgba`, `hsl`,
`hsla`, `hwb`, `lab`, `lch`, `oklab`, `oklch`, `color`) with `( ) ; : " '`
forbidden between the parentheses — as is the `// Security:` comment stating
which injection that last refusal exists to stop. Three mechanical adjustments:
the `BackedEnum` arm is dropped with the parameter typed `?string` (nothing in
the port passes an enum, and a caller with one unwraps it at the call site),
`preg_match()` results are compared `=== 1`, and `trim()` becomes `mb_trim()`
for the reason [`blank()` uses it](#blank-trims-with-mb_trim-so-unicode-whitespace-counts-as-blank):
a value padded with unicode whitespace (`"\u{00A0}red"`) is accepted here and
refused upstream, and the accepted answer is the trimmed string, so the padding
never reaches the declaration either way.

**It is not `ColorManager::sanitizeCssColor()`**, which stays. That one answers a
different question — "is this whole string safe to interpolate into a `--sy-*`
custom property?" — with one permissive character-class regex, and returns `''`
rather than `null` for a refusal, because a dropped declaration there must not
become a syntax error in the emitted `<style>` block. This one asks "is this a
CSS colour?" and answers `null` so a caller can render an unnamed, uncoloured
swatch instead.

### `diff_for_humans()` is a Carbon port, not a Carbon dependency

`nesbot/carbon` is not adopted (the M4 · 4 decision): the one call the toolkit
needs is `diffForHumans()` at its `$parts = 1` default, and its algorithm is
forty lines over `DateTimeInterface::diff()`. The port walks
`year → month → week → day → hour → minute → second`, taking the **first
non-zero** unit and stopping — weeks are `intdiv($d, 7)` and days `$d % 7`, which
is how Carbon derives its `weeks` / `daysExcludeWeeks` pair
([`CarbonInterval::forHumans()`](../../../vendor/nesbot/carbon/src/Carbon/CarbonInterval.php))
— and pluralises the count in English. There are no thresholds and no rounding,
which is Carbon's behaviour too: `47 seconds ago`, never "a minute ago". An
all-zero difference falls back to `0 seconds`.

Two deliberate narrowings. Carbon's `$syntax`, `$short`, `$parts` and `$options`
arguments have no port — the six behaviours they select (absolute phrasing,
`before`/`after` instead of `ago`/`from now`, short units, multi-part output,
`a_second`-style rounding, `JUST_NOW`) have no consumer, and adding one later is
additive. And the English strings are literals rather than a translation
catalogue, i18n being deferred.

The `$now` argument is required rather than defaulted to "now", so the function
stays pure and testable: the consuming column passes
`new DateTimeImmutable('now', $timezone)`. The reading is
`$date->diff($now)` — Carbon's own operand order
([`Difference.php`](../../../vendor/nesbot/carbon/src/Carbon/Traits/Difference.php)
`$this->diff($other)`) — so `invert === 1` means the date is in the future and
reads `from now`, while everything at or before `$now`, the zero difference
included, reads `ago`, exactly as Carbon does.
