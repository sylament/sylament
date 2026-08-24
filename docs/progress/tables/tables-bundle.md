# SylamentTablesBundle (tables)

[`Sylament\Tables\SylamentTablesBundle`](../../../packages/tables/src/SylamentTablesBundle.php)
— the tables bundle class, plus the two Twig-side pieces it owns:
[`TableExtension`](../../../packages/tables/src/Twig/Extension/TableExtension.php)
(the `renderTable()` template helper) and
[`TablesIconAlias`](../../../packages/tables/src/Twig/TablesIconAlias.php) (the
overridable icon slots). It ports Filament's
[`TablesServiceProvider`](../../../vendor/filament/tables/src/TablesServiceProvider.php)
(Spatie's `PackageServiceProvider` lifecycle becomes a Symfony `AbstractBundle`
via [`SylamentBundle`](../../../packages/support/src/SylamentBundle.php)) and
Filament's [`View/TablesIconAlias`](../../../vendor/filament/tables/src/View/TablesIconAlias.php).

Today `loadExtension()` wires exactly what the minimal tables vertical ships: the
`Columns\` component tier, the [`Table`](table.md) root object, the two
[`TextColumn`](text-column.md) colour policies under `Colors\Components\`, and
the `twig.extension`-tagged `TableExtension`. The first three have **no** counterpart in
Filament's provider — it binds no container services at all — and they are what
makes `make()` return an autowired, non-shared instance, which is in turn what
lets [`CanAccessProperties`](../../../packages/schemas/src/Components/Concerns/CanAccessProperties.php)'
`#[Required]` setter reach the real
[`PropertyAccessor`](../schemas/property-accessor.md) and a `records()` closure
reach the container. The `@SylamentTables` template namespace is live too, as of
the [`Table`](table.md) and [`TextColumn`](text-column.md) partials — though it
costs the bundle no code, since TwigBundle maps `packages/tables/templates` by
convention the moment the directory exists. Asset registration is live too, as
of the selection controller: `prependExtension()` maps `assets/dist` into
AssetMapper under `@sylament/tables`, and the package joined the pnpm workspace
to build what goes there — [below](#asset-registration). The four
interactive-column Alpine components that registration also carries in Filament,
the three `make:` commands and their stubs, translations, and the six Livewire
testing mixins are unported.
The package manifest gained `sylament/forms` with the filter layer: a filter
builds its control out of real form fields (`Checkbox`, `Select`), so `src/`
imports `Sylament\Forms\Components\Field` directly and the split-repo dependency
rule makes that a `require` rather than something inherited through a sibling —
[below](#the-manifest-declares-sylamentforms).

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`tables/src/TablesServiceProvider.php`](../../../vendor/filament/tables/src/TablesServiceProvider.php)
  — the provider whose `configurePackage()` / `packageBooted()` this bundle ports
- [`tables/src/View/TablesIconAlias.php`](../../../vendor/filament/tables/src/View/TablesIconAlias.php)
  — the 23 alias constants, read to judge what the alias row is missing

## Surface

Every provider responsibility, plus the two Twig-side classes the bundle owns
and the package manifest that declares what its `src/` needs.
_Blocked by_ names what has to land first. _Detail_ carries a **detail** link to
a [Details](#details) write-up, a **diverges** link to
[Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Columns\`, `Filters\` and `Colors\Components\` globs + `Table` component services](../../../packages/tables/src/SylamentTablesBundle.php) | — (Sylament-only; Filament's provider binds no services) | done | — | [diverges](#the-bundle-registers-container-services-which-filaments-provider-has-no-counterpart-for) |
| [TableExtension → `twig.extension`](../../../packages/tables/src/SylamentTablesBundle.php) (`renderTable()`) | — (Sylament-only; mirrors schemas' [`renderSchema()`](../../../packages/schemas/src/Twig/Extension/SchemaExtension.php)) | done | — | [diverges](#the-bundle-registers-a-rendertable-twig-extension-which-filament-has-no-counterpart-for) |
| [`TablesIconAlias` — `ACTIONS_FILTER`, `EMPTY_STATE`, `FILTERS_REMOVE_ALL_BUTTON`, the three `HEADER_CELL_SORT_*`, `SEARCH_FIELD`](../../../packages/tables/src/Twig/TablesIconAlias.php) | [`TablesIconAlias`](../../../vendor/filament/tables/src/View/TablesIconAlias.php) | in progress | — | [detail](#the-alias-set-is-seven-constants-of-filaments-23) · [diverges](#the-empty-state-alias-value-drops-filaments-package-prefix) |
| [`ext-mbstring` on the package manifest](../../../packages/tables/composer.json) | — (Sylament-only; Filament's manifest declares no extensions) | done | — | [diverges](#the-manifest-declares-ext-mbstring) |
| [`sylament/forms` on the package manifest](../../../packages/tables/composer.json) | [`filament/forms` on Filament's](../../../vendor/filament/tables/composer.json) | done | — | [diverges](#the-manifest-declares-sylamentforms) |
| [`@SylamentTables` view namespace](../../../packages/tables/templates) | [`->hasViews()`](../../../vendor/filament/tables/src/TablesServiceProvider.php) | done | — | [diverges](../schemas/schemas-bundle.md#the-view-namespace-is-registered-by-symfony-convention-not-by-an-explicit-provider-call) |
| [Asset registration](../../../packages/tables/src/SylamentTablesBundle.php) (`Js::make('tables')` + four column `AlpineComponent`s) | [`packageBooted()`](../../../vendor/filament/tables/src/TablesServiceProvider.php) | in progress | — | [detail](#asset-registration) · [diverges](#the-selection-controller-diverges-from-tablejs-in-four-places) |
| `Make*` commands + stub publishing | [`configurePackage()`](../../../vendor/filament/tables/src/TablesServiceProvider.php), [`packageBooted()`](../../../vendor/filament/tables/src/TablesServiceProvider.php) | todo | — | [detail](#make-commands--stub-publishing) |
| Translations (`hasTranslations`) | [`configurePackage()`](../../../vendor/filament/tables/src/TablesServiceProvider.php) | todo | — | [detail](#translations) |
| `Testable::mixin(...)` — the six tables mixins | [`packageBooted()`](../../../vendor/filament/tables/src/TablesServiceProvider.php) | skipped | — | [diverges](#livewire-specific-provider-responsibilities-are-dropped-not-ported) |

Four of the five **done** rows are covered by
[`SylamentTablesBundleTest`](../../../packages/tables/tests/Integration/SylamentTablesBundleTest.php),
which asserts each in the shape that fails without the registration rather
than the shape that passes either way: the container's twig service exposes
`TableExtension` (extension registration + tag); a `TextColumn` built through
`make()` returns the **container's** `PropertyAccessor` service, not the
`new`-built fallback the trait would otherwise construct, and a table built
through `Table::make()` carries the container's `Invoker` and hands a
`records()` closure the container's shared fixture repository, and a
`BaseFilter` and an `Indicator` built the same way carry that same `Invoker`
while `FilterIndicator` is absent from the container (four assertions for one
row — the `Columns\` glob, `->set(Table::class)`, the `Filters\` glob and its
one `exclude()`); and the booted
kernel's Twig loader lists a `SylamentTables` namespace under which
`@SylamentTables/partials/Table/index.html.twig` resolves
(the view namespace — the assertion that failed with
_"There are no registered paths for namespace `SylamentTables`"_ until
`packages/tables/templates/` existed); and the same loader lists a
`SylamentForms` namespace under which a real `Checkbox` renders inside a `Schema`
(the [`sylament/forms` declaration](#the-manifest-declares-sylamentforms), whose
test-kernel half is what those two cases can actually assert).
The **in progress** asset row is covered there too, by the same standard: the
booted `AssetMapperInterface` resolves both built files —
`assets/dist/controllers/table_controller.js` and the `assets/dist/utils/live_host.js`
it imports — to their `@sylament/tables/...` logical paths, which is exactly the
lookup StimulusBundle's `ControllersMapGenerator` performs and exactly what
returns null without the `prependExtension()` hook.
`renderTable()` itself is covered twice: by the Unit
[`TableExtensionTest`](../../../packages/tables/tests/Unit/Twig/Extension/TableExtensionTest.php)
against an `ArrayLoader` stub — the function name, the render, and both
`RuntimeException` guards — and by its Integration
[twin](../../../packages/tables/tests/Integration/Twig/Extension/TableExtensionTest.php),
which renders a `<twig:SongsTable />` host through the real Twig so the shipped
partial is in the loop. The fifth — the `ext-mbstring` declaration — is a
manifest fact with no runtime to assert; what would catch its absence is CI
installing the split package on a build without the extension. The unported
responsibilities have no coverage because they have no code.

## Details

Specifics for rows that are not finished — what is already in, what is left, and
what the next step is. Delete a subsection when its row flips to **done**.

### The alias set is seven constants of Filament's 23

Filament's `TablesIconAlias` declares 23 alias constants up front — every
overridable icon slot in the whole table builder. Sylament ships **seven** —
`ACTIONS_FILTER` (the toolbar funnel), `EMPTY_STATE`,
`FILTERS_REMOVE_ALL_BUTTON` (the indicators row's clear-all button), the three
`HEADER_CELL_SORT_*` (unsorted, ascending, descending — the sortable header's
chevron picks one by state) and `SEARCH_FIELD`. All seven have their consuming
surface ported. The remaining 16 all belong to features that are not built yet:
reordering (`ACTIONS_ENABLE_REORDERING`, `ACTIONS_DISABLE_REORDERING`,
`REORDER_HANDLE`), the filters query builder (the six
`FILTERS_QUERY_BUILDER_CONSTRAINTS_*`), grouping (`ACTIONS_GROUP`,
`GROUPING_COLLAPSE_BUTTON`), bulk actions (`ACTIONS_OPEN_BULK_ACTIONS`), the
column manager (`ACTIONS_COLUMN_MANAGER`) and the column catalog
(`COLUMNS_COLLAPSE_BUTTON`, `COLUMNS_ICON_COLUMN_TRUE`, `COLUMNS_ICON_COLUMN_FALSE`).

**Next step:** none on its own. An alias is only overridable once something reads
it, so each constant lands in the same change as the component or feature that
renders its icon — the same growth order
[`SupportIconAlias`](../support/support-icon-alias.md#pagination-rtl-aliases)
follows, and the four that arrived with the sort headers and the search toolbar
are that order in action. All seven are read by
[the table partial](../../../packages/tables/templates/partials/Table/index.html.twig)
— `ACTIONS_FILTER` and `FILTERS_REMOVE_ALL_BUTTON` by the filters chrome, in its
toolbar and its indicators row, rather than by the table's own rows — so every
one of them is overridable end to end.

### Asset registration

Filament's `packageBooted()` calls `FilamentAsset::register([...],
'filament/tables')` with `Js::make('tables', __DIR__ . '/../dist/index.js')` plus
four `AlpineComponent`s for the interactive columns (`columns/checkbox`,
`columns/select`, `columns/text-input`, `columns/toggle`). Sylament's translation
is a [`prependExtension()`](../../../packages/tables/src/SylamentTablesBundle.php)
registering `packages/tables/assets/dist` as `@sylament/tables` behind
`isAssetMapperAvailable()` (mirroring
[`SylamentSchemasBundle`](../../../packages/schemas/src/SylamentSchemasBundle.php)'s
line for line), and it ships: `packages/tables/assets` is the **seventh** pnpm
workspace, JS-only in the shape notifications, panels and schemas already have —
`dist/` holds controllers, while `src/styles/` keeps reaching the browser through
support's single stylesheet compile, unchanged.

This section previously predicted that the registration would arrive with the
interactive columns, "which is when the four `AlpineComponent`s are ported."
**Selection got there first.** What the workspace ships today is one controller,
[`table_controller.ts`](../../../packages/tables/assets/src/controllers/table_controller.ts)
(`sylament--tables--table`, `fetch: eager`), the client half of record selection
— the port of Filament's `filamentTable` Alpine data
([`resources/js/components/table.js`](../../../vendor/filament/tables/resources/js/components/table.js)),
minus everything that is not selection: the filters-dropdown positioning (support's
`Dropdown` owns that here), the group-collapse arm (M4 · 6), the two props no
caller sets (`maxSelectableRecords` and the entangled
`currentSelectionLivewireProperty`), the `mountAction` wrapper that pushed the
three models just before mounting — every tick already pushes them, so there is
nothing left to flush — the `scrollToTopOfTable` listener, and the
`deselectAllTableRecords` browser event the host broadcasts to clear a selection,
which lands as a re-render the repaint reads instead. What is ported behaves as
Filament's does in all but [four places](#the-selection-controller-diverges-from-tablejs-in-four-places). `BUNDLED_ENTRIES` in the copied
[`build-js.mjs`](../../../packages/tables/assets/scripts/build-js.mjs) is empty:
every file builds per-file and unbundled, and the only third-party import in the
tree — `@symfony/ux-live-component`, reached by
[`utils/live_host.ts`](../../../packages/tables/assets/src/utils/live_host.ts) —
stays a bare specifier the consuming app's importmap resolves, exactly as schemas'
does. That helper is a **deliberate ten-line copy** of
[schemas'](../../../packages/schemas/assets/src/utils/live_host.ts), the same
trade `cloak.ts` already carries
([schemas-bundle.md](../schemas/schemas-bundle.md#the-schemas-package-grew-a-js-workspace-of-its-own)):
a per-file build ships its specifiers verbatim, so importing across workspaces
would mean a bare specifier every consumer's importmap has to know.

Three consequences worth stating plainly. The controller writes selection through
`component.set(model, value, false)` and reads it back through
`component.getData(model)`, so N checkbox ticks cost **zero** requests and the
next action carries the whole selection — the reason a JS half exists at all,
alongside the tri-state header checkbox, whose `indeterminate` is an IDL-only
property no markup can carry. It re-asserts that state onto the DOM on `connect()`
and on the host's `render:finished` hook, because the store — not the
server-rendered `checked` attribute — is what wins after the first morph. And,
like [actions' page-wide `dispatch` controller](../actions/actions-bundle.md#the-dispatch-controller-is-page-wide-and-filament-has-no-counterpart-for-it),
a consuming app that leaves `@sylament/tables` out of its `assets/controllers.json`
loses every one of those behaviours with no error anywhere: the checkboxes render
and do nothing. Filament needs Alpine the same way.

**Next step:** the four interactive columns (checkbox, select, text input, toggle)
land as controllers in this same workspace — the `AlpineComponent`s exist for
exactly those — and the column manager's own component follows with M4 · 5.

### `Make*` commands + stub publishing

`->hasCommands([MakeColumnCommand, MakeLivewireTableCommand, MakeTableCommand])`
generates a user column class, a Livewire table component, and a table class from
[`stubs/`](../../../vendor/filament/tables/stubs) (`ColumnView.stub`,
`LivewireTableView.stub`), which `packageBooted()` also publishes into the app's
`stubs/filament/`. No `Commands/` subtree exists in the port, in any package.
**Next step:** port with the toolkit-wide `make:` story rather than per package —
a Symfony console command and a Twig-based generator have nothing package-specific
about them, and doing tables first would fix a shape the other eight then inherit.

### Translations

`->hasTranslations()` registers
[`resources/lang/`](../../../vendor/filament/tables/resources/lang) — the column
labels, the empty-state heading and description, the filter and bulk-action
strings, the pagination summary. Unported, and the immediate consequence is
visible in [`table.md`](table.md#the-empty-state-heading-hardcodes-no-records):
`getEmptyStateHeading()` returns the English literal `'No records'` because there
is no catalogue to look it up in. **Next step:** port with the toolkit-wide i18n
pass, which the empty-state heading divergence already names as its second
reversal step.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold. Say
what would reverse the decision when it is provisional.

### The bundle registers container services, which Filament's provider has no counterpart for

`TablesServiceProvider` binds nothing into the Laravel container: a Filament
`Table` or `Column` is constructed with `new` inside its own `make()`, and
Laravel's `app()->make()` fills any constructor dependency by reflection without
a registration. Symfony's container cannot do that, so
[`loadExtension()`](../../../packages/tables/src/SylamentTablesBundle.php)
registers the tier explicitly — a top-level `Columns\*.php` glob (which keeps
`Concerns/` out without an exclude list) and `->set(Table::class)`, both
`autowire()` + `autoconfigure()`.

Two more registrations ride along on the same mechanism for a different reason:
the `Colors/Components/` globs — `*/*.php` and `*/*/*.php`, one per directory
level the policies nest to, since
[`TextColumnComponent\ItemComponent\IconComponent`](../renames.md#namespace-segments)
sits a level below its item — are `autowire()` + **`public()`**, not
`autoconfigure()`. A colour policy is not a component and wants no
`sylament.component` tag; what it needs is to survive
`Container::get()`, because
[`ColorManager`](../support/color-manager.md) resolves a policy from the
container by the class-string a template names, and a private service is not
reachable that way. Same rule and same comment as schemas' policy glob.

That is not bookkeeping; it is what makes three things work, and each of them is
silently wrong without it:

- `autoconfigure()` applies support's `sylament.component` tag, so
  [`ComponentResolverPass`](../support/component.md#newinstance-routes-every-make-through-a-locator) marks
  the definitions non-shared and collects them into the class-keyed
  `ServiceLocator` that `Component::newInstance()` resolves through. `make()`
  therefore returns a container-built, autowired, **non-shared** instance.
- `#[Required]` setters fire on that instance:
  [`CanAccessProperties::setPropertyAccessor()`](../../../packages/schemas/src/Components/Concerns/CanAccessProperties.php)
  receives the real `PropertyAccessor` service instead of the `new PropertyAccessor(…)`
  fallback the trait builds when the setter never ran, and
  `EvaluatesClosures::setInvoker()` receives the container's `Invoker` instead of
  one wrapping an empty container.
- Because of the second point, a `records()` closure can type-hint a service —
  the arm [`has-table.md`](has-table.md#a-records-closure-resolves-public-container-services-by-type)
  records, together with its one boundary (the `Invoker` holds
  `service_container`, so only **public** ids resolve).

The `Filters\*.php` glob is the third, and it carries the only `exclude()` in
this bundle:
[`FilterIndicator`](../../../packages/tables/src/Filters/FilterIndicator.php) is
a readonly carrier whose `string` and `array` constructor arguments cannot be
autowired, so leaving it in fails the container compile rather than registering
something harmless — the same correction support's `Components/*.php` glob makes
for `ComponentManager`. `BaseFilter` is **concrete** and registers normally.

Permanent: the registration is the Symfony-idiom replacement for Laravel's
reflective container, not a temporary scaffold. It is filed as a provider row
because the provider is where the responsibility would sit if Laravel needed one
— which is also why the row's `Ports` cell names no Filament source.

### The bundle registers a `renderTable()` Twig extension, which Filament has no counterpart for

Filament host templates render a table by handing over the object itself —
`Table` extends a `ViewComponent` that implements `Htmlable`
([`ViewComponent.php`](../../../vendor/filament/support/src/Components/ViewComponent.php)),
so echoing it in Blade calls `toHtml()`, and a PHP caller calls `render()`
directly the way
[`EmbeddedTable`](../../../vendor/filament/schemas/src/Components/EmbeddedTable.php)
does (`$livewire->getTable()->render()`). Either way the provider wires nothing.
Twig has no equivalent implicit-render path, so the bundle registers
[`TableExtension`](../../../packages/tables/src/Twig/Extension/TableExtension.php)
(`sylament_tables.table_extension`, tagged `twig.extension`) supplying
`renderTable()` — the tables twin of schemas'
[`renderSchema()`](../../../packages/schemas/src/Twig/Extension/SchemaExtension.php)
and actions'
[`renderAction()`](../actions/actions-bundle.md#the-bundle-registers-a-renderaction-twig-extension-which-filament-has-no-counterpart-for),
with the wiring mirrored deliberately: support's
`sylament_support.component_extension` is constructor-injected, and the function
registers with `needs_environment` + `needs_context`. It reads the host from the
render context (`context['this']`, the UX LiveComponent that owns the table),
throws a `RuntimeException` when that is missing or is not an
[`InteractsWithTable`](../../../packages/tables/src/Contracts/InteractsWithTable.php),
and delegates to support's `ComponentExtension::renderComponent()`.

One shape difference from its two siblings, and it is not an omission: **there is
no name argument**. `renderSchema(name = 'schema')` and `renderAction(name)`
select one of many, where a host has exactly one table —
[`HasTable::getTable()`](has-table.md) takes no key and caches a single instance.
Adding a parameter nothing could vary would be API noise. Recorded in the
[rename table](../renames.md#names-with-no-filament-source) as a name with no
Filament source, alongside the reason `renderSchema()`/`renderAction()` are not
renames either.

### The manifest declares `ext-mbstring`

`packages/tables/composer.json` requires `ext-mbstring`, which Filament's tables
manifest does not (it declares no extensions at all, inheriting whatever the
Laravel app has). The trigger is one call:
[`CanSearchRecords::getTableSearch()`](../../../packages/tables/src/Concerns/CanSearchRecords.php)
trims the raw search term with `mb_trim()`, and
[`Table\Concerns\CanSearchRecords`](../../../packages/tables/src/Table/Concerns/CanSearchRecords.php)
uses it again before parsing the debounce duration.

This is the same **manifest correction** the minimal tables vertical made when it
declared `symfony/property-access` on this package: not a new dependency — support
has required `ext-mbstring` since it shipped `blank()`, and the root graph has
therefore always had it — but the declaration the
[split-repo dependency rule](../../../CLAUDE.md) requires, since each package is
published standalone and inheriting an extension through a sibling is exactly how
a split repo breaks. `mb_trim()` is used rather than `trim()` deliberately: it is
the same
[NBSP-and-em-space](../support/helpers.md) semantics `blank()` already applies to
user input, so a search box containing only a non-breaking space reads as empty.

### The empty-state alias value drops Filament's package prefix

Filament's constant is `EMPTY_STATE = 'tables::empty-state'`; ours is
`'table.empty-state'`. Filament is inconsistent here — `SupportIconAlias`'s values
are bare (`modal.close-button`, `section.collapse-button`) while every other
package namespaces its own (`tables::`, `actions::`, `schema::`) — and Sylament
follows the bare `<component>.<part>` form throughout, so an app registering
overrides under [`sylament.icons`](../support/icon-manager.md) reads one scheme
rather than two. The **constant** name is untouched, which is how both codebases
write it at every call site, so the difference is invisible to anyone who does not
hardcode the string. Recorded in the
[rename table](../renames.md#renames); permanent, and the rule the rest of the
tables aliases will follow as they land.

### Livewire-specific provider responsibilities are dropped, not ported

`packageBooted()` registers six `Testable` mixins — `TestsActions`,
`TestsBulkActions`, `TestsColumns`, `TestsFilters`, `TestsRecords`,
`TestsSummaries` — whose every method drives a mounted Livewire component through
`$this->call()` / `$this->set()`. Sylament tests are Pest/`KernelTestCase`, not
Livewire `Testable`; the Integration suite renders through
`ViewComponent::render(Environment $twig)` and asserts on markup and container
state instead. A deliberate non-goal — **skipped** — for the same reason actions'
[`TestsActions`](../actions/actions-bundle.md#livewire-specific-provider-responsibilities-are-dropped-not-ported)
and schemas' `TestsSchemas` are. What is skipped is the **mixin registration**,
not the idea of test helpers: Filament's
[`src/Testing`](../../../vendor/filament/tables/src/Testing) subtree carries six
**todo** rows in the package README's
[supporting surface](README.md#supporting-surface) — one per `Tests*.php` file,
`TestsActions` through `TestsSummaries` — because the helpers may still be worth
porting in a Pest shape.

### The manifest declares `sylament/forms`

Filament's `filament/tables` requires `filament/forms` for exactly one reason and
so does ours: a filter's control is a form field.
[`Filters\Concerns\HasSchema`](../../../packages/tables/src/Filters/Concerns/HasSchema.php)
types `getFormField(): ?Sylament\Forms\Components\Field` and
[`Filter`](base-filter.md) builds a `Checkbox`, so the symbol is imported
unconditionally by `src/` — the test the
[split-repo dependency rule](../../../CLAUDE.md) sets for a `require`, not a
`suggest`. No cycle: `sylament/forms` requires only `actions`, `schemas`,
`support`, `doctrine/orm` and its own Symfony deps, and nothing in forms
references tables.

The test-kernel half rides along: `tests/Fixtures/Kernel.php` registers
`SylamentFormsBundle`, without which a filter form cannot be built in the
Integration suite at all — the same trap schemas' kernel already documents. Two
`SylamentTablesBundleTest` cases pin it in the shape that fails without the
registration: the booted kernel's Twig loader lists a `SylamentForms` namespace
under which the checkbox partial resolves, and a real `Checkbox` renders inside a
`Schema` bound to a `data`-bag host.

### The selection controller diverges from `table.js` in four places

The port is otherwise line-for-line ([Asset registration](#asset-registration)
names the arms it drops whole). These four are behavioural, and all four are
deliberate.

**The shift-click range excludes the clicked checkbox.** Filament's
`handleCheckboxClick` pushes every value between the anchor and the clicked box
*inclusive*, because there the box's own toggle is an inline Alpine `click`
handler that has already run by the time the range is applied. Ours runs from a
delegated `click` listener while the box's own tick is
`change->sylament--tables--table#toggleRecord`, which fires **after** the click —
so a range including the clicked key would set it and have `toggleRecord`
immediately unset it again. The coupling is worth stating because it lives in the
template, not here: **the row checkbox must stay bound to `change`**. Rebinding it
to `click` reverses the two, and every shift-selection silently comes out one
record short.

**The tracking count is clamped at zero.** Filament returns
`allSelectableRecordsCount - deselectedRecords.size` raw, which goes negative the
moment the deselected list outlives the ceiling that produced it — a filter
narrowing the query between morphs is enough. The count is rendered into the
selection banner, so `Math.max(…, 0)` turns a wrong number into a merely stale
one.

**With no ceiling, the tracking count is the page's.** Filament's
`(ceiling ?? deselected.size) - deselected.size` evaluates to exactly `0` when the
host's `getAllSelectableTableRecordsCount()` is null — the banner disappears while
records are in fact selected. We count the checked boxes on the page instead, the
same floor the tri-state header checkbox already works from, so an unbounded
tracking selection still reads as "n records selected" for the n on screen.

**Select-all's server arm is a void live action, and its page-only arm never
leaves the browser.** Filament `await`s `$wire.getAllSelectableTableRecordKeys()`
and fills the client set from the return value, for both the no-tracking and the
page-only case. UX LiveComponent actions return no value to JS, so the
no-tracking arm calls `component.action('selectAllTableRecords')` and lets the
host fill its own prop and re-render — the repaint on `render:finished` is what
puts the ticks on screen. The page-only arm needs no round trip at all: the keys
it would ask for are the `value`s of the checkboxes already rendered, so it
selects those directly. The tracking arm is unchanged — flag up, both lists empty,
zero requests.

_See also: the global [rename table](../renames.md) — the
`TablesServiceProvider` → `SylamentTablesBundle` swap and the Spatie
`PackageServiceProvider` → Symfony `AbstractBundle` idiom translation are
systematic and recorded there, as is the `View\` → `Twig\` segment that puts
`TablesIconAlias` and `TableExtension` under `Sylament\Tables\Twig\`._
