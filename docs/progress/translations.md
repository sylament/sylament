# Translations

Systematic translations that follow the conventions in [CLAUDE.md](../../CLAUDE.md), and the names and namespaces with no Filament source.
They are **not** renames and never go in [the Renames table](renames.md#renames).

## Idiom

| Laravel / Filament | Sylament | Example |
| --- | --- | --- |
| `Htmlable` | `Twig\Markup` | — |
| Livewire | UX LiveComponent | `getLivewire()` → `getLiveComponent()`, `livewireProperty()` → [`liveComponentProperty()`](schemas/tabs.md), `Schemas\Components\Livewire` → [`LiveComponent`](schemas/live-component.md), `getLivewireKey()` → [`getLiveComponentKey()`](schemas/live-component.md#the-host-id-is-minted-by-the-host-because-ux-has-no-stable-component-id), `getSubmitFormLivewireMethodName()` → [`getSubmitFormLiveComponentMethodName()`](panels/form-actions.md#one-method-name-drives-both-the-form-element-and-the-submit-action), and a closure's `HasTable $livewire` → `InteractsWithTable $liveComponent`; `hiddenOn()`'s parameter is [a rename](renames.md#hiddenon-takes-host-class-strings-so-its-parameter-is-hosts) |
| Livewire's `WithPagination` trait (`gotoPage()`, `nextPage()`, `previousPage()`, `resetPage()`, the query-string page name) | a `#[LiveProp] int $tablePage` on the host and one `#[LiveAction]` that moves it | `gotoPage($page, $pageName)` → [`gotoTablePage(page)`](tables/host-can-paginate-records.md#gototablepage-replaces-livewires-paginator-trio) |
| Eloquent | Doctrine | — |
| `Carbon\CarbonInterface` / `Illuminate\Support\Carbon` | `DateTimeInterface` / `DateTimeImmutable` | `minDate(CarbonInterface \| string \| Closure \| null $date)` → [`minDate(DateTimeInterface\|string\|Closure\|null $date)`](forms/date-time-picker.md) ([why](#carbon-types-become-the-php-date-interfaces)) |
| Eloquent `Builder` | Doctrine `QueryBuilder` | `query(Builder $query)` → [`query(QueryBuilder $query)`](tables/has-query.md#every-read-clones-a-memoised-base-query); `modifyQueryUsing(fn (Builder $query) => …)` → `fn (QueryBuilder $query) => …` |
| Blade view | Twig template | `view()` → `template()`, `viewData()` → `templateData()`, and the `*_VIEW` constants that name one — `Action::ICON_BUTTON_VIEW` → [`ICON_BUTTON_TEMPLATE`](actions/action.md) |
| Blade component scope inheritance (`$this` is the Livewire component) | an explicit host — resolved from the Twig component stack, or passed as a prop | `<x-filament-actions::modals />` → `<twig:SylamentActions:Modals />` ([modals.md](actions/modals.md#the-modals-renderer-resolves-its-host-from-the-render-stack-not-from-template-scope)) |
| A Livewire event's `livewireId` payload key | dropped — a browser event is scoped by the live root that dispatched it | `form-validation-error` and `reset-schema-component-state` ([why](#a-browser-event-needs-no-livewireid-because-its-target-is-the-host)) |
| Alpine.js | Stimulus | `x-data` / `x-on:` → a controller under `packages/*/assets/src/controllers/`, wired with StimulusBundle's Twig helpers |
| A `window` event broadcast to reach one component among many | a method on the owning Stimulus controller, which its own targets scope | `repeater-collapse` / `repeater-expand` → the repeater controller's `collapseAll` / `expandAll` ([why](#a-window-broadcast-becomes-a-method-on-the-owning-controller)) |
| `fi-*` classes | `sy-*` classes | `fi-icon-btn` → `sy-icon-btn`, `fi-disabled` → `sy-disabled`, `fi-sortable-ghost` → `sy-sortable-ghost` ([with a package code](#css-class-names-insert-a-package-code)) |
| `filament.*` route names | `sylament.*` route names | `filament.admin.pages.dashboard` → `sylament.admin.pages.dashboard`, built by [`Page::getRouteName()`](panels/page.md); the panel id and the page slug are untouched |
| A Laravel storage disk | A Flysystem storage ([league/flysystem-bundle](../../vendor/league/flysystem-bundle)) | `disk('public')` → `disk('public.storage')`, and `filament.default_filesystem_disk` → `sylament.default_filesystem_disk` ([file-upload.md](forms/file-upload.md#disk-names-a-flysystem-storage-and-there-is-no-publiclocal-swap), [`FilesystemRegistry`](support/filesystem-registry.md)) |
| `Str::uuid()` | `Uuid::v4()->toRfc4122()` ([symfony/uid](../../vendor/symfony/uid)) | [`FileUploadStateCast`](../../packages/schemas/src/State/Casts/FileUploadStateCast.php) keys a file map by uuid; `Str::ulid()` → `new Ulid()->toBase32()` names a stored upload |
| A `Str::` macro Filament declares in its service provider | a function in [`Sylament\Support`'s `helpers.php`](support/helpers.md) | `Str::sanitizeUrl($url)` → `sanitize_url($url)`, `Str::sanitizeCssColor($color)` → `sanitize_css_color($color)` ([why](#a-pure-str-macro-becomes-a-helper-function)) |
| `Arr::first()` | `array_first()` (PHP 8.5 native) | `FileUploadStateCast`'s single-file read; the no-callback arm of `Arr::first()` is `array_first()` exactly |
| Laravel's translator (`__('filament-…::…')` keys, `translateLabel()`) | an English literal | Every user-facing string ships as Filament's `en` text until [the i18n pass](../internals/i18n.md) ([file-upload.md](forms/file-upload.md#literals-not-translation-keys)) |

### Carbon types become the PHP date interfaces

Carbon is a Laravel-ecosystem package with no place here, so every Filament signature naming a Carbon type takes the PHP interface it extends, and a value the port constructs is a `DateTimeImmutable`.
The lost surface is Carbon's fluent parsing and formatting sugar, which the consumers replace with `DateTimeImmutable::createFromFormat()` and `->format()` ([the cast](schemas/date-time-state-cast.md), [the pickers](forms/date-time-picker.md)).
Where a Carbon method has no `DateTime` equivalent at all, the port ports it: `$state->diffForHumans()` becomes [`Sylament\Support\diff_for_humans($date, $now)`](support/helpers.md#diff_for_humans-is-a-carbon-port-not-a-carbon-dependency), which walks Carbon's own cascade at its `$parts = 1` default.
The helper takes `$now` explicitly where the method reads the clock, so it stays pure; that is the only shape difference.

### A browser event needs no `livewireId`, because its target is the host

Filament dispatches `form-validation-error` with `livewireId: $this->getId()`, and `reset-schema-component-state` with `{livewireId, schemaKey}`, because Livewire broadcasts an event to every component on the page and the id is how a listener recognises its own host.
Here both travel as browser events dispatched on the host's own root through `dispatchBrowserEvent()`, and the listeners compare `event.target` with `this.element.closest('[data-controller~="live"]')`, so the id has nothing left to disambiguate.
`form-validation-error` carries `schemaKey` instead, which Filament's carries nowhere: Filament's reveal walks the DOM from the first error, where here the root controller has to know which schema the failure belongs to.
Both event names carry over unchanged; only the payload translates, so neither is a rename.

### A window broadcast becomes a method on the owning controller

Filament's `repeater-collapse` / `repeater-expand` are dispatched on `window` and keyed by state path, so every repeater on the page hears them and matches itself.
Here the collapse-all and expand-all triggers bind straight to `collapseAll` / `expandAll` on the repeater's own controller, which iterates its `item` targets.
Stimulus scopes targets to the nearest controller of the same identifier, so a nested repeater's items are never touched and two cards rendering the same state path cannot cross-talk.
The events have no Sylament names because nothing dispatches them; nothing public is renamed.

### A pure `Str::` macro becomes a helper function

There is no `Str` class here to extend and no provider to boot it in, so a macro whose body is pure lands in `helpers.php` under the snake_case naming the rest of that file uses.
The body ports verbatim, `// Security:` comments included; only the call site changes shape.
A macro that needs a service goes onto the service or Twig extension owning the dependency instead; [`generate_icon_html()`](support/helpers.md#generate_icon_html-moved-to-the-twig-extension) is the precedent, and the reason `helpers.php` is pure-only.

### CSS class names insert a package code

CSS class names are not a flat prefix swap: bundles above support insert a package code (`sy-fo-input`, `sy-sc-grid`), support-level classes carry none (`sy-icon-btn`), and every template root also gets the bare `sy` marker class.
A class handed to a bundled library, such as `sy-sortable-ghost` through SortableJS's `ghostClass`, follows [the bundled-library rule](../internals/styling.md#why-is-a-bundled-library-styled-under-a-sylament-class).
Why: [`docs/internals/styling.md`](../internals/styling.md#why-do-templates-carry-only-semantic-classes).

### Custom properties carry the `--sy-` prefix

The same `fi-` → `sy-` translation covers CSS custom properties, and there it is load-bearing rather than cosmetic.
Filament's colour variables are unprefixed — `--primary-600`, `--color-600`, `--text`, `--dark-hover-bg` — and Sylament's are `--sy-primary-600`, `--sy-color-600`, `--sy-text`, `--sy-dark-hover-bg`, whether they are written in a stylesheet, emitted inline by `ColorManager::getComponentCustomStyles()`, or emitted into a `<style>` element by `sylamentStyles()`.
Custom properties inherit and Sylament embeds into a page it does not own, so one `:root { --text: … }` in the host app would otherwise repaint every Sylament component.
The exception is the Filament-verbatim grid variables (`--cols-lg`, `--col-span-default`), which stay unprefixed on purpose — [styling.md](../internals/styling.md#why-are-per-instance-values-inline-variables) draws that line.
The porting cost and the reasoning: [color-manager.md](support/color-manager.md#every-emitted-custom-property-carries-the---sy--prefix).

## Namespace segments

Segments carry over from Filament **verbatim** — `Components\`, `Enums\`, `Exceptions\`, `Concerns\`, `Contracts\`, `Icons\` and the rest all keep Filament's spelling, so `Filament\Support\Components\Concerns\` is `Sylament\Support\Components\Concerns\`.
Exactly two segments change, each only because the thing it named was replaced or has no Symfony counterpart at all:

| Filament | Sylament | Why |
| --- | --- | --- |
| `View\` | `Twig\` | The Blade → Twig idiom; Laravel's `ComponentAttributeBag` and Filament's `SupportIconAlias` both live in `Sylament\Support\Twig\`. |
| `Http\Controllers\` | `Controllers\` | Symfony has no `Http\` application tier; the plural `Controllers` is [Filament's, kept](#no-segment-is-renamed-for-style). |

### No segment is renamed for style

Only the Laravel `Http\` tier drops from `Http\Controllers\`: middleware became event listeners and a response is Symfony's own object, so the segment names nothing here.
The plural `Controllers` is Filament's spelling kept verbatim, matching the repository's plural object directories (`Pages\`, `Components\`, `Exceptions\`), although Symfony's own bundles use a singular `Controller\` (FrameworkBundle, WebProfilerBundle).
Symfony's habit of a singular `Exception\` is not followed either: matching Filament costs a migrating user nothing to learn, and one carve-out is worse than none.

### Three namespaces leave `Components\`

What they hold is not part of the component tier — neither a component, nor a concern or contract of one.
Two of them land under `State\` rather than at the root, filing beside the [`PropertyAccessor`](schemas/property-accessor.md) they read and write through; each drops the segment that `State\` says for it (`StateCasts` → `Casts`, `Utilities` → nothing, since the word named no category):

| Filament | Sylament | Holds |
| --- | --- | --- |
| `Filament\Schemas\Components\StateCasts\` | `Sylament\Schemas\State\Casts\` | `StateCast` and its implementations — value objects that transform state |
| `Filament\Schemas\Components\Utilities\` | `Sylament\Schemas\State\` | `Get` / `Set` — the closure-injected state accessors |
| `Filament\Support\Components\ComponentManager` | `Sylament\Support\Configuration\ComponentManager` | The `configureUsing()` registry — an engine, filed by capability like `Evaluation\Invoker`, not beside what it configures |

A migrating user importing `Filament\Schemas\Components\Utilities\Get` will not find it by prefix swap alone, which is why these are recorded here rather than left to the verbatim rule.

### The colour tier files under `Colors\`, not under `Twig\Components\`

The colour tier leaves `View\` for the same reason, and is the one place the `View\` → `Twig\` translation is deliberately **not** applied:

| Filament | Sylament | Holds |
| --- | --- | --- |
| `Filament\Support\View\Components\` (the policy objects) | `Sylament\Support\Colors\Components\` | `BadgeComponent`, `ButtonComponent`, … — the per-element contrast policies |
| `Filament\Support\View\Components\ColorMaps\` | `Sylament\Support\Colors\Maps\` | `ComponentColorMap` and the two specialised resolvers |
| `Filament\Support\View\Components\Contracts\` | `Sylament\Support\Colors\Contracts\` | `HasColor` (the `getColorMap()` one) and `HasDefaultGrayColor` |
| `Filament\Schemas\View\Components\` | `Sylament\Schemas\Colors\Components\` | schemas' `TextComponent` and `IconComponent` |
| `Filament\Tables\View\Components\Columns\` | `Sylament\Tables\Colors\Components\` | tables' `TextColumnComponent\ItemComponent`, `TextColumnComponent\ItemComponent\IconComponent` and `IconColumnComponent\IconComponent` |

`Sylament\Support\Twig\Components\` — where the translation would have put them — means a class-backed **renderable** Twig component, the shape actions' `Modals` has.
A colour policy is never rendered and has no template, so it is filed by capability at `src/` level like `Evaluation\Invoker` and `Configuration\ComponentManager`.
Class names, including the nesting (`DropdownComponent\ItemComponent`, `TextColumnComponent\ItemComponent\IconComponent`), are Filament-verbatim to any depth; only the leading segments move.
Detail: [color-policies.md](support/color-policies.md#the-tier-is-filed-under-colors-not-under-twigcomponents).

The tables row absorbs one more segment than the others: Filament groups its tables policies under a `Columns\` directory, which is the **only** child `View\Components\` has there, so it separates nothing from anything and is dropped into the leading segments rather than carried.
Everything below it is untouched, so an icon policy belonging to the text column's item is `TextColumnComponent\ItemComponent\IconComponent` here exactly as it is there, the same nesting support's blocked [`DropdownComponent\ItemComponent\IconComponent`](support/color-policies.md#surface) row names.
The nesting is a class-string a consumer types into `colorAttributes()`, so a flatter spelling would be a rename in everything but name.

### Support-level traits split by who composes them

`Filament\Support\Concerns\` has two destinations:

| Filament | Sylament | Which traits |
| --- | --- | --- |
| `Filament\Support\Concerns\` | `Sylament\Support\Components\Concerns\` | Everything only a component composes — `HasIcon`, `HasColor`, `HasExtraAttributes`, … and `Configurable`, whose sole consumer is `Component` |
| `Filament\Support\Concerns\` | `Sylament\Support\Concerns\` | `EvaluatesClosures` alone |

`EvaluatesClosures` stays at root because a non-component composes it, which is checkable in Filament rather than a matter of taste: of its three direct importers there, two — `Colors\ColorManager` and `TimezoneManager` — are plain manager classes.
[`Sylament\Support\Colors\ColorManager`](../../packages/support/src/Colors/ColorManager.php) composes the trait to evaluate a `Closure` palette registration, and it is not a component, so a trait under `Support\Components\Concerns\` would be the wrong import.
The hold moves only if that consumer goes away.

### `CanBeMarkedAsRequired` moves down into schemas

One trait moves **down a package** for the same who-composes-it reason, except that here the layering forces it rather than merely suggesting it:

| Filament | Sylament | Which trait |
| --- | --- | --- |
| `Filament\Forms\Components\Concerns\` | `Sylament\Schemas\Components\Concerns\` | `CanBeMarkedAsRequired` alone |

Filament hosts the trait in forms yet imports it from schemas' `Fieldset` (`vendor/filament/schemas/src/Components/Fieldset.php`) — an upward import from schemas into forms.
Here `forms` depends on `schemas`, so that direction cannot be reproduced, and the trait has to sit at or below the lower package for both consumers to reach it.
Nor is that a style question: the CI split publishes each package standalone, so a package leaning on a symbol only a sibling declares breaks the moment it is installed on its own.
The class name, methods and body are unchanged, which is why this is a relocation rather than a rename.
See [schemas/can-be-marked-as-required.md](schemas/can-be-marked-as-required.md).

### `FusedGroup` moves up into forms

One **component** moves the other way for the same reason, and it is the same Filament file that forced the trait down:

| Filament | Sylament | Which class |
| --- | --- | --- |
| `Filament\Schemas\Components\FusedGroup` | `Sylament\Forms\Components\FusedGroup` | `FusedGroup` alone |

The criterion in both directions is **the lowest package that reaches every symbol the thing needs**.
For a trait two tiers compose, that is the lower package, so `CanBeMarkedAsRequired` goes down.
`FusedGroup` imports three forms concerns — `CanBeMarkedAsRequired`, `HasExtraFieldWrapperAttributes`, `HasHelperText` — a forms contract, and `Forms\Components\Field` for its error walk, and what it renders is forms' field-wrapper chrome; nothing it needs can move down with it, so the lowest package reaching all of it is `forms` and the class goes up.
Filament can host it in schemas because Laravel ships every Filament package together; the CI split here publishes each one standalone, so a `sylament/schemas` importing a forms symbol breaks on install.
Class name, factory, methods and behaviour are unchanged; only the namespace moves, taking the CSS class code with it — `fi-sc-fused-group` becomes `sy-fo-fused-group`, the [component code](../internals/styling.md#why-do-templates-carry-only-semantic-classes) naming the package that ships the component rather than the vendor directory the rules were copied from.
See [forms/fused-group.md](forms/fused-group.md#fusedgroup-lives-in-forms-not-schemas).

### `HasCellState` and `CanAggregateRelatedRecords` move up into tables

They are two of the traits Filament's `Column` composes from `Support\Concerns\`, and both reach Doctrine.
`support` depends on neither Doctrine nor schemas — deliberately, since [nothing is pushed down into support that support does not use](../../.claude/rules/manifests.md) — so hosting them there would mean either dragging the ORM into the base package for one consumer or splitting one read across two packages:

| Filament | Sylament | Which trait |
| --- | --- | --- |
| `Filament\Support\Concerns\HasCellState` | `Sylament\Tables\Columns\Concerns\HasCellState` | The cell read, which goes through schemas' `PropertyAccessor` — [why](tables/has-cell-state.md#hascellstate-is-relocated-into-the-column-tier) |
| `Filament\Support\Concerns\CanAggregateRelatedModels` | `Sylament\Tables\Columns\Concerns\CanAggregateRelatedRecords` | The six aggregate declarations, read by tables' own [batched aggregate query](tables/has-query.md#an-aggregate-is-a-batched-side-query-and-a-hidden-subselect-never-a-select-on-the-page-query) |

Filament's placement serves a **second** consumer in both cases — `Actions\Exports\ExportColumn` composes each of them — and the port has no export surface.
When `ExportColumn` is ported, both move to the lowest package both consumers reach, which is `schemas` (`tables` and `actions` each require it) and still not `support`.
The first is a pure relocation; the second is [also renamed](renames.md#renames), which is why it has a table row of its own and this one does not repeat it.

## Names with no Filament source

Methods, classes and events that exist only here, because a port needed behaviour Filament has no member for.
Nothing to rename from, so they are listed rather than mapped — and listed at all so a migrating user who greps Filament for the name and finds nothing knows the absence is real.

| Sylament | On | Why it is not a rename |
| --- | --- | --- |
| [`nullWhenBlank()`](schemas/has-nullable-branch.md#a-declared-branch-is-nulled-whole-before-its-leaves-are-written) | `Sylament\Schemas\Components\Concerns\HasNullableBranch` | Filament's nearest member, `relationship(condition:)`, has the inverted polarity (true means *save*) and no built-in blank test. |
| [`isBlankStateValue()`](schemas/has-nullable-branch.md#the-built-in-blank-test-asks-the-branchs-own-leaves) | `Sylament\Schemas\Components\Concerns\HasNullableBranch`, overridden on `Checkbox` / `Toggle` | Filament has no branch pass for the predicate to serve. |
| [`ownsRecordSubtree()`](schemas/belongs-to-record.md#a-component-can-own-its-whole-record-subtree) | `Sylament\Schemas\Components\Concerns\HasChildComponents` | Filament maps by wholesale array assignment, so it has no record path map to mark. |
| [`itemsAs()`](forms/repeater.md#the-item-cast-fires-at-the-repeaters-two-pipeline-boundaries-not-in-the-generic-seam) | `Sylament\Forms\Components\Repeater` | Filament's nearest member, `relationship()`, is identity-keyed mapping, which is [exclusive with `itemsAs()`](forms/repeater-relationship.md#the-relationship-family-loses-its-caches-its-lifecycle-hooks-and-its-query-scope). |
| [`ObjectStateCast`](schemas/object-state-cast.md) | `Sylament\Schemas\State\Casts` | Filament ships no object-constructing cast, so the name follows the shipped cast family (`BooleanStateCast`, `OptionStateCast`). |
| [`getOptionComparisonState()`](forms/has-options.md#the-option-vocabulary-is-normalized-once-rather-than-per-template) | `Sylament\Forms\Components\Concerns\HasOptions` | Filament's Alpine writes `selected` / `checked` client-side, where the port [renders them in PHP](forms/select.md#the-template-renders-the-selected-state-itself). |
| [`isMultiple()`](forms/checkbox-list.md#ismultiple-is-port-plumbing-with-no-filament-counterpart) | `Sylament\Forms\Components\CheckboxList` | Filament reads multiplicity off `getRelationship(): ?BelongsToMany`'s return type. |
| [`ValidDate`](forms/date-comparison.md#the-date-comparison-family-builds-constraints-where-filament-builds-rule-strings) | `Sylament\Forms\Validation` | Filament writes the `rule('date')` string; [no stock constraint fits](#a-validation-constraint-is-first-party-only-where-no-stock-one-fits). |
| [`DateComparison`](forms/date-comparison.md#the-date-comparison-family-builds-constraints-where-filament-builds-rule-strings) | `Sylament\Forms\Validation` | Filament writes `"after:{$date}"` and friends; its operator constants carry Filament's rule spellings, so `dateComparisonRule()` passes its argument through. |
| [`Prohibited`](forms/can-be-validated.md#prohibited-is-first-party-because-blank-violates-on-an-empty-array), [`Distinct`](forms/can-be-validated.md#distinct-is-three-arms-of-one-first-party-constraint-not-laravels-rule), [`MatchesField`](forms/can-be-validated.md#same-different-and-confirmed-need-a-first-party-constraint), [`StartsWith`](forms/can-be-validated.md#the-format-sugar-is-stock-where-a-constraint-fits-and-first-party-where-none-does), `EndsWith`, [`Alpha`](forms/can-be-validated.md#the-format-sugar-is-stock-where-a-constraint-fits-and-first-party-where-none-does), `AlphaDash`, `AlphaNum`, `Ascii`, `ActiveUrl` | `Sylament\Forms\Validation` | Filament writes each as a Laravel rule string; [no stock constraint fits](#a-validation-constraint-is-first-party-only-where-no-stock-one-fits). |
| [`UniqueValue`](forms/probes-records.md#unique-and-exists-are-one-dql-probe-behind-a-container-service) / `ExistingValue` | `Sylament\Forms\Validation` | Filament writes the `unique` / `exists` rules; [named to avoid a collision](#uniquevalue-existingvalue-and-requiredvalue-are-named-to-avoid-an-import-collision). |
| [`RequiredValue`](forms/can-be-validated.md#required-carries-laravels-blank-set-not-symfonys) | `Sylament\Forms\Validation` | Laravel's `required` rule, which Symfony's `NotBlank` is not; [named to avoid a collision](#uniquevalue-existingvalue-and-requiredvalue-are-named-to-avoid-an-import-collision). |
| [`MinFileSize`](forms/file-upload.md) | `Sylament\Forms\Validation` | Filament writes Laravel's `min:{n}` rule, and Symfony's `File` caps a size but never floors it; the unit is kibibytes, as `BaseFileUpload::minSize()` translates. |
| [`PresenceGate`](schemas/presence-gate.md) | `Sylament\Schemas\Validation` | Its source, Laravel's `Validator::presentOrRuleIsImplicit()`, is a private method; Symfony's validator has no implicit-rule concept. |
| [`ImplicitConstraint`](schemas/presence-gate.md) | `Sylament\Schemas\Validation\Contracts` | Laravel's equivalent is a hardcoded `$implicitRules` array of rule names. |
| [`Paginator`](support/pagination.md) and its counts — `getRecords()`, `getTotalRecordCount()`, `getPage()`, `getRecordsPerPage()`, `getLastPage()`, `getFirstRecordNumber()`, `getLastRecordNumber()`, `getPageWindow()`, `withRecords()` | `Sylament\Support\Pagination` | Filament returns Laravel's `LengthAwarePaginator` ([why first-party](support/pagination.md#the-carrier-is-first-party-rather-than-an-ecosystem-package), [why named in full](support/pagination.md#the-counts-are-named-in-full-not-abbreviated)). |
| [`getPaginator()`](tables/table.md#getrecords-unwraps-the-paginator-and-getpaginator-is-the-only-seam-to-it) / `getTablePaginator()` | `Sylament\Tables\Table\Concerns\HasRecords`, `Sylament\Tables\Concerns\HasTable` | Filament's `getRecords()` is the paginator; here it answers the keyed array. |
| [`getQueryEntityManager()`](tables/has-query.md#every-read-clones-a-memoised-base-query) | `Sylament\Tables\Table\Concerns\HasQuery` | An Eloquent builder carries its own connection; Doctrine's read side takes an `EntityManagerInterface`. |
| [`getSearchDebounceMilliseconds()`](tables/can-search-records.md#getsearchdebouncemilliseconds-is-a-sylament-only-translation-of-filaments-duration-string) | `Sylament\Tables\Table\Concerns\CanSearchRecords` | Livewire parses Filament's duration string itself; UX's `debounce(N)` takes milliseconds. |
| [`SessionNotifications`](notifications/notifications-bundle.md#the-flash-bag-replaces-laravels-session-push-and-owns-the-key) — `push()` / `pull()`, `FLASH_KEY` | `Sylament\Notifications\Delivery` | Filament's store is a `session()->push()` / `pull()` literal repeated in two files. |
| [`LiveDeliverySubscriber`](notifications/notifications-bundle.md#the-live-delivery-decision-point-is-a-prerenderevent-subscriber-not-a-dehydrate-hook) | `Sylament\Notifications\Delivery` | Filament's counterpart is a closure on the `dehydrate` hook in `NotificationsServiceProvider::packageBooted()`. |
| `sylament:notification` (browser event) | dispatched by `Sylament\Notifications\Delivery\LiveDeliverySubscriber`, consumed by the stack's Stimulus controller | Filament's `notificationsSent` / `notificationSent` / `notificationClosed` are Livewire events with other jobs; this one carries [rendered HTML](notifications/notifications.md#live-delivery-appends-server-rendered-html-instead-of-re-requesting-the-stack), and its `sylament` prefix matches actions' `sylament-open-modal`. |
| [`renderTable()`](tables/tables-bundle.md#the-bundle-registers-a-rendertable-twig-extension-which-filament-has-no-counterpart-for) | `Sylament\Tables\Twig\Extension\TableExtension` | Blade renders Filament's `Htmlable` table by echoing it, as with `renderSchema()` and `renderAction()`. |
| [`getFormattedState()`](tables/text-column.md#getformattedstate-replaces-the-embedded-views-local-preamble) | `Sylament\Tables\Columns\Concerns\CanFormatState` | Filament computes it as a local in `toEmbeddedHtml()`. |
| [`getStateItems()`](tables/text-column.md#getstateitems-is-the-cells-value-list) | `Sylament\Tables\Columns\TextColumn`, with `getStateOverListLimitCount()` beside it there, and with [`getStateOverLimitCount()`](tables/image-column.md#getstateitems-resolves-urls-in-php-not-in-the-template) beside it on `Sylament\Tables\Columns\ImageColumn` | Filament builds the item list inline in `toEmbeddedHtml()`; `TextColumn`'s count says `List` because the column also has a character `limit()`. |
| [`getCellClassName()` / `getHeaderCellClassName()`](tables/column.md#the-hook-class-kebabs-each-dotted-segment-on-its-own) | `Sylament\Tables\Columns\Column` | Filament builds both strings inline in `index.blade.php`. |
| [`getActionKind()`](tables/table.md#the-cell-wrapper-interleaves-the-column-arms-with-the-record-ones) | `Sylament\Tables\Columns\Column` | Filament's Blade branches on `$columnAction instanceof Action` inline. |
| [`getClientState()`](tables/has-columns.md#the-cell-repaints-from-the-root-because-a-touched-control-is-pinned-through-every-morph) | `Sylament\Tables\Columns\Concerns\CanUpdateState` | Filament's cell keeps the value in Alpine. |
| [`getUpdateStateError()`](tables/has-columns.md#the-refusal-is-request-scoped-and-server-rendered-where-filaments-lives-on-the-client) and [`getTableColumnStateError()`](tables/interacts-with-table.md#the-contract-is-thirty-five-methods-of-filaments-forty-six) | `Sylament\Tables\Columns\Concerns\CanUpdateState` and `Sylament\Tables\Contracts\InteractsWithTable` | Filament throws out of `updateTableColumnState()` and Livewire hands the message to the client. |
| [`getRecordRouteKey()`](panels/resource.md#a-record-is-resolved-through-the-resources-own-query) | `Sylament\Panels\Resources\Resource` | An Eloquent model answers `getRouteKey()` itself through `UrlRoutable`; `getRecordRouteKeyName()` is ported. |
| [`getResourceInstance()`](panels/resource-pages.md) | `Sylament\Panels\Resources\Pages\Page` | Filament's `getResource()` class-string is called straight through, `static::getResource()::getModel()` and friends, at 32 sites in `Resources/Pages/Page.php`. |
| The `get*Class()` family — [`getSizeClass()`](tables/text-column.md#getsizeclass-is-sylament-only-and-the-partial-resolves-the-size-twice), `getAlignmentClass()`, `getWeightClass()`, `getFontFamilyClass()`, [`ImageColumn::getLimitedRemainingTextSizeClass()`](tables/image-column.md#the-sy-size-base-css-arm-is-not-ported), and schemas' own `Text::getSizeClass()` / forms' `Repeater::getAddActionAlignmentClass()` | `Sylament\Tables\Columns\TextColumn` / `ImageColumn`, `Sylament\Support\Components\Concerns\HasAlignment` / `HasWeight` / `HasFontFamily`, `Sylament\Schemas\Components\Text`, `Sylament\Forms\Components\Repeater` | [One pattern](#the-getclass-family-is-one-pattern-and-filaments-getclass-means-a-class-string). |
| [`resolveSchemaByName()`](schemas/interacts-with-schemas.md#resolveschemabyname-is-a-sylament-only-extraction-with-no-filament-source) | `Sylament\Schemas\Concerns\HasSchemas` | The gate is Filament's, a private step inside `InteractsWithSchemas::cacheSchema()`; only the name is new. |
| `sylament:schema-component-method-result` (browser event) | dispatched by `Sylament\Schemas\Concerns\HasSchemas::callSchemaComponentMethod()` | Filament's `callSchemaComponentMethod()` returns to Livewire, and `#[LiveAction]` has [no return channel](schemas/interacts-with-schemas.md#callschemacomponentmethod-answers-through-a-browser-event-with-no-partial-render). |
| `sylament:select-refresh-selected-option-label` (browser event) | dispatched by `Sylament\Forms\Components\Select::refreshSelectedOptionLabel()`, consumed by forms' `select` Stimulus controller | Filament's `filament-forms::select.refreshSelectedOptionLabel` is a Livewire event; this is a [browser event with `{statePath}` alone](forms/select.md#refreshselectedoptionlabel-dispatches-a-browser-event-the-control-answers-by-re-syncing). |
| [`getFileUrl()`](support/filesystem-registry.md#getfileurl-mints-the-browser-facing-url) | `Sylament\Support\Filesystem\FilesystemRegistry` | Filament writes the `temporaryUrl()` / `url()` dance inline at each call site. |
| [`getActiveTabKey()`](schemas/tabs.md) | `Sylament\Schemas\Components\Tabs` | Filament's `getActiveTab(): int` answers a 1-based index for Alpine; the port renders the active tab and needs its key. `getActiveTab()` is ported beside it. |
| [`getStartStepKey()`](schemas/wizard.md) | `Sylament\Schemas\Components\Wizard` | The same split: Filament's ported `getStartStep(): int` answers the index its Alpine component consumes. |
| [`getActiveLiveComponentTab()`](schemas/tabs.md#two-sylament-only-accessors-carry-what-alpine-read-from-the-client) | `Sylament\Schemas\Components\Tabs` | Filament's Blade reads `$wire.{$property}` inside an Alpine expression. |
| [`getTabs()`](schemas/tabs.md#gettabs-filters-hidden-tabs-out-so-every-index-is-a-visible-position) / [`getSteps()`](schemas/wizard.md#every-index-is-an-index-into-the-visible-steps) | `Sylament\Schemas\Components\Tabs`, `Sylament\Schemas\Components\Wizard` | Filament repeats `$this->getChildSchema()->getComponents()` inline; [the names mean something else on its page tier](#gettabs-and-getsteps-mean-something-else-on-filaments-page-tier). |
| [`GlobalSearchProviderRegistry`](panels/global-search.md#the-provider-is-resolved-through-a-tagged-registry-rather-than-app) | `Sylament\Panels\GlobalSearch` | Filament resolves the provider with `app()`; the shape follows [`ResourceRegistry`](panels/resource.md). |
| [`hasDetailLabels()`](panels/global-search.md#hasdetaillabels-is-a-per-result-check) | `Sylament\Panels\GlobalSearch\GlobalSearchResult` | Filament's Blade memoises `Arr::isAssoc()` across the render, so the first result decides for all. |
| [`getGlobalSearchDebounceMilliseconds()`](panels/global-search.md#the-debounce-is-translated-for-ux-live-component-the-way-tables-already-does-it) | `Sylament\Panels\Panel\Concerns\HasGlobalSearch` | The [`getSearchDebounceMilliseconds()`](#names-with-no-filament-source) translation, one package up. |
| [`getWizard()`](schemas/step.md#the-owning-wizard-is-a-method-that-throws-rather-than-a-docblock-assertion) | `Sylament\Schemas\Components\Wizard\Step` | Filament reads the parent into a local inside `Step::toEmbeddedHtml()`; not the panel tier's `getWizardComponent()`. |
| [`CanPersistTableState`](tables/can-persist-table-state.md#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it) — the trait, plus `getTableSessionKey()`, `persistTableStateToSession()`, `readTableSessionState()`, `forgetTableSessionState()`, `getTableStateMainRequest()` and the four `persistTable*InSession()` writers | `Sylament\Tables\Concerns\CanPersistTableState` | Filament's session reads and writes are inline in `bootedInteractsWithTable()`, a hook UX has no counterpart for. |
| [`groupTable()`](tables/grouping.md#the-state-is-one-non-writable-scalar-and-its-getter-is-what-forces-dehydratewith) and [`mountTableGrouping()`](tables/grouping.md#a-bookmarked-grouping-beats-a-selectable-default-group) | `Sylament\Tables\Concerns\CanGroupRecords` | Livewire writes Filament's public `$tableGrouping` straight from the client and seeds it in `bootedInteractsWithTable()`. |
| [`getTableGroupingColumn()`](tables/grouping.md#a-null-direction-is-not-ungrouped) | `Sylament\Tables\Concerns\CanGroupRecords` | Filament splits the token in its Alpine `x-data`; `getTableGroupingDirection()` is ported. |
| [`getTableRecordGroupBoundaries()`](tables/grouping.md#the-group-boundaries-are-one-php-map-rather-than-loop-carried-template-state) | `Sylament\Tables\Concerns\CanGroupRecords` | Filament's Blade loop carries the previous group key from row to row. |
| [`getGroupedTableSummaryQuery()`](tables/summarizer.md#the-per-group-figure-is-one-scoped-query-per-group-not-one-group-by) | `Sylament\Tables\Concerns\CanSummarizeRecords` | Filament computes every group's figures in one `GROUP BY` batch inside its view. |
| [`Columns\Summarizers\Concerns\HasSelectAlias`](tables/summarizer.md#the-alias-trio-is-one-trait-behind-a-marker-interface-where-filament-copies-it-three-times) and its `Contracts\HasSelectAlias` marker | `Sylament\Tables\Columns\Summarizers` | Filament copies the `selectAlias()` / `getSelectAlias()` / `getSelectedState()` trio into `Sum`, `Average` and `Count`. |
| [`Query\SummaryQuery`](tables/summarizer.md#every-summary-read-is-one-clone-with-the-order-reset-and-the-paging-cleared) — `readRow()`, `readDistinct()`, `qualifyAttribute()` | `Sylament\Tables\Query` | Filament writes `$query->select(…)->get()` inline in each summarizer. |
| [`getTableUrlMapping()`](tables/has-table.md#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page) and the seven `modify*Prop()` modifiers (`modifyTablePageProp()`, `modifyTableSortProp()`, `modifyTableSearchProp()`, `modifyTableColumnSearchesProp()`, `modifyTableFiltersProp()`, `modifyTableGroupingProp()`, `modifyActiveTabProp()`) | `Sylament\Tables\Concerns\HasTable` and its concerns; `Sylament\Panels\Resources\Concerns\HasTabs` | Filament binds the props with a `#[Url]` attribute; a UX `#[LiveProp(modifier: …)]` names a method. `getTableQueryStringIdentifier()` [is Filament's](tables/has-table.md#the-query-string-identifier-is-live-on-the-table-and-dead-in-the-modifiers). |
| [`RecordKey::of()`](schemas/record-key-contract.md#the-api) / `find()` / `fromValues()` / `toIdentifier()` | `Sylament\Schemas\Identity\RecordKey` | Filament's `getRecordKey(Model \| array): string` reads one Eloquent key and has no inverse. |
| [`IdentifierConstraint::equals()`](schemas/record-key-contract.md#the-operations-and-their-dql) / `notEquals()` / `in()` / `notIn()` / `inColumns()` / `correlate()` / `select()` / `groupBy()` / `orderBy()` / `chunk()` | `Sylament\Schemas\Identity\IdentifierConstraint` | Eloquent writes each inline as a single-column comparison (`whereKey`, `whereKeyNot`). |
| [`KeySelection::keyOf()`](schemas/record-key-contract.md#the-operations-and-their-dql) / [`keyOfHydrated()`](schemas/record-key.md#a-hydrated-row-is-keyed-without-reading-its-fields-a-second-time) | `Sylament\Schemas\Identity\KeySelection` | Eloquent hands back models, so no row is re-keyed. |
| [`makeRecord()`](panels/create-record.md) | `Sylament\Panels\Resources\Pages\CreateRecord` | Filament's `handleRecordCreation(array $data)` builds `new ($this->getModel())($data)`; here [`handleRecordCreation()` takes the record](panels/create-record.md). The name is borrowed from `TranslatableContentDriver::makeRecord()`. |
| [`validateRecordKey()`](schemas/can-be-validated.md#a-taken-assigned-key-is-a-field-error-not-a-unique-violation) / `isRecordKeyValidated()` | `Sylament\Schemas\Concerns\CanBeValidated` (on `Schema`) | Filament has no automatic key rules; the shape follows its `bool \| Closure $condition = true` switches. |
| [`withEntityManager()`](schemas/association-reader.md#withentitymanager-is-a-clone-so-a-subclassed-reader-survives-the-rebind) | `Sylament\Schemas\State\AssociationReader` | An Eloquent relation carries its own connection; the name follows the house `with*` convention. |
| [`deleteSelectedRecordsByKey()`](actions/delete-action.md#the-by-key-arm-is-a-dql-delete-over-the-plucked-keys) | `Sylament\Actions\DeleteBulkAction` | Filament inlines `$action->getSelectedRecordsQuery()->delete()` in the action's closure. |

### A validation constraint is first-party only where no stock one fits

Filament writes each of these as a Laravel rule string, so there is no class to rename from, and each was measured against the nearest stock Symfony candidate before being written.
`Blank` violates on `[]`, `IdenticalTo` throws on a null comparison value, `Type('alpha')` is byte-only `ctype_alpha`, `Charset('ASCII')` passes control characters, and nothing in `symfony/validator` resolves DNS.
`Type('string')` says nothing about parseability and Symfony's own `DateTime` constraint demands one exact format, where a `datetime-local` control legally sends [several spellings](forms/date-time-picker.md#the-wire-format-uses-a-space-and-the-browser-answers-with-a-t) of the same instant, so `ValidDate` is a class of its own.
`GreaterThan` and its family cannot stand in for `DateComparison`, because `AbstractComparisonValidator` converts a string boundary only when the validated value is already a `DateTimeInterface`, and the runner validates raw wire strings.
`Alpha`, `AlphaDash`, `AlphaNum` and `Ascii` are `Regex` subclasses that reuse Symfony's own validator — [principles §2](../internals/principles.md)'s *wrap* rung — which buys each a readable default message and its own `validationMessages()` class key.

### `UniqueValue`, `ExistingValue` and `RequiredValue` are named to avoid an import collision

They are named for what they assert rather than after Laravel's `unique`, `exists` and `required`.
Symfony ships its own `Constraints\Unique`, which asserts an array has no duplicates, and `Symfony\Contracts\Service\Attribute\Required` is the DI attribute `CanBeValidated` itself imports.
Two classes of one name in an import list would let an IDE auto-import the wrong one as a `validationMessages()` key, and an unmatched key is silently ignored by design.
`RequiredValue` exists at all because Symfony's `NotBlank` violates on `false`, so translating `required` to it would make every boolean field over a non-nullable column unsubmittable while off.

### The `get*Class()` family is one pattern, and Filament's `get*Class()` means a class-string

Each member is a getter beside `getX()` that turns the enum-or-string union `getX()` answers into the CSS class fragment a template emits (`sy-size-lg`, `sy-align-center`, `sy-font-bold`), passing a custom string through untouched.
Filament has no counterpart, because its embedded views hold the resolved value in a local and interpolate `"fi-size-{$size->value}"` at the point of use, which a Twig template cannot do without type-sniffing the union in markup.
The family is the [template-side counterpart](tables/text-column.md#the-cell-renders-a-twig-partial-where-filament-emits-an-embedded-view) of the preamble `getFormattedState()` translates, and a new member follows the pattern rather than earning a row of its own.
The name shape exists in Filament and means something else: `grep -rn "function get[A-Za-z]*Class()" vendor/filament/*/src` finds `getPageClass()` on `RelationGroup`, `RelationManager` and `ManageRelatedRecords`, and `getCsrfMiddlewareClass()` on `PanelProviderClassGenerator`, and every one returns a PHP class-string.
So a migrating user grepping the suffix lands on the panel tier, not on this.

### `getTabs()` and `getSteps()` mean something else on Filament's page tier

`Resources\Concerns\HasTabs::getTabs()` and `Resources\Pages\Concerns\HasWizard::getSteps()` are page methods a user overrides to declare tabs and steps, where the components' members answer what the component already holds.
Filament's components name neither list, repeating `$this->getChildSchema()->getComponents()` at each use site — five in `Tabs`, four in `Wizard` — and filtering by type only where `toEmbeddedHtml()` needs it.
[`Panels\Resources\Concerns\HasTabs::getTabs()`](panels/has-tabs.md) ships under Filament's spelling, so both meanings of `getTabs()` exist in this codebase, as they do in Filament's.

### `RecordTypeReader`, `RecordTarget` and `HasNullableBranch` have no Filament counterpart

They are Sylament-only classes rather than renamed Filament ones.
Eloquent attributes are untyped, so the write-side type question these answer cannot be asked there; [record-type-reader.md](schemas/record-type-reader.md) records the reasoning.

## Namespaces with no Filament source

Segments that exist only here, because a port needed classes Filament does not have.
Nothing to rename from, so they are listed rather than mapped.

| Sylament | Holds | Why it exists |
| --- | --- | --- |
| `Sylament\Support\Pagination\` | `Paginator` | Filament carries a page of records in Laravel's `LengthAwarePaginator`; the port supplies the carrier ([pagination.md](support/pagination.md)). |
| `Sylament\Notifications\Delivery\` | `SessionNotifications`, `LiveDeliverySubscriber` | Filament's delivery mechanics are a session literal and a provider closure; both are services here ([notifications-bundle.md](notifications/notifications-bundle.md)). |
| `Sylament\Forms\Validation\` | the constraints and their validators, plus `Concerns\ProbesRecords` | Filament emits Laravel rule strings; `Concerns\ProbesRecords` serves the two validators that are container services, `UniqueValueValidator` and `ExistingValueValidator` ([probes-records.md](forms/probes-records.md#unique-and-exists-are-one-dql-probe-behind-a-container-service)). |
| `Sylament\Schemas\Validation\` | `PresenceGate`, `RecordKeyGuard`, `Contracts\ImplicitConstraint` | Laravel's counterpart is a private method plus a string array inside `Validator`; the contract sits here because [neither a host nor a component implements it](../internals/php.md#contracts) ([presence-gate.md](schemas/presence-gate.md)). |
| `Sylament\Support\Evaluation\` | `Invoker`, `ClosureParameter` | Filament inlines the closure-resolution loop in `EvaluatesClosures` and falls back to `app()->make()` ([component.md](support/component.md#closure-resolution-lives-in-a-service)). |
| `Sylament\Schemas\Identity\` | `RecordKey`, `IdentifierConstraint`, `KeySelection`, `IdentifierFields` | Eloquent has no composite-key vocabulary ([record-key.md](schemas/record-key.md)). |

Each is filed by capability at `src/` level, like every other engine.
