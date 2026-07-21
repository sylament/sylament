# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Sylament is a Filament-inspired UI toolkit for Symfony, delivered as a monorepo of nine Symfony bundles under `packages/`: `actions`, `forms`, `infolists`, `notifications`, `panels`, `schemas`, `support`, `tables`, `widgets`. Package names mirror Filament's PHP packages and the same layered dependency idea applies (`support` is the base; `schemas` builds the component model; `forms`/`infolists`/`tables` build on `schemas`; `panels` composes everything). The project is early-stage: today only `support` ships real code; the other bundles ship a bundle class plus a placeholder test.

## Reference Filament's implementation

This project is a deliberate Symfony port of Filament. Before designing or extending any component, trait, or API surface, **read how Filament does it first** — their decisions encode years of API ergonomics work and we want to inherit them unless we have a concrete reason to diverge (Symfony idiom, missing dependency, etc.).

Filament's source is installed at `vendor/filament/` (dev dep, never shipped). Useful entry points:
- `vendor/filament/schemas/src/Components/Component.php` — the base component analogous to our future `Sylament\Schemas\Components\Component`.
- `vendor/filament/schemas/src/Components/Concerns/` — single-purpose traits; we will mirror this directory by name as schemas grows.
- `vendor/filament/forms/src/Components/`, `vendor/filament/tables/src/`, etc. — per-bundle equivalents.

When porting, preserve trait names, method names, and the closure-evaluation parameter set (so users carrying Filament mental models stay productive). Translate Laravel idioms to Symfony equivalents (e.g. Eloquent → Doctrine, Livewire → UX LiveComponent, Blade → Twig, `Htmlable` → `Twig\Markup`) but keep the public API shape. The `Htmlable` translation is an escaping contract: wherever Filament types a human-text value as `string | Htmlable | Closure`, we type it `string|Markup|Closure` — plain strings are escaped by templates, `Markup` renders raw (Twig's escaper passes `Markup` instances through untouched, exactly as Blade does with `Htmlable`). When Filament puts a trait on the base class even though only some subclasses use it (e.g. `HasColumns`), assume the choice is for uniform fluent composition and follow suit unless you can articulate why the divergence is worth it.

Preserve Filament's API names by default — migrating users grep for them. Rename only when Filament itself is inconsistent and the better name already exists in their codebase, and record every rename here. Current renames: `Section::formBefore()` → `contentBefore()` (`HasContentBefore` trait; adopts the `contentBefore` prop name Filament's own support-layer section component uses).

Per-component porting state lives in `docs/parity/<package>/<component>.md` (file convention: `docs/parity/README.md`); cross-cutting blockers have anchors in `docs/parity/infrastructure.md` that parity files link instead of restating. Keep these current as work lands — stale entries are bugs.

## Monorepo wiring (read this before touching deps)

- **One root `vendor/`.** The repo root is a single Composer project. Every bundle is symlinked in via a Composer path repository (`"repositories": [{ "type": "path", "url": "packages/*" }]`) and required as `self.version`. Never run `composer install` inside a sub-package — there is only one lockfile and one vendor at the root. A stray `packages/*/vendor` or `packages/*/composer.lock` is leftover cruft; delete it.
- **All dev dependencies live in the root `composer.json`.** Sub-package `composer.json` files carry production `require` only (their real inter-bundle dependencies). When a bundle needs a new dev tool, add it to the root.
- **Assets are a separate pnpm workspace.** `pnpm-workspace.yaml` globs `packages/*/assets`. Today only `packages/support/assets/` has a `package.json` (`@sylament/support`); the other bundles join the workspace once they get one. PHP and JS are independent dependency graphs that meet only through Symfony AssetMapper.
- `pnpm-workspace.yaml` sets `hoistPattern: []` — transitive deps are NOT reachable by parent-walking. If you get "Module not found" in a workspace, add the dep explicitly to that workspace's `package.json`.
- Each push to `main` (and each tag) is split out to per-package read-only repos under the `sylament` org by `.github/workflows/monorepo-split.yml`. Keep each package self-contained so the split stays valid.

## Commands

PHP (run from repo root):
- `composer test` — full suite: every bundle's Pest suite + Rector dry-run + PHPStan (level max).
- `composer test:<bundle>` — single bundle's Pest suite, e.g. `composer test:support`. Each invokes `pest -c packages/<bundle>/phpunit.dist.xml --test-directory=packages/<bundle>/tests`.
- `composer test:static` — PHPStan only. `composer test:lint` — PHP-CS-Fixer dry-run. `composer test:rector` — Rector dry-run.
- `composer format` — apply Rector then PHP-CS-Fixer.
- Run one Pest file: `vendor/bin/pest -c packages/support/phpunit.dist.xml --test-directory=packages/support/tests path/to/Test.php` (or use `--filter`).

JS/CSS (run from repo root):
- `pnpm build` / `pnpm watch` — build/watch every asset workspace (`pnpm -r`). `pnpm test:types` — `tsc --noEmit` across workspaces.
- `pnpm format` (Biome write) / `pnpm test:lint` (Biome check). Biome only governs `packages/*/assets/src/**/*.{ts,js}`.
- `support`'s asset `build` runs `clean → tsc --noEmit → esbuild → tailwindcss` into `dist/` (plus a preflight scoping step). If `pnpm watch` dies in a non-TTY shell, run `cd packages/support/assets && pnpm watch` directly.

`symfony/asset-mapper` is a root dev dep needed for tests to exercise the AssetMapper path. Without it, bundle `prependExtension` hooks short-circuit (see below) so production consumers aren't forced to install it.

## Core architecture

**Bundle base class.** Every bundle extends `Sylament\Support\SylamentBundle` (an `AbstractBundle`). Its `isAssetMapperAvailable()` guard is why asset-mapper is optional: each bundle's `prependExtension()` registers its `assets/dist` directory into the `framework.asset_mapper.paths` config under the alias `@sylament/<pkg>`, but only when AssetMapper is actually present. Services are wired in `loadExtension()` via the PHP DSL configurator (no XML/YAML service files).

**Closure evaluation — the central pattern.** Almost every configurable value in the API can be either a literal or a closure, mirroring Filament. Two `support` pieces implement this:
- `Sylament\Support\Service\Invoker::call()` reflects a callable's parameters and resolves each by name: first from explicit `$parameters`, then via the `$resolveNamedParameter` callback (which returns a `ClosureParameter` wrapping the value, or `null` to skip), then by type-hint from the DI container, then by default value.
- `Sylament\Support\Concerns\EvaluatesClosures` is the trait classes use. `evaluate($value, $parameters)` returns `$value` as-is if it isn't callable, otherwise calls it through `Invoker::getInstance()`. `evaluatable($value)` wraps a callable as a `Closure` for deferred evaluation. Classes override `resolveDefaultClosureDependencyForEvaluationByName(string $name)` to declare which named closure parameters they expose, returning a `ClosureParameter::make(...)`.

**Static instance pattern.** `EvaluatesClosures` reaches the `Invoker` via `Invoker::getInstance()` so the container doesn't have to be threaded through every component constructor. The mechanism is reusable:
- A class implements `Sylament\Support\DependencyInjection\StaticInstanceInterface` and uses the `HasStaticInstance` trait.
- `StaticInstancePass` (a compiler pass) auto-tags every service definition whose class implements the interface.
- `StaticInstanceRegistry` is constructed with `tagged_iterator(StaticInstanceInterface::class)` and, on `SylamentSupportBundle::boot()`, calls `setInstance($self)` on each tagged service so the static accessor works.
- Tests call `StaticInstanceRegistry::unsetStaticInstances()` in `tearDown` (see `packages/support/tests/TestCase.php`) so state doesn't leak between cases.
  Currently only `Invoker` uses the pattern. Reuse it for future singleton-shaped services that need to be reachable from non-DI contexts.

**Component model (today: in `support`).** The abstract base is `Sylament\Support\Component\Component`, which only composes `EvaluatesClosures`. `Sylament\Support\Component\ViewComponent` extends it with Twig-backed rendering: `template()` / `templateData()` setters (each closure-evaluable), `getTemplate()` / `getTemplateData()`, and `render(Environment $twig)`. `Sylament\Support\Twig\Extension\ComponentExtension` exposes a `renderComponent(component)` Twig function backed by `ViewComponent::render()`. Higher bundles will subclass these (e.g. `schemas`' `Component` and the form fields); today those subclasses don't exist yet. Favor adding a focused `Concerns/Has*` trait over widening an existing class.

## Conventions

- PHP: `declare(strict_types=1)` everywhere; PHPStan **level max** must stay green (only `trait.unused` and `new.static` are globally ignored). PSR-4 namespace `Sylament\<Bundle>\` → `packages/<bundle>/src/`, tests `Sylament\<Bundle>\Tests\` → `packages/<bundle>/tests/`. Tests are Pest. Formatting is PHP-CS-Fixer + Rector — run `composer format`, don't hand-format.
- Test layout (canonical, currently lived out in `support`): each bundle's `tests/` is split into two buckets — `Unit/` for tests with no kernel boot (pure logic, compiler passes against a hand-built `ContainerBuilder`, trait behavior with fixture classes), `Integration/` for tests that boot the kernel or hit the container. Inside each bucket, **mirror the `src/` tree exactly**: `src/DependencyInjection/Compiler/StaticInstancePass.php` → `tests/Unit/DependencyInjection/Compiler/StaticInstancePassTest.php`, `src/SylamentSupportBundle.php` → `tests/Integration/SylamentSupportBundleTest.php`. `tests/Pest.php` attaches `TestCase` (the `KernelTestCase` subclass) only to `Integration/`; Unit tests use Pest's default base. Test file names match the SUT (`FooTest.php` for `Foo.php`). The other eight bundles currently ship a placeholder `Feature/ExampleTest.php` — adopt the Unit/Integration split as real tests land, don't entrench `Feature/`.
- Fixture layout: `tests/Fixtures/` mirrors `src/` for per-test fixture classes (e.g. fixtures supporting `src/DependencyInjection/` tests live in `tests/Fixtures/DependencyInjection/`). Global test-app infrastructure (the `Kernel`, fixture service-config files) stays at the root of `tests/Fixtures/`. Don't dump everything flat — put each fixture next to the subsystem it exercises so it's findable as the suite grows.
- Test Kernel wiring: the test `Kernel` PSR-4-loads everything under `tests/Fixtures/` recursively with `autowire + autoconfigure` defaults (excluding `Kernel.php` itself). Adding a new fixture means dropping a class in the right `Fixtures/` subfolder — never edit the Kernel to register individual fixture services. Services stay private by default; `KernelTestCase::getContainer()` returns the `test.service_container` which exposes privates to tests, so you rarely need anything public. When a fixture genuinely must be public in the real container, opt in with `#[Autoconfigure(public: true)]` on the fixture class itself — never widen the Kernel defaults. If a fixture needs special construction, write a small config (e.g. `Fixtures/services_special.php`) and `import()` it from the Kernel rather than hand-wiring inline.
- Pest tests: inside `it()` / `test()` closures, reach static `KernelTestCase` helpers through `$this::` (e.g. `$this::getContainer()`, `$this::bootKernel()`) — never `static::` or `self::`. Pest binds `$this` to the configured `TestCase`, so `$this::foo()` resolves to that class's static method; `static::foo()` resolves against the anonymous closure scope and is wrong.
- Pest tests: don't call `bootKernel()` explicitly — `KernelTestCase::getContainer()` auto-boots when no kernel is booted. Just call `$this::getContainer()->get(...)` directly. Only boot explicitly when you need to pass options (`$this::bootKernel(['environment' => 'foo'])`) or grab the kernel object itself.
- TS: strict mode, `target` ES2022, ESM, bundler resolution (`tsconfig.base.json`). Biome enforces `useImportType`, `useConst`, and bans `any` and non-null assertions (`!`) as errors. Stimulus is a peer dep pinned to `^3` via workspace overrides.
- CSS: semantic `sy-<pkg code>-*` classes only in Twig (never utilities — template scanning is off), e.g. `sy-fo-input`, `sy-sc-grid`; every component template root also carries the bare `sy` marker class; `@apply` authoring in `packages/<pkg>/assets/src/styles/components/<name>.css`; dynamic per-instance values via inline `--sy-*` CSS variables; colors through the `--sy-*` runtime tokens; Tailwind preflight is never shipped page-wide — `support`'s build generates a `.sy`-scoped copy from the installed Tailwind (`assets/scripts/scope-preflight.mjs`). Full contract: `docs/styling.md`.

## Definition of done

- `composer test` green (Pest + Rector dry-run + PHPStan max).
- `pnpm test:types` and `pnpm test:lint` green.
- `composer format` applied; no hand-formatting.

_Keep this file current. When commands, conventions, architecture, or gates change, update the relevant section in the same change._
