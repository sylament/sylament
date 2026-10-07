---
description: The `autocomplete()` / `getAutocomplete()` concern behind the native `autocomplete` attribute of TextInput and Textarea.
ports:
  - vendor/filament/forms/src/Components/Concerns/CanBeAutocompleted.php
owns:
  - packages/forms/src/Components/Concerns/CanBeAutocompleted.php
---
# CanBeAutocompleted (forms)

`Sylament\Forms\Components\Concerns\CanBeAutocompleted` is the shared concern behind the native `autocomplete` attribute: the `autocomplete()` setter and the `getAutocomplete()` reader that maps `true` to `'on'`, `false` to `'off'` and passes any other token (`given-name`, `one-time-code`, …) straight through.
`TextInput` and `Textarea` compose it, the same two fields Filament composes it on.
Its sibling `CanBeAutocapitalized`, the same trait over the `autocapitalize` attribute, is composed by `TextInput` alone and lives in [`text-input.md`](text-input.md); the [divergence](#the-deprecated-disable-aliases-are-not-ported) below covers the deprecated alias of both.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`autocomplete()` / `getAutocomplete()`](../../../packages/forms/src/Components/Concerns/CanBeAutocompleted.php) | [`Concerns/CanBeAutocompleted.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeAutocompleted.php) | done | — | [semantics](#getautocomplete-evaluates-before-the-match-not-inside-its-subject) |
| `disableAutocomplete()` | [`Concerns/CanBeAutocompleted.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeAutocompleted.php) | skipped | — | [diverges](#the-deprecated-disable-aliases-are-not-ported) |
| The `autocomplete` attribute on each control | [`TextInput::toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | [semantics](#the-autocomplete-attribute-renders-unguarded-because-the-bag-drops-a-null) |

## Semantics

### The `autocomplete` attribute renders unguarded, because the bag drops a `null`

Neither `autocomplete` nor `autocapitalize` is guarded by an `{% if %}` in the templates, and neither takes the `?: false` that `placeholder` and `inputmode` carry beside them.
[`ComponentAttributeBag`](../../../packages/support/src/Twig/ComponentAttributeBag.php) drops a `null`, which is exactly what an unset value reads back as, while the getter has already turned a `false` into the string `'off'`: the attribute a user calling `autocomplete(false)` is asking to see rendered.

### `getAutocomplete()` evaluates before the `match`, not inside its subject

Both getters hoist `evaluate()` into a variable rather than assigning inside the `match` subject the way Filament's `match ($autocomplete = $this->evaluate(…))` does.
PHPStan does not carry a type through an assignment made in a match condition: it reads the `default` arm as `mixed` and rejects the `?string` return at level max, so the inline spelling does not survive the gate.
Behaviour is identical, and no other `match` in the port assigns in its subject either.

## Divergences

### The deprecated disable aliases are not ported

Each Filament trait carries a second entry point beside its setter, `disableAutocomplete(bool | Closure $condition = true)` and `disableAutocapitalize(bool | Closure $condition = true)`, tagged `@deprecated` with a notice naming the setter it sits next to, and pushing `static fn (Field $component): ?bool => $component->evaluate($condition) ? false : null` into that very setter.
Both are skipped, as every [deprecated Filament member](../renames.md#a-deprecated-filament-member-is-not-ported) is.

The replacement is the setter the notice names.
`disableAutocomplete()` is `autocomplete(false)`, and the conditional form is the alias' own body spelled at the call site, `autocomplete(fn (Field $component): ?bool => $component->evaluate($condition) ? false : null)`, a closure the setter takes because the port's union is the same `bool|string|Closure|null` Filament declares.
The `evaluate()` is what makes the two equivalent: `$condition` is `bool|Closure`, and a `Closure` is an object and so truthy ([the same trap](text-input.md#numeric-seeds-the-step-through-isnumeric-not-the-captured-condition)), so testing it directly would render `autocomplete="off"` unconditionally for a closure condition.
