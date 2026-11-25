---
description: The shared copy-to-clipboard Stimulus controller and its stylesheet, the port of the inline Alpine click handler Filament writes in every copyable component.
ports:
  - vendor/filament/support/resources/js/components/copyable.js
  - vendor/filament/tables/src/Columns/TextColumn.php
  - vendor/filament/tables/resources/css/columns/color.css
owns:
  - packages/support/assets/src/controllers/clipboard_controller.ts
  - packages/support/assets/src/styles/components/copyable.css
---
# Copyable (support)

[`clipboard_controller.ts`](../../../packages/support/assets/src/controllers/clipboard_controller.ts) ports the Alpine `x-on:click` + `$tooltip()` pair Filament writes inline in every copyable component, and [`copyable.css`](../../../packages/support/assets/src/styles/components/copyable.css) carries the two rules its chip and its live region need.
It is the client half of [`CanBeCopied`](can-be-copied.md): a template that has a copyable item renders it as `<span role="button" class="sy-copyable" …>` carrying `sylament--support--clipboard` and the trait's values, and the controller does the rest.
It is host-agnostic, with no live action, no `@symfony/ux-live-component` import and no knowledge of tables, for the reason [`sortable`](sortable.md) is: `support` is the base workspace, and a live-component peer here would land on every consumer of it.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [The click handler → a Stimulus controller](../../../packages/support/assets/src/controllers/clipboard_controller.ts) | [`TextColumn.php`](../../../vendor/filament/tables/src/Columns/TextColumn.php) `x-on:click.prevent.stop` | done | — | — |
| [`navigator.clipboard.writeText()`](../../../packages/support/assets/src/controllers/clipboard_controller.ts) | the same call, inline in that handler | done | — | [semantics](#a-non-secure-context-and-a-refused-write-both-show-the-failure-message) |
| [Keyboard operability (Enter / Space) + the stamped `tabindex`](../../../packages/support/assets/src/controllers/clipboard_controller.ts) | [`copyable.js`](../../../vendor/filament/support/resources/js/components/copyable.js)'s `isStandalone` bindings | done | — | [diverges](#the-control-is-keyboard-operable-and-its-tabindex-is-stamped-client-side) |
| [The confirmation](../../../packages/support/assets/src/controllers/clipboard_controller.ts) | `$tooltip(message, { timeout })` | done | — | [semantics](#the-live-region-is-one-element-for-the-whole-page) · [diverges](#the-confirmation-is-a-css-chip-plus-an-aria-live-region-not-a-tooltip) |
| [The failure message](../../../packages/support/assets/src/controllers/clipboard_controller.ts) | `copyable.js`'s `catch` around `writeText()`, `components/copyable.messages.failed` | done | — | [semantics](#a-non-secure-context-and-a-refused-write-both-show-the-failure-message) |
| [`.sy-copyable` cursor + focus ring](../../../packages/support/assets/src/styles/components/copyable.css) | [`columns/color.css`](../../../vendor/filament/tables/resources/css/columns/color.css) `&.fi-copyable` | done | — | — |

## Contract

What a template writes, and what the element does.
Consumers build against this.

| Piece | Value |
| --- | --- |
| Identifier | `sylament--support--clipboard`, on the element that *is* the copy control: the text item, the badge pill, the colour swatch |
| Values | `text` (String, the string written to the clipboard); `message` (String, no default: the server always passes the translated confirmation); `failureMessage` (String, no default: the translated `components/copyable.messages.failed`); `duration` (Number, default `2000`) |
| Actions | `copy`, bound explicitly to `click`, `keydown.enter` and `keydown.space`, because the element is a `span` and Stimulus binds no default event on one |
| Element contract | the template renders `role="button"` and the class `sy-copyable`; `tabindex="0"` is not server-rendered, the controller stamps it at `connect()` |
| Accessible name | the consuming template's, never the controller's ([semantics](#a-contentless-copy-control-owes-its-own-aria-label)) |
| While confirming or failing | the element carries `data-copied` and `data-copy-message="<message>"` (the confirmation, or the failure message when the write is refused) for `duration` ms, and one `div.sy-copyable-live-region` at the end of `<body>` carries the same message as its text |

## Semantics

### A non-secure context and a refused write both show the failure message

`navigator.clipboard` is undefined outside a secure context (plain `http://` on anything but `localhost`), so `writeText()` throws inside the controller's `try`, exactly as Filament's `copy()` does, and a rejected `writeText()` (the permission denied, the document not focused) takes the same path.
Both show `components/copyable.messages.failed` (*Could not copy*, in `sylament`) the way the confirmation is shown: the chip attribute, the shared live region and the same `duration`, cleared by the same reset.
The server passes it as the `failureMessage` value from the text and colour columns' templates, since the controller holds no English default.
Confirmation therefore means the write happened, which is the property the `aria-live` announcement rests on, and a user is never left with a control that does nothing.

### The live region is one element for the whole page

The region is looked up by class and created on first use, so every copyable cell shares one `div.sy-copyable-live-region` rather than each announcing into its own.
That is what makes repeated copies read correctly: a screen reader announces the region's new text each time, and a second copy replaces the first message instead of adding a second live region the user would have to hear separately.
Clearing is guarded on the region's text still equalling this controller's message, so an expiring timer does not wipe a different message another cell announced since; two cells with the same message share the text, so the earlier timer clears the later announcement early.

### A contentless copy control owes its own `aria-label`

`role="button"` names itself from the element's text content, which reads the value for a text item or a badge pill, where Filament's text column names a standalone item *Copy {label}* ([text-column.md](../tables/text-column.md#a-standalone-copy-item-is-named-by-its-text-where-filament-names-it-copy-label)), and is empty for a contentless control.
[`ColorColumn`](../tables/color-column.md)'s swatch is the live case, a `<span>` painted by `background-color` with nothing inside it, so its partial writes `aria-label` of its own, from `components/copyable.label` (*Copy {label}*, in `sylament`) or [the port-added fallback](../tables/color-column.md#an-unsanitizable-value-paints-nothing-and-a-plain-swatch-names-nothing).
The controller stamps no name, because only the template knows what the copy is of.

## Divergences

### The confirmation is a CSS chip plus an `aria-live` region, not a tooltip

Filament's `filamentCopyable` shows the message in a tippy.js tooltip and also writes it into a `span.fi-sr-only[role=status][aria-live=polite]` it places after each standalone copyable element.
Neither tippy nor Alpine ships here, and the toolkit's tooltip shape is the native `title` attribute, which cannot be shown programmatically.
The port therefore draws the chip in CSS: the element stamps `data-copied` + `data-copy-message` and `copyable.css` renders it with `&[data-copied]::after { content: attr(data-copy-message) }`.
The announcement is a live region in both; the port's is [one for the whole page](#the-live-region-is-one-element-for-the-whole-page) where Filament's is one per element.

### The control is keyboard operable, and its `tabindex` is stamped client-side

Filament's `filamentCopyable` binds `role="button"`, `tabindex="0"` and Enter and Space handlers only while the element is standalone, and withholds all three inside interactive content, such as a cell that is a link.
The port always renders `role="button"` server-side, on a `span` too, binds `keydown.enter` and `keydown.space` beside `click`, and always stamps `tabindex="0"` from `connect()`.

The stamping is not decoration: a copyable cell may render inside a record `<a>`, where interactive content and a server-rendered `tabindex` descendant are invalid HTML.
Adding the attribute after boot is the same trick Filament uses for its editable columns' toggles (`x-bind:tabindex`), and it means a page with JavaScript disabled ships valid markup carrying a control that does nothing, which is correct for a client-only capability.

`event.preventDefault()` runs beside `event.stopPropagation()` for the same reason: Space would scroll the page, and the enclosing record `<a>` or row-click handler must not fire when the user meant to copy.
