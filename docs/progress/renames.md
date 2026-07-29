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
| `getModel()` (`BelongsToModel`, both tiers) | `getRecordClass()` | method | both tiers of `…\BelongsToRecord` | [Record vocabulary](#the-model-vocabulary-collapses-onto-record). |
| `getModelInstance()` (`BelongsToModel`, both tiers) | `getRecordInstance()` | method | both tiers of `…\BelongsToRecord` | [Record vocabulary](#the-model-vocabulary-collapses-onto-record). |
| `model()` (`BelongsToModel`, both tiers) | `record()` | method | both tiers of `…\BelongsToRecord` | [Record vocabulary](#the-model-vocabulary-collapses-onto-record) — absorbed by `record()`, which widens to accept the class-string payload (declares the class, binds no instance). |
| `$model` (closure parameter) | — not ported | closure param | — | [Record vocabulary](#the-model-vocabulary-collapses-onto-record) — `$record` and `getRecordClass()` cover it; revisit only if a ported surface needs the class-string injected. |
| `mountedActions.{i}.data` (state path) | `mountedActionsData.{i}` | state path | `Sylament\Actions\Concerns\HasActions` | Symfony's property accessor resolves the first path segment through the same-named getter, so the nested form is unreadable and unwritable — see [has-actions.md](actions/has-actions.md#per-mount-form-state-lives-in-a-sibling-bag-not-in-the-stack-descriptor). |
| `rule()` / `rules()` (forms `CanBeValidated`) | `constraint()` / `constraints()` | method | `Sylament\Forms\Components\Concerns\CanBeValidated` | [Constraint vocabulary](#laravel-rule-vocabulary-becomes-symfony-constraint-vocabulary) — the payload is Symfony `Constraint` objects, not Laravel rules; the rename makes the migration break loud and greppable instead of grep-hit-then-type-error. |
| `Section::formBefore()` / `isFormBefore()` | `contentBefore()` / `hasContentBefore()` | method | `Sylament\Schemas\Components\Concerns\HasContentBefore` | Adopts the `contentBefore` prop name Filament's own support-layer section component uses. |

### Interface and trait names are swapped

⚠️ **Grep trap.** For both schemas and actions, `Has*` and `InteractsWith*`
exist on **both** sides with **opposite** meanings:

| | Filament | Sylament |
| --- | --- | --- |
| **interface** | `HasSchemas` / `HasActions` | `InteractsWithSchemas` / `InteractsWithActions` |
| **trait** | `InteractsWithSchemas` / `InteractsWithActions` | `HasSchemas` / `HasActions` |

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
`validationMessages()` (including its `required`/`max`/`min`/`size`/`email`/
`numeric` keys), `validate()`.

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
| Eloquent | Doctrine | — |
| Blade view | Twig template | `view()` → `template()`, `viewData()` → `templateData()` |
| Blade component scope inheritance (`$this` is the Livewire component) | an explicit host — resolved from the Twig component stack, or passed as a prop | `<x-filament-actions::modals />` → `<twig:SylamentActions:Modals />` (see [has-actions.md](actions/has-actions.md#the-modals-renderer-resolves-its-host-from-the-render-stack-not-from-template-scope)) |
| Alpine.js | Stimulus | `x-data` / `x-on:` → a controller under `packages/*/assets/src/controllers/`, wired with StimulusBundle's Twig helpers |
| `fi-*` classes | `sy-*` classes | `fi-icon-btn` → `sy-icon-btn`, `fi-disabled` → `sy-disabled` |

CSS class names are not a flat prefix swap: bundles above support insert a
package code (`sy-fo-input`, `sy-sc-grid`), support-level classes carry none
(`sy-icon-btn`), and every template root also gets the bare `sy` marker class.
Full contract: [`docs/styling.md`](../styling.md).

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

Support-level traits split by **who composes them**, so
`Filament\Support\Concerns\` has two destinations:

| Filament | Sylament | Which traits |
| --- | --- | --- |
| `Filament\Support\Concerns\` | `Sylament\Support\Components\Concerns\` | Everything only a component composes — `HasIcon`, `HasColor`, `HasExtraAttributes`, … and `Configurable`, whose sole consumer is `Component` |
| `Filament\Support\Concerns\` | `Sylament\Support\Concerns\` | `EvaluatesClosures` alone |

`EvaluatesClosures` stays at root against that rule for a reason that is
checkable in Filament rather than a matter of taste: of its three direct
importers there, two — `Colors\ColorManager` and `TimezoneManager` — are plain
manager classes, not components. Sylament will have the same non-component
consumers once colors are ported, and `Sylament\Support\Colors\ColorManager`
importing `Support\Components\Concerns\EvaluatesClosures` would be wrong. The
move is cheap to make later and breaking to reverse, so it waits.

### Namespaces with no Filament source

Segments that exist only here, because a port needed classes Filament does not
have. Nothing to rename from, so they are listed rather than mapped.

| Sylament | Holds | Why it exists |
| --- | --- | --- |
| `Sylament\Support\Evaluation\` | `Invoker`, `ClosureParameter` | Filament inlines the closure-resolution loop in `EvaluatesClosures` and falls back to `app()->make()`; Symfony's container cannot, so the loop is extracted into a container-aware `Invoker`, and `ClosureParameter` replaces Filament's `[$value]`-vs-`[]` array wrap. See [component.md](support/component.md#closure-resolution-lives-in-a-service). |

## Divergences

Deliberate deviations from Filament's `make()` mechanics, applying to every
ported component — permanent, and the reason they hold.

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
