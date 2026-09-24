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
offending value there.

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
inside the item); end-to-end against real Doctrine records by
[`BelongsToRecordValidationTest`](../../../packages/schemas/tests/Integration/Concerns/BelongsToRecordValidationTest.php)
and, for the index arm over a real `Repeater::relationship()`, by
[`RepeaterRecordValidationTest`](../../../packages/forms/tests/Integration/Components/RepeaterRecordValidationTest.php)
— which pins the attribution through a deletion and through a reorder, and both
in-item fold shapes.

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
path. The message then shows on the owner rather than vanishing. How deep that
owner is depends on what the translator is handed: since
[M7 · 6](../roadmap.md#m7--composite-identifiers) the map it is built over
carries the paths the record path map drops beneath a subtree owner as well
([why](belongs-to-record.md#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk)),
so a singular relationship's `plaque.title` lands under the `title` field
declared inside it, and only a path nothing inside the owner declares — a
class-level violation on the related record — folds onto the owner. A keyed-item
component is the one owner that can go deeper, because its items declare paths
of their own — see
[the in-item fold](#a-violation-inside-an-item-folds-by-the-same-rule-as-one-at-the-root).

### An unowned violation returns null so the caller folds it at the schema root

A class-level constraint carries the empty property path, and a property-level
one may name a property no component maps (a `slug` the form does not show).
Both return `null`, and
[`validateSchemaRecord()`](can-be-validated.md#record-sourced-constraints-are-validated-on-the-mapped-object)
folds them at the schema's own state path. That path belongs to no input, so
such a message is carried on the `ValidationException` and reachable through
`getErrorsForSchemaStatePath('data')` without being rendered under a field —
deliberate: dropping it would lose a refusal the record made, and guessing a
field for it would be a lie. A schema wanting them on screen reads that path.

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
own state path — `data.sections.record-7`, the item schema's key. That is the
in-item counterpart of the
[root fold](#an-unowned-violation-returns-null-so-the-caller-folds-it-at-the-schema-root):
a message on a container rather than an input, carried on the exception and
reachable by path, never a state path no component owns. Appending the remainder
unconditionally would invent one.

### The path is parsed by Symfony's own parser, not by splitting on dots

`PropertyPath` is what produced these strings, so it is what reads them back:
it separates a property element from an index element (`[2]`), unescapes `\.`
inside a property name, and strips a null-safe `?`. Splitting on `.` would take
`sections[2]` for a property named `sections[2]` and mis-handle any escaped
name. A string `PropertyPath` refuses — the empty path included — is caught and
answered `null`.
