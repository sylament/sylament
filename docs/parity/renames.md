# Rename table

The single global record of API renames. Preserve Filament's API names by
default — migrating users grep for them. Rename only when Filament itself is
inconsistent and the better name already exists in their codebase; record every
rename here in the same change.

Systematic Laravel → Symfony translations are **not** renames and don't belong
here — `Htmlable` → `Twig\Markup`, Livewire → UX LiveComponent (e.g.
`getLivewire()` → `getLiveComponent()`), Eloquent → Doctrine. Those follow the
translation conventions in [CLAUDE.md](../../CLAUDE.md); this table is for name
changes a migrating user couldn't derive from them.

| Filament | Sylament | Home | Why |
| --- | --- | --- | --- |
| `Section::formBefore()` / `isFormBefore()` | `contentBefore()` / `hasContentBefore()` | `Sylament\Schemas\Components\Concerns\HasContentBefore` | Adopts the `contentBefore` prop name Filament's own support-layer section component uses. |
