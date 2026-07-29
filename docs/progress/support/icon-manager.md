# IconManager (support)

`Sylament\Support\Icons\IconManager` — the registry that maps icon aliases
(`panels::pages.dashboard.navigation-item`) to concrete icon references
(`heroicons:home`, a `BackedEnum`, or a `Markup`). It ports Filament's
`IconManager` `register`/`resolve` pair verbatim and adds a `renderIcon()`
shim that turns a resolved name into SVG markup via `symfony/ux-icons` — the
rendering half that Filament keeps in a Blade component, not on the manager.

**Status:** done — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/src/Icons/IconManager.php`](../../../vendor/filament/support/src/Icons/IconManager.php)
  — the `register`/`resolve` registry
- [`support/src/Facades/FilamentIcon.php`](../../../vendor/filament/support/src/Facades/FilamentIcon.php)
  — the facade that reaches the singleton, replaced here by the static-instance pattern

## Surface

Every capability of the manager. _Detail_ carries **diverges** → the permanent
note in [Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`register()`](../../../packages/support/src/Icons/IconManager.php) | [`IconManager::register()`](../../../vendor/filament/support/src/Icons/IconManager.php) | done | — | [diverges](#the-manager-is-a-process-lifetime-singleton-not-request-scoped) |
| [`resolve()`](../../../packages/support/src/Icons/IconManager.php) | [`IconManager::resolve()`](../../../vendor/filament/support/src/Icons/IconManager.php) | done | — | — |
| [`renderIcon()`](../../../packages/support/src/Icons/IconManager.php) | no Filament equivalent | done | — | [diverges](#icon-rendering-is-delegated-to-symfony-ux-icons) |
| Static-instance registration | [`FilamentIcon`](../../../vendor/filament/support/src/Facades/FilamentIcon.php) facade | done | — | [diverges](#the-facade-is-replaced-by-the-static-instance-pattern) |

## Details

Every row above is **done** — the surface is fully ported and tested by
[`tests/Unit/Icons/IconManagerTest.php`](../../../packages/support/tests/Unit/Icons/IconManagerTest.php)
(alias resolution, unknown-alias `null`, last-registration-wins merge,
first-match-from-a-list, `renderIcon` delegation, and the missing-renderer
throw) — so none carries a write-up here.

## Divergences

### Icon rendering is delegated to Symfony UX Icons

Filament's `IconManager` is a pure alias registry — `register` and `resolve`,
nothing more; turning a name into SVG is a separate Blade/`svg()` concern.
Sylament folds that step onto the manager as `renderIcon(string $name, array
$attributes)`, which delegates to the optional
[`Symfony\UX\Icons\IconRendererInterface`](../../../packages/support/src/Icons/IconManager.php).
The renderer is constructor-injected `nullOnInvalid()`, so `symfony/ux-icons`
stays optional: when it is absent, `renderIcon()` throws a `LogicException`
naming `UXIconsBundle` rather than silently returning nothing. `register` /
`resolve` remain byte-faithful to Filament (only the `Arr::wrap`→`(array)` and
`isset`→`array_key_exists` idiom swaps, neither observable for the non-null icon
values the type allows).

_See also: the [rename table](../renames.md) — `Htmlable`→`Markup` on the icon
value type is a systematic translation recorded there._

### The facade is replaced by the static-instance pattern

Filament reaches the singleton through the `FilamentIcon` facade over a
container binding. Sylament wires the manager as a DI service
(`sylament_support.icon_manager`) and makes it reachable from non-DI contexts
via `StaticInstanceInterface` + `HasStaticInstance`, tagged and booted through
`StaticInstanceRegistry` — the same mechanism `ComponentManager` and
`ComponentResolver` use. There is no facade class and no `FilamentIcon::register()` deferred-resolve
wrapper; callers hold the service or the static instance directly.

### The manager is a process-lifetime singleton, not request-scoped

Filament binds `IconManager` with `$this->app->scoped(...)`, so it is rebuilt at
each request boundary and a mid-request `register()` cannot leak into the next.
Sylament's is one process-lifetime static instance (`$icons` mutated by
`register()`) with no per-request reset: harmless under a classic per-request
kernel, but under a FrankenPHP/RoadRunner worker a request-time `register()` would
persist across requests. Same shape and resolution path as
[`component-manager.md`](component-manager.md)'s scoped-manager gap; a Symfony
reset seam lands with the live-component-state work.

_See also: the static-instance pattern documented in
[CLAUDE.md](../../../CLAUDE.md)._
