# Rename table

The single global record of API renames. Preserve Filament's API names by
default — migrating users grep for them. Rename only when Filament itself is
inconsistent and the better name already exists in their codebase; record every
rename here in the same change.

Two questions this file answers:

1. **Did this Filament name change?** → [Renames](#renames). If it isn't in that
   table, the name carries over unchanged.
2. **Where does this namespace or idiom land?** → [Not renames](#not-renames).
   Those are systematic and derivable, so they are documented, not enumerated.

## Renames

Sorted by Filament name. Every row is a name a migrating user could **not**
derive from the conventions below.

| Filament | Sylament | Kind | Home | Why |
| --- | --- | --- | --- | --- |
| `Filament\Actions\Concerns\InteractsWithActions` | `HasActions` | trait | `Sylament\Actions\Concerns\HasActions` | [Swapped](#interface-and-trait-names-are-swapped) — `Has*` matches the house trait prefix (`HasName`, `HasAction`, …). |
| `Filament\Actions\Contracts\HasActions` | `InteractsWithActions` | interface | `Sylament\Actions\Contracts\InteractsWithActions` | [Swapped](#interface-and-trait-names-are-swapped) — the interface names the behavior the host implements. |
| `Filament\Forms\Components\Contracts\HasValidationRules` | `HasValidationConstraints` | interface | `Sylament\Schemas\Components\Contracts\HasValidationConstraints` | Relocated into **schemas** — the schema walk type-checks it, and schemas may not reference a forms symbol (the split-repo dependency rule); renamed per the [constraint vocabulary](#laravel-rule-vocabulary-becomes-symfony-constraint-vocabulary). |
| `Filament\Schemas\Components\Concerns\BelongsToModel` | `BelongsToRecord` | trait | `Sylament\Schemas\Components\Concerns\BelongsToRecord` | [Record vocabulary](#the-model-vocabulary-collapses-onto-record). |
| `Filament\Schemas\Concerns\BelongsToModel` | `BelongsToRecord` | trait | `Sylament\Schemas\Concerns\BelongsToRecord` | [Record vocabulary](#the-model-vocabulary-collapses-onto-record). |
| `Filament\Schemas\Concerns\InteractsWithSchemas` | `HasSchemas` | trait | `Sylament\Schemas\Concerns\HasSchemas` | [Swapped](#interface-and-trait-names-are-swapped) — `Has*` matches the house trait prefix (`HasComponents`, `HasKey`, `HasGap`, …). |
| `Filament\Schemas\Contracts\HasSchemas` | `InteractsWithSchemas` | interface | `Sylament\Schemas\Contracts\InteractsWithSchemas` | [Swapped](#interface-and-trait-names-are-swapped) — the interface names the behavior. |
| `Filament\Tables\Concerns\InteractsWithTable` | `HasTable` | trait | `Sylament\Tables\Concerns\HasTable` | [Swapped](#interface-and-trait-names-are-swapped) — `Has*` matches the house trait prefix (`HasRecords`, `HasEmptyState`, …). |
| `Filament\Tables\Contracts\HasTable` | `InteractsWithTable` | interface | `Sylament\Tables\Contracts\InteractsWithTable` | [Swapped](#interface-and-trait-names-are-swapped) — the interface names the behavior the host implements. |
| `afterCreate()` / `afterUpdate()` / `afterDelete()` (`Repeater`'s relationship surface) | — not ported | method | — | Filament fires them around the per-item `save()`/`delete()` its relationship save makes. The port's [single-flush shape](schemas/belongs-to-record.md#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk) has no such moment — `getData()` mutates an object graph and returns, so nothing has reached the database and no new item has an identifier yet, and firing them there would be a lie about when they run. Doctrine's own lifecycle events (`postPersist` / `postUpdate` / `postRemove`, or an `onFlush` subscriber) are the seam instead. A real capability gap, recorded as one: [repeater.md](forms/repeater.md#the-relationship-family-loses-its-caches-its-lifecycle-hooks-and-its-query-scope). |
| `dehydrated(false)`, applied by `relationship()` (`Repeater`, `EntanglesStateWithSingularRelationship`) | — not applied | behaviour | — | Filament prunes a relationship component's state from the dehydrated bag, because its record save would otherwise try to write an array into a relation. Here the dehydration walk is what **routes** the value: `getData()` consults the save hook only for a path the bag still carries, so pruning would switch the feature off rather than protect it. The state stays in the bag and the hook takes it — [repeater.md](forms/repeater.md#the-relationship-family-loses-its-caches-its-lifecycle-hooks-and-its-query-scope), [entangles](schemas/entangles-state-with-singular-relationship.md#subtree-ownership-not-dehydratedfalse-is-what-makes-the-hooks-fire), where subtree ownership does the job the flag did. |
| `getExtraFieldWrapperAttributesBag()` (`HasExtraFieldWrapperAttributes`) | `getExtraFieldWrapperAttributeBag()` | method | `Sylament\Forms\Components\Concerns\HasExtraFieldWrapperAttributes` | Singularized to Filament's own majority spelling: thirteen of their fifteen `getExtra…Bag()` getters are `…AttributeBag` (`getExtraAttributeBag()`, `getExtraInputAttributeBag()`, `getExtraCellAttributeBag()`, …) and only this one and its infolists twin `getExtraEntryWrapperAttributesBag()` pluralize. A migrating user typing the form they use everywhere else lands on the right name. |
| `getModel()` (`BelongsToModel`, both tiers) | `getRecordClass()` | method | both tiers of `…\BelongsToRecord` | [Record vocabulary](#the-model-vocabulary-collapses-onto-record). |
| `getModel()` (`Table\Concerns\HasRecords`) | `getRecordClass()` | method | `Sylament\Tables\Table\Concerns\HasRecords` | [Record vocabulary](#the-model-vocabulary-collapses-onto-record) — the same rename on the tables root object, which reads the class off the declared query's root entity rather than off an Eloquent builder's model instance ([table.md](tables/table.md#the-record-class-is-read-off-the-base-querys-root-entity)). |
| `getModelInstance()` (`BelongsToModel`, both tiers) | `getRecordInstance()` | method | both tiers of `…\BelongsToRecord` | [Record vocabulary](#the-model-vocabulary-collapses-onto-record). |
| `labelTag` (`Field::wrapEmbeddedHtml()` parameter) | `labelAsHeading` | template prop | the `FieldWrapper` template (`Sylament\Forms`) | Narrowed to a boolean: Filament's tag-name string is only ever `'label'` or `'div'`, and its own template branches on `$labelTag === 'label'` to pick `for="…"` against `id="…-label"` — the prop carries that bit instead of a tag name interpolated into markup ([field.md](forms/field.md#labeltag-is-a-boolean-labelasheading)). |
| `model()` (`BelongsToModel`, both tiers) | `record()` | method | both tiers of `…\BelongsToRecord` | [Record vocabulary](#the-model-vocabulary-collapses-onto-record) — absorbed by `record()`, which widens to accept the class-string payload (declares the class, binds no instance). |
| `$model` (closure parameter) | — not ported | closure param | — | [Record vocabulary](#the-model-vocabulary-collapses-onto-record) — `$record` and `getRecordClass()` cover it; revisit only if a ported surface needs the class-string injected. |
| `mountedActions.{i}.data` (state path) | `mountedActionsData.{i}` | state path | `Sylament\Actions\Concerns\HasActions` | Symfony's property accessor resolves the first path segment through the same-named getter, so the nested form is unreadable and unwritable — see [has-actions.md](actions/has-actions.md#per-mount-form-state-lives-in-a-sibling-bag-not-in-the-stack-descriptor). |
| `notifications::notification.*` (the five `NotificationsIconAlias` values) | `notification.*` | icon-alias value | `Sylament\Notifications\Twig\NotificationsIconAlias` | The same de-prefixing as `tables::empty-state` below, for the same reason: Sylament's alias values are the bare `<component>.<part>` form throughout, so an app registering overrides under [`sylament.icons`](support/icon-manager.md) reads one scheme rather than two. All five shift — `close-button`, `danger`, `info`, `success`, `warning` — and the **constant** names are untouched. [Written up in notifications-icon-alias.md](notifications/notifications-icon-alias.md#the-alias-values-drop-filaments-package-prefix). |
| `$relatedModel` (`EntanglesStateWithSingularRelationship::relationship()`, third argument) | — not ported | method param | — | Eloquent needs it: a relation object is the only place the related class can be read from, and a morph relation cannot answer it at all. Doctrine's `ClassMetadata` answers directly — [`AssociationReader::describe()`](schemas/association-reader.md) returns the target class with the rest of the mapping — so the argument could only ever let a caller contradict the mapping. `relationship()` takes the name and the condition; the target comes off the mapping. [Why](schemas/entangles-state-with-singular-relationship.md#the-relatedmodel-argument-is-dropped-the-mapping-names-the-target). |
| `rule()` / `rules()` (forms `CanBeValidated`) | `constraint()` / `constraints()` | method | `Sylament\Forms\Components\Concerns\CanBeValidated` | [Constraint vocabulary](#laravel-rule-vocabulary-becomes-symfony-constraint-vocabulary) — the payload is Symfony `Constraint` objects, not Laravel rules; the rename makes the migration break loud and greppable instead of grep-hit-then-type-error. |
| `saveRelationships()` / `loadStateFromRelationships()` (both tiers, plus `saveRelationshipsBeforeChildren()` and `castStateAfterLoadingFromRelationships()`) | — not ported | method | — | The whole third pass is collapsed, not renamed: Filament walks the component tree twice on an insert to run per-component relationship callbacks, because Eloquent needs the parent row inserted before a child row can carry its foreign key. Doctrine's `UnitOfWork` sorts the INSERTs itself at one `flush()`, so the ordering the walk exists to impose is re-derived anyway. The per-component hooks survive under Filament's own `…Using` names and are [consulted by the two mapping edges](schemas/belongs-to-record.md#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk) — `fill()` and `getData()` already walk the record path map — which also leaves the `exists` guard and the post-load re-cast with nothing to do. |
| `schema()` (`HasChildComponents` / root `HasComponents`) | — not ported | method | — | `components()` is the sole spelling (decided 2026-08-04). Filament ships the alias undeprecated as back-compat for its own pre-v4 call sites, which this port has none of; a migrating `->schema([...])` breaks loudly into a mechanical fix — [component.md](schemas/component.md#the-schema-alias-is-not-ported). |
| `tables::empty-state` (`TablesIconAlias::EMPTY_STATE` value) | `table.empty-state` | icon-alias value | `Sylament\Tables\Twig\TablesIconAlias` | Filament is inconsistent about the prefix: `SupportIconAlias`'s values are bare (`modal.close-button`, `section.collapse-button`) while every other package namespaces its own (`tables::`, `actions::`, `schema::`). The bare `<component>.<part>` form is the one Sylament follows everywhere, so an app registering overrides under [`sylament.icons`](support/icon-manager.md) reads one scheme rather than two. The **constant** name is untouched — a user reaching the alias as `TablesIconAlias::EMPTY_STATE`, which is how both codebases write it, never meets the difference. [Written up in tables-bundle.md](tables/tables-bundle.md#the-empty-state-alias-value-drops-filaments-package-prefix). |
| `validationMessages()` keys (`required`, `max`, `email`, …) | `class-string<Constraint>` keys (`NotBlank::class`, `Length::class`, …) | array keys | `Sylament\Forms\Components\Concerns\CanBeValidated` | [Constraint vocabulary](#laravel-rule-vocabulary-becomes-symfony-constraint-vocabulary) — the rule names have nothing behind them in Symfony, where the message is a property on the constraint; class keys are IDE-completable and let one post-pass message any constraint, including escape-hatch ones ([forms/can-be-validated.md](forms/can-be-validated.md#validation-messages-apply-after-the-constraints-are-built)). |
| `Section::formBefore()` / `isFormBefore()` | `contentBefore()` / `hasContentBefore()` | method | `Sylament\Schemas\Components\Concerns\HasContentBefore` | Adopts the `contentBefore` prop name Filament's own support-layer section component uses. |
| `ButtonComponent::make(isOutlined: true)` | `OutlinedButtonComponent` | class | `Sylament\Support\Colors\Components\OutlinedButtonComponent` | The outlined variant is a subclass, not constructor state — Twig cannot call a static factory and `colorAttributes()` takes a class-string. [Why, and what it buys](support/color-policies.md#the-outlined-button-is-a-subclass-not-a-constructor-flag). |

### Interface and trait names are swapped

⚠️ **Grep trap.** For schemas, actions and tables alike, `Has*` and
`InteractsWith*` exist on **both** sides with **opposite** meanings:

| | Filament | Sylament |
| --- | --- | --- |
| **interface** | `HasSchemas` / `HasActions` / `HasTable` | `InteractsWithSchemas` / `InteractsWithActions` / `InteractsWithTable` |
| **trait** | `InteractsWithSchemas` / `InteractsWithActions` / `InteractsWithTable` | `HasSchemas` / `HasActions` / `HasTable` |

Here `Has*` is reserved for traits, matching every other trait in the codebase,
and the interface names the behavior the host implements. Porting code that
mentions either name means checking which one it is first.

### Laravel rule vocabulary becomes Symfony constraint vocabulary

The validation payload changed ecosystems — Laravel rule strings and rule
objects became Symfony `Constraint` instances (decided 2026-07-29, M1 · 1) —
and the API vocabulary follows it: a migrating user's
`->rules('required|max:255')` cannot work under any name, and a missing method
is a louder, more searchable break than a kept name that greps clean and then
type-errors. Names derived from the same vocabulary translate systematically
and carry no table rows of their own: `getValidationRules()` →
`getValidationConstraints()` (field tier and `Schema` alike),
`dehydrateValidationRules()` → `dehydrateValidationConstraints()`,
`getLengthValidationRules()` → `getLengthValidationConstraints()`. Names that
are not rule vocabulary keep Filament's spelling: `required()`, `nullable()`,
`validationMessages()` (the method name — its *keys* are re-vocabularied, row
above), `validate()`.

The message keys move with the payload. A rule name was the only handle Laravel
gave a message, so Filament keys `validationMessages()` by it; Symfony puts the
message on the constraint object, so the constraint class is the handle and the
option names are its own properties. `Length`'s three keys (`size`, `max`, `min`)
collapse onto one class with an option map, and `Toggle`'s `required` becomes
`NotNull::class` rather than `NotBlank::class` because that is the constraint it
emits.

### The model vocabulary collapses onto record

Decided with Martin, 2026-07-30 (M1 · 2). Filament itself is inconsistent
here: root-tier `record()` aliases `model()` onto one last-write-wins property
with divergent payload types, the component tier ships `model()` but no
`record()`, and the rest of Filament — panels (`EditRecord`, `getRecord()`),
tables, actions — speaks "record". Sylament collapses onto Filament's own more
prevalent name, which also removes a real trap
(`->record($x)->model(Y::class)` looks additive but silently unbinds the
instance) and converges with actions' record-only `InteractsWithRecord`.
"Model" is additionally an Eloquent concept-name with no Doctrine referent —
the concept-name goes with its ecosystem, the constraint-vocabulary precedent.
Migration cost, accepted: `->model(...)` and `fn ($model)` break loudly
(missing method / unresolvable parameter) instead of grepping clean. Kept
names: `record()`, `getRecord()`. Full rationale and surface:
[belongs-to-record.md](schemas/belongs-to-record.md).

## Not renames

Systematic translations that follow the conventions in
[CLAUDE.md](../../CLAUDE.md). They are **not** renames and must not be added to
the table above.

### Idiom

| Laravel / Filament | Sylament | Example |
| --- | --- | --- |
| `Htmlable` | `Twig\Markup` | — |
| Livewire | UX LiveComponent | `getLivewire()` → `getLiveComponent()` |
| Livewire's `WithPagination` trait (`gotoPage()`, `nextPage()`, `previousPage()`, `resetPage()`, the query-string page name) | a `#[LiveProp] int $tablePage` on the host and one `#[LiveAction]` that moves it | `gotoPage($page, $pageName)` → [`gotoTablePage(page)`](tables/has-table.md#gototablepage-replaces-livewires-paginator-trio) |
| Eloquent | Doctrine | — |
| Eloquent `Builder` | Doctrine `QueryBuilder` | `query(Builder $query)` → [`query(QueryBuilder $query)`](tables/table.md#every-read-clones-a-memoised-base-query); `modifyQueryUsing(fn (Builder $query) => …)` → `fn (QueryBuilder $query) => …`. Both are the query object each ORM composes a select in, so the parameter and property types translate wholesale wherever a Filament signature names `Builder`. The two are not interchangeable in shape — Doctrine's carries its `EntityManagerInterface` and a class-string FROM clause where Eloquent's carries a model instance — which is why `getQueryEntityManager()` below has no Filament counterpart and why `getRecordClass()` reads a root entity where `getModel()` read an instance. |
| Blade view | Twig template | `view()` → `template()`, `viewData()` → `templateData()` |
| Blade component scope inheritance (`$this` is the Livewire component) | an explicit host — resolved from the Twig component stack, or passed as a prop | `<x-filament-actions::modals />` → `<twig:SylamentActions:Modals />` (see [has-actions.md](actions/has-actions.md#the-modals-renderer-resolves-its-host-from-the-render-stack-not-from-template-scope)) |
| Alpine.js | Stimulus | `x-data` / `x-on:` → a controller under `packages/*/assets/src/controllers/`, wired with StimulusBundle's Twig helpers |
| `fi-*` classes | `sy-*` classes | `fi-icon-btn` → `sy-icon-btn`, `fi-disabled` → `sy-disabled` |

CSS class names are not a flat prefix swap: bundles above support insert a
package code (`sy-fo-input`, `sy-sc-grid`), support-level classes carry none
(`sy-icon-btn`), and every template root also gets the bare `sy` marker class.
Full contract: [`docs/styling.md`](../styling.md).

The same `fi-` → `sy-` translation covers **CSS custom properties**, and there it
is load-bearing rather than cosmetic. Filament's colour variables are unprefixed
— `--primary-600`, `--color-600`, `--text`, `--dark-hover-bg` — and Sylament's
are `--sy-primary-600`, `--sy-color-600`, `--sy-text`, `--sy-dark-hover-bg`,
whether they are written in a stylesheet, emitted inline by
`ColorManager::getComponentCustomStyles()`, or emitted into a `<style>` element
by `sylamentStyles()`. Custom properties inherit and Sylament embeds into a page
it does not own, so one `:root { --text: … }` in the host app would otherwise
repaint every Sylament component. The exception is the Filament-*verbatim* grid
variables (`--cols-lg`, `--col-span-default`), which stay unprefixed on purpose —
[styling.md rule 4](../styling.md) draws that line. Measured porting cost of the
prefix, and the reasoning:
[color-manager.md](support/color-manager.md#every-emitted-custom-property-carries-the---sy--prefix).

### Namespace segments

Segments carry over from Filament **verbatim** — `Components\`, `Enums\`,
`Exceptions\`, `Concerns\`, `Contracts\`, `Icons\` and the rest all keep
Filament's spelling, so `Filament\Support\Components\Concerns\` is
`Sylament\Support\Components\Concerns\`. Exactly one segment is translated, and
only because the thing it names was replaced:

| Filament | Sylament | Why |
| --- | --- | --- |
| `View\` | `Twig\` | The Blade → Twig idiom — the template engine itself changed. Laravel's `ComponentAttributeBag` and Filament's `SupportIconAlias` both live in `Sylament\Support\Twig\`. |

No segment is renamed for style. Symfony's own habit of singular `Exception\`
(its components and bundles both use it) is **not** followed here: matching
Filament costs a migrating user nothing to learn, and one carve-out is worse
than none.

Three namespaces **leave `Components\`**, because what they hold is not part of
the component tier — neither a component, nor a concern or contract of one. Two
of them land under `State\` rather than at the root, filing beside the
[`PropertyAccessor`](schemas/property-accessor.md) they read and write through;
each drops the segment that `State\` now says for it (`StateCasts` → `Casts`,
`Utilities` → nothing, since the word named no category):

| Filament | Sylament | Holds |
| --- | --- | --- |
| `Filament\Schemas\Components\StateCasts\` | `Sylament\Schemas\State\Casts\` | `StateCast` and its implementations — value objects that transform state |
| `Filament\Schemas\Components\Utilities\` | `Sylament\Schemas\State\` | `Get` / `Set` — the closure-injected state accessors |
| `Filament\Support\Components\ComponentManager` | `Sylament\Support\Configuration\ComponentManager` | The `configureUsing()` registry — an engine, filed by capability like `Evaluation\Invoker`, not beside what it configures |

A migrating user importing `Filament\Schemas\Components\Utilities\Get` will
not find it by prefix swap alone, which is why these are recorded here rather
than left to the verbatim rule.

The colour tier leaves `View\` for the same reason, and is the one place the
`View\` → `Twig\` translation above is deliberately **not** applied:

| Filament | Sylament | Holds |
| --- | --- | --- |
| `Filament\Support\View\Components\` (the policy objects) | `Sylament\Support\Colors\Components\` | `BadgeComponent`, `ButtonComponent`, … — the per-element contrast policies |
| `Filament\Support\View\Components\ColorMaps\` | `Sylament\Support\Colors\Maps\` | `ComponentColorMap` and the two specialised resolvers |
| `Filament\Support\View\Components\Contracts\` | `Sylament\Support\Colors\Contracts\` | `HasColor` (the `getColorMap()` one) and `HasDefaultGrayColor` |
| `Filament\Schemas\View\Components\` | `Sylament\Schemas\Colors\Components\` | schemas' `TextComponent` and `IconComponent` |
| `Filament\Tables\View\Components\Columns\` | `Sylament\Tables\Colors\Components\` | tables' `TextColumnComponent\ItemComponent` and `TextColumnComponent\ItemComponent\IconComponent` |

`Sylament\Support\Twig\Components\` — where the translation would have put
them — already means a class-backed **renderable** Twig component, the shape
actions' `Modals` has. A colour policy is never rendered and has no template, so
it is filed by capability at `src/` level like `Evaluation\Invoker` and
`Configuration\ComponentManager`. Class names, including the nesting
(`DropdownComponent\ItemComponent`, `TextColumnComponent\ItemComponent\IconComponent`),
are Filament-verbatim to any depth; only the leading segments move. Detail:
[color-policies.md](support/color-policies.md#the-tier-is-filed-under-colors-not-under-twigcomponents).

The tables row absorbs one more segment than the others, and that is the whole of
the difference: Filament groups its tables policies under a `Columns\` directory
which is the **only** child `View\Components\` has there, so it separates nothing
from anything and is dropped into the leading segments rather than carried.
Everything below it is untouched — an icon policy that belongs to the text
column's item is `TextColumnComponent\ItemComponent\IconComponent` here exactly as
it is there, the same nesting support's still-blocked
[`DropdownComponent\ItemComponent\IconComponent`](support/color-policies.md#surface)
already carries. The nesting is a class-string a consumer types into
`colorAttributes()`, so a flatter spelling would be a rename in everything but
name.

Support-level traits split by **who composes them**, so
`Filament\Support\Concerns\` has two destinations:

| Filament | Sylament | Which traits |
| --- | --- | --- |
| `Filament\Support\Concerns\` | `Sylament\Support\Components\Concerns\` | Everything only a component composes — `HasIcon`, `HasColor`, `HasExtraAttributes`, … and `Configurable`, whose sole consumer is `Component` |
| `Filament\Support\Concerns\` | `Sylament\Support\Concerns\` | `EvaluatesClosures` alone |

`EvaluatesClosures` stays at root against that rule for a reason that is
checkable in Filament rather than a matter of taste: of its three direct
importers there, two — `Colors\ColorManager` and `TimezoneManager` — are plain
manager classes, not components. That is no longer a prediction:
[`Sylament\Support\Colors\ColorManager`](../../packages/support/src/Colors/ColorManager.php)
composes the trait today, to evaluate a `Closure` palette registration, and it is
not a component — so a trait under `Support\Components\Concerns\` would be the
wrong import. The hold stands on a real consumer now rather than on Filament's,
and moves only if that consumer goes away.

One trait moves **down a package** for the same who-composes-it reason, except
that here the layering forces it rather than merely suggesting it:

| Filament | Sylament | Which trait |
| --- | --- | --- |
| `Filament\Forms\Components\Concerns\` | `Sylament\Schemas\Components\Concerns\` | `CanBeMarkedAsRequired` alone |

Filament hosts the trait in forms yet imports it from schemas'
`Fieldset` (`vendor/filament/schemas/src/Components/Fieldset.php`) — an upward
import from schemas into forms. Here `forms` depends on `schemas`, so that
direction cannot be reproduced, and the trait has to sit at or below the lower
package for both consumers to reach it. Nor is that a style question: the CI
split publishes each package standalone, so a package leaning on a symbol only a
sibling declares breaks the moment it is installed on its own. See
[schemas/can-be-marked-as-required.md](schemas/can-be-marked-as-required.md).
The class name, methods and body are unchanged, which is why this is a
relocation rather than a rename.

### Names with no Filament source

Methods, classes and events that exist only here, because a port needed behaviour
Filament has no member for. Nothing to rename from, so they are listed rather than
mapped — and listed at all so a migrating user who greps Filament for the name and
finds nothing knows the absence is real.

| Sylament | On | Why it is not a rename |
| --- | --- | --- |
| [`nullWhenBlank()`](schemas/belongs-to-record.md#a-declared-branch-is-nulled-whole-before-its-leaves-are-written) | `Sylament\Schemas\Components\Concerns\HasNullableBranch` | Declares a container an optional branch of the record graph, so a blank one is written as `null` instead of being built empty. The nearest Filament member is `relationship(condition:)`, and it is not the same call under another name: its **polarity is inverted** (`condition:` true means *save*, this means *discard*), it carries no built-in blank test — a Filament user hand-writes the emptiness check at every call site — and it is scoped to an Eloquent relationship where this is scoped to a declared state path. Renaming `condition:` onto it would map a Filament call to a Sylament call that does the opposite. |
| [`isBlankStateValue()`](schemas/belongs-to-record.md#the-built-in-blank-test-asks-the-branchs-own-leaves) | `Sylament\Schemas\Components\Concerns\HasNullableBranch`, overridden on `Checkbox` / `Toggle` | The blank test each declared leaf answers for the branch pass, so a field whose cast never dehydrates null (an untouched checkbox rests at `false`) can still read as blank. Filament has no branch pass at all — `nullWhenBlank()` above is Sylament-only — so there is nothing for the predicate it consults to be renamed from. |
| [`ownsRecordSubtree()`](schemas/belongs-to-record.md#a-component-can-own-its-whole-record-subtree) | `Sylament\Schemas\Components\Concerns\HasChildComponents` | Marks a component whose own dehydrated value is the record value for every path beneath it, so the record path map treats it as a leaf and drops the paths its descendants declare. The map is Sylament-only — Filament maps by wholesale array assignment on the record, so it never had to ask which component speaks for a path and has no member to rename from. |
| [`itemsAs()`](forms/repeater.md#the-item-cast-fires-at-the-repeaters-two-pipeline-boundaries-not-in-the-generic-seam) | `Sylament\Forms\Components\Repeater` | Declares the item class a repeater's items round-trip as, so the record property holds identity-free value objects while the live bag keeps arrays. Filament's nearest member is `relationship()`, and mapping one onto the other would promise semantics this deliberately excludes: `relationship()` is identity-keyed Eloquent mapping with the responsibilities inverted — it maps item state onto *managed records*, deciding which row is which entity and what happens to the ones that vanished, where this constructs a fresh value object per item every save and never asks. Identity is [`relationship()`'s](forms/repeater.md#a-relationship-repeater-keys-its-items-by-identity-and-diffs-the-collection), which shipped with the Doctrine relation layer and is [exclusive with `itemsAs()`](forms/repeater.md#the-relationship-family-loses-its-caches-its-lifecycle-hooks-and-its-query-scope) in either declaration order. |
| [`ObjectStateCast`](schemas/object-state-cast.md) | `Sylament\Schemas\State\Casts` | The per-value transform behind `itemsAs()`: an array's keys in, a fresh instance of a declared class out, and back. Filament ships no object-constructing cast anywhere in `Components/StateCasts/` — its answer to object-shaped repeater state is `relationship()`, per the row above — so the class name follows the shipped cast family (`BooleanStateCast`, `OptionStateCast`) rather than renaming anything. |
| [`Paginator`](support/pagination.md) and its counts — `getRecords()`, `getTotalRecordCount()`, `getPage()`, `getRecordsPerPage()`, `getLastPage()`, `getFirstRecordNumber()`, `getLastRecordNumber()`, `getPageWindow()`, `withRecords()` | `Sylament\Support\Pagination` | The carrier of one page of records. Filament has no class to rename from — it returns Laravel's `LengthAwarePaginator`, a framework type with no Symfony equivalent, so the class is first-party ([why, against principle §2](support/pagination.md#the-carrier-is-first-party-rather-than-an-ecosystem-package)) and its getters are [named in full](support/pagination.md#the-counts-are-named-in-full-not-abbreviated) rather than carrying Laravel's `total()` / `perPage()` / `firstItem()` spellings into a codebase whose vocabulary is *record*. |
| [`getPaginator()`](tables/table.md#getrecords-unwraps-the-paginator-and-getpaginator-is-the-only-seam-to-it) / `getTablePaginator()` | `Sylament\Tables\Table\Concerns\HasRecords`, `Sylament\Tables\Concerns\HasTable` | The seam to the carrier above. Filament needs neither: its `getRecords()` **is** the paginator, so the page and the counts come off one object. Here `getRecords()` always answers the keyed array, and a second member carries the counts — a split, not a rename. |
| [`getQueryEntityManager()`](tables/table.md#every-read-clones-a-memoised-base-query) | `Sylament\Tables\Table\Concerns\HasQuery` | The entity manager behind the declared query. Filament needs no member: an Eloquent builder carries its own connection, and every consumer that needs more reaches the model class statically. Doctrine's read side takes an `EntityManagerInterface` explicitly, so the seam has to exist — and it reads off the base query rather than being injected, which keeps a `Table` free of a container dependency it would carry only for this. |
| [`getSearchDebounceMilliseconds()`](tables/table.md#getsearchdebouncemilliseconds-is-a-sylament-only-translation-of-filaments-duration-string) | `Sylament\Tables\Table\Concerns\CanSearchRecords` | The debounce as ux-live-component's `debounce(N)` modifier wants it. `searchDebounce()` / `getSearchDebounce()` are ported unchanged and still carry Filament's duration string (`'500ms'`); Livewire parses that string itself, where the UX modifier takes milliseconds only, so the translation needs a member and there is nothing to rename it from. |
| [`SessionNotifications`](notifications/notifications-bundle.md#the-flash-bag-replaces-laravels-session-push-and-owns-the-key) — `push()` / `pull()`, `FLASH_KEY` | `Sylament\Notifications\Delivery` | The store a pending notification waits in between `send()` and render. Filament needs no class: `send()` calls `session()->push('filament.notifications', …)` and the provider's hook calls `session()->pull(…)`, so the key is a literal repeated in two files and there is nothing to rename from. The port has one owner for the key, three consumers, a Symfony **flash bag** rather than a plain session array (read-once is the bag's own semantics, not a convention), and two type guards on the way out — session contents are attacker-adjacent input by the time they are read. |
| [`LiveDeliverySubscriber`](notifications/notifications-bundle.md#the-live-delivery-decision-point-is-a-prerenderevent-subscriber-not-a-dehydrate-hook) | `Sylament\Notifications\Delivery` | The live-request delivery arm. Filament's counterpart is not a class but a closure — the `on('dehydrate', …)` hook registered inline in `NotificationsServiceProvider::packageBooted()` — so there is no name to carry over. The Symfony shape has to be a service (an `EventSubscriberInterface` on ux-twig-component's `PreRenderEvent`, at a priority above the subscriber that drains `LiveResponder`'s queues), and the name says which of the two delivery arms it is. |
| `sylament:notification` (browser event) | dispatched by `Sylament\Notifications\Delivery\LiveDeliverySubscriber`, consumed by the stack's Stimulus controller | Not a rename of Filament's `notificationsSent` / `notificationSent` / `notificationClosed`, all three of which are **Livewire** events with different jobs: `notificationsSent` tells the stack to fire a second request and re-read the session, `notificationSent` carries a notification as *data* from the client-side builder, and `notificationClosed` tells the server to forget one. This event travels the other way and carries **rendered HTML** on the response already in flight, so the client appends markup instead of asking for it — [why](notifications/notifications.md#live-delivery-appends-server-rendered-html-instead-of-re-requesting-the-stack). The `sylament` prefix matches actions' `sylament-open-modal` / `sylament-close-modal`; the separator is a colon rather than a hyphen, the conventional namespaced-DOM-event form a Stimulus action descriptor reads (`sylament:notification@window->…#receive`). |
| [`renderTable()`](tables/tables-bundle.md#the-bundle-registers-a-rendertable-twig-extension-which-filament-has-no-counterpart-for) | `Sylament\Tables\Twig\Extension\TableExtension` | Renders the context host's table from a Twig template. Filament needs no member to rename from: its `ViewComponent` implements `Htmlable`, so Blade renders a table by echoing it, where Twig needs an explicit `Markup` conversion — the same gap that already produced schemas' `renderSchema(name)` and actions' `renderAction(name, arguments)`, both equally Sylament-only and neither a rename either. `renderTable()` takes no name argument, unlike `renderSchema()`: a host has exactly one table. |
| [`getFormattedState()`](tables/text-column.md#getformattedstate-replaces-the-embedded-views-local-preamble) | `Sylament\Tables\Columns\Concerns\CanFormatState` | `formatState(getState())` under a name — the one-getter translation of `toEmbeddedHtml()`'s local-variable preamble, which a Twig template has no place to hold ([why](tables/text-column.md#the-cell-renders-a-twig-partial-where-filament-emits-an-embedded-view)). Filament computes the same pair as two local variables and never names the composition; there is nothing to rename from. |
| [`getStateItems()`](tables/text-column.md#getstateitems-is-the-cells-value-list) | `Sylament\Tables\Columns\TextColumn` | The cell's value list a Twig template loops over — one entry per badge, or one already-joined entry when a multi-value state collapses without one. Filament builds the equivalent per-item array inline inside `toEmbeddedHtml()` and never returns it to a caller; there is no method to rename from, only a PHP-side loop this port had to give a name because Twig cannot inline one. |
| The `get*Class()` family — [`getSizeClass()`](tables/text-column.md#getsizeclass-is-sylament-only-and-the-partial-resolves-the-size-twice), `getAlignmentClass()`, `getWeightClass()`, `getFontFamilyClass()`, and schemas' own `Text::getSizeClass()` / forms' `Repeater::getAddActionAlignmentClass()` | `Sylament\Tables\Columns\TextColumn`, `Sylament\Support\Components\Concerns\HasAlignment` / `HasWeight` / `HasFontFamily`, `Sylament\Schemas\Components\Text`, `Sylament\Forms\Components\Repeater` | One pattern, not six independent names: a getter beside `getX()` that turns the enum-or-string union `getX()` answers into the CSS class fragment a template emits (`sy-size-lg`, `sy-align-center`, `sy-font-bold`), passing a custom string through untouched. Filament has no counterpart, because its embedded views hold the resolved value in a local and interpolate `"fi-size-{$size->value}"` at the point of use, which a Twig template has no place to do without type-sniffing the union in markup. The name shape does exist over there and means something else entirely, which is the trap worth naming: `grep -rn "function get[A-Za-z]*Class()" vendor/filament/*/src` finds four — `getPageClass()` on `RelationGroup`, `RelationManager` and `ManageRelatedRecords`, and `getCsrfMiddlewareClass()` on `PanelProviderClassGenerator` — and every one returns a **PHP class-string**, never a CSS class fragment. So a migrating user grepping the suffix lands on the panel tier, not on this. So the family is the [template-side counterpart](tables/text-column.md#the-cell-renders-a-twig-partial-where-filament-emits-an-embedded-view) of the same preamble `getFormattedState()` translates, and new members follow the pattern rather than earning rows of their own. |

`RecordTypeReader`, `RecordTarget` and `HasNullableBranch` are likewise
Sylament-only classes rather than renamed Filament ones; the reason none of them
has a Filament counterpart is structural and is recorded in
[record-type-reader.md](schemas/record-type-reader.md) — Eloquent attributes are
untyped, so the write-side type question Filament's mapper would have to answer
cannot be asked there.

### Namespaces with no Filament source

Segments that exist only here, because a port needed classes Filament does not
have. Nothing to rename from, so they are listed rather than mapped.

| Sylament | Holds | Why it exists |
| --- | --- | --- |
| `Sylament\Support\Pagination\` | `Paginator` | Filament carries a page of records in Laravel's `LengthAwarePaginator`, so it needs no namespace of its own; the port has to supply the carrier, and it is filed by capability at `src/` level like every other engine. See [pagination.md](support/pagination.md). |
| `Sylament\Notifications\Delivery\` | `SessionNotifications`, `LiveDeliverySubscriber` | Filament's two delivery mechanics are a session literal inside `Notification::send()` and a closure inside `NotificationsServiceProvider`, so neither has a home to rename. Both are services here, and they are filed by capability at `src/` level like every other engine rather than under `Concerns/` with the trait they serve. See [notifications-bundle.md](notifications/notifications-bundle.md). |
| `Sylament\Support\Evaluation\` | `Invoker`, `ClosureParameter` | Filament inlines the closure-resolution loop in `EvaluatesClosures` and falls back to `app()->make()`; Symfony's container cannot, so the loop is extracted into a container-aware `Invoker`, and `ClosureParameter` replaces Filament's `[$value]`-vs-`[]` array wrap. See [component.md](support/component.md#closure-resolution-lives-in-a-service). |

## Divergences

Deliberate deviations that are **global** rather than component-local —
permanent, and the reason they hold. A divergence constraining one component
lives in that component's file, per
[the placement rule](README.md#divergences-are-addressable); what earns a heading
here is a decision every ported component inherits. The first two are the
`make()` mechanics; the rest came with the colour registry, and each states the
decision and links the write-up rather than repeating it.

### `make()` arguments move from constructors to setters

Filament passes `make()` arguments through `app(static::class, ['name' => $name])`,
letting the container fill a constructor parameter positionally. Symfony's
container cannot pass constructor arguments through a service locator lookup,
so `make()` resolves the instance first — through
[`Component::newInstance()`](../../packages/support/src/Components/Component.php),
see [component.md](support/component.md#newinstance-routes-every-make-through-a-locator)
— and applies the value via the existing setter instead. Behaviour is
identical either way: the setter still runs before `configure()`/`setUp()`.

### The `final` constructors are deleted

Filament seals `Action::__construct` (and its siblings) `final`; Sylament
removes the constructor entirely so a user subclass can declare its own
constructor dependencies, autowired through
[`ComponentResolver`](support/component.md#newinstance-routes-every-make-through-a-locator).
This is the entire point of the mechanism.

### The shipped stylesheet keeps a palette floor, so nothing has to be called

Filament's compiled `theme.css` defines **no** palette values — every colour on
a Filament page comes from the `@filamentStyles` directive at runtime. Sylament
keeps the six default palettes in `index.css`, and
`ColorExtension::sylamentStyles()` emits only what a consumer registered on top,
returning an empty string when that is nothing.

Filament can require the directive because its panel owns the page layout and its
scaffolding generates one containing it. Sylament has neither and embeds into an
app it does not control, where a forgotten call would render every component
colourless with no error. The cost is that the six palettes exist in both PHP and
CSS, which
[`CssPaletteFloorTest`](../../packages/support/tests/Architecture/CssPaletteFloorTest.php)
exists to make safe. Detail:
[color-manager.md](support/color-manager.md#sylamentstyles-emits-only-the-delta-over-the-shipped-css-floor).

### `info` defaults to Indigo, and the default palettes were chosen by measurement

`ColorManager::DEFAULT_COLORS` is Filament's, with one entry changed: `info` is
Indigo where Filament uses Blue. Blue fails the filled button's hover state in
both light and dark mode at 3.74:1; Indigo clears all four states. `primary`
stays Filament's Amber — which also clears all four — and, as in Filament,
`warning` is Amber too, so the two render identically.

This is a global divergence rather than a button-local one because every
component measures against these palettes. Full measurements, including the two
danger states that still fail and why they are pinned rather than fixed:
[color-manager.md](support/color-manager.md#info-defaults-to-indigo-where-filament-uses-blue).

### A palette registered shade by shade must fill the shade grid exactly

Filament accepts any palette shape and fails later — silently for an empty one,
with a crash inside a policy for a sparse one, and invisibly for a shade off the
grid, which emits a class no stylesheet defines. Sylament checks every explicit
palette against `ColorManager::SHADES` on the way in and throws an
`InvalidArgumentException` naming the palette and what is wrong, whether the
palette came from `sylament.colors`, from `register()`, or inline from
`->color([...])`. The corollary is that Filament's int-valued **shade aliases**
(`950 => 900`) are not accepted. Detail:
[color-manager.md](support/color-manager.md#a-registered-palette-must-fill-the-shade-grid-exactly).
