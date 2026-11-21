---
description: The shared concern that offers a component's state to the clipboard, with the copied text, the confirmation message and how long it stays.
ports:
  - vendor/filament/support/src/Concerns/CanBeCopied.php
owns:
  - packages/support/src/Components/Concerns/CanBeCopied.php
---
# CanBeCopied (support)

`Sylament\Support\Components\Concerns\CanBeCopied` lets a component offer its state to the clipboard: `copyable()` turns the control on, `copyableState()` overrides what is written, and `copyMessage()` / `copyMessageDuration()` set the confirmation and how long it stays.
It ports Filament's support-tier `CanBeCopied` method for method, including its four state-injected getters, which [inject no related record](#the-getters-inject-the-state-and-no-related-record).
The client half, the `sylament--support--clipboard` Stimulus controller a template hands these values to, is [copyable.md](copyable.md).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`copyable()` + `isCopyable()`](../../../packages/support/src/Components/Concerns/CanBeCopied.php) | [`CanBeCopied::copyable()`](../../../vendor/filament/support/src/Concerns/CanBeCopied.php) | done | — | [diverges](#the-getters-inject-the-state-and-no-related-record) |
| [`copyableState()` + `getCopyableState()`](../../../packages/support/src/Components/Concerns/CanBeCopied.php) | [`CanBeCopied::copyableState()`](../../../vendor/filament/support/src/Concerns/CanBeCopied.php) | done | — | [semantics](#a-null-copyable-state-means-copy-what-the-cell-rendered) · [diverges](#the-getters-inject-the-state-and-no-related-record) |
| [`copyMessage()` + `getCopyMessage()`](../../../packages/support/src/Components/Concerns/CanBeCopied.php) | [`CanBeCopied::copyMessage()`](../../../vendor/filament/support/src/Concerns/CanBeCopied.php) | done | — | [semantics](#the-confirmation-is-translated-before-the-template-reads-it) · [diverges](#the-getters-inject-the-state-and-no-related-record) |
| [`copyMessageDuration()` + `getCopyMessageDuration()`](../../../packages/support/src/Components/Concerns/CanBeCopied.php) | [`CanBeCopied::copyMessageDuration()`](../../../vendor/filament/support/src/Concerns/CanBeCopied.php) | done | — | [diverges](#the-getters-inject-the-state-and-no-related-record) |
| [`hasCopyable()`](../../../packages/support/src/Components/Concerns/CanBeCopied.php) | [`CanBeCopied::hasCopyable()`](../../../vendor/filament/support/src/Concerns/CanBeCopied.php) | done | — | [semantics](#hascopyable-answers-configured-not-currently-on) |

## Semantics

### `hasCopyable()` answers "configured", not "currently on"

`hasCopyable()` is `$this->isCopyable !== false`, so a **closure** condition answers true even while it evaluates false, and an explicit `copyable(false)` answers false.
The two questions differ: `hasCopyable()` asks whether copying is configured on the component at all, and `isCopyable($state)` asks whether one rendered item gets the control.
Filament's `TextColumn` asks `hasCopyable()` before taking its optimised-HTML render path, and a closure must count there because it may answer true for some row.
No Sylament code calls `hasCopyable()`: the text and colour column templates ask `isCopyable()` per item.

### A null copyable state means "copy what the cell rendered"

`getCopyableState()` returns `null` until someone calls `copyableState()`, and that `null` is not "copy nothing": the consumer falls back to its own value.
The fallback lives in the consuming column rather than here, exactly as Filament arranges it: [the text column's](../tables/text-column.md#the-copy-target-is-the-content-element-and-the-badge-pill-when-there-is-one) and [the colour column's](../tables/color-column.md#the-copy-values-are-resolved-only-for-a-copyable-swatch).

### The confirmation is translated before the template reads it

`copyMessage()` takes a string, a `TranslatableInterface` or a closure returning either, and falls back to a `TranslatableMessage` for Filament's `components/copyable.messages.copied` key in the `sylament` catalogue, `Copied` in Filament's English.
`getCopyMessage()` always answers the translated string, because the clipboard controller takes it as a Stimulus value and holds no default of its own.
The fallback is a message, so a column built with `new` and asked for its confirmation throws the [`LogicException`](component.md#a-message-is-translated-through-an-injected-translator-which-a-new-built-component-lacks) a message always throws there; a rendered column is container-built and never does.
`copyableState()` is clipboard data rather than interface text, so a string there is never a key and a message is not accepted.

## Divergences

### The getters inject the state and no related record

Filament's `isCopyable()`, `getCopyableState()`, `getCopyMessage()` and `getCopyMessageDuration()` each take a `?Model $relatedRecord` beside the state and inject it into their closures as `$relatedRecord`.
The port's four take the state alone, because no column of the port resolves a related record per item for them to hand over.
The parameter is dropped rather than renamed, so `renames.md` carries no entry for it.
