# Select (forms)

`Sylament\Forms\Components\Select` — the native `<select>` field, over a static
option map, an enum, or a Doctrine association. It composes the shared option
pair ([`HasOptions`](has-options.md),
[`CanDisableOptions`](can-disable-options.md) and its
[contract](../../../packages/forms/src/Components/Contracts/CanDisableOptions.php)),
which have files of their own, plus
[`CanSelectPlaceholder`](../../../packages/forms/src/Components/Concerns/CanSelectPlaceholder.php),
its one single-use concern, folded here, and
[`HasRelationship`](../../../packages/forms/src/Components/Concerns/HasRelationship.php),
which it now shares with
[`CheckboxList`](checkbox-list.md#the-to-many-relationship-arm-serves-this-field-unchanged)
and whose write-up stays here as the arm's home.
It casts its state through schemas'
[`OptionStateCast`](../schemas/option-state-cast.md) — or, under `multiple()`,
[`OptionsArrayStateCast`](../schemas/options-array-state-cast.md), or, under an
[enum](has-enum.md), [`EnumStateCast`](../schemas/enum-state-cast.md) /
[`EnumArrayStateCast`](../schemas/enum-array-state-cast.md) — and appends a
Symfony `Choice` constraint over its enabled options so a tampered payload is
rejected server-side.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php)
  — the field
- [`Components/Concerns/HasOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasOptions.php),
  [`CanSelectPlaceholder.php`](../../../vendor/filament/forms/src/Components/Concerns/CanSelectPlaceholder.php),
  [`CanDisableOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptions.php)
  — the concerns
- [`Components/Concerns/CanBeNative.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeNative.php)
  — the native/JS renderer switch (unported)

## Surface

_Blocked by_ names what has to land first. _Detail_ links a permanent note in
[Details](#details), [Semantics](#semantics) or [Divergences](#divergences), or
`—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Native `<select>` rendering | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#the-template-renders-the-selected-state-itself) · [diverges](#canbenative-is-not-ported) · [diverges](#the-default-placeholder-is-an-untranslated-literal) |
| [`HasOptions`](../../../packages/forms/src/Components/Concerns/HasOptions.php) | [`Concerns/HasOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasOptions.php) | done | — | [detail](has-options.md) |
| [`HasEnum`](../../../packages/forms/src/Components/Concerns/HasEnum.php) — inherited from `Field`; `Select` overrides `getEnumDefaultStateCast()` for the `multiple()` arm | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [detail](has-enum.md) |
| [`CanSelectPlaceholder`](../../../packages/forms/src/Components/Concerns/CanSelectPlaceholder.php) | [`Concerns/CanSelectPlaceholder.php`](../../../vendor/filament/forms/src/Components/Concerns/CanSelectPlaceholder.php) | done | — | [diverges](#disableplaceholderselection-is-not-ported) |
| [`CanDisableOptions`](../../../packages/forms/src/Components/Concerns/CanDisableOptions.php) + [contract](../../../packages/forms/src/Components/Contracts/CanDisableOptions.php) | [`Concerns/CanDisableOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptions.php) | done | — | [detail](can-disable-options.md) |
| [`hasNullableBooleanState()`](../../../packages/forms/src/Components/Select.php) | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [field.md](field.md) |
| [`HasExtraInputAttributes`](../../../packages/forms/src/Components/Concerns/HasExtraInputAttributes.php) | [`Concerns/HasExtraInputAttributes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraInputAttributes.php) | done | — | [detail](has-extra-input-attributes.md) |
| `boolean()` | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | — |
| `getOptionLabel()` | [`resolveOptionLabel()`](../../../vendor/filament/forms/src/Components/Select.php) | in progress | the JS select control | [detail](#getoptionlabel-omits-the-getoptionlabelusing-arm) |
| Option validation (`Choice`) | [`getInValidationRuleValues()`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#the-choice-constraint-is-the-fields-only-server-side-guard) |
| `CanBeNative` | [`Concerns/CanBeNative.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeNative.php) | blocked | the JS select control | [diverges](#canbenative-is-not-ported) |
| `multiple()` / [`OptionsArrayStateCast`](../schemas/options-array-state-cast.md) | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#a-multiple-select-validates-element-by-element) · [diverges](#multiple-renders-a-native-multi-select-and-nothing-else) |
| `searchable()` / `preload()` / `HasLoadingMessage` | [`Concerns/CanBeSearchable.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeSearchable.php) | todo | the JS select control | — |
| [`relationship()`](../../../packages/forms/src/Components/Concerns/HasRelationship.php) — to-one and to-many, over the [association reader](../schemas/association-reader.md) | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [detail](#relationship-is-three-lazy-closures-over-the-association-reader) · [semantics](#a-relationship-select-is-always-enumerable-or-it-throws) · [diverges](#the-save-arm-writes-a-desired-list-not-attachdetach) |
| `relationship()` create + edit option actions (`createOptionUsing`, `editOptionForm`, …) | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | todo | — | — |
| `relationship()` `ignoreRecord:` | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | todo | the `unique`/`exists` constraint family | — |
| `pivotData()` | [`Concerns/HasPivotData.php`](../../../vendor/filament/forms/src/Components/Concerns/HasPivotData.php) | blocked | a join-entity recipe | [diverges](#the-save-arm-writes-a-desired-list-not-attachdetach) |
| `allowHtml()` | [`Concerns/CanAllowHtml.php`](../../../vendor/filament/forms/src/Components/Concerns/CanAllowHtml.php) | todo | — | — |
| `CanFixIndistinctState` | [`Concerns/CanFixIndistinctState.php`](../../../vendor/filament/forms/src/Components/Concerns/CanFixIndistinctState.php) | blocked | `Repeater` | — |
| Affixes ([`HasAffixes`](../../../packages/forms/src/Components/Concerns/HasAffixes.php), `wrapInputHtml`) | [`Concerns/HasAffixes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasAffixes.php) | done | — | [detail](has-affixes.md) · [semantics](#the-inline-prefix-class-counts-visible-actions-where-the-text-inputs-counts-all-of-them) |

Coverage: the concern surface — flattening, per-option disabling, merge/replace,
placeholder selectability and dynamic options — by
[`CanDisableOptionsTest`](../../../packages/forms/tests/Unit/Components/Concerns/CanDisableOptionsTest.php);
the rendered markup (including a disabled option in both the flat and the grouped
shape), the state round-trip, `boolean()`, `getOptionLabel()` over flat, grouped
and boolean options, the no-options `LogicException` and the `Choice` guard — the
tampered-value, disabled-option and int-keyed cases plus both stored forms of a
`boolean()` option, all run through the container's real validator — the enum
arm (its options rendered, the enum-valued state's option marked `selected`, the
single and `multiple()` round trips against an enum-typed record property, an
enum-only select validating rather than throwing, and — driven end to end through
a host, so the tampered string is the field's *own* raw state — the
[filled gate](#the-filled-gate-reads-the-raw-state-because-a-cast-can-null-a-tampered-value-out)
rejecting a value the enum cast nulls out, in both multiplicities) — and the
extra input attributes reaching the `<select>` rather than its `<option>`s — by
[`SelectTest`](../../../packages/forms/tests/Integration/Components/SelectTest.php);
the multi-value arm — `multiple()` as literal, `false` and closure, the cast
swap (and a custom cast still winning), the state round-trip, the
`Choice(multiple: true)` guard against a tampered element, the empty-selection
and no-options cases, and the rendered `multiple` attribute with its selected
options in both the flat and the grouped shape — by
[`SelectMultipleTest`](../../../packages/forms/tests/Integration/Components/SelectMultipleTest.php);
the relationship arm — the to-one fill/option-map/`getOptionLabel` trio, the
selected-id write, the empty-submission null, the `Choice` rejection of an id
outside the map, `modifyQueryUsing` scoping, the memoized map (a row inserted
between two `getOptions()` calls surfaces only on the scoped select), a clone
reading its own configuration rather than the original's, the
missing-`titleAttribute` throw, a
relationship named apart from the field, and the to-many arm's fill, collection
sync (the `PersistentCollection` instance surviving, the dropped `Tag` row
surviving, `multiple()` declared on either side of `relationship()`), empty
submission, a round-trip against a reloaded post whose collection is still
lazy, and both multiplicity refusals (a to-many association without `multiple()`,
a to-one association with it, each asserted from the option map and from
`fill()`) — by
[`SelectRelationshipTest`](../../../packages/forms/tests/Integration/Components/SelectRelationshipTest.php),
against the in-memory SQLite rig
([`EntityManagerFactory`](../../../packages/forms/tests/Fixtures/Doctrine/EntityManagerFactory.php),
smoke-tested by
[`DoctrineRigTest`](../../../packages/forms/tests/Integration/Fixtures/DoctrineRigTest.php));
the wiring that needs no database — `hasRelationship()`, the name fallback, the
title attribute, and the no-entity-manager throw — by
[`HasRelationshipTest`](../../../packages/forms/tests/Unit/Components/Concerns/HasRelationshipTest.php).

## Details

### `getOptionLabel()` omits the `getOptionLabelUsing` arm

The port resolves the label from the static option map only, walking both the
flat and the grouped shape. The match is an array-key lookup rather than a
comparison, which is what Filament's `$options[$state]` is: PHP canonicalizes a
numeric-string key to an int, so a state of `'1'` finds the `1` option however
the field's casts spell it. A `bool` state is normalized to `int` first — the
coercion PHP would apply to an array offset anyway, and the one `boolean()`
depends on to resolve `true` to its `1 => 'Yes'` option.

Filament's `getOptionLabelUsing` closure arm exists for the case where the
selected record is *not* in the option map — a searchable, non-preloaded select
fetches one record's label by id. The port's
[`relationship()`](#relationship-is-three-lazy-closures-over-the-association-reader)
always builds the full map, so a relationship-backed label resolves off it like
any other and needs no second lookup; the closure arm therefore lands with the JS
select control that makes partial option maps possible, not with the relation
layer. Filament's `$withDefault` parameter — falling back to the raw state when
no option matches — is also unported: the port returns `null`, which is the
honest answer for a value that is not an option.

### `relationship()` is three lazy closures over the association reader

[`HasRelationship`](../../../packages/forms/src/Components/Concerns/HasRelationship.php)
stores the title attribute and the optional query scope, then wires three
closures and nothing else. Each takes its component as `self`, which resolves to
whichever class composed the trait, so
[`CheckboxList`](checkbox-list.md#the-to-many-relationship-arm-serves-this-field-unchanged)
takes the same three unchanged; the option query the first of them runs is a
`getRecordsFromRelationship()` seam both fields read, so it is built and scoped
in one place. The association *name* and the target resolution
below moved one level down into
[`BelongsToRelationship`](../../../packages/forms/src/Components/Concerns/BelongsToRelationship.php)
when [`Repeater::relationship()`](repeater.md#a-relationship-repeater-keys-its-items-by-identity-and-diffs-the-collection)
needed the same three-arm refusal, and one package down into
[schemas' trait of the same name](../schemas/entangles-state-with-singular-relationship.md#the-association-core-is-shared-with-forms-relation-backed-fields)
when the entangled layout components needed it too — what is left in forms is the
adapter that says "The [name] field" and that a field has its own name to fall
back on. The clauses that differ per field — why an unresolvable relationship is
fatal for *this* field, and what to do instead — stay with each field as small
hooks, so no message lost a word to the sharing:

- **`options()`** — [`AssociationReader::describe()`](../schemas/association-reader.md)
  resolves the record class's mapping at that property, `createOptionQuery()`
  builds `SELECT record FROM <target> record`, `modifyQueryUsing` scopes it, and
  each row becomes its `getRecordKey()` mapped to the title attribute, read
  through
  [`PropertyAccessor::getFromRecord()`](../schemas/property-accessor.md). The
  keys are the identifier strings the client speaks, so PHP canonicalizes numeric
  ones to `int` array keys — the same vocabulary
  [`getEnabledOptionValues()`](#the-choice-choices-cover-both-forms-the-raw-state-can-take)
  already reconciles, which is why the `Choice` guard needed no relationship arm.
- **`loadStateFromRelationshipsUsing()`** — the association value's record key
  (null when unset), or the list of keys for a to-many. This is the
  [hook pair](../schemas/belongs-to-record.md) `fill()` consults instead of the
  ordinary record read, which would have skipped the path entirely: an
  object-valued leaf is skipped by `readStateFromRecord()`, and a collection is
  refused by `setOnRecord()`.
- **`saveRelationshipsUsing()`** — the reverse, through
  [`AssociationReader::getReference()`](../schemas/association-reader.md), so
  `getData()` writes managed references without loading the target rows.

Every one of them resolves the record class and the reader **at evaluation
time**, never at `relationship()` time — the same lazy shape
[`Repeater::refuseObjectCollectionRecord()`](repeater.md) uses, and what lets
`relationship()` be declared before the component is attached to a schema. The
to-one/to-many branch is read off the mapping on each call rather than captured:
all three go through `getMultiplicityMatchedRelationshipTarget()`, which reads
`isMultiple()` on the same call and refuses the two shapes where the field and
the mapping disagree — a to-many association the field never declared
`multiple()` for (every selection but one would be dropped on the way in, and the
save would write a single reference over the collection), and a to-one
association it did (a list of selections has no single record to map onto). Both
sides are still read per call rather than snapshotted, so `multiple()` may be
declared on either side of `relationship()`; it is the *pairing* that is fixed,
not the declaration order.

All three take the component as an injected parameter
(`static function (Select $component)`, Filament-exact) rather than closing over
`$this`. [`getClone()`](../schemas/component.md) is a shallow `clone`, so a
closure bound to `$this` would keep pointing at the *original* component after
the clone — every per-item schema a `Repeater` builds is a clone of the child
schema, so a relationship select inside one would read the template's
configuration, not the item's. The values it reads happen to be
instance-invariant today, which is precisely why the binding has to be right
before something instance-varying (`ignoreRecord:`, a per-item scope) makes it
observable.

`$name` defaults to the field's own name at declaration time
(`$name ?? $this->getName()`, Filament-exact), which is what keeps
`hasRelationship()` false for a select that never declared one — the fallback
lives in the assignment, not in the getter.

The option map is memoized on the component, and reset by `relationship()`
itself — again Filament-exact, and load-bearing here because the v1 map is the
*whole* target table: `getOptions()` runs at least twice per request (once
through `getEnabledOptionValues()` during validation, once from the template)
plus once per `getOptionLabel()`, and without the memo each is another unbounded
`SELECT`. A field declaring `modifyQueryUsing` neither reads nor writes the
memo, because a scoping closure may read state that changes within the request
and a cached first answer would freeze it.

The concern imports `Doctrine\ORM\QueryBuilder` directly, so `forms` declares
`doctrine/orm` in its own `require` rather than inheriting it through `schemas` —
the split-repo rule, since each package is published standalone.

## Semantics

### The `Choice` constraint is the field's only server-side guard

A native `<select>` gives no server-side guarantee at all: the submitted value is
an ordinary string a client can set to anything, and `disabled` on an `<option>`
is a browser-side courtesy.
[`buildValidationConstraints()`](../../../packages/forms/src/Components/Select.php)
therefore appends a `Choice` over
[`getEnabledOptions()`](../../../packages/forms/src/Components/Concerns/CanDisableOptions.php)
whenever the [raw state is filled](#the-filled-gate-reads-the-raw-state-because-a-cast-can-null-a-tampered-value-out)
— that one constraint is what rejects both a tampered
value and a value whose option is disabled. Symfony's `Choice` is always strict
(`ChoiceValidator` throws on any other setting), so the comparison is identity,
which is why the choices are built in the field's *stored* form (see below).

One shape is guarded by a different constraint, because `Choice` cannot express
it: a `multiple` select whose raw state is not an array
([below](#a-non-array-raw-state-is-failed-by-a-type-constraint)).

The constraint carries no message argument: one is written afterwards by the
[post-pass](can-be-validated.md#validation-messages-apply-after-the-constraints-are-built),
keyed `validationMessages([Choice::class => …])` where Filament keyed the `in`
rule. `Choice` declares four message options (`message`, `multipleMessage`,
`minMessage`, `maxMessage`), so the string shorthand sets `message` — the one a
single-value select violates — and an option map reaches the others.

The `LogicException` raised when a filled select has no `options()` exists so
that "validates nothing" can never be the silent default: a select whose options
come from somewhere the port cannot see must say so loudly rather than wave every
payload through.

The *whenever state is filled* half of that has an upstream twin now, and the two
do not stack. `Choice` is not implicit, so the
[presence gate](../schemas/presence-gate.md#the-gate-is-a-no-op-for-select-which-already-guarded-itself)
would skip it against the `''` a placeholder submits — except this builder never
emits one for a blank state in the first place, so the gate finds nothing to
filter and select validation is unchanged in both directions. The guard here is
not redundant with it either: it exists because there are no choices to enumerate
for a value that is not there, which is a different question from whether a
constraint should run.

### The *filled* gate reads the raw state, because a cast can null a tampered value out

All four option fields open `buildValidationConstraints()` with the same
short-circuit — there are no choices to enumerate for a value that is not there —
and the state it asks about has to be the **raw** one,
`blank($this->getRawState())`. Reading `getState()` there was a validation bypass.

The two readings differ for exactly one input, and it is the one that matters. A
state cast may find nothing it can represent in what a payload wrote:
[`EnumStateCast`](../schemas/enum-state-cast.md) fails closed to `null` for a
value matching no case, and
[`EnumArrayStateCast`](../schemas/enum-array-state-cast.md) drops every such
element. So a tampered `'deleted-by-hand'` on an enum-backed field casts to
`null`, a *cast* gate reads "nothing selected", the `Choice` is never appended,
and the field is left holding `NotBlank` alone — which a non-blank string passes.
[`HasSchemas::validateSchema()`](../../../packages/schemas/src/Concerns/HasSchemas.php)
then validates the **raw** bag entry against that empty guard, so the tampered
value clears validation and reaches the record write, where a typed setter throws
a `TypeError` — a 500 where a rejection belonged.

The raw gate splits the two cases the cast gate had conflated:

- **Nothing was submitted** — the raw state is `null`, `''` or `[]` — and the
  `Choice` is still skipped, so an unanswered required field is failed by
  `NotBlank` alone and an optional one passes untouched. Unchanged, and pinned by
  the required and blank-state cases in all four suites.
- **Something was submitted that resolves to nothing** — the raw state is filled,
  so the `Choice` runs and
  [`getEnabledOptionValues()`](#the-choice-choices-cover-both-forms-the-raw-state-can-take),
  which enumerates both stored forms of every enabled option, rejects it.

Reading raw is the only reading consistent with what is validated: the constraint
list guards the raw bag entry, so the gate deciding whether to build it must ask
about that same value. The non-enum arms never showed the hole —
[`OptionStateCast`](../schemas/option-state-cast.md) passes a tampered scalar
straight through, so its cast state was filled and the `Choice` was built — which
is why the defect surfaced only once an enum reached a field.

The widening adds rejections without removing any. Every cast these fields
install — the option, options-array, enum, enum-array and `boolean()` casts —
maps a blank raw state to a blank cast state, so nothing that used to reach the
`Choice` stops reaching it. What it newly catches is the fail-closed arm of each
cast: besides the enum case above, a raw `false` on a single field
(`OptionStateCast` stringifies it to `''`) and a raw array on a single field (no
scalar to read, so `null`). The array one lands on `Choice(multiple: false)`,
whose validator does an `in_array()` and fails it rather than throwing, so it is
a violation and not a 500.

The gate is four one-line reads rather than a shared helper, deliberately.
`getRawState()` is already the shared accessor ([schemas'
component-level `HasState`](../schemas/component.md)); each builder puts its gate
at a different point in a different sequence —
[`CheckboxList`](checkbox-list.md#the-three-server-side-guards-are-ordered-so-they-never-collide)
runs two constraints ahead of it — and a name like `hasBlankState()` would hide
the one thing a reader of this method has to see, which state is being asked
about.

### The `Choice` choices cover both forms the raw state can take

`getEnabledOptions()` returns PHP array keys, while validation runs against the
**raw** state a payload wrote —
[`HasSchemas::validateSchema()`](../../../packages/schemas/src/Concerns/HasSchemas.php)
reads the host property, not `getState()`. Two different values can legitimately
sit there, and a strict `Choice` has to admit both:

- **The cast form** — what `state()` and `Schema::fill()` write, each key run
  through the reversed state-cast chain. With `options([1 => 'One'])` the key is
  `int 1` while `OptionStateCast::set()` stores the string `'1'`; a `Choice` over
  the bare keys would reject the field's own filled value.
- **The submitted form** — the option's `value` attribute, which the template
  renders from the array key, so it is always `(string) $key`. A single
  `<select>` binds through `live_controller`'s `getValueFromElement()`, which
  writes `element.value` — a string — straight into the host's bag, and nothing
  re-normalizes it afterwards: `hydrateState()` is reached from `Schema::fill()`
  only, i.e. at mount.

[`getEnabledOptionValues()`](can-disable-options.md#the-choice-choice-list-lives-here-not-on-the-field)
therefore emits the cast value and, when it differs,
the submitted string. For string options the two coincide and the list is the
plain key list; for `boolean()`, whose `BooleanStateCast(isStoredAsInt: true)`
stores `int 1`, the choices are `[1, '1', 0, '0']` — without the string arm a
`boolean()` select rejects its own value the moment it round-trips through the
browser. An enum'd field is the coinciding case again, since
[`EnumStateCast::set()`](../schemas/enum-state-cast.md) stores
`(string) $case->value`, which is the option key the template rendered. Nothing
is loosened by the extra arm: `Choice` stays strict and a tampered or disabled
value is still absent from the list.

The method itself lives on
[`CanDisableOptions`](can-disable-options.md#the-choice-choice-list-lives-here-not-on-the-field)
rather than on `Select`, so every option field builds its choice list the same
way; the reasoning above is what it implements.

### A multiple select validates element by element

`multiple()` swaps the field's default cast for
[`OptionsArrayStateCast`](../schemas/options-array-state-cast.md), so the state is
a list and the `Choice` gains `multiple: true` — which is what makes Symfony
validate each submitted element against the choices instead of the array as a
whole. Three consequences worth stating, because each looks like an omission in
the code:

- **An empty selection builds no constraint.** The builder's existing
  [`blank($this->getRawState())`](#the-filled-gate-reads-the-raw-state-because-a-cast-can-null-a-tampered-value-out)
  early return already covers it —
  [`blank([])`](../support/helpers.md) is true — so the empty-selection case
  needs no branch of its own, and validation of "must this field be filled?"
  stays with the presence/required layer where it belongs.
- **The choices stay scalar.** `getEnabledOptionValues()` runs each option key
  through the cast chain, and a multi-value cast turns one key into a
  *one-element list*; the builder spreads that list rather than pushing it, since
  `Choice(multiple: true)` compares elements and a value wrapped in an array
  would match nothing. Both stored forms of a key are still emitted — the
  reasoning is [unchanged](#the-choice-choices-cover-both-forms-the-raw-state-can-take).
- **The cast is non-nullable.** `OptionsArrayStateCast(isNullable: false)` writes
  `[]` for an empty selection, not `null`, so the host's bag keeps an array the
  template can iterate and the browser can write back.

### A non-array raw state is failed by a `Type` constraint

`Choice(multiple: true)` is defined over arrays only:
[`ChoiceValidator::validate()`](../../../vendor/symfony/validator/Constraints/ChoiceValidator.php)
throws `UnexpectedValueException` for any other value rather than building a
violation. Validation runs against the **raw** state, which a tampered payload
controls, and the field's
[filled gate](#the-filled-gate-reads-the-raw-state-because-a-cast-can-null-a-tampered-value-out)
does not stop a scalar from reaching the builder — a non-blank string is exactly
what that gate lets through. Handing it to the `Choice` would end the request in
a 500 instead of a rejection.

The builder therefore branches before it appends the `Choice`: when the field is
`multiple` and the raw state is not an array, it appends a `Type('array')` and
returns. The payload is failed, with a violation on the field, and every
array-shaped state takes the `Choice` path unchanged. The no-`options()`
`LogicException` still comes first, so a tampered payload cannot mask a
misconfiguration.

### A relationship select is always enumerable, or it throws

The `Choice` guard enumerates `getOptions()`, so an option map that comes back
empty does not fail open — it fails *closed*, rejecting every value the user can
pick, with a violation that names nothing wrong with the submission. That is the
one outcome a relationship select must never produce, and it is exactly what a
silently-degrading options closure would produce: no entity manager, no record
class, or a property Doctrine maps nothing at, each yielding `[]`.

So the closure throws instead. Each arm names which of the three it hit and what
would fix it, and the reader's own
[`getReference()`](../schemas/association-reader.md) and `createOptionQuery()`
throw on the same missing entity manager from the write side. There is no
fallback option map and no empty-map arm anywhere in the concern; a select whose
options the port cannot enumerate is a configuration error, not a runtime state.
Validation then stays exactly as it was — the `Choice` is built over a full map,
which is what makes the epic's "never a partial enumeration" rule mechanical
rather than a convention.

One consequence worth stating: because the map is full and the constraint strict,
the save hook receives an identifier that has already been checked against the
target table by the time it runs. Its own "not an identifier" throw is therefore
a belt-and-braces guard for a state that reached `getData()` without validation,
not the primary defence.

### The template renders the selected state itself

The template emits `selected` per option, comparing the option's key against
[`getOptionComparisonState()`](has-options.md#the-option-vocabulary-is-normalized-once-rather-than-per-template)
— `in` when the state is a list, a loose `==` otherwise. For a
single select this is redundant with the live binding on a first render and
becomes load-bearing on any server-rendered non-live one; for a multiple select
there is no alternative, since a multi-select's value cannot be expressed as an
attribute at all. The comparison is deliberately loose because the two sides
speak different vocabularies — an option key is an `int` where the state may hold
the `'1'` a browser submitted — the same mismatch
[`getEnabledOptionValues()`](#the-choice-choices-cover-both-forms-the-raw-state-can-take)
answers on the validation side. Nothing is loosened security-wise: `selected` is
presentation, and the strict `Choice` is what admits or rejects a value.

What the comparison never sees is a raw `getState()`: the normalization is one
method on [`HasOptions`](has-options.md), so an enum-valued or boolean state
reaches the template already spelled in the option vocabulary, and
`getOptionLabel()` resolves through the same method rather than repeating the
bool-to-int coercion it used to carry.

### The inline-prefix class counts visible actions where the text input's counts all of them

`sy-fo-select-has-inline-prefix` strips the `<select>`'s left padding so an
inline prefix can occupy it, and the template computes it from the
**visibility-filtered** prefix actions
([`Select::toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/Select.php)
filters before deriving `$hasPrefix`). The text input and the picker family
compute their `sy-fo-input-has-inline-prefix` from the **unfiltered** arrays
instead, which is Filament's own asymmetry and is kept: a select whose only
prefix action is hidden keeps its padding and renders no gap, while a text input
in the same state keeps `ps-0` and the wrapper's own padding carries the space.
There is no suffix twin here — the `<select>` keeps its `pe-8` for the chevron
whatever the suffix side holds, so suffix actions (which is where
`createOption` / `editOption` live) never touch the control's padding.

## Divergences

Deliberate deviations from Filament, and the reason each holds. Provisional ones
say what would reverse them.

### `multiple()` renders a native multi-select and nothing else

In Filament `multiple()` is coupled to the Choices.js control: it forces
`native(false)` territory, drives tag-style rendering, and switches
`dehydrated()` off for the relationship arm. The port carries only the parts that
are meaningful for a native `<select multiple>` — the state cast swap, the
`multiple` attribute, the multi-value `Choice`, and the selected state — because
[`CanBeNative` is not ported](#canbenative-is-not-ported). The affordances that
belong to the JS control (searchable, `maxItems`, option reordering) land with
it. Filament's `dehydrated(false)` arm is not among them and never will be: it
exists for the relationship-saving path rather than for multi-value state, and
that path works the other way round here
([below](#the-save-arm-writes-a-desired-list-not-attachdetach)).

### The save arm writes a desired list, not attach/detach

Filament's `saveStateToRelationship()` speaks Eloquent relation verbs:
`associate()` for a `BelongsTo`, and for a `BelongsToMany` a `detach()` of the
diff followed by `sync(detaching: false)` — plus `unsetRelation()` to drop the
stale in-memory copy, and `syncWithPivotValues()` when `pivotData()` is set.
Doctrine has no equivalent verbs on the entity: the association *is* the
property, and the unit of work computes the difference at flush.

The port therefore writes the whole desired list onto the property, through
[`PropertyAccessor::setAssociationOnRecord()`](../schemas/property-accessor.md) —
Symfony's accessor diffs it against the collection by strict identity and calls
the entity's own `addX()`/`removeX()` per element, so the collection instance
survives, the owning side is maintained by the adder the maker generated, and
Doctrine emits only the join rows that actually changed
([characterized in `DoctrineCollectionWriteTest`](../../../packages/schemas/tests/Integration/State/DoctrineCollectionWriteTest.php)).
Kept elements diff as kept because `getReference()` answers from the identity
map with the very instances the loaded collection holds. Two things follow from
the mechanism rather than from a choice of ours: removing an element from a
`ManyToMany` deletes the join row only — the target entity survives, since
nothing declares `orphanRemoval` there — while a `OneToMany` that does declare it
deletes the child, which is Doctrine's contract and not something the select
decides.

`pivotData()` has no home in this shape at all: a Doctrine many-to-many with
columns of its own is modelled as a join *entity*, which is a repeater over that
entity rather than extra data on a select's write. It is blocked on that recipe,
not on this arm.

The single-flush shape also removes Filament's `dehydrated(false)` for the
multiple arm. Upstream it exists because a `BelongsToMany` cannot be written as a
model attribute and is saved afterwards by a separate `saveRelationships()` pass;
here the save hook fires *from* the dehydrated bag
([`getData()`](../schemas/belongs-to-record.md)), so switching dehydration off
would silence the hook instead of protecting it — the multiple relationship
select stays dehydrated, deliberately.

### `CanBeNative` is not ported

Filament's `native(false)` switches `Select` from a real `<select>` to its
JavaScript Choices.js control, and every searchable/multiple affordance hangs off
that renderer. The port ships the native renderer only, so `native()` has no
second branch to switch to: it could only lie (accept the call and render
natively anyway) or throw at runtime. A method that does not exist is the signal
PHPStan and the IDE can both see, so the row is **blocked** rather than skipped —
it lands with the JS control.

The trait itself now ships, composed by the
[date pickers](date-time-picker.md#nativefalse-throws-rather-than-falling-back-to-the-native-control),
which chose the other horn: they compose it and **throw** on `native(false)`,
because a picker has a second renderer in Filament that a user can reasonably
expect. This field's row stays blocked all the same — composing the trait here
would add a setter whose only outcome is that throw, where not composing it keeps
the absence visible at the call site.

### The default placeholder is an untranslated literal

`setUp()` sets the placeholder to the literal `'Select an option'`, and
`boolean()` to `'-'`. Filament reads
`__('filament-forms::components.select.placeholder')`; the translation surface is
unported, matching
[`getDefaultLabel`](field.md#getdefaultlabel-drops-label-translation).

### `disablePlaceholderSelection()` is not ported

Filament keeps it as a deprecated alias for `selectablePlaceholder()`. Porting a
method that is already deprecated upstream would import a migration the toolkit
never had.
