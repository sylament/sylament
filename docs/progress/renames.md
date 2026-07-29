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
| `Filament\Schemas\Concerns\InteractsWithSchemas` | `HasSchemas` | trait | `Sylament\Schemas\Concerns\HasSchemas` | [Swapped](#interface-and-trait-names-are-swapped) — `Has*` matches the house trait prefix (`HasComponents`, `HasKey`, `HasGap`, …). |
| `Filament\Schemas\Contracts\HasSchemas` | `InteractsWithSchemas` | interface | `Sylament\Schemas\Contracts\InteractsWithSchemas` | [Swapped](#interface-and-trait-names-are-swapped) — the interface names the behavior. |
| `mountedActions.{i}.data` (state path) | `mountedActionsData.{i}` | state path | `Sylament\Actions\Concerns\HasActions` | Symfony's property accessor resolves the first path segment through the same-named getter, so the nested form is unreadable and unwritable — see [has-actions.md](actions/has-actions.md#per-mount-form-state-lives-in-a-sibling-bag-not-in-the-stack-descriptor). |
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

Two namespaces **leave `Components\`**, because what they hold is not part of
the component tier — neither a component, nor a concern or contract of one. The
casts also gain a `State\` parent rather than sitting at the root, so they file
beside the `PropertyAccessor` they cast for:

| Filament | Sylament | Holds |
| --- | --- | --- |
| `Filament\Schemas\Components\StateCasts\` | `Sylament\Schemas\State\Casts\` | `StateCast` and its implementations — value objects that transform state |
| `Filament\Schemas\Components\Utilities\` | `Sylament\Schemas\Utilities\` | `Get` / `Set` — the closure-injected state accessors |

A migrating user importing `Filament\Schemas\Components\Utilities\Get` will
not find it by prefix swap alone, which is why these are recorded here rather
than left to the verbatim rule.

Support-level traits split by what they do, so `Filament\Support\Concerns\` has
two destinations:

| Filament | Sylament | Which traits |
| --- | --- | --- |
| `Filament\Support\Concerns\` | `Sylament\Support\Components\Concerns\` | Component traits — `HasIcon`, `HasColor`, `HasExtraAttributes`, … |
| `Filament\Support\Concerns\` | `Sylament\Support\Concerns\` | Evaluation / configuration plumbing — `EvaluatesClosures`, `Configurable`. |

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
