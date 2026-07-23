# Component (schemas base class)

The abstract base every schemas component extends. Component files (e.g.
[section.md](section.md)) track only their own surface; the inherited surface
lives here. Ported so far: `BelongsToContainer`, `CanGrow`, `CanOrderColumns`,
`CanSpanColumns`, `HasChildComponents`, `HasColumns`, `HasExtraAttributes`,
`HasGap`, `HasHeadings`, `HasId`, `HasKey` — modulo the items below.

Reference:

- [`vendor/filament/schemas/src/Components/Component.php`](../../../vendor/filament/schemas/src/Components/Component.php)
- [`vendor/filament/schemas/src/Components/Concerns/`](../../../vendor/filament/schemas/src/Components/Concerns)

## Missing

Portable now (no blocker):

- [ ] `CanBeHidden` core: `hidden()` / `visible()` closures,
  `hiddenWhenAllChildComponentsHidden()`, and the schema render loop skipping
  hidden children. `hiddenOn()` / `visibleOn()` need the schema operation
  concept; `whenTruthy()` / `whenFalsy()` and JS visibility are blocked on
  [live-component-state](../infrastructure.md#live-component-state).
- [ ] `HasMeta` (`meta()`, `getMeta()`, `hasMeta()`).
- [ ] `Cloneable` (`getClone()`, `afterCloned()`; must flush the cached keys —
  `flushCachedAbsoluteKey()` / `flushCachedAbsoluteInheritanceKey()` — as
  Filament's does).
- [ ] `CanBeDisabled` (`disabled()`, `isDisabled()`, `isEnabled()`;
  `disabledOn()` has the same operation caveat as `hiddenOn()`).
- [ ] `HasMaxWidth` (`maxWidth()`; needs a support `Width` enum port).
- [ ] `HasInlineLabel` (`inlineLabel()`, cascading from the schema).
- [ ] `CanBeGridContainer` (`gridContainer()`).
- [ ] `CanBeLiberatedFromContainerGrid` (`liberatedFromContainerGrid()`).
- [ ] `HasChildComponents` gaps: `schema()` alias, `childComponents()`
  accepting single `Action | ActionGroup` values (the `Component | string |
  Markup` single-or-array coercion is **ported** — the string→`Text` /
  other→`Html` normalization runs in the child schema's `getComponents()`, see
  [schema.md](schema.md)), `getChildComponents()`,
  `getChildSchemas(withHidden:)`, the container-named aliases, and the
  keyed-slot `cachedChildSchemas` memoization (Filament caches non-closure
  slots; ours rebuilds per `getChildSchema()` call) together with the
  schema-level `clearCachedChildSchemas()` flush that pairs with it.
- [ ] `EvaluatesClosures::evaluationValueIsFunctionAndHasParameter()` — used
  by Filament's `CanOpenUrl` twins; port with the first of them.
Blocked on infrastructure:

- [ ] `HasActions` (`registerActions()`, `getAction()`, …) — blocked on
  [actions-package](../infrastructure.md#actions-package)
- [ ] `HasState`, `HasStateBindingModifiers`, `CanPoll`, `CanPartiallyRender`,
  the state features of the per-child wrapper (`wire:key`, JS visibility,
  partial-render targeting — the grid-column wrapper itself is ported), the
  state-dependent closure parameters (`$state`, `$rawState`, `$get`, `$set`,
  `$livewire`, `$operation`), and the state-dependent remainder of `HasKey`
  (state-path fallbacks, `getLivewireKey()`) — blocked on
  [live-component-state](../infrastructure.md#live-component-state)
- [ ] `CanBeConcealed` (`isConcealed()`, `getConcealingComponent()`) — the
  `CanConcealComponents` contract it pairs with exists (Section implements it);
  the trait itself needs `getHiddenJs()`/`getVisibleJs()` from
  [live-component-state](../infrastructure.md#live-component-state).

Revisit with later packages:

- [ ] `BelongsToModel` (+ `BelongsToContainer::getModelRootContainer()`,
  `$record` / `$model` closure parameters) — Doctrine equivalent of Eloquent
  record binding; revisit when forms/infolists exist.
- [ ] `CanBeRepeated` — revisit with the forms `Repeater` / `Builder`.
- [ ] `HasEntryWrapper` — revisit with infolists.
- [ ] `HasFieldWrapper` — revisit with forms.
- [ ] Support-level `VerticalAlignment` enum + `HasVerticalAlignment` — first
  consumers are tables/`Flex`; revisit when those land.

## Divergences

- **Component-level `components()` union is widened** to
  `Schema|Component|string|Markup|array|Closure|null` — Filament omits `Schema`
  from `components()` (reserving it, with `null`, for `childComponents()`).
  Passing a `Schema` straight to `components()` is accepted convenience here; the
  `Component|string|Markup` single/array values are the ported coercion (Filament
  accepts those too, minus `Schema`).
- **Minimal attribute bag.** `HasExtraAttributes` folds entries through
  `Sylament\Support\Twig\ComponentAttributeBag` — a small value object with
  Laravel's merge-as-defaults semantics (class/style token lists append,
  defaults first; other existing keys win) instead of Filament's
  `Filament\Support\View\ComponentAttributeBag` extending Laravel's full bag.
  It normalizes `class`/`style` to token lists so templates can pass
  `getExtraAttributes()` straight into `attrs()` (the bag-backed `html_attr()`
  replacement), which merges them with other sources and escapes at render —
  hence no `escape:` parameter on `extraAttributes()` (Filament merges raw with
  `escape: false`). `View` → `Twig` follows the Laravel → Symfony translation
  conventions, not a rename.
- **No container-`make()` fallback in closure evaluation.** Filament's last
  resort for a class-typed closure parameter is Laravel's `app()->make()`,
  which auto-wires unbound classes; Symfony's container cannot instantiate ad
  hoc. After the resolver hooks and the identifier/self-type checks, we consult
  the DI container for **public services only** — on a compiled container
  `has()` answers `false` for private services, i.e. nearly every real app
  service (Twig, the entity manager, the translator) — then fall back to the
  declared default, then `null` for optional/nullable parameters, and otherwise
  throw. The planned sharpening (a curated `ServiceLocator` of injectable
  services) is recorded on the
  [translation](../infrastructure.md#translation) anchor.
- **Closure results are type-checked, not coerced.** Filament wraps evaluated
  flags in `(bool)` casts (`CanBeCollapsed`, `HasGap`, `CanBeContained`, …);
  under `strict_types=1` we return the evaluated value against the declared
  native return type, so a closure returning `1` / `'yes'` TypeErrors here
  where Filament silently coerces. One policy across every trait — new ports
  drop Filament's casts rather than reintroducing coercion.
