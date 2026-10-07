---
description: The trait binding an action or action group to the table that renders it, and why its Table reference needs no Composer require.
ports:
  - vendor/filament/actions/src/Concerns/BelongsToTable.php
owns:
  - packages/actions/src/Concerns/BelongsToTable.php
---
# BelongsToTable (actions)

Both [`Action`](action.md) and [`ActionGroup`](action-group.md) compose it.
`getTable()` falls back to the action's group, and `hasTable()` is the predicate the mount context and the live-component chain branch on.

Covered by [`ActionsBelongsToTableTest`](../../../packages/tables/tests/Unit/ActionsBelongsToTableTest.php), which lives in the **tables** suite rather than the actions one: the binding takes a real `Sylament\Tables\Table`, and the actions test kernel registers no tables bundle to build one from.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`table()` / `getTable()` / `hasTable()`](../../../packages/actions/src/Concerns/BelongsToTable.php) | [`Concerns/BelongsToTable.php`](../../../vendor/filament/actions/src/Concerns/BelongsToTable.php) | done | — | [diverges](#the-table-reference-is-a-guarded-soft-dependency-not-a-declared-one) |

## Semantics

### The trait leans on `BelongsToGroup` without composing it

`getTable()` calls `getGroup()` from [`BelongsToGroup`](action-group.md) without composing that trait, as Filament's does, the same way [`BelongsToLiveComponent`](action.md#the-live-component-chain-narrows-inline-instead-of-through-a-second-getter) leans on the schema getters.
Both consumers compose both traits, so PHPStan resolves the call in every context it analyses; a future class mixing in `BelongsToTable` without `BelongsToGroup` fails the run, which is the intended signal rather than an oversight to guard with a `method_exists()` check.

## Divergences

### The `Table` reference is a guarded soft dependency, not a declared one

`actions` does **not** require `sylament/tables`, and must not: the dependency runs the other way for everything else in the toolkit, and a panel that ships forms without tables would otherwise pay for a package it never renders.
Filament makes the identical call: `filament/actions`' `composer.json` requires `filament/tables` nowhere, yet its `BelongsToTable` and `InteractsWithActions` both `use Filament\Tables\…` symbols, so this is a port of the decision, not a Sylament invention.

What makes it safe is that the reference is never *dereferenced* in a tables-less install.
`protected ?Table $table = null` is a typed property with a null default, and PHP resolves a property's class type lazily: nothing autoloads `Table` until a value of that type is assigned.
Only tables-side code assigns one (the table binding itself to the actions it renders), so in an install without the package the property stays null, `getTable()` returns null through the group fallback, `hasTable()` is false, and the class is never loaded.
The return and parameter types are the same story: a null passes a `?Table` check without the class being resolved.

Two things this costs, both accepted.
Static analysis sees the class because the monorepo autoloads every package at once, so PHPStan validates the reference here but cannot prove the tables-less install works; that guarantee rests on the property staying nullable and unassigned, which is why no throwing `getTable()` variant belongs on this trait.
And a consumer who *does* install both gets no Composer signal beyond the `suggest` entry for `sylament/tables` that `sylament/actions` carries for the [guarded table branch](has-actions.md#the-table-branch-reaches-tables-through-a-guarded-soft-reference) in `resolveActions()`, a `suggest` being the strongest declaration an optional integration gets.

Two reads sit inside that guarantee rather than outside it.
An action **body** may read the table it runs against, because such a body only runs mounted from a table; [`DeleteBulkAction`](delete-action.md) does, with a `?? throw` naming the action for the case where none is bound.
And [`Action`](action.md#tableclass-resolves-by-type-as-well-as-by-name) injects the bound table into an evaluated closure by the name `table` and by `Table::class`: the by-type arm is a `match` over a `::class` constant, which compares compile-time strings and never autoloads, so it answers `null` in a tables-less install exactly as the property does.
