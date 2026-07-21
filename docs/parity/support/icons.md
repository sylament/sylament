# Icons (support)

The cross-component icon infrastructure: the `generate_icon_html` helper, the
`IconManager` alias registry, the `IconSize` enum, the `ScalableIcon` contract,
the `HasIcon` / `HasIconColor` / `HasIconSize` concerns, the `generateIconHtml`
Twig function, the `<twig:SylamentSupport:Icon>` anonymous component, and the
`sy-icon` sizing CSS.

Reference:

- [`vendor/filament/support/src/helpers.php`](../../../vendor/filament/support/src/helpers.php)
  — `generate_icon_html()`
- [`vendor/filament/support/src/Icons/IconManager.php`](../../../vendor/filament/support/src/Icons/IconManager.php)
- [`vendor/filament/support/src/Icons/Heroicon.php`](../../../vendor/filament/support/src/Icons/Heroicon.php)
- [`vendor/filament/support/src/Enums/IconSize.php`](../../../vendor/filament/support/src/Enums/IconSize.php)
- [`vendor/filament/support/src/Enums/IconPosition.php`](../../../vendor/filament/support/src/Enums/IconPosition.php)
- [`vendor/filament/support/src/Contracts/ScalableIcon.php`](../../../vendor/filament/support/src/Contracts/ScalableIcon.php)
- [`vendor/filament/support/src/Concerns/HasIcon.php`](../../../vendor/filament/support/src/Concerns/HasIcon.php)
- [`vendor/filament/support/src/Concerns/HasIconColor.php`](../../../vendor/filament/support/src/Concerns/HasIconColor.php)
- [`vendor/filament/support/src/Concerns/HasIconSize.php`](../../../vendor/filament/support/src/Concerns/HasIconSize.php)
- [`vendor/filament/support/resources/views/components/icon.blade.php`](../../../vendor/filament/support/resources/views/components/icon.blade.php)
- [`vendor/filament/support/resources/css/components/icon.css`](../../../vendor/filament/support/resources/css/components/icon.css)

## Missing

- [ ] Icon-button component (`<x-filament::icon-button>` /
  `CanGenerateIconButtonHtml`) and the `IconPosition` enum that ships with it —
  planned with the collapse/stimulus effort that first consumes them
  ([../infrastructure.md#icon-system](../infrastructure.md#icon-system),
  [../infrastructure.md#stimulus-controllers](../infrastructure.md#stimulus-controllers))
- [ ] Array (custom-palette) icon colors: `iconColor(['500' => …])` needs the
  FilamentColor-style inline `--sy-color-*` emission. Section renders array
  colors with the default gray styling until then.
- [ ] Worker-mode request isolation. Filament's `IconManager` is a
  request-scoped Laravel singleton, re-seeded from the panel each request; ours
  is one process-lifetime singleton, so request-time `register()` calls would
  leak across requests under FrankenPHP/RoadRunner worker mode — same shape and
  resolution path as [component-manager.md](component-manager.md)'s isolation
  item. Classic per-request kernels have no leak.

## Divergences

- **Rendering backend is symfony/ux-icons, not blade-icons.** `IconManager`
  holds ux-icons' `IconRendererInterface` alongside the alias map (Filament
  splits aliases from the global blade-icons `svg()` helper; Symfony has no
  such global, so the one static-instance service plays both roles). Icon
  names are ux-icons names — iconify heroicons: outline `heroicons:x-mark`,
  solid `heroicons:x-mark-solid`, mini `heroicons:x-mark-20-solid`, micro
  `heroicons:x-mark-16-solid` — fetched on demand from the Iconify API by
  default. Local `assets/icons/` / `icon_sets` SVGs shadow per-name, with
  chain fallthrough to iconify on a local miss; apps pin icons with
  `ux:icons:lock` / `ux:icons:import`.
- **No bundled `Heroicon` enum.** Users pass raw ux-icons names; the
  `BackedEnum` + `ScalableIcon` seam in `generate_icon_html` is kept, so an
  icon enum (user-defined, or a future port) lands without an API break.
- **Missing `UXIconsBundle` fails lazily.** Rendering throws a
  `LogicException` naming the bundle instead of failing container compile, so
  minimal kernels booting `SylamentSupportBundle` alone keep compiling
  (`IconManager`'s renderer is wired `nullOnInvalid()`).
- **`aria-hidden="true"` comes from ux-icons** (added whenever no
  `aria-*`/`title` attribute is given); Filament relies on it being baked into
  its shipped SVG files. Net-same for heroicons, broader for other sets.
- **ux-icons attribute precedence bites raw local SVG files**: the default
  `default_icon_attributes` (`fill: currentColor`) overrides root-level
  attributes of local icon files — keep `fill`/`stroke` on the paths of local
  copies (iconify-served bodies already do).
- **`HasIcon::getIcon()` drops the `Renderable → HtmlString` normalization**
  (Laravel view objects have no Twig counterpart; pass `Markup`).
- **ux-icons' own `aliases` config is a separate layer** from `IconManager`
  aliases (Filament's component-slot override system); both compose — an
  `IconManager` alias may resolve to a name that ux-icons aliases further.
- **Enum home is `Sylament\Support\Enum`** (singular, house layout) instead of
  Filament's `Enums`.
- **Blade `<x-filament::icon>` maps to the anonymous
  `<twig:SylamentSupport:Icon>` component plus the `generateIconHtml` Twig
  function it delegates to** (house camelCase of the helper name — a systematic
  Blade→Twig translation, not a rename). The component syntax needs
  symfony/ux-twig-component in the consuming app; support ships the template
  without requiring it, same as its Section component template.
