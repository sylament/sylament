---
description: The anonymous `<twig:SylamentSupport:Breadcrumbs>` Twig component and its stylesheet, the port of Filament's `<x-filament::breadcrumbs>`.
ports:
  - vendor/filament/support/resources/views/components/breadcrumbs.blade.php
  - vendor/filament/support/resources/css/components/breadcrumbs.css
owns:
  - packages/support/assets/src/styles/components/breadcrumbs.css
  - packages/support/templates/components/Breadcrumbs/
---
# Breadcrumbs (support)

[`Breadcrumbs/index.html.twig`](../../../packages/support/templates/components/Breadcrumbs/index.html.twig) is the anonymous Twig component `<twig:SylamentSupport:Breadcrumbs>`.
It renders one `<nav>` holding an `<ol>` of crumbs, each crumb either a link or a plain label, separated by a direction-aware chevron pair.
It takes a single `breadcrumbs` prop, a map whose key is the URL and whose value is the label, and the key's kind, not its content, decides whether the crumb links.
Filament's breadcrumbs Blade has no PHP class behind it, so the port is the view and its stylesheet.
Panels' [`Header`](../panels/layout-templates.md) renders it, filled from `Page::getBreadcrumbs()`, and the panels suite holds its render tests, because support's fixture kernel registers no TwigComponentBundle.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Root `<nav aria-label>` + `<ol>` list](../../../packages/support/templates/components/Breadcrumbs/index.html.twig) | [`<nav>` / `fi-breadcrumbs-list`](../../../vendor/filament/support/resources/views/components/breadcrumbs.blade.php) | done | — | [diverges](#the-navigation-label-is-a-literal-not-a-translation-key) |
| [Key kind decides link vs label](../../../packages/support/templates/components/Breadcrumbs/index.html.twig) | [`@if (is_int($url))`](../../../vendor/filament/support/resources/views/components/breadcrumbs.blade.php) | done | — | [semantics](#a-digits-only-key-is-the-twig-side-spelling-of-is_int) · [diverges](#two-unreachable-key-shapes-link-where-filament-does-not) |
| [Separator pair from the second crumb on](../../../packages/support/templates/components/Breadcrumbs/index.html.twig) | [`@if (! $loop->first)` + the two `generate_icon_html()` calls](../../../vendor/filament/support/resources/views/components/breadcrumbs.blade.php) | done | — | — |
| [`BREADCRUMBS_SEPARATOR` / `_RTL` overrides](../../../packages/support/src/Twig/SupportIconAlias.php) | [`SupportIconAlias::BREADCRUMBS_SEPARATOR`, `…_RTL`](../../../vendor/filament/support/src/View/SupportIconAlias.php) | done | — | [support-icon-alias.md](support-icon-alias.md) |
| [`aria-current="page"` on the last crumb](../../../packages/support/templates/components/Breadcrumbs/index.html.twig) | [`@if ($loop->last)`](../../../vendor/filament/support/resources/views/components/breadcrumbs.blade.php) | done | — | — |
| [`breadcrumbs.css`](../../../packages/support/assets/src/styles/components/breadcrumbs.css) | [`components/breadcrumbs.css`](../../../vendor/filament/support/resources/css/components/breadcrumbs.css) | done | — | — |

## Semantics

### A digits-only key is the Twig-side spelling of `is_int()`

Filament branches on `is_int($url)`, and PHP's array semantics are what make that a kind test rather than a content test: a key written as the numeric string `'12'` is cast to the integer `12` on insertion, so `is_int()` answers true for both spellings and a caller cannot opt a numeric label into the link arm by quoting it.
Twig has no `is_int` test that survives the key coercion, because `{% for url, label in … %}` hands back whatever PHP stored, so the port asks the equivalent question of the stringified key:

```twig
{% if url matches '/^\\d+$/' %}
```

**The double backslash is load-bearing, and getting it wrong fails no gate.**
`Twig\Lexer::stripcslashes()` processes escape sequences inside single-quoted strings too, so `'/^\\d+$/'` reaches the regex engine as `/^\d+$/`.
Written with one backslash, the lexer drops the backslash and raises only a deprecation notice, so the pattern reaches the engine as `/^d+$/`, matching the literal letter `d`: every crumb takes the link arm and the last crumb becomes an anchor to its own label text.
The single-backslash form compiles and renders, the suites do not fail on deprecations, and the only guard is the panels DOM assertion that an int-keyed crumb renders a `<span>`.

## Divergences

### The navigation label is a literal, not a translation key

Filament merges `__('filament::components/breadcrumbs.label')` onto the `<nav>`; the port merges the literal `'Breadcrumbs'`, as [`Pagination`](pagination.md) does with its `aria-label="Pagination"`.
The port ships no translation layer, so every user-facing literal is English at the point of use and moves in one pass when i18n lands.
A caller can still override it: `attrs()` lets a later source win for a scalar attribute, so `<twig:SylamentSupport:Breadcrumbs aria-label="…" />` replaces it.

### Two unreachable key shapes link where Filament does not

`is_int()` and `/^\d+$/` agree on every key a caller can realistically produce, and disagree on exactly two:

- A **negative** integer key.
  `is_int(-1)` is true, so Filament renders a `<span>`; `-1` stringifies to `-1`, which the pattern rejects, so the port renders `<a href="-1">`.
  PHP's array append only mints non-negative keys, and a breadcrumb trail hand-writing a negative one has nothing to express by it.
- An **empty-string** key.
  Both sides take the anchor arm, but Filament's `generate_href_html('')` returns an empty string for a `blank()` URL, so its anchor carries no `href` at all, where the port renders `href=""`: `attrs()` drops only `null` and `false`, and an empty string is a scalar that renders.

Neither is worth a guard: one would trade a rule a reader can check against `is_int()` in their head for a three-armed condition guarding cases nothing reaches.
It is the one place the ported rule is not an exact restatement of the original.
