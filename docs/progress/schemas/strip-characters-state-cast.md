---
description: The StripCharactersStateCast, which removes configured characters from string state, element by element for arrays.
ports:
  - vendor/filament/schemas/src/Components/StateCasts/StripCharactersStateCast.php
owns:
  - packages/schemas/src/State/Casts/StripCharactersStateCast.php
---
# StripCharactersStateCast (schemas)

[`Sylament\Schemas\State\Casts\StripCharactersStateCast`](../../../packages/schemas/src/State/Casts/StripCharactersStateCast.php) implements [`StateCast`](state-cast.md).
[`TextInput`](../forms/text-input.md), [`Textarea`](../forms/textarea.md) and [`TagsInput`](../forms/tags-input.md) append it to their default casts when `stripCharacters()` is set, which is how stripping reaches the saved value.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`__construct(array $characters = [])`](../../../packages/schemas/src/State/Casts/StripCharactersStateCast.php) | [`StateCasts/StripCharactersStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/StripCharactersStateCast.php) | done | — | — |
| [`get()` / `set()` — the same strip both ways, element by element for an array](../../../packages/schemas/src/State/Casts/StripCharactersStateCast.php) | [`StateCasts/StripCharactersStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/StripCharactersStateCast.php) | done | — | — |
