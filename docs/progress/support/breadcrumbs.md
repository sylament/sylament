---
description: The anonymous Twig component (`<twig:SylamentSupport:Breadcrumbs>`) that ports Filament's `<x-filament::breadcrumbs>`. (draft)
ports:
  - vendor/filament/support/resources/views/components/breadcrumbs.blade.php
  - vendor/filament/support/resources/css/components/breadcrumbs.css
owns:
  - packages/support/assets/src/styles/components/breadcrumbs.css
  - packages/support/templates/components/Breadcrumbs/
---
# Breadcrumbs (support)

`packages/support/templates/components/Breadcrumbs/index.html.twig` — the anonymous Twig component (`<twig:SylamentSupport:Breadcrumbs>`) that ports Filament's `<x-filament::breadcrumbs>`.
It renders one `<nav>` holding an `<ol>` of crumbs, each crumb either a link or a plain label, separated by a direction-aware chevron pair.
It takes a single `breadcrumbs` prop — a map whose **key** is the URL and whose **value** is the label — and the key's *kind*, not its content, decides whether the crumb links.

The component ships whole.
Unlike the trigger primitives beside it, Filament's breadcrumbs Blade has **no PHP class behind it** — no colour map, no size resolution, nothing but markup — so a faithful port is the view and its stylesheet, with no interactive layer left to defer.
Its only consumer today is panels' [`Header`](../panels/layout-templates.md), which fills it from `Page::getBreadcrumbs()`.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Root `<nav aria-label>` + `<ol>` list](../../../packages/support/templates/components/Breadcrumbs/index.html.twig) | [`<nav>` / `fi-breadcrumbs-list`](../../../vendor/filament/support/resources/views/components/breadcrumbs.blade.php) | done | — | [diverges](#the-navigation-label-is-a-literal-not-a-translation-key) |
| [Key kind decides link vs label](../../../packages/support/templates/components/Breadcrumbs/index.html.twig) | [`@if (is_int($url))`](../../../vendor/filament/support/resources/views/components/breadcrumbs.blade.php) | done | — | [semantics](#a-digits-only-key-is-the-twig-side-spelling-of-is_int) · [diverges](#two-unreachable-key-shapes-link-where-filament-does-not) |
| [Separator pair from the second crumb on](../../../packages/support/templates/components/Breadcrumbs/index.html.twig) | [`@if (! $loop->first)` + the two `generate_icon_html()` calls](../../../vendor/filament/support/resources/views/components/breadcrumbs.blade.php) | done | — | — |
| [`BREADCRUMBS_SEPARATOR` / `_RTL` overrides](../../../packages/support/src/Twig/SupportIconAlias.php) | [`SupportIconAlias::BREADCRUMBS_SEPARATOR`, `…_RTL`](../../../vendor/filament/support/src/View/SupportIconAlias.php) | done | — | [support-icon-alias.md](support-icon-alias.md) |
| [`aria-current="page"` on the last crumb](../../../packages/support/templates/components/Breadcrumbs/index.html.twig) | [`@if ($loop->last)`](../../../vendor/filament/support/resources/views/components/breadcrumbs.blade.php) | done | — | — |
| [`breadcrumbs.css`](../../../packages/support/assets/src/styles/components/breadcrumbs.css) | [`components/breadcrumbs.css`](../../../vendor/filament/support/resources/css/components/breadcrumbs.css) | done | — | — |

Every row is exercised from **panels**, not from support — support's own fixture Kernel registers no TwigComponentBundle, so a shipped component's render tests live wherever its first consumer does, the split [pagination.md](pagination.md) and [badge.md](badge.md) record.
The trail assertions in [`ListRecordsTest`](../../../packages/panels/tests/Integration/Resources/Pages/ListRecordsTest.php) pin the whole component in one selector chain — a string-keyed crumb rendering `<a href>`, an int-keyed one rendering `<span>` with no anchor in that item, `aria-current="page"` present exactly once and only on the last crumb, one `sy-ltr` and one `sy-rtl` separator on the second item and none on the first, and the root `nav.sy.sy-breadcrumbs[aria-label="Breadcrumbs"]` that proves the `attrs()` merge — with the create and edit pages ([`CreateRecordTest`](../../../packages/panels/tests/Integration/Resources/Pages/CreateRecordTest.php), [`EditRecordTest`](../../../packages/panels/tests/Integration/Resources/Pages/EditRecordTest.php)) covering their own last crumb and [`PageControllerTest`](../../../packages/panels/tests/Integration/Controllers/PageControllerTest.php) asserting that a page contributing no crumbs renders no `nav` at all.

## Semantics

### A digits-only key is the Twig-side spelling of `is_int()`

Filament branches on `is_int($url)`, and PHP's array semantics are what make that a *kind* test rather than a content test: a key written as the numeric string `'12'` is silently cast to the integer `12` on insertion, so `is_int()` answers true for both spellings and a caller cannot opt a numeric label into the link arm by quoting it.
Twig has no `is_int` test that survives the key coercion — `{% for url, label in … %}` hands back whatever PHP stored — so the port asks the equivalent question of the stringified key:

```twig
{% if url matches '/^\\d+$/' %}
```

**The double backslash is load-bearing, and getting it wrong fails silently.**
`Twig\Lexer::stripcslashes()` processes escape sequences inside *single*-quoted strings too, so `'/^\\d+$/'` reaches the regex engine as `/^\d+$/`.
Written with one backslash it reaches the engine as `/^d+$/` — a pattern matching the literal letter `d` — so **every** crumb takes the link arm, the last crumb becomes an anchor to its own label text, and nothing in the toolchain says a word: the single-backslash form is valid Twig, compiles, renders, and the only guard against it is the panels DOM assertion that an int-keyed crumb renders a `<span>`.
Anyone "simplifying" the escape breaks the key-kind rule without a failing gate.

## Divergences

### The navigation label is a literal, not a translation key

Filament merges `__('filament::components/breadcrumbs.label')` onto the `<nav>`; ours merges the literal `'Breadcrumbs'`, the same call [`Pagination`](pagination.md) made with its `aria-label="Pagination"`.
There is no translation layer in the port yet, so every user-facing literal is English at the point of use and moves in one pass when i18n lands.
A caller can still override it — `attrs()` lets a later source win for a scalar attribute, so `<twig:SylamentSupport:Breadcrumbs aria-label="…" />` replaces ours.

### Two unreachable key shapes link where Filament does not

`is_int()` and `/^\d+$/` agree on every key a caller can realistically produce, and disagree on exactly two:

- A **negative** integer key.
  `is_int(-1)` is true, so Filament renders a `<span>`; `-1` stringifies to `-1`, which the pattern rejects, so ours renders `<a href="-1">`.
  PHP's array append only ever mints non-negative keys, and a breadcrumb trail hand-writing a negative one has no meaning to express by it.
- An **empty-string** key.
  Both sides take the anchor arm, but Filament's `generate_href_html('')` returns an empty string for a `blank()` url, so its anchor carries no `href` at all, where ours renders `href=""` — `attrs()` drops only `null` and `false`, and an empty string is a scalar that renders.

Neither is worth a guard: adding one would trade a rule a reader can check against `is_int()` in their head for a three-armed condition guarding cases nothing reaches.
Recorded because it is the one place the ported rule is not an exact restatement of the original.
