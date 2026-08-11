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
**semantics** link (a permanent note in [Semantics](#semantics)) and/or a
**diverges** link (the permanent note in [Divergences](#divergences)), or `—`.
Nothing here ports a Filament source, so the _Ports_ column is dropped.

| Feature | Status | Blocked by | Detail |
| --- | --- | --- | --- |
| [`__construct(array $recordPathMap, ?string $rootStatePath)`](../../../packages/schemas/src/State/ViolationPathTranslator.php) — built by [`Schema::getRecordViolationPathTranslator()`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) | done | — | [semantics](#the-state-path-is-rebuilt-from-the-prefix-rather-than-read-off-the-component) |
| [`translate(string $violationPropertyPath): ?string`](../../../packages/schemas/src/State/ViolationPathTranslator.php) — deepest declared path wins | done | — | [semantics](#a-violation-beneath-a-declared-leaf-folds-onto-that-leaf) |
| The `null` return — a violation no declared path owns | done | — | [semantics](#an-unowned-violation-returns-null-so-the-caller-folds-it-at-the-schema-root) |
| The index arm — `sections[2].heading` → the item key at position 2 | done | — | [diverges](#the-index-is-read-as-a-position-which-a-reordered-or-thinned-collection-breaks) |
| [`HasKeyedItems::getItemKeys()`](../../../packages/schemas/src/Components/Contracts/HasKeyedItems.php) — implemented by [`Repeater`](../forms/repeater.md) | done | — | [diverges](#the-index-is-read-as-a-position-which-a-reordered-or-thinned-collection-breaks) |
| Parsing — `Symfony\Component\PropertyAccess\PropertyPath` | done | — | [semantics](#the-path-is-parsed-by-symfonys-own-parser-not-by-splitting-on-dots) |

Coverage: every arm by
[`ViolationPathTranslatorTest`](../../../packages/schemas/tests/Unit/State/ViolationPathTranslatorTest.php)
(leaf, nested leaf, deepest-wins, fold-onto-owner, no-prefix, the three `null`
arms, the index hop, the collection's own path, and the four stop conditions);
end-to-end against real Doctrine records by
[`BelongsToRecordValidationTest`](../../../packages/schemas/tests/Integration/Concerns/BelongsToRecordValidationTest.php)
and, for the index arm over a real `Repeater::relationship()`, by
[`RepeaterRecordValidationTest`](../../../packages/forms/tests/Integration/Components/RepeaterRecordValidationTest.php)
— which also pins both halves of the divergence below.

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
path. The message then shows on the owner rather than vanishing. The one
exception is the index arm, which appends the remainder *because* a keyed-item
component's item schema mirrors the child record's own paths.

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

### The path is parsed by Symfony's own parser, not by splitting on dots

`PropertyPath` is what produced these strings, so it is what reads them back:
it separates a property element from an index element (`[2]`), unescapes `\.`
inside a property name, and strips a null-safe `?`. Splitting on `.` would take
`sections[2]` for a property named `sections[2]` and mis-handle any escaped
name. A string `PropertyPath` refuses — the empty path included — is caught and
answered `null`.

## Divergences

Deliberate deviations, and the reason each holds. Provisional ones say what
would reverse them.

### The index is read as a position, which a reordered or thinned collection breaks

`HasKeyedItems::getItemKeys()` returns the item keys **in state order**, and
`sections[2]` is resolved as `getItemKeys()[2]`. That is exact whenever the
record's collection is keyed `0..n-1` in the same order the state holds the
items — a record built fresh, or an edit that kept every child in place, which
is the common submit.

It is **not** exact otherwise, and both failure shapes are pinned by test rather
than left to be discovered:

- **A deletion** leaves a `PersistentCollection` keyed sparsely (removing the
  element at key `0` of two leaves key `1` behind), so the violation on the
  survivor arrives as `sections[1]` while the state holds one item at position
  `0`. The index is out of range and the message folds at the repeater's own
  path — a visible, correctly-scoped error on the wrong granularity.
- **A reorder** is worse. Symfony's adder/remover diffing does not re-key a
  collection whose membership is unchanged, so the record still iterates in the
  old order while the state holds the new one, and the violation is attributed
  to the item that now occupies that position. The message shows under the wrong
  item.

The cause is that a violation path carries a collection **key**, and
`list<string>` cannot express a mapping from a sparse key set. The fix is to
widen the contract to `array<int|string, string>` — the item key keyed by the
collection key of the element it was mapped onto, which a repeater can build by
walking the record's own collection and asking the
[association reader](association-reader.md) for each child's record key. Nothing
else changes: the translator would index that array instead of a list.
**Reverses when that widening lands**; until then this section, and the two
tests named above, are the record of what the positional read costs.
