# Composite keys

Sylament works with Doctrine entities whose identifier has more than one field, or includes an association. Tables, resources, relation managers, forms and actions treat them like entities with a single `id`.

## Supported identifiers

- Several scalar `#[ORM\Id]` fields: strings, integers, big integers, GUIDs and UUIDs, backed enums, and custom types that read back what they print.
- An `#[ORM\Id]` association plus scalar fields, for example a translation keyed by its article and a locale.
- A single `#[ORM\Id]` association (a shared primary key).

Doctrine itself refuses date and time identifier fields, and an entity with a composite key inside another entity's identifier, so Sylament cannot offer them either.

## What a record key looks like

Every record gets one string key. A single identifier is unchanged: `/admin/articles/7/edit`. A composite key joins its fields with `~`, in the order the entity declares its `#[ORM\Id]` fields. Every byte outside `A–Z`, `a–z`, `0–9`, `_` and `-` is written as `=` plus two hex digits:

| Identifier | Key | Edit URL |
| --- | --- | --- |
| article 7, locale `pt-BR` | `7~pt-BR` | `/admin/article-translations/7~pt-BR/edit` |
| host `www.example.com`, path `/blog` | `www=2Eexample=2Ecom~=2Fblog` | `/admin/site-redirects/www=2Eexample=2Ecom~=2Fblog/edit` |

Keys are read strictly. Any other spelling of a key is a 404, and it never reaches the database.

The order of the `#[ORM\Id]` fields is part of the key. Reordering them changes every key, and every URL someone saved.

## Building forms for composite keys

Nothing in tables or relation managers is specific to composite keys. In forms:

- Give every key field a form field. On the edit page, disable it with `disabledOn('edit')` and nothing else.
- Do not add `dehydrated(false)` to a key field. The create page would then save no key, and the save is refused with *This value should not be blank.*
- A key value set outside the form, for example in `mutateFormDataBeforeCreate()`, is only written if a field maps it. Use a `Hidden` field for keys the user should not type.
- A key field the form leaves out and nothing fills before the save — a `prePersist` listener may — fails the save with an error naming the class. It is a mistake in the form, not something the user can fix.
- Leave a key field that a `prePersist` callback or listener fills out of the form. A key field the form shows is the user's to fill, and leaving it blank is refused under it, whatever a callback would do.
- Give the resource a record title (`getRecordTitle()` or `recordTitle()`), since no single attribute names a record with a composite key.

## What users see on create and edit

- **A key another record already uses:** *This value is already used.*, under the first key field that is not an association. This also finds rows that a Doctrine SQL filter, such as soft delete, hides.
- **A key left blank:** *This value should not be blank.*, under that field.
- **A changed key on edit:** *This value cannot be changed.*, and the stored key is put back.

These are Symfony validator messages, translated through the `validators` domain. If two users create the same key at the same moment, the second one gets a database error.

## Replicating records

`ReplicateAction` never copies identifier fields. Give it a form with the key fields:

```php
ReplicateAction::make()->schema([
    TextInput::make('path')->required(),
]);
```

A key set in `beforeReplicaSaved()` is not checked for clashes.

## Relation managers

Attach, detach, associate and dissociate work for composite targets and owners. Associate and dissociate are refused when the relationship's foreign key is part of the target's identifier, because moving the record would change its key. Use `DeleteAction` instead.

## Custom URLs

Override `getRecordRouteKey()` and `resolveRecordRouteBinding()` on the resource to use a slug or another key. `$recordRouteKeyName` works only for a single unique column.

## Custom bulk actions

`$selectedRecords` reads a large composite selection in batches of 500 keys per statement, or the keys alone under `fetchSelectedRecords(false)`. `$selectedRecordsQuery` binds every selected key in one statement, so prefer the records or keys for large composite selections.

## Entities with constructor arguments

Sylament creates new records without calling a constructor with arguments. An entity whose constructor requires its key fails with an error naming the class. Give the constructor defaults, or build the record yourself: `CreateAction::using()` replaces the create step, and `Select::createOptionUsing()` replaces the create-option step. The resource create page has no such hook yet.

## Case-insensitive databases

Under a case- or accent-insensitive collation, such as MySQL's default `utf8mb4_0900_ai_ci`, the database treats `Example.com` and `example.com` as the same key. A lookup by either spelling finds the same row, and the check for a key already in use sees it. If two new records that differ only by case or accents are saved in one form, the database refuses the second one. The form does not.
