---
description: The icon alias names an app registers overrides against through IconManager, so a component's default icon can be swapped without touching its template.
ports:
  - vendor/filament/support/src/View/SupportIconAlias.php
owns:
  - packages/support/src/Twig/SupportIconAlias.php
---
# SupportIconAlias (support)

`Sylament\Support\Twig\SupportIconAlias` ports Filament's `Filament\Support\View\SupportIconAlias`, a flat class of `const` alias literals, one per overridable icon slot in support's components; [`icon-manager.md`](icon-manager.md) resolves the overrides.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`SECTION_COLLAPSE_BUTTON`](../../../packages/support/src/Twig/SupportIconAlias.php) | [`SupportIconAlias::SECTION_COLLAPSE_BUTTON`](../../../vendor/filament/support/src/View/SupportIconAlias.php) | done | — | [diverges](#the-section-alias-is-a-pinned-literal-not-referenced-from-the-template) |
| [`MODAL_CLOSE_BUTTON`](../../../packages/support/src/Twig/SupportIconAlias.php) | [`SupportIconAlias::MODAL_CLOSE_BUTTON`](../../../vendor/filament/support/src/View/SupportIconAlias.php) | done | — | — |
| [`PAGINATION_FIRST_BUTTON`, `PAGINATION_LAST_BUTTON`, `PAGINATION_NEXT_BUTTON`, `PAGINATION_PREVIOUS_BUTTON`](../../../packages/support/src/Twig/SupportIconAlias.php) | [`SupportIconAlias`](../../../vendor/filament/support/src/View/SupportIconAlias.php) | done | — | — |
| [`BADGE_DELETE_BUTTON`](../../../packages/support/src/Twig/SupportIconAlias.php) | [`SupportIconAlias::BADGE_DELETE_BUTTON`](../../../vendor/filament/support/src/View/SupportIconAlias.php) | done | — | — |
| [`BREADCRUMBS_SEPARATOR`, `BREADCRUMBS_SEPARATOR_RTL`](../../../packages/support/src/Twig/SupportIconAlias.php) | [`SupportIconAlias`](../../../vendor/filament/support/src/View/SupportIconAlias.php) | done | — | — |
| `PAGINATION_FIRST_BUTTON_RTL`, `PAGINATION_LAST_BUTTON_RTL`, `PAGINATION_NEXT_BUTTON_RTL`, `PAGINATION_PREVIOUS_BUTTON_RTL` | [`SupportIconAlias`](../../../vendor/filament/support/src/View/SupportIconAlias.php) | blocked | [the pagination RTL branch](pagination.md#the-footer-has-no-rtl-branch) | [detail](#the-four-pagination-rtl-aliases-wait-on-the-footers-rtl-branch) · [diverges](#an-alias-constant-lands-with-the-template-that-reads-it) |

## Details

### The four pagination RTL aliases wait on the footer's RTL branch

`PAGINATION_FIRST_BUTTON_RTL`, `PAGINATION_LAST_BUTTON_RTL`, `PAGINATION_NEXT_BUTTON_RTL` and `PAGINATION_PREVIOUS_BUTTON_RTL` are the mirrored chevrons of [Pagination's RTL branch](pagination.md#the-footer-has-no-rtl-branch), which the port does not render.
They land with that branch, referenced from the template through `constant()`.

## Divergences

### An alias constant lands with the template that reads it

Filament defines every alias constant of the kit up front.
The port adds a constant in the same change as the component that renders its icon, because an alias is meaningful only when a template renders through it; an unread constant would advertise an override that does nothing.
The class reaches Filament's constant set once every slot's component ships.

### The section alias is a pinned literal, not referenced from the template

Filament's Section blade wires the alias by referencing the constant: `:icon-alias="\Filament\Support\View\SupportIconAlias::SECTION_COLLAPSE_BUTTON"` ([section/index.blade.php](../../../vendor/filament/support/resources/views/components/section/index.blade.php)).
Sylament's [Section template](../../../packages/support/templates/components/Section/index.html.twig) hardcodes the literal `iconAlias="section.collapse-button"` instead, and [`SupportIconAliasTest`](../../../packages/support/tests/Unit/Twig/SupportIconAliasTest.php) pins the constant to that literal, so for this one slot the class mirrors the alias the template carries rather than being its single source.
The Modal, Pagination, Badge and Breadcrumbs templates do it Filament's way, with `constant('Sylament\\Support\\Twig\\SupportIconAlias::…')`, Twig's equivalent of Blade's constant reference.
Section reverses by adopting the same `constant()` call.
