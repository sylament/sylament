---
description: The searchable switch, its four messages, the search debounce and the label/value switches that Select and CheckboxList compose.
ports:
  - vendor/filament/forms/src/Components/Concerns/CanBeSearchable.php
  - vendor/filament/forms/src/Components/Select.php
  - vendor/filament/forms/resources/lang/en/components.php
owns:
  - packages/forms/src/Components/Concerns/CanBeSearchable.php
---
# CanBeSearchable (forms)

`Sylament\Forms\Components\Concerns\CanBeSearchable` — the searchable switch and the six messages around it: `searchable()`, the `noOptionsMessage()` / `noSearchResultsMessage()` / `searchPrompt()` / `searchingMessage()` quartet, `searchDebounce()`, and the `searchLabels()` / `searchValues()` pair that `getSearchableOptionFields()` composes.
Two fields compose it: [`CheckboxList`](checkbox-list.md), whose search is a client-side filter over the options it already rendered, and [`Select`](select.md), whose search is the server-side arm the whole trait exists for.
Filament composes the trait on `Select` and `MorphToSelect`, where `Select` overrides two of its members exactly as it does here; its `CheckboxList` declares four of the members inline instead ([below](#checkboxlist-composes-the-whole-trait-where-filament-inlines-four-members)).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`searchable()` / `isSearchable()`](../../../packages/forms/src/Components/Concerns/CanBeSearchable.php) — the trait's `bool\|Closure\|null` arm | [`Concerns/CanBeSearchable.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeSearchable.php) | done | — | [semantics](#select-widens-searchable-to-a-column-array-and-defaults-it-to-multiple) |
| `Select::searchable(bool\|array\|Closure\|null)` / `Select::isSearchable()` — the column-array arm and the `?? isMultiple()` default | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#select-widens-searchable-to-a-column-array-and-defaults-it-to-multiple) |
| `noOptionsMessage()` / `noSearchResultsMessage()` / `searchPrompt()` / `searchingMessage()` and their getters | [`Concerns/CanBeSearchable.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeSearchable.php) | done | — | [diverges](#the-four-message-literals-are-untranslated) |
| `searchDebounce()` / `getSearchDebounce()` — 1000 ms by default | [`Concerns/CanBeSearchable.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeSearchable.php) | done | — | [semantics](#the-checkbox-list-search-is-client-side-only-and-mostly-select-only) |
| `searchLabels()` / `searchValues()` / `getSearchableOptionFields()` | [`Concerns/CanBeSearchable.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeSearchable.php) | done | — | [diverges](#checkboxlist-composes-the-whole-trait-where-filament-inlines-four-members) · [diverges](#the-label-and-value-switches-take-null) |

## Semantics

### `Select` widens `searchable()` to a column array, and defaults it to `multiple()`

The trait's setter takes `bool|Closure|null`.
`Select` overrides it with `bool|array|Closure|null`: an array turns searching on **and** names the relationship columns the [search arm](has-relationship.md#the-relationship-search-arm-runs-a-like-per-column-over-the-scoped-option-query) runs its `LIKE`s over, where a bare `searchable()` searches the title attribute alone; every other arm assigns the condition and clears the columns, so `searchable(['name'])->searchable()` is back to the title attribute.
Filament's override is the same except for the `null` arm, which it leaves out: the port keeps the nullable parameter the trait's setter declares, so `searchable(null)` assigns rather than throwing on `Select` too.

`Select::isSearchable()` reads `evaluate($isSearchable) ?? isMultiple()`, as Filament's does: a `multiple()` select is searchable unless told otherwise, which is what puts every multiple select on the JS chips control ([why that matters for a relationship select](has-relationship.md#a-relationship-map-is-full-or-an-exception-except-where-search-makes-it-partial-by-design)).
The trait's own `isSearchable()` casts to `bool` and knows nothing of multiplicity — the class method wins over the trait's, so `CheckboxList` keeps the plain reading.
Both overrides live on `Select`, not in the trait, because a `CheckboxList` has no columns to search and no multiplicity to default from.

### The checkbox list search is client-side only, and mostly Select-only

On `CheckboxList` the search the trait configures never reaches the server: the template renders **every** option server side and the controller hides the non-matching ones with the `hidden` attribute.
There is no request to make, so there is nothing to debounce for latency and no loading state to show.

`CheckboxList::setUp()` calls `searchDebounce(0)`, overriding the trait's `1000` default; Filament reaches the same `0` through a property default on its own class.
A caller can still raise it, and the value travels to the controller as the Stimulus `debounce` value, which gates the filter's `setTimeout`.
On `Select` the default stands, and it bounds the search round-trip.

The controller matches the rendered text of the option label **and** the option description, never the option value, as Filament's Alpine component does.
An empty option list renders an empty options grid, and there is no searching state to message because there is no request.

## Divergences

### CheckboxList composes the whole trait where Filament inlines four members

Filament's `CheckboxList` does not compose `CanBeSearchable`: it declares `searchable()`, `isSearchable()`, `searchPrompt()`, `noSearchResultsMessage()`, `searchDebounce()` and their getters on the class itself, and has no `noOptionsMessage()`, `searchingMessage()`, `searchLabels()`, `searchValues()` or `getSearchableOptionFields()` at all.
The port composes the trait whole, so those members exist on a `CheckboxList` and do nothing there.
`getSearchableOptionFields()` feeds `Select`'s local matcher, and the checkbox list's template and controller consult neither it nor `getNoOptionsMessage()` / `getSearchingMessage()`; they are deliberately left out of that contract rather than wired to something plausible.
One trait over two declarations is what keeps the shared members' signatures and defaults from drifting apart; the cost is five inert members on one field.

### The four message literals are untranslated

Matching [`Select`'s placeholder](select.md#the-default-placeholder-is-an-untranslated-literal) and [`Radio`'s boolean labels](radio.md#the-boolean-labels-are-untranslated-literals), the four defaults are literals, not translation keys.
Their Filament keys are recorded here so the strings can be found when a translation layer lands, and the family is worth noting because it is **`select`, not `checkbox_list`**: `filament-forms::components.select.search_prompt` (`Start typing to search...`), `filament-forms::components.select.no_search_results_message` (`No options match your search.`), `filament-forms::components.select.no_options_message` (`No options available.`) and `filament-forms::components.select.searching_message` (`Searching...`).
Filament's `CheckboxList` reads the same `select` keys for its two inline messages, so a `CheckboxList` speaks `Select`'s strings on both sides.
Only the checkbox list's two bulk-toggle labels have keys of that field's own ([checkbox-list.md](checkbox-list.md#the-bulk-toggle-labels-are-untranslated-literals)).

### The label and value switches take `null`

`$shouldSearchLabels` and `$shouldSearchValues` are nullable here, where Filament declares them `bool|Closure` against setters whose own signature is `bool|Closure|null` — so `searchLabels(null)` throws a `TypeError` upstream and assigns here.
Null means off, through the readers' `bool` cast; the contract is also on [the trait](../../../packages/forms/src/Components/Concerns/CanBeSearchable.php) as a docblock, because it is the one place a reader would "fix" the widening.
