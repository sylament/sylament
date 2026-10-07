---
description: 'The option map every option-shaped field is configured with: a literal array (flat or one optgroup level deep), a closure evaluated per render, or an… (draft)'
ports:
  - vendor/filament/forms/src/Components/Concerns/HasOptions.php
  - vendor/filament/forms/src/Components/Concerns/HasEnum.php
  - vendor/filament/support/src/Contracts/HasLabel.php
owns:
  - packages/forms/src/Components/Concerns/HasOptions.php
  - packages/support/src/Contracts/HasLabel.php
---
# HasOptions (forms)

`Sylament\Forms\Components\Concerns\HasOptions` — the option map every option-shaped field is configured with: a literal array (flat or one optgroup level deep), a closure evaluated per render, or an **enum class-string**, which also seeds [`HasEnum`](has-enum.md) so the same declaration drives the field's state cast.
It carries one member Filament has no counterpart for, `getOptionComparisonState()`, because the port emits `selected` / `checked` server-side where Filament leaves that to Livewire.

Promoted out of [`select.md`](select.md) ahead of the option fields that share it, per [one row per file](../README.md#one-row-per-file-one-file-per-thing).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| `options()` / `getOptions()` / `hasDynamicOptions()` | [`Concerns/HasOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasOptions.php) | done | — | [diverges](#the-arrayable-arm-is-dropped) |
| The enum class-string arm — `options(MyEnum::class)` also calling `enum()`, and `getOptions()` expanding `cases()` through support's [`HasLabel`](../../../packages/support/src/Contracts/HasLabel.php) | [`Concerns/HasOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasOptions.php) | done | — | [detail](has-enum.md) · [semantics](#an-enum-label-stays-markup-rather-than-being-stringified) |
| `getOptionComparisonState()` — no Filament source | — | done | — | [semantics](#the-option-vocabulary-is-normalized-once-rather-than-per-template) · [renames](../translations.md#names-with-no-filament-source) |

Coverage: the enum arm — a `HasLabel` enum's labelled map, a backed enum without the contract keying off its value, a pure `UnitEnum` keying off its case name, `enum()` alone with no `options()` call, an explicit map winning over the enum, a class-string returned by a closure, and a non-enum literal string (`options('date')`) staying a value rather than seeding the enum — by [`HasOptionsTest`](../../../packages/forms/tests/Unit/Components/Concerns/HasOptionsTest.php); the comparison state — a backed enum unwrapped, a boolean coerced to its int key, an iterable mapped item by item with the uncomparable dropped, scalars passed through and everything else nulled — by its [Integration twin](../../../packages/forms/tests/Integration/Components/Concerns/HasOptionsTest.php), which binds a real host so the normalization runs over state that travelled the cast chain; closure options and the empty default by [`CanDisableOptionsTest`](../../../packages/forms/tests/Unit/Components/Concerns/CanDisableOptionsTest.php); the rendered end of it — an enum's options as `<option>` elements and the enum-valued state's option marked `selected` — by [`SelectTest`](../../../packages/forms/tests/Integration/Components/SelectTest.php).

## Semantics

### The option vocabulary is normalized once, rather than per template

An option's identity is the **array key** `getOptions()` returns, which is what a template renders into `value` and what a browser submits back.
A field's state is not required to speak that vocabulary: an enum-cast field holds `BackedEnum` instances, a `boolean()` select holds `true` / `false`, and a multi-value field holds a list of either.
Filament never has to reconcile the two, because its `selected` / `checked` attributes are written by Alpine from the Livewire-hydrated model rather than by PHP.
The port renders them server-side ([why](select.md#the-template-renders-the-selected-state-itself)), so something has to translate.

`getOptionComparisonState()` is that translation, and it is deliberately the *only* one: it unwraps a `BackedEnum` to its backing value, coerces a `bool` to `1` / `0`, passes `int` and `string` through, maps an iterable item by item, and answers `null` for anything else — an object that is not an enum has no option key it could match, so it compares as "nothing selected" rather than being stringified into a bogus one.
Every option template compares against this, never against raw `getState()`, and `Select::getOptionLabel()` resolves through it too so a label lookup and a `selected` attribute can never disagree.

The comparison the templates then make stays deliberately loose (`==`), because the key may be `int 1` where the submitted state is the string `'1'` — the same mismatch [`getEnabledOptionValues()`](can-disable-options.md) answers on the validation side.
Nothing is loosened security-wise: `selected` is presentation, and the strict `Choice` is what admits or rejects a value.

### An enum label stays `Markup` rather than being stringified

Support's [`HasLabel`](../../../packages/support/src/Contracts/HasLabel.php) returns `string|Markup|null`, so an enum case may label itself with markup.
The option map keeps it: the value type is `string|Markup`, and the trap avoided is that stringifying a `Markup` label would make the template **escape** it — Twig's escaper passes a `Markup` instance through untouched but escapes the identical raw string, so `(string) $label` silently turns an author's markup into visible angle brackets.
Filament lands in the same place by accident rather than by type: its `array<string | array<string>>` docblock and `?string` on `getOptionLabel()` under-describe an `Htmlable` label that weak mode coerces on the way out, which `declare(strict_types=1)` would turn into a `TypeError`.

Three signatures widened with it, and two of the three move *towards* Filament rather than away: `CanDisableOptions::isOptionDisabled()` now takes `string|Markup $label` where Filament takes `string | Htmlable`, and `getEnabledOptions()` returns the same union its map holds.
The third, `Select::getOptionLabel(): string|Markup|null`, is the recorded departure — Filament declares `?string` there, which only survives because Blade never runs under strict types.

## Divergences

### The `Arrayable` arm is dropped

Filament types `options()` as `array<string | array<string>> | Arrayable | string | Closure | null` and `getOptions()` unwraps an `Illuminate\Contracts\Support\Arrayable` with `->toArray()`.
That interface is Laravel's, with no Symfony counterpart and no port here, so the union is `array|string|Closure|null` and the getter has no `instanceof` branch.
A caller holding a collection-like object passes `$collection->toArray()` — or a closure returning it — which is what the `Closure` arm is for.
[`datalist()`](has-datalist-options.md) [drops the same arm](has-datalist-options.md#the-datalist-drops-the-arrayable-arm) for the same reason.

Only the Laravel contract stays out; the enum class-string arm this divergence used to sit beside is [restored below](#the-enum-class-string-arm-is-no-longer-dropped).

One consequence of the string arm existing at all: a string that names no enum — `options('date')` — is a value, not a callable and not an enum, so `getOptions()` answers `[]` rather than returning the string out of an `array` return type as Filament's would.
It does not fail open: an option field over an empty map builds a `Choice` with no choices, so every submitted value is rejected.
Filament fatals on the same input instead, which is the only place the port is softer here.

### The enum class-string arm is no longer dropped

**A retired divergence, kept as its own record.**
`options(MyEnum::class)` seeds [`enum()`](has-enum.md) and expands the cases exactly as Filament does.
The arm was dropped alongside the `Arrayable` one while nothing consumed it — an enum class-string reaching a port with no `HasEnum` had nowhere to go — and it came back with that concern, on every option field at once.
Recorded here rather than deleted because the trail carried it as a divergence for as long as the arm was dropped, so a reader meeting the old note elsewhere needs the reversal to be findable.
