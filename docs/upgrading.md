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
