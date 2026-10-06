---
paths:
  - "packages/{actions,forms,panels,schemas,tables}/src/**/*.php"
description: How code compares, keys and reads records, since a record may be keyed by several fields or by an association.
---
# Record rules

- **Identity goes through `Identity\`.** Compare, select, group, order, chunk and batch record identifiers through `IdentifierConstraint`, and mint or decode keys through `RecordKey`, never through a single id column, because a record may be keyed by several fields or by an association.
- **Decode a client-supplied key before binding it.** On a malformed key, answer "no such record" without querying (`IdentifierConstraint::equals()` returns `null`), because an untyped bind lets `05` alias record 5 and turns a non-numeric key into a 500.
- **Never construct an `AssociationReader`.** Rebind the one a site already holds with `withEntityManager()`; an application may subclass or decorate the service, and `new AssociationReader(...)` drops that in silence. `AssociationReaderConstructionTest` pins it.

Background: `docs/progress/schemas/record-key.md`, `docs/progress/schemas/association-reader.md`.
