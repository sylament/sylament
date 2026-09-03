# HasColumns (tables host)

[`Sylament\Tables\Concerns\HasColumns`](../../../packages/tables/src/Concerns/HasColumns.php)
— the **host**-tier column concern
[`HasTable`](has-table.md) composes: the live actions a cell's own controls
fire back into. It ports Filament's
[`Tables\Concerns\HasColumns`](../../../vendor/filament/tables/src/Concerns/HasColumns.php).

Do not confuse it with
[`Sylament\Tables\Table\Concerns\HasColumns`](../../../packages/tables/src/Table/Concerns/HasColumns.php),
the **table**-tier trait of the same name that owns `columns()` / `getColumns()` /
`getColumn()` and folds into [`table.md`](table.md). Filament ships both names in
both tiers and the port keeps them; read the import, never the bare `use`.

Do not confuse the **state** either. `updateTableColumnState()` below writes one
editable cell's value onto a record; the `…TableColumnState` family on
[`Concerns\HasColumnManager`](column-manager.md) means the column manager's
visibility-and-order bag. Both vocabularies are Filament's own and both flatten
into the same host class —
[why they were kept](has-table.md#two-tablecolumnstate-vocabularies-meet-on-one-host-and-both-are-filaments).

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`tables/src/Concerns/HasColumns.php`](../../../vendor/filament/tables/src/Concerns/HasColumns.php)
  — `callTableColumnAction()`, `updateTableColumnState()`, `callTableColumnMethod()`
  and the deprecated `getTableColumns()`

## Surface

Every point of this trait. _Blocked by_ names what has to land first. _Detail_
carries up to three links: **detail** → the write-up in [Details](#details),
deleted once the row is **done**; **diverges** → the permanent note in
[Divergences](#divergences); **semantics** → the permanent note in
[Semantics](#semantics).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`callTableColumnAction(string $name, string $recordKey)` — run a column's `action()` closure for one record](../../../packages/tables/src/Concerns/HasColumns.php) | [`HasColumns::callTableColumnAction()`](../../../vendor/filament/tables/src/Concerns/HasColumns.php) | done | — | [diverges](#a-live-action-answers-nothing-so-the-return-value-is-dropped) · [semantics](#a-hidden-column-is-unreachable-from-the-wire-on-both-actions) |
| [`updateTableColumnState(string $column, string $record, mixed $input)` — the editable columns' write-back](../../../packages/tables/src/Concerns/HasColumns.php) | [`HasColumns::updateTableColumnState()`](../../../vendor/filament/tables/src/Concerns/HasColumns.php) | done | — | [diverges](#a-live-action-answers-nothing-so-the-return-value-is-dropped) · [semantics](#a-record-type-the-input-cannot-hold-seeds-a-type-constraint-and-the-write-refuses-the-rest) · [semantics](#a-json-null-input-arrives-as-null-without-a-declared-default) · [semantics](#the-action-binds-the-record-and-its-key-and-only-one-of-the-two-invalidations-is-observable) · [semantics](#a-hidden-column-is-unreachable-from-the-wire-on-both-actions) |
| [`getTableColumnStateError()` + the request-scoped `$tableColumnStateErrors` bag](../../../packages/tables/src/Concerns/HasColumns.php) | — (Sylament-only, replacing Filament's client-held error) | done | — | [diverges](#the-refusal-is-request-scoped-and-server-rendered-where-filaments-lives-on-the-client) |
| [`Columns\Concerns\CanUpdateState` — the column half of the write](../../../packages/tables/src/Columns/Concerns/CanUpdateState.php) | [`Columns\Concerns\CanUpdateState`](../../../vendor/filament/tables/src/Columns/Concerns/CanUpdateState.php) | done | — | [semantics](#the-write-goes-through-the-accessor-so-a-dotted-path-walks-the-to-one-chain) · [diverges](#an-array-record-throws-where-filament-writes-nothing) · [diverges](#the-pivot-column-arm-has-nothing-to-write-through) · [diverges](#the-write-is-not-authorization-checked) |
| [`Columns\Concerns\CanBeValidated` — the column half of the refusal](../../../packages/tables/src/Columns/Concerns/CanBeValidated.php) | [`Columns\Concerns\CanBeValidated`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeValidated.php) | done | — | [diverges](#validation-speaks-symfony-constraints-and-rules-is-not-ported) · [semantics](#a-record-type-that-rejects-null-seeds-a-presence-constraint) · [semantics](#a-record-type-the-input-cannot-hold-seeds-a-type-constraint-and-the-write-refuses-the-rest) |
| [`getClientState()` — the display state the cell root publishes](../../../packages/tables/src/Columns/Concerns/CanUpdateState.php) | — (Sylament-only, for [the repaint](#the-cell-repaints-from-the-root-because-a-touched-control-is-pinned-through-every-morph)) | done | — | [detail](#the-cell-repaints-from-the-root-because-a-touched-control-is-pinned-through-every-morph) |
| `callTableColumnMethod()` — dispatch to an `#[ExposedLivewireMethod]` on a column | [`HasColumns::callTableColumnMethod()`](../../../vendor/filament/tables/src/Concerns/HasColumns.php) | blocked | the select-engine promotion ([forms-bundle.md](../forms/forms-bundle.md#the-select-controller-binds-a-port-of-filaments-select-utility-to-the-live-host)) | Its only Filament consumer is `SelectColumn`'s searchable arm, which ships native-only |
| `getTableColumns()` | [`HasColumns::getTableColumns()`](../../../vendor/filament/tables/src/Concerns/HasColumns.php) | skipped | — | The whole method carries `@deprecated Override the \`table()\` method`, and the port has never landed a deprecated Filament surface — [renames.md](../renames.md) |
| _rest not audited yet_ | | | | |

`updateTableColumnState()` is what the four editable columns
([`ToggleColumn`](toggle-column.md), [`CheckboxColumn`](checkbox-column.md),
[`SelectColumn`](select-column.md), [`TextInputColumn`](text-input-column.md))
write through, and it is the only channel they have: the client posts, the host
writes, and the re-render — plus
[the repaint](#the-cell-repaints-from-the-root-because-a-touched-control-is-pinned-through-every-morph)
that carries it onto a touched control — is the whole answer. Its arms are
covered by the second half of
[`HasColumnsTest`](../../../packages/tables/tests/Integration/Concerns/HasColumnsTest.php),
which drives the live fixture
[`EditableSongsTable`](../../../packages/tables/tests/Fixtures/Twig/Components/EditableSongsTable.php)
over the wire and reads every outcome off the **container's** entity manager and
off `(string) ->render()` — never off `->component()`, which re-hydrates a fresh
host and would let a refusal pass whatever the write actually did. Between them
those cases cover the write itself and its re-render, the boolean and `null`
arrivals, the refusal's paragraph and echo and its one-request life, every
refusal arm (unknown column, non-editable column, a column the developer hid,
unknown key, filtered-out key, disabled cell, disabled option, a blank against a
non-nullable type), the write the gate lets through for a column the *user* hid,
the record-and-key bind, the state-cache invalidation, the refused option keeping
its `selected`, and the override arm — an `updateStateUsing()` closure doing the
write itself, over an integer-keyed option map, with `afterStateUpdated()` still
running on top of the early return.

`callTableColumnAction()`'s eight arms are covered by
[`HasColumnsTest`](../../../packages/tables/tests/Integration/Concerns/HasColumnsTest.php),
which calls the live action on a real live host over the wire and reads what the
bound closure recorded off a `LiveProp`: the record and its key reaching the
closure, an unknown record key, an unknown column name, a column carrying no
action at all, a column the developer hid, the column a user toggled hidden that
the gate still runs, the column set a second `columns()` call replaced, and the
closure a `string` action name was wrapped into calling its host method. The
wiring that fires it — the cell wrapper's `button` arm choosing
`callTableColumnAction` over `mountAction` by
[`Column::getActionKind()`](column.md) — is pinned in
[`TableRenderingTest`](../../../packages/tables/tests/Integration/TableRenderingTest.php).

## Details

Specifics for rows that are not finished — what is already in, what is left, and
what the next step is. Delete a subsection when its row flips to **done**; the
table row and any divergence it earned stay behind.

### The cell repaints from the root because a touched control is pinned through every morph

UX LiveComponent's `UnsyncedInputsTracker` (the package ships only the built
[`live_controller.js`](../../../vendor/symfony/ux-live-component/assets/dist/live_controller.js),
so that bundle is where to read it)
records every input the user has touched — an `input` event anywhere under the
live root adds the element to a bucket it never empties — and
`beforeNodeMorphed` then stamps the **live** value back onto the incoming server
node for such an element. So a control the user has touched is never corrected
by a re-render: a refused write would leave the refused value on screen for
good, and a `poll()`ed change made by somebody else would never appear.

That is why the authoritative display state rides the cell **root** rather than
the control. `getClientState()` answers the rejected input while the host holds
one for this cell, else the record's own state — `'1'`/`'0'` for a boolean
control and the string form (`''` for `null`) otherwise — and the partial
renders it as the `sylament--tables--editable-cell` controller's `state` value.
The root is a `div`, which the tracker never touches, so it morphs normally; the
[controller](../../../packages/tables/assets/src/controllers/editable_cell_controller.ts)
writes that value onto the control on `connect()`, on `stateValueChanged()` and
on every `render:finished`, skipping only while its own write is in flight or
while the control is focused and its value differs from the root's (a text input
mid-edit under a poll). It is the same fact the `table` controller's checkbox
repaint exists for.

The ordering that makes the repaint the *last* word is UX's own, and it is worth
knowing before reading the controller's `finally`: `Component.performRequest()`
calls `processRerender()` — whose closing line triggers `render:finished` — and
only *then* resolves the promise `component.action()` handed back. So by the time
the controller's `await` returns, the morph has already run and the repaint
subscribed to `render:finished` has already fired. That is why the `finally`
restores the control's `disabled` **unconditionally** to what it was before the
write, rather than deferring to whatever the fresh markup carried, and why it
calls the repaint once more on the way out: the two are the controller's own
last-writer-wins pass over a DOM the server has already replaced.

The PHP suite cannot see the repaint — it renders markup, it does not run
Stimulus — so the tests pin the two halves it can: that the root carries the
right `state` value, and that a refusal echoes the input back into the control.

## Divergences

### The refusal is request-scoped and server-rendered where Filament's lives on the client

Filament's editable columns hold their validation error in Alpine state on the
cell and re-render nothing: `updateTableColumnState()` throws, Livewire returns
the message, and the client paints it. UX LiveComponent has no such channel —
`component.action()` resolves to a `BackendResponse`, never a PHP value — so the
port catches schemas'
[`ValidationException`](../schemas/can-be-validated.md) into
`$tableColumnStateErrors[$column][$recordKey]` and lets the re-render paint it:
`<p class="sy-ta-cell-error">` under the control, `aria-invalid="true"` on the
control, and the rejected input echoed back as its value.

The bag is a plain property, deliberately **not** a `LiveProp`. It lives for the
request that produced it, the re-render that request answers with paints it, and
the next request starts clean — so a refusal is never replayed, and nothing about
it is client-writable. `getTableColumnStateError()` is on the contract because
the partial reads it through the column's `getUpdateStateError()`, which asks the
host by column name and record key.

### An array record throws where Filament writes nothing

Filament's `CanUpdateState::updateState()` returns `null` when the record is not
an Eloquent model. The port throws instead — an editable column on the
`records()` array lane has nowhere to write, and silently discarding a user's
edit is worse than saying so:

> The editable column [title] has no record to write to on the records() lane;
> give it updateStateUsing().

`updateStateUsing()` is the escape, and it is checked first, so the array lane is
editable the moment the application says how.

### The pivot-column arm has nothing to write through

Filament's `updateState()` carries a third target beside "the record" and "the
far side of a to-one relationship": when the **table** is scoped to a
`BelongsToMany` and the column names one of that relation's pivot columns, the
write lands on the pivot row rather than on either entity
(`$record->getRelationValue($tableRelationship->getPivotAccessor())`). That arm
is skipped, and the reason is structural rather than a judgment. Eloquent's
`BelongsToMany` hands back a pivot **model** with its own attributes, which is
what makes it a write target; Doctrine's many-to-many is a join table with no
entity behind it, so
[`Table::relationship()`](table.md#the-relationship-query-is-the-base-query-rooted-at-the-target)
roots its query at the *target* entity and there is no third object to address.
An application that wants columns on the join models it as an entity in its own
right — Doctrine's own advice — at which point the association is two to-manys
and the pivot is an ordinary record the dotted path already reaches.

### The write is not authorization-checked

Filament carries a `// Security:` comment on each editable column saying the
write bypasses Laravel model policies and that `disabled()` is the restriction.
The port carries the same comment at the write site itself — the one place a
reader can act on it — with one thing added: the toolkit has **no** authorization
layer at all, so `disabled()` is not merely the recommended gate, it is the only
one. Anything stricter belongs on a page or modal action the application guards
itself. The same divergence is recorded on
[`column.md`](column.md#the-editable-write-is-not-authorization-checked).

### Validation speaks Symfony constraints and `rules()` is not ported

Filament's tables-tier `CanBeValidated` is a Laravel rule bag — `rules()`,
`getRules()`, `validationAttribute()` and an implicit `'nullable'`. The port
speaks constraints instead: `constraint()`, `constraints()` and
`validationMessages()` keyed by constraint class, exactly as forms'
[`CanBeValidated`](../forms/can-be-validated.md) does, and `validate()` runs them
through `PresenceGate::apply()` before Symfony's validator. `renames.md`'s
[rule-vocabulary section](../renames.md) already rules that a missing `rules()`
reads louder than a kept name that means something else; Symfony messages carry
no attribute name, so `validationAttribute()` has nothing to fill; and
nullability is the **absence** of a presence constraint rather than a rule.

The validator arrives by `#[Required] setValidator(ValidatorInterface)` and
`getValidator()` throws when it is unset, rather than falling back to
`Validation::createValidator()` — the fallback would fatal on any constraint
whose validator is a container service (`unique()`, `exists()`), which is exactly
the case a fallback would be reached for.

### A live action answers nothing, so the return value is dropped

Filament's `callTableColumnAction()` returns whatever the column's closure
returned, and Livewire hands that back to the browser. UX LiveComponent has no
such channel: `component.action()` resolves to a `BackendResponse`, never the
PHP return value, so a returned value would be unreachable by construction. The
port therefore types the live action `void` and the closure's own return is
discarded — the re-render is the whole answer, which is the same rule the rest of
the toolkit's live actions follow.

A closure that wants to change what the user sees writes host state (a
`LiveProp`, the record through Doctrine) and lets the re-render show it, rather
than returning something.

## Semantics

### A hidden column is unreachable from the wire, on both actions

Both live actions refuse a column whose `isHidden()` answers true, and they
refuse the **whole** call rather than only the write: a silent early `return`,
no error-bag entry and no exception, exactly as vendor's two `return null`s. A
column the user cannot see was never offered by a rendered page, so a name
posted for one is a forged argument like any other, and the shape a forged
argument earns here is the shape every other refusal in these two actions
already has.

The gate reads the **column's own** hidden state — the `hidden()` / `visible()`
closures [`CanBeHidden`](column.md) evaluates against this host — never a
client-supplied column set and never the column manager's applied state. A
column toggled off through the manager is `isToggledHidden()`, which is a
different question and deliberately not asked: the user chose to stop looking at
it, which is not the same as being forbidden it. Refusing their own writes on
the strength of a preference they set for themselves would protect nobody, and
[`Column::isToggleable()`](../../../packages/tables/src/Columns/Concerns/CanBeToggled.php)
asks `isHidden()` on its way to answering, so a gate that folded the toggle back
in would ask itself. The rendered set — the one
[`Table::getVisibleColumns()`](table.md) memoises — is the intersection of the
two, and it is what the partial walks; the gate is not. None of that is visible
from a suite that only ever posts columns the user left on, so both fixtures
carry a `toggleable(isToggledHiddenByDefault: true)` column beside their hidden
one and an Integration case apiece asserts `isToggledHidden()` first and then
that the call went through — the pair that fails the moment either gate grows an
`|| $column->isToggledHidden()`, which is a plausible-looking change and the
wrong one.

Position is vendor's in both, and the two differ because the cheap check comes
first in each. `updateTableColumnState()` resolves the column by name, requires
it to be a `Column` and `Editable`, **then** asks whether it is hidden, and only
then resolves the record, binds it and asks `isDisabled()`.
`callTableColumnAction()` resolves the record **first**, through
[`getTableRecord()`](has-table.md), so the table's filters are re-applied and a
record the user cannot reach is simply absent; then the column by name; **then**
the hidden gate; then the action, which must be a `Closure` — an `Action`
instance mounts through the modal lane instead and a `null` action does nothing.

The gate is the **closure lane and the write lane, not the wire as a whole**.
[`Table::columns()`](table.md) caches an `Action` for every column it is handed,
hidden ones included, so a hidden column's action object is still resolvable by
name out of `$flatActions` and `mountAction` still opens its modal. Vendor
caches unconditionally too and gates only these two live actions, so this is
parity rather than a hole left behind — but it is why a column action that must
be unreachable while its column is hidden carries its own `visible()` on the
`Action`.

The gate stays correct under a column reorder. `Table::columns()` called a
second time does not evict the first set's cached column actions
([table.md](table.md)), but neither action reads that cache: each resolves its
column by name off the table as it stands after the manager's sync, so the
column it asks `isHidden()` of is the one the current definition holds. The
fixture is reorderable for exactly that reason, so every case in this file runs
on a table the manager has already re-`columns()`ed.

### A JSON `null` input arrives as `null` without a declared default

UX LiveComponent turns each `#[LiveArg]` into a request attribute, and its filter
drops any argument whose value is `null` — so a client posting `input: null`
sends nothing the attribute resolver can see. `updateTableColumnState()`
therefore declares **no** default on `$input`: `mixed` is a nullable type, so
Symfony's `DefaultValueResolver` supplies `null` on its own, and a declared
default would only make that arrival indistinguishable from an argument the
client never sent. An Integration case posts `input => null` to pin it.

### The write goes through the accessor, so a dotted path walks the to-one chain

`CanUpdateState` writes with schemas'
[`PropertyAccessor::setOnRecord()`](../schemas/property-accessor.md), which is a
superset of Filament's relationship arm: `artist.name` walks the to-one
association and writes on the far side, and Doctrine's unit of work picks the
change up from the flush. A path whose leaf is an object **collection** is
refused by the accessor in the vocabulary of the schema component that would map
it; `CanUpdateState` catches that and re-throws it naming the column, which is
what the reader of a table has in hand.

One consequence has no Eloquent counterpart: a `<select>` and a text input post
**strings**, and the record's declared PHP type is what receives them. Symfony's
accessor is compiled without `strict_types`, so a *numeric* string reaches an
`int` or `float` property coerced and silent — but any other string raises its
`InvalidTypeException` (`Expected argument of type "?int", "string" given`), and
an object-typed property (`DateTimeImmutable`, a backed enum) has no coercion to
reach at all. Neither raise reaches the browser as a 500: the column seeds a
`Type` constraint from the record's own declared type, and what no seed can
catch the write seam files against the cell — see
[the type seed](#a-record-type-the-input-cannot-hold-seeds-a-type-constraint-and-the-write-refuses-the-rest). `CanUpdateState` therefore declares `getStateCasts()` — `[]` by
default, which is the truth for every control whose values are the record's own
— and `updateState()` runs each cast's `get()` over the posted value *after* the
blank normalisation and *before* the before-hook and the write, with
`getClientState()` running `set()` back in reverse order over the record's own
state so the control still speaks the wire vocabulary — a *refused* input is
echoed as it was posted, being that vocabulary already. One column overrides it today:
[`SelectColumn`](select-column.md#the-only-state-cast-a-column-carries-is-the-enum-arms)
seeds a schemas `EnumStateCast` for an enum option map, which is where Filament
would have leaned on an Eloquent cast. Order is what keeps validation honest —
the host validates the **wire** value, so a `Choice` seeded from string option
keys still judges the string the browser sent. A column over any other
object-typed property still takes an `updateStateUsing()` closure to cast. The
boolean columns are unaffected — the controller posts a JSON boolean for a
checkbox.

### The action binds the record **and** its key, and only one of the two invalidations is observable

`updateTableColumnState()` binds `record($tableRecord)->recordKey($record)`
before it asks `isDisabled()`, so every closure the write path evaluates —
`disabled()`, `updateStateUsing()`, `beforeStateUpdated()`, `afterStateUpdated()`
— is handed a column that knows both which record it is judging and under which
key. The key half reaches no *shipped* consumer inside the trait (the error bag
is keyed by the `$record` argument, and the partial's key comes from the row
loop), so it is pinned directly instead: an Integration case calls the action on
the container-built host and reads `getRecord()` / `getRecordKey()` back off the
column.

After the write the trait invalidates two caches, and they are not equally
provable. `clearCachedState()` is load-bearing and pinned: `getState()` memoises
per record key, so a hook that read the old state — the natural shape of a
`beforeStateUpdated()` comparing old against new — would leave the re-render
painting the value the user just replaced. `flushCachedTableRecords()` is
**defensive and unpinned**: on the query lane the cached page holds the very
entities Doctrine's identity map just mutated, so re-reading them changes
nothing, and nothing in a live request resolves the record page *before* the
action runs. Deleting it fails no test. It stays because the host is a public
seam — an application calling `getTableRecords()` earlier in the request, or a
future lane whose records are not identity-mapped, is exactly the case it covers
— and because the alternative to a cheap null-assignment is a stale page nobody
would think to look for.

### A record type that rejects null seeds a presence constraint

`getValidationConstraints()` prepends a
[`RequiredValue`](../forms/can-be-validated.md) when the record's declared
write-side type at the column's path rejects `null`
(`PropertyAccessor::acceptsNullOnRecord()`, forms' `isRequiredByRecord()`
precedent) and no presence constraint was declared. Without it, a blank select
placeholder written against a non-nullable property would reach the write and
raise a bare `TypeError`; with it, the blank is a refusal painted under the
control. A lane with no record class to read a type off — `records()` — seeds
nothing.

### A record type the input cannot hold seeds a type constraint, and the write refuses the rest

Beside the presence arm, `getValidationConstraints()` prepends a Symfony `Type`
built from the record's declared native type at the column's path
(`PropertyAccessor::getBuiltinTypeOnRecord()`, the nullability probe's twin) —
unless the column already declares a `Type` of its own, which is why
[`ToggleColumn`](toggle-column.md) and [`CheckboxColumn`](checkbox-column.md)
keep exactly the one they seed themselves. An `int` or `float` property asks for
`numeric` rather than its own name, because the accessor coerces a numeric string
and that is the only shape a control ever posts; a `string` property asks for
`string`. A `bool` property asks for **nothing**, and that is the same argument
run to its end: only a checkbox posts a JSON boolean, every other control posts
`control.value`, a string — so a derived `Type('bool')` would be reachable only
from [`TextInputColumn`](text-input-column.md) and
[`SelectColumn`](select-column.md), where it refuses every write the browser can
make, while the two columns whose control *does* post a boolean seed their own
and suppress it. The boolean arm is theirs. An enum, an object, a collection and
a type that cannot be read ask for nothing either — the enum arm belongs to
[`SelectColumn`](select-column.md)'s `Choice` and its state cast, and a column
over any other object-typed property takes an `updateStateUsing()` closure. Like
the presence seed, it is derived from the column's **own** path whether or not
such a closure redirects the write — a column writing somewhere else declares the
constraints it wants itself, and a declared `Type` is what turns the seed off.

That leaves a gap the seed cannot close, and the write seam closes it instead:
where nothing could be derived, `CanUpdateState` raises its own
[`RecordWriteException`](../../../packages/tables/src/Exceptions/RecordWriteException.php)
and `updateTableColumnState()` files it into the error bag exactly as it files a
validation refusal, so the cell paints the refusal and the request answers 200.
The catch is that class alone, deliberately: a throwing `updateStateUsing()` is
user code and still propagates, as does a misconfigured column's own
`LogicException`. All four arms — the two seeded ones, the object property no
seed reaches, and the throwing override — are pinned over the
[`TypeRefusalSongsTable`](../../../packages/tables/tests/Fixtures/Twig/Components/TypeRefusalSongsTable.php)
fixture, whose columns carry no constraints of their own so the derivation is the
only thing under test.
