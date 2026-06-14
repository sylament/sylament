# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Sylament is a Filament-inspired UI toolkit for Symfony, delivered as a monorepo of nine Symfony bundles under `packages/`: `actions`, `forms`, `infolists`, `notifications`, `panels`, `schemas`, `support`, `tables`, `widgets`. Package names mirror Filament's PHP packages and the same layered dependency idea applies (`support` is the base; `schemas` builds the component model; `forms`/`infolists`/`tables` build on `schemas`; `panels` composes everything).

## Reference Filament's implementation

This project is a deliberate Symfony port of Filament. Before designing or extending any component, trait, or API surface, **read how Filament does it first** — their decisions encode years of API ergonomics work and we want to inherit them unless we have a concrete reason to diverge (Symfony idiom, missing dependency, etc.).

Filament's source is installed at `vendor/filament/` (dev dep, never shipped). Useful entry points:
- `vendor/filament/schemas/src/Components/Component.php` — the base component analogous to our `Sylament\Schemas\Components\Component`.
- `vendor/filament/schemas/src/Components/Concerns/` — single-purpose traits; our `packages/schemas/src/Components/Concerns/` mirrors this directory by name.
- `vendor/filament/forms/src/Components/`, `vendor/filament/tables/src/`, etc. — per-bundle equivalents.

When porting, preserve trait names, method names, and the closure-evaluation parameter set (so users carrying Filament mental models stay productive). Translate Laravel idioms to Symfony equivalents (e.g. Eloquent → Doctrine, Livewire → UX LiveComponent, Blade → Twig) but keep the public API shape. When Filament puts a trait on the base class even though only some subclasses use it (e.g. `HasColumns`), assume the choice is for uniform fluent composition and follow suit unless you can articulate why the divergence is worth it.

## Monorepo wiring (read this before touching deps)

- **One root `vendor/`.** The repo root is a single Composer project. Every bundle is symlinked in via a Composer path repository (`"repositories": [{ "type": "path", "url": "packages/*" }]`) and required as `self.version`. Never run `composer install` inside a sub-package — there is only one lockfile and one vendor at the root. A stray `packages/*/vendor` or `packages/*/composer.lock` is leftover cruft; delete it.
- **All dev dependencies live in the root `composer.json`.** Sub-package `composer.json` files carry production `require` only (their real inter-bundle dependencies). When a bundle needs a new dev tool, add it to the root.
- **Assets are a separate pnpm workspace.** Each `packages/*/assets/` directory is a workspace (glob `packages/*/assets` in `pnpm-workspace.yaml`), named `@sylament/<pkg>`. PHP and JS are independent dependency graphs that meet only through Symfony AssetMapper.
- `pnpm-workspace.yaml` sets `hoistPattern: []` — transitive deps are NOT reachable by parent-walking. If you get "Module not found" in a workspace, add the dep explicitly to that workspace's `package.json`.
- Each push to `main` (and each tag) is split out to per-package read-only repos under the `sylament` org by `.github/workflows/monorepo-split.yml`. Keep each package self-contained so the split stays valid.

## Commands

PHP (run from repo root):
- `composer test` — full suite: every bundle's Pest suite + Rector dry-run + PHPStan (level max).
- `composer test:<bundle>` — single bundle's Pest suite, e.g. `composer test:schemas`. Each invokes `pest -c packages/<bundle>/phpunit.dist.xml --test-directory=packages/<bundle>/tests`.
- `composer test:static` — PHPStan only. `composer test:lint` — PHP-CS-Fixer dry-run. `composer test:rector` — Rector dry-run.
- `composer format` — apply Rector then PHP-CS-Fixer.
- Run one Pest file: `vendor/bin/pest -c packages/schemas/phpunit.dist.xml --test-directory=packages/schemas/tests path/to/Test.php` (or use `--filter`).

JS/CSS (run from repo root):
- `pnpm build` / `pnpm watch` — build/watch every asset workspace (`pnpm -r`). `pnpm test:types` — `tsc --noEmit` across workspaces.
- `pnpm format` (Biome write) / `pnpm test:lint` (Biome check). Biome only governs `packages/*/assets/src/**/*.{ts,js}`.
- Per-bundle asset `build` runs `clean → tsc --noEmit → esbuild` into `dist/`. `support` additionally builds Tailwind v4 CSS. If `pnpm watch` dies in a non-TTY shell, run `cd packages/<pkg>/assets && pnpm watch` individually.

`symfony/asset-mapper` is a root dev dep needed for tests to exercise the AssetMapper path. Without it, bundle `prependExtension` hooks short-circuit (see below) so production consumers aren't forced to install it.

## Core architecture

**Bundle base class.** Every bundle extends `Sylament\Support\SylamentBundle` (an `AbstractBundle`). Its `isAssetMapperAvailable()` guard is why asset-mapper is optional: each bundle's `prependExtension()` registers its `assets/dist` directory into the `framework.asset_mapper.paths` config under the alias `@sylament/<pkg>`, but only when AssetMapper is actually present. Services are wired in `loadExtension()` via the PHP DSL configurator (no XML/YAML service files).

**Closure evaluation — the central pattern.** Almost every configurable value in the API can be either a literal or a closure, mirroring Filament. Two `support` pieces implement this:
- `Sylament\Support\Services\Invoker` — reflects a callable's parameters and resolves each by name: first from explicit `$parameters`, then via a `resolveNamedParameter` callback, then by type-hint from the DI container.
- `Sylament\Support\Concerns\EvaluatesClosures` — the trait classes use. `evaluate($value, $parameters)` returns `$value` as-is if it isn't callable, otherwise calls it through the Invoker. The trait injects `Invoker` via a `#[Required] setInvoker()` setter.

Classes override `resolveDefaultClosureDependencyForEvaluationByName()` to declare which named closure parameters are available. For example `Schemas\Components\Component` exposes `$get`, `$set`, `$state`, `$rawState`, `$component`, `$liveComponent`. When adding closure-driven config to a component, wire its injectable parameters there and return a `ClosureParameter::make(...)`.

**Component model (`schemas`).** `Schemas\Components\Component` is the abstract base for all UI components, assembled from single-purpose traits in `Components/Concerns/` (`HasComponents`, `HasState`, `HasName`, `HasLabel`, `HasTemplate`, `BelongsToParentComponent`, `HasStateBindingModifiers`, …). Components use the fluent `static::make()` + chained setters convention and compose into a `Schema`. Higher bundles (`forms`'s `Field`/`TextInput`, etc.) extend this model. The Stimulus/LiveComponent layer (`symfony/ux-live-component`, `symfony/stimulus-bundle`) drives interactivity. Favor adding a focused `Concerns/Has*` trait over widening an existing class.

## Conventions

- PHP: `declare(strict_types=1)` everywhere; PHPStan **level max** must stay green (only `trait.unused` and `new.static` are globally ignored). PSR-4 namespace `Sylament\<Bundle>\` → `packages/<bundle>/src/`, tests `Sylament\<Bundle>\Tests\` → `packages/<bundle>/tests/`. Tests are Pest. Formatting is PHP-CS-Fixer + Rector — run `composer format`, don't hand-format.
- Test layout: each bundle's `tests/` is split into buckets — `Unit/` for tests with no kernel boot (pure logic, compiler passes against a hand-built `ContainerBuilder`, trait behavior with fixture classes), `Integration/` for tests that boot the kernel or hit the container, and `Architecture/` for project-wide convention tests that guard no single class (e.g. scanning Twig templates for the `html_classes`/`html_attr` split convention — see `schemas/tests/Architecture/TemplateConventionsTest.php`). Inside `Unit/` and `Integration/`, **mirror the `src/` tree exactly**: `src/DependencyInjection/Compiler/StaticInstancePass.php` → `tests/Unit/DependencyInjection/Compiler/StaticInstancePassTest.php`, `src/SylamentSupportBundle.php` → `tests/Integration/SylamentSupportBundleTest.php`. `Architecture/` tests have no SUT so they don't mirror `src/`. `tests/Pest.php` attaches `TestCase` (the `KernelTestCase` subclass) only to `Integration/`; `Unit/` and `Architecture/` use Pest's default base. Test file names match the SUT (`FooTest.php` for `Foo.php`); note that Pest's `arch()` helper only inspects PHP classes/namespaces, so template-content rules live in `Architecture/` as expectation-based tests rather than `arch()` calls.
- Fixture layout: `tests/Fixtures/` mirrors `src/` for per-test fixture classes (e.g. fixtures supporting `src/DependencyInjection/` tests live in `tests/Fixtures/DependencyInjection/`). Global test-app infrastructure (the `Kernel`, fixture service-config files) stays at the root of `tests/Fixtures/`. Don't dump everything flat — put each fixture next to the subsystem it exercises so it's findable as the suite grows.
- Test Kernel wiring: the test `Kernel` PSR-4-loads everything under `tests/Fixtures/` recursively with `autowire + autoconfigure` defaults (excluding `Kernel.php` itself). Adding a new fixture means dropping a class in the right `Fixtures/` subfolder — never edit the Kernel to register individual fixture services. Services stay private by default; `KernelTestCase::getContainer()` returns the `test.service_container` which exposes privates to tests, so you rarely need anything public. When a fixture genuinely must be public in the real container, opt in with `#[Autoconfigure(public: true)]` on the fixture class itself — never widen the Kernel defaults. If a fixture needs special construction, write a small config (e.g. `Fixtures/services_special.php`) and `import()` it from the Kernel rather than hand-wiring inline.
- Pest tests: inside `it()` / `test()` closures, reach static `KernelTestCase` helpers through `$this::` (e.g. `$this::getContainer()`, `$this::bootKernel()`) — never `static::` or `self::`. Pest binds `$this` to the configured `TestCase`, so `$this::foo()` resolves to that class's static method; `static::foo()` resolves against the anonymous closure scope and is wrong.
- Pest tests: don't call `bootKernel()` explicitly — `KernelTestCase::getContainer()` auto-boots when no kernel is booted. Just call `$this::getContainer()->get(...)` directly. Only boot explicitly when you need to pass options (`$this::bootKernel(['environment' => 'foo'])`) or grab the kernel object itself.
- TS: strict mode, `target` ES2022, ESM, bundler resolution (`tsconfig.base.json`). Biome enforces `useImportType`, `useConst`, and bans `any` and non-null assertions (`!`) as errors. Stimulus is a peer dep pinned to `^3` via workspace overrides.
- CSS: semantic `sy-<pkg code>-*` classes only in Twig (never utilities — template scanning is off), e.g. `sy-fo-input`, `sy-sc-grid`; every component template root also carries the bare `sy` marker class; `@apply` authoring in `packages/<pkg>/assets/src/styles/components/<name>.css`; dynamic per-instance values via inline `--sy-*` CSS variables; colors through the `--sy-*` runtime tokens; Tailwind preflight is never shipped page-wide — `support`'s build generates a `.sy`-scoped copy from the installed Tailwind (`assets/scripts/scope-preflight.mjs`). Full contract: `docs/styling.md`.

## Testing — prefer integration, what to test, what to skip

Coverage is not the goal; catching regressions cheaply is. The layout rules live under Conventions; this section governs **what to write**. Two constraints shape everything: **PHPStan runs at level `max` over `src/` (not tests)** — so never spend a test re-proving what the type checker already guarantees — and we **prefer integration tests that exercise a whole feature over many isolated unit tests**.

**Prefer integration tests (the default, the bulk of the suite).** The unit of a good test here is a **feature observed through its public surface**, not a method. For components that means: build the component through its fluent API and **render it through the booted kernel's Twig**, then assert on the HTML. One render exercises the fluent setters, state resolution, inheritance chains, closure evaluation through the *real* `Invoker`, the Twig templates, and the registered extensions (`grid()`, `renderComponent`, `html_attr`) all at once — so a handful of render assertions replace dozens of per-trait unit tests. Use `renderToHtml($component)` (defined in `tests/Pest.php`) and assert with `toContain(...)` on the produced classes/markup. See `schemas/tests/Integration/Components/SchemaRenderTest.php` and `SectionRenderTest.php` for the pattern. **Before adding a unit test for a trait/setter, ask: "is this behavior observable by rendering a component that uses it?" If yes, assert it in a render test instead of in isolation.**

The render harness is the test Kernel playing the role of a consuming app: it registers `TwigComponentBundle`, `Twig\Extra\Html\HtmlExtension`, and `twig_component` config so the templates actually render (production leaves these to the consumer). Anonymous Twig components resolve case-sensitively on Linux CI — keep component templates at `templates/components/<Name>/index.html.twig` (all lowercase except the component segment), matching ux-twig-component's `@Namespace/components/...` lookup. macOS hides casing bugs; CI won't.

**Keep unit tests only where integration genuinely can't reach — and say why in a comment.** These are the deliberate exceptions, not the norm:
- **Pure infrastructure / engine code** with many branches and no render path: the closure `Invoker` (parameter-resolution strategies), DI compiler passes (against a hand-built `ContainerBuilder`), the static-instance registry, the `EvaluatesClosures` seam, the `ViewComponent` template-data engine. Unit tests are the *clearest* tool here; a render would only hit the happy path.
- **Branches a render never triggers** — e.g. `GridExtension`'s column direction and breakpoint-name sanitization (a `Schema` always renders row-direction with plain breakpoints). Test just those branches as a unit, and note that the common path is covered by the render test.
- **Error paths and object contracts not reachable through a render** — e.g. "`getLiveComponent()` throws when there is none" (a render always supplies one), or schema-method resolution + caching + its error messages.
- **Caching** — that a result is memoized **and** that the relevant mutation invalidates it (both halves). A single render can't show the second half.

**The one filter for any test (unit or integration):** *"What bug does this catch that PHPStan max — or an existing render test — wouldn't?"* If "none," don't write it. Prefer one assertion per **decision** (a branch, a default, a transform), never one per method.

**Skip entirely (type system / framework / language already guarantees it):**
- **Fluent chaining** — never write `it('returns itself from x() for chaining')`. Setters are typed `: static`; PHPStan proves it.
- **Plain store-and-return** getters/setters with no logic — *unless* the setter transforms or runs through `evaluatable()`, and even then prefer to observe it through a render. No `clear-with-null` tests when `null` just flows through unchanged.
- **Re-testing dependencies or the language** — that Twig renders, that a backed enum's `from()` works or that it has the cases you just declared, that PHP assigns constructor properties.
- **Pure `instanceof`/inheritance** and **classes with no logic of their own** (a component that only composes traits) — covered by the feature render; don't manufacture a unit file to mirror `src/`. Mirroring is for files with something to assert.

**Placeholder `expect(true)->toBeTrue()` suites** exist only so a package with no `src/` yet keeps `composer test:<pkg>` green (Pest fails a zero-test run). Replace them with real (preferably integration) tests as soon as the package gains behavior.

Always test through **real collaborators**, not mocks: the booted kernel's real services, an actual `ContainerBuilder` for compiler passes. Reserve fixtures for engine traits that have no concrete renderable host.
