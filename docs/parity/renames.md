# Rename table

The single global record of API renames. Preserve Filament's API names by
default — migrating users grep for them. Rename only when Filament itself is
inconsistent and the better name already exists in their codebase; record every
rename here in the same change.

Systematic Laravel → Symfony translations are **not** renames and don't belong
here — `Htmlable` → `Twig\Markup`, Livewire → UX LiveComponent (e.g.
`getLivewire()` → `getLiveComponent()`), Eloquent → Doctrine, Blade view →
Twig template (`view()` → `template()`, `viewData()` → `templateData()`).
Those follow the translation conventions in [CLAUDE.md](../../CLAUDE.md); this
table is for name changes a migrating user couldn't derive from them.

Namespace layout translations are equally systematic: Filament's plural
`Components\` and `Enums\` segments singularize (`Filament\Support\Components\`
→ `Sylament\Support\Component\`, `Filament\Support\Enums\` →
`Sylament\Support\Enum\`); `View\` → `Twig\` — e.g. Laravel's
`ComponentAttributeBag` and Filament's `SupportIconAlias` both live in
`Sylament\Support\Twig\`; and Filament's support-level component traits
(`Filament\Support\Concerns\HasIcon`, …) live under
`Sylament\Support\Component\Concerns\`, while the evaluation/configuration
plumbing traits (`EvaluatesClosures`, `Configurable`) stay in
`Sylament\Support\Concerns\`. `Concerns\`, `Contracts\`,
and `Icons\` carry over unchanged.

| Filament | Sylament | Home | Why |
| --- | --- | --- | --- |
| `Section::formBefore()` / `isFormBefore()` | `contentBefore()` / `hasContentBefore()` | `Sylament\Schemas\Components\Concerns\HasContentBefore` | Adopts the `contentBefore` prop name Filament's own support-layer section component uses. |
| `Filament\Schemas\Contracts\HasSchemas` (interface) | `InteractsWithSchemas` | `Sylament\Schemas\Contracts\InteractsWithSchemas` | Deliberate swap with the row below: here `Has*` is reserved for traits and the interface names the behavior, consistent with every other trait/interface naming in the codebase. **Grep trap for migrating users:** both names exist on both sides with opposite meanings. |
| `Filament\Schemas\Concerns\InteractsWithSchemas` (trait) | `HasSchemas` | `Sylament\Schemas\Concerns\HasSchemas` | Other half of the swap above: `Has*` matches the house trait prefix (`HasComponents`, `HasKey`, `HasGap`, …). |
| `Filament\Actions\Contracts\HasActions` (interface) | `InteractsWithActions` | `Sylament\Actions\Contracts\InteractsWithActions` | Same swap as the schemas rows above: `Has*` is reserved for traits and the interface names the behavior the host implements. **Grep trap for migrating users:** both names exist on both sides with opposite meanings. |
| `Filament\Actions\Concerns\InteractsWithActions` (trait) | `HasActions` | `Sylament\Actions\Concerns\HasActions` | Other half of the swap above: `Has*` matches the house trait prefix (`HasName`, `HasAction`, …). |
