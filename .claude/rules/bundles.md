---
paths:
  - "packages/*/src/*Bundle.php"
  - "packages/*/src/DependencyInjection/**"
description: "How a bundle class wires its services, config tree, asset path and component registration."
---
# Bundle rules

- **Extend `Sylament\Support\SylamentBundle`** (an `AbstractBundle`), wire services in `loadExtension()` through the PHP DSL configurator and declare consumer settings in `configure()`; there are no XML or YAML service files, so nothing else needs editing.
- **Prepend `assets/dist` as `@sylament/<pkg>`** to `framework.asset_mapper.paths` from `prependExtension()`, only in a bundle with an `assets/` workspace and only behind `isAssetMapperAvailable()`; `symfony/asset-mapper` is a root dev dependency for the tests alone, so production consumers never have to install it.
- **Put a setting under the `sylament` alias** only when the whole toolkit shares it and a `support` service backs it; `SylamentSupportBundle` claims that alias through `$extensionAlias`, and every other package keeps its derived `sylament_<pkg>` alias, since a bundle cannot extend another bundle's config tree.
- **Never let a second bundle claim `sylament`,** and give any concrete meta-bundle named `SylamentBundle` its own `$extensionAlias`: the kernel keys extensions by alias without a duplicate check, so the loser's `loadExtension()` silently never runs and the failure surfaces later as a missing service.
- **Leave support's alias alone** even though it breaks Symfony's bundle-name convention; `AbstractBundle` skips the naming check, and `SylamentSupportBundleTest` fails if it is "corrected" back to `sylament_support`.
- **A config node whose value can be a string or a map takes `variablePrototype()`** (`sylament.colors`); a `scalarPrototype()` rejects the map arm.
- **Call `->normalizeKeys(false)` on every node keyed by user-supplied strings,** because `ArrayNode` silently rewrites a dashed key that has no underscore (`modal.close-button` → `modal.close_button`) and it then matches nothing; `SylamentSupportBundleTest` pins both of support's keyed nodes.
- **Register component classes with a `load()` glob that names only the directory levels holding components** (`Components/{*,Tabs/*,Wizard/*}.php`), so `Concerns/` and `Contracts/` stay out without an exclude list; a new nested component directory needs its level added, or its classes fall back to `new` and lose autowiring.
- **`exclude()` only a non-component that a glob cannot avoid** (tables' `Filters/FilterIndicator.php`, a value object); abstract bases need nothing, since the loader tags them `container.excluded` and every tag lookup skips them.
- **`set()` a component class that sits at `src/` root explicitly** (actions' `Action`, `ActionGroup` and every prebuilt action, notifications' `Notification`); no component glob reaches that level.
- **Keep forms' `Validation/*Validator.php` glob** with `autowire()` and `autoconfigure()`: a constraint validator that needs a service must come from the container, because `ContainerConstraintValidatorFactory` otherwise builds it with `new`, and the `*Validator.php` suffix keeps the constraint value objects and `Validation/Concerns/` out.
- **Give such a validator a nullable dependency that throws `LogicException` when unwired** (`UniqueValueValidator`); a probe that silently does nothing is a security-shaped failure.
- **Leave component registration to the `sylament.component` tag:** support's `build()` autoconfigures every `Component` subclass with it, and `ComponentResolverPass` marks each tagged definition non-shared and collects it into the class-keyed locator `make()` resolves through.
- **Static instances wire themselves:** `StaticInstancePass` tags every definition whose class implements `StaticInstance`, and support's `boot()` runs `StaticInstanceRegistry::bootStaticInstances()` over that tag, so a bundle adds no wiring for one.
- **Register class-backed Twig components with one `->load(__NAMESPACE__ . '\\Twig\\Components\\', __DIR__ . '/Twig/Components/')->autowire()->autoconfigure()`;** put an alias-less dependency on the constructor parameter as `#[Autowire(service: '…')]`, never in `args()`, so a new component never edits the bundle.
- **Colour-policy globs** follow `colors.md`; `ColorManager` resolves policies from the container.

Background: `docs/internals/bundles.md`, `docs/progress/support/support-bundle.md`, `docs/progress/support/component.md`, `docs/progress/forms/can-be-validated.md`.
