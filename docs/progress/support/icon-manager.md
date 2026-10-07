---
description: The registry that maps icon aliases (`panels::pages.dashboard.navigation-item`) to concrete icon references (`heroicons:home`, a `BackedEnum`, or a… (draft)
ports:
  - vendor/filament/support/src/Icons/IconManager.php
  - vendor/filament/support/src/Facades/FilamentIcon.php
owns:
  - packages/support/src/Icons/
---
# IconManager (support)

`Sylament\Support\Icons\IconManager` — the registry that maps icon aliases (`panels::pages.dashboard.navigation-item`) to concrete icon references (`heroicons:home`, a `BackedEnum`, or a `Markup`).
It ports Filament's `IconManager` `register`/`resolve` pair verbatim and adds a `renderIcon()` shim that turns a resolved name into SVG markup via `symfony/ux-icons` — the rendering half that Filament keeps in a Blade component, not on the manager.

## Surface

Every capability of the manager.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`register()`](../../../packages/support/src/Icons/IconManager.php) | [`IconManager::register()`](../../../vendor/filament/support/src/Icons/IconManager.php) | done | — | [diverges](#the-manager-is-a-process-lifetime-singleton-not-request-scoped) |
| [`resolve()`](../../../packages/support/src/Icons/IconManager.php) | [`IconManager::resolve()`](../../../vendor/filament/support/src/Icons/IconManager.php) | done | — | — |
| [`renderIcon()`](../../../packages/support/src/Icons/IconManager.php) | no Filament equivalent | done | — | [diverges](#icon-rendering-is-delegated-to-symfony-ux-icons) · [semantics](#an-unprefixed-heroicon-case-is-the-mini-20px-set-and-our-literals-must-spell-it) · [semantics](#the-painted-size-is-resolved-by-the-generator-not-by-the-callers-size) |
| Global reachability | [`FilamentIcon`](../../../vendor/filament/support/src/Facades/FilamentIcon.php) facade | done | — | [diverges](#the-facade-is-replaced-by-plain-injection) |
| [`sylament.icons` config](../../../packages/support/src/SylamentSupportBundle.php) | no Filament equivalent | done | — | [diverges](#alias-overrides-are-declarable-from-bundle-configuration) |

## Details

Every row above is **done** — the surface is fully ported and tested by [`tests/Unit/Icons/IconManagerTest.php`](../../../packages/support/tests/Unit/Icons/IconManagerTest.php) (alias resolution, unknown-alias `null`, last-registration-wins merge, first-match-from-a-list, `renderIcon` delegation, and the missing-renderer throw) — so none carries a write-up here.

## Semantics

Both notes below are about the **icon name a template or a PHP default writes**, which is what every icon in the toolkit hands `renderIcon()`, so they govern all nine packages rather than this class alone.

### An unprefixed Heroicon case is the mini 20px set, and our literals must spell it

Filament never writes an icon name; it writes a case of [`Filament\Support\Icons\Heroicon`](../../../vendor/filament/support/src/Icons/Heroicon.php), which implements [`ScalableIcon`](../../../packages/support/src/Contracts/ScalableIcon.php) and resolves to a *set* at render time.
`getIconForSize()` is the whole contract:

```php
if (str_starts_with($this->value, 'o-')) {
    return "heroicon-{$this->value}";               // 24px outline, size-independent
}

return match ($size) {
    IconSize::ExtraSmall, IconSize::Small       => "heroicon-c-{$this->value}",  // 16px micro solid
    IconSize::Medium                            => "heroicon-m-{$this->value}",  // 20px mini solid
    IconSize::Large, ExtraLarge, TwoExtraLarge  => "heroicon-s-{$this->value}",  // 24px solid
};
```

`generate_icon_html()` defaults `$size ??= IconSize::Medium`, so an **unprefixed** case rendered without an explicit size is the **mini 20px solid** set, and an `Outlined*` case (the `o-` values) is the 24px outline set at every size.
The enum carries that choice; a literal cannot, which is the whole point of this note.
Our port speaks Iconify names through `symfony/ux-icons`, and the Iconify vocabulary spells the variant in the name:

| Filament resolves to | Ours |
| --- | --- |
| `heroicon-o-<n>` | `heroicons:<n>` |
| `heroicon-m-<n>` | `heroicons:<n>-20-solid` |
| `heroicon-c-<n>` | `heroicons:<n>-16-solid` |

So porting `Heroicon::ChevronRight` as `heroicons:chevron-right` is **wrong** — that is the outline set, which Filament reaches only for `OutlinedChevronRight`.
The bare name is correct exactly when the vendor case is an `Outlined*` one.
Read the vendor case before writing the literal: the two look identical at the call site and differ by 4px and a stroke.

The rule reaches PHP defaults as well as templates — `ActionGroup`'s `ellipsis-vertical`, `CreateAction`'s `plus`, the four `Repeater` action icons — because those are `icon()` defaults Filament sets from the same enum.
Nothing in the toolchain checks a name against its vendor case, so the only standing guard is the fixture set: each package's test kernel disables Iconify's on-demand fetch and `ux_icons`' `ignore_not_found` defaults to `false`, so a rendered icon with no fixture file throws.
That catches a name reverted to one nobody vendored and misses a name whose old spelling is still on disk — which is why the two places both spellings coexist carry an explicit `viewBox` pin ([`BadgeRenderTest`](../../../packages/actions/tests/Integration/Components/BadgeRenderTest.php), [`TableRenderingTest`](../../../packages/tables/tests/Integration/TableRenderingTest.php)) and why the sidebar chevron, whose two candidates share a `0 0 20 20` viewBox, is pinned on path data instead ([`SidebarRenderTest`](../../../packages/panels/tests/Integration/Twig/SidebarRenderTest.php)).

**One shipped site is micro rather than mini**, and it is the exception that proves the size argument matters: Filament renders the badge's delete button at an explicit `IconSize::ExtraSmall` ([`badge.blade.php`](../../../vendor/filament/support/resources/views/components/badge.blade.php)), which resolves to `heroicon-c-x-mark`, so ours is `heroicons:x-mark-16-solid`.

### The painted size is resolved by the generator, not by the caller's `Size`

The second half of the same trap, and the reason "Medium" can never be assumed.
`Size` (the *control's* size) and `IconSize` (the *glyph's*) are different enums, and every HTML generator maps between them differently — a control at `Size::Small` does not imply an icon at `IconSize::Small`:

| Generator | `Size::Small` resolves the icon to | Set |
| --- | --- | --- |
| [`CanGenerateIconButtonHtml`](../../../vendor/filament/support/src/View/Concerns/CanGenerateIconButtonHtml.php) | `null` → the `generate_icon_html()` default, Medium | mini |
| [`CanGenerateButtonHtml`](../../../vendor/filament/support/src/View/Concerns/CanGenerateButtonHtml.php) | `IconSize::Small` | micro |
| [`CanGenerateLinkHtml`](../../../vendor/filament/support/src/View/Concerns/CanGenerateLinkHtml.php) | `IconSize::Small` | micro |
| [`CanGenerateDropdownItemHtml`](../../../vendor/filament/support/src/View/Concerns/CanGenerateDropdownItemHtml.php) | no map at all → Medium | mini |
| [`CanGenerateBadgeHtml`](../../../vendor/filament/support/src/View/Concerns/CanGenerateBadgeHtml.php) | `?? IconSize::Small` whatever the control size | micro |

The icon-button row is the one that misleads: its `match` names only `ExtraSmall`, `Large` and `ExtraLarge`, so `Size::Small` falls to `default => null` and the glyph stays medium while the **button** carries `fi-size-sm`.
A port reading the button's class as the icon's shrinks the glyph.

The mirror image is a control with **no** size class at all.
`.fi-icon-btn`'s base rule is `size-9` and its `&.fi-size-md` block adds only negative-margin arms, no box change ([`icon-button.css`](../../../vendor/filament/support/resources/css/components/icon-button.css)), so a bare `fi-icon-btn` **is** the medium button — which is why our notification close button, our tables sort chevron and our tables search prefix all render at `md` rather than the `sm` that reads like the smaller, tidier choice.

## Divergences

### Icon rendering is delegated to Symfony UX Icons

Filament's `IconManager` is a pure alias registry — `register` and `resolve`, nothing more; turning a name into SVG is a separate Blade/`svg()` concern.
Sylament folds that step onto the manager as `renderIcon(string $name, array $attributes)`, which delegates to the optional [`Symfony\UX\Icons\IconRendererInterface`](../../../packages/support/src/Icons/IconManager.php).
The renderer is constructor-injected `nullOnInvalid()`, so `symfony/ux-icons` stays optional: when it is absent, `renderIcon()` throws a `LogicException` naming `UXIconsBundle` rather than silently returning nothing.
`register` / `resolve` remain byte-faithful to Filament (only the `Arr::wrap`→`(array)` and `isset`→`array_key_exists` idiom swaps, neither observable for the non-null icon values the type allows).

_See also: the [rename table](../renames.md) — `Htmlable`→`Markup` on the icon value type is a systematic translation recorded there._

### The facade is replaced by plain injection

Filament reaches the singleton through the `FilamentIcon` facade over a container binding.
Sylament wires the manager as an ordinary DI service (`sylament_support.icon_manager`, aliased to the FQCN) and every consumer takes it as a constructor argument — there is no facade class, no `FilamentIcon::register()` deferred-resolve wrapper, and no static accessor.

It did carry `StaticInstance` for a while, for exactly one reason: the ported `generate_icon_html()` was a free function and a free function has no `$this`.
Moving that body onto [`IconExtension`](../../../packages/support/src/Twig/Extension/IconExtension.php), its only caller, removed the reason — see [helpers.md](helpers.md#generate_icon_html-moved-to-the-twig-extension).
An app registering alias overrides injects the manager or fetches it from the container; it cannot reach it statically, which is the Symfony answer to what a facade was doing.

The alias stays **private**, unlike [the colour policies](color-policies.md), even though shipped closures type-hint the manager — [`Dashboard::getNavigationIcon()`](../panels/dashboard.md) is the first.
The [`Invoker`](../../../packages/support/src/Evaluation/Invoker.php) resolving that parameter through `ContainerInterface::has()`/`get()` would need a public alias, but it consults the evaluating object's `resolveDefaultClosureDependencyForEvaluationByType()` hook first, and `NavigationItem` — the object that evaluates every navigation closure — takes the manager through a `#[Required]` setter and hands it over from there ([the item hands its own services to evaluated closures](../panels/navigation-item.md#the-item-hands-its-own-services-to-evaluated-closures)).
Closure access to the manager rides that injected set, so nothing has to be published to make sylament's own icons resolve.

### Alias overrides are declarable from bundle configuration

Filament has one way to override an alias: call `FilamentIcon::register([...])` from a service provider's `boot()`.
Sylament keeps the equivalent runtime call but adds the Symfony-native route, since an app overriding an icon is configuration rather than code:

```yaml
# config/packages/sylament.yaml
sylament:
    icons:
        modal.close-button: 'lucide:x'
        section.collapse-button: 'lucide:chevron-up'
```

The node is declared in [`SylamentSupportBundle::configure()`](../../../packages/support/src/SylamentSupportBundle.php) and applied as a `->call('register', [...])` on the manager's definition, so it is container-time and costs nothing at runtime.
A later runtime `register()` still wins, because `register()` merges last-wins — config sets the floor, code can still override it.

One flat map covers **every** package's aliases, rather than a per-bundle `icons` key.
That matches what the registry is — a single global map whose keys are already package-namespaced (`modal.*`, `section.*`, later `tables.*`) — and avoids real machinery: `IconManager` is support's service, and a higher bundle's `loadExtension()` cannot reliably reach its definition, because extension load order follows bundle registration order in `bundles.php`.

The extension is `sylament`, not `sylament_support`: support sets `protected string $extensionAlias = 'sylament'` and owns the tree for whatever the whole toolkit shares.
Overriding a *tables* icon under a key named for the *support* package would have been the tell that the alias was wrong — the registry was never support-specific, only its implementation is.
Package-owned settings still live under the derived `sylament_<pkg>` alias, since a bundle cannot extend another bundle's config tree.

A package shipping its own *default* aliases has a clean seam regardless: `prependExtensionConfig('sylament', ['icons' => ...])` lands below the app's config in the merge, so defaults and overrides compose.

**`->normalizeKeys(false)` is load-bearing and its absence is silent.**
`ArrayNode::preNormalize()` rewrites any key that holds a dash and no underscore, and both aliases support ships match that shape — the override would register as `modal.close_button`, `resolve()` would never match it, and the app would simply keep seeing the default icon with no error anywhere.
Two tests in [`SylamentSupportBundleTest`](../../../packages/support/tests/Integration/SylamentSupportBundleTest.php) pin it; both fail if the call is removed.

### The manager is a process-lifetime singleton, not request-scoped

Filament binds `IconManager` with `$this->app->scoped(...)`, so it is rebuilt at each request boundary and a mid-request `register()` cannot leak into the next.
Sylament's is one process-lifetime service (`$icons` mutated by `register()`) with no per-request reset: harmless under a classic per-request kernel, but under a FrankenPHP/RoadRunner worker a request-time `register()` would persist across requests.
Same shape and resolution path as [`component-manager.md`](component-manager.md)'s scoped-manager gap; a Symfony reset seam lands with the live-component-state work.

