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
| `schema()` (`HasChildComponents` / root `HasComponents`) | — not ported | method | — | `components()` is the sole spelling (decided 2026-08-04). Filament ships the alias undeprecated as back-compat for its own pre-v4 call sites, which this port has none of; a migrating `->schema([...])` breaks loudly into a mechanical fix — [component.md](schemas/component.md#the-schema-alias-is-not-ported). |
| `validationMessages()` keys (`required`, `max`, `email`, …) | `class-string<Constraint>` keys (`NotBlank::class`, `Length::class`, …) | array keys | `Sylament\Forms\Components\Concerns\CanBeValidated` | [Constraint vocabulary](#laravel-rule-vocabulary-becomes-symfony-constraint-vocabulary) — the rule names have nothing behind them in Symfony, where the message is a property on the constraint; class keys are IDE-completable and let one post-pass message any constraint, including escape-hatch ones ([forms/can-be-validated.md](forms/can-be-validated.md#validation-messages-apply-after-the-constraints-are-built)). |
| `Section::formBefore()` / `isFormBefore()` | `contentBefore()` / `hasContentBefore()` | method | `Sylament\Schemas\Components\Concerns\HasContentBefore` | Adopts the `contentBefore` prop name Filament's own support-layer section component uses. |
| `ButtonComponent::make(isOutlined: true)` | `OutlinedButtonComponent` | class | `Sylament\Support\Colors\Components\OutlinedButtonComponent` | The outlined variant is a subclass, not constructor state — Twig cannot call a static factory and `colorAttributes()` takes a class-string. [Why, and what it buys](support/color-policies.md#the-outlined-button-is-a-subclass-not-a-constructor-flag). |

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
| Eloquent | Doctrine | — |
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

`Sylament\Support\Twig\Components\` — where the translation would have put
them — already means a class-backed **renderable** Twig component, the shape
actions' `Modals` has. A colour policy is never rendered and has no template, so
it is filed by capability at `src/` level like `Evaluation\Invoker` and
`Configuration\ComponentManager`. Class names, including the nesting
(`DropdownComponent\ItemComponent`), are Filament-verbatim; only the leading
segments move. Detail:
[color-policies.md](support/color-policies.md#the-tier-is-filed-under-colors-not-under-twigcomponents).

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

Methods that exist only here, because a port needed behaviour Filament has no
member for. Nothing to rename from, so they are listed rather than mapped — and
listed at all so a migrating user who greps Filament for the name and finds
nothing knows the absence is real.

| Sylament | On | Why it is not a rename |
| --- | --- | --- |
| [`nullWhenBlank()`](schemas/belongs-to-record.md#a-declared-branch-is-nulled-whole-before-its-leaves-are-written) | `Sylament\Schemas\Components\Concerns\HasNullableBranch` | Declares a container an optional branch of the record graph, so a blank one is written as `null` instead of being built empty. The nearest Filament member is `relationship(condition:)`, and it is not the same call under another name: its **polarity is inverted** (`condition:` true means *save*, this means *discard*), it carries no built-in blank test — a Filament user hand-writes the emptiness check at every call site — and it is scoped to an Eloquent relationship where this is scoped to a declared state path. Renaming `condition:` onto it would map a Filament call to a Sylament call that does the opposite. |
| [`isBlankStateValue()`](schemas/belongs-to-record.md#the-built-in-blank-test-asks-the-branchs-own-leaves) | `Sylament\Schemas\Components\Concerns\HasNullableBranch`, overridden on `Checkbox` / `Toggle` | The blank test each declared leaf answers for the branch pass, so a field whose cast never dehydrates null (an untouched checkbox rests at `false`) can still read as blank. Filament has no branch pass at all — `nullWhenBlank()` above is Sylament-only — so there is nothing for the predicate it consults to be renamed from. |

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
