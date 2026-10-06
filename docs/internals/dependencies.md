# Package dependencies

The reasoning behind `.claude/rules/manifests.md`: what each package's `composer.json` must declare, when a dependency is a `require`, a `suggest` or a root `require-dev`, and why some imports are deliberately left undeclared. How the manifests are wired into one Composer project is in `docs/internals/monorepo.md`.

## Why a package declares what a sibling already pulls in

The monorepo resolves every package out of one root `vendor/`, so a class autoloads whether or not the importing package's manifest names its vendor. Nothing in the monorepo fails when a line is missing. The failure appears only after the `split-monorepo` job in `.github/workflows/ci.yml` publishes the package alone and someone installs it. For example, every package requires `sylament/support`, but each still declares its own `symfony/ux-twig-component` or `symfony/ux-live-component`.

Three things count as using a package:
- a PHP symbol imported by `src/`, such as `actions`' `Twig\Components\Modals` type-hinting `AsTwigComponent` and `ComponentStack`;
- a Twig tag or extension that a shipped template cannot parse without, such as `{% props %}` from `symfony/ux-twig-component`, which templates in `actions`, `forms`, `panels`, `support` and `tables` use;
- a PHP extension a call needs. `tables` declares `ext-intl` because `TextColumn::money()`/`numeric()` and the summarizers format through `NumberFormatter`. Filament declares `ext-intl` on `filament/support` instead of `filament/tables`, which is the inherit-through-a-sibling shape the split would publish broken, so here it sits where the call is.

## Require, suggest or root require-dev

An import that some code path always reaches is a `require`. `tables` requires `symfony/html-sanitizer` and `symfony/validator` because `#[Required]` setters import them unconditionally (`Columns\Concerns\CanFormatState::setHtmlSanitizer()`, `Columns\Concerns\CanBeValidated::setValidator()`).

A guarded optional is code that is dead without the package, so the missing symbol is never resolved: a branch gated on `class_exists`, `interface_exists` or `instanceof` (PHP does not autoload for `instanceof` or for a property's declared type), or a service definition that nothing instantiates unless the package is present. It takes a `suggest` instead of a `require`.

A dev tool, or the wiring a consuming application installs for itself, is a root `require-dev`.

`league/flysystem` shows all three:
- `forms` requires `league/flysystem: ^3`, because its upload code writes through `FilesystemOperator` on every save.
- `support` only suggests `league/flysystem` and `league/flysystem-bundle`. Its `Filesystem\FilesystemRegistry` imports the operator, but support never constructs that service; without flysystem the definition is never instantiated.
- `league/flysystem-bundle: ^3.7` is a root `require-dev`. The bundle turns configuration into tagged storages, and that wiring belongs to the consuming application. Support's test kernel registers the bundle over two local storages so the tag the registry collects is tested.

## The guarded optionals and their pins

A `suggest` is invisible to Composer's own checks, and a renamed contract turns a guard into a branch that is silently never taken. Each guard therefore needs a test that fails when the link breaks.

| Package | Optional | Guard | Failure when used without it | Pin |
|---|---|---|---|---|
| `actions` | `sylament/tables` | `instanceof InteractsWithTable` in `Concerns\HasActions` | `LogicException` when a table context reaches a host that is not a table | `packages/tables/tests/Integration/RecordActionsResolutionTest.php` |
| `actions` | `sylament/notifications` | `class_exists(Notification::class)` in `Concerns\CanNotify` | none: the notification is skipped silently | none |
| `support` | `league/flysystem`, `league/flysystem-bundle` | structural: `FilesystemRegistry` is never built without them | — | `packages/support/tests/Integration/SylamentSupportBundleTest.php` ("collects the flysystem storages by name") |
| `tables` | `league/commonmark` | `class_exists(GithubFlavoredMarkdownConverter::class)` in the column and the summarizer `CanFormatState` | `LogicException` naming the package | `packages/tables/tests/Unit/Columns/Concerns/CanFormatStateTest.php`, which reads the guard site, because no install that has the library can reach the other branch |
| `forms` | `symfony/security-core`, `symfony/security-bundle` | `class_exists(UserPassword::class)` in `TextInput::currentPassword()` | `LogicException` naming both packages | `packages/forms/tests/Integration/Components/TextInputTest.php` |
| `support` | `symfony/asset-mapper` | `SylamentBundle::isAssetMapperAvailable()` around each bundle's `prependExtension()` | none: the asset path is not registered | no `suggest` entry (see the gaps below) |

The rule is to throw where the user opts in. `CanNotify` is the one guard that degrades silently. Filament has no counterpart guard, because `filament/actions` requires `filament/notifications`.

## Why a return type forces a require

A guard can only stand in front of a branch. `AttachAction::getRecordSelect()` and `AssociateAction::getRecordSelect()` build and return a forms `Select` unconditionally, so `actions` requires `sylament/forms`. Since `forms` requires `actions` back, that is a second cycle beside `actions`⇄`schemas`; in both, each side's `src/` imports the other (for example `schemas`' `Schema` imports `Action`, and `actions`' `CreateAction` imports `Schema`).

Filament's manifests have `actions`⇄`forms` directly and `schemas` → `actions`, but `filament/actions` reaches `filament/schemas` only through `filament/forms`. Here `actions` requires `schemas` directly, because its `src/` imports it.

`packages/actions/tests/Architecture/ManifestTest.php` reads actions' `composer.json`, because removing the `sylament/forms` line would fail nothing else in the monorepo.

## Why support stays lean

Every package requires `support`, so anything support declares, every package inherits. Support therefore declares only what its own code uses. It does not reference `Symfony\UX\LiveComponent`, so that stays on the packages that do. It does require `symfony/string`, for `get_record_label()` (`u()->kebab()`) and `plural()` (`EnglishInflector`) in `helpers.php`.

## The transitive guarantees behind the omissions

An omission is safe only while a package the importer requires hard-requires the omitted one, over a range on which every imported name exists. These are the guarantees, read from the installed manifests:

| Omitted | Imported by | Guaranteed by |
|---|---|---|
| `twig/twig` | `actions`, `forms`, `notifications`, `panels`, `schemas`, `tables` | `sylament/support` requires `twig/twig: ^3.27` |
| `symfony/type-info` | `schemas` (`State\RecordTypeReader`, five symbols) | `symfony/property-info` requires `^7.4.7\|^8.0.7` |
| `doctrine/dbal` | `actions`, `schemas`, `tables`, support's test infrastructure | `doctrine/orm` requires `^3.8.2 \|\| ^4` |
| `doctrine/persistence` | `actions` (`ManagerRegistry`, `ObjectManager`), `tables` (`ManagerRegistry`) | `doctrine/orm` requires `^3.3.1 \|\| ^4` |
| `doctrine/collections` | `actions` (`new ArrayCollection()`), `tables` (`instanceof Collection`) | `doctrine/orm` requires `^2.2 \|\| ^3.1` |
| `symfony/polyfill-php86` | `schemas`, `tables` (the global `SortDirection` enum) | `doctrine/orm` requires `^1.37` |
| `psr/container` | `support`, `panels` | `symfony/service-contracts`, which support requires, requires `^1.1\|^2.0` |
| `symfony/config` | `support`, `forms` (`DefinitionConfigurator`), `panels` (`FileResource`) | `symfony/framework-bundle` requires `^7.4.4\|^8.0.4` |
| `symfony/dependency-injection`, `symfony/http-kernel`, `symfony/framework-bundle` | every bundle | ambient: framework-bundle requires the other two |
| `league/flysystem` | `tables` (`Columns\ImageColumn`) | `sylament/forms` requires `^3` |

Notes on particular rows:
- **`doctrine/dbal`.** Every imported name exists on DBAL 3 and DBAL 4. That was checked against a cached DBAL 3 archive as well as the installed DBAL 4, because only DBAL 4 is installed here. `ParameterType` and `ArrayParameterType` are the trap: on DBAL 3 they are final classes of `int` constants, and on DBAL 4 they are native enums. A declared `: ParameterType` is therefore a `TypeError` on every DBAL 3 application, and no gate here can see it. `packages/schemas/tests/Architecture/DbalParameterTypeDeclarationTest.php` stands in for the check Composer cannot make. A negative `instanceof` guard, as in `tables`' `Query\AggregateQuery`, is a value test and is allowed.
- **`doctrine/collections`.** `actions`' `Replication\RecordReplicator` instantiates `ArrayCollection`, so an ORM-less `sylament/actions` would fatal there; `tables` only tests `instanceof`, which PHP does not autoload for. This is the omission to declare first if `doctrine/orm` ever stops being an `actions` requirement.
- **The framework-bundle trio** is the weakest omission. `panels`' `Routing\PanelRouteLoader` implements `RouteLoaderInterface` from framework-bundle itself, which makes it a first-party import rather than a transitive guarantee. A split `sylament/panels` used outside a FrameworkBundle application fatals there. It stays undeclared because a panel cannot be assembled without framework-bundle anyway.
- **`league/flysystem` in `tables`.** Only `ImageColumn`'s disk-path arm reaches the flysystem symbols, through the registry. A column whose state is already a URL works with no flysystem installed.

## Known gaps

The rule is broken in a few places, and no decision covers these imports. Each is safe today only through a sibling, which is the hazard the rule exists for. None is recorded as a deliberate omission:
- `actions`, `forms` and `panels` call `mb_*` functions without declaring `ext-mbstring`. `schemas`, `support` and `tables` declare it.
- `forms` imports `Symfony\Component\PropertyAccess\Exception\AccessException` without declaring `symfony/property-access`.
- `panels` imports `Symfony\Contracts\Service\Attribute\Required` without declaring `symfony/service-contracts`.
- `support` guards `symfony/asset-mapper` but no manifest suggests it.
- `actions`' notifications guard is silent and unpinned.

## Third-party grants

`symfony/*` packages are pre-approved, and every other vendor needs explicit sign-off. Each grant is recorded as a carried decision on `docs/progress/roadmap.md`, together with the manifests it lands in.
