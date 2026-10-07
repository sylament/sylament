---
description: The shared concern that offers a component's state to the clipboard, with the copied text, the confirmation message and how long it stays.
ports:
  - vendor/filament/support/src/Concerns/CanBeCopied.php
owns:
  - packages/support/src/Components/Concerns/CanBeCopied.php
---
# CanBeCopied (support)

`Sylament\Support\Components\Concerns\CanBeCopied` lets a component offer its state to the clipboard: `copyable()` turns the control on, `copyableState()` overrides what is written, and `copyMessage()` / `copyMessageDuration()` set the confirmation and how long it stays.
It ports Filament's support-tier `CanBeCopied` method for method, including its four state-injected getters.
The client half, the `sylament--support--clipboard` Stimulus controller a template hands these values to, is [copyable.md](copyable.md).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`copyable()` + `isCopyable()`](../../../packages/support/src/Components/Concerns/CanBeCopied.php) | [`CanBeCopied::copyable()`](../../../vendor/filament/support/src/Concerns/CanBeCopied.php) | done | — | — |
| [`copyableState()` + `getCopyableState()`](../../../packages/support/src/Components/Concerns/CanBeCopied.php) | [`CanBeCopied::copyableState()`](../../../vendor/filament/support/src/Concerns/CanBeCopied.php) | done | — | [semantics](#a-null-copyable-state-means-copy-what-the-cell-rendered) |
| [`copyMessage()` + `getCopyMessage()`](../../../packages/support/src/Components/Concerns/CanBeCopied.php) | [`CanBeCopied::copyMessage()`](../../../vendor/filament/support/src/Concerns/CanBeCopied.php) | done | — | [diverges](#the-copied-default-is-an-english-literal) |
| [`copyMessageDuration()` + `getCopyMessageDuration()`](../../../packages/support/src/Components/Concerns/CanBeCopied.php) | [`CanBeCopied::copyMessageDuration()`](../../../vendor/filament/support/src/Concerns/CanBeCopied.php) | done | — | — |
| [`hasCopyable()`](../../../packages/support/src/Components/Concerns/CanBeCopied.php) | [`CanBeCopied::hasCopyable()`](../../../vendor/filament/support/src/Concerns/CanBeCopied.php) | done | — | [semantics](#hascopyable-answers-configured-not-currently-on) |

## Semantics

### `hasCopyable()` answers "configured", not "currently on"

`hasCopyable()` is `$this->isCopyable !== false`, so a **closure** condition answers true even while it evaluates false, and an explicit `copyable(false)` answers false.
The two questions differ: `hasCopyable()` asks whether copying is configured on the component at all, and `isCopyable($state)` asks whether one rendered item gets the control.
Filament's `TextColumn` asks `hasCopyable()` before taking its optimised-HTML render path, and a closure must count there because it may answer true for some row.
No Sylament code calls `hasCopyable()`: the text and colour column templates ask `isCopyable()` per item.

### A null copyable state means "copy what the cell rendered"

`getCopyableState()` returns `null` until someone calls `copyableState()`, and that `null` is not "copy nothing": the consumer falls back to its own value.
The text column falls back to the **formatted** state, what the user can see, so a money column copies `$1,234.00` rather than `1234`, and a caller who wants the raw value asks for it with `copyableState(fn ($state) => $state)`.
The colour column falls back to the raw colour string, as Filament's `ColorColumn` does.
The fallback lives in the consuming column rather than here, exactly as Filament arranges it.

## Divergences

### The `Copied` default is an English literal

Filament's `getCopyMessage()` falls back to `__('filament::components/copyable.messages.copied')`.
i18n is [deferred toolkit-wide](../../internals/i18n.md), so the port falls back to the literal `'Copied'`, as every other user-visible default string in the toolkit does.
The `2000` ms duration default is Filament's own literal and is unchanged.
When i18n lands, this fallback is one of the strings it collects.
