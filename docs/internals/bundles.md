# Bundles

How a Sylament bundle class is put together, and why the wiring rules in `.claude/rules/bundles.md` and `.claude/rules/colors.md` are shaped the way they are. Component behaviour (how `make()` resolves, the static-instance pattern, closure evaluation) is in `.claude/rules/php.md` and the port trail; this page covers only the container side.

## Why is asset-mapper optional?

`SylamentBundle::isAssetMapperAvailable()` returns true only when `AssetMapperInterface` exists and FrameworkBundle ships `Resources/config/asset_mapper.php`. Each bundle with an `assets/` workspace calls it from `prependExtension()` before prepending its `assets/dist` directory as `@sylament/<pkg>`. `infolists` and `widgets` have no workspace and no `prependExtension()`.

The guard lets a consuming application run without `symfony/asset-mapper`. The monorepo installs it only as a root dev dependency, so the test kernels exercise the prepended path while no package manifest requires it. See `docs/progress/support/support-bundle.md`, section "Asset registration".

## Why does an application register the bundles by hand?

Sylament has no Flex recipe, and Flex's fallback does not fire either. For a package without a recipe, Flex (read at v2.11.0) builds an auto-generated recipe from `SymfonyBundle::getClassNames()`, whose `isBundleClass()` accepts a candidate class only when its file contains the string `Symfony\Component\HttpKernel\Bundle\Bundle` or `Symfony\Component\HttpKernel\Bundle\AbstractBundle`. Every concrete bundle extends `SylamentBundle` and names neither; only `packages/support/src/SylamentBundle.php` does, and it is abstract. So Flex registers none of them, and an application lists each one it installs in `config/bundles.php`, as the demo does.

## Why does `support` own the `sylament` alias?

Settings the whole toolkit shares need one root, and `support` is the package whose services back them. `SylamentSupportBundle` sets `protected string $extensionAlias = 'sylament'`. Without it, `Symfony\Component\DependencyInjection\Kernel\AbstractBundle::getContainerExtension()` derives the alias from the class name minus `Bundle`, underscored: `sylament_support`.

The four nodes split into two kinds:

- `colors` and `icons` are maps registered onto a manager with `->call('register', [$config])`: `ColorManager::register()` and `IconManager::register()`. `colors` is a `variablePrototype()`, because a value is either a colour string to generate a palette from or an explicit shade map; `icons` is a `scalarPrototype()`.
- `default_filesystem_disk` (default null) and `temporary_file_url_expiry_minutes` (integer, at least 1, default 30) are scalars passed straight into `FilesystemRegistry`'s constructor. See `docs/progress/support/filesystem-registry.md`.

A higher package cannot add nodes under `sylament`, because a bundle cannot extend another bundle's config tree. So anything `forms` owns lives under `sylament_forms`, and so on.

## What happens if two bundles claim `sylament`?

Nothing loud. `ContainerBuilder::registerExtension()` is a plain assignment into an array keyed by alias, and the kernel's `prepareContainer()` (in `Symfony\Component\DependencyInjection\Kernel\KernelTrait` on Symfony 8.1) registers each bundle's extension in turn without checking for a collision. The second claimant replaces the first, the replaced extension's `loadExtension()` never runs, and the symptom is a missing service much later, never a duplicate-alias error.

The realistic claimant is our own: a concrete bundle named `SylamentBundle` derives exactly `sylament`. Today's `SylamentBundle` is the abstract base and is never registered, so it cannot collide. A real meta-bundle would have to set its own `$extensionAlias`.

## Why does the alias not trip Symfony's naming check?

`Symfony\Component\HttpKernel\Bundle\Bundle::getContainerExtension()` throws a `LogicException` when an extension's alias is not the underscored bundle name. `AbstractBundle` overrides that method and delegates to the DependencyInjection `AbstractBundle`, which performs no such check. The deliberate break of convention is why `SylamentSupportBundleTest` pins the alias: someone tidying the code would otherwise "correct" it back to the derived name.

## Why `normalizeKeys(false)`?

`ArrayNode::preNormalize()` rewrites any key that contains a dash and no underscore, unless the underscored form is already present. An icon alias such as `modal.close-button` would become `modal.close_button` and match nothing, with no error anywhere. `SylamentSupportBundleTest` resolves the underscored form to null for both `icons` and `colors`. The colour node's reasoning is also in `docs/progress/support/color-manager.md`, section "The config node is a variable prototype with key normalisation off".

## How do components reach the container?

Two independent mechanisms meet in `ComponentResolver`:

- **Autoconfiguration.** `SylamentSupportBundle::build()` calls `registerForAutoconfiguration(Component::class)->addTag('sylament.component')`. `ComponentResolverPass` then marks every tagged definition non-shared and collects them into a class-keyed service locator. Any `Component` subclass the application registers as a service is picked up the same way.
- **Sylament's own globs.** Each bundle `load()`s its component classes with a glob that names only the directory levels that hold components: `support` loads `Components/*.php`, `schemas` loads `Components/{*,Tabs/*,Wizard/*}.php`, `forms` loads `Components/{*,Repeater/*,TextInput/Actions/*}.php`, `tables` loads `Columns/{*,Summarizers/*}.php`, `Filters/*.php` and `Grouping/*.php`, and `panels` loads `Navigation/*.php`. Naming levels keeps `Concerns/` and `Contracts/` out without an exclude list. Tables' `Filters/` holds one class no level-naming can avoid, the value object `FilterIndicator`, so that glob carries an `exclude()`.

Classes at `src/` root sit outside every component glob, so they are `set()` one by one: actions' `Action`, `ActionGroup` and every prebuilt action, and notifications' `Notification`.

Abstract bases need no exclusion. Symfony's `FileLoader` registers an abstract class it meets in a glob as an abstract definition tagged `container.excluded`, and `ContainerBuilder::findTaggedServiceIds()` skips anything carrying that tag. `ComponentResolverPass` asks with `$throwOnAbstract` on, so this skip is what keeps it from throwing.

## Why are forms' validators a glob of their own?

`unique()` and `exists()` need the `AssociationReader` to run their DQL probe. `ContainerConstraintValidatorFactory` asks the container for a constraint's `validatedBy()` and falls back to `new $class()` when the container has no such service. So `SylamentFormsBundle` loads `Validation/*Validator.php` with `autowire()` and `autoconfigure()`. The `*Validator.php` suffix keeps out the constraints themselves, which are value objects and several take required constructor arguments, and `Validation/Concerns/`.

The validators take `?AssociationReader $associationReader = null`, so a validator built with `new` constructs fine and throws a `LogicException` naming the bundle at its first probe. The full reasoning is in `docs/progress/forms/can-be-validated.md`, section "unique() and exists() are one DQL probe behind a container service".

## How do static instances get booted?

`StaticInstancePass` walks every definition and adds the `StaticInstance` tag to any whose class implements that interface. `StaticInstanceRegistry` is constructed with `tagged_iterator(StaticInstance::class)`, and `SylamentSupportBundle::boot()` calls its `bootStaticInstances()`, which sets each service as its class's static instance. When to use the pattern at all is a PHP rule, not a wiring one.

## Why are class-backed Twig components one glob?

`actions`, `notifications` and `panels` each register their `Twig/Components/` directory with one `load()` plus `autowire()` and `autoconfigure()`. A dependency autowiring cannot see, because it has no class alias, is named on the constructor parameter instead: `Modals` takes `#[Autowire(service: 'ux.twig_component.component_stack')]`. Keeping the service id on the class means adding a component never touches the bundle beyond that one line.

## Why are colour policies public?

`ColorManager::resolveComponent()` fetches a policy with `$container->get($class)`, and a private service is unreachable that way. So every `Colors/Components/` glob carries `->public()`, which breaks the repository's private-by-default habit on purpose. Policies nest by directory, and each glob covers one level, so a package declares one glob per level its policies use: `support` three, `tables` two (its policies all nest), `notifications` two and `schemas` one. See `docs/progress/support/color-policies.md`, section "A policy is a public container service, resolved by class-string".
