# helpers.php (support)

`packages/support/src/helpers.php` — the `Sylament\Support`-namespaced global
functions (composer `files` autoload, imported with `use function`). It ports
Filament's own `helpers.php` **and** the Laravel global helpers Filament's file
leans on but never defines (`blank()`, `filled()`, `Arr::wrap()`) — Sylament has
no Laravel runtime, so they are reimplemented here. Today one Filament helper is
ported (`generate_icon_html`) plus those three Laravel foundations; the rest of
Filament's `helpers.php` surface is not built yet.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/src/helpers.php`](../../../vendor/filament/support/src/helpers.php)
  — the Filament helper set (`generate_icon_html` and the fifteen not-yet-ported
  functions below)
- [`Illuminate/Support/helpers.php`](../../../vendor/laravel/framework/src/Illuminate/Support/helpers.php)
  — Laravel's `blank()` / `filled()`, which Filament's file consumes
- [`Illuminate/Collections/Arr.php`](../../../vendor/laravel/framework/src/Illuminate/Collections/Arr.php)
  — Laravel's `Arr::wrap()`, ported as the free function `wrap()`

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
| [`generate_icon_html()`](../../../packages/support/src/helpers.php) | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | done | — | [diverges](#decorative-icon-defaults-rely-on-ux-icons-not-on-bundled-icon-sets) |
| `format_money()` | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | todo | — | — |
| `format_number()` | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | todo | — | — |
| `get_model_label()` | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | todo | — | — |
| `locale_has_pluralization()` | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | todo | — | — |
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

Specifics for the rows that are not finished. The four `done` rows are covered
by [`tests/Unit/HelpersTest.php`](../../../packages/support/tests/Unit/HelpersTest.php)
(`blank`/`filled` valueless-vs-meaningful data sets, the Markup text-semantics
case, the `wrap` cases, and the full `generate_icon_html` matrix) and
[`tests/Integration/HelpersTest.php`](../../../packages/support/tests/Integration/HelpersTest.php)
(`generate_icon_html` through the real ux-icons renderer); they carry no
write-up here.

### The rest of Filament's `helpers.php`

Fifteen functions remain **todo**. They split into clusters that will port only
once their dependency lands, so none is built piecemeal now:

- **Money / number formatting** — `format_money()`, `format_number()` are
  `@deprecated` in Filament (thin `Illuminate\Support\Number` wrappers); port
  their non-deprecated replacement, not these, if at all.
- **Doctrine / query** — `generate_search_column_expression()`,
  `generate_search_term_expression()`, `get_model_label()` are Eloquent/Query
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

### Decorative-icon defaults rely on ux-icons, not on bundled icon sets

An unnamed icon must end up decorative on **both** render branches, and only one
of those branches lives in this file. The image branch is ours: `generate_icon_html()`
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

### `wrap()` and the Laravel globals are reimplemented, not forwarded

`blank()`, `filled()`, and `wrap()` are **not** Filament `helpers.php` functions
— they are Laravel globals (`blank()` / `filled()`) and `Arr::wrap()` that
Filament's file consumes without defining. Because Sylament ships no Laravel
runtime, they are reimplemented here in the `Sylament\Support` namespace so the
ported Filament helpers (and every downstream component) have them. `wrap()` is a
free-function port of the static `Arr::wrap()` method, kept minimal (the
`null → []`, array-passthrough, scalar-to-single-element contract) — no
`Collection` or `Arrayable` handling, since nothing in the port needs it. Its one
job here is guarding the `foreach (wrap($this->evaluate(…)))` shape the schemas
and actions concerns use: a closure that returns `null` or a single item would
otherwise silently empty the loop or fatal it.
