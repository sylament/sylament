# RecordKey (schemas)

[`Sylament\Schemas\Identity\RecordKey`](../../../packages/schemas/src/Identity/RecordKey.php),
[`IdentifierConstraint`](../../../packages/schemas/src/Identity/IdentifierConstraint.php)
and
[`KeySelection`](../../../packages/schemas/src/Identity/KeySelection.php),
over the `@internal` per-class descriptor
[`IdentifierFields`](../../../packages/schemas/src/Identity/IdentifierFields.php)
— a Sylament invention with **no Filament source**. Eloquent has no composite-key
vocabulary at all: `Model::getKeyName()` is one string, `getKey()` reads one
attribute, and `Builder::whereKey(array)` means *many keys*, never one tuple — so
Filament's `getRecordKey(Model | array $record): string` inherits a single-column
world and there is nothing over there to diff against.

**A record key is one string everywhere it travels.** That is the whole stance,
and it is what these three classes exist to make true: the codec turns a record —
or a raw identifier tuple read back off a result row — into one string and
back again, and the predicate builder replaces every single-column comparison
with its per-field form so a query can be narrowed, correlated, projected,
grouped and ordered by an identifier of any width. Both are **static, with the
entity manager passed per call**, in
[`SearchConstraint`](search-constraint.md)'s shape: no service, no trait, no DI,
so a component, a table host, a resource and a bulk action all reach them the
same way.

They landed with [M7 · 1](../roadmap.md#m7--composite-identifiers) and are the
file every later epic of that milestone plans from. Their first consumers are
tables' [`HasTable`](../tables/has-table.md#object-records-are-keyed-by-a-readable-id-rather-than-an-eloquent-key),
`HasBulkActions`, `CanReorderRecords`, `CanSortRecords` and
`CanSummarizeRecords`, and actions'
[`DeleteBulkAction`](../actions/delete-action.md#the-by-key-arm-is-a-dql-delete-over-the-plucked-keys)
and [`Action::getContext()`](../actions/action.md#getcontext-drops-the-record-model-gate).
M7 · 2 added tables' `Query\AggregateQuery`, `Query\RelationConstraint` and
`Query\FetchJoinPageQuery`, and found nothing here short. M7 · 3 added panels'
[`Resources\Resource`](../panels/README.md#a-record-is-resolved-through-the-resources-own-query)
— the `{record}` route key both ways — consuming `of()` and `equals()` as they
landed and needing nothing more for the **default** arm. It did find one
addition, and left it to a later epic: closing the custom `$recordRouteKeyName`
arm needs a typed bind with a print-back compare against a *named column*,
which is not an operation this file exposes
([why it is still open](../panels/README.md#a-custom-recordroutekeyname-names-one-column-and-the-codec-never-sees-it)).
M7 · 4 added the relation layer's — `AssociationReader`, forms' `Select`,
`HasRelationship`, `Repeater` and `UniqueValueValidator`, and actions' four
relationship actions — consuming `of()`, `find()`, `toIdentifier()`,
`equals()`, `notEquals()`, `in()`, `correlate()`, `select()` + `keyOf()` — which
earned its keep on the `IDENTITY()`-selected halves, raw under every hydrator —
and `chunk()` as they landed, and finding nothing here short. M7 · 5 added the owner
half — both relationship queries scoping to an owner of any width — and is the
first epic to find this interface **long** rather than short: the owner scope is
one `equals()` on a subselect's alias, and the operation reserved for it was
never built ([why](#owner-scoping-was-reserved-and-turned-out-not-to-be-needed)).
M7 · 5b is the first to find it **short** and build the addition, in one place:
the by-key dissociate became one SQL `UPDATE` of one table — since M7 · 7 only
where its mapped-by spans several join columns — which no DQL operation can
narrow, so `IdentifierConstraint` gained
[`inColumns()`](#incolumns-is-in-in-sql-for-a-statement-that-names-one-table) —
its first operation since M7 · 1, `in()` spelled over a DBAL builder's identifier
columns. The same step taught the decode to read a component back
[through its type's own writer](#a-type-that-refuses-its-printed-form-on-read-only-decodes-it-through-its-own-writer)
and gave `RecordKey` one `@internal` read, `values()`, for the relation layer's
owner bind — three files of this directory moved, one of them by that method
alone, and `KeySelection` did not. M7 · 6, the create-path rules, changed what
`of()` and `find()` **answer** rather than what they accept: each component is
minted [as its row would read it back](#a-key-is-minted-from-the-value-its-row-would-read-back),
through one private method — so `RecordKey.php` moved by that method, its
import and two call lines — and by
the docblocks corrected to match, with the one import their `@throws` names —
and `IdentifierConstraint`, `IdentifierFields` and `KeySelection` did not move at
all.
Its other consumers took the interface as it stood: the record check's two
passes read `values()` and `IdentifierFields::printable()`, and `ReplicateAction`
reads `values()` and asks `find()` whether a replica is keyable yet.
M7 · 7 added `IdentifierConstraint::batch()` (client-sized key lists), bound a
composite list's parameters in one pass and made a refused key name its row;
M7 · 9 checked a component's shape by type class, read a blank component through
support's `blank()`, let the key-list constraints take int keys, and moved
`orderBy()` onto `SortDirection`.
[M7 · 10](../roadmap.md#m7--composite-identifiers) moved `RecordKey.php` by one
docblock line and no code: `values()`'s `@internal` note named the owner bind
alone, and now names every reader it has — the owner bind, the record check's
taken-key and changed-key passes, and `ReplicateAction`'s blank-key check, which
reads it from another package. `@internal` keeps meaning *no part of the key
contract*, and the note is the list of sanctioned readers.
[M7 · 11](../roadmap.md#m7--composite-identifiers) is the second epic to find the
interface short, in `KeySelection`: a row whose hydrator already read its fields
through their types — an array result, or the rows Doctrine's limit walker
answers — was read a second time by `keyOf()`, so it gained
[`keyOfHydrated()`](#a-hydrated-row-is-keyed-without-reading-its-fields-a-second-time),
which converts only the `IDENTITY()` association halves, and the four consumers
reading such rows moved onto it. The same step offers a component its type reads
as another value to the [writer](#a-type-that-refuses-its-printed-form-on-read-only-decodes-it-through-its-own-writer)
too, and shape-checks a boolean component as `0` or `1`.

**Status:** done — see the [status vocabulary](../README.md#statuses).

## Surface

Every operation these three classes expose. _Blocked by_ names what has to land
first. _Detail_ carries **contract** → the frozen interface in
[Contract](#contract), **semantics** → the write-up in [Semantics](#semantics),
and/or **diverges** → the permanent note in [Divergences](#divergences).

| Feature | Status | Blocked by | Detail |
| --- | --- | --- | --- |
| [`RecordKey::of()`](../../../packages/schemas/src/Identity/RecordKey.php) — a record to its key, throwing on an incomplete or unprintable identifier, each component minted as its row would read it back | done | — | [contract](#the-api) · [semantics](#find-answers-identity-not-persistence) · [semantics](#a-key-is-minted-from-the-value-its-row-would-read-back) |
| [`RecordKey::find()`](../../../packages/schemas/src/Identity/RecordKey.php) — the non-throwing twin, `null` while the identifier is incomplete, minted the same way | done | — | [semantics](#find-answers-identity-not-persistence) · [semantics](#a-key-is-minted-from-the-value-its-row-would-read-back) |
| [`RecordKey::fromValues()`](../../../packages/schemas/src/Identity/RecordKey.php) — identifier values already in their PHP form, in mapping order, to the key a record mints — never a projected row's raw columns, which go through [`KeySelection::keyOf()`](../../../packages/schemas/src/Identity/KeySelection.php) | done | — | [semantics](#the-row-side-and-the-record-side-mint-one-string-and-only-the-codec-may) |
| [`RecordKey::toIdentifier()`](../../../packages/schemas/src/Identity/RecordKey.php) — a client string to the field ⇒ PHP value map `EntityManager::find()` takes, `null` when malformed | done | — | [contract](#the-grammar) · [semantics](#a-malformed-key-never-reaches-the-database) |
| [`RecordKey::values()`](../../../packages/schemas/src/Identity/RecordKey.php) — a record's identifier values before they are printed, an association reduced to its target's (`@internal`: the relation layer's owner bind and the record check's taken-key and changed-key passes read it, and so does `ReplicateAction`'s blank-key check; it is no part of the key contract) | done | — | [contract](#the-api) |
| [`IdentifierConstraint::equals()`](../../../packages/schemas/src/Identity/IdentifierConstraint.php) / `notEquals()` | done | — | [contract](#the-operations-and-their-dql) |
| [`IdentifierConstraint::in()`](../../../packages/schemas/src/Identity/IdentifierConstraint.php) / `notIn()` | done | — | [contract](#the-operations-and-their-dql) · [semantics](#the-batch-is-a-balanced-or-tree-because-sqlite-counts-expression-depth-not-terms) |
| [`IdentifierConstraint::inColumns()`](../../../packages/schemas/src/Identity/IdentifierConstraint.php) — `in()` in SQL, on the class's identifier **columns** over a DBAL `QueryBuilder`, for a statement that names one table | done | — | [contract](#incolumns-is-in-in-sql-for-a-statement-that-names-one-table) |
| [`IdentifierConstraint::correlate()`](../../../packages/schemas/src/Identity/IdentifierConstraint.php) — `alias.f = outer.f AND …` as **text**, because `RelationConstraint` splices a subselect string | done | — | [contract](#the-operations-and-their-dql) · [diverges](#comparing-two-identification-variables-compares-the-first-identifier-column-only) |
| [`IdentifierConstraint::select()`](../../../packages/schemas/src/Identity/IdentifierConstraint.php) + [`KeySelection::keyOf()`](../../../packages/schemas/src/Identity/KeySelection.php) (a scalar row) / `keyOfHydrated()` (an array row, or the limit walker's) | done | — | [contract](#the-operations-and-their-dql) · [semantics](#the-row-side-and-the-record-side-mint-one-string-and-only-the-codec-may) · [semantics](#a-hydrated-row-is-keyed-without-reading-its-fields-a-second-time) |
| [`IdentifierConstraint::groupBy()`](../../../packages/schemas/src/Identity/IdentifierConstraint.php) — the bare path per field, never `IDENTITY()` | done | — | [diverges](#bare-identitypath-on-a-multi-column-association-answers-only-the-first-column) |
| [`IdentifierConstraint::orderBy()`](../../../packages/schemas/src/Identity/IdentifierConstraint.php) — the identifier tiebreak, per field, deduped against the existing order | done | — | [contract](#the-operations-and-their-dql) |
| [`IdentifierConstraint::chunk()`](../../../packages/schemas/src/Identity/IdentifierConstraint.php) | done | — | [contract](#the-chunk-rule) |
| [`IdentifierConstraint::batch()`](../../../packages/schemas/src/Identity/IdentifierConstraint.php) | done | — | [contract](#the-chunk-rule) |
| Owner scoping — `scopeToOwner()` / the owner tuple | skipped | — | [contract](#owner-scoping-was-reserved-and-turned-out-not-to-be-needed) |
| [`IdentifierFields`](../../../packages/schemas/src/Identity/IdentifierFields.php) — the per-class descriptor both public classes share (`@internal`) | done | — | [diverges](#identifier-order-is-declaration-order-so-reordering-two-id-properties-invalidates-every-minted-key) |

## Contract

What a consumer writes, and what it gets back. This section is the interface:
M7 · 4–5 build against it unchanged, and an epic that finds it short records the
addition **here** rather than reshaping what landed.

### The grammar

```
key        = component *( "~" component )      ; one per identifier field, in mapping order
component  = 1*( safe / escape )               ; never empty
safe       = ALPHA / DIGIT / "-" / "_"
escape     = "=" 2UPPERHEXDIG                  ; one per byte of the component's UTF-8 form
```

The alphabet of any composite key is `[A-Za-z0-9_-]`, `~` and `=` — and nothing
else, ever. `~` is `RecordKey::SEPARATOR`; `=` is the escape introducer, written
once per **byte**, uppercase, so a multi-byte character becomes several escapes.

Three rules complete it.

**A class with one identifier field encodes to the flattened value verbatim** —
no escaping, no splitting, no alphabet check — so every URL, option value and
test that exists today stands unmoved. That includes a **single derived
identity** (`#[Id]` on a to-one association, alone), which is flattened to the
target's own key: a `PostSeal` keyed on its `Post` encodes to `1`. Two or more
fields take the grammar above. Decode is therefore **class-aware**, and every
decode site in the toolkit has the class in hand.

**A composite key is never numeric.** It always contains `~`, so it stays a
string as a PHP array key and cannot alias another key under Twig's loose `==` or
`in`, which compile to PHP's native comparison and make `"0123" == "123"` true.
A base64url format could not promise this, and a `<option selected>` or a checked
checkbox is decided by exactly that comparison.
**"Composite" here means two or more identifier fields, not "anything that is
not a plain `id`"**: a single derived identity is **one** field, so it takes
the verbatim arm above and inherits its target's spelling — a `PostSeal` keyed
on an `int`-keyed `Post` mints `1`, and PHP coerces that to the **int** array
key `1` exactly as it does for a single-`id` target. It is the one flavour this
promise does not cover, and a test written against `['1']` is what finds out.

**Canonical form is what encode emits, and decode accepts nothing else.** The
arity must equal the field count; no component may be empty; every `=` must be
followed by two **uppercase** hex digits; a byte that is `safe` must not be
escaped; the result must be valid UTF-8. That much is implemented as decode →
re-encode → compare, at the level of the *string*. A second rule finishes it at
the level of the *value*: **every component must print back to itself** once its
field's DBAL type has converted it — the rule
[the decode](#parameter-naming-and-what-an-empty-or-malformed-key-means) states
in full.

Together they give **exactly one string per record for every identifier type
whose PHP value prints back to its own component**, which is the promise a
consumer may build on. It is one-directional: a record mints exactly one key
once it holds the values its own type reads back — a record built in memory and
never reloaded can, under a converting type, mint a spelling the decode then
refuses, and a type that refuses its printed form on read **and** on write does
the same to a record freshly loaded from the database — while the guarantee that no *other*
string resolves that record belongs to the database and holds on a binary or
case-sensitive collation — PostgreSQL's default and SQLite's `BINARY` — except
where the column compares by value or the field's own writer normalises. A
`Types::GUID` half on PostgreSQL is the first: the column is a native `uuid`
compared by value, `GuidType` reads a string back untouched, so an uppercase
spelling passes the shape check and the print-back rule and resolves the record
the lowercase key names. A type whose writer normalises is the second, on every
platform: the M7 · 9 fixture `CaselessType` lower-cases on write and reads back
untouched, so `AB` and `Ab` both print back to themselves, both bind as `ab`,
and `toIdentifier()` answers the non-canonical map it was handed. Both are
self-aliasing — the row reached is the one the sender addressed. Four
residuals are recorded rather than closed; the fourth is
[a type that cannot read its own printed form](#a-type-that-cannot-read-its-own-printed-form-mints-keys-that-never-decode),
and it is the one that took something away — narrowed by
[M7 · 5b](../roadmap.md#m7--composite-identifiers) to the type that refuses
that form on write too, since
[the writer fallback](#a-type-that-refuses-its-printed-form-on-read-only-decodes-it-through-its-own-writer)
gives back what the strict decode took from every other.

`decimal` is **narrowed, not closed** by its shape check: `10.5` and `10.50`
still name one record, because nothing here can canonicalise scale; `-0` and
`-0.0` are spellings of zero the same way; a PostgreSQL `numeric` holding `NaN`
or `Infinity` mints a key the decode then refuses, so such a row is unreachable
by key — a `null`, never a throw; and an absurdly long decimal still raises
`22003` on PostgreSQL, the decimal sibling of the integer-width residual below.
One more belongs to the platform rather than the type: the decimal shape check
is platform-independent while `RecordKey::of()` mints unchecked, so a platform
that hands the value back in a form the pattern refuses makes a row's own key
name no record. Executed on SQLite: a `NUMERIC(30,2)` identifier
`123456789012345678901.50` reads back as `1.2345678901235E+20` under column
affinity, which the pattern refuses. It takes an identifier beyond about fifteen
significant digits on a platform with float affinity to reach; a platform
handing back `.5` for a value below one would be the same shape, and that one is
not executed. Same class as the `guid` residual one type over, and unlike that
one it is not gated, because the decimal form the check accepts is the one every
supported platform prints.

`guid` is shape-checked **only where the platform declares a guid type of its
own** — PostgreSQL's `UUID`, SQL Server's `UNIQUEIDENTIFIER`, which
`IdentifierFields` recognises by comparing `getGuidTypeDeclarationSQL()` against
a fixed 36-character string declaration, since DBAL 4 ships no
`hasNativeGuidType()`. Everywhere else — SQLite, MySQL, MariaDB, Oracle — the
column is a plain `CHAR(36)` holding whatever the application put in it, and
refusing a non-UUID spelling there would refuse a key the record side itself
minted: a legacy table keyed on un-hyphenated text goes on resolving, which is
the point of gating rather than dropping the check. The check reaches a type by
**class**, not by name, since M7 · 9: a type an application registers under a
name of its own and derives from `BigIntType`, `DecimalType` or `GuidType` is
checked as the family it extends, where it used to escape the check — a
`bigint` one turning a malformed `{record}` into a `TypeError` 500 rather than a
404 with no statement.

The third belongs to the **collation**, not to any type. A key is compared with
`=`, so a platform whose collation ignores case or accents resolves a record
from any spelling that collation equates — MySQL 8's default
`utf8mb4_0900_ai_ci`, MariaDB's own default, which is a different collation and
also case-insensitive, and SQL Server's default CI collation. It reaches every
text-compared component alike: `string`, `ascii_string`, `text`, `guid`, and a
string-backed value object or enum that preserves the case it was given. A
`decimal` is not among them — the column compares numerically, and its own
residual is the scale aliasing above. Symfony's `uuid`, `integer` and a value
object that normalises in its constructor are immune, because their reader
normalises and the print-back rule leaves them one accepted spelling; a plain
`guid` column on PostgreSQL is not, as above. It is self-aliasing only — the identifier's unique index sits under
that same collation, so the record a loose spelling reaches is the one the
sender was already addressing and no scope is escaped — and an application that
wants strictness declares the identifier column `BINARY`/`utf8mb4_bin` or
normalises in the entity.

A **NUL byte** is not a residual, because `printable()` refuses one at both
ends: no record mints a key carrying it, and no key carrying one decodes. A text
parameter reaches PostgreSQL as a C string, so `abc=00zzz~p` would otherwise be
compared as `abc` and hand that record a second canonical-looking spelling.
Platforms disagree about whether such a row can exist at all: PostgreSQL cannot
store a NUL in a text column, while SQLite stores `abc\0xyz` whole and hands all
seven bytes back (executed). Refusing it at the one print rule is deliberate
anyway — a key must mean the same record on every platform, and the key's own
alphabet never carries a NUL. The accepted cost is the other direction: a record
whose identifier holds one now refuses to mint a key at all, with the
`is not a printable value` `LogicException`, where before it rendered.

Worked, all **verified by executing** the reference implementation against a
SQLite rig:

| Identifier values | Key |
| --- | --- |
| `('plain', 'about')` | `plain~about` |
| `('www.example.com', '/blog/2026/bundles all the way down')` | `www=2Eexample=2Ecom~=2Fblog=2F2026=2Fbundles=20all=20the=20way=20down` |
| `('example.com', '/clanky/Žofia~Návratová')` | `example=2Ecom~=2Fclanky=2F=C5=BDofia=7EN=C3=A1vratov=C3=A1` |
| `('a=b', 'c\|d')` | `a=3Db~c=7Cd` |
| `('x y', 'p%2Fq')` | `x=20y~p=252Fq` |
| `Venue('main-hall')` + `'autumn/2026'` (derived identity) | `main-hall~autumn=2F2026` |
| `Zone::Footer` + `2` (a backed enum plus a scalar) | `footer~2` |
| `1`, `2` (scalar pair) | `1~2` |
| `'sy/0.1'` (a **single** string identifier) | `sy/0.1` — verbatim, see the divergence below |

### Where a key travels, and what each context forbids

The alphabet is not a preference. A key crosses **six** contexts on one round
trip, and every character outside `[A-Za-z0-9_-]` `~` `=` breaks at least one of
them — four of the six **silently**, which is why the format escapes rather than
picking a delimiter and hoping, as every comparable ecosystem does.

| Context | Mechanism | Forbidden | Evidence |
| --- | --- | --- | --- |
| One URL path segment | `RouteCompiler` compiles `{record}` to `[^/]+`; `UrlGenerator` maps `'%252F' => '%2F'` out of `rawurlencode`, and the matcher `rawurldecode`s the whole path **before** matching | `/`, and the literal sequence `%2F` — which round-trips into a slash and 404s with no error; and, wherever the route's static text after the placeholder opens on a `RouteCompiler::SEPARATORS` character, that character too, since the compiler's default pattern excludes it — `-`, `_`, `~` and `=` are all in that set, which is why [panels narrows the placeholder](../panels/custom-pages.md#the-loader-narrows-a-record-placeholder-a-separator-follows-because-the-compilers-default-excludes-it) | [`RouteCompiler.php:165-171`](../../../vendor/symfony/routing/RouteCompiler.php), [`Generator/UrlGenerator.php:55-74`](../../../vendor/symfony/routing/Generator/UrlGenerator.php) and `:201`, [`Matcher/UrlMatcher.php:73`](../../../vendor/symfony/routing/Matcher/UrlMatcher.php) |
| A dotted state path — `Repeater` keys a relationship item `record-<key>` | `PropertyAccessor` splits every path on `.`, at eight sites | `.`, which does not error — it **re-addresses the state tree one level deeper** | [`PropertyAccessor.php`](../../../packages/schemas/src/State/PropertyAccessor.php) `:32`, `:57`, `:88`, `:129`, `:284`, `:305`, `:324`, `:338` |
| The live `data-model` directive the state path is embedded in | UX's directive parser is a character state machine reserving `(`, `)`, `,`, `\|` and space; `normalizeModelName` rewrites `[` and `]` into `.` | `( ) , \| [ ]` and space | [`HasStateBindingModifiers.php:54-72`](../../../packages/schemas/src/Components/Concerns/HasStateBindingModifiers.php); `vendor/symfony/ux-live-component/assets/dist/live_controller.js:204-240`, `:264-266` |
| The state-path sanitiser, in **both** `HasState` traits | a `preg_replace` that **strips** rather than refuses, so two records could share one path | `< > " '` `` ` `` and C0/DEL | [`Concerns/HasState.php:33-41`](../../../packages/schemas/src/Concerns/HasState.php), [`Components/Concerns/HasState.php:67-75`](../../../packages/schemas/src/Components/Concerns/HasState.php) |
| A DOM id, a `<label for>` and a CSS class token | the row id, the cell id and `data-sortable-item` interpolate the **raw** key as idiomorph's morph identity; panels stamps `sy-pa-resource-record-<key>`; `LiveComponent::getChildId()` sanitises lossily | whitespace (an invalid id, and a class token splits in two); anything outside the class `getChildId()` admits | [`Table/index.html.twig:794`](../../../packages/tables/templates/partials/Table/index.html.twig), `:795`, `:864`; [`EditRecord.php:166`](../../../packages/panels/src/Resources/Pages/EditRecord.php), [`ManageRelatedRecords.php:180`](../../../packages/panels/src/Resources/Pages/ManageRelatedRecords.php); [`LiveComponent.php:93`](../../../packages/schemas/src/Components/LiveComponent.php) |
| A Twig `==` or `in` over an option value | compiles to PHP's native `==` on PHP 8, which aliases two numeric strings | an all-digit key for a class whose identifier is composite — which the grammar makes impossible | [`EqualBinary.php:19-27`](../../../vendor/twig/twig/src/Node/Expression/Binary/EqualBinary.php); [`Select/index.html.twig:86`](../../../packages/forms/templates/partials/Select/index.html.twig) |

Two consequences the table encodes rather than states. `%` is never emitted at
all, so the `%2F` trap cannot fire from our side. And `getChildId()`'s sanitiser
was **widened to `[^A-Za-z0-9_~=-]`** by M7 · 1 so the grammar survives it — see
[live-component.md](live-component.md#the-child-identity-is-an-id-prop-and-a-keyless-embed-throws).

No ecosystem escapes its delimiter: Doctrine's own identity hash joins with a
space, Sonata joins with `~`, API Platform writes `a=1;b=2`, Rails joins with
`_`, and EasyAdmin and the Django admin refuse composite keys outright. The
choice here was therefore between adopting a known-ambiguous convention and
exceeding it, and the six contexts above are the named, checkable gap that
[principles §2](../../principles.md) asks for before inventing.

### The API

`Sylament\Schemas\Identity\` is a capability directory at `src/` level, beside
`Query\` and `State\`, per `.claude/rules/php.md`'s rule that the engine a trait delegates to
is filed by capability. Three public classes; `IdentifierFields` is `@internal`
and is the per-class descriptor the other two share — field names in mapping
order, whether each is a to-one association, and the resolved DBAL type name each
binds as. `@internal` means *no part of the key contract*, not *no reader outside
this directory*: since M7 · 5b the relation layer's owner bind reads the
terminal field's type off it, which makes `State\AssociationReader` its first
reader outside `Identity\` — same package, and
[recorded with the bind](association-reader.md#a-single-identifier-owner-binds-its-identifier-value-typed-by-the-fields-own-dbal-type).

```php
final class RecordKey
{
    public const string SEPARATOR = '~';

    /**
     * Each component as its row would read it back — through its field's DBAL type to the database form and back.
     *
     * @param class-string|null $class the mapped class to key by, when the caller declares one
     *
     * @throws LogicException when the identifier is incomplete, or a component is not printable
     */
    public static function of(EntityManagerInterface $entityManager, object $record, ?string $class = null): string;

    /**
     * Null while a component is missing or blank — a `Stringable` that prints blank included; otherwise minted as of() mints.
     *
     * @param class-string|null $class the mapped class to key by, when the caller declares one
     *
     * @throws LogicException when a component is not blank and still has no printed form
     */
    public static function find(EntityManagerInterface $entityManager, object $record, ?string $class = null): ?string;

    /**
     * @param class-string $class
     * @param list<mixed>  $values in the mapping's identifier order
     */
    public static function fromValues(EntityManagerInterface $entityManager, string $class, array $values): string;

    /**
     * @param class-string $class
     *
     * @return array<string, mixed>|null field ⇒ the PHP value its type holds, in mapping order — what EntityManager::find() takes; null when malformed
     */
    public static function toIdentifier(EntityManagerInterface $entityManager, string $class, string $key): ?array;

    /**
     * @internal read by the relation layer — the owner bind and the record check's taken-key and changed-key passes — and by ReplicateAction's blank-key check, and not part of the key contract
     *
     * @param class-string|null $class the mapped class to read the record by, when the caller declares one
     *
     * @return list<mixed>|null each identifier value as its own field's type holds it, an association reduced to its target's; null while one is missing
     */
    public static function values(EntityManagerInterface $entityManager, object $record, ?string $class = null): ?array;
}

final class IdentifierConstraint
{
    /** @param class-string $class */
    public static function equals(QueryBuilder $query, string $alias, string $class, int|string $key, string $parameter): ?string;

    /** @param class-string $class */
    public static function notEquals(QueryBuilder $query, string $alias, string $class, int|string $key, string $parameter): ?string;

    /**
     * @param class-string     $class
     * @param list<int|string> $keys
     */
    public static function in(QueryBuilder $query, string $alias, string $class, array $keys, string $parameter): string;

    /**
     * @param class-string     $class
     * @param list<int|string> $keys
     */
    public static function notIn(QueryBuilder $query, string $alias, string $class, array $keys, string $parameter): ?string;

    /**
     * `in()` over raw SQL: the class's identifier columns, quoted where the mapping quotes them, bound on a DBAL builder
     *
     * @param class-string     $class
     * @param list<int|string> $keys
     */
    public static function inColumns(EntityManagerInterface $entityManager, \Doctrine\DBAL\Query\QueryBuilder $query, string $class, array $keys, string $parameter): string;

    /** `alias.f1 = outer.f1 AND …` — text, because RelationConstraint splices a subselect string */
    public static function correlate(QueryBuilder $query, string $alias, string $class, string $outerAlias): string;

    public static function select(QueryBuilder $query, string $alias, string $class, string $resultAlias = 'sy_key'): KeySelection;

    /** @return list<string> `alias.field` per identifier field */
    public static function groupBy(QueryBuilder $query, string $alias, string $class): array;

    public static function orderBy(QueryBuilder $query, string $alias, string $class, string $direction): void;

    /**
     * @param class-string     $class
     * @param list<int|string> $keys
     *
     * @return list<list<string>>
     */
    public static function chunk(EntityManagerInterface $entityManager, string $class, array $keys): array;

    /**
     * @param class-string     $class
     * @param list<int|string> $keys
     *
     * @return list<list<string>>
     */
    public static function batch(EntityManagerInterface $entityManager, string $class, array $keys): array;
}

final readonly class KeySelection
{
    /** @var list<string> e.g. ['IDENTITY(r.venue) AS sy_key_0', 'r.season AS sy_key_1'] */
    public array $expressions;

    /** @param array<array-key, mixed> $row one scalar result row, every value raw */
    public function keyOf(array $row): string;

    /** @param array<array-key, mixed> $row an array result row, or one Doctrine's limit walker answers */
    public function keyOfHydrated(array $row): string;
}
```

`of()` and `find()` key a record by its own class unless the caller **declares**
one through the optional third argument: a table passes its record class, so a
subclass the mapping does not know — a test double, an old-style proxy — keys by
the class the table was built over rather than failing a metadata lookup.

**There is no field-names accessor, and that is a decision.** A consumer that
needs the identifier's field names or its arity reads
`ClassMetadata::$identifier` and `isIdentifierComposite`, which it already holds
whenever it holds the class — the foundation exposes neither, so that nothing
grows a second, drifting copy of the mapping. `IdentifierFields` carries them
`@internal` for the two public classes; M7 · 4
deleted `AssociationReader::getIdentifierFieldName()` with its last caller. Its
seven **external** call sites became `IdentifierConstraint` operations rather
than a member here, while of the four uses inside the reader itself the
owner-field read became `getOwnerIdentifierField()`, a private
`$metadata->identifier[0]` — the one replacement that is no operation of this
foundation at all.

Read the shapes off those signatures in one line each. Every **predicate** method
binds its values on the `QueryBuilder` it is handed and returns DQL **text** for
the caller to place — in a `WHERE`, an `andWhere`, a `DELETE` or a spliced
subselect — so the builder never decides where the predicate goes; `inColumns()`
is the one that returns **SQL** text, for a DBAL statement, and binds on the DBAL
builder it is handed.
`select()`, `correlate()` and `groupBy()` bind nothing at all. `equals()`,
`notEquals()` and `notIn()` answer `null` rather than throwing, and `null` means
*there is no predicate to add*, never *something went wrong*.

A key list may hold ints beside strings — PHP turns a numeric string array key
into an int, so `array_keys()` of a map keyed by record key hands one back — and
each is cast to its string once, where it enters; a key stays a string
everywhere below that, and `chunk()`/`batch()` hand strings back.

**The decode is one pipeline, and every operation shares it.** A key is split
into components (a single-field identifier is one component, verbatim); each
component is **shape-checked** if and only if its field's type is, or extends,
`BigIntType`, `DecimalType` or `GuidType` — the three families, matched by
class, whose own conversion hands a malformed string straight back, so nothing
downstream would refuse it before the database does, the `guid` arm running
only where the platform declares a guid type of its own; each is then run
through its field's `Type::convertToPHPValue()` — and where that raises a
`ConversionException`, through
[the type's own writer first](#a-type-that-refuses-its-printed-form-on-read-only-decodes-it-through-its-own-writer),
`convertToPHPValue(convertToDatabaseValue($component))`, a
`ConversionException` out of that pair meaning *the key names no record*, not
an error; and finally the converted
PHP value must **print back to the component it came from** under the one
printable rule below. `toIdentifier()` answers the result of that pipeline — the
**PHP values**, keyed by field name, which is exactly what
`EntityManager::find()` takes. The predicate methods take it one step further and
bind `convertToDatabaseValue()` of those same values, with the type's
`getBindingType()`; a single-field batch binds the converted list under the
matching `ArrayParameterType`. The round trip is deliberate: a DBAL type's
`convertToDatabaseValue()` is contracted for the *PHP* value, and a custom
value-object identifier type may be strict about it where today's untyped string
bind happened to work. Anything other than a `ConversionException` that a custom
type throws — including a refusal of the value it has just produced — is that
type breaking DBAL's contract, and propagates — a writer that throws so for a
forged component included, since the fallback now hands it one. **A field or
column name reaches DQL or SQL from `ClassMetadata` only**, never from the key.

**One printable rule, used on both sides.** `IdentifierFields::printable()` is
what the record side prints a value with and what the decode compares against, so
encode and decode cannot drift: a `BackedEnum` becomes its backing value, a
`Stringable` its `__toString()`, a `bool` `'1'` or `'0'` (a plain cast prints
`false` as the empty string, which would mint an illegal empty component), any
other scalar its `(string)` cast — and a result that is non-scalar, not valid
UTF-8, blank, or carrying a **NUL byte** is **not printable**: `of()` and
`fromValues()` refuse it with today's `is not a printable value` message, and
the decode answers `null`.

For an association identifier field the type is the **target's** identifier
field type, followed through a target whose own single identifier is itself an
association — so `Residency.venue` binds as the `Venue.handle` type, and a value
selected through `IDENTITY()` is run back through that type's
`convertToPHPValue()` before it is printed, which is what makes the row side and
the record side agree.

### The operations and their DQL

Three classes stand in below, all real fixtures: `Song` (`#[Id] int $id`) for the
single-identifier lane that must not move; `Shortcut` (`#[Id] string $host`,
`#[Id] string $path`) for a scalar pair; and `Residency` (`#[Id]` ManyToOne
`Venue`, `#[Id] string $season`) for a derived identity, whose `Venue` is itself
keyed on a `Handle` value object through a custom DBAL type — so the association
arm is exercised with a type that actually converts.

| Operation | Single identifier | Scalar pair | Derived identity |
| --- | --- | --- | --- |
| `equals(…, 'sylament_record_key')` | `song.id = :sylament_record_key` | `shortcut.host = :sylament_record_key_0 AND shortcut.path = :sylament_record_key_1` | `residency.venue = :sylament_record_key_0 AND residency.season = :sylament_record_key_1` |
| `notEquals(…, 'sylament_ignored_record')` | `song.id <> :sylament_ignored_record` | `NOT (shortcut.host = :sylament_ignored_record_0 AND shortcut.path = :sylament_ignored_record_1)` | `NOT (residency.venue = :sylament_ignored_record_0 AND residency.season = :sylament_ignored_record_1)` |
| `in([$a, $b], 'sylament_selected_record_keys')` | `song.id IN (:sylament_selected_record_keys)` | `((shortcut.host = :sylament_selected_record_keys_0_0 AND shortcut.path = :sylament_selected_record_keys_0_1) OR (shortcut.host = :sylament_selected_record_keys_1_0 AND shortcut.path = :sylament_selected_record_keys_1_1))` | same shape, over `residency.venue` and `residency.season` |
| `in([], …)` | `1 = 0` | `1 = 0` | `1 = 0` |
| `notIn([$a], 'sylament_selected_record_keys')` | `song.id NOT IN (:sylament_selected_record_keys)` | `NOT ((shortcut.host = :sylament_selected_record_keys_0_0 AND shortcut.path = :sylament_selected_record_keys_0_1))` | same shape |
| `notIn([], …)` | `null` — **no predicate at all** | `null` | `null` |
| `correlate('i', …, 'o')` | `i.id = o.id` | `i.host = o.host AND i.path = o.path` | `i.venue = o.venue AND i.season = o.season` |
| `select(…)->expressions` | `['song.id AS sy_key_0']` | `['shortcut.host AS sy_key_0', 'shortcut.path AS sy_key_1']` | `['IDENTITY(residency.venue) AS sy_key_0', 'residency.season AS sy_key_1']` |
| `groupBy(…)` | `['song.id']` | `['shortcut.host', 'shortcut.path']` | `['residency.venue', 'residency.season']` |
| `orderBy(…, 'asc')` on an unordered query | `ORDER BY song.id ASC` | `ORDER BY shortcut.host ASC, shortcut.path ASC` | `ORDER BY residency.venue ASC, residency.season ASC` |
| `orderBy(…, 'desc')` after `orderBy('residency.season', 'desc')` | `ORDER BY song.id DESC` untouched when already ordered by it | appends only the fields not already named | `ORDER BY residency.season DESC, residency.venue DESC` |
| `chunk(…)` — keys per statement | 2 000 | 500 | 500 |

Four things that table encodes.

**`IDENTITY()` appears in a `SELECT` list and nowhere else.** The plain path
`alias.field` is what goes into `WHERE`, correlation, `GROUP BY`, `ORDER BY` and
a DQL `DELETE` — legal for an identifier association because such an
association always has exactly one join column — while `IDENTITY()` is *illegal*
in `GROUP BY` and emits a table-qualified column in a `DELETE`. Conversely a bare
association path is a semantical error in a `SELECT` list, which is why the
projection is the one place that uses `IDENTITY()`. The plain path is also what
[`ColumnPathResolver::resolveOrderable()`](../tables/table.md#a-dotted-name-is-resolved-against-the-mapping-before-it-is-refused)
emits, which is what keeps the sort tiebreak's string dedupe honest.

**The single-identifier emission is today's, byte for byte.** `alias.id = :base`
and `alias.id IN (:base)` are unchanged — only now typed — and the DQL-text
assertions in `HasBulkActionsTest` and `CanSortRecordsTest` did not move when
M7 · 1 landed. That is the property that makes this a foundation rather than a
rewrite. The one single-identifier statement that moved since, the by-key
dissociate's, which M7 · 5b rewrote in SQL over
[`inColumns()`](#incolumns-is-in-in-sql-for-a-statement-that-names-one-table),
is DQL again since M7 · 7 wherever its foreign key is one column; only a
mapped-by spanning several join columns still writes through `inColumns()`.

**The DQL parses down to ordinary SQL.** `equals()` on a scalar pair executes as
`… WHERE s0_.host = ? AND s0_.path = ?`, on a derived identity as
`… WHERE r0_.venue_id = ? AND r0_.season = ?`, and a DQL `DELETE` over two
composite keys as
`DELETE FROM Residency WHERE ((venue_id = ? AND season = ?) OR (venue_id = ? AND season = ?))`
— all three executed.

**A `KeySelection` is read back through `keyOf($row)` or `keyOfHydrated($row)`,
never re-derived.** Each reads exactly the aliases it projected, throwing a
`LogicException` naming the class and the missing column if the row came from a
different selection, and hands the values to its held
`IdentifierFields::toKey()`; they differ in which values they convert through the
field's own DBAL type first, and the caller picks by the hydrator that produced
the row. `keyOf()` takes a **scalar** row, whose every value is raw, and converts
every value it was not handed as an object. `keyOfHydrated()` takes an **array**
row, or a row Doctrine's limit walker answers, whose field paths the hydrator has
already read through their types, and converts only the `IDENTITY()` association
halves, which arrive raw under every hydrator. Either way that is the *same*
print step the record side uses, by construction rather than by agreement; see
[the semantics note](#the-row-side-and-the-record-side-mint-one-string-and-only-the-codec-may).

#### `inColumns()` is `in()` in SQL, for a statement that names one table

[M7 · 5b](../roadmap.md#m7--composite-identifiers)'s, and the one operation
here that emits **SQL**. Its caller is the by-key dissociate over a mapped-by
spanning several join columns, whose statement there is a DBAL `UPDATE` of one
table: there is no identification variable to qualify a
field with, and a to-one spanning several join columns has no DQL form to null
it in at all
([why the statement left DQL](../actions/dissociate-action.md#the-by-key-arm-is-a-dql-update-and-a-dbal-one-where-the-foreign-key-spans-several-columns)).
So the operands are the class's identifier **columns** in identifier order, as
`QuoteStrategy::getIdentifierColumnNames()` names them — quoted only where the
mapping quotes them, so an ordinary mapping's statement carries bare
`host = ?` — with no alias, and the parameters are bound on the DBAL builder it
is handed. A DBAL builder carries no entity manager, so the manager is the
leading argument, as it is for `chunk()`.

Worked over the schemas fixtures its own test runs on — `Post` (`int $id`),
`Redirect` (`host` plus `path`), `PostTranslation` (`#[Id]` `Post` plus
`locale`) and `PostSeal` (a single derived identity onto `Post`) — each as the
`WHERE` of a real statement:

| Class | `inColumns($entityManager, $query, …, $keys, 'keys')` | Bound |
| --- | --- | --- |
| `Post`, keys `1`, `3`, `abc`, `3` | `id IN (:keys)` | `[1, 3]` as `ArrayParameterType::INTEGER` — `abc` dropped, the repeat bound once |
| `Redirect`, two keys | `((host = :keys_0_0 AND path = :keys_0_1) OR (host = :keys_1_0 AND path = :keys_1_1))` | four `ParameterType::STRING` |
| `PostTranslation`, two keys | `((post_id = :keys_0_0 AND locale = :keys_0_1) OR (post_id = :keys_1_0 AND locale = :keys_1_1))` — the **join column**, never the field | `INTEGER`, `STRING` per key |
| `PostSeal`, keys `1`, `2` | `post_id IN (:keys)` | `[1, 2]` as `ArrayParameterType::INTEGER` |
| any class, no key, or none well-formed | `1 = 0` | nothing |

**One membership body, two vocabularies.** `in()` and `inColumns()` share one
private body that takes the left-hand operands — `alias.field` for DQL, the
column for SQL — and the binding step takes the same list, so the two cannot
drift: every `in()` and `equals()` pin exercises the code `inColumns()` runs,
the raw-bytes `BINARY` ones included, and every DQL operation above emits the
text it emitted before. Everything else is `in()`'s: a single identifier is one
`IN` under the array type its binding type maps to, a composite one the balanced
OR of ANDs under `:<base>_<i>_<j>`, a malformed key dropped, a repeated one
bound once, and a base ending in `_<digits>` refused by `assertParameterBase()`.

**It names columns, never a table.** The table, the `SET` half and the quoting
of both are the statement's author's —
[the dissociate's](../actions/dissociate-action.md#the-by-key-arm-is-a-dql-update-and-a-dbal-one-where-the-foreign-key-spans-several-columns)
reads them off the mapping through the same quote strategy, and that is where a
JOINED leaf, a reserved word and a single-table leaf are pinned, through the
real action. The column count agrees with the field count by an ORM guarantee
rather than a check: an identifier association always resolves to
[exactly one join column](#an-identifier-association-always-resolves-to-exactly-one-join-column).
The rest of the vocabulary is pinned over fixtures the test brings along: a
JOINED subclass is named by the identifier column its
root declares — `ledger_id IN (:keys)` for `LedgerDraft`, whose field is
`id` — which its own table keys by too; a composite identifier of reserved words comes
out as `(("where" = :keys_0_0 AND "having" = :keys_0_1) OR …)`; and a raw-bytes
`Crate` or `Pallet` half is bound as its sixteen bytes under
`ArrayParameterType::BINARY` or `ParameterType::BINARY`, where the same bytes
bound as text match no row on SQLite.

**It applies no discriminator and no SQL filter.** A single-table subclass is
named by the columns it shares with its root, so a key naming a row of the root
class selects that row too — pinned as such — and no enabled Doctrine SQL filter
reaches a DBAL statement. A caller narrows by keys a discriminated,
filtered query already produced, which the one caller does
([why that is enough there](../actions/dissociate-action.md#the-by-key-arm-is-a-dql-update-and-a-dbal-one-where-the-foreign-key-spans-several-columns)).
Pinned by
[`IdentifierConstraintColumnsTest`](../../../packages/schemas/tests/Unit/Identity/IdentifierConstraintColumnsTest.php).

### Invariants

Seven, and a consumer may rely on all of them.

1. A key is **one string**. A composite key matches `^[A-Za-z0-9_=~-]+$`,
   contains `~`, and is never numeric.
2. `RecordKey::of()` is **injective and stable across requests**, and
   `KeySelection::keyOf()` over a scalar row selected with `select()`, like
   `keyOfHydrated()` over an array row or a limit-walker row, equals `of()` of
   that record. Each reads a value through its type once; `keyOf()` handed a
   row the hydrator already converted reads it twice, which only a type whose
   reader takes its own PHP value back unchanged survives — every built-in and
   every object-returning type does, while a scalar type whose reader does not
   mints another key, or throws
   ([why the two readers exist](#a-hydrated-row-is-keyed-without-reading-its-fields-a-second-time)).
   `fromValues()` does **not** carry that guarantee over a
   projected row — it prints what it is handed without converting, so it agrees
   only where a value's database form is already its printed one. The second
   half is not decoration: `AggregateQuery::readValues()` fills a store under
   row keys that cells look up by record key, and a disagreement there fills
   the store under keys no cell ever asks for — a cell reading its default,
   with no error anywhere. Its sibling in
   [the page read](../tables/has-table.md#a-keyed-root-that-joins-a-collection-pages-in-two-statements-because-doctrines-walkers-key-by-one-column)
   fails the same way and worse: the key goes into a predicate, so the page
   itself comes back empty.
3. **Client input never throws.** `toIdentifier()`, `equals()`, `notEquals()`,
   `in()` and `notIn()` are total over arbitrary strings — and a map
   `toIdentifier()` answers is always one `EntityManager::find()` can take,
   because the decode has already run each component through its field's DBAL
   type, the same step the predicates bind through. The two answer `null`
   together, over every string: a key that narrows no query decodes to no
   identifier, and a key that decodes never explodes inside `find()` — for every
   field type that honours DBAL's contract by refusing only with a
   `ConversionException`; a custom type that throws anything else propagates
   ([why](#a-type-that-refuses-its-printed-form-on-read-only-decodes-it-through-its-own-writer)).
4. A decoded value is **only ever a bound parameter**; a field name **only ever**
   comes from `ClassMetadata`.
5. A single-identifier class emits **today's DQL text**, apart from the
   `ORDER BY` tiebreak's direction token, which the ORM 3.7 floor prints as
   `ASC`/`DESC` at every width — which binds what `IdentifierConstraint` emits,
   not what a consumer composes around it. The
   relationship query's many-to-many arm moved from an `IN` subselect to a
   correlated `EXISTS` in
   [M7 · 4](../roadmap.md#m7--composite-identifiers) for every width alike, and
   the rows it returns are the rows the `IN` returned. The one statement that
   left DQL — the by-key dissociate — is a DBAL `UPDATE` over `inColumns()`
   only for a mapped-by spanning several join columns, which has no DQL form;
   since M7 · 7 a one-column foreign key is nulled by the DQL `UPDATE` it was
   before M7 · 5b, as
   [enumerated where the statement lives](../actions/dissociate-action.md#the-by-key-arm-is-a-dql-update-and-a-dbal-one-where-the-foreign-key-spans-several-columns).
6. **Four consumers chunk** — the by-key delete, the reorder's re-resolution,
   tables' aggregate batch since M7 · 2 and the by-key dissociate since
   M7 · 4, a DQL statement, and since M7 · 5b a DBAL one where its mapped-by
   spans several join columns. Since M7 · 7 a key list the client sizes is
   batched wherever the identifier is composite; the two reads that still run
   one statement over a whole list are
   [recorded with the chunk rule](#the-chunk-rule).
7. **A key is minted from the value the row would read back.** `of()` and
   `find()` pass each component through its field's DBAL type to the database
   form and back before printing it, so a record this request has just written
   mints the key a later read of its row mints — the one the strict decode
   answers for — while a loaded record, which already holds that value, mints
   what it always did. A blank component is handed back unconverted, so `of()`
   still refuses it as not printable and `find()` still answers `null`; and a
   **non-numeric** string held in an integer-typed half of a new record — which
   only an untyped or `int|string` property can hold — now raises its type's own
   error, or mints `…0`, where it minted a key that never decoded. The round
   trip is the type's, not the database's, so a database that rewrites what it
   stores can still mint two spellings of one row — SQLite keeps a `decimal`
   `10.50` as `10.5`, and the new record mints `…10.50` where the same row,
   loaded, mints `…10.5` — a pre-existing, database-side gap the mint cannot
   see. Since M7 · 6
   ([why](#a-key-is-minted-from-the-value-its-row-would-read-back)).

### The chunk rule

`chunk()` splits a key list into statements of `min(500, intdiv(2000, F))` keys
for `F ≥ 2` identifier fields, and 2 000 keys for one — under SQL Server's
2 100-parameter ceiling, linear in total parse cost, and with the **same DQL
text** for every full chunk of **distinct, well-formed** keys, so such a chunk is
parsed once and served from the query cache thereafter. The two qualifiers are
load-bearing rather than pedantic: `chunk()` **de-duplicates first**, so a
duplicate cannot silently shorten a chunk, but a malformed key inside one is
dropped by `in()` later and that chunk's text is a term shorter — a cache miss,
not a wrong answer. A chunk of nothing but malformed keys still issues its
statement, as `1 = 0`.

The numbers behind it, measured rather than assumed. SQLite refuses a **flat** OR
chain at ~997 terms with `Expression tree is too large` — its limit is expression
*depth*, not parameter count, and its compiled `MAX_VARIABLE_NUMBER` here is
250 000 — which is why the disjunction is nested as a balanced binary tree; with
that shape 1 501 two-field keys execute in ~490 ms and 16 000 have been run.
PostgreSQL 18 takes either shape up to its 65 535 bound-parameter ceiling, but
on a populated table a statement that large crosses `jit_optimize_above_cost`.
One 10 000-key `notIn()` over 10 000 rows spent 337 s of backend time with the
default `jit=on`, and 4.8 s with it off. On the PHP side both building and
parsing were quadratic. The build was ours: one `setParameter()` per bound
value, each a linear lookup in the builder's parameter collection. Since M7 · 7
a composite membership appends its parameters in one pass, and 5 000 keys build
in about 15 ms where they took about 1.8 s. The parse is Doctrine's and stays
quadratic: `SqlWalker::walkInputParameter()` asks `Query::getParameter()`, a
linear filter, once per parameter. A first parse takes about 50 ms at 500 keys,
170 ms at 1 000, 3.4 s at 5 000 and 14 s at 10 000. The 500-key composite chunk
is sized against that curve. On PostgreSQL 18 a 500-key `in()` plans as an index
scan costing about 3 500–4 500 whatever the table size, some 25× under
`jit_above_cost` (100 000). A 500-key `notIn()` is a sequential scan whose cost
grows with the table — about 25 000 at 10 000 rows, 126 000 at 50 000 — so it
crosses `jit_above_cost` from roughly 40 000 rows on, and JIT then adds about
130–280 ms of emission to that one statement (0.54 s against 0.19 s at
50 000 rows, 0.76 s against 0.45 s at 200 000). Through
`getSelectedTableRecords()` only a tracked deselection of 101–500 keys reaches
that shape; a larger one is subtracted in PHP.

**Four sites chunk, and three of them are writes.** The by-key delete issues
one DQL `DELETE` per chunk, several chunks inside one transaction, and the
by-key **dissociate** nulls a one-column foreign key by one DQL `UPDATE` per
chunk under the same rule, and a foreign key spanning several columns by one
DBAL `UPDATE` per chunk, wrapped in one `Connection::transactional()` from the
second chunk on
([dissociate-action.md](../actions/dissociate-action.md#the-by-key-arm-is-a-dql-update-and-a-dbal-one-where-the-foreign-key-spans-several-columns))
— the DBAL arm SQL since M7 · 5b, so no DQL parse is paid there and its chunk
sizes bound the statement's parameter count alone;
the reorder's
re-resolution issues one `SELECT` per chunk, each narrowing a **copy** of the
table's query — a copy because a second predicate ANDed onto the first would
answer nothing, and each chunk keeps the one parameter base, since a base
carrying a chunk index of its own is what `assertParameterBase()` refuses.
Reordering is unpaginated by default, so its order list is the whole table,
which is what made it the second site to earn this.

The one read among the four is tables' aggregate batch, and its chunks combine
as cheaply as the reorder's: it runs the scope closure once on the pristine builder,
narrows a **copy** per chunk under that same single base, and unions the
per-chunk maps with `+` — the chunks are disjoint by construction, since a key
appears in one of them, and each chunk's result is keyed by record key, so
nothing has to be reconciled. That is the same property the reorder's
re-resolution relies on, which is why a chunked read may hydrate: what a chunk
boundary destroys is not hydration but **order**. A read whose caller consumes
the statement's order cannot be chunked — the record step of the keyed
fetch-join page — and a `NOT IN` cannot be chunked at all. The hydrating
selection arm was rejected on the order ground until M7 · 7, and is batched now
for a composite selection larger than one batch, keeping table order within a
batch (below).

**A key list the client sizes is batched wherever the identifier is composite.** That list is untrusted, and one statement over it cost time quadratic in a number the client chooses: a forged 10 000-key selection held a request for about 23 s. `IdentifierConstraint::batch()` answers the batches: the whole list for a single identifier, whose one array parameter scales, and `chunk()`'s batches for a composite one. The sites are:

- The selection's reads in `HasBulkActions`, hydrating and plucking alike, one statement per batch. While tracking, a deselected bag larger than one batch is subtracted in PHP from a pluck of every key the table admits, since a `NOT IN` cannot be split across statements. A bag that fits one batch stays one `notIn()`.
- Under `fetchSelectedRecords(false)` beside `checkIfRecordIsSelectableUsing()`, the records are hydrated once and keyed in PHP instead of bound back as a second key list.
- `AttachAction` and `AssociateAction`'s by-key fetch.
- `Select::getOptionLabelsFromRelationship()`.

A composite selection's hydrated records come back in table order within a batch, and batch after batch across them. Where batches of records merge — the hydrating selection read and the attach and associate fetch — each record is kept once, by identity: two spellings of one key that the database compares equal, such as a decimal `1` and `1.0` or a case variant under a case-insensitive collation, can land in different batches, and one statement returned such a row once.

Two sites still run one statement over a whole list:

- `getSelectedTableRecordsQuery()` returns a builder to its caller (a custom bulk action's `$selectedRecordsQuery`), so it cannot be split, and it binds every key.
- The record step of [the keyed fetch-join page](../tables/has-table.md#a-keyed-root-that-joins-a-collection-pages-in-two-statements-because-doctrines-walkers-key-by-one-column) has a key count bounded by the largest numeric page option.

A single identifier's lists are unchanged: one statement, one array parameter.

Three alternatives were considered and rejected: factoring a batch's leading field (`a = ? AND b IN (…)`), range-checking integers by column width, and a `CONCAT`-keyed `IN` (non-sargable, and it breaks typed binds). A fourth, batching the hydrating selection read, was rejected until M7 · 7 because it reorders results. It is taken now for a composite selection larger than one batch, where the unbounded single statement was the worse cost.

### Parameter naming, and what an empty or malformed key means

Every predicate takes a **caller-supplied parameter base** and derives its names
from it: `:base` for a single field, `:base_<j>` for the fields of one composite
key, `:base_<i>_<j>` for key *i* of a batch. Two things force this. A counter
minted off `count($query->getParameters())` — the trick
[`SearchConstraint`](search-constraint.md) and `Group::mintParameter()` use —
does not move until each `setParameter()` lands, so an F×K application would
collide with itself; and the selection lane puts **two** predicates on one
builder in one request (`getSelectedTableRecordsQuery()` and the selectable
narrowing), where `setParameter()` would overwrite in silence. A composite membership appends its parameters in one pass instead, and refuses by name a base the builder already carries; a single-field predicate still binds through `setParameter()`. The bases in use today
are `sylament_record_key`, `sylament_selected_record_keys`,
`sylament_selectable_record_keys`, `sylament_reordered_record_keys`,
`sylament_summary_keys`, `sylament_agg_keys` and
`sylament_page_record_keys` — every one of the first six the name the
single-identifier code already used, so the predicate text is stable for the
query cache, and the seventh minted by M7 · 2 for a statement that had none.
M7 · 4 added three from the relation layer: `sylament_selected` (a relationship
`Select`'s selected-record lookup), `sylament_values` (its label read) and
`sylament_ignored_record`, which that field's `ignoreRecord` exclusion already
used and which `unique(ignoreRecord:)` **moved onto** — its own base was the
counted `sylament_probe_<n>`, and a base ending in `_<digits>` is refused for
**every** class, a single identifier included, so converting that site without
changing its base would have reddened every `unique(ignoreRecord:)` test in
forms. M7 · 5 added the eleventh, and it is the only one that was a base before
it was this file's: `sylament_relationship_owner`, which both relationship
queries have bound the owner under since the relation layer shipped, is now
`equals()`'s base where the owner is keyed by several fields — minting `_0`,
`_1`, … — and still one plain `setParameter()` of that exact name where it is
not. The two spellings never meet, the route being decided once per builder, and
the name did not move for the same reason the first six did not: a
single-identifier query's text stays stable for the query cache
([the owner route](association-reader.md#an-owner-of-any-width-is-scoped-through-its-own-collection-rather-than-through-a-mapped-by-path)).
M7 · 5b added no base. `inColumns()` binds under its caller's, and its one
caller — the by-key dissociate over a mapped-by spanning several join
columns — keeps `sylament_selected_record_keys` — the base its DQL `in()` bound
before — on a DBAL builder, where `assertParameterBase()` refuses a
`_<digits>` ending exactly as it does on a DQL one.
(One *result* alias did move with M7 · 2: the aggregate batch projects
`sy_key_0` where it projected `sy_key`. One query-cache miss, no behaviour.)

Three rules come with a caller-supplied base, and the first is enforced. **A base
never ends in `_<digits>`**: base `k` with key 1, field 0 and base `k_1` with
field 0 both mint `:k_1_0`, and `setParameter()` would overwrite one with the
other in silence — so a base that ends that way is refused with a
`LogicException` as the developer error it is, rather than answering a query with
the wrong rows. **The text a predicate returns belongs on the builder it was
bound on**: binding is a side effect of asking for the text, so dropping the text
leaves parameters bound to nothing and Doctrine counts them (`the query defines 0
parameters and you bound 2`). And **a base is applied once per builder**: a
second application with fewer keys leaves the first application's parameters
bound and over-counts the same way. None of the bases above is applied twice on
one builder, and since M7 · 7 a composite membership on a DQL builder refuses a
base whose first name that builder already carries.

Empty and malformed, stated once:

| Call | Answer |
| --- | --- |
| `equals()` / `notEquals()` with a malformed key | `null` — nothing to match, nothing to exclude, and **nothing is bound** |
| `in()` with some malformed keys | the malformed ones are **dropped**; duplicates are de-duplicated |
| `in()` with no well-formed key left, or an empty list | `1 = 0` — an empty `Orx` renders as `WHERE ` and is a parse error, so the constant is emitted in PHP before the builder sees it |
| `notIn()` with no well-formed key left, or an empty list | `null` — **omit the predicate entirely**. An empty exclusion set means *every* row, and DBAL expands an empty list to `NOT IN (NULL)`, which matches nothing: a bulk delete after select-all would delete nothing at all, silently. This is the omission `getSelectedTableRecordsQuery()` hand-coded before M7 · 1 |
| `toIdentifier()` with a malformed key | `null`, never an exception |
| `chunk()` with an empty list | `[]` |

"Malformed" is exact, and **applies to single-identifier keys too**. Three
clauses, in the order the decode runs them:

1. **Not canonical** per [the grammar](#the-grammar) — the wrong arity, an empty
   component, a lowercase or short escape, an escape of a `safe` byte, invalid
   UTF-8 after unescaping.
2. **The wrong shape for a type whose conversion refuses nothing** — exactly
   three families of them, matched by class, and the boolean family beside them. A component whose type is or
   extends `BigIntType` must match `^(?:0|-?[1-9][0-9]*)$`, one whose type is or
   extends `DecimalType` `^-?(?:0|[1-9][0-9]*)(?:\.[0-9]+)?$`, and one whose type
   is or extends `GuidType` must be UUID-shaped, because all three types hand a malformed string
   straight back and nothing downstream would refuse it before the database does.
   The `guid` arm runs **only where the platform declares a guid type of its
   own**, which `IdentifierFields::hasNativeGuidType()` decides by comparing
   `getGuidTypeDeclarationSQL([])` against the platform's own fixed
   36-character string declaration: where they differ the column is a `UUID` or
   `UNIQUEIDENTIFIER` and a non-UUID spelling is a syntax error, where they match
   it is a plain `CHAR(36)` holding whatever the record side put there, and
   refusing that spelling would refuse a key the record side minted. The
   `bigint` and `decimal` arms are platform-independent. All three patterns carry
   PHP's **`D` modifier**, so `$` means end of *subject*:
   without it PCRE also matches before a trailing newline and `7\n` would name
   record 7 — the same trap
   [the upload seam](interacts-with-schemas.md#uploadschemacomponentfile-carries-the-bytes-beside-the-seam)
   records, and the reason `HasSchemas.php:230` and
   `Uploads\TemporaryUploads.php:31` anchor theirs the same way.
   A component whose type is or extends `BooleanType` must be `0` or `1`, the
   only two spellings a boolean prints: any other would fail the print-back
   rule anyway, but on PostgreSQL DBAL's boolean **writer**, which
   [the writer fallback](#a-type-that-refuses-its-printed-form-on-read-only-decodes-it-through-its-own-writer)
   reaches for a spelling the reader reads as another value, refuses an
   unrecognised literal with an `UnexpectedValueException` rather than a
   `ConversionException` — so the check refuses it first, at no conversion
   (M7 · 11).
3. **A component its field's type refuses, or one that does not print back to
   itself.** Every other type is left to its own `convertToPHPValue()`, whose
   `ConversionException` means "names no record" — and the converted value is
   then printed with the one printable rule and compared against the component it
   came from. That single compare is what refuses `05`, `5abc`, `"5\n"`, `+5`,
   `1e3`, `-0`, a lowercase spelling of a strict value object, **and**
   `99999999999999999999`, which `IntegerType`'s `(int)` cast *saturates* to
   `PHP_INT_MAX` and would otherwise resolve the `PHP_INT_MAX` row. (`BigIntType`
   does not saturate, which is why clause 2 exists for it instead.) There is no
   integer-family regex beyond `bigint`'s: the print-back compare does that job
   for `smallint` and `integer`, and does it without a per-type table to drift.

### Owner scoping was reserved, and turned out not to be needed

`scopeToOwner()` and the owner tuple were named here so their absence read as a
decision, and [M7 · 5](../roadmap.md#m7--composite-identifiers) closed the row by
**not** building them. The prediction this section carried was wrong in both
halves. They were to be the one operation that **mutates** the builder rather
than only binding on it, and a derived-identity owner was to need a join — inner
for the related query, **left plus `IS NULL`** for the candidate one. Neither
holds: the owner scope the relation layer ships is one `equals()` call against
the alias of a correlated subselect over the owner's **own** collection, so
nothing is mutated, no join reaches the outer builder, and the candidate arm is
that same text under `NOT EXISTS` — which keeps a partially-`NULL` foreign key
where a negated equality drops it
([the shape, and the two measured against it](association-reader.md#an-owner-of-any-width-is-scoped-through-its-own-collection-rather-than-through-a-mapped-by-path)).

The join was not merely unnecessary but **wrong**, executed: it returns the wrong
rows on every many-to-many candidate cell, because narrowing the target by
joining the owner inverts what the negation is taken over. The other candidate,
`IDENTITY(path, 'field')` per key field, cannot reach an association-valued key
half at all. So the epic that would have spent this operation is the one that
recorded the interface **long** — the symmetric duty to
[the rule above](#contract), which asks an epic that finds it short to record the
addition here.

The row is **skipped** rather than done, on the status vocabulary's own terms: a
deliberate non-goal the toolkit works without. Nothing that has no code is ever
`done`, and `scopeToOwner()` has none.

Two more facts M7 · 4 and M7 · 6 will need, recorded now so no later plan is
written against a passing test:

- **A reference by key is `getReference()` only for a class no identifier field
  of which is an association.** Where one is — a derived identity, a join entity
  or a single derived identity — `getReference()` is *state-dependent*:
  it succeeds while the record is already managed and **fatals on a cold identity
  map**, because `ProxyFactory` assigns the scalar component straight to the
  typed association property. Both outcomes were reproduced in one process.
  `find()` works in every state. The codec consumes no reference by key at all;
  [the reader routes between the two](association-reader.md#getreference-decodes-first-and-then-picks-its-route).
- **The codec owns the `mixed` → string narrowing for an entity's identifier.**
  Four hand-written copies of it existed — `HasTable`'s query arm,
  `pluckTableRecordKeys()`'s inline closure, `AssociationReader::findRecordKey()`
  and `Resource::getRecordRouteKey()`. M7 · 1 replaced the first two, M7 · 3
  the third and M7 · 4 the reader's. `HasTable::toRecordKey()` itself
  stays, for the lanes that have no mapping to ask — array records and the `id`
  convention — and it is a deliberately **narrower** copy of the same rule:
  `printable()` additionally unwraps a `BackedEnum`, normalises a `bool` to
  `'1'`/`'0'` and refuses a non-UTF-8 string, none of which an array `__key` or
  an `id` property has ever been allowed to be. Nothing should add a fifth.

## Semantics

### `find()` answers identity, not persistence

`RecordKey::find()` asks "does this record carry a **complete** identifier", and
that is not the same question as "is it persisted". With a generated id the two
coincide; with an **assigned** one — which is every composite key, since Doctrine
refuses an id generator on a composite identifier — they part company: a record
whose fields the user has filled in is keyable before the first `flush()`, and
`find()` answers its key. A caller that needs persistence asks the `UnitOfWork`.

A component that is `null` **or still blank** — a whitespace-only string, or a
`Stringable` that prints as one, the test support's `blank()` applies — counts
as not assigned yet, and
`find()` answers `null` for it: an assigned string key sits at `''` until a form
fills it, and a create form's `unique(ignoreRecord:)` must be able to ask without
a throw. `of()` draws the line differently on purpose and keeps both refusals a
table has always raised — `has none set` for a missing component, `is not a
printable value` for a blank or unprintable one — because a row index cannot
proceed without a key.

Before M7 · 9 the test read `is_string()` first, so a value object printing
blank — a slug object whose constructor defaults to `''` — reached the printer
and threw *"is not a printable value"* out of `find()`, where a blank string
answered `null`.

Completeness is **counted**, never tested for emptiness, and this is the trap the
method exists to close. `ClassMetadata::getIdentifierValues()` **drops** a null
component on the composite branch rather than returning it, so an `=== []` test
is correct for one field and wrong for two — a half-filled pair would read as
complete. The count is compared against `count($metadata->identifier)` **at
every level**: an association component is flattened by recursing into the
target's own identifier, which counts again there, so a derived identity whose
target is itself keyed by an association is complete only if the whole chain is.
That recursion is the codec's own — one private method that also subsumes the
completeness count and needs no unit of work; see
[the divergence](#doctrines-identifierflattener-is-not-adopted-because-it-misreads-a-chained-derived-identity)
for why Doctrine's `IdentifierFlattener` is not what runs here.

The two refusals name **different classes**, which is deliberate rather than an
oversight: `has none set` names `$record::class`, because the record that turned
up without an identifier is what a reader has to find, while `is not a printable
value` names the **declared** class the key was being minted for, since that is
the mapping whose field could not be printed. On the common path they are the
same string; they part only for the unmapped-subclass case the third argument
exists for.

### The row side and the record side mint one string, and only the codec may

`KeySelection`'s readers and `RecordKey::of()` must produce the same string for
the same row, or a store filled under row keys is read by cells asking for record
keys and every cell quietly renders its default. They are the same function by
construction: a reader normalises its row's values and hands them to
`IdentifierFields::toKey()`, which is the one print step `RecordKey::fromValues()`
calls too — so `of()` reaches it after flattening, and neither side owns a copy.

The normalisation is where a hand-rolled copy would go wrong, and the rule is
**one rule, not a table of special cases**: every value the row hands back still
raw is run through its field's own DBAL type, once, before it is printed. Which
values are raw is the hydrator's answer, and **no hydrator converts every key
column and the hydrators disagree about which ones they do**. The scalar
hydrator converts *none* — an aliased select is `isScalar`, and
`AbstractHydrator::gatherScalarRowData()` skips both the DBAL type and
`enumType` for those under a documented `// WARNING: BC break!` decision
(`vendor/doctrine/orm/src/Internal/Hydration/AbstractHydrator.php:422-424`). The
array hydrator converts a *field path* but not an `IDENTITY()` expression, which
`SqlWalker` registers as a plain `'string'` (`SqlWalker.php:1338`, against
`:1303` for a field path). So a `Handle`-keyed `Venue` reached through
`IDENTITY()` arrives raw under **both** hydrations, a `BackedEnum` arrives as an
*instance* under the array hydrator and as its **backing value** under the scalar
one, and converting only the association arm of a scalar row mints a different
string on the row side than the record side mints. So `keyOf()`, over a scalar
row, converts every value, and `keyOfHydrated()`, over an array row, converts the
association halves alone ([the second reader](#a-hydrated-row-is-keyed-without-reading-its-fields-a-second-time)). A value the
hydrator already built into an **object** is the one thing left alone, and it
needs nothing: the printable rule unwraps a `BackedEnum` to its backing value and
a `Stringable` to its string, which is exactly what the record side does with the
same object. The rule that falls out: a row-to-key decode is **called**, never
re-derived at a call site.

### A hydrated row is keyed without reading its fields a second time

Until [M7 · 11](../roadmap.md#m7--composite-identifiers) there was one reader,
`keyOf()`, converting every non-object value — sound over a scalar row, and over
an array row only while reading a value twice changes nothing. That holds for
every DBAL built-in and every object-returning type, and fails for a scalar type
whose reader is not idempotent, the shape no fixture had: the tests'
`HexCodeType` stores an integer and reads it as its lower-case hex spelling,
taking whatever it is handed as a decimal number, so the array hydrator reads
the column's 255 as `ff` and a second read turns `ff` into `0` — a key no record
mints, with no error anywhere — while the strict `MarkedCodeType`, which stores
`m:<value>` and refuses an unprefixed one, threw a `ConversionException` on the
second read. Four consumers read such rows: actions' attach and associate option
projections and tables' aggregate batch read `getArrayResult()`, and tables'
two-step page reads the rows Doctrine's `LimitSubqueryOutputWalker` answers,
whose result-set mapping converts a field column regardless and leaves an
association column raw. So a lenient type offered options under `0`, filled
aggregate cells under keys no cell asked for, and paged an empty window; a
strict one made the option read and the page throw.

`keyOfHydrated()` is the reader for those rows: it takes every field value as
the hydrator handed it and converts only the indexes `IdentifierFields`
marks as associations, since an `IDENTITY()` half arrives raw under every
hydrator. The four consumers moved onto it, and tables' by-key selection pluck,
which reads `getScalarResult()`, keeps `keyOf()`. A converting association half
stays covered — `ListingNote`, keyed through `IDENTITY()` onto a `Sku`, keys
from an array row as its record does. Pinned in
[`KeySelectionTest`](../../../packages/schemas/tests/Unit/Identity/KeySelectionTest.php)
over both types at both widths, and at the four consumers in tables'
[`AggregateQueryTest`](../../../packages/tables/tests/Unit/Query/AggregateQueryTest.php),
[`FetchJoinPageQueryTest`](../../../packages/tables/tests/Unit/Query/FetchJoinPageQueryTest.php)
and [`HexKeyAttachAssociateActionsTest`](../../../packages/tables/tests/Integration/HexKeyAttachAssociateActionsTest.php).

### A malformed key never reaches the database

A key is client input, and the guard is a strict decode *before anything is
bound* — not the typed bind, which is only the second half. The typed bind alone
does not close the hole: on PostgreSQL 18 a `'5abc'` bound `PARAM_INT` raises
`22P02` under native prepares and, under emulated prepares, **matches record 5**.
And a `DriverException` is not an `HttpExceptionInterface`, so
`PageController::__invoke()`'s render catch, which rethrows a Twig `RuntimeError`'s previous exception only when it is an `HttpExceptionInterface`, does not convert it — the render ends in a
**500**.

That 500 is still live wherever a client-supplied key is bound outside the table
lane, and naming those here is the point.
`Resource::resolveRecordRouteBinding()` was one:
**[M7 · 3](../roadmap.md#m7--composite-identifiers)** routed its default arm
through this decode, so `/admin/articles/abc/edit` is a 404 that runs no
statement and `/admin/articles/05/edit` no longer resolves article 5 — while its
`$recordRouteKeyName` arm binds one named column raw and
[keeps the 500](../panels/README.md#a-custom-recordroutekeyname-names-one-column-and-the-codec-never-sees-it).
The relation layer bound untyped too, and
**[M7 · 4](../roadmap.md#m7--composite-identifiers) closed it**: a relationship
`Select`'s selected-record and option-label lookups and `AttachAction`,
`AssociateAction` and `DissociateBulkAction`'s key `IN` all take client state, and
all of them decode before they bind, while `Select::relationship(ignoreRecord:)` and
`unique(ignoreRecord:)` bind a server-derived identifier, so those two converted for
composite correctness rather than for the hole. With the **table** lane closed by
M7 · 1 — row resolution, editable cells, selection, reorder and the by-key
delete — and panels' default route arm by M7 · 3, the one client-supplied key
still bound raw is `Resource`'s `$recordRouteKeyName` arm, deliberately.

So `equals()` on a malformed key returns `null` and binds nothing, and the
caller's contract is to answer "no such record" **without querying**. This is
pinned by asserting the refusal — that no statement was executed, through a
recording middleware — rather than by asserting a platform's leniency, which
differs per driver and per prepare mode.

### The batch is a balanced OR tree because SQLite counts expression depth, not terms

`in()` over K composite keys emits K parenthesised AND groups, nested as a
**balanced binary tree** rather than chained left to right. A flat chain is an
expression nested K levels deep, and SQLite's `MAX_EXPR_DEPTH` is 1 000, so it
refuses at ~997 terms regardless of how many fields each key has — long before
any bind-parameter limit is in sight. Nesting makes the depth `log2(K)`, and
1 501 keys execute. The tree is invisible to the planner on every platform
measured and costs nothing; the reason it looks over-engineered is that the
constraint it answers is not the one a reader assumes.

### A key is minted from the value its row would read back

`of()` and `find()` hand their flattened values to `fromValues()` through a
private `asStored()`: per component,
`Type::getType($name)->convertToPHPValue($type->convertToDatabaseValue($value, $platform), $platform)`,
typed by `IdentifierFields::of()->types`. Two conversions per component per mint,
none for a blank component, which is handed back as it is, and no
statement.

Before [M7 · 6](../roadmap.md#m7--composite-identifiers) the mint printed the
value the record held in memory, and for a record the request had just written
that is not always the value its row holds. Under a type whose writer rewrites
what was typed — the panels fixture's `Stamp` code type hands `new/1` back as
`NEW/1` — a freshly created record was keyed `1~new=2F1`, a key
[`toIdentifier()`](#a-malformed-key-never-reaches-the-database) refuses, since
`new/1` does not print back to itself through that type. Two defects followed,
and neither depends on the key's width — a **single** assigned identifier under
such a type met both: `CreateRecord`'s redirect landed on a 404 for a record that
existed under `1~NEW=2F1`, and a relationship `Select`'s created option was named
by a key its option list did not hold, so nothing was selected and the parent's
save refused *"The value you selected is not a valid choice."*. Both remedies at
the call site were measured and lost: `refresh()` registers a **second** managed
instance and leaves the key where it was, and `detach()` plus `find()` costs a
`SELECT` and detaches the record the page just created.

A loaded record's key does not move — it already holds `convertToPHPValue()` of
its row, and the round trip is idempotent for a well-behaved type — which
[`RecordKeyTest`](../../../packages/schemas/tests/Unit/Identity/RecordKeyTest.php) pins over every converting fixture type, a raw-bytes uuid,
a cipher, a decimal and a boolean. A **new** record's key is normalised through
its type: an integer half typed `'0007'` mints `…~7`, the key its row will answer
to, through `of()` and `find()` alike. Three things did not move. `fromValues()`
still prints what it is handed — it is fed values already in their PHP form —
and `toIdentifier()` still decodes no spelling the type would rewrite
(*mints nothing through the type from raw values, and decodes no spelling the
type would rewrite*). And a blank component is still no key at all: `find()`
reads it **before** its type converts it and answers `null` (*finds no key for a
blank component before its type converts it*), and `asStored()` hands a blank
component — whitespace alone, or a `Stringable` printing that — back
unconverted, so
`of()` refuses it with its documented *"… is not a printable value."*, exactly
as before (*refuses a blank component through `of()` exactly as before the
conversion*). That pass-through is load-bearing rather than tidy: a type's
reader may assert what it reads — DBAL's `BigIntType` asserts a numeric value —
and an unguarded round trip would turn `of()`'s refusal of a blank `bigint`
half into an `AssertionError`, or a `TypeError` with assertions off.

Two faces of a new record are not pinned. A **non-numeric** string in an
integer-typed key half (which only an untyped or `int|string` property can hold)
now raises that type's own error from `of()` and `find()` — `bigint`'s
`AssertionError` under `zend.assertions=1`, a `TypeError` with assertions off,
which is why no test pins the message — or, under `integer` or `smallint`, whose
readers answer `0`, mints `…0`; before, it minted a key that never decoded. The
`bigint` face was executed through the mint, on the one fixture property that
can hold such a string; the `integer` and `smallint` face at the type level
alone. And a value its type's **writer** refuses, on a new record, now throws
that type's `ConversionException` from the mint where it was printed before. No
fixture property can hold such a value, so no test reaches it.

### The shapes the fixtures pin

Every identifier shape the codec supports by construction has a fixture and runs
through the whole battery ([`KeyShapesTest`](../../../packages/schemas/tests/Unit/Identity/KeyShapesTest.php),
[`AssociationReaderKeyShapesTest`](../../../packages/schemas/tests/Unit/State/AssociationReaderKeyShapesTest.php)) on
SQLite, MySQL and PostgreSQL. The battery covers the mint, the decode, `equals()`,
`in()`, `correlate()`, the key selection with `keyOf()`, group-by, order-by, a
reference — which costs no statement for a scalar-keyed shape and one load for a
key with an association, [the reader's route](association-reader.md#getreference-decodes-first-and-then-picks-its-route) —
the taken key on create and the changed key on edit.

- **Three scalar fields.** Chunked at 500, the same as two.
- **An association in the middle of the key.**
- **An int-backed enum half.** It prints its backing value.
- **An id declared on a mapped superclass.**
- **A JOINED hierarchy under a composite root.**
- **A single `#[Id] ManyToOne`.**
- **A smallint beside an ascii_string.**
- **Symfony's own `UuidType` and `UlidType`.** They are `BINARY(16)` on SQLite and
  MySQL and a native `uuid` on PostgreSQL, and a key prints the value's canonical
  string.

A NUL byte never reaches PostgreSQL as part of a key. The decoder refuses one on every
platform, and pdo_pgsql would truncate it silently in a bound string. The two tests
about a NUL-byte identifier are therefore restricted to SQLite and MySQL.

### A property test carries the fuzz harness's claims

`RecordKeyPropertyTest` runs a seeded, deterministic slice of the validation's fuzz
harness on every platform the suites run on. It checks four properties:

1. **Round trip.** A generated tuple's key decodes to the same printed values and
   re-encodes to itself, and a record holding the raw values mints the key its row
   binds.
2. **Only canonical spellings decode.** Every one of 31 misspellings is refused, or
   is itself the canonical key of another tuple, and `equals()` agrees with the
   decode.
3. **A single identifier keys verbatim.**
4. **Totality.** No client string makes the decode or a constraint builder throw,
   nor the lookup that follows a decode.

The cipher classes are left out by name: their type refuses its own printed form.
`SYLAMENT_PROPERTY_SEED` and `SYLAMENT_PROPERTY_ITERATIONS` widen a local run.

On PostgreSQL, property 1 leaves out a tuple whose `guid` component is not a UUID,
because a native `uuid` column cannot hold one and [the grammar](#the-grammar)
shape-checks `guid` there; a pin in the same file shows `'not-a-uuid'` decoding on
SQLite and MySQL and refused on PostgreSQL. On PostgreSQL, property 4 also skips the
lookup for an integer wider than its column, which raises
[the platform's range error](#an-integer-beyond-the-columns-width-still-raises-the-platforms-range-error)
there and names no row on the other two, as a second pin shows.

### A mutation gate holds the codec

`composer test:mutation` runs Pest's mutation testing over every file in
`packages/schemas/src/Identity`, against the schemas suite. CI runs it on every pull
request and push to `main`, and a score under the committed `--min` blocks the split.
Every mutant a test can tell from the original is killed by a named test in its
file's own test. No mutant is equivalent: the table of the ones no input can tell
apart is empty. `--min` is 98.6, not 100, because of four mutants the gate never
runs a test against:

| File:line | Mutator | Why the gate cannot run a test against it |
| --- | --- | --- |
| `IdentifierConstraint.php:20` | `DecrementInteger`, `IncrementInteger` | `MAX_PARAMETERS_PER_STATEMENT` is a class constant, and pcov records no executed line for a constant's declaration, so the plugin finds no covering test and reports it uncovered. |
| `IdentifierConstraint.php:22` | `DecrementInteger`, `IncrementInteger` | The same, for `MAX_COMPOSITE_KEYS_PER_STATEMENT`. |

Those four are not equivalent, and the suite does kill them. Run by hand, each fails
`IdentifierConstraintTest`'s *chunks a key list to what one statement carries*, and
the two on line 22 also fail *batches a composite key list by the chunk rule and a
single one whole*. So a changed statement limit still fails the schemas suite, just
not this gate.

A fault in the plugin's own swap makes a mutant escape; it never kills one. The
plugin swaps the mutated file in through a `file` stream wrapper that stays
unregistered if a wrapped file operation throws, so a test process can end up
running the original file, and a mutant it should kill then survives.

## Divergences

Limits that are permanent, and the reason each holds. There is no Filament
counterpart to deviate from here, so these are deviations from what a reader
would otherwise assume the codec guarantees — most of them Doctrine's own, all of
them recorded rather than left as a silent gap.

### An identifier association always resolves to exactly one join column

That is the load-bearing fact, and it is worth stating more carefully than "a
composite-key entity cannot be part of another entity's identifier", which is
what a reader assumes and is not quite what Doctrine enforces. Mapping validation
refuses only a **declared** identifier association carrying two or more join
columns (`ClassMetadata`'s
`cannotMapCompositePrimaryKeyEntitiesAsForeignId`). An identifier association
onto a composite-key target whose join columns are **auto-derived** builds
happily — the mapping ends up with one bogus join column — and then fails later
and elsewhere: in `SchemaTool`, and in `getSingleIdentifierFieldName()` the
moment anything asks the target for its one identifier field. So the guarantee
holds in practice through two mechanisms rather than one, and the codec adds a
third of its own: flattening an association component that resolves to more than
one value throws a `LogicException` naming the property, so the truncation can
never happen silently whatever order the checks fire in.

The fact is why `alias.assoc = :p` is legal DQL on the record's own query, why
`IDENTITY()` in a projection is unambiguous, and why the codec never needs a
nested tuple. If it ever weakened, every one of those would need revisiting.

### Doctrine's `IdentifierFlattener` is not adopted, because it misreads a chained derived identity

[Principles §2](../../principles.md) says adopt what the ecosystem ships, and the
plan for this file was to: `Utility\IdentifierFlattener` is public, final,
recursive and enum-aware, which is the whole job. It was **overturned on executed
evidence**, which is the bar §2 sets for not adopting — a named, checkable gap
rather than a preference.

Two gaps. Fed an **incomplete** identifier it answers `''` plus a PHP warning
rather than refusing, and under Pest a warning is an exception. And for a
**chained** derived identity — an identifier association whose target's own
identifier is an association — its recursion is handed
`UnitOfWork::getEntityIdentifier()`'s *already flattened* array, reads it by join
column name, finds no such key, and answers a blank. That second one only
appears **once the target is managed**, which a row read from the database always
is: key such a record before `flush()` and it works, key it after and `of()`
throws `is not a printable value` while `find()` answers `null` for a record that
is plainly saved. A suite that keys its fixtures before flushing stays green over
it, which is exactly the defect class this trail exists to catch.

`RecordKey::flatten()` therefore recurses itself, in one private method that also
subsumes the completeness count and needs no unit of work. What survives of the
original decision is its second half, which was the important one: **no consumer
ever branches on the identifier flavour.**

### `getReference()` on a derived identity is state-dependent, so the codec never mints one

`EntityManager::getReference()` works on a scalar composite identifier — a
reordered map included — but wherever **any** identifier field is an association
it succeeds only while the
record is already managed and throws a `TypeError` against a cold identity map,
because `ProxyFactory` assigns the scalar identifier component directly to the
typed association property. That is **three of the five flavours**: a derived
identity, a join entity, and a **single derived identity**, which reports
`isIdentifierComposite === false` and which a width test therefore misses.
Both outcomes were reproduced in a single process, by
`clear()`ing between them. That is precisely the defect a green suite hides: a
test that seeds and then references passes, and production fails on the first
cold request. `RecordKey` therefore offers no reference-by-key at all, and
[M7 · 4](../roadmap.md#m7--composite-identifiers), which needed one for the
reader's `getReference()`, routes those three flavours through `find()` — which
works in every state, at one statement — on the predicate
`array_any($metadata->identifier, $metadata->hasAssociation(...))`, never on
`isIdentifierComposite`. The scalar route keeps the reference's zero
statements.

### Bare `IDENTITY(path)` on a multi-column association answers only the first column

Silently — no exception, no warning, just the first join column's value. The
two-argument form `IDENTITY(path, 'field')` does not rescue it either: it
resolves **field mappings only**, so a component that is itself an association
raises `Undefined reference field mapping`. An identifier association resolves to
[exactly one join column](#an-identifier-association-always-resolves-to-exactly-one-join-column),
so the codec's projection is safe by construction — and, where that guarantee is
weakest, by the codec's own throw rather than by a silent truncation. `groupBy()`
still emits the **bare path** rather than `IDENTITY()`, both because `IDENTITY()`
is illegal in a `GROUP BY` and so that no consumer learns the wrong habit from
this file.

### Comparing two identification variables compares the first identifier column only

`WHERE inner = outer` between two aliases of the same entity is legal DQL and
compiles to a comparison of their **first** identifier columns alone — no
exception, no warning, and rows that are not the same record come back. Executed
against a scalar pair inside a correlated subselect it returned two rows where
one was right. Doctrine's parser resolves an identification variable in a
comparison to a single-column expression
(`vendor/doctrine/orm/src/Query/Parser.php:736`), and what it emits is valid
SQL, so nothing downstream can catch it.

`MEMBER OF` fails the same way from the other end:
`outer MEMBER OF alias.<collection>` compares every inverse join column of the
association against the target's **first** identifier column, and returns
nothing at all.

So `correlate()` exists, and it is the only correlation the toolkit writes. It
emits one `AND`ed equality per identifier field — `i.id = o.id` for a
single-`id` class, so today's text is unmoved — and every consumer that needs
two aliases of one class compared uses it: tables' relation search
([table.md](../tables/table.md#a-composite-identifier-root-correlates-and-groups-by-every-key-field))
and, since [M7 · 4](../roadmap.md#m7--composite-identifiers), the relationship
query's many-to-many arm
([association-reader.md](association-reader.md#the-relationship-query-is-rooted-at-the-target-and-needs-no-inverse-mapping)),
whose `MEMBER OF` alternative this closes for good rather than parking as an
optimisation.

### Doctrine's own identity hash is an unescaped `implode(' ', …)`

`UnitOfWork::getIdHashByIdentifier()` joins the identifier values with a single
space, positionally, unwrapping a `BackedEnum` and escaping nothing — so
`('a b', 'c')` and `('a', 'b c')` hash to the same string and collide inside the
ORM's identity map. Doctrine gets away with it because the hash is
process-internal, scoped per class and never parsed back. It is **not** prior art
for a key that crosses a request boundary, and it is recorded here so nobody
reads this file's escaping as paranoia the ORM shares: it is an ambient
Doctrine-imposed ambiguity, out of our hands, and one our own format does not
inherit.

### A non-`Stringable` object identifier cannot be persisted at all

An identifier field whose PHP value is an object with no `__toString()` fatals
inside `UnitOfWork::addToIdentityMap()`, before anything of ours runs. So for
such an object the codec's *not a printable value* refusal can only fire on a record that was never persistable. The same refusal fires for a blank string component, and that one the database accepts — see [the next section](#a-stored-row-with-a-blank-string-component-cannot-be-keyed). The rule it
applies is today's, in one place: `BackedEnum` → backing value, `Stringable` →
`__toString()`, `bool` → `'1'`/`'0'`, any other scalar → `(string)` — and a
result that is non-scalar, not valid UTF-8, blank, or carrying a NUL byte
refuses. The `bool` arm is the one addition to the rule as it stood: a plain
cast prints `false` as the empty string, which would mint an illegal empty
component that the key's own decode then refuses.

### A stored row with a blank string component cannot be keyed

A string component that is empty or only whitespace has no printed form: no component may be empty ([the grammar](#the-grammar)). PostgreSQL, MySQL and SQLite all store it in a `NOT NULL` key column anyway.

Such a row loads, and minting its key throws, so a table or resource page that shows the row fails whole. A page that does not show it renders. A single string identifier `''` behaves the same way, before M7 and since.

Since M7 · 7 the refusal names the row — *The identifier of [App\Entity\ProductVariant] is not a printable value. It holds code "SKU-1", suffix "".* — so an operator can find it. A create can no longer write such a row: a new record whose assigned key is still blank is refused before anything is written ([can-be-validated.md](can-be-validated.md#a-blank-assigned-key-is-refused-before-anything-is-written)).

A reserved spelling for an empty component (a lone `=`, which is malformed today) would let such legacy rows list. It is recorded, not built.

### A single string identifier containing `.` or `/` is broken today and stays broken

[The grammar](#the-grammar) exempts a one-field identifier from escaping, so
existing URLs, option values and tests do not move — and that deliberately
**preserves** a pre-existing hole rather than closing it. A `Book` keyed on
`code` with the value `sy/0.1` already breaks the `{record}` route (the `/`) and
already re-addresses a repeater item's state path (the `.`), and it still does.
This is stated so the first user with a slug-keyed entity does not read the
contract as a guarantee it does not make: **only a composite key carries the
alphabet guarantee.** Closing it for single identifiers would move every URL and
every pinned test in the repo, which is a milestone of its own.

### An integer beyond the column's width still raises the platform's range error

Not one beyond PHP's, though, and the difference is the whole of the residual. A
key of `99999999999999999999` **names no record**: `IntegerType`'s `(int)` cast
saturates it to `PHP_INT_MAX`, that value prints back as `9223372036854775807`,
and the print-back compare refuses the mismatch — without it the key would
resolve the `PHP_INT_MAX` row, which is the `5abc matches record 5` hole in a
different costume. `BigIntType` does not saturate, so it gets
[a shape check](#parameter-naming-and-what-an-empty-or-malformed-key-means)
instead.

What remains is an integer that is valid for PHP and too wide for the
**column** — `5000000000` against a `smallint`, say. That decodes, binds, and
raises the platform's own out-of-range error exactly as it does today for a
single `id`. Checking it would mean reading the column's declared width and
reproducing each platform's limits, which is a per-platform table that would
drift; it was considered and rejected. The residual is recorded rather than
hidden.

### A type that cannot read its own printed form mints keys that never decode

Since [M7 · 5b](../roadmap.md#m7--composite-identifiers) this is the type that
refuses its printed form on read **and** on write — the doubly-strict shape. A
type that refuses it on read only
[decodes through its own writer](#a-type-that-refuses-its-printed-form-on-read-only-decodes-it-through-its-own-writer),
the section below; one whose writer throws for that same string has no route by
which a string names its record. The fixtures' `CipherType` is one: it stores
`c:<value>`, reads nothing without that prefix, and writes nothing but a
`Cipher`. `RecordKey::of()` mints the PHP value's printed form,
`IdentifierFields::toPhpValues()` hands that string to the reader, which raises a
`ConversionException`, then to the writer, which raises another, and the decode
answers `null`. So **every** key such a class mints is refused: a keyed read
narrows to nothing and a reference is refused by name, on a record that exists
and whose key nothing forged.

What that costs depends on which side of a relation the record stands. As an
**owner** it is loud: `getReference()` refuses by name at zero statements, and a
composite owner with such a half throws by name at `getOwnerScope()` before
anything runs, on both arms of both relationship queries
([the message](association-reader.md#an-owner-of-any-width-is-scoped-through-its-own-collection-rather-than-through-a-mapped-by-path)).
A **single** owner of the type, and a single derived identity onto one, are not
refused at all — their bind reads the identifier value, never the key
([the bind](association-reader.md#a-single-identifier-owner-binds-its-identifier-value-typed-by-the-fields-own-dbal-type)).
As the **selected records** of a by-key bulk write it is silent: the plucked
keys decode to nothing, so a by-key dissociate narrows to `1 = 0` and reports a
zero count with every row left owned — executed on SQLite and PostgreSQL 18.4,
the same before M7 · 5b as after — and the by-key delete narrows through the
same `in()` to the same `1 = 0`. The owner side throws and the child side does
not. Recorded rather than fixed, with
[a ledger row](../roadmap.md#m7--composite-identifiers) of its own: the type
breaks the round trip every key depends on, and nothing on this side of the
codec can mend that. The decode and the reference are pinned over a single
(`Strongbox`), a composite (`Vault`, one half of the type) and a single derived
identity onto one (`StrongboxSeal`) in
[`IdentifierFieldsTest`](../../../packages/schemas/tests/Unit/Identity/IdentifierFieldsTest.php)
and
[`AssociationReaderCompositeKeyTest`](../../../packages/schemas/tests/Unit/State/AssociationReaderCompositeKeyTest.php),
`Vault`'s owner throw in
[`AssociationReaderOwnerScopeTest`](../../../packages/schemas/tests/Unit/State/AssociationReaderOwnerScopeTest.php),
and `StrongboxSeal` scoping by value in
[`AssociationReaderOwnerBindTest`](../../../packages/schemas/tests/Unit/State/AssociationReaderOwnerBindTest.php).
The child side is not pinned: it was executed as a probe.

### A type that refuses its printed form on read only decodes it through its own writer

A DBAL type whose reader accepts **only the database form** while its writer
takes the printed one — the `uuid_binary` shape, where the column holds sixteen
raw bytes: `ramsey/uuid-doctrine`'s, not `symfony/uid`'s, whose types
[round-trip their printed form](#the-shapes-the-fixtures-pin) — minted keys the strict decode refused until
[M7 · 5b](../roadmap.md#m7--composite-identifiers). `toPhpValues()` now answers
the reader's `ConversionException` — and, since
[M7 · 11](../roadmap.md#m7--composite-identifiers), a value the reader answers
that does not print back to the component — by running the component through
the type's own writer and reading back the database form that makes —
`convertToPHPValue(convertToDatabaseValue($component))` — and hands the result to
the unchanged print-back rule. So such a record's key decodes, and everything
downstream of the decode follows it: `equals()`, `notEquals()`, `in()` and
`notIn()` bind the sixteen bytes under `ParameterType::BINARY` — the batch under
`ArrayParameterType::BINARY` — and match rows for the first time, since an
untyped string bind never matched such a column either; `getReference()` returns
a reference where M7 · 4's strict decode refused one; and a composite owner with
such a half scopes both relationship queries where it threw. Every consumer is
on that one call — panels' `{record}` binding, tables' row resolution,
selection, reorder, aggregates and paging, forms' relationship `Select` save and
`unique(ignoreRecord:)`, and actions' by-key delete, detach, attach and
associate.

The print-back rule is what keeps it one key per record. The writer takes
spellings the printed form is not — uppercase, thirty-two hex digits, braces, a
`urn:uuid:` prefix, base58 and base32 all name one uuid to
`Uuid::fromString()` — and each reads back to a value that prints lowercase and
dashed, so each is refused at zero statements, as are garbage and the sixteen raw
bytes themselves. Neither step catches more than `ConversionException`: a custom
type whose writer throws anything else for a forged component breaks DBAL's
contract, and that exception now surfaces where the strict decode answered
`null` — a forged `{record}` against such a type is a 500 where it was a 404.
Catching `Throwable` would turn a genuine bug inside a type into *this key names
no record*, which is a catch-and-ignore; `ramsey/uuid-doctrine` wraps every
failure into a `ConversionException` on both sides, so it loses nothing. Since
M7 · 11 the writer is reached for **every** non-canonical spelling of a type
whose reader answers without refusing — `05` for an `integer` half, `FF` for a
hex one — so a writer that raises anything but a `ConversionException`, or a PHP
deprecation (`hexdec()` raises one for `+ff`), surfaces there, where the decode
answered `null` before. DBAL's own boolean writer is the one built-in that does,
on PostgreSQL, with an `UnexpectedValueException` for an unrecognised literal,
which is why [the decode](#parameter-naming-and-what-an-empty-or-malformed-key-means)
shape-checks a boolean component as `0` or `1` before any conversion; every other
built-in either passes the string through its writer or refuses it with a
`ConversionException`.

The fallback touches the decode alone: `toDatabaseValues()`, the split, the
shape checks (bar M7 · 11's boolean arm, above) and `printable()` are as they were, and a type that reads its
printed form back as itself never reaches it, so every existing dataset keeps its answer —
`SkuType`'s `not-a-sku` included, its writer taking a `Sku` and nothing else.
One shape widens further than the rest: where a type's **writer** takes any
string at all — DBAL's four JSON types, `json`, `jsonb`, `json_object` and
`jsonb_object`, whose writer is `json_encode()` — every well-formed component
now decodes, each to the string it spells, so a **forged** key of such a field
costs one statement that finds nothing where it used to cost none. Nothing is
mis-keyed by that: the print-back rule holds there too, and one key still names
one record. The fallback costs three or four conversions instead of one for a
component the reader refuses or reads as another value, unmeasured, and only
such a component pays — since M7 · 11 that includes a forged non-canonical
spelling of any type, `05` for an `integer` half among them. A reference to a class whose
every identifier field is scalar is still built at zero statements; one behind an
association — a single derived identity onto such a target — is loaded, at one.

Pinned over [`BinaryUuidType`](../../../packages/schemas/tests/Fixtures/Doctrine/BinaryUuidType.php),
a mimic of ramsey's `UuidBinaryType` over `symfony/uid` — ramsey deliberately
not a dependency — and three owners of it, `Crate` (one such identifier),
`Pallet` (such a half beside a string) and `CrateSeal` (a single derived
identity onto `Crate`), in
[`IdentifierFieldsTest`](../../../packages/schemas/tests/Unit/Identity/IdentifierFieldsTest.php),
[`IdentifierConstraintTest`](../../../packages/schemas/tests/Unit/Identity/IdentifierConstraintTest.php),
[`AssociationReaderCompositeKeyTest`](../../../packages/schemas/tests/Unit/State/AssociationReaderCompositeKeyTest.php)
and
[`AssociationReaderOwnerScopeTest`](../../../packages/schemas/tests/Unit/State/AssociationReaderOwnerScopeTest.php).
It is the first fixture type in the repo to reach the per-field `BINARY` bind and
its array twin, and it reads a **stream** where PostgreSQL's hydrator hands
`bytea` back as one — a mimic without that arm passes every SQLite suite and
breaks on PostgreSQL alone.

The carve-out was written for an **object**-returning type, the `uuid_binary`
shape, and a scalar one meets it too since M7 · 11. The fixtures' `HexCodeType`
holds an integer column as its lower-case hex spelling and reads whatever it is
handed as a decimal number, so its reader takes the printed `ff` without
refusing and answers `0`; the M7 · 5b fallback, reached only on a
`ConversionException`, never ran for it, and every key such a class minted — the
record side's `ff~north`, through the writer and back — was refused at every
decode site. The retry on a failed print-back reaches the writer (`ff` → 255)
and reads 255 back as `ff`, while `FF` (the writer refuses), `0ff` (the writer
takes it, and it prints back without the zero) and `+ff` stay refused. The retry
lands only with the row side's
[second reader](#a-hydrated-row-is-keyed-without-reading-its-fields-a-second-time):
on its own it would have turned such a type's silent `0` options into keys that
decode, to the wrong record. Pinned over `HexSwatch` and `HexTile` in
[`IdentifierFieldsTest`](../../../packages/schemas/tests/Unit/Identity/IdentifierFieldsTest.php),
beside the boolean shape check over `Flagged` on a PostgreSQL and a SQLite
platform.

### Identifier order is declaration order, so reordering two `#[Id]` properties invalidates every minted key

`ClassMetadata::$identifier` is filled in the order the attributes are declared —
not alphabetically, not by column order — and the codec joins and splits in that
order. Swapping two `#[Id]` properties in an entity therefore changes what every
already-minted key means: a bookmarked URL, a stored selection, a key in flight
in an open tab. There is no version marker in the format to detect it with, and
adding one would cost every key two characters forever. Treat the order of
`#[Id]` declarations as part of the schema, like a column name; it is pinned by a
test over the fixtures so the fact cannot quietly stop being true.

### A type extending a shape-checked family is checked as that family

The shape check reaches `BigIntType`, `DecimalType`, `GuidType` and `BooleanType` by class, so a
type registered under a name of its own is checked as the family it extends —
which is the point, since such a type inherits the conversion that hands a
malformed string back untouched — or, for `BooleanType`, whose PostgreSQL writer
refuses an unrecognised literal with no `ConversionException`, the reason
[M7 · 11](../roadmap.md#m7--composite-identifiers) added that arm once the
decode began offering a component to the writer. The cost is a subclass whose
PHP value prints **outside** its parent's shape — a `BigIntType` whose reader
answers a `T-123` value object, or a `BooleanType` whose value is not a bool —
whose every key the decode now refuses where it used to accept it. No such type is known, and the way out is not to extend the family whose
shape it no longer prints. Pinned by `Voucher` in `IdentifierFieldsTest`
and `RecordKeyTest` (M7 · 9).
