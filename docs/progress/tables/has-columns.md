---
description: 'The host-tier HasColumns live actions and the column traits behind an editable cell: the write, its validation, the refusal bag and the repaint.'
ports:
  - vendor/filament/tables/src/Concerns/HasColumns.php
  - vendor/filament/tables/src/Columns/Concerns/CanBeValidated.php
  - vendor/filament/tables/src/Columns/Concerns/CanUpdateState.php
  - vendor/filament/tables/src/Columns/Contracts/Editable.php
  - vendor/filament/tables/resources/js/components/columns/checkbox.js
  - vendor/filament/tables/resources/js/components/columns/select.js
  - vendor/filament/tables/resources/js/components/columns/text-input.js
  - vendor/filament/tables/resources/js/components/columns/toggle.js
owns:
  - packages/tables/src/Columns/Concerns/CanBeValidated.php
  - packages/tables/src/Columns/Concerns/CanUpdateState.php
  - packages/tables/src/Concerns/HasColumns.php
---
# HasColumns (tables host)

[`Sylament\Tables\Concerns\HasColumns`](../../../packages/tables/src/Concerns/HasColumns.php) is the **host**-tier column concern [`HasTable`](has-table.md) composes: the live actions a cell's own controls fire back into.
It is not [`Sylament\Tables\Table\Concerns\HasColumns`](../../../packages/tables/src/Table/Concerns/HasColumns.php), the **table**-tier trait of the same name that owns `columns()` / `getColumns()` / `getColumn()` and folds into [`table.md`](table.md); Filament ships both names in both tiers and the port keeps them, so read the import, never the bare `use`.
`updateTableColumnState()` writes one editable cell's value onto a record, where the `…TableColumnState` family on [`Concerns\HasColumnManager`](column-manager.md) means the column manager's visibility-and-order bag; both vocabularies are Filament's own and both flatten into the same host class ([why they were kept](column-manager.md#two-tablecolumnstate-vocabularies-meet-on-one-host-and-both-are-filaments)).
`updateTableColumnState()` is the only channel the editable columns ([`ToggleColumn`](toggle-column.md), [`CheckboxColumn`](checkbox-column.md), [`SelectColumn`](select-column.md), [`TextInputColumn`](text-input-column.md)) write through.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`callTableColumnAction(string $name, string $recordKey)` — run a column's `action()` closure for one record](../../../packages/tables/src/Concerns/HasColumns.php) | [`HasColumns::callTableColumnAction()`](../../../vendor/filament/tables/src/Concerns/HasColumns.php) | done | — | [diverges](#a-live-action-answers-nothing-so-the-return-value-is-dropped) · [semantics](#a-hidden-column-is-unreachable-from-the-wire-on-both-actions) |
| [`updateTableColumnState(string $column, string $record, mixed $input)` — the editable columns' write-back](../../../packages/tables/src/Concerns/HasColumns.php) | [`HasColumns::updateTableColumnState()`](../../../vendor/filament/tables/src/Concerns/HasColumns.php) | done | — | [diverges](#a-live-action-answers-nothing-so-the-return-value-is-dropped) · [semantics](#a-record-type-the-input-cannot-hold-seeds-a-type-constraint-and-the-write-refuses-the-rest) · [semantics](#a-json-null-input-arrives-as-null-without-a-declared-default) · [semantics](#the-action-binds-the-record-and-its-key-and-only-one-of-the-two-invalidations-is-observable) · [semantics](#a-hidden-column-is-unreachable-from-the-wire-on-both-actions) |
| [`getTableColumnStateError()` + the request-scoped `$tableColumnStateErrors` bag](../../../packages/tables/src/Concerns/HasColumns.php) | — (Sylament-only, replacing Filament's client-held error) | done | — | [diverges](#the-refusal-is-request-scoped-and-server-rendered-where-filaments-lives-on-the-client) |
| [`Editable`](../../../packages/tables/src/Columns/Contracts/Editable.php) (`validate()` / `updateState()`) | [`Columns/Contracts/Editable.php`](../../../vendor/filament/tables/src/Columns/Contracts/Editable.php) | done | — | — |
| [`Columns\Concerns\CanUpdateState` — the column half of the write](../../../packages/tables/src/Columns/Concerns/CanUpdateState.php) | [`Columns\Concerns\CanUpdateState`](../../../vendor/filament/tables/src/Columns/Concerns/CanUpdateState.php) | done | — | [semantics](#the-write-goes-through-the-accessor-so-a-dotted-path-walks-the-to-one-chain) · [diverges](#an-array-record-throws-where-filament-writes-nothing) · [diverges](#the-pivot-column-arm-has-nothing-to-write-through) · [diverges](#the-write-is-not-authorization-checked) · [diverges](#a-changed-identifier-is-the-cells-own-error) |
| [`Columns\Concerns\CanBeValidated` — the column half of the refusal](../../../packages/tables/src/Columns/Concerns/CanBeValidated.php) | [`Columns\Concerns\CanBeValidated`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeValidated.php) | done | — | [diverges](#validation-speaks-symfony-constraints-and-rules-is-not-ported) · [semantics](#a-record-type-that-rejects-null-seeds-a-presence-constraint) · [semantics](#a-record-type-the-input-cannot-hold-seeds-a-type-constraint-and-the-write-refuses-the-rest) |
| `rules()` / `getRules()` / `validationAttribute()` / `getValidationAttribute()` | [`Columns\Concerns\CanBeValidated`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeValidated.php) | skipped | — | [rename](../renames.md#laravel-rule-vocabulary-becomes-symfony-constraint-vocabulary) · [diverges](#validation-speaks-symfony-constraints-and-rules-is-not-ported) |
| [`getClientState()` — the display state the cell root publishes](../../../packages/tables/src/Columns/Concerns/CanUpdateState.php) | — (Sylament-only, for the repaint) | done | — | [semantics](#the-cell-repaints-from-the-root-because-a-touched-control-is-pinned-through-every-morph) |
| [`sylament--tables--editable-cell` — commit through the host, repaint from the cell root](../../../packages/tables/assets/src/controllers/editable_cell_controller.ts) | [`components/columns/checkbox.js`](../../../vendor/filament/tables/resources/js/components/columns/checkbox.js), [`select.js`](../../../vendor/filament/tables/resources/js/components/columns/select.js), [`text-input.js`](../../../vendor/filament/tables/resources/js/components/columns/text-input.js), [`toggle.js`](../../../vendor/filament/tables/resources/js/components/columns/toggle.js) | done | — | [diverges](#one-editable-cell-controller-stands-in-for-filaments-four-column-components) · [semantics](#the-cell-repaints-from-the-root-because-a-touched-control-is-pinned-through-every-morph) |
| `callTableColumnMethod()` — dispatch to an `#[ExposedLivewireMethod]` on a column | [`HasColumns::callTableColumnMethod()`](../../../vendor/filament/tables/src/Concerns/HasColumns.php) | blocked | the select-engine promotion ([forms-bundle.md](../forms/forms-bundle.md#the-select-controller-binds-a-port-of-filaments-select-utility-to-the-live-host)) | [detail](#the-method-dispatcher-waits-on-its-only-consumer-the-searchable-select-column) |
| `getTableColumns()` — deprecated for overriding `table()` | [`HasColumns::getTableColumns()`](../../../vendor/filament/tables/src/Concerns/HasColumns.php) | skipped | — | [deprecated: override `table()`](../renames.md#a-deprecated-filament-member-is-not-ported) |

## Details

### The method dispatcher waits on its only consumer, the searchable select column

Filament's only consumer of `callTableColumnMethod()` is `SelectColumn`'s searchable arm, whose exposed methods feed the JS select engine.
The port ships `SelectColumn` native-only ([select-column.md](select-column.md#the-js-control-arms-wait-on-the-select-engine-reaching-tables)), so the dispatcher would have no method to reach until that engine is promoted out of forms.

## Semantics

### The cell repaints from the root because a touched control is pinned through every morph

UX LiveComponent's `UnsyncedInputsTracker` (the package ships only the built [`live_controller.js`](../../../vendor/symfony/ux-live-component/assets/dist/live_controller.js), so that bundle is where to read it) records every input the user has touched, in a bucket an `input` event anywhere under the live root adds to and nothing empties, and `beforeNodeMorphed` then stamps the **live** value back onto the incoming server node for such an element.
So a control the user has touched is never corrected by a re-render: a refused write would leave the refused value on screen for good, and a `poll()`ed change made by somebody else would never appear.

That is why the authoritative display state rides the cell **root** rather than the control.
`getClientState()` answers the rejected input while the host holds one for this cell, else the record's own state — `'1'`/`'0'` for a boolean control and the string form (`''` for `null`) otherwise — and the partial renders it as the `sylament--tables--editable-cell` controller's `state` value.
The root is a `div`, which the tracker never touches, so it morphs normally; the [controller](../../../packages/tables/assets/src/controllers/editable_cell_controller.ts) writes that value onto the control on `connect()`, on `stateValueChanged()` and on every `render:finished`, skipping only while its own write is in flight or while the control is focused and its value differs from the root's (a text input mid-edit under a poll).
The selection checkboxes repaint from the store for the same reason: after the first morph the client state, not the server-rendered attribute, is what the DOM shows unless something puts the server's answer back.

The ordering that makes the repaint the *last* word is UX's own, and it is worth knowing before reading the controller's `finally`: `Component.performRequest()` calls `processRerender()`, whose closing line triggers `render:finished`, and only *then* resolves the promise `component.action()` handed back.
So by the time the controller's `await` returns, the morph has already run and the repaint subscribed to `render:finished` has already fired.
That is why the `finally` restores the control's `disabled` **unconditionally** to what it was before the write, rather than deferring to whatever the fresh markup carried, and why it calls the repaint once more on the way out: the two are the controller's own last-writer-wins pass over a DOM the server has already replaced.

### A hidden column is unreachable from the wire, on both actions

Both live actions refuse a column whose `isHidden()` answers true, and they refuse the **whole** call rather than only the write: a silent early `return`, no error-bag entry and no exception, exactly as vendor's two `return null`s.
A column the user cannot see was never offered by a rendered page, so a name posted for one is a forged argument like any other, and the shape a forged argument earns here is the shape every other refusal in these two actions already has.

The gate reads the **column's own** hidden state — the `hidden()` / `visible()` closures [`CanBeHidden`](column.md) evaluates against this host — never a client-supplied column set and never the column manager's applied state.
A column toggled off through the manager is `isToggledHidden()`, which is a different question and deliberately not asked: the user chose to stop looking at it, which is not the same as being forbidden it.
Refusing their own writes on the strength of a preference they set for themselves would protect nobody, and [`Column::isToggleable()`](../../../packages/tables/src/Columns/Concerns/CanBeToggled.php) asks `isHidden()` on its way to answering, so a gate that folded the toggle back in would ask itself.
The rendered set — the one [`Table::getVisibleColumns()`](table.md) memoises — is the intersection of the two, and it is what the partial walks; the gate is not.
A gate that grows an `|| $column->isToggledHidden()` is a plausible-looking change and the wrong one.

Position is vendor's in both, and the two differ because the cheap check comes first in each.
`updateTableColumnState()` resolves the column by name, requires it to be a `Column` and `Editable`, **then** asks whether it is hidden, and only then resolves the record, binds it and asks `isDisabled()`.
`callTableColumnAction()` resolves the record **first**, through [`getTableRecord()`](has-table.md), so the table's filters are re-applied and a record the user cannot reach is simply absent; then the column by name; **then** the hidden gate; then the action, which must be a `Closure` — an `Action` instance mounts through the modal lane instead and a `null` action does nothing.

The gate is the **closure lane and the write lane, not the wire as a whole**.
[`Table::columns()`](table.md) caches an `Action` for every column it is handed, hidden ones included, so a hidden column's action object is still resolvable by name out of `$flatActions` and `mountAction` still opens its modal.
Vendor caches unconditionally too and gates only these two live actions, so this is parity rather than a hole left behind, and it is why a column action that must be unreachable while its column is hidden carries its own `visible()` on the `Action`.

The gate stays correct under a column reorder.
`Table::columns()` called a second time does not evict the first set's cached column actions ([table.md](table.md)), but neither action reads that cache: each resolves its column by name off the table as it stands after the manager's sync, so the column it asks `isHidden()` of is the one the current definition holds.

### A JSON `null` input arrives as `null` without a declared default

UX LiveComponent copies a `#[LiveArg]` into a request attribute only when `isset()` holds for it ([`LiveComponentSubscriber`](../../../vendor/symfony/ux-live-component/src/EventListener/LiveComponentSubscriber.php)), so a client posting `input: null` sends nothing the attribute resolver can see.
`updateTableColumnState()` therefore declares **no** default on `$input`: `mixed` is a nullable type, so Symfony's `DefaultValueResolver` supplies `null` on its own, and a declared default would only make that arrival indistinguishable from an argument the client never sent.

### The write goes through the accessor, so a dotted path walks the to-one chain

`CanUpdateState` writes with schemas' [`PropertyAccessor::setOnRecord()`](../schemas/property-accessor.md), which is a superset of Filament's relationship arm: `artist.name` walks the to-one association and writes on the far side, and Doctrine's unit of work picks the change up from the flush.
A path whose leaf is an object **collection** is refused by the accessor in the vocabulary of the schema component that would map it; `CanUpdateState` catches that and re-throws it naming the column, which is what the reader of a table has in hand.

One consequence has no Eloquent counterpart: a `<select>` and a text input post **strings**, and the record's declared PHP type is what receives them.
Symfony's accessor is compiled without `strict_types`, so a *numeric* string reaches an `int` or `float` property coerced and silent, but any other string raises its `InvalidTypeException` (`Expected argument of type "?int", "string" given`), and an object-typed property (`DateTimeImmutable`, a backed enum) has no coercion to reach at all.
Neither raise reaches the browser as a 500: the column seeds a `Type` constraint from the record's own declared type, and what no seed can catch the write seam files against the cell — see [the type seed](#a-record-type-the-input-cannot-hold-seeds-a-type-constraint-and-the-write-refuses-the-rest).
`CanUpdateState` therefore declares `getStateCasts()` — `[]` by default, which is the truth for every control whose values are the record's own — and `updateState()` runs each cast's `get()` over the posted value *after* the blank normalisation and *before* the before-hook and the write, with `getClientState()` running `set()` back in reverse order over the record's own state so the control still speaks the wire vocabulary; a *refused* input is echoed as it was posted, being that vocabulary already.
[`SelectColumn`](select-column.md#the-only-state-cast-a-column-carries-is-the-enum-arms) alone overrides it, seeding a schemas `EnumStateCast` for an enum option map, which is where Filament would have leaned on an Eloquent cast.
Order is what keeps validation honest: the host validates the **wire** value, so a `Choice` seeded from string option keys still judges the string the browser sent.
A column over any other object-typed property still takes an `updateStateUsing()` closure to cast.
The boolean columns are unaffected, because the controller posts a JSON boolean for a checkbox.

### The action binds the record **and** its key, and only one of the two invalidations is observable

`updateTableColumnState()` binds `record($tableRecord)->recordKey($record)` before it asks `isDisabled()`, so every closure the write path evaluates — `disabled()`, `updateStateUsing()`, `beforeStateUpdated()`, `afterStateUpdated()` — is handed a column that knows both which record it is judging and under which key.
The key half reaches no *shipped* consumer inside the trait: the error bag is keyed by the `$record` argument, and the partial's key comes from the row loop.

After the write the trait invalidates two caches, and they are not equally load-bearing.
`clearCachedState()` matters: `getState()` memoises per record key, so a hook that read the old state — the natural shape of a `beforeStateUpdated()` comparing old against new — would leave the re-render painting the value the user just replaced.
`flushCachedTableRecords()` is **defensive**: on the query lane the cached page holds the very entities Doctrine's identity map just mutated, so re-reading them changes nothing, and nothing in a live request resolves the record page *before* the action runs.
It stays because the host is a public seam — an application calling `getTableRecords()` earlier in the request, or a lane whose records are not identity-mapped, is exactly the case it covers — and because the alternative to a cheap null-assignment is a stale page nobody would think to look for.

### A record type that rejects null seeds a presence constraint

`getValidationConstraints()` prepends a [`RequiredValue`](../forms/can-be-validated.md) when the record's declared write-side type at the column's path rejects `null` (`PropertyAccessor::acceptsNullOnRecord()`, forms' `isRequiredByRecord()` precedent) and no presence constraint was declared.
Without it, a blank select placeholder written against a non-nullable property would reach the write and raise a bare `TypeError`; with it, the blank is a refusal painted under the control.
A lane with no record class to read a type off — `records()` — seeds nothing.

### A record type the input cannot hold seeds a type constraint, and the write refuses the rest

Beside the presence arm, `getValidationConstraints()` prepends a Symfony `Type` built from the record's declared native type at the column's path (`PropertyAccessor::getBuiltinTypeOnRecord()`, the nullability probe's twin) — unless the column already declares a `Type` of its own, which is why [`ToggleColumn`](toggle-column.md) and [`CheckboxColumn`](checkbox-column.md) keep exactly the one they seed themselves.
An `int` or `float` property asks for `numeric` rather than its own name, because the accessor coerces a numeric string and that is the only shape a control ever posts; a `string` property asks for `string`.
A `bool` property asks for **nothing**, and that is the same argument run to its end: only a checkbox posts a JSON boolean, every other control posts `control.value`, a string — so a derived `Type('bool')` would be reachable only from [`TextInputColumn`](text-input-column.md) and [`SelectColumn`](select-column.md), where it refuses every write the browser can make, while the boolean columns, whose control *does* post a boolean, seed their own and suppress it.
An enum, an object, a collection and a type that cannot be read ask for nothing either: the enum arm belongs to [`SelectColumn`](select-column.md)'s `Choice` and its state cast, and a column over any other object-typed property takes an `updateStateUsing()` closure.
Like the presence seed, it is derived from the column's **own** path whether or not such a closure redirects the write; a column writing somewhere else declares the constraints it wants itself, and a declared `Type` is what turns the seed off.

That leaves a gap the seed cannot close, and the write seam closes it instead: where nothing could be derived, `CanUpdateState` raises its own [`RecordWriteException`](../../../packages/tables/src/Exceptions/RecordWriteException.php) and `updateTableColumnState()` files it into the error bag exactly as it files a validation refusal, so the cell paints the refusal and the request answers 200.
The catch is that class alone, deliberately: a throwing `updateStateUsing()` is user code and still propagates, as does a misconfigured column's own `LogicException`.
The same route carries a second refusal, [a changed identifier](#a-changed-identifier-is-the-cells-own-error).

## Divergences

### The refusal is request-scoped and server-rendered where Filament's lives on the client

Filament's editable columns hold their validation error in Alpine state on the cell and re-render nothing: `updateTableColumnState()` returns the message, Livewire hands it to the client, and the client paints it.
UX LiveComponent has no such channel — `component.action()` resolves to a `BackendResponse`, never a PHP value — so the port catches schemas' [`ValidationException`](../schemas/can-be-validated.md) into `$tableColumnStateErrors[$column][$recordKey]` and lets the re-render paint it: `<p class="sy-ta-cell-error">` under the control, `aria-invalid="true"` on the control, and the rejected input echoed back as its value.

The bag is a plain property, deliberately **not** a `LiveProp`.
It lives for the request that produced it, the re-render that request answers with paints it, and the next request starts clean, so a refusal is never replayed and nothing about it is client-writable.
`getTableColumnStateError()` is on the contract because the partial reads it through the column's `getUpdateStateError()`, which asks the host by column name and record key.

### One editable-cell controller stands in for Filament's four column components

Filament registers four Alpine components for the editable columns — `columns/checkbox`, `columns/select`, `columns/text-input` and `columns/toggle` — each posting its state through `$wire.updateTableColumnState()` and adopting the server's state after every response.
The port ships **one** Stimulus controller, `sylament--tables--editable-cell`, because the four share that seam and differ only in which DOM property carries the value: a checkbox posts `checked` as a JSON boolean, every other control posts its string `value`.
The commit is the controller's `change` action; it disables the control while the write is in flight and [repaints from the cell root](#the-cell-repaints-from-the-root-because-a-touched-control-is-pinned-through-every-morph) once the re-render lands.
Vendor's `select.js` also drives the searchable select engine, which is the blocked [`callTableColumnMethod()`](#the-method-dispatcher-waits-on-its-only-consumer-the-searchable-select-column) row's arm.

### A changed identifier is the cell's own error

Filament writes a cell over a primary key like any other column, and Eloquent updates the key.
Doctrine keys its identity map by the old one, so an editable cell left to write an identifier field would re-key the row silently, or — the new key being taken — throw a unique violation out of `updateTableColumnState()`.
So `CanUpdateState::updateState()` runs the record check's changed-key pass itself, after the write and before its flush, on the **table's** association reader rebound to the record's own manager — `$this->getTable()->getAssociationReader()->withEntityManager($manager)` — so a reader the application subclassed or decorated runs here as it does under the form ([the pass](../schemas/association-reader.md#a-changed-identifier-is-put-back-and-refused-and-costs-no-statement)).
Its refusals are raised the way the form raises them: as violations of a [`RecordKeyGuard`](../../../packages/schemas/src/Validation/RecordKeyGuard.php) constraint, validated by the column's own validator — the one [`CanBeValidated`](../../../packages/tables/src/Columns/Concerns/CanBeValidated.php) already holds for the input, which `CanUpdateState` declares as an abstract `getValidator(): ValidatorInterface`, so a hand-written column composing `CanUpdateState` without `CanBeValidated` defines the method or fails at class load — and the first violation's message is thrown as a [`RecordWriteException`](../../../packages/tables/src/Exceptions/RecordWriteException.php), which the host files and the cell paints exactly as it does [the type refusal](#a-record-type-the-input-cannot-hold-seeds-a-type-constraint-and-the-write-refuses-the-rest).
So *"This value cannot be changed."* goes through the validator's translator in its translation domain, and an application's `translations/validators.*` reaches the cell as it reaches the form.
The toolkit ships no catalog of its own, so without the application's the message stays English.
The pass has already put the key back, so the row stays keyed as it was posted, the re-render keeps the error on its cell rather than losing it under a new key, and nothing is written.

Two edges follow from the cell reading the table's reader and the column's validator.
The validator is reached only when the pass refuses something, so a column built by hand without one writes normally until it changes a key, and then throws the `LogicException` naming the missing validator — after the key is put back and before any statement.
And a column mounted to no table cannot write through the default arm: it has no reader to rebind, so `getTable()` throws *"The column [...] is not mounted to a table."*, even where `getEntityManagerForRecord()` could have found a manager through the registry.
No host reaches that case — every cell renders from a table, and `getUpdateStateError()` asks the table for every keyed cell — so it is recorded rather than handled.

It is the changed-key pass alone: a cell writes a managed record, so there is no new record whose key could be taken.
A cell writing the same key back in another type — an integer id arrives as the string a control posts — is no change.
A `SelectColumn` over an **association** key half keeps the type refusal it already has, which answers first.

### An array record throws where Filament writes nothing

Filament's `CanUpdateState::updateState()` returns `null` when the record is not an Eloquent model.
The port throws instead — an editable column on the `records()` array lane has nowhere to write, and silently discarding a user's edit is worse than saying so:

> The editable column [title] has no record to write to on the records() lane;
> give it updateStateUsing().

`updateStateUsing()` is the escape, and it is checked first, so the array lane is editable the moment the application says how.

### The pivot-column arm has nothing to write through

Filament's `updateState()` carries a third target beside "the record" and "the far side of a to-one relationship": when the **table** is scoped to a `BelongsToMany` and the column names one of that relation's pivot columns, the write lands on the pivot row rather than on either entity (`$record->getRelationValue($tableRelationship->getPivotAccessor())`).
That arm is skipped, and the reason is structural rather than a judgment.
Eloquent's `BelongsToMany` hands back a pivot **model** with its own attributes, which is what makes it a write target; Doctrine's many-to-many is a join table with no entity behind it, so [`Table::relationship()`](has-query.md#the-relationship-query-is-the-base-query-rooted-at-the-target) roots its query at the *target* entity and there is no third object to address.
An application that wants columns on the join models it as an entity in its own right — Doctrine's own advice — at which point the association is two to-manys and the pivot is an ordinary record the dotted path already reaches.

### The write is not authorization-checked

The editable columns write straight onto the record, with nothing between the posted value and `persist()`/`flush()` but the host's `isDisabled()` check, which runs after `updateTableColumnState()` binds the record so a per-row closure judges the row it was handed.
Filament carries a `// Security:` comment on each editable column class and on `CanUpdateState` saying the write bypasses Laravel model policies and that `disabled()` is the restriction.
The port carries the same comment at the write site in `CanUpdateState::updateState()` — the one place a reader can act on it — with one thing added: the toolkit has **no** authorization layer at all, so `disabled()` is not merely the recommended gate, it is the only one.
Anything stricter belongs on a page or modal action the application guards itself.

### Validation speaks Symfony constraints and `rules()` is not ported

Filament's tables-tier `CanBeValidated` is a Laravel rule bag: `rules()`, `getRules()`, `validationAttribute()` and an implicit `'nullable'`.
The port speaks constraints instead: `constraint()`, `constraints()` and `validationMessages()` keyed by constraint class, exactly as forms' [`CanBeValidated`](../forms/can-be-validated.md) does, and `validate()` runs them through `PresenceGate::apply()` before Symfony's validator.
Why `rules()`, `validationAttribute()` and the implicit `'nullable'` have no counterpart is the [rule-vocabulary ruling](../renames.md#laravel-rule-vocabulary-becomes-symfony-constraint-vocabulary).

The validator arrives by `#[Required] setValidator(ValidatorInterface)` and `getValidator()` throws when it is unset, rather than falling back to `Validation::createValidator()`: the fallback would fatal on any constraint whose validator is a container service (`unique()`, `exists()`), which is exactly the case a fallback would be reached for.

### A live action answers nothing, so the return value is dropped

Filament's `callTableColumnAction()` returns whatever the column's closure returned, and Livewire hands that back to the browser.
UX LiveComponent has no such channel: `component.action()` resolves to a `BackendResponse`, never the PHP return value, so a returned value would be unreachable by construction.
The port therefore types the live action `void` and the closure's own return is discarded; the re-render is the whole answer, which is the same rule the rest of the toolkit's live actions follow.

A closure that wants to change what the user sees writes host state (a `LiveProp`, the record through Doctrine) and lets the re-render show it, rather than returning something.
