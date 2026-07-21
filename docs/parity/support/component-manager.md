# ComponentManager (+ Configurable)

The global-configuration singleton behind `configureUsing()` / `configure()`.
Ported: class-keyed configuration closures, removal closures, scoped `during()`
runs, `isImportant` ordering, the hierarchy walk interleaving `setUp()` at its
declaring class, and the hierarchy/setUp-declaring-class memoization. The
`Configurable` trait delegates exactly as Filament's does.

Reference:

- [`vendor/filament/support/src/Components/ComponentManager.php`](../../../vendor/filament/support/src/Components/ComponentManager.php)
- [`vendor/filament/support/src/Concerns/Configurable.php`](../../../vendor/filament/support/src/Concerns/Configurable.php)
- [`vendor/filament/support/src/Components/Contracts/ScopedComponentManager.php`](../../../vendor/filament/support/src/Components/Contracts/ScopedComponentManager.php)
- [`vendor/filament/support/src/SupportServiceProvider.php`](../../../vendor/filament/support/src/SupportServiceProvider.php)
  (the scoped-clone registration, `packageRegistered()`)

## Missing

- [ ] Worker-mode request isolation. Filament rebinds `ScopedComponentManager`
  to a `clone()` of the boot-time singleton per Octane request, so request-time
  `configureUsing()` registrations flush while service-provider ones persist.
  Our manager is one process-lifetime singleton; under FrankenPHP/RoadRunner
  worker mode, request-time registrations would leak across requests. The
  Symfony shape is `kernel.reset` (`ResetInterface`), but what reset restores
  to depends on where boot-time registrations idiomatically live (likely the
  panel provider equivalent) — decide when panels exist. Classic per-request
  kernels have no leak.

## Divergences

- **No `ScopedComponentManager` contract, `clone()`, or `resolveScoped()`.**
  Laravel-Octane container-scoping machinery; Symfony has no per-request
  service scope. The worker-mode concern it solves is tracked above.
- **No `extractPublicMethods()` / `$methodCache`.** A Blade-ism: Filament
  injects every public method as a view closure (`$getHeading()`); Twig
  templates call methods on the component object directly.
- **`resolve()` fallback is the static-instance pattern, not `singletonIf`.**
  The DI service is defined with `factory(ComponentManager::resolve(...))`, so
  the container's instance is identical to the statically resolved one and
  pre-boot registrations survive the registry's `setInstance()` at kernel
  boot. Outside DI (kernel-less unit tests), `resolve()` lazily creates the
  singleton; Unit suites unset it in a `tests/Pest.php` `afterEach` hook.
