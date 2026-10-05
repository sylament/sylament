# Composite keys

Sylament works with Doctrine entities whose identifier has more than one field, or includes an association. Tables, resources, relation managers, forms and actions treat them like entities with a single `id`.

## Supported identifiers

- Several scalar `#[ORM\Id]` fields: strings, integers, big integers, GUIDs and UUIDs, backed enums, and custom types that read back what they print — the value the type's writer stores reads back, through its reader, as the value the writer was given.
- An `#[ORM\Id]` association plus scalar fields, for example a translation keyed by its article and a locale.
- Two or more `#[ORM\Id]` associations, for example a membership keyed by its user and its group (a join entity with a payload).
- A single `#[ORM\Id]` association (a shared primary key).

Doctrine cannot persist or find an entity whose identifier field holds a date or time, and refuses to map an entity with a composite key inside another entity's identifier, so Sylament cannot offer them either.

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
- A key value set outside the form, for example in `mutateFormDataBeforeCreate()`, must be mapped by a field: a hook that adds a key no field maps fails the save with an error naming it. Use a `Hidden` field for a key the user should not type, with `required(false)` where the entity would make it required, or build the record with its key in `makeRecord()` (see [Entities with constructor arguments](#entities-with-constructor-arguments)).
- A key field the form leaves out and nothing fills before the save — a `prePersist` listener may — fails the save with an error naming the class. It is a mistake in the form, not something the user can fix.
- Leave a key field that a `prePersist` callback or listener fills out of the form. A key field the form shows is the user's to fill, and leaving it blank is refused under it, whatever a callback would do.
- Override `getRecordTitle()` on the resource, since no single `$recordTitleAttribute` names a record with a composite key.

## What users see on create and edit

- **A key another record already uses:** *This value is already used.*, under every key field of the form that is not an association — the host and the path of a key made of two strings. When the form maps none of them, the message is shown above the form, as an error of the form itself. This also finds rows that a Doctrine SQL filter, such as soft delete, hides.
- **A key left blank:** *This value should not be blank.*, under that field.
- **A changed key on edit:** *This value cannot be changed.*, and the stored key is put back.

The first two messages are Symfony's own, translated through the `validators` domain. *This value cannot be changed.* is Sylament's: Sylament ships no translation of it, and your application's `validators` catalog translates it, in a form and in an editable table cell alike. To word the taken-key message differently, translate it in that catalog, or declare `#[UniqueEntity(fields: ['host', 'path'], message: '…', errorPath: 'path')]` on the entity, which is checked first.

`Schema::validateRecordKey(false)` — on one form, or on every schema through `Schema::configureUsing()` — switches the first two checks off: a taken key then fails in the database. A blank key field over a non-nullable property is still refused under the field, since the field is required on its own. One that is not required — it declares `required(false)`, or its property is nullable — is not, and what happens then depends on what the blank reaches the record as. Whitespace reaches it as a string and fails the save with an error naming the class, which ends: *The schema switches the key rules off with validateRecordKey(false), so a key field it maps is not refused while blank; switch them back on to have the blank field refused under it.* An empty field becomes `null`: over a nullable property Doctrine refuses it inside `persist()` with its own `EntityMissingAssignedId` (*Entity of type … is missing an assigned ID for field '…'*), and over a non-nullable property the save fails earlier, at the mapping, with Symfony's `InvalidTypeException`, since `null` cannot be written there. A `Select`'s create-option action refuses the `null` with the error above before `persist()` unless the class declares a `prePersist` callback or entity listener, when Doctrine's message is what appears. A `prePersist` listener that fills the key saves the record either way. A changed key is still refused. If two users create the same key at the same moment, the second one gets a database error.

## Replicating records

`ReplicateAction` never copies a scalar identifier field; an identifier association is kept unless it is the whole key. Give it a form with the key fields:

```php
ReplicateAction::make()->schema([
    TextInput::make('host')->required(),
    TextInput::make('path')->required(),
]);
```

A key made of an association plus scalars, such as a translation, needs only its scalar fields (`locale`), because the association is kept. A key made of two or more associations is copied whole, so the replicate is refused as a taken key unless a form or `beforeReplicaSaved()` gives the copy a new one.

A key set in `beforeReplicaSaved()` is checked for clashes too: with a form, a taken key is *This value is already used.*; without one, the replicate is refused with an error naming the fix. A key a `prePersist` callback or listener sets is not checked, and a taken one fails inside Doctrine.

## Relation managers

Attach, detach, associate and dissociate work for composite targets and owners. Associate and dissociate are refused when the relationship's foreign key is part of the target's identifier, because moving the record would change its key. Use `DeleteAction` instead.

## Custom URLs

Override `getRecordRouteKey()` and `resolveRecordRouteBinding()` on the resource to use a slug or another key. `$recordRouteKeyName` works only for a single unique column. Naming one field of a composite key, an association, or anything the record class does not map as a field is refused on first use (the first row it keys or URL it binds).

## Custom bulk actions

`$selectedRecords` reads a large composite selection in batches of at most 500 keys per statement (500 for two to four identifier fields, fewer above that), or the keys alone under `fetchSelectedRecords(false)`. `$selectedRecordsQuery` binds every selected key in one statement, so prefer the records or keys for large composite selections.

## Entities with constructor arguments

Sylament creates new records without arguments, so an entity whose constructor requires them fails with an error naming the class until you build it yourself. On a resource create page, override `makeRecord()` and construct the record from `$data`: the form's state after `mutateFormDataBeforeCreate()` — every field the form maps, validated, plus any other key the browser sent in `data`, unvalidated. Read only keys a field of the form maps. The form is then mapped onto the record you return, which must be an instance of the resource's record class:

```php
final class CreateSiteRedirect extends CreateRecord
{
    protected static string $resource = SiteRedirectResource::class;

    protected function makeRecord(array $data): SiteRedirect
    {
        return new SiteRedirect($data['host'], $data['path']);
    }
}
```

`$data` holds `mixed` values, so a real page narrows `host` and `path` to strings first, as its static analysis wants; the demo's `CreateSiteRedirect` checks both with `is_string()` and throws otherwise.

Elsewhere, `CreateAction::using()` replaces the create step, and `Select::createOptionUsing()` replaces the create-option step.

A key the constructor sets and no setter can change — a `readonly` property, or one with a getter and no setter — is left alone when the form writes the value it already holds, and refused with an error naming the field when it does not, for example when the constructor lowercases the host. "The value it already holds" is strict for a bool, for two strings (`'7'` over a held `'007'` is another value) and for an entity mapped with Doctrine attributes (the same instance); a blank field matches only a held `null` or `''`; a numeric string matches its number (`'5'` over a held `5`); two arrays match element by element under the same rule; and two dates or value objects of one class match when they compare equal. Hand the constructor the form's value as it is, make the field's state match with `dehydrateStateUsing()`, or leave the field out of the form. The property must be readable, through a getter or public visibility; one that is neither still fails with Symfony's `NoSuchPropertyException`. On the edit page an unchanged such key saves, and a changed one is refused with that error rather than with *This value cannot be changed.*; `disabledOn('edit')` avoids both.

- Never `hiddenOn('create')` a key field the constructor sets: a taken key's error is filed under that field too, so when every key field is hidden the save is refused with no message. A `disabledOn('create')` field shows the error under a field the user cannot change.
- Compute any value only `makeRecord()` reads inside it: the check for values no field maps runs first, so `mutateFormDataBeforeCreate()` cannot hand it a key no field maps.
- `ReplicateAction` cannot copy a key no setter can change. Doctrine builds the copy without its constructor, so the property holds no value, and the replicate is refused with an error naming it.

## Case-insensitive databases

Under a case- or accent-insensitive collation, such as MySQL's default `utf8mb4_0900_ai_ci`, the database treats `Example.com` and `example.com` as the same key. A lookup by either spelling finds the same row, and the check for a key already in use sees it. If two new records that differ only by case or accents are saved in one form, the database refuses the second one. The form does not.
