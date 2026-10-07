# PHP internals

The reasoning behind `.claude/rules/php.md`: how closures are evaluated and typed, how components are built, when a service becomes a static instance, where traits and contracts are filed, and why the helper, optional-package, DBAL and comment rules read as they do. Where a port-trail file already holds the reasoning, this file links it rather than repeating it.

## Closure evaluation

Almost every configurable value in the API is either a literal or a closure, as in Filament. Two `support` pieces implement it.

`Sylament\Support\Concerns\EvaluatesClosures` is the trait. `evaluate($value, $namedInjections, $typedInjections)` keeps Filament's parameter names. It returns `$value` unchanged unless it is a `Closure` instance, so a literal string that happens to name a function, such as `'date'` or `'strtoupper'`, is a value and is never invoked. A Closure goes to `getInvoker()->call()` with a resolver and the evaluating class's name, which the error message uses.

`Sylament\Support\Evaluation\Invoker::call()` reflects the callable and fills each parameter from the first source that answers:

1. `$namedInjections`, by parameter name.
2. `$typedInjections`, by class-string. A `self` or `parent` hint is normalised against the declaring class.
3. The resolver callback, which returns a `ClosureParameter` or `null` to pass.
4. The container, only when `has()` knows the type.
5. The declared default.
6. `null`, when the parameter is optional or nullable.

Otherwise it throws. A `Closure` given as a named or typed injection *value* is called with no arguments and its result is injected, which is Laravel's `value()` behaviour.

A variadic parameter still takes an explicit named, typed or resolver injection. When none applies it is skipped, leaving `...$x = []`, instead of being filled from the container, a default or `null`. Filament passes a spurious `null` there.

There is no `app()->make()`-style construction of an unbound class, because Symfony's container cannot build a class it was not told about. Why the loop is a service rather than inline in the trait, and why `ClosureParameter` replaces Filament's array wrapping, is in [component.md](../progress/support/component.md#closure-resolution-lives-in-a-service).

The trait's resolver asks `resolveDefaultClosureDependencyForEvaluationByName()`, then `resolveDefaultClosureDependencyForEvaluationByType()`, and finally hands over the instance itself. It does that when the parameter is named after `$evaluationIdentifier`, or when its type is a class or interface the instance `is_a()`. Every class that Filament gives an identifier sets the same one: `component`, `schema`, `action`, `group`, `table`, `column`, `filter`, `indicator`, `summarizer`. So `fn ($component) => …`, `fn (Section $component) => …` and `fn ($schema) => …` all receive the object being configured.

## The closure-typing convention

Setters take native `Closure` unions and assign directly, with no `@param`, exactly as Filament writes them:

```php
public function heading(string|Markup|Closure|null $heading): static
{
    $this->heading = $heading;

    return $this;
}
```

An arity spec (`callable(): T`, `Closure(): T`) is never written for a closure whose parameters the `Invoker` fills. PHPStan cannot express parameters injected by reflection, so a published arity-zero spec makes every consumer's analysis reject `fn (Section $component) => …`.

### `evaluate()`'s generic

`evaluate()` is annotated `@template T @param T|Closure $value @return T`. PHPStan infers `T` by subtracting `Closure` from the property's union, so a `string|Closure|null` property evaluates to `?string`, and the property needs no docblock beyond its native type. An array-shaped property keeps a docblock with bare `Closure` entries, widened to include what the closures return where that differs (schemas' `HasColumns`).

A callback that the code invokes directly with fixed arguments is a different case: a spec is truthful there. The state casts' `normalize()` helpers carry one (`@param callable(mixed): ?TValue $castItem`).

## The component model

A component's `make()` calls `static::newInstance()`, which calls `ComponentResolver::resolve()->instantiate(static::class)`, and then `configure()`. The resolver asks its class-keyed service locator first; the bundle wiring that fills it is in `.claude/rules/bundles.md`. A class the container knows comes back autowired and non-shared, its constructor dependencies resolved, private services included, and its `#[Required]` setters run. A class nobody registered falls back to `new $class()`, so a subclass with constructor arguments throws the ordinary too-few-arguments error unless it is a registered service. The details, including why the pass runs at default priority, are in [component.md](../progress/support/component.md#newinstance-routes-every-make-through-a-locator).

Services reach a component through a `#[Required]` setter rather than its constructor, since `make()` takes no dependencies. A `new`-built object never runs the setter, so each getter answers one of two ways:
- It builds a dependency-free fallback where one exists. `EvaluatesClosures::getInvoker()` builds an `Invoker` over an empty container, which evaluates closures but resolves no services into them. Schemas' `CanAccessProperties` does the same.
- It throws a `LogicException` naming the fix where no fallback could work, as `BaseFileUpload`'s upload store does.

### The template context

`ViewComponent::render()` passes `component`, then `getExtraTemplateData()`, then the user's `templateData()`, which wins on a key collision. `getExtraTemplateData()` is the Twig-side port of the local-variable preamble in Filament's `toEmbeddedHtml()`. It holds only what a template cannot get from one getter call: generated ids, fallback chains, cross-trait policy such as `Section`'s `collapsible`, and child schemas that need constants or mutations. A plain getter is read in the template as `component.x` and never mirrored into it.

### A `LiveProp`'s getter shadows it

UX LiveComponent de- and rehydrates a `LiveProp` through Symfony's property accessor (`LiveComponentHydrator::dehydrate()`), which prefers a same-named getter over the property. So `getFoo()` decides what `$foo` puts on the wire.

That is load-bearing where the getter mints the value. Tables' `getTableHostId()` fills `$tableHostId` lazily, precisely because the dehydration read is the minting read. It is a defect where the client writes the prop back: an element bound to a model the getter retyped is permanently dirty and fires its `onUpdated` hook on every request.

There are two bridges, and neither renames Filament's getter:
- `dehydrateWith:` returns the value the element speaks ([has-table.md](../progress/tables/has-table.md#the-writable-props-dehydrate-in-their-elements-wire-vocabulary)).
- Renaming the property removes the shadow, and `fieldName:` keeps Filament's wire name, as `$selectedTableRecordKeys` does ([renames.md](../progress/renames.md#renames)).

## Static instance pattern

A static instance is a service reachable from a static method or a free function, where no injected property is in scope. That is the whole test. An instance method takes a `#[Required]` setter instead, which is why `EvaluatesClosures` reaches its `Invoker` that way. The cost of the trade is that a `new`-built object gets a fallback dependency, or an exception, rather than the container's.

The mechanism:
- The class implements `Sylament\Support\DependencyInjection\Contracts\StaticInstance` and uses `DependencyInjection\Concerns\HasStaticInstance`.
- `StaticInstancePass` tags every definition whose class implements it.
- `StaticInstanceRegistry` takes the tagged services, and `SylamentSupportBundle::boot()` calls `setInstance()` on each.

Two classes use it, each pinned by a static call site: `ComponentManager`, by `Configurable::configureUsing()`, and `ComponentResolver`, by `Component::newInstance()`.

Before accepting a free function as the reason, check whether it should be a free function at all. `IconManager` stopped being a static instance once the icon helper it served moved onto its one caller, `IconExtension` ([icon-manager.md](../progress/support/icon-manager.md)).

The two services are defined differently on purpose:
- `ComponentManager` is `factory(ComponentManager::resolve(...))`, so the booted service and the lazily created outside-DI instance are the same object.
- `ComponentResolver` is a plain `set()` whose `$components` defaults to `null`, and `ComponentResolverPass` replaces that argument with the locator. Only the container-built instance carries the locator, and `resolve()` is purely the fallback for code running outside a kernel.

Tests reset the instances between cases: kernel tests through `StaticInstanceRegistry::unsetStaticInstances()` in `TestCase::tearDown()`, and Unit tests through the `afterEach` hook in each package's `tests/Pest.php`, which names each class. A new static instance has to be added to those hooks. Why the pattern replaces Laravel's scoped container is in [support-bundle.md](../progress/support/support-bundle.md#static-instance-boot-replaces-laravels-container-scoping).

## Global configuration

`ComponentManager` is the port of Filament's `configureUsing()`. `X::configureUsing($closure)`, through the `Configurable` trait, registers a closure keyed by class. Every `make()` runs `configure()`, which walks the class hierarchy root-first:
1. At each class, it calls `setUp()` if that is the class declaring it.
2. It then runs that class's registered closures.
3. After the walk, it runs every `isImportant` closure.

`during:` scopes a registration to one call of the given closure and returns that closure's result. Without it, `configureUsing()` returns a closure that removes the registration. See [component-manager.md](../progress/support/component-manager.md).

## Where traits and contracts go

Concerns and contracts sit one directory per object tier, placed by who uses them rather than by what they do:
- `src/Components/Concerns/` holds the nested-component tier's traits.
- `src/Concerns/` holds the root object's traits, the host traits and general plumbing.
- `schemas` has both tiers, because `Schema` is the root object and components nest inside it.
- `actions` has only the root tier: `Action` and `ActionGroup` *are* actions' root objects, and `HasActions`, the host trait, sits beside their traits.
- `tables` follows Filament's own split: `Table/Concerns/` for the `Table` object, `Columns/`, `Filters/`, `Grouping/` and `Columns/Summarizers/` for their tiers, and `src/Concerns/` for the host traits such as `HasTable`.
- `support` composes nearly everything into components, so `Configurable` lives in `Components/Concerns/` beside `HasIcon` and the rest.
- `support` keeps two traits at root. One is `EvaluatesClosures`, which `ColorManager`, a plain class, also composes ([translations.md](../progress/translations.md#namespace-segments)). The other is `HasDefaultDataFormattingSettings`, which a root object, `Tables\Table`, composes ([has-default-data-formatting-settings.md](../progress/support/has-default-data-formatting-settings.md)).

A trait any component uses lives under `Components/` even when the root object uses it too, as schemas' `CanAccessProperties`, `HasColumns` and `HasGap` do for `Schema`. The root `Concerns/` holds only what components never import.

### The by-import trap

The two tiers share trait names with different meanings. Schemas has `BelongsToRecord`, `CanBeDisabled`, `Cloneable`, `HasHeadings`, `HasKey` and `HasState` in both. `use HasState;` therefore means one trait in `Schema` and another in `Component`, and grepping the short form conflates them. Read the import.

The interface and trait names `Has*` and `InteractsWith*` are also swapped relative to Filament ([renames.md](../progress/renames.md#interface-and-trait-names-are-swapped)).

### Contracts

Contracts follow the same line:
- A contract the host implements is root: `InteractsWithActions`, `InteractsWithSchemas`.
- One a component implements is in `Components/Contracts/`: `CanConcealComponents`.
- One that neither tier implements is filed under the capability directory that consults it. Schemas' `Validation\Contracts\ImplicitConstraint` is a marker that a consumer puts on a Symfony `Constraint`, so it sits beside `Validation\PresenceGate`.

No interface carries an `Interface` suffix, because `Contracts\` already says what it is.

### Why not feature directories

Filament sub-groups no `Concerns/` directory: actions alone ships fifty traits flat. Regrouping by feature (`Concerns/State/`, `Concerns/Layout/`) would break the namespace mapping that migrating users rely on, and it would force a placement judgment on every trait still to port. Revisit only at parity, when the categories can be read off the finished set instead of guessed.

### Engines

The engine a trait delegates to is filed by capability at `src/` level, never under `Concerns/` with its trait and never beside the class it operates on: `EvaluatesClosures` → `Evaluation/Invoker`, `Configurable` → `Configuration/ComponentManager`. Filament files `ComponentManager` under `Components/`, which is not where it belongs here, because the bundle's `Components/*.php` glob registers every top-level class there as a component service ([component-manager.md](../progress/support/component-manager.md#the-manager-lives-in-configuration-not-components)). A static instance defined twice is also not a harmless duplicate: both definitions are tagged, and the registry calls `setInstance()` for each.

## Helpers

Every function in either `helpers.php` is pure, and that is load-bearing. A helper that needs a service can only reach one globally, which is the shape that made `IconManager` a static instance. When a Filament helper reaches a facade, as `get_component_color_classes()` does, its port goes onto the service or Twig extension that owns the dependency.

Placement is by source:
- A Laravel helper's port goes in support's file, in the `Sylament\Support` namespace.
- A Filament-forms helper's port goes in forms' file, in the `Sylament\Forms` namespace.

Each file is a Composer `files` autoload, imported with `use function`.

Where `docs/internals/principles.md` §1 maps a helper onto a Symfony mechanism, as it maps `data_get()`/`Arr::get()` onto `symfony/property-access`, use that mechanism. Never inline a helper's body at a call site: a hand-rolled `is_array` ternary once shipped five call sites away from `wrap()`.

`blank()` and `filled()` mirror Laravel's, except that a `Twig\Markup` is read as text, `blank()` trims with `mb_trim`, and there is no Eloquent-model short-circuit. Each delta is reasoned in [helpers.md](../progress/support/helpers.md#divergences).

## Optional packages fail loudly at the point of use

A guarded optional integration is code that is dead without a `suggest`ed package. It sits behind a `class_exists()` or `instanceof` guard, so the missing symbol is never resolved. When a developer explicitly opts into the feature, the else-branch throws a `LogicException` naming the package to install, and never falls through to a degraded result:
- `TextColumn::markdown()` without `league/commonmark` ([text-column.md](../progress/tables/text-column.md#markdown-throws-for-the-package-it-suggests-rather-than-falling-through)).
- `TextInput::currentPassword()` without `symfony/security-core`.

The exception is an integration that Filament itself treats as ambient. An action's success and failure notifications are sent by every action without being asked for, and `sylament/notifications` is only suggested by `actions`. So `CanNotify` returns early when the class is missing, rather than breaking every action in an install without toasts ([action.md](../progress/actions/action.md#the-notification-arm-is-a-guarded-soft-integration-not-a-require)). The manifest side of the pattern is in `docs/internals/dependencies.md`.

## DBAL parameter types

No package declares `doctrine/dbal`. The omission rests on `doctrine/orm: ^3.7`, which requires `^3.8.2 || ^4`, and on `ParameterType` and `ArrayParameterType` keeping their names across that range.

A name is all that buys. Both are final classes of int constants on DBAL 3 and native enums on DBAL 4, so they may be used as values only: a constant read, a `match` subject, a `setParameter()` argument, or a negative `instanceof` guard. A declared `: ParameterType` is a `TypeError` on every DBAL 3 application, and no suite here can see it, because only DBAL 4 is installed. `packages/schemas/tests/Architecture/DbalParameterTypeDeclarationTest.php` is the gate that stands in for the missing Composer-level check.

## No inline PHPStan ignores

PHPStan runs at level max over every `src/`, with `trait.unused` as the only global ignore, and `reportUnmatchedIgnoredErrors` is on, so a stale ignore fails the run. An inline `@phpstan-ignore` hides a real defect at the one place a reader is least likely to question it. The three that once existed all masked the same unguarded nullable, and the fix was to make it throw: `BelongsToSchemaComponent::getSchemaComponentOrFail()`. An error that genuinely cannot be fixed belongs in a baseline file, where it is counted and reviewable. `packages/schemas/tests/Architecture/StaticAnalysisSuppressionTest.php` enforces the ban.

## Comments

A comment stays only when it states something the code cannot. Type-carrying PHPDoc is load-bearing at level max and never optional. Beyond it, three kinds of comment earn their place:
- **Contract prose a signature cannot carry:** a null meaning, a side effect, or what a `mixed` return actually is, as in `ComponentManager::configureUsing()`.
- **An invariant stated at its enforcement point:** the `// Security:` blocks in forms' upload code, whose downstream sinks rely on the check instead of re-validating.
- **Why a `catch` swallows:** an empty or bare catch is the one shape a reader reliably "fixes", as in `HasActions`' `catch (Cancel)`.

Even these yield to the port trail. Once a trail file explains a swallow, the comment is a duplicate, as happened to `StaticInstancePass`'s `catch (Throwable)`.

Provenance is no defence: a comment that Filament or Laravel also carries is judged on the same terms, because comment style is idiom, translated like Blade → Twig, not API. When removing a comment, never delete reasoning that has no home in the trail; write it there first.
