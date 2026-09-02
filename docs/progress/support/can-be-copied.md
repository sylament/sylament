# CanBeCopied (support)

`Sylament\Support\Components\Concerns\CanBeCopied` — the shared concern that lets
a component offer its state to the clipboard: `copyable()` turns the control on,
`copyableState()` overrides what is written, and `copyMessage()` /
`copyMessageDuration()` set the confirmation and how long it stays. It ports
Filament's support-tier `CanBeCopied` method for method, including its four
state-injected getters. The client half — the `sylament--support--clipboard`
Stimulus controller a template hands these values to — is tracked separately in
[copyable.md](copyable.md).

**Status:** done — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/src/Concerns/CanBeCopied.php`](../../../vendor/filament/support/src/Concerns/CanBeCopied.php)
  — the trait ported here, method for method

## Surface

Every point of this concern. _Blocked by_ names what has to land first. _Detail_
carries **detail** → the write-up in [Details](#details), deleted once the row is
**done**; **semantics** → the permanent note in [Semantics](#semantics);
**diverges** → the permanent note in [Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`copyable()` + `isCopyable()`](../../../packages/support/src/Components/Concerns/CanBeCopied.php) | [`CanBeCopied::copyable()`](../../../vendor/filament/support/src/Concerns/CanBeCopied.php) | done | — | — |
| [`copyableState()` + `getCopyableState()`](../../../packages/support/src/Components/Concerns/CanBeCopied.php) | [`CanBeCopied::copyableState()`](../../../vendor/filament/support/src/Concerns/CanBeCopied.php) | done | — | [semantics](#a-null-copyable-state-means-copy-what-the-cell-rendered) |
| [`copyMessage()` + `getCopyMessage()`](../../../packages/support/src/Components/Concerns/CanBeCopied.php) | [`CanBeCopied::copyMessage()`](../../../vendor/filament/support/src/Concerns/CanBeCopied.php) | done | — | [diverges](#the-copied-default-is-an-english-literal) |
| [`copyMessageDuration()` + `getCopyMessageDuration()`](../../../packages/support/src/Components/Concerns/CanBeCopied.php) | [`CanBeCopied::copyMessageDuration()`](../../../vendor/filament/support/src/Concerns/CanBeCopied.php) | done | — | — |
| [`hasCopyable()`](../../../packages/support/src/Components/Concerns/CanBeCopied.php) | [`CanBeCopied::hasCopyable()`](../../../vendor/filament/support/src/Concerns/CanBeCopied.php) | done | — | [semantics](#hascopyable-answers-configured-not-currently-on) |

## Details

Every row is **done**, exercised by
[`CanBeCopiedTest`](../../../packages/support/tests/Unit/Components/Concerns/CanBeCopiedTest.php)
— the defaults, the state injection on all four getters, and the two fallbacks.
No row has a write-up here.

## Semantics

### `hasCopyable()` answers "configured", not "currently on"

`hasCopyable()` is `$this->isCopyable !== false`, so a **closure** condition
answers true even while it evaluates false, and an explicit `copyable(false)`
answers false. The two are not interchangeable: a template asks `hasCopyable()`
to decide whether the copy control is part of this component's markup at all,
and asks `isCopyable($state)` per rendered item to decide whether *that* item
gets it. A dynamic condition must therefore report "configured" up front, or a
row whose closure happens to answer false for the first record would render a
component that can never copy any of them.

### A null copyable state means "copy what the cell rendered"

`getCopyableState()` returns `null` until someone calls `copyableState()`, and
that `null` is not "copy nothing" — the consumer falls back to the **formatted**
state, i.e. what the user can see, not the raw record value. So a money column
copies `$1,234.00` rather than `1234`, and a caller who wants the raw value asks
for it explicitly with `copyableState(fn ($state) => $state)`. The fallback lives
in the consuming column rather than here, exactly as Filament arranges it.

## Divergences

### The `Copied` default is an English literal

Filament's `getCopyMessage()` falls back to
`__('filament::components/copyable.messages.copied')`. i18n is deferred
(the M3 · 1 decision, [roadmap](../roadmap.md)), so the port falls back to the
literal `'Copied'`, the same way every other user-visible default string in the
toolkit does. The `2000` ms duration default is Filament's own literal and is
unchanged. When i18n lands, this fallback is one of the strings it collects.
