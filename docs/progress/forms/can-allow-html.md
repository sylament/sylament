---
description: The `allowHtml()` / `isHtmlAllowed()` switch that lets a string option label render raw on CheckboxList and Select.
ports:
  - vendor/filament/forms/src/Components/Concerns/CanAllowHtml.php
  - vendor/filament/forms/src/Components/CheckboxList.php
  - vendor/filament/forms/src/Components/Select.php
owns:
  - packages/forms/src/Components/Concerns/CanAllowHtml.php
---
# CanAllowHtml (forms)

`Sylament\Forms\Components\Concerns\CanAllowHtml` — the `allowHtml()` / `isHtmlAllowed()` switch that lets an option label render raw.
Two fields compose it: [`CheckboxList`](checkbox-list.md), where it opens exactly one hole in the template, and [`Select`](select.md), where it is one of the three things that force the JS control.
Filament composes it on the same two plus `MorphToSelect`, which forwards it to its type select and has no port.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`allowHtml()` / `isHtmlAllowed()`](../../../packages/forms/src/Components/Concerns/CanAllowHtml.php) | [`Concerns/CanAllowHtml.php`](../../../vendor/filament/forms/src/Components/Concerns/CanAllowHtml.php) | done | — | [semantics](#the-switch-opens-exactly-one-hole-per-field) |
| The `CheckboxList` label site | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | done | — | [semantics](#the-switch-opens-exactly-one-hole-per-field) |
| The `Select` JS-control label site, and the switch forcing that control | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [select.md](select.md#nativefalse-switches-to-the-js-control) |

## Semantics

### The switch opens exactly one hole per field

`allowHtml()` makes the **option label** render raw and nothing else.
On `CheckboxList` the description beside it keeps the template's escaping in both states, as vendor's template does (`CheckboxList.php:658-669`), and `CheckboxListTest` asserts it so that a template widening the raw site by one element fails.
The hole is stated at the sink, in a `{# Security: … #}` comment on that template's label `<span>`, matching the same comment on [the trait](../../../packages/forms/src/Components/Concerns/CanAllowHtml.php).
On `Select` the flag reaches the JS control as its `htmlAllowed` value, and the label site is the control's rendered option and item — the native `<select>` never sees it, because `allowHtml()` is one of the three conditions that [switch the field to the JS control](select.md#nativefalse-switches-to-the-js-control), a native `<option>` having no way to hold markup.

A `Markup` label renders raw **whether or not** `allowHtml()` is on, because that is what `Markup` means everywhere in this codebase.
It is the Twig-side translation of Filament's `Htmlable`, which behaves the same: Laravel's `e()` returns an `Htmlable`'s `toHtml()` unescaped, so Filament's `e($label)` passes it through.
`allowHtml()` is therefore the switch for a plain **string**, and a caller who wants one label raw can reach for `Markup` instead of opening the whole field.
The one place that clause bends is the select's JS option payload, whose default transform casts a `Markup` label to a string so raw HTML travels to the control only under `allowHtml()` ([select.md](select.md#transformoptionsforjs-casts-a-markup-label-to-text)).
