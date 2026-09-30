# Upgrading

## Composite keys (M7)

Support for [composite keys](composite-keys.md) changes a few things for applications whose entities all have a single identifier:

- **Stricter URLs.** A record key in a URL must be spelled exactly as Sylament writes it. `/admin/articles/05/edit` and `+5` now return 404; before, they opened record 5. So does `5.0` (which opened record 5 on SQLite and was a database error on PostgreSQL). On PostgreSQL, uppercase, braced or 32-hex spellings of a UUID key also return 404. On MySQL and SQLite, UUID keys stored as binary now open their record; before, no spelling did.
- **Primary keys are read-only on edit.** Editing an assigned primary key (a slug, a UUID) or an auto-incremented one is refused with *This value cannot be changed.* Before, the key was rewritten or ignored.
- **Taken keys are form errors.** Creating a record with an assigned key runs one extra `SELECT` to check the key is free, and a taken key is a form error instead of a database error.
- **Blank keys are form errors.** A shared-primary-key entity saved with its mapped key association left blank is refused with *This value should not be blank.*, where the create used to fail inside Doctrine.
- **Many-to-many relation queries use `EXISTS`.** Relation managers and attach options over a many-to-many now query with `EXISTS` instead of `IN`. Results are the same. On SQLite, a very large related table can make these queries slower; PostgreSQL and MySQL plan them as before.
- **Removed methods.** `AssociationReader::getIdentifierFieldName()` is gone.
- **Changed signature.** The protected `DeleteBulkAction::deleteSelectedRecordsByKey()` now takes `(EntityManagerInterface $entityManager, string $recordClass, array $chunks)`.
- **Constructor errors are named.** An entity whose constructor requires arguments now fails with a `LogicException` naming the class, instead of PHP's `ArgumentCountError`.
- **Filtered rows count as taken.** The check for a key already in use looks past Doctrine SQL filters. A key held by a soft-deleted or filtered-out row is now a form error instead of a database error.

## Hardening (M7 · 9)

- **Searching a non-text column works on PostgreSQL.** A `searchable()` integer, decimal, boolean, uuid or date column now compares as text there (`LOWER(col || '') LIKE …`), where every search of the table was a database error. String columns are searched exactly as before. `jsonb`, binary and array columns still cannot be searched this way; use `searchable(query:)`.
- **A search that filtered a fetched collection is refused.** A searchable column like `blocks.heading` on a table whose query fetch-joins `blocks` under the alias `blocks` used to narrow each row's collection instead of choosing rows. It now throws a `LogicException` on the first search. Alias the join something the column does not start with, or use `searchable(query:)`. A resource's globally searchable attributes are checked the same way, on the first global search; there the only way out is to alias the join differently, since an attribute has no `searchable(query:)`.
- **`$recordRouteKeyName` is checked.** Naming an association, a name the record class does not map as a field, or one field of a several-field identifier now throws a `LogicException` on the first list render or record binding, where it failed later with a database or printing error, or, for one field of several, resolved a record by that field alone and failed once two records shared its value.
- **Replicate names its refusals.** A `ReplicateAction` whose copy would carry a key another record already holds is refused with a `LogicException` naming the fix (or, with a modal, *This value is already used.*), where Doctrine raised `EntityIdentityCollisionException` or a unique violation. A single string key a `prePersist` hook leaves blank is refused by name where Doctrine raised `ORMInvalidArgumentException`.
- **Second-level cache.** The by-key bulk delete evicts the cached entities of the class it deletes from. The by-key bulk dissociate evicts the cached entities of the class it updates and the owner association's cached collections.
- **Int keys.** `IdentifierConstraint::in()`, `notIn()`, `inColumns()`, `chunk()`, `batch()`, `equals()` and `notEquals()` accept int keys.
