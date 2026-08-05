# BelongsToTable (actions)

[`Sylament\Actions\Concerns\BelongsToTable`](../../../packages/actions/src/Concerns/BelongsToTable.php)
— the binding between an action and the table that renders it: `table()` sets
it, `getTable()` reads it or falls back through the action's group, and
`hasTable()` is the predicate the mount context and the live-component chain
branch on.

Both [`Action`](action.md) and [`ActionGroup`](action-group.md) compose it, and
three consumers read it today: the live-component chain's
[table hop](action.md#the-live-component-chain-narrows-inline-instead-of-through-a-second-getter),
[`getContext()`](action.md#getcontext-drops-the-record-model-gate-and-the-bulk-flag)'s
`table => true` entry, and the group fallback inside `getTable()` itself. It
earns a file of its own only because of the divergence below, which constrains
the trait rather than either consumer.

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`actions/src/Concerns/BelongsToTable.php`](../../../vendor/filament/actions/src/Concerns/BelongsToTable.php)
  — the trait this ports, method for method and body for body

Covered by
[`ActionsBelongsToTableTest`](../../../packages/tables/tests/Unit/ActionsBelongsToTableTest.php),
which lives in the **tables** suite rather than the actions one: the binding
takes a real `Sylament\Tables\Table`, and the actions test kernel registers no
tables bundle to build one from. Its four cases pin the group fallback, an own
binding winning over the group's, the `table` context flag with its
nested-frame suppression, and the live-component table hop.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold.

### The `Table` reference is a guarded soft dependency, not a declared one

`actions` does **not** require `sylament/tables`, and must not: the dependency
runs the other way for everything else in the toolkit, and a panel that ships
forms without tables would otherwise pay for a package it never renders.
Filament makes the identical call —
`filament/actions`' `composer.json` requires `filament/tables` nowhere, yet its
`BelongsToTable` and `InteractsWithActions` both `use Filament\Tables\…`
symbols — so this is a port of the decision, not a Sylament invention.

What makes it safe is that the reference is never *dereferenced* in a
tables-less install. `protected ?Table $table = null` is a typed property with
a null default, and PHP resolves a property's class type lazily — nothing
autoloads `Table` until a value of that type is assigned. The only code that
will ever assign one is tables-side (the table binding itself to the actions it
renders), so in an install without the package the property stays null,
`getTable()` returns null through the group fallback, `hasTable()` is false,
and the class is never loaded. The return and parameter types are the same
story: a null passes an `?Table` check without the class being resolved.

Two things this costs, both accepted. Static analysis sees the class because
the monorepo autoloads every package at once — so PHPStan validates the
reference here but cannot prove the tables-less install works; that guarantee
rests on the property staying nullable and unassigned, which is why no
`getTableOrFail()`-style throw belongs on this trait. And a consumer who *does*
install both gets no Composer signal until `sylament/actions` grows a `suggest`
entry for `sylament/tables`, which lands with the table's action rendering.

The one thing that is genuinely ours: the trait leans on `getGroup()` from
[`BelongsToGroup`](action-group.md) without composing it, the same way
[`BelongsToLiveComponent`](action.md#the-live-component-chain-narrows-inline-instead-of-through-a-second-getter)
leans on the schema getters. Both consumers compose both traits, so PHPStan
resolves the call in every context it analyses; a future class mixing in
`BelongsToTable` without `BelongsToGroup` fails the run, which is the intended
signal rather than an oversight to guard with a `method_exists()` check.
