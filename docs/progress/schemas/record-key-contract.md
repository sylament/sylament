---
description: 'The key contract of `RecordKey`, `IdentifierConstraint` and `KeySelection`: the grammar, where a key travels, the API, the operations and the chunk rule.'
ports: []
owns: []
---
# RecordKey contract (schemas)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`RecordKey`](../../../packages/schemas/src/Identity/RecordKey.php), [`IdentifierConstraint`](../../../packages/schemas/src/Identity/IdentifierConstraint.php), [`KeySelection`](../../../packages/schemas/src/Identity/KeySelection.php) — the key contract | — | done | — | [contract](#contract) |

## Contract

What a consumer writes, and what it gets back.
This section is the interface: a consumer builds against it unchanged, and a change that finds it short records the addition **here** rather than reshaping what is there.

### The grammar

```
key        = component *( "~" component )      ; one per identifier field, in mapping order
component  = 1*( safe / escape )               ; never empty
safe       = ALPHA / DIGIT / "-" / "_"
escape     = "=" 2UPPERHEXDIG                  ; one per byte of the component's UTF-8 form
```

The alphabet of any composite key is `[A-Za-z0-9_-]`, `~` and `=` — and nothing else, ever.
`~` is `RecordKey::SEPARATOR`; `=` is the escape introducer, written once per **byte**, uppercase, so a multi-byte character becomes several escapes.

Three rules complete it.

**A class with one identifier field encodes to the flattened value verbatim** — no escaping, no splitting, no alphabet check — so a single-identifier key is the identifier value itself in every URL, option value and test.
That includes a **single derived identity** (`#[Id]` on a to-one association, alone), which is flattened to the target's own key: a `PostSeal` keyed on its `Post` encodes to `1`.
Two or more fields take the grammar above.
Decode is therefore **class-aware**, and every decode site in the toolkit has the class in hand.

**A composite key is never numeric.**
It always contains `~`, so it stays a string as a PHP array key and cannot alias another key under Twig's loose `==` or `in`, which compile to PHP's native comparison and make `"0123" == "123"` true.
A base64url format could not promise this, and a `<option selected>` or a checked checkbox is decided by exactly that comparison.
**"Composite" here means two or more identifier fields, not "anything that is not a plain `id`"**: a single derived identity is **one** field, so it takes the verbatim arm above and inherits its target's spelling — a `PostSeal` keyed on an `int`-keyed `Post` mints `1`, and PHP coerces that to the **int** array key `1` exactly as it does for a single-`id` target.
It is the one flavour this promise does not cover, and a test written against `['1']` is what finds out.

**Canonical form is what encode emits, and decode accepts nothing else.**
The arity must equal the field count; no component may be empty; every `=` must be followed by two **uppercase** hex digits; a byte that is `safe` must not be escaped; the result must be valid UTF-8.
That much is implemented as decode → re-encode → compare, at the level of the *string*.
A second rule finishes it at the level of the *value*: **every component must print back to itself** once its field's DBAL type has converted it — the rule [the decode](#parameter-naming-and-what-an-empty-or-malformed-key-means) states in full.

Together they give **exactly one string per record for every identifier type whose PHP value prints back to its own component**, which is the promise a consumer may build on.
It is one-directional: a record mints exactly one key once it holds the values its own type reads back — a record built in memory and never reloaded can, under a converting type, mint a spelling the decode then refuses, and a type that refuses its printed form on read **and** on write does the same to a record freshly loaded from the database — while the guarantee that no *other* string resolves that record belongs to the database and holds on a binary or case-sensitive collation — PostgreSQL's default and SQLite's `BINARY` — except where the column compares by value or the field's own writer normalises.
A `Types::GUID` half on PostgreSQL is the first: the column is a native `uuid` compared by value, `GuidType` reads a string back untouched, so an uppercase spelling passes the shape check and the print-back rule and resolves the record the lowercase key names, while a braced or 32-hex spelling fails the shape check.
That is how a single `guid` key behaves too, so lower-casing the component on a native-guid platform would turn that accepted spelling into a new single-key behaviour change; the behaviour is kept and recorded instead.
A type whose writer normalises is the second, on every platform: the fixture `CaselessType` lower-cases on write and reads back untouched, so `AB` and `Ab` both print back to themselves, both bind as `ab`, and `toIdentifier()` answers the non-canonical map it was handed.
Both are self-aliasing — the row reached is the one the sender addressed.
Four residuals are recorded rather than closed: these two, the collation's below, and [a type that cannot read its own printed form](record-key.md#a-type-that-cannot-read-its-own-printed-form-mints-keys-that-never-decode), which is only the type that refuses that form on write too, because [the writer fallback](record-key.md#a-type-that-refuses-its-printed-form-on-read-only-decodes-it-through-its-own-writer) decodes every type that refuses it on read alone.

`decimal` is **narrowed, not closed** by its shape check: `10.5` and `10.50` still name one record, because nothing here can canonicalise scale; `-0` and `-0.0` are spellings of zero the same way; a PostgreSQL `numeric` holding `NaN` or `Infinity` mints a key the decode then refuses, so such a row is unreachable by key — a `null`, never a throw; and an absurdly long decimal still raises `22003` on PostgreSQL, the decimal sibling of the integer-width residual below.
One more belongs to the platform rather than the type: the decimal shape check is platform-independent while `RecordKey::of()` mints unchecked, so a platform that hands the value back in a form the pattern refuses makes a row's own key name no record.
Executed on SQLite: a `NUMERIC(30,2)` identifier `123456789012345678901.50` reads back as `1.2345678901235E+20` under column affinity, which the pattern refuses.
It takes an identifier beyond about fifteen significant digits on a platform with float affinity to reach; a platform handing back `.5` for a value below one would be the same shape, and that one is not executed.
Same class as the `guid` residual one type over, and unlike that one it is not gated, because the decimal form the check accepts is the one every supported platform prints.

`guid` is shape-checked **only where the platform declares a guid type of its own** — PostgreSQL's `UUID`, SQL Server's `UNIQUEIDENTIFIER`, which `IdentifierFields` recognises by comparing `getGuidTypeDeclarationSQL()` against a fixed 36-character string declaration, since DBAL 4 ships no `hasNativeGuidType()`.
Everywhere else — SQLite, MySQL, MariaDB, Oracle — the column is a plain `CHAR(36)` holding whatever the application put in it, and refusing a non-UUID spelling there would refuse a key the record side itself minted: a legacy table keyed on un-hyphenated text goes on resolving, which is the point of gating rather than dropping the check.
The check reaches a type by **class**, not by name: a type an application registers under a name of its own and derives from `BigIntType`, `DecimalType` or `GuidType` is checked as the family it extends, where a check by name would let it escape — a `bigint` one turning a malformed `{record}` into a `TypeError` 500 rather than a 404 with no statement.

The third belongs to the **collation**, not to any type.
A key is compared with `=`, so a platform whose collation ignores case or accents resolves a record from any spelling that collation equates — MySQL 8's default `utf8mb4_0900_ai_ci`, MariaDB's own default, which is a different collation and also case-insensitive, and SQL Server's default CI collation.
It reaches every text-compared component alike: `string`, `ascii_string`, `text`, `guid`, and a string-backed value object or enum that preserves the case it was given.
A `decimal` is not among them — the column compares numerically, and its own residual is the scale aliasing above.
Symfony's `uuid`, `integer` and a value object that normalises in its constructor are immune, because their reader normalises and the print-back rule leaves them one accepted spelling; a plain `guid` column on PostgreSQL is not, as above.
It is self-aliasing only — the identifier's unique index sits under that same collation, so the record a loose spelling reaches is the one the sender was already addressing and no scope is escaped — and an application that wants strictness declares the identifier column `BINARY`/`utf8mb4_bin` or normalises in the entity.
Two new records whose keys the collation equates, saved in one form, pass the taken-key check, and the database refuses the second at the flush ([the taken-key probe's limits](association-reader.md#what-the-taken-key-probe-cannot-see)).

A **NUL byte** is not a residual, because `printable()` refuses one at both ends: no record mints a key carrying it, and no key carrying one decodes.
A text parameter reaches PostgreSQL as a C string, so `abc=00zzz~p` would otherwise be compared as `abc` and hand that record a second canonical-looking spelling.
Platforms disagree about whether such a row can exist at all: PostgreSQL cannot store a NUL in a text column, while SQLite stores `abc\0xyz` whole and hands all seven bytes back (executed).
Refusing it at the one print rule is deliberate anyway — a key must mean the same record on every platform, and the key's own alphabet never carries a NUL.
The accepted cost is the other direction: a record whose identifier holds one mints no key at all, and is refused with the `is not a printable value` `LogicException`.

Worked, all **verified by executing** the reference implementation against a SQLite rig:

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
| `'sy/0.1'` (a **single** string identifier) | `sy/0.1` — verbatim, see [the divergence](record-key.md#a-single-string-identifier-containing--or--stays-broken) |

### Where a key travels, and what each context forbids

The alphabet is not a preference.
A key crosses **six** contexts on one round trip, and every character outside `[A-Za-z0-9_-]` `~` `=` breaks at least one of them — four of the six **silently**, which is why the format escapes rather than picking a delimiter and hoping, as every comparable ecosystem does.

| Context | Mechanism | Forbidden | Evidence |
| --- | --- | --- | --- |
| One URL path segment | `RouteCompiler` compiles `{record}` to `[^/]+`; `UrlGenerator` maps `'%252F' => '%2F'` out of `rawurlencode`, and the matcher `rawurldecode`s the whole path **before** matching | `/`, and the literal sequence `%2F` — which round-trips into a slash and 404s with no error; and, wherever the route's static text after the placeholder opens on a `RouteCompiler::SEPARATORS` character, that character too, since the compiler's default pattern excludes it — `-`, `_`, `~` and `=` are all in that set, which is why [panels narrows the placeholder](../panels/routing.md#the-loader-narrows-a-record-placeholder-a-separator-follows-because-the-compilers-default-excludes-it) | [`RouteCompiler.php:165-171`](../../../vendor/symfony/routing/RouteCompiler.php), [`Generator/UrlGenerator.php:55-74`](../../../vendor/symfony/routing/Generator/UrlGenerator.php) and `:201`, [`Matcher/UrlMatcher.php:73`](../../../vendor/symfony/routing/Matcher/UrlMatcher.php) |
| A dotted state path — `Repeater` keys a relationship item `record-<key>` | `PropertyAccessor` splits every path on `.`, at eight sites | `.`, which does not error — it **re-addresses the state tree one level deeper** | [`PropertyAccessor.php`](../../../packages/schemas/src/State/PropertyAccessor.php) `:32`, `:57`, `:88`, `:129`, `:284`, `:305`, `:324`, `:338` |
| The live `data-model` directive the state path is embedded in | UX's directive parser is a character state machine reserving `(`, `)`, `,`, `\|` and space; `normalizeModelName` rewrites `[` and `]` into `.` | `( ) , \| [ ]` and space | [`HasStateBindingModifiers.php:54-72`](../../../packages/schemas/src/Components/Concerns/HasStateBindingModifiers.php); `vendor/symfony/ux-live-component/assets/dist/live_controller.js:204-240`, `:264-266` |
| The state-path sanitiser, in **both** `HasState` traits | a `preg_replace` that **strips** rather than refuses, so two records could share one path | `< > " '` `` ` `` and C0/DEL | [`Concerns/HasState.php:33-41`](../../../packages/schemas/src/Concerns/HasState.php), [`Components/Concerns/HasState.php:67-75`](../../../packages/schemas/src/Components/Concerns/HasState.php) |
| A DOM id, a `<label for>` and a CSS class token | the row id, the cell id and `data-sortable-item` interpolate the **raw** key as idiomorph's morph identity; panels stamps `sy-pa-resource-record-<key>`; `LiveComponent::getChildId()` sanitises lossily | whitespace (an invalid id, and a class token splits in two); anything outside the class `getChildId()` admits | [`Table/index.html.twig:794`](../../../packages/tables/templates/partials/Table/index.html.twig), `:795`, `:864`; [`EditRecord.php:166`](../../../packages/panels/src/Resources/Pages/EditRecord.php), [`ManageRelatedRecords.php:180`](../../../packages/panels/src/Resources/Pages/ManageRelatedRecords.php); [`LiveComponent.php:93`](../../../packages/schemas/src/Components/LiveComponent.php) |
| A Twig `==` or `in` over an option value | compiles to PHP's native `==` on PHP 8, which aliases two numeric strings | an all-digit key for a class whose identifier is composite — which the grammar makes impossible | [`EqualBinary.php:19-27`](../../../vendor/twig/twig/src/Node/Expression/Binary/EqualBinary.php); [`Select/index.html.twig:86`](../../../packages/forms/templates/partials/Select/index.html.twig) |

Two consequences the table encodes rather than states.
`%` is never emitted at all, so the `%2F` trap cannot fire from our side.
And `getChildId()`'s sanitiser is **widened to `[^A-Za-z0-9_~=-]`** so the grammar survives it — see [live-component.md](live-component.md#the-child-identity-is-an-id-prop-and-a-keyless-embed-throws).

A seventh context, outside the table, is held off rather than handled.
No `LiveProp` in the toolkit declares `UrlMapping(mapPath: true)`, and that is the only reason ux-live-component's `LiveUrlSubscriber` never re-generates a route path carrying a record key during a live request.
A prop that opts into path mapping sends the key back through the router's `generate()` on every such request and inherits every constraint of the URL path row above.

No ecosystem escapes its delimiter: Doctrine's own identity hash joins with a space, Sonata joins with `~`, API Platform writes `a=1;b=2`, Rails joins with `_`, and EasyAdmin and the Django admin refuse composite keys outright.
The choice is therefore between adopting a known-ambiguous convention and exceeding it, and the six contexts in the table are the named, checkable gap that [principles §2](../../internals/principles.md) asks for before inventing, and the seventh is held off.

**The client treats a key as opaque.**
No Stimulus controller splits a key or interpolates one unescaped into a selector — forms' [`select.ts`](../../../packages/forms/assets/src/utils/select.ts) finds an option's badge through `CSS.escape()` — key lists cross as JSON (`data-sy-group-keys`), and the selection `LiveProp`s carry keys without reading them.
So the grammar is the server's alone and no JavaScript depends on it; a controller that parses a key, or puts one into a selector raw, breaks that.

### The API

`Sylament\Schemas\Identity\` is a capability directory at `src/` level, beside `Query\` and `State\`, per `.claude/rules/php.md`'s rule that the engine a trait delegates to is filed by capability.
It lives in `schemas` rather than `support` because every Doctrine-facing package already requires `schemas`, and `support` has no Doctrine at all.
Three public classes; `IdentifierFields` is `@internal` and is the per-class descriptor the other two share — field names in mapping order, whether each is a to-one association, and the resolved DBAL type name each binds as.
`@internal` means *no part of the key contract*, not *no reader outside this directory*: the relation layer's owner bind reads the terminal field's type off it, which makes `State\AssociationReader` its first reader outside `Identity\` — same package, and [recorded with the bind](relationship.md#a-single-identifier-owner-binds-its-identifier-value-typed-by-the-fields-own-dbal-type).

```php
final class RecordKey
{
    public const string SEPARATOR = '~';

    /**
     * Each component as its row would read it back — through its field's DBAL type to the database form and back.
     *
     * @param class-string|null $class the mapped class to key by, when the caller declares one
     *
     * @throws LogicException      when the identifier is incomplete, or a component is not printable
     * @throws ConversionException when a component's type refuses to convert it
     */
    public static function of(EntityManagerInterface $entityManager, object $record, ?string $class = null): string;

    /**
     * Null while a component is missing or blank — a `Stringable` that prints blank included; otherwise minted as of() mints.
     *
     * @param class-string|null $class the mapped class to key by, when the caller declares one
     *
     * @throws LogicException      when a component is not blank and still has no printed form
     * @throws ConversionException when a component's type refuses to convert it
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

`of()` and `find()` key a record by its own class unless the caller **declares** one through the optional third argument: a table passes its record class, so a subclass the mapping does not know — a test double, an old-style proxy — keys by the class the table was built over rather than failing a metadata lookup.

**There is no field-names accessor, and that is a decision.**
A consumer that needs the identifier's field names or its arity reads `ClassMetadata::$identifier` and `isIdentifierComposite`, which it already holds whenever it holds the class — the foundation exposes neither, so that nothing grows a second, drifting copy of the mapping.
`IdentifierFields` carries them `@internal` for the two public classes.
`AssociationReader` has no identifier-field-name accessor either: what its callers need is an `IdentifierConstraint` operation, and the reader's own owner-field read is `getOwnerIdentifierField()`, a private `$metadata->identifier[0]` — the one such read that is no operation of this foundation at all.

Read the shapes off those signatures in one line each.
Every **predicate** method binds its values on the `QueryBuilder` it is handed and returns DQL **text** for the caller to place — in a `WHERE`, an `andWhere`, a `DELETE` or a spliced subselect — so the builder never decides where the predicate goes; `inColumns()` is the one that returns **SQL** text, for a DBAL statement, and binds on the DBAL builder it is handed.
`select()`, `correlate()` and `groupBy()` bind nothing at all.
`equals()`, `notEquals()` and `notIn()` answer `null` rather than throwing, and `null` means *there is no predicate to add*, never *something went wrong*.

A key list may hold ints beside strings — PHP turns a numeric string array key into an int, so `array_keys()` of a map keyed by record key hands one back — and each is cast to its string once, where it enters; a key stays a string everywhere below that, and `chunk()`/`batch()` hand strings back.

**The decode is one pipeline, and every operation shares it.**
A key is split into components (a single-field identifier is one component, verbatim); each component is **shape-checked** if and only if its field's type is, or extends, `BigIntType`, `DecimalType` or `GuidType` — the three families, matched by class, whose own conversion hands a malformed string straight back, so nothing downstream would refuse it before the database does, the `guid` arm running only where the platform declares a guid type of its own; each is then run through its field's `Type::convertToPHPValue()` — and where that raises a `ConversionException` or answers a value that does not print back to the component, through [the type's own writer and back](record-key.md#a-type-that-refuses-its-printed-form-on-read-only-decodes-it-through-its-own-writer), `convertToPHPValue(convertToDatabaseValue($component))`, a `ConversionException` out of that pair meaning *the key names no record*, not an error; and finally the converted PHP value must **print back to the component it came from** under the one printable rule below.
`toIdentifier()` answers the result of that pipeline — the **PHP values**, keyed by field name, which is exactly what `EntityManager::find()` takes.
`find()` takes that map in any field order and with string-typed values, and throws on a bare scalar for a composite class, on a missing field and on an unrecognised one alike, so a partial map is never handed to it: `toIdentifier()` answers the whole map or `null`.
The predicate methods take it one step further and bind `convertToDatabaseValue()` of those same values, with the type's `getBindingType()`; a single-field batch binds the converted list under the matching `ArrayParameterType`.
The round trip is deliberate: a DBAL type's `convertToDatabaseValue()` is contracted for the *PHP* value, and a custom value-object identifier type may be strict about it where an untyped string bind would happen to work.
Anything other than a `ConversionException` that a custom type throws — including a refusal of the value it has just produced — is that type breaking DBAL's contract, and propagates — a writer that throws so for a forged component included, because the fallback hands it one.
**A field or column name reaches DQL or SQL from `ClassMetadata` only**, never from the key.

**One printable rule, used on both sides.**
`IdentifierFields::printable()` is what the record side prints a value with and what the decode compares against, so encode and decode cannot drift: a `BackedEnum` becomes its backing value, a `Stringable` its `__toString()`, a `bool` `'1'` or `'0'` (a plain cast prints `false` as the empty string, which would mint an illegal empty component), any other scalar its `(string)` cast — and a result that is non-scalar, not valid UTF-8, blank, or carrying a **NUL byte** is **not printable**: `of()` and `fromValues()` refuse it with the `is not a printable value` message, and the decode answers `null`.

For an association identifier field the type is the **target's** identifier field type, followed through a target whose own single identifier is itself an association — so `Residency.venue` binds as the `Venue.handle` type, and a value selected through `IDENTITY()` is run back through that type's `convertToPHPValue()` before it is printed, which is what makes the row side and the record side agree.

### The operations and their DQL

Three classes stand in below, all real fixtures: `Song` (`#[Id] int $id`) for the single-identifier lane that must not move; `Shortcut` (`#[Id] string $host`, `#[Id] string $path`) for a scalar pair; and `Residency` (`#[Id]` ManyToOne `Venue`, `#[Id] string $season`) for a derived identity, whose `Venue` is itself keyed on a `Handle` value object through a custom DBAL type — so the association arm is exercised with a type that actually converts.

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

**`IDENTITY()` appears in a `SELECT` list and nowhere else.**
The plain path `alias.field` is what goes into `WHERE`, correlation, `GROUP BY`, `ORDER BY` and a DQL `DELETE` — legal for an identifier association because such an association always has exactly one join column — while `IDENTITY()` is *illegal* in `GROUP BY` and emits a table-qualified column in a `DELETE`.
Conversely a bare association path is a semantical error in a `SELECT` list, which is why the projection is the one place that uses `IDENTITY()`.
The plain path is also what [`ColumnPathResolver::resolveOrderable()`](../tables/has-query.md#a-dotted-name-is-resolved-against-the-mapping-before-it-is-refused) emits, which is what keeps the sort tiebreak's string dedupe honest.

**The single-identifier emission is the plain one-column DQL.**
`alias.id = :base` and `alias.id IN (:base)`, bound under the field's binding type, are what a single-identifier class emits, and the DQL-text assertions in `HasBulkActionsTest` and `CanSortRecordsTest` pin that text.
That is the property that makes this a foundation rather than a rewrite.
The by-key dissociate's statement is DQL wherever its foreign key is one column; only a mapped-by spanning several join columns writes through [`inColumns()`](#incolumns-is-in-in-sql-for-a-statement-that-names-one-table).

**The DQL parses down to ordinary SQL.**
`equals()` on a scalar pair executes as `… WHERE s0_.host = ? AND s0_.path = ?`, on a derived identity as `… WHERE r0_.venue_id = ? AND r0_.season = ?`, and a DQL `DELETE` over two composite keys as `DELETE FROM Residency WHERE ((venue_id = ? AND season = ?) OR (venue_id = ? AND season = ?))` — all three executed.

**A `KeySelection` is read back through `keyOf($row)` or `keyOfHydrated($row)`, never re-derived.**
Each reads exactly the aliases it projected, throwing a `LogicException` naming the class and the missing column if the row came from a different selection, and hands the values to its held `IdentifierFields::toKey()`; they differ in which values they convert through the field's own DBAL type first, and the caller picks by the hydrator that produced the row.
`keyOf()` takes a **scalar** row, whose every value is raw, and converts every value it was not handed as an object.
`keyOfHydrated()` takes an **array** row, or a row Doctrine's limit walker answers, whose field paths the hydrator has already read through their types, and converts only the `IDENTITY()` association halves, which arrive raw under every hydrator.
Either way that is the *same* print step the record side uses, by construction rather than by agreement; see [the semantics note](record-key.md#the-row-side-and-the-record-side-mint-one-string-and-only-the-codec-may).

#### `inColumns()` is `in()` in SQL, for a statement that names one table

The one operation here that emits **SQL**.
Its caller is the by-key dissociate over a mapped-by spanning several join columns, whose statement there is a DBAL `UPDATE` of one table: there is no identification variable to qualify a field with, and a to-one spanning several join columns has no DQL form to null it in at all ([why the statement left DQL](../actions/dissociate-action.md#the-by-key-arm-is-a-dql-update-and-a-dbal-one-where-the-foreign-key-spans-several-columns)).
So the operands are the class's identifier **columns** in identifier order, as `QuoteStrategy::getIdentifierColumnNames()` names them — quoted only where the mapping quotes them, so an ordinary mapping's statement carries bare `host = ?` — with no alias, and the parameters are bound on the DBAL builder it is handed.
A DBAL builder carries no entity manager, so the manager is the leading argument, as it is for `chunk()`.

Worked over the schemas fixtures its own test runs on — `Post` (`int $id`), `Redirect` (`host` plus `path`), `PostTranslation` (`#[Id]` `Post` plus `locale`) and `PostSeal` (a single derived identity onto `Post`) — each as the `WHERE` of a real statement:

| Class | `inColumns($entityManager, $query, …, $keys, 'keys')` | Bound |
| --- | --- | --- |
| `Post`, keys `1`, `3`, `abc`, `3` | `id IN (:keys)` | `[1, 3]` as `ArrayParameterType::INTEGER` — `abc` dropped, the repeat bound once |
| `Redirect`, two keys | `((host = :keys_0_0 AND path = :keys_0_1) OR (host = :keys_1_0 AND path = :keys_1_1))` | four `ParameterType::STRING` |
| `PostTranslation`, two keys | `((post_id = :keys_0_0 AND locale = :keys_0_1) OR (post_id = :keys_1_0 AND locale = :keys_1_1))` — the **join column**, never the field | `INTEGER`, `STRING` per key |
| `PostSeal`, keys `1`, `2` | `post_id IN (:keys)` | `[1, 2]` as `ArrayParameterType::INTEGER` |
| any class, no key, or none well-formed | `1 = 0` | nothing |

**One membership body, two vocabularies.**
`in()` and `inColumns()` share one private body that takes the left-hand operands — `alias.field` for DQL, the column for SQL — and the binding step takes the same list, so the two cannot drift: every `in()` and `equals()` pin exercises the code `inColumns()` runs, the raw-bytes `BINARY` ones included.
Everything else is `in()`'s: a single identifier is one `IN` under the array type its binding type maps to, a composite one the balanced OR of ANDs under `:<base>_<i>_<j>`, a malformed key dropped, a repeated one bound once, and a base ending in `_<digits>` refused by `assertParameterBase()`.

**It names columns, never a table.**
The table, the `SET` half and the quoting of both are the statement's author's — [the dissociate's](../actions/dissociate-action.md#the-by-key-arm-is-a-dql-update-and-a-dbal-one-where-the-foreign-key-spans-several-columns) reads them off the mapping through the same quote strategy, and that is where a JOINED leaf, a reserved word and a single-table leaf are pinned, through the real action.
The column count agrees with the field count by an ORM guarantee rather than a check: an identifier association always resolves to [exactly one join column](record-key.md#an-identifier-association-always-resolves-to-exactly-one-join-column).
The rest of the vocabulary is pinned over fixtures the test brings along: a JOINED subclass is named by the identifier column its root declares — `ledger_id IN (:keys)` for `LedgerDraft`, whose field is `id` — which its own table keys by too; a composite identifier of reserved words comes out as `(("where" = :keys_0_0 AND "having" = :keys_0_1) OR …)`; and a raw-bytes `Crate` or `Pallet` half is bound as its sixteen bytes under `ArrayParameterType::BINARY` or `ParameterType::BINARY`, where the same bytes bound as text match no row on SQLite.

**It applies no discriminator and no SQL filter.**
A single-table subclass is named by the columns it shares with its root, so a key naming a row of the root class selects that row too — pinned as such — and no enabled Doctrine SQL filter reaches a DBAL statement.
A caller narrows by keys a discriminated, filtered query already produced, which the one caller does ([why that is enough there](../actions/dissociate-action.md#the-by-key-arm-is-a-dql-update-and-a-dbal-one-where-the-foreign-key-spans-several-columns)).
Pinned by [`IdentifierConstraintColumnsTest`](../../../packages/schemas/tests/Unit/Identity/IdentifierConstraintColumnsTest.php).

### Invariants

A consumer may rely on every one of these.

1. A key is **one string**.
   A composite key matches `^[A-Za-z0-9_=~-]+$`, contains `~`, and is never numeric.
2. `RecordKey::of()` is **injective and stable across requests**, and `KeySelection::keyOf()` over a scalar row selected with `select()`, like `keyOfHydrated()` over an array row or a limit-walker row, equals `of()` of that record.
   Each reads a value through its type once; `keyOf()` handed a row the hydrator already converted reads it twice, which only a type whose reader takes its own PHP value back unchanged survives — every built-in and every object-returning type does, while a scalar type whose reader does not mints another key, or throws ([why the two readers exist](record-key.md#a-hydrated-row-is-keyed-without-reading-its-fields-a-second-time)).
   `fromValues()` does **not** carry that guarantee over a projected row — it prints what it is handed without converting, so it agrees only where a value's database form is already its printed one.
   The second half is not decoration: `AggregateQuery::readValues()` fills a store under row keys that cells look up by record key, and a disagreement there fills the store under keys no cell ever asks for — a cell reading its default, with no error anywhere.
   Its sibling in [the page read](../tables/host-can-paginate-records.md#a-keyed-root-that-joins-a-collection-pages-in-two-statements-because-doctrines-walkers-key-by-one-column) fails the same way and worse: the key goes into a predicate, so the page itself comes back empty.
3. **Client input never throws.**
   `toIdentifier()`, `equals()`, `notEquals()`, `in()` and `notIn()` are total over arbitrary strings — and a map `toIdentifier()` answers is always one `EntityManager::find()` can take, because the decode has already run each component through its field's DBAL type, the same step the predicates bind through.
   The two answer `null` together, over every string: a key that narrows no query decodes to no identifier, and a key that decodes never explodes inside `find()` — for every field type that honours DBAL's contract by refusing only with a `ConversionException`; a custom type that throws anything else propagates ([why](record-key.md#a-type-that-refuses-its-printed-form-on-read-only-decodes-it-through-its-own-writer)).
4. A decoded value is **only ever a bound parameter**; a field name **only ever** comes from `ClassMetadata`.
5. A single-identifier class emits **the one-column DQL text** above; the `ORDER BY` tiebreak's direction token is printed `ASC`/`DESC` by the ORM 3.7 floor at every width — which binds what `IdentifierConstraint` emits, not what a consumer composes around it.
   The relationship query's many-to-many arm is a correlated `EXISTS` rather than an `IN` subselect, for every width alike, and returns the rows an `IN` subselect would.
   The one statement that is not DQL — the by-key dissociate — is a DBAL `UPDATE` over `inColumns()` only for a mapped-by spanning several join columns, which has no DQL form; a one-column foreign key is nulled by a DQL `UPDATE`, as [enumerated where the statement lives](../actions/dissociate-action.md#the-by-key-arm-is-a-dql-update-and-a-dbal-one-where-the-foreign-key-spans-several-columns).
6. **Four consumers chunk** — the by-key delete, the reorder's re-resolution, tables' aggregate batch and the by-key dissociate, a DQL statement, or a DBAL one where its mapped-by spans several join columns.
   A key list the client sizes is batched wherever the identifier is composite; the two reads that run one statement over a whole list are [recorded with the chunk rule](#the-chunk-rule).
7. **A key is minted from the value the row would read back.**
   `of()` and `find()` pass each component through its field's DBAL type to the database form and back before printing it, so a record this request has just written mints the key a later read of its row mints — the one the strict decode answers for — while a loaded record, which already holds that value, mints what it always did.
   A blank component is handed back unconverted, so `of()` refuses it as not printable and `find()` answers `null`; and a **non-numeric** string held in an integer-typed half of a new record — which only an untyped or `int|string` property can hold — raises its type's own error, or mints `…0`.
   The round trip is the type's, not the database's, so a database that rewrites what it stores can still mint two spellings of one row — SQLite keeps a `decimal` `10.50` as `10.5`, and the new record mints `…10.50` where the same row, loaded, mints `…10.5` — a database-side gap the mint cannot see ([why the mint converts](record-key.md#a-key-is-minted-from-the-value-its-row-would-read-back)).

### The chunk rule

`chunk()` splits a key list into statements of `min(500, intdiv(2000, F))` keys for `F ≥ 2` identifier fields, and 2 000 keys for one — under SQL Server's 2 100-parameter ceiling, linear in total parse cost, and with the **same DQL text** for every full chunk of **distinct, well-formed** keys, so such a chunk is parsed once and served from the query cache thereafter.
The two qualifiers are load-bearing rather than pedantic: `chunk()` **de-duplicates first**, so a duplicate cannot silently shorten a chunk, but a malformed key inside one is dropped by `in()` later and that chunk's text is a term shorter — a cache miss, not a wrong answer.
A chunk of nothing but malformed keys still issues its statement, as `1 = 0`.

The numbers behind it, measured rather than assumed.
SQLite refuses a **flat** OR chain at ~997 terms with `Expression tree is too large` — its limit is expression *depth*, not parameter count, and its compiled `MAX_VARIABLE_NUMBER` here is 250 000 — which is why the disjunction is nested as a balanced binary tree; with that shape 1 501 two-field keys execute in ~490 ms and 16 000 have been run.
PostgreSQL 18 takes either shape up to its 65 535 bound-parameter ceiling, but on a populated table a statement that large crosses `jit_optimize_above_cost`.
One 10 000-key `notIn()` over 10 000 rows spent 337 s of backend time with the default `jit=on`, and 4.8 s with it off.
On the PHP side a `setParameter()` per bound value is quadratic in building, each call a linear lookup in the builder's parameter collection: about 1.8 s for 5 000 keys.
So a composite membership appends its parameters in one pass, and 5 000 keys build in about 15 ms.
The parse is Doctrine's and stays quadratic: `SqlWalker::walkInputParameter()` asks `Query::getParameter()`, a linear filter, once per parameter.
A first parse takes about 50 ms at 500 keys, 170 ms at 1 000, 3.4 s at 5 000 and 14 s at 10 000.
The 500-key composite chunk is sized against that curve.
On PostgreSQL 18 a 500-key `in()` plans as an index scan costing about 3 500–4 500 whatever the table size, some 25× under `jit_above_cost` (100 000).
A 500-key `notIn()` is a sequential scan whose cost grows with the table — about 25 000 at 10 000 rows, 126 000 at 50 000 — so it crosses `jit_above_cost` from roughly 40 000 rows on, and JIT then adds about 130–280 ms of emission to that one statement (0.54 s against 0.19 s at 50 000 rows, 0.76 s against 0.45 s at 200 000).
Through `getSelectedTableRecords()` only a tracked deselection of 101–500 keys reaches that shape; a larger one is subtracted in PHP.

**Four sites chunk, and three of them are writes.**
The by-key delete issues one DQL `DELETE` per chunk, several chunks inside one transaction, and the by-key **dissociate** nulls a one-column foreign key by one DQL `UPDATE` per chunk under the same rule, and a foreign key spanning several columns by one DBAL `UPDATE` per chunk, wrapped in one `Connection::transactional()` from the second chunk on ([dissociate-action.md](../actions/dissociate-action.md#the-by-key-arm-is-a-dql-update-and-a-dbal-one-where-the-foreign-key-spans-several-columns)) — the DBAL arm SQL, so no DQL parse is paid there and its chunk sizes bound the statement's parameter count alone; the reorder's re-resolution issues one `SELECT` per chunk, each narrowing a **copy** of the table's query — a copy because a second predicate ANDed onto the first would answer nothing, and each chunk keeps the one parameter base, since a base carrying a chunk index of its own is what `assertParameterBase()` refuses.
Reordering is unpaginated by default, so its order list is the whole table.

The one read among the four is tables' aggregate batch, and its chunks combine as cheaply as the reorder's: it runs the scope closure once on the pristine builder, narrows a **copy** per chunk under that same single base, and unions the per-chunk maps with `+` — the chunks are disjoint by construction, since a key appears in one of them, and each chunk's result is keyed by record key, so nothing has to be reconciled.
That is the same property the reorder's re-resolution relies on, which is why a chunked read may hydrate: what a chunk boundary destroys is not hydration but **order**.
A read whose caller consumes the statement's order cannot be chunked — the record step of the keyed fetch-join page — and a `NOT IN` cannot be chunked at all.
The hydrating selection arm loses that order across batches, and is batched anyway, for a composite selection larger than one batch only, keeping table order within a batch (below).

**A key list the client sizes is batched wherever the identifier is composite.**
That list is untrusted, and one statement over it costs time quadratic in a number the client chooses: a forged 10 000-key selection holds a request for about 23 s as one statement.
`IdentifierConstraint::batch()` answers the batches: the whole list for a single identifier, whose one array parameter scales, and `chunk()`'s batches for a composite one.
The sites are:

- The selection's reads in `HasBulkActions`, hydrating and plucking alike, one statement per batch.
  While tracking, a deselected bag larger than one batch is subtracted in PHP from a pluck of every key the table admits, since a `NOT IN` cannot be split across statements.
  A bag that fits one batch stays one `notIn()`.
- Under `fetchSelectedRecords(false)` beside `checkIfRecordIsSelectableUsing()`, the records are hydrated once and keyed in PHP instead of bound back as a second key list.
- `AttachAction` and `AssociateAction`'s by-key fetch.
- `Select::getOptionLabelsFromRelationship()`.

A composite selection's hydrated records come back in table order within a batch, and batch after batch across them.
Where batches of records merge — the hydrating selection read and the attach and associate fetch — each record is kept once, by identity: two spellings of one key that the database compares equal, such as a decimal `1` and `1.0` or a case variant under a case-insensitive collation, can land in different batches, where one statement returns such a row once.

Two sites run one statement over a whole list:

- `getSelectedTableRecordsQuery()` returns a builder to its caller (a custom bulk action's `$selectedRecordsQuery`), so it cannot be split, and it binds every key.
- The record step of [the keyed fetch-join page](../tables/host-can-paginate-records.md#a-keyed-root-that-joins-a-collection-pages-in-two-statements-because-doctrines-walkers-key-by-one-column) has a key count bounded by the largest numeric page option.

A single identifier's list is one statement with one array parameter.

Three alternatives are rejected: factoring a batch's leading field (`a = ? AND b IN (…)`), range-checking integers by column width, and a `CONCAT`-keyed `IN` (non-sargable, and it breaks typed binds).
A fourth, batching the hydrating selection read, reorders results, so it is taken only for a composite selection larger than one batch, where the unbounded single statement is the worse cost.

### Parameter naming, and what an empty or malformed key means

Every predicate takes a **caller-supplied parameter base** and derives its names from it: `:base` for a single field, `:base_<j>` for the fields of one composite key, `:base_<i>_<j>` for key *i* of a batch.
Two things force this.
A counter minted off `count($query->getParameters())` — the trick [`SearchConstraint`](search-constraint.md) and `Group::mintParameter()` use — does not move until each `setParameter()` lands, so an F×K application would collide with itself; and the selection lane puts **two** predicates on one builder in one request (`getSelectedTableRecordsQuery()` and the selectable narrowing), where `setParameter()` would overwrite in silence.
A composite membership appends its parameters in one pass instead, and refuses by name a base the builder already carries; every other predicate binds through `setParameter()`.
The table lane's bases are `sylament_record_key`, `sylament_selected_record_keys`, `sylament_selectable_record_keys`, `sylament_reordered_record_keys`, `sylament_summary_keys`, `sylament_agg_keys` and `sylament_page_record_keys`, each a fixed name, so a single-identifier query's predicate text is stable for the query cache.
The relation layer binds three more: `sylament_selected` (a relationship `Select`'s selected-record lookup), `sylament_values` (its label read) and `sylament_ignored_record`, which both that field's `ignoreRecord` exclusion and `unique(ignoreRecord:)` use — a counted `sylament_probe_<n>` would end in `_<digits>`, which is refused for **every** class, a single identifier included.
`sylament_relationship_owner` is `equals()`'s base in both relationship queries where the owner is keyed by several fields — minting `_0`, `_1`, … — and one plain `setParameter()` of that exact name where it is not.
The two spellings never meet, the route being decided once per builder, and the single-identifier spelling keeps that query's text stable for the query cache ([the owner route](relationship.md#an-owner-of-any-width-is-scoped-through-its-own-collection-rather-than-through-a-mapped-by-path)).
`inColumns()` adds no base: it binds under its caller's, and its one caller — the by-key dissociate over a mapped-by spanning several join columns — keeps `sylament_selected_record_keys` — the base its DQL `in()` binds — on a DBAL builder, where `assertParameterBase()` refuses a `_<digits>` ending exactly as it does on a DQL one.

Three rules come with a caller-supplied base, and the first is enforced.
**A base never ends in `_<digits>`**: base `k` with key 1, field 0 and base `k_1` with field 0 both mint `:k_1_0`, and `setParameter()` would overwrite one with the other in silence — so a base that ends that way is refused with a `LogicException` as the developer error it is, rather than answering a query with the wrong rows.
**The text a predicate returns belongs on the builder it was bound on**: binding is a side effect of asking for the text, so dropping the text leaves parameters bound to nothing and Doctrine counts them (`the query defines 0 parameters and you bound 2`).
And **a base is applied once per builder**: a second application with fewer keys leaves the first application's parameters bound and over-counts the same way.
None of the bases above is applied twice on one builder, and a composite membership on a DQL builder refuses a base whose first name that builder already carries.

Empty and malformed, stated once:

| Call | Answer |
| --- | --- |
| `equals()` / `notEquals()` with a malformed key | `null` — nothing to match, nothing to exclude, and **nothing is bound** |
| `in()` with some malformed keys | the malformed ones are **dropped**; duplicates are de-duplicated |
| `in()` with no well-formed key left, or an empty list | `1 = 0` — an empty `Orx` renders as `WHERE ` and is a parse error, so the constant is emitted in PHP before the builder sees it |
| `notIn()` with no well-formed key left, or an empty list | `null` — **omit the predicate entirely**. An empty exclusion set means *every* row, and DBAL expands an empty list to `NOT IN (NULL)`, which matches nothing: a bulk delete after select-all would delete nothing at all, silently |
| `toIdentifier()` with a malformed key | `null`, never an exception |
| `chunk()` with an empty list | `[]` |

"Malformed" is exact, and **applies to single-identifier keys too**.
Three clauses, in the order the decode runs them:

1. **Not canonical** per [the grammar](#the-grammar) — the wrong arity, an empty component, a lowercase or short escape, an escape of a `safe` byte, invalid UTF-8 after unescaping.
2. **The wrong shape for a type whose conversion refuses nothing** — exactly three families of them, matched by class, and the boolean family beside them.
   A component whose type is or extends `BigIntType` must match `^(?:0|-?[1-9][0-9]*)$`, one whose type is or extends `DecimalType` `^-?(?:0|[1-9][0-9]*)(?:\.[0-9]+)?$`, and one whose type is or extends `GuidType` must be UUID-shaped, because all three types hand a malformed string straight back and nothing downstream would refuse it before the database does.
   The `guid` arm runs **only where the platform declares a guid type of its own**, which `IdentifierFields::hasNativeGuidType()` decides by comparing `getGuidTypeDeclarationSQL([])` against the platform's own fixed 36-character string declaration: where they differ the column is a `UUID` or `UNIQUEIDENTIFIER` and a non-UUID spelling is a syntax error, where they match it is a plain `CHAR(36)` holding whatever the record side put there, and refusing that spelling would refuse a key the record side minted.
   The `bigint` and `decimal` arms are platform-independent.
   All three patterns carry PHP's **`D` modifier**, so `$` means end of *subject*: without it PCRE also matches before a trailing newline and `7\n` would name record 7 — the same trap [the upload seam](interacts-with-schemas.md#uploadschemacomponentfile-carries-the-bytes-beside-the-seam) records, and the reason `HasSchemas.php:230` and `Uploads\TemporaryUploads.php:31` anchor theirs the same way.
   A component whose type is or extends `BooleanType` must be `0` or `1`, the only two spellings a boolean prints: any other would fail the print-back rule anyway, but on PostgreSQL DBAL's boolean **writer**, which [the writer fallback](record-key.md#a-type-that-refuses-its-printed-form-on-read-only-decodes-it-through-its-own-writer) reaches for a spelling the reader reads as another value, refuses an unrecognised literal with an `UnexpectedValueException` rather than a `ConversionException` — so the check refuses it first, at no conversion.
3. **A component its field's type refuses, or one that does not print back to itself.**
   Every other type is left to its own `convertToPHPValue()`, whose `ConversionException` means "names no record" — and the converted value is then printed with the one printable rule and compared against the component it came from.
   That single compare is what refuses `05`, `5abc`, `"5\n"`, `+5`, `1e3`, `-0`, a lowercase spelling of a strict value object, **and** `99999999999999999999`, which `IntegerType`'s `(int)` cast *saturates* to `PHP_INT_MAX` and would otherwise resolve the `PHP_INT_MAX` row.
   (`BigIntType` does not saturate, which is why clause 2 exists for it instead.)
   There is no integer-family regex beyond `bigint`'s: the print-back compare does that job for `smallint` and `integer`, and does it without a per-type table to drift.
