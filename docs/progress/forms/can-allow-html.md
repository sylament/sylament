# CanAllowHtml (forms)

`Sylament\Forms\Components\Concerns\CanAllowHtml` — the `allowHtml()` /
`isHtmlAllowed()` switch that lets an option label render raw. Two fields
compose it — [`CheckboxList`](checkbox-list.md), where it opens exactly one hole
in the template, and [`Select`](select.md), where it is one of the three things
that force the JS control — so it is promoted out of the former's fold to a file
of its own per [one row per file](../README.md#one-row-per-file-one-file-per-thing).
Filament composes it on the same two plus `ToggleButtons`, `Radio`'s infolist
twin and several table columns, unchanged between them.

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`forms/src/Components/Concerns/CanAllowHtml.php`](../../../vendor/filament/forms/src/Components/Concerns/CanAllowHtml.php)
  — the trait
- [`forms/src/Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php),
  [`Select.php`](../../../vendor/filament/forms/src/Components/Select.php) — the
  two composers, and where each reads the flag

## Surface

_Blocked by_ names what has to land first. _Detail_ links a permanent note in
[Semantics](#semantics), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`allowHtml()` / `isHtmlAllowed()`](../../../packages/forms/src/Components/Concerns/CanAllowHtml.php) | [`Concerns/CanAllowHtml.php`](../../../vendor/filament/forms/src/Components/Concerns/CanAllowHtml.php) | done | — | [semantics](#the-switch-opens-exactly-one-hole-per-field) |
| The `CheckboxList` label site | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | done | — | [semantics](#the-switch-opens-exactly-one-hole-per-field) |
| The `Select` JS-control label site, and the switch forcing that control | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [select.md](select.md#nativefalse-switches-to-the-js-control) |

Coverage: the default and both arms of the switch by
[`CanAllowHtmlTest`](../../../packages/forms/tests/Unit/Components/Concerns/CanAllowHtmlTest.php);
the checkbox list's raw label beside its still-escaped description by its own
suite ([checkbox-list.md](checkbox-list.md)); `allowHtml()` forcing the select's
JS branch by
[`SelectTest`](../../../packages/forms/tests/Integration/Components/SelectTest.php),
whose render-contract cases also pin what that branch does with a raw label.

## Semantics

### The switch opens exactly one hole per field

`allowHtml()` makes the **option label** render raw and nothing else. On
`CheckboxList` the description beside it keeps the template's escaping in both
states, which is vendor's own escaping map (`CheckboxList.php:606-615`) and the
assertion that would catch a template widening the raw site by one element. The
hole is stated at the sink, in a `{# Security: … #}` comment on that template's
label `<span>`, matching the same comment on
[the trait](../../../packages/forms/src/Components/Concerns/CanAllowHtml.php).
On `Select` the flag reaches the JS control as its `htmlAllowed` value, and the
label site is the control's rendered option and item — the native `<select>`
never sees it, because `allowHtml()` is one of the three conditions that
[switch the field to the JS control](select.md#nativefalse-switches-to-the-js-control),
a native `<option>` having no way to hold markup.

One port-specific clause: a `Markup` label renders raw **whether or not**
`allowHtml()` is on, because that is what `Markup` means everywhere in this
codebase — the Twig-side translation of Filament's `Htmlable`, which their
`e($label)` would have escaped. `allowHtml()` is therefore the switch for a
plain **string**, and a caller who wants one label raw can reach for `Markup`
instead of opening the whole field. The one place that clause bends is the
select's JS option payload, whose default transform casts a `Markup` label to a
string so raw HTML travels to the control only under `allowHtml()`
([select.md](select.md#transformoptionsforjs-casts-a-markup-label-to-text)).
