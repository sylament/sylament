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
M7 · 2–5 add consumers; none of them reshapes what is below.

**Status:** done — see the [status vocabulary](../README.md#statuses).

## Surface

Every operation these three classes expose. _Blocked by_ names what has to land
first. _Detail_ carries **contract** → the frozen interface in
[Contract](#contract), **semantics** → the write-up in [Semantics](#semantics),
and/or **diverges** → the permanent note in [Divergences](#divergences).

| Feature | Status | Blocked by | Detail |
| --- | --- | --- | --- |
| [`RecordKey::of()`](../../../packages/schemas/src/Identity/RecordKey.php) — a record to its key, throwing on an incomplete or unprintable identifier | done | — | [contract](#the-api) · [semantics](#find-answers-identity-not-persistence) |
| [`RecordKey::find()`](../../../packages/schemas/src/Identity/RecordKey.php) — the non-throwing twin, `null` while the identifier is incomplete | done | — | [semantics](#find-answers-identity-not-persistence) |
| [`RecordKey::fromValues()`](../../../packages/schemas/src/Identity/RecordKey.php) — raw identifier values in mapping order to the key a record mints | done | — | [semantics](#the-row-side-and-the-record-side-mint-one-string-and-only-the-codec-may) |
| [`RecordKey::toIdentifier()`](../../../packages/schemas/src/Identity/RecordKey.php) — a client string to the field ⇒ PHP value map `EntityManager::find()` takes, `null` when malformed | done | — | [contract](#the-grammar) · [semantics](#a-malformed-key-never-reaches-the-database) |
| [`IdentifierConstraint::equals()`](../../../packages/schemas/src/Identity/IdentifierConstraint.php) / `notEquals()` | done | — | [contract](#the-operations-and-their-dql) |
| [`IdentifierConstraint::in()`](../../../packages/schemas/src/Identity/IdentifierConstraint.php) / `notIn()` | done | — | [contract](#the-operations-and-their-dql) · [semantics](#the-batch-is-a-balanced-or-tree-because-sqlite-counts-expression-depth-not-terms) |
| [`IdentifierConstraint::correlate()`](../../../packages/schemas/src/Identity/IdentifierConstraint.php) — `alias.f = outer.f AND …` as **text**, because `RelationConstraint` splices a subselect string — ships unconsumed, for M7 · 2 | done | — | [contract](#the-operations-and-their-dql) |
| [`IdentifierConstraint::select()`](../../../packages/schemas/src/Identity/IdentifierConstraint.php) + [`KeySelection::keyOf()`](../../../packages/schemas/src/Identity/KeySelection.php) | done | — | [contract](#the-operations-and-their-dql) · [semantics](#the-row-side-and-the-record-side-mint-one-string-and-only-the-codec-may) |
| [`IdentifierConstraint::groupBy()`](../../../packages/schemas/src/Identity/IdentifierConstraint.php) — the bare path per field, never `IDENTITY()` — ships unconsumed, for M7 · 2 | done | — | [diverges](#bare-identitypath-on-a-multi-column-association-answers-only-the-first-column) |
| [`IdentifierConstraint::orderBy()`](../../../packages/schemas/src/Identity/IdentifierConstraint.php) — the identifier tiebreak, per field, deduped against the existing order | done | — | [contract](#the-operations-and-their-dql) |
| [`IdentifierConstraint::chunk()`](../../../packages/schemas/src/Identity/IdentifierConstraint.php) | done | — | [contract](#the-chunk-rule) |
| Owner scoping — `scopeToOwner()` / the owner tuple | blocked | [M7 · 5](../roadmap.md#m7--composite-identifiers) | [contract](#owner-scoping-is-reserved-not-missing) |
| [`IdentifierFields`](../../../packages/schemas/src/Identity/IdentifierFields.php) — the per-class descriptor both public classes share (`@internal`) | done | — | [diverges](#identifier-order-is-declaration-order-so-reordering-two-id-properties-invalidates-every-minted-key) |

## Contract

What a consumer writes, and what it gets back. This section is the interface:
M7 · 2–5 build against it unchanged, and an epic that finds it short records the
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
consumer may build on. It is one-directional: a record always mints exactly one
key, while the guarantee that no *other* string resolves that record belongs to
the database and holds on a binary or case-sensitive collation — PostgreSQL's
default and SQLite's `BINARY`. Three residuals are recorded rather than closed.

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
the point of gating rather than dropping the check.

The third belongs to the **collation**, not to any type. A key is compared with
`=`, so a platform whose collation ignores case or accents resolves a record
from any spelling that collation equates — MySQL 8's default
`utf8mb4_0900_ai_ci`, MariaDB's own default, which is a different collation and
also case-insensitive, and SQL Server's default CI collation. It reaches every
text-compared component alike: `string`, `ascii_string`, `text`, `guid`, and a
string-backed value object or enum that preserves the case it was given. A
`decimal` is not among them — the column compares numerically, and its own
residual is the scale aliasing above. `uuid`, `integer` and a value object that
normalises in its constructor are immune, because the print-back rule leaves
them one accepted spelling. It is self-aliasing only — the identifier's unique index sits under
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
| One URL path segment | `RouteCompiler` compiles `{record}` to `[^/]+`; `UrlGenerator` maps `'%252F' => '%2F'` out of `rawurlencode`, and the matcher `rawurldecode`s the whole path **before** matching | `/`, and the literal sequence `%2F` — which round-trips into a slash and 404s with no error | [`RouteCompiler.php:165-171`](../../../vendor/symfony/routing/RouteCompiler.php), [`Generator/UrlGenerator.php:55-74`](../../../vendor/symfony/routing/Generator/UrlGenerator.php) and `:201`, [`Matcher/UrlMatcher.php:73`](../../../vendor/symfony/routing/Matcher/UrlMatcher.php) |
| A dotted state path — `Repeater` keys a relationship item `record-<key>` | `PropertyAccessor` splits every path on `.`, at eight sites | `.`, which does not error — it **re-addresses the state tree one level deeper** | [`PropertyAccessor.php`](../../../packages/schemas/src/State/PropertyAccessor.php) `:30`, `:55`, `:86`, `:120`, `:263`, `:284`, `:303`, `:317` |
| The live `data-model` directive the state path is embedded in | UX's directive parser is a character state machine reserving `(`, `)`, `,`, `\|` and space; `normalizeModelName` rewrites `[` and `]` into `.` | `( ) , \| [ ]` and space | [`HasStateBindingModifiers.php:54-72`](../../../packages/schemas/src/Components/Concerns/HasStateBindingModifiers.php); `vendor/symfony/ux-live-component/assets/dist/live_controller.js:204-240`, `:264-266` |
| The state-path sanitiser, in **both** `HasState` traits | a `preg_replace` that **strips** rather than refuses, so two records could share one path | `< > " '` `` ` `` and C0/DEL | [`Concerns/HasState.php:33-41`](../../../packages/schemas/src/Concerns/HasState.php), [`Components/Concerns/HasState.php:67-75`](../../../packages/schemas/src/Components/Concerns/HasState.php) |
| A DOM id, a `<label for>` and a CSS class token | the row id, the cell id and `data-sortable-item` interpolate the **raw** key as idiomorph's morph identity; panels stamps `sy-pa-resource-record-<key>`; `LiveComponent::getChildId()` sanitises lossily | whitespace (an invalid id, and a class token splits in two); anything outside the class `getChildId()` admits | [`Table/index.html.twig:794`](../../../packages/tables/templates/partials/Table/index.html.twig), `:795`, `:864`; [`EditRecord.php:151`](../../../packages/panels/src/Resources/Pages/EditRecord.php), [`ManageRelatedRecords.php:180`](../../../packages/panels/src/Resources/Pages/ManageRelatedRecords.php); [`LiveComponent.php:93`](../../../packages/schemas/src/Components/LiveComponent.php) |
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
`Query\` and `State\`, per CLAUDE.md's rule that the engine a trait delegates to
is filed by capability. Three public classes; `IdentifierFields` is `@internal`
and is the per-class descriptor the other two share — field names in mapping
order, whether each is a to-one association, and the resolved DBAL type name each
binds as.

```php
final class RecordKey
{
    public const string SEPARATOR = '~';

    /**
     * @param class-string|null $class the mapped class to key by, when the caller declares one
     *
     * @throws LogicException when the identifier is incomplete, or a component is not printable
     */
    public static function of(EntityManagerInterface $entityManager, object $record, ?string $class = null): string;

    /**
     * Null while a component is missing or still blank.
     *
     * @param class-string|null $class the mapped class to key by, when the caller declares one
     *
     * @throws LogicException when a component is set but not printable
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
}

final class IdentifierConstraint
{
    /** @param class-string $class */
    public static function equals(QueryBuilder $query, string $alias, string $class, string $key, string $parameter): ?string;

    /** @param class-string $class */
    public static function notEquals(QueryBuilder $query, string $alias, string $class, string $key, string $parameter): ?string;

    /**
     * @param class-string $class
     * @param list<string> $keys
     */
    public static function in(QueryBuilder $query, string $alias, string $class, array $keys, string $parameter): string;

    /**
     * @param class-string $class
     * @param list<string> $keys
     */
    public static function notIn(QueryBuilder $query, string $alias, string $class, array $keys, string $parameter): ?string;

    /** `alias.f1 = outer.f1 AND …` — text, because RelationConstraint splices a subselect string */
    public static function correlate(QueryBuilder $query, string $alias, string $class, string $outerAlias): string;

    public static function select(QueryBuilder $query, string $alias, string $class, string $resultAlias = 'sy_key'): KeySelection;

    /** @return list<string> `alias.field` per identifier field */
    public static function groupBy(QueryBuilder $query, string $alias, string $class): array;

    public static function orderBy(QueryBuilder $query, string $alias, string $class, string $direction): void;

    /**
     * @param class-string $class
     * @param list<string> $keys
     *
     * @return list<list<string>>
     */
    public static function chunk(EntityManagerInterface $entityManager, string $class, array $keys): array;
}

final readonly class KeySelection
{
    /** @var list<string> e.g. ['IDENTITY(r.venue) AS sy_key_0', 'r.season AS sy_key_1'] */
    public array $expressions;

    /** @param array<array-key, mixed> $row one scalar or array result row */
    public function keyOf(array $row): string;
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
`@internal` for the two public classes; M7 · 4, which deletes
`AssociationReader::getIdentifierFieldName()` with its last caller, replaces it
with the metadata read rather than with a member here.

Read the shapes off those signatures in one line each. Every **predicate** method
binds its values on the `QueryBuilder` it is handed and returns DQL **text** for
the caller to place — in a `WHERE`, an `andWhere`, a `DELETE`, an `UPDATE` or a
spliced subselect — so the builder never decides where the predicate goes.
`select()`, `correlate()` and `groupBy()` bind nothing at all. `equals()`,
`notEquals()` and `notIn()` answer `null` rather than throwing, and `null` means
*there is no predicate to add*, never *something went wrong*.

**The decode is one pipeline, and every operation shares it.** A key is split
into components (a single-field identifier is one component, verbatim); each
component is **shape-checked** if and only if its field is `bigint`, `decimal`
or `guid` — the three types whose own conversion hands a malformed string
straight back, so nothing downstream would refuse it before the database does,
the `guid` arm running only where the platform declares a guid type of its own;
each is then run
through its field's `Type::convertToPHPValue()`, and a `ConversionException` out
of that means *the key names no record*, not an error; and finally the converted
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
type breaking DBAL's contract, and propagates. **A field name reaches DQL from
`ClassMetadata` only**, never from the key.

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
| `orderBy(…, 'asc')` on an unordered query | `ORDER BY song.id asc` | `ORDER BY shortcut.host asc, shortcut.path asc` | `ORDER BY residency.venue asc, residency.season asc` |
| `orderBy(…, 'desc')` after `orderBy('residency.season', 'desc')` | `ORDER BY song.id desc` untouched when already ordered by it | appends only the fields not already named | `ORDER BY residency.season desc, residency.venue desc` |
| `chunk(…)` — keys per statement | 2 000 | 500 | 500 |

Four things that table encodes.

**`IDENTITY()` appears in a `SELECT` list and nowhere else.** The plain path
`alias.field` is what goes into `WHERE`, correlation, `GROUP BY`, `ORDER BY` and
a DQL `DELETE`/`UPDATE` — legal for an identifier association because such an
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
rewrite.

**The DQL parses down to ordinary SQL.** `equals()` on a scalar pair executes as
`… WHERE s0_.host = ? AND s0_.path = ?`, on a derived identity as
`… WHERE r0_.venue_id = ? AND r0_.season = ?`, and a DQL `DELETE` over two
composite keys as
`DELETE FROM Residency WHERE ((venue_id = ? AND season = ?) OR (venue_id = ? AND season = ?))`
— all three executed.

**A `KeySelection` is read back through `keyOf($row)`, never re-derived.** It
takes one result row — a **scalar** one or an **array** one, both are safe —
reads exactly the aliases it projected, throwing a `LogicException` naming the
class and the missing column if the row came from a different selection, and
converts **every** value the row did not already hand back as an object through
that field's own DBAL type before handing the lot to its held
`IdentifierFields::toKey()`. Converting all of them rather than only the
association ones is the whole point: no hydrator converts every key column, and
the two disagree about which ones they do convert. That is the *same* function the record side uses, by construction
rather than by agreement; see
[the semantics note](#the-row-side-and-the-record-side-mint-one-string-and-only-the-codec-may).

### Invariants

Six, and a consumer may rely on all of them.

1. A key is **one string**. A composite key matches `^[A-Za-z0-9_=~-]+$`,
   contains `~`, and is never numeric.
2. `RecordKey::of()` is **injective and stable across requests**, and
   `fromValues()` over a row selected with `select()` equals `of()` of that
   record. The second half is not decoration: `AggregateQuery::readValues()`
   already fills a store under row keys that cells look up by record key, and a
   disagreement there fills the store under keys no cell ever asks for — a cell
   reading its default, with no error anywhere.
3. **Client input never throws.** `toIdentifier()`, `equals()`, `notEquals()`,
   `in()` and `notIn()` are total over arbitrary strings — and a map
   `toIdentifier()` answers is always one `EntityManager::find()` can take,
   because the decode has already run each component through its field's DBAL
   type, the same step the predicates bind through. The two answer `null`
   together, over every string: a key that narrows no query decodes to no
   identifier, and a key that decodes never explodes inside `find()`.
4. A decoded value is **only ever a bound parameter**; a field name **only ever**
   comes from `ClassMetadata`.
5. A single-identifier class emits **today's DQL text**.
6. **The two write-side consumers chunk** — the by-key delete and the reorder's
   re-resolution. Every **read** that narrows by a key list is one statement over
   the whole list, and carries the parse curve below as a recorded limit.

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
PostgreSQL 18 takes either shape up to its 65 535 bound-parameter ceiling:
32 767 two-field keys in ~110 ms. What remains is Doctrine's own cost — DQL parse
time is superlinear in key count (500 ≈ 55 ms, 1 000 ≈ 160 ms, 5 000 ≈ 3.4 s on a
first parse), which is what the 500-key composite chunk is sized against.

**Two sites chunk, and both are writes.** The by-key delete issues one DQL
`DELETE` per chunk, several chunks inside one transaction; the reorder's
re-resolution issues one `SELECT` per chunk, each narrowing a **copy** of the
table's query — a copy because a second predicate ANDed onto the first would
answer nothing, and each chunk keeps the one parameter base, since a base
carrying a chunk index of its own is what `assertParameterBase()` refuses.
Reordering is unpaginated by default, so its order list is the whole table,
which is what made it the second site to earn this.

**Every other site that narrows by a key list runs one statement over the whole
list**, whether this package executes that statement or hands the builder out —
the axis is write versus read, never executing versus returning, because
`getSelectedTableRecordsQuery()` is both. Those sites, as recorded limits riding
the parse curve above: that method's two arms — `in()` over the selected bag and
`notIn()` over the deselected one, which cannot be chunked at all, since a
`NOT IN` per chunk would union near-complete result sets — the selectable-key
narrowing in `constrainQueryToSelectableTableRecords()`, the hydrating selection
read, the key pluck, and the page summary, which under `recordsPerPage: 'all'`
narrows by every key on the table. Two of them can land on **one** builder:
under `fetchSelectedRecords(false)` with `checkIfRecordIsSelectableUsing()` the
selected-key predicate and the selectable-key predicate decorate the same query,
so the executed statement binds both lists at once.

Four alternatives were considered and rejected: factoring a batch's leading field
(`a = ? AND b IN (…)`), chunking the **hydrating** selection arm (it would
reorder results), range-checking integers by column width, and a `CONCAT`-keyed
`IN` (non-sargable, and it breaks typed binds).

### Parameter naming, and what an empty or malformed key means

Every predicate takes a **caller-supplied parameter base** and derives its names
from it: `:base` for a single field, `:base_<j>` for the fields of one composite
key, `:base_<i>_<j>` for key *i* of a batch. Two things force this. A counter
minted off `count($query->getParameters())` — the trick
[`SearchConstraint`](search-constraint.md) and `Group::mintParameter()` use —
does not move until each `setParameter()` lands, so an F×K application would
collide with itself; and the selection lane puts **two** predicates on one
builder in one request (`getSelectedTableRecordsQuery()` and the selectable
narrowing), where `setParameter()` overwrites in silence. The bases in use today
are `sylament_record_key`, `sylament_selected_record_keys`,
`sylament_selectable_record_keys`, `sylament_reordered_record_keys` and
`sylament_summary_keys` — all of them the names the single-identifier code
already used, so the DQL text is stable for the query cache.

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
bound and over-counts the same way. None of the five bases above is applied twice
on one builder.

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
   three of them. A `bigint` component must match `^(?:0|-?[1-9][0-9]*)$`, a
   `decimal` component `^-?(?:0|[1-9][0-9]*)(?:\.[0-9]+)?$`, and a `guid`
   component must be UUID-shaped, because all three types hand a malformed string
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
   records, and the reason `HasSchemas.php:229` and
   `Uploads\TemporaryUploads.php:31` anchor theirs the same way.
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

### Owner scoping is reserved, not missing

`scopeToOwner()` and the owner tuple belong to
[M7 · 5](../roadmap.md#m7--composite-identifiers) and are named here so their
absence reads as a decision. They are the one operation that will **mutate** the
builder rather than only bind on it: a derived-identity owner needs a join —
inner for the related query, and **left plus `IS NULL`** for the candidate query
— which is why they were left out of the shape above rather than stubbed into it.

Two more facts M7 · 4 and M7 · 6 will need, recorded now so no later plan is
written against a passing test:

- **A reference by key is `getReference()` only for a class with no foreign
  identifier.** On a **derived identity** `getReference()` is *state-dependent*:
  it succeeds while the record is already managed and **fatals on a cold identity
  map**, because `ProxyFactory` assigns the scalar component straight to the
  typed association property. Both outcomes were reproduced in one process.
  `find()` works in every state. The codec consumes no reference by key at all.
- **The codec owns the `mixed` → string narrowing for an entity's identifier.**
  Four hand-written copies of it existed — `HasTable`'s query arm,
  `pluckTableRecordKeys()`'s inline closure, `AssociationReader::findRecordKey()`
  and `Resource::getRecordRouteKey()`. M7 · 1 replaced the first two; M7 · 3
  replaces `Resource`'s and M7 · 4 the reader's. `HasTable::toRecordKey()` itself
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

A component that is `null` **or still blank** counts as not assigned yet, and
`find()` answers `null` for it: an assigned string key sits at `''` until a form
fills it, and a create form's `unique(ignoreRecord:)` must be able to ask without
a throw. `of()` draws the line differently on purpose and keeps both refusals a
table has always raised — `has none set` for a missing component, `is not a
printable value` for a blank or unprintable one — because a row index cannot
proceed without a key.

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

`KeySelection::keyOf()` and `RecordKey::of()` must produce the same string for
the same row, or a store filled under row keys is read by cells asking for record
keys and every cell quietly renders its default. They are the same function by
construction: `keyOf()` normalises its raw values and hands them to
`IdentifierFields::toKey()`, which is the one print step `RecordKey::fromValues()`
calls too — so `of()` reaches it after flattening, and neither side owns a copy.

The normalisation is where a hand-rolled copy would go wrong, and the rule is
**one rule, not a table of special cases**: every value the row did not already
hand back as an object is run through its field's own DBAL type before it is
printed. It has to be all of them, because **no hydrator converts every key
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
one, and converting only the association arm — or trusting the hydrator — mints a
different string on the row side than the record side mints. A value the
hydrator already built into an **object** is the one thing left alone, and it
needs nothing: the printable rule unwraps a `BackedEnum` to its backing value and
a `Stringable` to its string, which is exactly what the record side does with the
same object. The rule that falls out: a row-to-key decode is **called**, never
re-derived at a call site.

### A malformed key never reaches the database

A key is client input, and the guard is a strict decode *before anything is
bound* — not the typed bind, which is only the second half. The typed bind alone
does not close the hole: on PostgreSQL 18 a `'5abc'` bound `PARAM_INT` raises
`22P02` under native prepares and, under emulated prepares, **matches record 5**.
And a `DriverException` is not an `HttpExceptionInterface`, so
`PageController::convertTwigError()` does not convert it — the render ends in a
**500**.

That 500 is still live wherever a client-supplied key is bound outside the table
lane, and naming those here is the point.
`Resource::resolveRecordRouteBinding()` binds the raw `{record}` segment untyped
and never reaches this decode, so `/admin/articles/abc/edit` remains a 500 on
PostgreSQL and `/admin/articles/05/edit` still resolves article 5; converting the
panel's route binding is **[M7 · 3](../roadmap.md#m7--composite-identifiers)'s**.
The relation layer binds untyped too, and is
**[M7 · 4](../roadmap.md#m7--composite-identifiers)'s**: a relationship
`Select`'s selected-record and option-label lookups and `AttachAction`,
`AssociateAction` and `DissociateBulkAction`'s key `IN` all take client state and
carry the same 500, while `Select::relationship(ignoreRecord:)` and
`unique(ignoreRecord:)` bind a server-derived identifier, so they convert for
composite correctness rather than for the hole. Both sets are recorded in that
milestone's site ledger; M7 · 1 closes the hole on the **table** lane only — row
resolution, editable cells, selection, reorder and the by-key delete.

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
reordered map included — but on a **derived identity** it succeeds only while the
record is already managed and throws a `TypeError` against a cold identity map,
because `ProxyFactory` assigns the scalar identifier component directly to the
typed association property. Both outcomes were reproduced in a single process, by
`clear()`ing between them. That is precisely the defect a green suite hides: a
test that seeds and then references passes, and production fails on the first
cold request. `RecordKey` therefore offers no reference-by-key at all, and
[M7 · 4](../roadmap.md#m7--composite-identifiers), which needs one for the
reader's `getReference()`, must use `find()` — which works in every state.

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
inside `UnitOfWork::addToIdentityMap()`, before anything of ours runs. So the
codec's "not a printable value" refusal — `LogicException`, the same message a
blank identifier gets — can only ever fire on a record that was never
persistable, and the codec is not the thing standing in the way. The rule it
applies is today's, in one place: `BackedEnum` → backing value, `Stringable` →
`__toString()`, `bool` → `'1'`/`'0'`, any other scalar → `(string)` — and a
result that is non-scalar, not valid UTF-8, blank, or carrying a NUL byte
refuses. The `bool` arm is the one addition to the rule as it stood: a plain
cast prints `false` as the empty string, which would mint an illegal empty
component that the key's own decode then refuses.

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

### Identifier order is declaration order, so reordering two `#[Id]` properties invalidates every minted key

`ClassMetadata::$identifier` is filled in the order the attributes are declared —
not alphabetically, not by column order — and the codec joins and splits in that
order. Swapping two `#[Id]` properties in an entity therefore changes what every
already-minted key means: a bookmarked URL, a stored selection, a key in flight
in an open tab. There is no version marker in the format to detect it with, and
adding one would cost every key two characters forever. Treat the order of
`#[Id]` declarations as part of the schema, like a column name; it is pinned by a
test over the fixtures so the fact cannot quietly stop being true.
