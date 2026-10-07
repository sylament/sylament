---
description: The shared copy-to-clipboard control — `packages/support/assets/src/controllers/clipboard_controller.ts`, the port of the Alpine `x-on:click` +… (draft)
ports:
  - vendor/filament/tables/src/Columns/TextColumn.php
  - vendor/filament/tables/resources/css/columns/color.css
owns:
  - packages/support/assets/src/controllers/clipboard_controller.ts
  - packages/support/assets/src/styles/components/copyable.css
---
# Copyable (support)

The shared copy-to-clipboard control — [`packages/support/assets/src/controllers/clipboard_controller.ts`](../../../packages/support/assets/src/controllers/clipboard_controller.ts), the port of the Alpine `x-on:click` + `$tooltip()` pair Filament writes inline in every copyable component, plus the two rules its chip and its live region need ([`assets/src/styles/components/copyable.css`](../../../packages/support/assets/src/styles/components/copyable.css)).
It is the client half of [`CanBeCopied`](can-be-copied.md): a template that has a copyable item renders it as `<span role="button" class="sy-copyable" …>` carrying `sylament--support--clipboard` and the trait's three values, and the controller does the rest.

It is deliberately **host-agnostic** — no live action, no `@symfony/ux-live-component` import, no knowledge of tables — for the reason [`sortable`](sortable.md) is: `support` is the base workspace and a live-component peer here would land on every consumer of it.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [The click handler → a Stimulus controller](../../../packages/support/assets/src/controllers/clipboard_controller.ts) | [`TextColumn.php`](../../../vendor/filament/tables/src/Columns/TextColumn.php) `x-on:click.prevent.stop` | done | — | — |
| [`navigator.clipboard.writeText()`](../../../packages/support/assets/src/controllers/clipboard_controller.ts) | the same call, inline in that handler | done | — | [semantics](#a-non-secure-context-is-a-no-op-not-an-error) |
| [Keyboard operability (Enter / Space) + the stamped `tabindex`](../../../packages/support/assets/src/controllers/clipboard_controller.ts) | — (Filament's copyable element has neither) | done | — | [diverges](#the-control-is-keyboard-operable-and-its-tabindex-is-stamped-client-side) |
| [The confirmation](../../../packages/support/assets/src/controllers/clipboard_controller.ts) | `$tooltip(message, { timeout })` | done | — | [semantics](#the-live-region-is-one-element-for-the-whole-page) · [diverges](#the-confirmation-is-a-css-chip-plus-an-aria-live-region-not-a-tooltip) |
| [`.sy-copyable` cursor + focus ring](../../../packages/support/assets/src/styles/components/copyable.css) | [`columns/color.css`](../../../vendor/filament/tables/resources/css/columns/color.css) `&.fi-copyable` | done | — | — |

## Contract

What a template writes, and what the element does.
Consumers build against this.

| Piece | Value |
| --- | --- |
| Identifier | `sylament--support--clipboard`, on the element that *is* the copy control — the text item, the badge pill, the colour swatch |
| Values | `text` (String, the string written to the clipboard); `message` (String, default `Copied`); `duration` (Number, default `2000`) |
| Actions | `copy` — bound explicitly to `click`, `keydown.enter` and `keydown.space`, because the element is a `span` and Stimulus binds no default event on one |
| Element contract | the template renders `role="button"` and the class `sy-copyable`; `tabindex="0"` is **not** server-rendered — the controller stamps it at `connect()` |
| Accessible name | **the consuming template's**, never the controller's. `role="button"` names itself from the element's text content, which is right for a text item or a badge pill and empty for a contentless control — [`ColorColumn`](../tables/color-column.md)'s swatch is the live case, a `<span>` painted by `background-color` with nothing inside it, so its partial writes `aria-label="Copy <colour>"` of its own. A copyable element that renders no text owes an `aria-label`; the controller stamps none, because only the template knows what the copy is *of* |
| While confirming | the element carries `data-copied` and `data-copy-message="<message>"` for `duration` ms, and one `div.sy-copyable-live-region` at the end of `<body>` carries the message as its text |

## Semantics

### A non-secure context is a no-op, not an error

`navigator.clipboard` is undefined outside a secure context (plain `http://` on anything but `localhost`), so `copy()` returns before writing and nothing is stamped — no throw, no confirmation, no console noise.
A rejected `writeText()` (the permission denied, the document not focused) takes the same path: the promise is awaited inside a `try`, and a failure returns without confirming.
Confirmation therefore means the write actually happened, which is the property the `aria-live` announcement rests on.

### The live region is one element for the whole page

The region is looked up by class and created on first use, so N copyable cells share one `div.sy-copyable-live-region` rather than each announcing into its own.
That is what makes repeated copies read correctly: a screen reader announces the region's new text each time, and a second copy replaces the first message instead of adding a second live region the user would have to hear separately.
Clearing is guarded on the text still being this controller's message, so a cell whose timer expires after another cell has already announced does not wipe the newer announcement.

## Divergences

### The confirmation is a CSS chip plus an `aria-live` region, not a tooltip

Filament calls Alpine's `$tooltip(message, { theme, timeout })` magic, which mounts a tippy.js popper.
Neither tippy nor Alpine ships here, and the toolkit's standing stance is that tooltips are the native `title` attribute — which cannot be shown programmatically.
The port therefore splits the job in two: the element stamps `data-copied` + `data-copy-message` and `copyable.css` renders the chip with `&[data-copied]::after { content: attr(data-copy-message) }`, while the message is also written into a visually-hidden `aria-live="polite"` region so the confirmation is announced rather than only drawn.
Filament's tippy tooltip happens to be both at once; two mechanisms are what it costs to get the same outcome without the dependency.

### The control is keyboard operable, and its `tabindex` is stamped client-side

Filament's copyable element is a `div`/`span` with a click handler, no `tabindex` and no key bindings — its own source comments the gap ([`ColorColumn.php`](../../../vendor/filament/tables/src/Columns/ColorColumn.php)), and it still styles `:focus-visible` for a focus it can never receive.
The port renders `role="button"` server-side, binds `keydown.enter` and `keydown.space` beside `click`, and stamps `tabindex="0"` from `connect()`.

The stamping is not decoration: a copyable cell may render **inside** a record `<a>`, where interactive content and a server-rendered `tabindex` descendant are invalid HTML.
Adding the attribute after boot is the same trick Filament uses for its editable columns' toggles (`x-bind:tabindex`), and it means a page with JavaScript disabled ships valid markup carrying a control that does nothing — which is correct, since the clipboard write is a client-only capability.

`event.preventDefault()` runs beside `event.stopPropagation()` for the same reason: Space would scroll the page and the enclosing record `<a>` or row-click handler must not fire when the user meant to copy.
