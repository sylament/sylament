# SupportIconAlias (support)

`Sylament\Support\Twig\SupportIconAlias` — the string-keyed registry of icon *alias* names apps register overrides against (through `IconManager`), so a component's default icon can be swapped without touching its template.
It ports Filament's `Filament\Support\View\SupportIconAlias`, a flat class of `const` alias literals, one per overridable icon slot across the kit.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/src/View/SupportIconAlias.php`](../../../vendor/filament/support/src/View/SupportIconAlias.php) — the full alias set (13 constants)

## Surface

Every point of this item.
_Blocked by_ names what has to land first.
_Detail_ carries **detail** → the write-up in [Details](#details), and/or **diverges** → the permanent note in [Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`SECTION_COLLAPSE_BUTTON`](../../../packages/support/src/Twig/SupportIconAlias.php) | [`SupportIconAlias::SECTION_COLLAPSE_BUTTON`](../../../vendor/filament/support/src/View/SupportIconAlias.php) | done | — | [diverges](#the-section-alias-is-a-pinned-literal-not-referenced-from-the-template) |
| [`MODAL_CLOSE_BUTTON`](../../../packages/support/src/Twig/SupportIconAlias.php) | [`SupportIconAlias::MODAL_CLOSE_BUTTON`](../../../vendor/filament/support/src/View/SupportIconAlias.php) | done | — | exercised by the close-button case in [`ModalRenderTest`](../../../packages/actions/tests/Integration/Components/ModalRenderTest.php), which renders the constant through the template; it needs no pinned-literal test because the [Modal template](../../../packages/support/templates/components/Modal/index.html.twig) references the constant itself rather than a copy of its value |
| [`PAGINATION_FIRST_BUTTON`, `PAGINATION_LAST_BUTTON`, `PAGINATION_NEXT_BUTTON`, `PAGINATION_PREVIOUS_BUTTON`](../../../packages/support/src/Twig/SupportIconAlias.php) | [`SupportIconAlias`](../../../vendor/filament/support/src/View/SupportIconAlias.php) | done | — | all four referenced through `constant()` from the [Pagination component](../../../packages/support/templates/components/Pagination/index.html.twig), the way Modal's close button is, so no pinned-literal test is needed; the rendered footer is asserted from tables' [`TableRenderingTest`](../../../packages/tables/tests/Integration/TableRenderingTest.php) |
| [`BADGE_DELETE_BUTTON`](../../../packages/support/src/Twig/SupportIconAlias.php) | [`SupportIconAlias::BADGE_DELETE_BUTTON`](../../../vendor/filament/support/src/View/SupportIconAlias.php) | done | — | referenced through `constant()` from the [Badge template](../../../packages/support/templates/components/Badge/index.html.twig), so no pinned-literal test is needed; the icon it draws is asserted by the delete-button cases in [`BadgeRenderTest`](../../../packages/actions/tests/Integration/Components/BadgeRenderTest.php) and, through the chip, by tables' [`TableRenderingTest`](../../../packages/tables/tests/Integration/TableRenderingTest.php) |
| [`BREADCRUMBS_SEPARATOR`, `BREADCRUMBS_SEPARATOR_RTL`](../../../packages/support/src/Twig/SupportIconAlias.php) | [`SupportIconAlias`](../../../vendor/filament/support/src/View/SupportIconAlias.php) | done | — | both referenced through `constant()` from the [Breadcrumbs component](../../../packages/support/templates/components/Breadcrumbs/index.html.twig), the way Modal's close button is, so neither needs a pinned-literal test; the pair is asserted from panels' [`ListRecordsTest`](../../../packages/panels/tests/Integration/Resources/Pages/ListRecordsTest.php), which counts one `sy-ltr` and one `sy-rtl` separator on the second crumb — [breadcrumbs.md](breadcrumbs.md) |
| Pagination RTL aliases (the other 4) | [`SupportIconAlias.php`](../../../vendor/filament/support/src/View/SupportIconAlias.php) | blocked | [the pagination RTL branch](pagination.md#the-footer-has-no-rtl-branch) | [detail](#details) |

## Details

Specifics for rows that are not finished — what is already in, what is left, and what the next step is.

### Pagination RTL aliases

Filament defines 13 alias constants up front — every overridable icon slot in the whole support kit.
Sylament ships **nine**: `BADGE_DELETE_BUTTON`, `SECTION_COLLAPSE_BUTTON`, `MODAL_CLOSE_BUTTON`, the four `PAGINATION_*_BUTTON`s the [Pagination component](pagination.md) reads, and the [Breadcrumbs](breadcrumbs.md) separator pair.
The remaining four — `PAGINATION_FIRST_BUTTON_RTL`, `PAGINATION_LAST_BUTTON_RTL`, `PAGINATION_NEXT_BUTTON_RTL`, `PAGINATION_PREVIOUS_BUTTON_RTL` — are the mirrored chevrons of the [RTL branch](pagination.md#the-footer-has-no-rtl-branch), which needs both i18n and panels before it can ask which direction the page runs.

An alias constant is meaningful only when a template renders through it, so each missing constant lands in the same change as its consuming component rather than being copied wholesale now — which is exactly how the four pagination aliases arrived, with the component that reads them, how `BADGE_DELETE_BUTTON` arrived with the delete button, and how the breadcrumbs pair arrived with Breadcrumbs.
Next step: add the four RTL literals as that branch is ported, referencing the constant from the template the way [Modal's close button](../../../packages/support/templates/components/Modal/index.html.twig) does rather than hardcoding the string (see the divergence).
The class flips to **done** once its constant set matches Filament's and each is exercised.

## Divergences

### The section alias is a pinned literal, not referenced from the template

Filament's Section blade wires the alias by referencing the constant directly: `:icon-alias="\Filament\Support\View\SupportIconAlias::SECTION_COLLAPSE_BUTTON"` ([section/index.blade.php](../../../vendor/filament/support/resources/views/components/section/index.blade.php)).
Sylament's [Section template](../../../packages/support/templates/components/Section/index.html.twig) hardcodes the string literal `iconAlias="section.collapse-button"` instead, and [`SupportIconAliasTest`](../../../packages/support/tests/Unit/Twig/SupportIconAliasTest.php) pins the constant to that literal — so for that one slot the class is a documentation-and-guard mirror of the alias the template already carries, not the template's single source of truth.
The Modal close button does it Filament's way instead: its template reads `:iconAlias="constant('Sylament\\Support\\Twig\\SupportIconAlias::MODAL_CLOSE_BUTTON')"`, Twig's equivalent of Blade's constant reference.
Section reverses by adopting the same `constant()` call.

_See also: the global [rename table](../renames.md) — the `View\` → `Twig\` namespace move is a systematic translation recorded there, not a divergence._
