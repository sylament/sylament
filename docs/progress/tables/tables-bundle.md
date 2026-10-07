---
description: 'The tables bundle class, its renderTable() Twig extension, the TablesIconAlias slots, the package manifest and the asset registration.'
ports:
  - vendor/filament/tables/src/TablesServiceProvider.php
  - vendor/filament/tables/src/View/TablesIconAlias.php
  - vendor/filament/tables/resources/js/index.js
owns:
  - packages/tables/src/SylamentTablesBundle.php
---
# SylamentTablesBundle (tables)

[`Sylament\Tables\SylamentTablesBundle`](../../../packages/tables/src/SylamentTablesBundle.php) ports Filament's [`TablesServiceProvider`](../../../vendor/filament/tables/src/TablesServiceProvider.php): Spatie's `PackageServiceProvider` lifecycle becomes a Symfony `AbstractBundle` through [`SylamentBundle`](../../../packages/support/src/SylamentBundle.php).
This file also documents the two Twig-side pieces beside it, [`TableExtension`](../../../packages/tables/src/Twig/Extension/TableExtension.php) (the `renderTable()` template helper) and [`TablesIconAlias`](../../../packages/tables/src/Twig/TablesIconAlias.php) (the overridable icon slots, under the [`View\` → `Twig\`](../translations.md#namespace-segments) segment), and the package manifest that declares what `src/` needs.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Columns\` (with `Columns\Summarizers\`), `Filters\`, `Grouping\` and `Colors\Components\` globs + the `Table` service](../../../packages/tables/src/SylamentTablesBundle.php) | — (Sylament-only; Filament's provider binds no services) | done | — | [diverges](#the-bundle-registers-container-services-which-filaments-provider-has-no-counterpart-for) |
| [`TableExtension` → `twig.extension`](../../../packages/tables/src/SylamentTablesBundle.php) (`renderTable()`) | — (Sylament-only; mirrors schemas' [`renderSchema()`](../../../packages/schemas/src/Twig/Extension/SchemaExtension.php)) | done | — | [diverges](#the-bundle-registers-a-rendertable-twig-extension-which-filament-has-no-counterpart-for) |
| [`TablesIconAlias`](../../../packages/tables/src/Twig/TablesIconAlias.php) — every alias whose icon the port renders | [`TablesIconAlias`](../../../vendor/filament/tables/src/View/TablesIconAlias.php) | done | — | [prefix](../renames.md#icon-alias-values-drop-filaments-package-prefix) · [semantics](#an-alias-resolves-where-its-icon-is-chosen) |
| `TablesIconAlias::FILTERS_QUERY_BUILDER_CONSTRAINTS_*` | [`TablesIconAlias`](../../../vendor/filament/tables/src/View/TablesIconAlias.php) | blocked | the query-builder filter | [detail](#an-alias-lands-with-the-component-that-reads-it) |
| `TablesIconAlias::COLUMNS_COLLAPSE_BUTTON` | [`TablesIconAlias`](../../../vendor/filament/tables/src/View/TablesIconAlias.php) | blocked | the layout columns (`Columns\Layout\`) | [detail](#an-alias-lands-with-the-component-that-reads-it) |
| `TablesIconAlias::ACTIONS_OPEN_BULK_ACTIONS` | [`TablesIconAlias`](../../../vendor/filament/tables/src/View/TablesIconAlias.php) | skipped | — | [action-group.md](../actions/action-group.md#bulkactiongroup-sets-its-icon-explicitly-and-keeps-the-label-a-literal) |
| [`ext-mbstring` on the package manifest](../../../packages/tables/composer.json) | — (Sylament-only; Filament's manifest declares no extensions) | in progress | — | [detail](#the-ext-mbstring-sylamentforms-and-symfonyvalidator-requires-are-pinned-by-no-test) · [diverges](#the-manifest-declares-ext-mbstring) |
| [`sylament/forms` on the package manifest](../../../packages/tables/composer.json) | [`filament/forms` on Filament's](../../../vendor/filament/tables/composer.json) | in progress | — | [detail](#the-ext-mbstring-sylamentforms-and-symfonyvalidator-requires-are-pinned-by-no-test) · [diverges](#the-manifest-declares-sylamentforms) |
| [`ext-intl`, `symfony/html-sanitizer` and `symfony/validator` on the package manifest](../../../packages/tables/composer.json) | [`ext-intl` on `filament/support`'s](../../../vendor/filament/support/composer.json); the two Symfony packages have no Filament counterpart | in progress | — | [detail](#the-ext-mbstring-sylamentforms-and-symfonyvalidator-requires-are-pinned-by-no-test) · [diverges](#the-manifest-requires-ext-intl-and-two-symfony-packages-where-the-call-is-and-only-suggests-leaguecommonmark) |
| [`league/commonmark` as a `suggest`](../../../packages/tables/composer.json) | [`Str::markdown()`, which Laravel requires outright](../../../vendor/filament/tables/src/Columns/Concerns/CanFormatState.php) | done | — | [diverges](#the-manifest-requires-ext-intl-and-two-symfony-packages-where-the-call-is-and-only-suggests-leaguecommonmark) · [text-column.md](text-column.md#markdown-throws-for-the-package-it-suggests-rather-than-falling-through) |
| [`@SylamentTables` view namespace](../../../packages/tables/templates) | [`->hasViews()`](../../../vendor/filament/tables/src/TablesServiceProvider.php) | done | — | [diverges](../schemas/schemas-bundle.md#the-view-namespace-is-registered-by-symfony-convention-not-by-an-explicit-provider-call) |
| [`prependExtension()` — `assets/dist` in AssetMapper as `@sylament/tables`](../../../packages/tables/src/SylamentTablesBundle.php) | [`packageBooted()`](../../../vendor/filament/tables/src/TablesServiceProvider.php) (`Js::make('tables')` + the column `AlpineComponent`s), [`resources/js/index.js`](../../../vendor/filament/tables/resources/js/index.js) | done | — | [semantics](#leaving-sylamenttables-out-of-controllersjson-disables-every-controller-without-an-error) · [diverges](#the-assets-register-through-assetmapper-as-stimulus-controllers-not-as-alpine-components) |
| `Make*` commands + stub publishing | [`configurePackage()`](../../../vendor/filament/tables/src/TablesServiceProvider.php), [`packageBooted()`](../../../vendor/filament/tables/src/TablesServiceProvider.php) | todo | — | [detail](#the-make-commands-and-stub-publishing-are-not-ported) |
| Translations (`hasTranslations`) | [`configurePackage()`](../../../vendor/filament/tables/src/TablesServiceProvider.php) | todo | — | [detail](#the-translations-namespace-is-not-registered) |
| `Testable::mixin(...)` — the tables testing mixins | [`packageBooted()`](../../../vendor/filament/tables/src/TablesServiceProvider.php) | skipped | — | [diverges](#livewire-specific-provider-responsibilities-are-dropped-not-ported) |

## Details

### An alias lands with the component that reads it

An alias is overridable only once something resolves it, so each `TablesIconAlias` constant lands in the same change as the component or feature that renders its icon, the growth order [`SupportIconAlias`](../support/support-icon-alias.md#an-alias-constant-lands-with-the-template-that-reads-it) follows too.
The absent constants wait on features the package does not build: the six `FILTERS_QUERY_BUILDER_CONSTRAINTS_*` belong to the query-builder filter, and `COLUMNS_COLLAPSE_BUTTON` collapses a `Columns\Layout\Stack`, which this package does not render.
`ACTIONS_OPEN_BULK_ACTIONS` is the exception whose feature ships: Filament's actions package reads it from `BulkActionGroup::setUp()`, and the port keeps actions from reaching into tables for its icon ([action-group.md](../actions/action-group.md#bulkactiongroup-sets-its-icon-explicitly-and-keeps-the-label-a-literal)), so nothing would read the constant.
Each new alias needs its default glyph in [the fixture icon set](../../../packages/tables/tests/Fixtures/assets/icons/heroicons), because ux-icons resolves a missing local icon by throwing rather than by rendering nothing.

### The `ext-mbstring`, `sylament/forms` and `symfony/validator` requires are pinned by no test

[`SylamentTablesBundleTest`](../../../packages/tables/tests/Integration/SylamentTablesBundleTest.php) reads `packages/tables/composer.json` and asserts the `league/commonmark` suggest and the `symfony/html-sanitizer` and `ext-intl` requires, and nothing else in the manifest.
The monorepo resolves every package out of one root `vendor/`, so dropping `ext-mbstring`, `sylament/forms` or `symfony/validator` fails nothing in the tables suite and breaks only the split repository.
The kernel's `SylamentFormsBundle` registration is asserted (the `SylamentForms` Twig namespace and a `Checkbox` rendering inside a `Schema`), but that is the test kernel's half, not the manifest line.
What is missing is the same manifest test asserting the three requires, the shape actions' [`ManifestTest`](../../../packages/actions/tests/Architecture/ManifestTest.php) has.

### The `Make*` commands and stub publishing are not ported

`->hasCommands([MakeColumnCommand, MakeLivewireTableCommand, MakeTableCommand])` generates a user column class, a Livewire table component and a table class from Filament's [`stubs/`](../../../vendor/filament/tables/stubs) (`ColumnView.stub`, `LivewireTableView.stub`), which `packageBooted()` also publishes into the app's `stubs/filament/`.
No `Commands/` subtree exists in the port, in any package; Filament's `Commands/` is an [Unported row](README.md#unported).
A Symfony console command and a Twig-based generator have nothing package-specific about them, so the commands port with the toolkit-wide `make:` story rather than per package.

### The translations namespace is not registered

`->hasTranslations()` registers Filament's [`resources/lang/`](../../../vendor/filament/tables/resources/lang) — the column labels, the empty-state heading and description, the filter and bulk-action strings, the pagination summary — and the bundle registers no translation domain.
The visible consequence is in [`has-empty-state.md`](has-empty-state.md#the-copy-is-english-and-hardcoded-because-no-i18n-layer-ships): `getEmptyStateHeading()` interpolates the plural record label into an English `"No …"` in PHP, because there is no catalogue to look the phrase up in.
It ports with the toolkit-wide [i18n pass](../../internals/i18n.md).

## Semantics

### An alias resolves where its icon is chosen

A template reads an alias where the markup renders the icon: the table partial for the sort chevrons, the group header's collapse button, the reorder handle, the empty state and the collapsible above-content filters' funnel, the [`SearchField`](../../../packages/tables/templates/components/SearchField/index.html.twig) component for `SEARCH_FIELD`, and the column manager's item list for its own reorder handle.
PHP reads the rest, because the icon is chosen before any template runs: the filter, remove-all-filters, column-manager, grouping and reorder triggers are `Action` objects whose icon is set where the action is built ([`HasFilters`](../../../packages/tables/src/Table/Concerns/HasFilters.php), [`HasColumnManager`](column-manager.md), [`CanGroupRecords`](grouping.md), [`CanReorderRecords`](../../../packages/tables/src/Table/Concerns/CanReorderRecords.php)), and [`IconColumn`](icon-column.md)'s `getTrueIcon()` / `getFalseIcon()` resolve the boolean pair through the icon manager because the fallback has to yield to a `trueIcon()` the caller set.
Either way every shipped alias is overridable end to end.

### Leaving `@sylament/tables` out of `controllers.json` disables every controller without an error

A consuming app that leaves `@sylament/tables` out of its `assets/controllers.json` gets markup whose controllers never connect, and nothing reports it.
Filament needs Alpine the same way, but the cost here is graded, and some of it is worse than inert:

- **Selection** renders its checkboxes and does nothing, and a `limitList()` cell renders its `and N more` toggles inert ([text-column.md](text-column.md#the-expand-toggles-render-only-where-there-is-something-to-expand)).
- **A `deferLoading()` table** renders its loading indicator and nothing else, forever: the second request that fetches the records is the table controller's `loadTable` call on `connect()`.
- **An editable cell** lets the user change its control and writes nothing, with no request and no error, because the commit is the [editable-cell controller](has-columns.md#one-editable-cell-controller-stands-in-for-filaments-four-column-components)'s `change` action.
- **A grouping selector** never fires, and on a table with `collapsedGroupsByDefault()` the rows the server painted collapsed can never expand ([grouping.md](grouping.md#the-server-paints-the-collapsed-state-where-vendors-alpine-flashes-it-open)).

No test in this repo can enforce the app's `controllers.json`.

## Divergences

### The bundle registers container services, which Filament's provider has no counterpart for

`TablesServiceProvider` binds nothing into the Laravel container: a Filament `Table` or `Column` is constructed with `new` inside its own `make()`, and Laravel's `app()->make()` fills any constructor dependency by reflection without a registration.
Symfony's container cannot do that, so [`loadExtension()`](../../../packages/tables/src/SylamentTablesBundle.php) registers the tier explicitly, all `autowire()` + `autoconfigure()`: a `Columns/{*,Summarizers/*}.php` glob (the brace form reaches the summarizers one directory down and keeps every `Concerns/` out without an exclude list), a `Filters/*.php` glob, a `Grouping/*.php` glob and `->set(Table::class)`.

Two more registrations ride along on the same mechanism for a different reason: the `Colors/Components/` globs — `*/*.php` and `*/*/*.php`, one per directory level the policies nest to, since [`TextColumnComponent\ItemComponent\IconComponent`](../translations.md#namespace-segments) sits a level below its item — are `autowire()` + **`public()`**, not `autoconfigure()`.
A colour policy is not a component and wants no `sylament.component` tag; what it needs is to survive `Container::get()`, because [`ColorManager`](../support/color-manager.md) resolves a policy from the container by the class-string a template names, and a private service is not reachable that way.
Same rule and same comment as schemas' policy glob.

That is what makes three things work, and each of them is silently wrong without it:

- `autoconfigure()` applies support's `sylament.component` tag, so [`ComponentResolverPass`](../support/component.md#newinstance-routes-every-make-through-a-locator) marks the definitions non-shared and collects them into the class-keyed `ServiceLocator` that `Component::newInstance()` resolves through.
  `make()` therefore returns a container-built, autowired, **non-shared** instance.
- `#[Required]` setters fire on that instance: [`CanAccessProperties::setPropertyAccessor()`](../../../packages/schemas/src/Components/Concerns/CanAccessProperties.php) receives the real `PropertyAccessor` service instead of the `new PropertyAccessor(…)` fallback the trait builds when the setter never ran, and `EvaluatesClosures::setInvoker()` receives the container's `Invoker` instead of one wrapping an empty container.
- Because of the second point, a `records()` closure can type-hint a service — the arm [`has-table.md`](has-table.md#a-records-closure-resolves-public-container-services-by-type) records, together with its one boundary (the `Invoker` holds `service_container`, so only **public** ids resolve).

The `Filters/*.php` glob carries the only `exclude()` in this bundle: [`FilterIndicator`](../../../packages/tables/src/Filters/FilterIndicator.php) is a readonly carrier whose `string` and `array` constructor arguments cannot be autowired, so leaving it in fails the container compile rather than registering something harmless.
`BaseFilter` is **concrete** and registers normally.

The registration is the Symfony-idiom replacement for Laravel's reflective container, not a temporary scaffold.
It is filed as a provider row because the provider is where the responsibility would sit if Laravel needed one, which is also why the row's Ports cell names no Filament source.

### The bundle registers a `renderTable()` Twig extension, which Filament has no counterpart for

Filament host templates render a table by handing over the object itself — `Table` extends a `ViewComponent` that implements `Htmlable` ([`ViewComponent.php`](../../../vendor/filament/support/src/Components/ViewComponent.php)), so echoing it in Blade calls `toHtml()`, and a PHP caller calls `render()` directly the way [`EmbeddedTable`](../../../vendor/filament/schemas/src/Components/EmbeddedTable.php) does (`$livewire->getTable()->render()`).
Either way the provider wires nothing.
Twig has no equivalent implicit-render path, so the bundle registers [`TableExtension`](../../../packages/tables/src/Twig/Extension/TableExtension.php) (`sylament_tables.table_extension`, tagged `twig.extension`) supplying `renderTable()` — the tables twin of schemas' [`renderSchema()`](../../../packages/schemas/src/Twig/Extension/SchemaExtension.php) and actions' [`renderAction()`](../actions/actions-bundle.md#the-bundle-registers-a-renderaction-twig-extension-which-filament-has-no-counterpart-for), with the wiring mirrored deliberately: support's `sylament_support.component_extension` is constructor-injected, and the function registers with `needs_environment` + `needs_context`.
It reads the host from the render context (`context['this']`, the UX LiveComponent that owns the table), throws a `RuntimeException` when that is missing or is not an [`InteractsWithTable`](../../../packages/tables/src/Contracts/InteractsWithTable.php), and delegates to support's `ComponentExtension::renderComponent()`.

One shape difference from its two siblings, and it is not an omission: **there is no name argument**.
`renderSchema(name = 'schema')` and `renderAction(name)` select one of many, where a host has exactly one table — [`HasTable::getTable()`](has-table.md) takes no key and caches a single instance.
Adding a parameter nothing could vary would be API noise.
It is recorded in the [translations table](../translations.md#names-with-no-filament-source) as a name with no Filament source, alongside the reason `renderSchema()`/`renderAction()` are not renames either.

### The manifest declares `ext-mbstring`

`packages/tables/composer.json` requires `ext-mbstring`, which Filament's tables manifest does not (it declares no extensions at all, inheriting whatever the Laravel app has).
`src/` calls the `mb_*` functions throughout — [`Concerns\CanSearchRecords::getTableSearch()`](../../../packages/tables/src/Concerns/CanSearchRecords.php) trims the raw search term with `mb_trim()`, and the debounce and poll-interval parsers, the summarizers and the column formatters call them too.
Support requires `ext-mbstring` as well, so the root graph always has it, but the [split-repo dependency rule](../actions/actions-bundle.md#every-package-declares-what-its-own-source-needs-transitives-notwithstanding) counts what this package's own source calls, since each package is published standalone and inheriting an extension through a sibling is exactly how a split repo breaks.
`mb_trim()` is used rather than `trim()` deliberately: it is the same [NBSP-and-em-space](../support/helpers.md) semantics `blank()` already applies to user input, so a search box containing only a non-breaking space reads as empty.

### The manifest requires `ext-intl` and two Symfony packages where the call is, and only suggests `league/commonmark`

`ext-intl` is declared on the same grounds as `ext-mbstring`: [`TextColumn`](text-column.md)'s `money()` and `numeric()` and the summarizers format through `NumberFormatter`, which is intl's, so the package that calls it is the package that declares it.
Filament declares `ext-intl` too, but on **`filament/support`** rather than on `filament/tables`, and inheriting an extension through a sibling is the shape the split-repo rule exists to stop.
The alternative considered and rejected was a guard around `NumberFormatter` with the two families throwing when intl is absent: a formatting column that throws at render time is worse than a manifest a composer install refuses.

`symfony/html-sanitizer` and `symfony/validator` are required under the [blanket `symfony/*` approval](../../../CLAUDE.md), and both are imported unconditionally by `src/` — the sanitizer by [`CanFormatState`](text-column.md#html-sanitizes-in-php-and-markdown-converts-before-it-does)'s `#[Required]` setter, the validator by [`CanBeValidated`](has-columns.md#validation-speaks-symfony-constraints-and-rules-is-not-ported)'s.
Neither is reachable through a sibling: forms requires the validator, but the split-repo rule counts what this package's own `src/` imports.

`league/commonmark` is a **suggest** rather than a require, one of the toolkit's [guarded optional integrations](../../internals/dependencies.md#the-guarded-optionals-and-their-pins).
The guard is a `class_exists()` around the one conversion call, and its failure mode is a `LogicException` naming the package rather than a silent fall-through to raw text ([text-column.md](text-column.md#markdown-throws-for-the-package-it-suggests-rather-than-falling-through)), which is what makes the softer link safe: `markdown()` is opt-in, and a column that never calls it never reaches the branch.
Upgrading to a require is one line if infolists' `TextEntry` wants markdown too.

### The manifest declares `sylament/forms`

Filament's `filament/tables` requires `filament/forms` for exactly one reason and so does ours: a filter's control is a form field.
[`Filters\Concerns\HasSchema`](../../../packages/tables/src/Filters/Concerns/HasSchema.php) types `getFormField(): ?Sylament\Forms\Components\Field` and [`Filter`](base-filter.md) builds a `Checkbox`, so the symbol is imported unconditionally by `src/` — the test the [split-repo dependency rule](../../../.claude/rules/manifests.md) sets for a `require`, not a `suggest`.
No cycle: `sylament/forms` requires `actions`, `schemas`, `support`, `doctrine/orm`, `league/flysystem` and Symfony packages, and nothing in forms references tables.

The `require` also buys a set of **CSS classes**: `TextInputColumn` and `SelectColumn` render their controls inside support's `InputWrapper` wearing forms' `sy-fo-input` and `sy-fo-select` skins, and `ToggleColumn` wears forms' `sy-fo-toggle*`, rather than restating them.
Reaching across a hard dependency for a class is not a package-boundary break — the styling contract's own test is whether the emitting package could be absent, and forms cannot be — but hoisting those skins into support under code-less names, the way `checkbox.css` already went, is the cleaner end state; it would touch forms files the tables port leaves alone ([forms/styles.md](../forms/styles.md#the-checkbox-skin-lives-in-support-because-tables-renders-it-too)).

The test kernel registers `SylamentFormsBundle` (`tests/Fixtures/Kernel.php`), without which a filter form cannot be built in the Integration suite at all.

### Livewire-specific provider responsibilities are dropped, not ported

`packageBooted()` registers six `Testable` mixins — `TestsActions`, `TestsBulkActions`, `TestsColumns`, `TestsFilters`, `TestsRecords`, `TestsSummaries` — whose every method drives a mounted Livewire component through `$this->call()` / `$this->set()`.
Sylament tests are Pest over `KernelTestCase`, not Livewire `Testable`; the Integration suite renders through `ViewComponent::render(Environment $twig)` and asserts on markup and container state instead.
The registration is a deliberate non-goal, **skipped** for the same reason actions' [`TestsActions`](../actions/actions-bundle.md#livewire-specific-provider-responsibilities-are-dropped-not-ported) and schemas' `TestsSchemas` are.
What is skipped is the **mixin registration**, not the idea of test helpers: Filament's [`src/Testing`](../../../vendor/filament/tables/src/Testing) files are rows in the package README's [Unported table](README.md#unported), because the helpers may still be worth porting in a Pest shape.

### The assets register through AssetMapper as Stimulus controllers, not as Alpine components

Filament's `packageBooted()` calls `FilamentAsset::register([...], 'filament/tables')` with `Js::make('tables', __DIR__ . '/../dist/index.js')`, whose [`index.js`](../../../vendor/filament/tables/resources/js/index.js) registers the `filamentTable` and `filamentTableColumnManager` Alpine data, plus four `AlpineComponent`s for the editable columns.
Sylament's translation is a [`prependExtension()`](../../../packages/tables/src/SylamentTablesBundle.php) mapping `packages/tables/assets/dist` to `@sylament/tables` behind `isAssetMapperAvailable()`, the shape [actions' prepend](../actions/actions-bundle.md#asset-registration-is-an-assetmapper-prepend) and [`SylamentSchemasBundle`](../../../packages/schemas/src/SylamentSchemasBundle.php)'s carry.
`packages/tables/assets` is a JS-only pnpm workspace: `dist/` holds the controllers its `package.json` declares, all `fetch: eager`, while `src/styles/` reaches the browser through support's single stylesheet compile.

Each Alpine registration becomes a Stimulus controller: [`table`](table.md) for `filamentTable`, [`column-manager`](column-manager.md) for `filamentTableColumnManager`, [`editable-cell`](has-columns.md#one-editable-cell-controller-stands-in-for-filaments-four-column-components) for the four column components, and [`limited-list`](text-column.md#the-expand-toggles-render-only-where-there-is-something-to-expand) for the inline `x-data` a `TextColumn` list carries, whose state has to survive a morph an Alpine scope minted per render does not.

`BUNDLED_ENTRIES` in [`build-js.mjs`](../../../packages/tables/assets/scripts/build-js.mjs) is empty: every file builds per-file and unbundled, and the only third-party import in the tree — `@symfony/ux-live-component`, reached by [`utils/live_host.ts`](../../../packages/tables/assets/src/utils/live_host.ts), which `table`, `editable-cell` and `column-manager` import — stays a bare specifier the consuming app's importmap resolves, exactly as schemas' does.
That helper is a deliberate verbatim copy of [schemas'](../../../packages/schemas/assets/src/utils/live_host.ts), the same trade `cloak.ts` carries ([schemas-bundle.md](../schemas/schemas-bundle.md#the-schemas-package-has-a-js-workspace-of-its-own)): a per-file build ships its specifiers verbatim, so importing across workspaces would mean a bare specifier every consumer's importmap has to know.
