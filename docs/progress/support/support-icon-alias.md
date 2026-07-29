# SupportIconAlias (support)

`Sylament\Support\Twig\SupportIconAlias` — the string-keyed registry of icon
*alias* names apps register overrides against (through `IconManager`), so a
component's default icon can be swapped without touching its template. It ports
Filament's `Filament\Support\View\SupportIconAlias`, a flat class of `const`
alias literals, one per overridable icon slot across the kit.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/src/View/SupportIconAlias.php`](../../../vendor/filament/support/src/View/SupportIconAlias.php)
  — the full alias set (13 constants)

## Surface

Every point of this item. _Blocked by_ names what has to land first. _Detail_
carries **detail** → the write-up in [Details](#details), and/or **diverges** →
the permanent note in [Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`SECTION_COLLAPSE_BUTTON`](../../../packages/support/src/Twig/SupportIconAlias.php) | [`SupportIconAlias::SECTION_COLLAPSE_BUTTON`](../../../vendor/filament/support/src/View/SupportIconAlias.php) | done | — | [diverges](#the-section-alias-is-a-pinned-literal-not-referenced-from-the-template) |
| [`MODAL_CLOSE_BUTTON`](../../../packages/support/src/Twig/SupportIconAlias.php) | [`SupportIconAlias::MODAL_CLOSE_BUTTON`](../../../vendor/filament/support/src/View/SupportIconAlias.php) | done | — | exercised by the close-button case in [`ModalRenderTest`](../../../packages/actions/tests/Integration/Components/ModalRenderTest.php), which renders the constant through the template; it needs no pinned-literal test because the [Modal template](../../../packages/support/templates/components/Modal/index.html.twig) references the constant itself rather than a copy of its value |
| Badge / breadcrumbs / pagination aliases (the other 11) | [`SupportIconAlias.php`](../../../vendor/filament/support/src/View/SupportIconAlias.php) | blocked | the [Badge](badge.md) delete button, Breadcrumbs, Pagination | [detail](#details) |

## Details

Specifics for rows that are not finished — what is already in, what is left, and
what the next step is.

### Badge / breadcrumbs / pagination aliases

Filament defines 13 alias constants up front — every overridable icon slot in
the whole support kit. Sylament ships **two**: `SECTION_COLLAPSE_BUTTON` and
`MODAL_CLOSE_BUTTON`, the slots whose consuming components (Section, Modal) are
ported. The remaining eleven all belong to components that have not been built
yet:

- `BADGE_DELETE_BUTTON` — the [Badge](badge.md) delete button (todo). The Badge
  component itself is now ported; its delete button is the unbuilt part, and it
  is the only thing this alias names.
- `BREADCRUMBS_SEPARATOR`, `BREADCRUMBS_SEPARATOR_RTL` — Breadcrumbs (todo)
- `PAGINATION_FIRST_BUTTON`(`_RTL`), `PAGINATION_LAST_BUTTON`(`_RTL`),
  `PAGINATION_NEXT_BUTTON`(`_RTL`), `PAGINATION_PREVIOUS_BUTTON`(`_RTL`) —
  Pagination (todo)

An alias constant is meaningful only when a template renders through it, so each
missing constant lands in the same change as its consuming component rather than
being copied wholesale now. Next step: add each literal as Badge, Breadcrumbs,
and Pagination are ported, referencing the constant from the template the way
[Modal's close button](../../../packages/support/templates/components/Modal/index.html.twig)
does rather than hardcoding the string (see the divergence). The class flips to
**done** once its constant set matches Filament's and each is exercised.

## Divergences

### The section alias is a pinned literal, not referenced from the template

Filament's Section blade wires the alias by referencing the constant directly:
`:icon-alias="\Filament\Support\View\SupportIconAlias::SECTION_COLLAPSE_BUTTON"`
([section/index.blade.php](../../../vendor/filament/support/resources/views/components/section/index.blade.php)).
Sylament's [Section template](../../../packages/support/templates/components/Section/index.html.twig)
hardcodes the string literal `iconAlias="section.collapse-button"` instead, and
[`SupportIconAliasTest`](../../../packages/support/tests/Unit/Twig/SupportIconAliasTest.php)
pins the constant to that literal — so for that one slot the class is a
documentation-and-guard mirror of the alias the template already carries, not the
template's single source of truth. The Modal close button does it Filament's way
instead: its template reads
`:iconAlias="constant('Sylament\\Support\\Twig\\SupportIconAlias::MODAL_CLOSE_BUTTON')"`,
Twig's equivalent of Blade's constant reference. Section reverses by adopting the
same `constant()` call.

_See also: the global [rename table](../renames.md) — the `View\` → `Twig\`
namespace move is a systematic translation recorded there, not a divergence._
