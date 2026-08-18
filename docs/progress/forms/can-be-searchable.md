# CanBeSearchable (forms)

`Sylament\Forms\Components\Concerns\CanBeSearchable` — the searchable switch and
the six messages around it: `searchable()`, the `noOptionsMessage()` /
`noSearchResultsMessage()` / `searchPrompt()` / `searchingMessage()` quartet,
`searchDebounce()`, and the `searchLabels()` / `searchValues()` pair that
`getSearchableOptionFields()` composes. Two fields compose it —
[`CheckboxList`](checkbox-list.md), whose search is a client-side filter over
the options it already rendered, and [`Select`](select.md), whose search is the
server-side arm the whole trait exists for — so it is promoted out of the
former's fold to a file of its own per
[one row per file](../README.md#one-row-per-file-one-file-per-thing). Filament
composes it on the same two fields, and `Select` overrides two of its members
there exactly as it does here.

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`forms/src/Components/Concerns/CanBeSearchable.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeSearchable.php)
  — the trait
- [`forms/src/Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php)
  — `searchable()` widened to the column-array arm and `isSearchable()`
  defaulting to `isMultiple()`, the two overrides
- [`forms/resources/lang/en/components.php`](../../../vendor/filament/forms/resources/lang/en/components.php)
  — the `select.*` message keys the four literals stand in for

## Surface

_Blocked by_ names what has to land first. _Detail_ links a permanent note in
[Semantics](#semantics) or [Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`searchable()` / `isSearchable()`](../../../packages/forms/src/Components/Concerns/CanBeSearchable.php) — the trait's `bool\|Closure\|null` arm | [`Concerns/CanBeSearchable.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeSearchable.php) | done | — | [semantics](#select-widens-searchable-to-a-column-array-and-defaults-it-to-multiple) |
| `Select::searchable(bool\|array\|Closure\|null)` / `Select::isSearchable()` — the column-array arm and the `?? isMultiple()` default | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#select-widens-searchable-to-a-column-array-and-defaults-it-to-multiple) |
| `noOptionsMessage()` / `noSearchResultsMessage()` / `searchPrompt()` / `searchingMessage()` and their getters | [`Concerns/CanBeSearchable.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeSearchable.php) | done | — | [diverges](#the-four-message-literals-are-untranslated) |
| `searchDebounce()` / `getSearchDebounce()` — 1000 ms by default | [`Concerns/CanBeSearchable.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeSearchable.php) | done | — | [semantics](#the-checkbox-list-search-is-client-side-only-and-mostly-select-only) |
| `searchLabels()` / `searchValues()` / `getSearchableOptionFields()` | [`Concerns/CanBeSearchable.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeSearchable.php) | done | — | [semantics](#the-checkbox-list-search-is-client-side-only-and-mostly-select-only) · [diverges](#the-label-and-value-switches-take-null) |

Coverage: the trait itself — the default, the literal, closure and `null` arms
of `searchable()`, the four literals and their overrides, the debounce, and the
label/value switches in every combination — by
[`CanBeSearchableTest`](../../../packages/forms/tests/Unit/Components/Concerns/CanBeSearchableTest.php)
over a fixture field; `Select`'s two overrides — `searchable(array)` setting the
columns, `isSearchable()` reading `isMultiple()` when nothing was declared and a
declared `false` winning over `multiple()` — by
[`SelectTest`](../../../packages/forms/tests/Integration/Components/SelectTest.php);
what the checkbox list does with the switch is covered where the field is,
[`checkbox-list.md`](checkbox-list.md).

## Semantics

### `Select` widens `searchable()` to a column array, and defaults it to `multiple()`

The trait's setter takes `bool|Closure|null`. `Select` overrides it, Filament-exact,
with `bool|array|Closure|null`: an array turns searching on **and** names the
relationship columns the [search arm](select.md#the-relationship-search-arm-runs-a-like-per-column-over-the-scoped-option-query)
runs its `LIKE`s over, where a bare `searchable()` searches the title attribute
alone; every other arm assigns the condition and clears the columns, so
`searchable(['name'])->searchable()` is back to the title attribute. The `null`
arm is the port's, not Filament's — the trait's setter is deliberately nullable
so `searchable(null)` assigns rather than throwing, and an override may not
narrow a parameter it inherits.

`Select::isSearchable()` reads `evaluate($isSearchable) ?? isMultiple()`, also
Filament-exact: a `multiple()` select is searchable unless told otherwise, which
is what puts every multiple select on the JS chips control
([why that matters for a relationship select](select.md#a-relationship-map-is-full-or-an-exception-except-where-search-makes-it-partial-by-design)).
The trait's own `isSearchable()` casts to `bool` and knows nothing of
multiplicity — the class method wins over the trait's, so `CheckboxList` keeps
the plain reading. Both overrides live on `Select`, not in the trait, because a
`CheckboxList` has no columns to search and no multiplicity to default from.

### The checkbox list search is client-side only, and mostly Select-only

On `CheckboxList` the trait ships whole, and the search it configures never
reaches the server: the template renders **every** option server side and the
controller hides the non-matching ones with the `hidden` attribute. There is no
request to make, so there is nothing to debounce for latency and no loading
state to show.

Four things are worth stating, because three of them read as omissions:

1. **`CheckboxList::setUp()` forces `searchDebounce(0)`**, overriding the trait's
   `1000` default, vendor-exact — a client-side filter has no request to
   debounce. A caller can still raise it, and the value travels to the controller
   as the Stimulus `debounce` value, which gates the filter's `setTimeout`. On
   `Select` the default stands, and it bounds the search round-trip.
2. **`searchLabels()` / `searchValues()` / `getSearchableOptionFields()` are
   dead surface on `CheckboxList`.** Vendor's Alpine component matches the
   rendered `innerText` of the option label **and** the option description, and
   never the option value; the three members exist for `Select`'s search, whose
   controller feeds `getSearchableOptionFields()` to the local matcher, and
   `CheckboxList` consults none of them. They are deliberately absent from that
   template's and controller's contract rather than wired to something plausible.
3. **`getNoOptionsMessage()` and `getSearchingMessage()` are not rendered by
   `CheckboxList`.** An empty option list renders an empty options grid (vendor
   renders an empty `wire:key`ed `<div>` as a morph anchor and nothing else), and
   there is no searching state to message because there is no request. `Select`
   renders all four.
4. **The four message literals are untranslated** ([below](#the-four-message-literals-are-untranslated)).

## Divergences

Deliberate deviations from Filament, and the reason each holds.

### The four message literals are untranslated

Matching [`Select`'s placeholder](select.md#the-default-placeholder-is-an-untranslated-literal)
and [`Radio`'s boolean labels](radio.md#the-boolean-labels-are-untranslated-literals),
the four defaults are literals, not translation keys. Their Filament keys are
recorded here so the strings can be found when a translation layer lands, and
the family is worth noting because it is **`select`, not `checkbox_list`**:
`filament-forms::components.select.search_prompt`
(`Start typing to search...`),
`filament-forms::components.select.no_search_results_message`
(`No options match your search.`),
`filament-forms::components.select.no_options_message`
(`No options available.`) and
`filament-forms::components.select.searching_message` (`Searching...`) — all
four owned by the trait, so a `CheckboxList` reads `Select`'s strings. Only the
checkbox list's two bulk-toggle labels have keys of that field's own
([checkbox-list.md](checkbox-list.md#the-bulk-toggle-labels-are-untranslated-literals)).

### The label and value switches take `null`

`$shouldSearchLabels` and `$shouldSearchValues` are nullable here, where
Filament declares them `bool|Closure` against setters whose own signature is
`bool|Closure|null` — so `searchLabels(null)` throws a `TypeError` upstream and
assigns here. Null means off, through the readers' `bool` cast; the contract is
also on
[the trait](../../../packages/forms/src/Components/Concerns/CanBeSearchable.php)
as a docblock, because it is the one place a reader would "fix" the widening.
