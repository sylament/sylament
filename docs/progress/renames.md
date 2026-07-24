# Rename table

The single global record of API renames. Preserve Filament's API names by
default — migrating users grep for them. Rename only when Filament itself is
inconsistent and the better name already exists in their codebase; record every
rename here in the same change.

Two questions this file answers:

1. **Did this Filament name change?** → [Renames](#renames). If it isn't in that
   table, the name carries over unchanged.
2. **Where does this namespace or idiom land?** → [Not renames](#not-renames).
   Those are systematic and derivable, so they are documented, not enumerated.

## Renames

Sorted by Filament name. Every row is a name a migrating user could **not**
derive from the conventions below.

| Filament | Sylament | Kind | Home | Why |
| --- | --- | --- | --- | --- |
| `Filament\Actions\Concerns\InteractsWithActions` | `HasActions` | trait | `Sylament\Actions\Concerns\HasActions` | [Swapped](#interface-and-trait-names-are-swapped) — `Has*` matches the house trait prefix (`HasName`, `HasAction`, …). |
| `Filament\Actions\Contracts\HasActions` | `InteractsWithActions` | interface | `Sylament\Actions\Contracts\InteractsWithActions` | [Swapped](#interface-and-trait-names-are-swapped) — the interface names the behavior the host implements. |
| `Filament\Schemas\Concerns\InteractsWithSchemas` | `HasSchemas` | trait | `Sylament\Schemas\Concerns\HasSchemas` | [Swapped](#interface-and-trait-names-are-swapped) — `Has*` matches the house trait prefix (`HasComponents`, `HasKey`, `HasGap`, …). |
| `Filament\Schemas\Contracts\HasSchemas` | `InteractsWithSchemas` | interface | `Sylament\Schemas\Contracts\InteractsWithSchemas` | [Swapped](#interface-and-trait-names-are-swapped) — the interface names the behavior. |
| `Section::formBefore()` / `isFormBefore()` | `contentBefore()` / `hasContentBefore()` | method | `Sylament\Schemas\Components\Concerns\HasContentBefore` | Adopts the `contentBefore` prop name Filament's own support-layer section component uses. |

### Interface and trait names are swapped

⚠️ **Grep trap.** For both schemas and actions, `Has*` and `InteractsWith*`
exist on **both** sides with **opposite** meanings:

| | Filament | Sylament |
| --- | --- | --- |
| **interface** | `HasSchemas` / `HasActions` | `InteractsWithSchemas` / `InteractsWithActions` |
| **trait** | `InteractsWithSchemas` / `InteractsWithActions` | `HasSchemas` / `HasActions` |

Here `Has*` is reserved for traits, matching every other trait in the codebase,
and the interface names the behavior the host implements. Porting code that
mentions either name means checking which one it is first.

## Not renames

Systematic translations that follow the conventions in
[CLAUDE.md](../../CLAUDE.md). They are **not** renames and must not be added to
the table above.

### Idiom

| Laravel / Filament | Sylament | Example |
| --- | --- | --- |
| `Htmlable` | `Twig\Markup` | — |
| Livewire | UX LiveComponent | `getLivewire()` → `getLiveComponent()` |
| Eloquent | Doctrine | — |
| Blade view | Twig template | `view()` → `template()`, `viewData()` → `templateData()` |
| Alpine.js | Stimulus | `x-data` / `x-on:` → a controller under `packages/*/assets/src/controllers/`, wired with StimulusBundle's Twig helpers |
| `fi-*` classes | `sy-*` classes | `fi-icon-btn` → `sy-icon-btn`, `fi-disabled` → `sy-disabled` |

CSS class names are not a flat prefix swap: bundles above support insert a
package code (`sy-fo-input`, `sy-sc-grid`), support-level classes carry none
(`sy-icon-btn`), and every template root also gets the bare `sy` marker class.
Full contract: [`docs/styling.md`](../styling.md).

### Namespace segments

| Filament | Sylament | Note |
| --- | --- | --- |
| `Components\` | `Component\` | Plural segments singularize — `Filament\Support\Components\` → `Sylament\Support\Component\`. |
| `Enums\` | `Enum\` | Same rule — `Filament\Support\Enums\` → `Sylament\Support\Enum\`. |
| `View\` | `Twig\` | Laravel's `ComponentAttributeBag` and Filament's `SupportIconAlias` both live in `Sylament\Support\Twig\`. |
| `Concerns\`, `Contracts\`, `Icons\` | unchanged | Carry over as-is. |

Support-level traits split by what they do, so `Filament\Support\Concerns\` has
two destinations:

| Filament | Sylament | Which traits |
| --- | --- | --- |
| `Filament\Support\Concerns\` | `Sylament\Support\Component\Concerns\` | Component traits — `HasIcon`, `HasColor`, `HasExtraAttributes`, … |
| `Filament\Support\Concerns\` | `Sylament\Support\Concerns\` | Evaluation / configuration plumbing — `EvaluatesClosures`, `Configurable`. |
