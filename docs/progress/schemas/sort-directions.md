# SortDirections (schemas)

[`Sylament\Schemas\Query\SortDirections`](../../../packages/schemas/src/Query/SortDirections.php)
— a Sylament invention with **no Filament source**. Filament hands Eloquent's
`orderBy()` the `asc`/`desc` string its sort API carries; from ORM 3.7 Doctrine's
`QueryBuilder::orderBy()`/`addOrderBy()` and `Expr\OrderBy` take PHP 8.6's
`SortDirection` enum instead, a string being deprecated there, and a to-many
mapping's `orderBy()` answers the enum too.

The public sort API keeps Filament's strings — `defaultSort('id', 'desc')`, the
`$direction` a `sortable(query:)` or `orderQueryUsing()` closure receives — so
the string becomes the enum at the builder boundary, and this is the one place
that happens: `IdentifierConstraint::orderBy()`, tables' column, default and
group sorts, `FetchJoinPageQuery`'s re-emitted orderings, and
[`AssociationReader`](association-reader.md), which normalises any string a
mapping still hands it.

**Status:** done — see the [status vocabulary](../README.md#statuses).

## Surface

| Feature | Status | Blocked by | Detail |
| --- | --- | --- | --- |
| [`from(string $direction): SortDirection`](../../../packages/schemas/src/Query/SortDirections.php) — `asc`/`desc` in any case as the enum | done | — | [semantics](#anything-but-asc-or-desc-throws) |

## Semantics

### Anything but asc or desc throws

`from()` matches the lower-cased string and throws an `InvalidArgumentException`
naming it for anything else, rather than defaulting to ascending — the answer
Doctrine's own string path gives (`QueryBuilder::getSortDirection()` throws for
an unknown string), so a direction that reached the builder before still fails
there, only earlier and by name. It matters in one place: `FetchJoinPageQuery`
re-reads a direction off an ordering part already on the builder, and a part
carrying more than a direction — a hand-built `new Expr\OrderBy('x', 'DESC NULLS
LAST')` stores its string raw — is refused there by name, as Doctrine's builder
would refuse it.
