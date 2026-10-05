# ViolationPathTranslator (schemas)

[`Sylament\Schemas\State\ViolationPathTranslator`](../../../packages/schemas/src/State/ViolationPathTranslator.php)
and the [`HasKeyedItems`](../../../packages/schemas/src/Components/Contracts/HasKeyedItems.php)
component contract it consults — Sylament-only, with no Filament counterpart:
Filament never validates a mapped model, so it never has to speak a violation
path back in state terms.

It exists because the two sides of
[`getData()`'s record tail](can-be-validated.md#record-sourced-constraints-are-validated-on-the-mapped-object)
speak different languages. A `ConstraintViolation` carries a property path that
indexes the **record graph** (`title`, `author.name`, `sections[2].heading`),
while the error bag
[`getErrorsForSchemaStatePath()`](has-schemas.md) reads is keyed by the
**absolute state paths** components declare (`data.title`,
`data.sections.record-9.heading`). The translator is the inverse of the mapping
walk: it walks the same
[record path map](belongs-to-record.md#mapping-walks-the-record-graph-by-declared-leaf-paths)
`getData()` wrote through, so a violation lands under the component that put the
offending value there — or, since [M7 · 11](../roadmap.md#m7--composite-identifiers),
at the schema's own path when that component shows no errors of its own.

**Status:** done — see the [status vocabulary](../README.md#statuses).

## Surface

Every point of the translator and the contract. _Detail_ carries a
**semantics** link (a permanent note in [Semantics](#semantics)) or `—`.
Nothing here ports a Filament source, so the _Ports_ column is dropped, and
nothing here diverges from one, so there is no Divergences section.

| Feature | Status | Blocked by | Detail |
| --- | --- | --- | --- |
| [`__construct(array $recordPathMap, ?string $rootStatePath)`](../../../packages/schemas/src/State/ViolationPathTranslator.php) — built by [`Schema::getRecordViolationPathTranslator()`](../../../packages/schemas/src/Concerns/BelongsToRecord.php), over the record path map and the paths it drops beneath a subtree owner | done | — | [semantics](#the-state-path-is-rebuilt-from-the-prefix-rather-than-read-off-the-component) |
| [`translate(string $violationPropertyPath): ?string`](../../../packages/schemas/src/State/ViolationPathTranslator.php) — deepest declared path wins | done | — | [semantics](#a-violation-beneath-a-declared-leaf-folds-onto-that-leaf) |
| The `null` return — a violation no declared path owns | done | — | [semantics](#an-unowned-violation-returns-null-so-the-caller-folds-it-at-the-schema-root) |
| [`translateToErrorPath(string $violationPropertyPath): ?string`](../../../packages/schemas/src/State/ViolationPathTranslator.php) — `translate()`'s answer when the owning component implements [`HasValidationConstraints`](../../../packages/schemas/src/Components/Contracts/HasValidationConstraints.php) or `HasKeyedItems` and the answer is no keyed item's own path, else `null`; the path `validateSchemaRecord()` files a message under | done | — | [semantics](#a-message-is-filed-only-under-a-component-that-shows-its-own-errors) |
| The index arm — `sections[2].heading` → the item key the record's collection holds at key `2` | done | — | [semantics](#the-index-is-a-collection-key-not-a-position) |
| The in-item fold — a violation naming a path the item schema declares nothing for | done | — | [semantics](#a-violation-inside-an-item-folds-by-the-same-rule-as-one-at-the-root) |
| [`HasKeyedItems::getItemKeys()`](../../../packages/schemas/src/Components/Contracts/HasKeyedItems.php) — implemented by [`Repeater`](../forms/repeater.md) | done | — | [semantics](#the-index-is-a-collection-key-not-a-position) |
| [`HasKeyedItems::getItemStatePaths()`](../../../packages/schemas/src/Components/Contracts/HasKeyedItems.php) — implemented by [`Repeater`](../forms/repeater.md) | done | — | [semantics](#a-violation-inside-an-item-folds-by-the-same-rule-as-one-at-the-root) |
| Parsing — `Symfony\Component\PropertyAccess\PropertyPath` | done | — | [semantics](#the-path-is-parsed-by-symfonys-own-parser-not-by-splitting-on-dots) |

Coverage: every arm by
[`ViolationPathTranslatorTest`](../../../packages/schemas/tests/Unit/State/ViolationPathTranslatorTest.php)
(leaf, nested leaf, deepest-wins, fold-onto-owner, no-prefix, the three `null`
arms, the index hop over a list key / a sparse key / a string key, the
collection's own path, and the five stop conditions — an index naming no item,
an in-item path the item declares nothing for, a class-level violation on the
item record, a fold onto a declared item path, and a second index nesting
inside the item — and `translateToErrorPath()`'s arms: a constrained field, a
keyed item, a component showing no errors, the four `null` paths, the three
shapes that stop at an item's own path, an item field taking the remainder, and
the collection's own path);
end-to-end against real Doctrine records by
[`BelongsToRecordValidationTest`](../../../packages/schemas/tests/Integration/Concerns/BelongsToRecordValidationTest.php),
the error placement by
[`HasSchemasRecordErrorPlacementTest`](../../../packages/schemas/tests/Integration/Concerns/HasSchemasRecordErrorPlacementTest.php)
and, for the index arm over a real `Repeater::relationship()`, by
[`RepeaterRecordValidationTest`](../../../packages/forms/tests/Integration/Components/RepeaterRecordValidationTest.php)
— which pins the attribution through a deletion and through a reorder, and that
both in-item fold shapes are filed at the schema's own path.

## Semantics

Faithful-in-spirit behaviour a reader would plausibly misread from the source.
Permanent — these outlive the detail sections above.

### The state path is rebuilt from the prefix rather than read off the component

The map's values are components, each of which already knows its absolute state
path, so `translate()` looks like it should return `$component->getStatePath()`.
It instead re-joins `$rootStatePath` with the matched record path. The two are
equal by construction —
[`getRecordStatePath()`](belongs-to-record.md#getrecordstatepath-is-the-one-definition-of-record-relative-and-both-walks-must-share-it)
strips exactly that prefix — and the string form is what the index arm needs
anyway, since an item key is appended to a path no component in the map owns.
The component is consulted for one thing only: whether it keys its items.

### A violation beneath a declared leaf folds onto that leaf

`author.name` translates to `data.author.name` when the map carries
`author.name`, and to `data.author` when the map carries only `author` — a
component that
[owns its record subtree](belongs-to-record.md#a-component-can-own-its-whole-record-subtree)
declares no leaf beneath it, so it is the deepest thing that can speak for the
path. How deep that owner is depends on what the translator is handed: since
[M7 · 6](../roadmap.md#m7--composite-identifiers) the map it is built over
carries the paths the record path map drops beneath a subtree owner as well
([why](belongs-to-record.md#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk)),
so a singular relationship's `plaque.title` lands under the `title` field
declared inside it, and only a path nothing inside the owner declares — a
class-level violation on the related record — folds onto the owner. A keyed-item
component is the one owner that can go deeper, because its items declare paths
of their own — see
[the in-item fold](#a-violation-inside-an-item-folds-by-the-same-rule-as-one-at-the-root).
Where the message is then **shown** is a second question, answered by
[`translateToErrorPath()`](#a-message-is-filed-only-under-a-component-that-shows-its-own-errors).

### A message is filed only under a component that shows its own errors

`translate()` answers which declared path owns a violation; that is not always a
path anything renders. Forms' `Field` reads its own state path's errors, and the
page and the action modal read the schema's own, but a layout component
entangling a related record — `Group`, `Section` or `Fieldset` with
`relationship()` — reads none. A violation that folded onto such an owner — a
class-level one on the related record, its constraint on a field the group does
not map, or the blank-key refusal for a derived identity whose target key field
nothing inside the group maps — was a 422 with no message on screen.

`translateToErrorPath()` answers `translate()`'s path only when the owning
component declares that it shows its errors: it implements
[`HasValidationConstraints`](../../../packages/schemas/src/Components/Contracts/HasValidationConstraints.php),
as every form field does, or `HasKeyedItems`, as the repeater does. Otherwise it
answers `null`, and
[`validateSchemaRecord()`](can-be-validated.md#record-sourced-constraints-are-validated-on-the-mapped-object)
files the message at the schema's own path, which the page and the modal render
above the form. The test is a positive one on purpose: a component that proves
nothing — a new layout, an application's own — fails toward the path that always
renders, never toward one that may not.

The keyed-items arm needs one more test. The repeater is a field, so a message
at its own path renders under it, and each item field renders its own; but
`translate()` can also answer an **item's** own state path —
`data.sections.record-7`, for a class-level violation on the item record or one
on an item property no item field maps — and nothing reads that path. Until
[M7 · 11](../roadmap.md#m7--composite-identifiers) such a message was carried on
the exception, reachable through `getErrorsForSchemaStatePath()`, and shown
nowhere: a 422 with no message on screen. `translateToErrorPath()` now answers
`null` for that path too, so the message is filed at the schema's own path and
renders above the form; a violation on a mapped item field still lands under
that field, and one on the collection itself — or on an index naming no item —
still lands on the repeater.

Only the filing moved. The record check still asks `translate()` whether a
component maps a blank or taken key candidate, so a group entangling the related
record still counts as mapping it and the refusal is still raised; it is shown
above the form rather than nowhere. Landed in
[M7 · 11](../roadmap.md#m7--composite-identifiers); pinned by
[`HasSchemasRecordErrorPlacementTest`](../../../packages/schemas/tests/Integration/Concerns/HasSchemasRecordErrorPlacementTest.php)
and, rendered, by panels'
[`EditRecordRelationshipsTest`](../../../packages/panels/tests/Integration/Resources/Pages/EditRecordRelationshipsTest.php).

### An unowned violation returns null so the caller folds it at the schema root

A class-level constraint carries the empty property path, and a property-level
one may name a property no component maps (a `slug` the form does not show).
Both return `null`, and
[`validateSchemaRecord()`](can-be-validated.md#record-sourced-constraints-are-validated-on-the-mapped-object)
folds them at the schema's own state path. That path belongs to no input, so
such a message is carried on the `ValidationException` and reachable through
`getErrorsForSchemaStatePath('data')` without being rendered under a field —
deliberate: dropping it would lose a refusal the record made, and guessing a
field for it would be a lie. The resource page's form and the action modal read
that path and render it above the form; a host of its own reads it the same way.

### The index is a collection key, not a position

A violation path indexes the **record**, so `sections[2]` names whatever key the
record's collection holds that element under — which is not the item's position
in the state, and after an edit is often not a position at all.
`HasKeyedItems::getItemKeys()` therefore returns `array<int|string, string>`,
the item key keyed by the collection key it was written to, and `translate()`
indexes that array rather than counting.

Only the write can know the pairing, which is why the
[repeater](../forms/repeater.md) records it in its save hook rather than
deriving it: Symfony's accessor hands the desired list to the record's adder and
remover, so an unchanged membership keeps its old order and a thinned one is
keyed sparsely (removing the element at key `0` of two leaves key `1` behind).
Reading the collection back after the write, and pairing each element with the
item key it was written from **by identity**, survives both — and an added item,
which has no key of its own until a flush. A key the map does not carry folds at
the component's own path, which is the honest answer for a violation on a
collection nothing in the state produced.

### A violation inside an item folds by the same rule as one at the root

An item's own paths are invisible to the record path map: a repeater
[owns its record subtree](belongs-to-record.md#a-component-can-own-its-whole-record-subtree),
so the map carries `sections` and nothing beneath it. The remainder after the
index (`sections[2].heading` → `heading`) is therefore matched against
`HasKeyedItems::getItemStatePaths()` — the paths the item schema declares, which
are exactly what the save wrote onto the item's record — deepest-prefix-wins,
the same walk `translate()` runs over the map at the root.

So `heading` appends when a field declares it, `meta.locale.code` folds onto a
declared `meta.locale`, and a path no item field maps (`internalNote`, or a
child's class-level violation carrying no remainder at all) folds at the item's
own state path — `data.sections.record-7`, the item schema's key. Like the
[root fold](#an-unowned-violation-returns-null-so-the-caller-folds-it-at-the-schema-root),
it is a message on a container rather than an input, never a state path no
component owns. No template reads an item's own path, so since
[M7 · 11](../roadmap.md#m7--composite-identifiers) the filing does not leave it
there: `translateToErrorPath()` answers `null` for it, and the message is shown
at the schema's own path, above the form
([the filing](#a-message-is-filed-only-under-a-component-that-shows-its-own-errors)).
`translate()` still answers the item's path, which is what it owns. Appending
the remainder unconditionally would invent a path no component owns.

### The path is parsed by Symfony's own parser, not by splitting on dots

`PropertyPath` is what produced these strings, so it is what reads them back:
it separates a property element from an index element (`[2]`), unescapes `\.`
inside a property name, and strips a null-safe `?`. Splitting on `.` would take
`sections[2]` for a property named `sections[2]` and mis-handle any escaped
name. A string `PropertyPath` refuses — the empty path included — is caught and
answered `null`.
