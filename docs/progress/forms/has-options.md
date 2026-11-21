---
description: 'The option map the option-shaped fields are configured with: a literal array, a closure or an enum class-string, plus the state templates compare against.'
ports:
  - vendor/filament/forms/src/Components/Concerns/HasOptions.php
owns:
  - packages/forms/src/Components/Concerns/HasOptions.php
---
# HasOptions (forms)

`Sylament\Forms\Components\Concerns\HasOptions` — the option map every option-shaped field is configured with: a literal array (flat or one optgroup level deep), a closure evaluated per render, or an **enum class-string**, which also seeds [`HasEnum`](has-enum.md) so the same declaration drives the field's state cast.
It carries members Filament has no counterpart for, `getOptionComparisonState()` and the per-value `normalizeOptionComparisonValue()` behind it, because the port emits `selected` / `checked` server-side where Filament leaves that to Livewire.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| `options()` / `getOptions()` / `hasDynamicOptions()` | [`Concerns/HasOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasOptions.php) | done | — | [semantics](#an-option-label-may-be-a-message-translated-label-by-label) · [semantics](#a-group-label-is-an-array-key-so-it-cannot-be-a-message) · [diverges](#the-arrayable-arm-is-dropped) |
| The enum class-string arm — `options(MyEnum::class)` also calling `enum()`, and `getOptions()` expanding `cases()` through support's [`HasLabel`](../../../packages/support/src/Contracts/HasLabel.php) | [`Concerns/HasOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasOptions.php) | done | — | [detail](has-enum.md) · [semantics](#an-enum-label-stays-markup-rather-than-being-stringified) · [diverges](#an-enum-that-is-a-message-labels-its-own-option) |
| `getOptionComparisonState()` / `normalizeOptionComparisonValue()` — no Filament source | — | done | — | [semantics](#the-option-vocabulary-is-normalized-once-rather-than-per-template) · [renames](../translations.md#names-with-no-filament-source) |

## Semantics

### The option vocabulary is normalized once, rather than per template

An option's identity is the **array key** `getOptions()` returns, which is what a template renders into `value` and what a browser submits back.
A field's state is not required to speak that vocabulary: an enum-cast field holds `BackedEnum` instances, a `boolean()` select holds `true` / `false`, and a multi-value field holds a list of either.
Filament never has to reconcile the two, because its `selected` / `checked` attributes are written by Alpine from the Livewire-hydrated model rather than by PHP.
The port renders them server-side ([why](select.md#the-template-renders-the-selected-state-itself)), so something has to translate.

`getOptionComparisonState()` is that translation, and it is deliberately the *only* one: it unwraps a `BackedEnum` to its backing value, coerces a `bool` to `1` / `0`, passes `int` and `string` through, maps an iterable item by item, and answers `null` for anything else — an object that is not an enum has no option key it could match, so it compares as "nothing selected" rather than being stringified into a bogus one.
Every option template compares against this, never against raw `getState()`, and `Select::getOptionLabel()` resolves through it too so a label lookup and a `selected` attribute can never disagree.
`normalizeOptionComparisonValue()` is the per-value half, which [`disableOptionsWhenSelectedInSiblingRepeaterItems()`](can-disable-options-when-selected-in-sibling-repeater-items.md) also runs over a sibling item's raw value.

The key may be `int 1` where the submitted state is the string `'1'` — the same mismatch [`getEnabledOptionValues()`](can-disable-options.md) answers on the validation side — so a template cannot compare the two strictly as they come: [`Select`'s](select.md#the-template-renders-the-selected-state-itself) casts both to strings and compares by identity, and the radio, toggle-buttons and checkbox-list templates do the same, so of the keys `01` and `1` only the one the state spells is checked.
Nothing is loosened security-wise: `selected` is presentation, and the strict `Choice` is what admits or rejects a value.

### An option label may be a message, translated label by label

`options()` takes a `TranslatableInterface` as any label, flat or inside a group, and `getOptions()` translates each one through the field's translator before it returns.
Every reader of the map therefore receives the string or `Markup` it did before: `isOptionDisabled()` and a `disableOptionWhen()` closure, the templates, `transformOptionsForJs()` and [`Select`'s label lookups](select.md#getoptionlabel-resolves-off-the-map-or-through-getoptionlabelusing).
The enum arm reads each case raw in the private static `getOptionsFromEnum()`, where no translator is reachable, and the instance getter translates what it read, so a case's [`HasLabel`](../../../packages/support/src/Contracts/HasLabel.php) label may be a message ([contracts](../support/contracts.md#text-a-contract-answers-may-be-a-message-which-its-reader-translates)).
The map is translated on every read, in the locale of the request reading it.

### A group label is an array key, so it cannot be a message

In `options(['Ferns' => ['fern' => 'Fern']])` the group's label is the array key, which PHP admits only as `int|string`, so it renders as given.
A user translates it before passing it, as a Filament user calls `__()` there; the options inside the group take a message like any other label.

### An enum label stays `Markup` rather than being stringified

Support's [`HasLabel`](../../../packages/support/src/Contracts/HasLabel.php) returns `string|Markup|TranslatableInterface|null`, so an enum case may label itself with markup, and a message translates to a plain string.
The option map keeps a `Markup` label: the value type `getOptions()` returns is `string|Markup`, and the trap avoided is that stringifying a `Markup` label would make the template **escape** it — Twig's escaper passes a `Markup` instance through untouched but escapes the identical raw string, so `(string) $label` silently turns an author's markup into visible angle brackets.
Filament lands in the same place by accident rather than by type: its `array<string | array<string>>` docblock and `?string` on `getOptionLabel()` under-describe an `Htmlable` label that weak mode coerces on the way out, which `declare(strict_types=1)` would turn into a `TypeError`.

Three signatures widened with it, and two of the three move *towards* Filament rather than away: `CanDisableOptions::isOptionDisabled()` takes `string|Markup $label` where Filament takes `string | Htmlable`, and `getEnabledOptions()` returns the same union its map holds.
The third, `Select::getOptionLabel(): string|Markup|null`, is the recorded departure — Filament declares `?string` there, which only survives because Blade never runs under strict types.

## Divergences

### The `Arrayable` arm is dropped

Filament types `options()` as `array<string | array<string>> | Arrayable | string | Closure | null` and `getOptions()` unwraps an `Illuminate\Contracts\Support\Arrayable` with `->toArray()`.
That interface is Laravel's, with no Symfony counterpart and no port here, so the union is `array|string|Closure|null` and the getter has no `instanceof` branch.
A caller holding a collection-like object passes `$collection->toArray()` — or a closure returning it — which is what the `Closure` arm is for.
[`datalist()`](has-datalist-options.md) [drops the same arm](has-datalist-options.md#the-datalist-drops-the-arrayable-arm) for the same reason.

One consequence of the string arm existing at all: a string that names no enum — `options('date')` — is a value, not a callable and not an enum, so `getOptions()` answers `[]` rather than returning the string out of an `array` return type as Filament's would.
It does not fail open: an option field over an empty map builds a `Choice` with no choices, so every submitted value is rejected.
Filament fatals on the same input instead, which is the only place the port is softer here.

### An enum that is a message labels its own option

An option enum implementing `Symfony\Contracts\Translation\TranslatableInterface` and not [`HasLabel`](../../../packages/support/src/Contracts/HasLabel.php) is labelled by its own `trans()`, because that is how a Symfony application already makes an enum translatable.
Filament has no such arm and labels the case by its name.
A case implementing both is labelled by `HasLabel`, Filament's contract, and a `HasLabel` case answering `null` falls back to its name as in Filament.
