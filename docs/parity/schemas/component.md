# Component (schemas base class)

The abstract base every schemas component extends. Component files (e.g.
[section.md](section.md)) track only their own surface; the inherited surface
lives here. Ported so far: `BelongsToContainer`, `HasChildComponents`,
`HasColumns`, `HasGap` — modulo the items below.

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
- [ ] `Cloneable` (`getClone()`, `afterCloned()`).
- [ ] `CanBeDisabled` (`disabled()`, `isDisabled()`, `isEnabled()`;
  `disabledOn()` has the same operation caveat as `hiddenOn()`).
- [ ] `HasMaxWidth` (`maxWidth()`; needs a support `Width` enum port).
- [ ] `HasInlineLabel` (`inlineLabel()`, cascading from the schema).
- [ ] `CanBeGridContainer` (`gridContainer()`).
- [ ] `CanBeLiberatedFromContainerGrid` (`liberatedFromContainerGrid()`).
- [ ] `HasChildComponents` gaps: `schema()` alias, `childComponents()`
  accepting single `Component | string | Htmlable` (and, with actions,
  `Action | ActionGroup`) values, `getChildComponents()`,
  `getChildSchemas(withHidden:)`, the container-named aliases.
- [ ] Global configuration hooks (`configureUsing()`) — see
  [global-configuration](../infrastructure.md#global-configuration).

Blocked on infrastructure:

- [ ] `HasKey` (`key()`, `getKey()`, LiveComponent key equivalent) — blocked on
  [keys](../infrastructure.md#keys)
- [ ] `HasId` + `HasExtraAttributes` — blocked on
  [extra-attributes](../infrastructure.md#extra-attributes)
- [ ] `HasHeadings` (`getHeadingLevel()` / `getHeadingTag()` /
  `getHeadingsCount()`) — blocked on
  [heading-cascade](../infrastructure.md#heading-cascade)
- [ ] `CanSpanColumns` (`columnSpan()`, `columnSpanFull()`, `columnStart()`) +
  `CanOrderColumns` (`columnOrder()`) — blocked on
  [column-span](../infrastructure.md#column-span)
- [ ] `CanGrow` (`grow()`; sizing for inline-schema children) — blocked on
  [schema-slot-apis](../infrastructure.md#schema-slot-apis)
- [ ] `HasActions` (`registerActions()`, `getAction()`, …) — blocked on
  [actions-package](../infrastructure.md#actions-package)
- [ ] `HasState`, `HasStateBindingModifiers`, `CanPoll`, `CanPartiallyRender`,
  the `toSchemaHtml()` per-child wrapper, and the state-dependent closure
  parameters (`$state`, `$rawState`, `$get`, `$set`, `$livewire`,
  `$operation`) — blocked on
  [live-component-state](../infrastructure.md#live-component-state)
- [ ] `CanBeConcealed` (`isConcealed()`, `getConcealingComponent()`) — pairs
  with the `CanConcealComponents` contract from Section's collapse item.

Revisit with later packages:

- [ ] `BelongsToModel` (+ `BelongsToContainer::getModelRootContainer()`,
  `$record` / `$model` closure parameters) — Doctrine equivalent of Eloquent
  record binding; revisit when forms/infolists exist.
- [ ] `CanBeRepeated` — revisit with the forms `Repeater` / `Builder`.
- [ ] `HasEntryWrapper` — revisit with infolists.
- [ ] `HasFieldWrapper` — revisit with forms.

## Divergences

- **No container-`make()` fallback in closure evaluation.** Filament's last
  resort for a class-typed closure parameter is Laravel's `app()->make()`,
  which auto-wires unbound classes; Symfony's container cannot instantiate ad
  hoc. After the resolver hooks and the identifier/self-type checks, we consult
  the DI container for known services only (`has()`), then fall back to the
  declared default, then `null` for optional/nullable parameters, and otherwise
  throw.
