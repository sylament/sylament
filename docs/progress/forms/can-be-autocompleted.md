---
description: 'The shared concern behind the native `autocomplete` attribute: the `autocomplete()` setter and the `getAutocomplete()` reader that maps `true` to… (draft)'
ports:
  - vendor/filament/forms/src/Components/Concerns/CanBeAutocompleted.php
  - vendor/filament/forms/src/Components/Concerns/CanBeAutocapitalized.php
owns:
  - packages/forms/src/Components/Concerns/CanBeAutocompleted.php
---
# CanBeAutocompleted (forms)

`Sylament\Forms\Components\Concerns\CanBeAutocompleted` — the shared concern behind the native `autocomplete` attribute: the `autocomplete()` setter and the `getAutocomplete()` reader that maps `true` to `'on'`, `false` to `'off'` and passes any other token (`given-name`, `one-time-code`, …) straight through.
The port's two text fields compose it, `TextInput` and `Textarea`, so it keeps a row of its own rather than folding into either — and those are the only two Filament composes it on, so no unported field is waiting to widen it.

Its sibling `CanBeAutocapitalized` is the same trait over the `autocapitalize` attribute, line for line.
Filament composes that one on `TextInput` alone, so it folds into [`text-input.md`](text-input.md) per [one row per file](../../internals/trail.md#one-file-per-piece-of-code-one-row-per-feature); the [divergence](#the-deprecated-disable-aliases-are-not-ported) below covers both, since the trimmed method is the same on each.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`autocomplete()` / `getAutocomplete()`](../../../packages/forms/src/Components/Concerns/CanBeAutocompleted.php) | [`Concerns/CanBeAutocompleted.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeAutocompleted.php) | done | — | — |
| `disableAutocomplete()` | [`Concerns/CanBeAutocompleted.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeAutocompleted.php) | skipped | — | [diverges](#the-deprecated-disable-aliases-are-not-ported) |
| The `autocomplete` attribute on each control | [`TextInput::toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | — |

Coverage: the `null` default, both boolean spellings, and a literal and a closure token by [`CanBeAutocompletedTest`](../../../packages/forms/tests/Unit/Components/Concerns/CanBeAutocompletedTest.php), which also pins that both text fields compose it.
The sibling gets the same four cases from [`CanBeAutocapitalizedTest`](../../../packages/forms/tests/Unit/Components/Concerns/CanBeAutocapitalizedTest.php), whose composition case asserts `Textarea` **lacks** `autocapitalize()` — the asymmetry is Filament's and reads enough like an oversight that a reader may "correct" it, so it is pinned by a test rather than left to a sentence.
The rendered attributes are asserted present-and-absent by [`TextInputTest`](../../../packages/forms/tests/Integration/Components/TextInputTest.php) (both) and [`TextareaTest`](../../../packages/forms/tests/Integration/Components/TextareaTest.php) (`autocomplete` only, in its `off` spelling).

Neither attribute is guarded by an `{% if %}` in the templates, and neither takes the `?: false` that `placeholder` and `inputmode` carry beside them: [`ComponentAttributeBag`](../../../packages/support/src/Twig/ComponentAttributeBag.php) drops a `null`, which is exactly what an unset value reads back as, while the getter has already turned a `false` into the string `'off'` — the attribute a user calling `autocomplete(false)` is asking to see rendered.

One shape note against the vendor source: both getters hoist `evaluate()` into a variable rather than assigning inside the `match` subject the way Filament's `match ($autocomplete = $this->evaluate(…))` does.
PHPStan does not carry a type through an assignment made in a match condition — it reads the `default` arm as `mixed` and rejects the `?string` return at level max — so the inline spelling does not survive the gate.
Behaviour is identical, and no other `match` in the port assigns in its subject either.

## Divergences

### The deprecated disable aliases are not ported

Each Filament trait carries a second entry point beside its setter — `disableAutocomplete(bool | Closure $condition = true)` and `disableAutocapitalize(bool | Closure $condition = true)` — tagged `@deprecated` with a notice naming the setter it sits next to, and pushing `static fn (Field $component): ?bool => $component->evaluate($condition) ? false : null` into that very setter.
Both are **skipped**.

Nothing is lost.
`disableAutocomplete()` is `autocomplete(false)`, and the conditional form is the alias' own body spelled at the call site, `autocomplete(fn (Field $component): ?bool => $component->evaluate($condition) ? false : null)` — a closure the setter already takes, since the port's union is the same `bool|string|Closure|null` Filament declares.
The `evaluate()` is what makes the two equivalent: `$condition` is `bool|Closure`, and a `Closure` is an object and so truthy ([the same trap](text-input.md#numeric-seeds-the-step-through-isnumeric-not-the-captured-condition)), so testing it directly would render `autocomplete="off"` unconditionally for a closure condition.
What the alias adds is a second name for one behaviour, deprecated the moment it arrived, carrying Filament's own users across a change this toolkit has no history of; porting it would ship a migration path away from an API that never existed here, and leave a reader disambiguating two spellings of one call.
Reversible if a codebase migrating off Filament ever needs the literal names.
