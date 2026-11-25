# Package dependencies

The reasoning behind `.claude/rules/manifests.md`: what each package's `composer.json` must declare, when a dependency is a `require`, a `suggest` or a root `require-dev`, and why some imports are deliberately left undeclared. How the manifests are wired into one Composer project is in `docs/internals/monorepo.md`.

## Why a package declares what a sibling already pulls in

The monorepo resolves every package out of one root `vendor/`, so a class autoloads whether or not the importing package's manifest names its vendor. Nothing in the monorepo fails when a line is missing. The failure appears only after the `split-monorepo` job in `.github/workflows/ci.yml` publishes the package alone and someone installs it. For example, every package requires `sylament/support`, but each still declares its own `symfony/ux-twig-component` or `symfony/ux-live-component`.

Four things count as using a package:
- a PHP symbol imported by `src/`, such as `actions`' `Twig\Components\Modals` type-hinting `AsTwigComponent` and `ComponentStack`;
- a Twig tag or extension that a shipped template cannot parse without, such as `{% props %}` from `symfony/ux-twig-component`, which templates in `actions`, `forms`, `panels`, `support` and `tables` use;
- a PHP extension a call needs. `tables` declares `ext-intl` for `NumberFormatter` too, because `TextColumn::money()`/`numeric()` and the summarizers format through it. Filament declares `ext-intl` on `filament/support` instead of `filament/tables`, which is the inherit-through-a-sibling shape the split would publish broken, so here it sits where the call is;
- a shipped catalogue, which no PHP file imports for: the translator loads `translations/*.yaml` only through `symfony/yaml` and formats a `+intl-icu` message only through `ext-intl`, and FrameworkBundle enables the translator by default only when `symfony/translation` is a non-dev require, so every package that ships a catalogue requires `symfony/translation`, `symfony/translation-contracts`, `symfony/yaml` and `ext-intl`.

## Require, suggest or root require-dev

An import that some code path always reaches is a `require`. `tables` requires `symfony/html-sanitizer` and `symfony/validator` because `#[Required]` setters import them unconditionally (`Columns\Concerns\CanFormatState::setHtmlSanitizer()`, `Columns\Concerns\CanBeValidated::setValidator()`).

A guarded optional is code that is dead without the package, so the missing symbol is never resolved: a branch gated on `class_exists`, `interface_exists` or `instanceof` (PHP does not autoload for `instanceof` or for a property's declared type), or a service definition that nothing instantiates unless the package is present. It takes a `suggest` instead of a `require`.

A dev tool, or the wiring a consuming application installs for itself, is a root `require-dev`.

`league/flysystem` shows all three:
- `forms` requires `league/flysystem: ^3`, because its upload code writes through `FilesystemOperator` on every save.
- `support` only suggests `league/flysystem` and `league/flysystem-bundle`. Its `Filesystem\FilesystemRegistry` imports the operator, but support never constructs that service; without flysystem the definition is never instantiated.
- `league/flysystem-bundle: ^3.7` is a root `require-dev`. The bundle turns configuration into tagged storages, and that wiring belongs to the consuming application. The root suite's kernel registers the bundle over two local storages so the tag the registry collects is tested.

## The guarded optionals and their pins

A `suggest` is invisible to Composer's own checks, and a renamed contract turns a guard into a branch that is silently never taken. Each guard therefore needs a test that fails when the link breaks.

| Package | Optional | Guard | Failure when used without it | Pin |
|---|---|---|---|---|
| `actions` | `sylament/tables` | `instanceof InteractsWithTable` in `Concerns\HasActions` | `LogicException` when a table context reaches a host that is not a table | `tests/Packages/Actions/Concerns/HasActionsTest.php`: on a table host a row or bulk action mounts through the table and binds the row's record, and a table context on a host that is not a table falls through to the host's own resolver |
| `actions` | `sylament/tables` | `instanceof InteractsWithTable` in `Testing\TestsActions` | `LogicException` naming the host and the contract when a test names a table record on a host that is not a table | `tests/Packages/Actions/Testing/TestsActionsTest.php` |
| `actions` | `sylament/notifications` | `class_exists(Notification::class)` in `Concerns\CanNotify` | none: the notification is skipped silently | `tests/Packages/Actions/Concerns/CanNotifyTest.php`, one case per arm, on the root suite's kernel, which registers both bundles |
| `actions` | `sylament/notifications` | `class_exists(TestsNotifications::class)` in `Testing\Testable`, beside a `class_exists(NotificationRecorder::class)` that skips collecting after each request | `LogicException` naming the package when `assertNotified()` or `assertNotNotified()` runs without it, thrown before either names `TestsNotifications` | the condition, by use: no kernel here lacks the package, and a renamed recorder or matcher dead-ends every `assertNotified()`, which fails `tests/Packages/Actions/DeleteActionTest.php`; the throw itself needs an install without the package, so no test reaches it |
| `support` | `league/flysystem`, `league/flysystem-bundle` | structural: `FilesystemRegistry` is never built without them | — | `tests/Packages/Support/Filesystem/FilesystemRegistryTest.php` ("collects the flysystem storages by name") |
| `tables` | `league/commonmark` | `class_exists(GithubFlavoredMarkdownConverter::class)` in the column and the summarizer `CanFormatState` | `LogicException` naming the package | the condition, by use: a renamed converter class dead-ends every `markdown()` cell and figure, which fails `tests/Packages/Tables/Columns/Concerns/CanFormatStateTest.php` ("converts a markdown() state before sanitizing it, with the options and extensions it is given") and the summarizer's `tests/Packages/Tables/Columns/Summarizers/Concerns/CanFormatStateTest.php`; the throw needs an install without `league/commonmark`, so no test reaches it, and `tests/Architecture/ManifestTest.php` keeps the package a `suggest` |
| `forms` | `symfony/security-core`, `symfony/security-bundle` | `class_exists(UserPassword::class)` in `TextInput::currentPassword()` | `LogicException` naming both packages | the condition, by use: `tests/Packages/Forms/Components/TextInputTest.php` ("checks the signed-in user's password only when asked") finds the `UserPassword` constraint behind the flag, so a renamed constraint class fails it; the throw needs an install without `symfony/security-core`, so no test reaches it |
| `support` | `symfony/asset-mapper` | `SylamentBundle::isAssetMapperAvailable()` inside `SylamentBundle::prependAssetMapper()` | none: the asset path is not registered | `tests/Packages/Support/SylamentBundleTest.php`, both arms in one case derived over every `SylamentBundle` the kernel registers; no `suggest` entry (see the gaps below) |

The rule is to throw where the user opts in. `CanNotify` is the one guard that degrades silently. Filament has no counterpart guard, because `filament/actions` requires `filament/notifications`. `actions` cannot require `sylament/notifications` back, because `notifications` requires `actions`, so the toast arm is a `suggest` behind the guard and its pin needs a kernel that registers both bundles.

## Why a return type forces a require

A guard can only stand in front of a branch. `AttachAction::getRecordSelect()` and `AssociateAction::getRecordSelect()` build and return a forms `Select` unconditionally, so `actions` requires `sylament/forms`. Since `forms` requires `actions` back, that is a second cycle beside `actions`⇄`schemas`; in both, each side's `src/` imports the other (for example `schemas`' `Schema` imports `Action`, and `actions`' `CreateAction` imports `Schema`).

Filament's manifests have `actions`⇄`forms` directly and `schemas` → `actions`, but `filament/actions` reaches `filament/schemas` only through `filament/forms`. Here `actions` requires `schemas` directly, because its `src/` imports it.

`tests/Architecture/ManifestTest.php` reads actions' `composer.json`, because removing the `sylament/forms` line would fail nothing else in the monorepo.

## Why support stays lean

Every package requires `support`, so anything support declares, every package inherits. Support therefore declares only what its own code uses. It does not reference `Symfony\UX\LiveComponent`, so that stays on the packages that do. It does require `symfony/string`, for `get_record_label()` (`u()->kebab()`) and `plural()` (`EnglishInflector`) in `helpers.php`.

## The transitive guarantees behind the omissions

An omission is safe only while a package the importer requires hard-requires the omitted one, over a range on which every imported name exists. These are the guarantees, read from the installed manifests; `.claude/rules/manifests.md`'s list of deliberate omissions restates each one on purpose, because the rule has to be actionable at the manifest without this table, so a guarantee that changes is edited in both:

| Omitted | Imported by | Guaranteed by |
|---|---|---|
| `twig/twig` | `actions`, `forms`, `notifications`, `panels`, `schemas`, `tables` | `sylament/support` requires `twig/twig: ^3.27` |
| `symfony/type-info` | `schemas` (`State\RecordTypeReader`, five symbols) | `symfony/property-info` requires `^7.4.7\|^8.0.7` |
| `doctrine/dbal` | `actions`, `tables`, the root suite's `tests/Infrastructure/` | `sylament/schemas`, which each requires, requires `^4.6` |
| `doctrine/persistence` | `actions` (`ManagerRegistry`, `ObjectManager`), `tables` (`ManagerRegistry`) | `doctrine/orm` requires `^3.3.1 \|\| ^4` |
| `doctrine/collections` | `actions` (`new ArrayCollection()`), `tables` (`instanceof Collection`) | `doctrine/orm` requires `^2.2 \|\| ^3.1` |
| `symfony/polyfill-php86` | `schemas`, `tables` (the global `SortDirection` enum) | `doctrine/orm` requires `^1.37` |
| `psr/container` | `support`, `panels` | `symfony/service-contracts`, which support requires, requires `^1.1\|^2.0` |
| `symfony/config` | `support`, `forms` (`DefinitionConfigurator`), `panels` (`FileResource`) | `symfony/stimulus-bundle`, which `support` and `forms` require, requires `^7.4\|^8.0` |
| `symfony/twig-bridge` | the templates of `forms`, `notifications`, `panels`, `schemas`, `support` and `tables` (the `trans` filter) | through `support`: its `symfony/ux-icons` requires `symfony/twig-bundle`, which requires `^7.4\|^8.0` |
| `symfony/dependency-injection`, `symfony/http-kernel`, `symfony/framework-bundle` | every bundle | through `support`: its `symfony/stimulus-bundle` requires the first two and its `symfony/ux-icons` requires framework-bundle, all `^7.4\|^8.0` |
| `league/flysystem` | `tables` (`Columns\ImageColumn`) | `sylament/forms` requires `^3` |
| `psr/log` | `tables` (`LoggerInterface`, `NullLogger`) | `symfony/http-kernel`, which support's `symfony/stimulus-bundle` requires, requires `^1\|^2\|^3` |

Notes on particular rows:
- **`doctrine/dbal`.** `schemas` declares it, at `^4.6`, because its `Identity\` classes, `State\AssociationReader` and `Query\SearchConstraint` resolve a field's type through the connection's type provider, which only DBAL 4.6 has ([why](php.md#dbal-types)); `doctrine/orm`'s own `^3.8.2 || ^4` would still admit DBAL 3 and DBAL 4 before 4.6. `actions` and `tables` require `schemas`, so DBAL 4.6 or later sits beside every package that imports a DBAL name, and `ParameterType` and `ArrayParameterType` are the native enums of DBAL 4 wherever they are used. A package that imports DBAL without requiring `schemas` declares it itself.
- **`doctrine/collections`.** `actions`' `Replication\RecordReplicator` instantiates `ArrayCollection`, so an ORM-less `sylament/actions` would fatal there; `tables` only tests `instanceof`, which PHP does not autoload for. This is the omission to declare first if `doctrine/orm` ever stops being an `actions` requirement.
- **The framework-bundle trio.** `panels`' `Routing\PanelRouteLoader` implements `RouteLoaderInterface` from framework-bundle itself. That import is still guaranteed, because `support` requires `symfony/ux-icons`, which requires framework-bundle; declare framework-bundle in `panels` the day `support` stops requiring `ux-icons`.
- **`league/flysystem` in `tables`.** Only `ImageColumn`'s disk-path arm reaches the flysystem symbols, through the registry. A column whose state is already a URL works with no flysystem installed.

## Test tooling is imported, never declared

`src/Testing` imports PHPUnit (`PHPUnit\Framework\Assert`, in `actions`, `forms`, `notifications`, `schemas` and `tables`) and Symfony's test tooling: framework-bundle's `KernelBrowser` (in `actions` and `tables`), which runs on `symfony/browser-kit`, `symfony/dom-crawler` (whose `Crawler` `tables` reads a render with) and `symfony/css-selector`, and `symfony/http-foundation`'s `ResponseHeaderLocationSame` (in `actions`), a PHPUnit constraint from a package `actions` requires for its own code.
No manifest declares any of them, not even as a `suggest`, which is how Symfony's own `Test\` namespaces ship: framework-bundle's `KernelTestCase` and UX LiveComponent's `TestLiveComponent` import PHPUnit and browser-kit, and their manifests list them only as `require-dev`.
Only an application's test suite loads a tester, and that suite has installed PHPUnit and the test tooling to run at all; a `require` would push them into every production install, and a `suggest` would advertise as optional what the tester cannot run without.
It stays safe because no bundle registers a class that imports them: every bundle's component glob names component levels only, and the two test-mode services, actions' `ExceptionRecorder` and notifications' `NotificationRecorder`, import neither and exist only under `framework.test`.

## Known gaps

The rule is broken in a few places, and no decision covers these imports. Each is safe today only through a sibling, which is the hazard the rule exists for. None is recorded as a deliberate omission:
- `actions`, `forms` and `panels` call `mb_*` functions without declaring `ext-mbstring`. `schemas`, `support` and `tables` declare it.
- `forms` imports `Symfony\Component\PropertyAccess\Exception\AccessException` without declaring `symfony/property-access`.
- `panels` imports `Symfony\Contracts\Service\Attribute\Required` without declaring `symfony/service-contracts`.
- `support` guards `symfony/asset-mapper` but no manifest suggests it.
- `actions`' notifications guard degrades silently, by design (see the table).

## Third-party grants

`symfony/*` packages are pre-approved, and every other vendor needs explicit sign-off. Each grant is a row below: the package, what it is for, and the manifests that declare it. A package missing from the table has no sign-off.

| Package | For | Manifests |
| --- | --- | --- |
| `twig/twig` | The template engine every bundle renders through; declared on `support`, which every package requires (see [the transitive guarantees](#the-transitive-guarantees-behind-the-omissions)). | `support` `require` (`^3.27`) |
| `doctrine/orm` | Doctrine entities as records: the tables `query()` lane and record resolution, record mapping and relation fields in `schemas` and `forms`, the record actions, and panels' resources. | `actions`, `forms`, `panels`, `schemas`, `tables` `require` (`^3.7`); not in the root `composer.json` |
| `doctrine/dbal` | The database layer under the ORM: the DBAL types `schemas`' `Identity\` binds and converts record keys through, resolved through each connection's type provider, and the parameter types the query sites bind under. | `schemas` `require` (`^4.6`); undeclared in `actions` and `tables`, guaranteed transitively (see [the transitive guarantees](#the-transitive-guarantees-behind-the-omissions)) |
| `league/flysystem` | The storage contract (`FilesystemOperator`) file uploads write through and `FilesystemRegistry` hands out. | `forms` `require` (`^3`); `support` `suggest` |
| `doctrine/doctrine-bundle` | Configures the root suite's kernel: one connection, the entity managers over the fixture areas' entities, the `doctrine` registry Sylament's bundles autowire, and the DBAL middlewares the test database runs through; no shipped code imports it. | root `require-dev` (`^3.4`, because the rig's entity manager sets `use_schema_editor_api`, which exists from that version and needs ORM 3.8 and DBAL 4.5) |
| `doctrine/orm-plugin-second-level-cache` | The second-level cache ORM 3.8 moved out of its core, which the root suite's entity managers enable to pin the bulk actions' region evictions; no shipped code imports it, since `getCache()` stays in the ORM and answers `null` with the cache off. | root `require-dev` (`^1.0`) |
| `league/flysystem-bundle` | Turns configuration into named storages tagged `flysystem.storage`, which support's `FilesystemRegistry` collects; the consuming application's own wiring. | root `require-dev` (`^3.7`); `support` `suggest` |
| `psr/log` | The logger contract the editable-cell host writes a refused write's developer message through (`Concerns\HasColumns::setTableColumnStateLogger()`), and the one `actions` may take for the exceptions its bulk bodies swallow ([delete-action.md](../progress/actions/delete-action.md#the-swallowed-exception-has-nowhere-to-go-yet)). | undeclared in `tables`, guaranteed transitively (see [the transitive guarantees](#the-transitive-guarantees-behind-the-omissions)) |
| `league/commonmark` | Converts `TextColumn::markdown()` and the summarizers' `markdown()` cells; a guarded optional that throws by name when absent. | `tables` `suggest` |
| `filament/filament` | Filament's source under `vendor/filament/`, read before designing anything and diffed against; no shipped code imports it. | root `require-dev` (`^5.0`) |
| `nikic/php-parser` | The PHP parser Symfony's `PhpAstExtractor` reads with, for the Architecture gate that diffs each package's message keys against its catalogue; no shipped code imports it. | root `require-dev` (`^5.0`) |
| `@hotwired/stimulus` | The controller runtime every Stimulus controller extends; kept external in every build, so it resolves through the consuming application's importmap. | every `packages/*/assets/package.json` `peerDependencies` (`^3.0.0`) |
| `@symfony/ux-live-component` | The JavaScript half of `symfony/ux-live-component`, which the live-aware controllers import; Symfony's own, so pre-approved, and kept external like Stimulus. | `actions`, `forms`, `panels`, `schemas`, `tables` asset `peerDependencies` (`^3.2.0`) |
| `@floating-ui/dom` | Dropdown positioning: support's `dropdown` controller and the select engine forms' `select` controller inlines (Filament's `computePosition()` with `offset`, `shift` and `flip`, ported verbatim). Bundled into each owning dist, so no consumer importmap gains an entry. | `packages/support/assets/package.json` and `packages/forms/assets/package.json` `dependencies` (`^1.8.0`) |
| `sortablejs` | The drag engine: support's `sortable` controller (repeater items, table rows, key-value rows, tag chips) and forms' select engine (chip reorder). Bundled into each owning dist through `BUNDLED_ENTRIES`, each dist inlining its own copy as Filament's bundles do, so no consumer importmap gains an entry; a `pnpm-workspace.yaml` override keeps the two copies on one version ([sortable.md](../progress/support/sortable.md#sortablejs-is-bundled-twice-pinned-to-one-version)). | `packages/support/assets/package.json` and `packages/forms/assets/package.json` `dependencies` (`^1.15.7`; `@types/sortablejs` in each `devDependencies`) |
| `@fontsource-variable/inter` | The Inter Variable latin woff2 and its licence, copied into `dist/styles/fonts/` by `copy-fonts.mjs` for the panel font. | `packages/support/assets/package.json` `dependencies` (`^5.3.0`) |
| `filepond` | The file-upload drop zone, bundled into forms' `file-upload` controller (no consumer importmap entry) with its stylesheet regenerated scoped under `.sy-fo-file-upload`. `symfony/ux-dropzone` was the declined alternative. | `packages/forms/assets/package.json` `dependencies` |
| `filepond-plugin-file-validate-size` | Client-side size refusal in the drop zone. | `packages/forms/assets/package.json` `dependencies` |
| `filepond-plugin-file-validate-type` | Client-side type refusal in the drop zone, resolved through `mimeTypeMap()`; FilePond's mime database is not bundled. | `packages/forms/assets/package.json` `dependencies` |
| `filepond-plugin-image-preview` | Image thumbnails in the drop zone. | `packages/forms/assets/package.json` `dependencies` |
| `filepond-plugin-image-crop` | Aspect-ratio cropping of an uploaded image. | `packages/forms/assets/package.json` `dependencies` |
| `filepond-plugin-image-edit` | The hook that opens the image editor on a FilePond item. | `packages/forms/assets/package.json` `dependencies` |
| `filepond-plugin-image-exif-orientation` | `orientImagesFromExif()`. | `packages/forms/assets/package.json` `dependencies` |
| `filepond-plugin-image-resize` | The automatic resize family. | `packages/forms/assets/package.json` `dependencies` |
| `filepond-plugin-image-transform` | Applies the crop and resize to the uploaded file before it is sent. | `packages/forms/assets/package.json` `dependencies` |
| `cropperjs` | The image editor's engine (`FileUpload::imageEditor()`). Pintura and Doka were rejected as commercial; `symfony/ux-cropperjs` was declined because it is a Symfony Form type wrapping Cropper over a standalone image, not an editor over a FilePond item. | `packages/forms/assets/package.json` `dependencies` (`^1.5.13`) |

Every FilePond plugin and `cropperjs` is bundled into forms' `file-upload` controller beside FilePond, and its licence heads that bundle.

Development tooling needs no row of its own: the root `require-dev` carries Pest, PHPStan, Rector, PHP-CS-Fixer and Twig-CS-Fixer, and the asset workspaces' `devDependencies` and the root `package.json` carry the build and lint tools (esbuild, TypeScript, Tailwind CSS and its CLI, PostCSS, concurrently, Biome). None of them ships.

Beyond these grants, the project builds on what the Symfony team maintains, the stack it sits on (Twig, Doctrine) and established tooling, and writes its own code rather than adopt a package from outside that circle. `dama/doctrine-test-bundle` was declined on that ground for the root suite's per-test database, and why its rollback would not keep the database contract either is in [test-infrastructure.md](test-infrastructure.md#why-not-roll-each-test-back-instead).
