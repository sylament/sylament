---
description: The CanTrimState concern that TextInput, Textarea and TagsInput compose to trim string state before validation and saving.
ports:
  - vendor/filament/schemas/src/Components/Concerns/CanTrimState.php
owns:
  - packages/schemas/src/Components/Concerns/CanTrimState.php
---
# CanTrimState (schemas)

[`Sylament\Schemas\Components\Concerns\CanTrimState`](../../../packages/schemas/src/Components/Concerns/CanTrimState.php) is composed by forms' [`TextInput`](../forms/text-input.md), [`Textarea`](../forms/textarea.md) and [`TagsInput`](../forms/tags-input.md), not by the base `Component`, as in Filament.
Each field decides where `trimState()` runs, and its own file says so.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`trim()` / `isTrimmed()`](../../../packages/schemas/src/Components/Concerns/CanTrimState.php) | [`Concerns/CanTrimState.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanTrimState.php) | done | — | — |
| [`trimState()`](../../../packages/schemas/src/Components/Concerns/CanTrimState.php) | [`Concerns/CanTrimState.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanTrimState.php) | done | — | [diverges](#mb_trim-keeps-the-invisible-characters-that-filaments-strtrim-strips) |

## Divergences

### `mb_trim()` keeps the invisible characters that Filament's `Str::trim()` strips

Filament's `trimState()` calls `Str::trim()`, which strips ASCII whitespace plus every character in Laravel's `Str::INVISIBLE_CHARACTERS`, among them the zero-width space U+200B and the byte-order mark U+FEFF.
The port calls PHP's `mb_trim()`, which strips Unicode whitespace such as U+00A0 and U+3000 but leaves U+200B and U+FEFF in place.
A value pasted with a leading zero-width space therefore saves trimmed in Filament and untrimmed here.
The port uses the multibyte trim the language ships; matching Filament would take a helper in `helpers.php` that carries Laravel's character list.
