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
| [`callTableColumnAction(string $name, string $recordKey)` — run a column's `action()` closure for one record](../../../packages/tables/src/Concerns/HasColumns.php) | [`HasColumns::callTableColumnAction()`](../../../vendor/filament/tables/src/Concerns/HasColumns.php) | done | — | [diverges](#a-live-action-answers-nothing-so-the-return-value-is-dropped) · [detail](#the-hidden-column-gate-waits-on-column-visibility) |
| [`updateTableColumnState(string $column, string $record, mixed $input)` — the editable columns' write-back](../../../packages/tables/src/Concerns/HasColumns.php) | [`HasColumns::updateTableColumnState()`](../../../vendor/filament/tables/src/Concerns/HasColumns.php) | done | — | [diverges](#a-live-action-answers-nothing-so-the-return-value-is-dropped) · [semantics](#a-json-null-input-arrives-as-null-without-a-declared-default) · [detail](#the-hidden-column-gate-waits-on-column-visibility) |
| [`getTableColumnStateError()` + the request-scoped `$tableColumnStateErrors` bag](../../../packages/tables/src/Concerns/HasColumns.php) | — (Sylament-only, replacing Filament's client-held error) | done | — | [diverges](#the-refusal-is-request-scoped-and-server-rendered-where-filaments-lives-on-the-client) |
| [`Columns\Concerns\CanUpdateState` — the column half of the write](../../../packages/tables/src/Columns/Concerns/CanUpdateState.php) | [`Columns\Concerns\CanUpdateState`](../../../vendor/filament/tables/src/Columns/Concerns/CanUpdateState.php) | done | — | [semantics](#the-write-goes-through-the-accessor-so-a-dotted-path-walks-the-to-one-chain) · [diverges](#an-array-record-throws-where-filament-writes-nothing) · [diverges](#the-write-is-not-authorization-checked) |
| [`Columns\Concerns\CanBeValidated` — the column half of the refusal](../../../packages/tables/src/Columns/Concerns/CanBeValidated.php) | [`Columns\Concerns\CanBeValidated`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeValidated.php) | done | — | [diverges](#validation-speaks-symfony-constraints-and-rules-is-not-ported) · [semantics](#a-record-type-that-rejects-null-seeds-a-presence-constraint) |
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
host and would let a refusal pass whatever the write actually did.

`callTableColumnAction()`'s five arms are covered by
[`HasColumnsTest`](../../../packages/tables/tests/Integration/Concerns/HasColumnsTest.php),
which calls the live action on a real live host over the wire and reads what the
bound closure recorded off a `LiveProp`: the record and its key reaching the
closure, an unknown record key, an unknown column name, a column carrying no
action at all, and the closure a `string` action name was wrapped into calling
its host method. The wiring that fires it — the cell wrapper's `button` arm
choosing `callTableColumnAction` over `mountAction` by
[`Column::getActionKind()`](column.md) — is pinned in
[`TableRenderingTest`](../../../packages/tables/tests/Integration/TableRenderingTest.php).

## Details

Specifics for rows that are not finished — what is already in, what is left, and
what the next step is. Delete a subsection when its row flips to **done**; the
table row and any divergence it earned stay behind.

### The hidden-column gate waits on column visibility

Filament refuses the call when `$column->isHidden()` — a column the user cannot
see must not be reachable from the wire, whatever the client posts. `hidden()`,
`visible()` and `toggleable()` are unported (their rows are on
[`column.md`](column.md)), so there is no predicate to ask and the guard is
absent rather than stubbed true. It lands with `CanBeHidden`, in the same change
that gives `updateTableColumnState()` its copy of the same gate.

Nothing else is missing from the resolution order, and the order itself is
vendor's: the record resolves **first**, through
[`getTableRecord()`](has-table.md), so the table's filters are re-applied and a
record the user cannot reach is simply absent; then the column by name off the
table; then the action, which must be a `Closure` — an `Action` instance mounts
through the modal lane instead and a `null` action does nothing. Each refusal
returns without effect, exactly as vendor's returns `null`.

### The cell repaints from the root because a touched control is pinned through every morph

UX LiveComponent's
[`UnsyncedInputsTracker`](../../../vendor/symfony/ux-live-component/assets/src/Component/UnsyncedInputsTracker.ts)
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
**strings**, and the record's declared PHP type is what receives them. A column
over an `int`-typed property therefore needs an `updateStateUsing()` closure to
cast, where Filament would have leaned on an Eloquent cast. The boolean columns
are unaffected — the controller posts a JSON boolean for a checkbox.

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
