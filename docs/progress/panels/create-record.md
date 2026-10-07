---
description: 'The resource''s create page: the resource form bound to the record class, persisted, then redirected.'
ports:
  - vendor/filament/filament/src/Resources/Pages/CreateRecord.php
owns:
  - packages/panels/src/Resources/Pages/CreateRecord.php
---
# CreateRecord (panels)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Resources\Pages\CreateRecord`](../../../packages/panels/src/Resources/Pages/CreateRecord.php) | [`Resources/Pages/CreateRecord.php`](../../../vendor/filament/filament/src/Resources/Pages/CreateRecord.php) | in progress | — | — |
| [Page form variant](../../../packages/panels/templates/partials/Page/form.html.twig) | no Filament counterpart — v5's create and edit pages have no view of their own; [`CreateRecord::content()`](../../../vendor/filament/filament/src/Resources/Pages/CreateRecord.php) and [`EditRecord::content()`](../../../vendor/filament/filament/src/Resources/Pages/EditRecord.php) return a schema holding a `Form` wrapping an `EmbeddedSchema` and the form `Actions` | in progress | — | — |
| _rest not audited yet_ | — | — | — | — |

## Details

### The create page binds the resource form to the record class, persists and redirects

The resource's create page: a live component hosting the resource's `form()` schema, bound to the record **class** rather than an instance, that persists and redirects.

```php
abstract class CreateRecord extends Page
{
    use HasUnsavedDataChangesAlert;   // Sylament\Panels\Pages\Concerns
}
```

Both hosts come from the plain [`Pages\Page`](page.md) base, so the one trait this page composes of its own is [the unsaved-changes marker](unsaved-changes-alert.md).
Exactly one `LiveProp` of its own — `$data`, writable, the state bag every field binds into — and **none** for the record: `protected ?object $record` is a plain property, set by `create()` and read by `getRedirectUrl()` a few lines later, which is its whole life.
`#[Required] setRecordPersistence(EntityManagerInterface)` is the persistence hop Filament does not need, because an Eloquent model saves itself; it is a setter rather than a constructor argument so a subclass declaring its own constructor never repeats it, and a save that finds it unset throws the same container-registration `LogicException` [`getResourceInstance()`](resource-pages.md) throws, before anything is mapped.
`EditRecord` declares that setter again rather than sharing a trait — two classes, and Filament keeps its record handlers per page too.

- `mount(array $parentRecords = [])` assigns the ancestor keys first — the [chain is a mount argument everywhere](nested-resources.md#the-chain-rides-one-liveprop-of-keys-assigned-inside-mount) — then calls `fillForm()`: `callHook('beforeFill')`, `$this->getSchema('form')->fill()` and `callHook('afterFill')`.
  `fillForm()` is Filament's own method name, kept as a method of its own — mirroring [`EditRecord`'s](edit-record.md) — so a page that mutates or hooks on fill has one place to reach.
  The fill takes no argument, so `Schema::fill(null)` hydrates with `applyDefaults: true` and every component's `default()` lands; there is no fill mutator here, because Filament has none on its create page either.
- `form(Schema)` stamps `statePath('data')`, `record($resource->getRecordClass())` — the **class-string** arm of `BelongsToRecord`, so nothing is built before the save, where `makeRecord()` builds the record the form is then bound to — and `operation(Operation::Create->value)`, then hands the schema to `$resource->form()`.
  The schema is named `form`, so `HasSchemas::getSchema('form')` resolves this very method and the template says `renderSchema('form')`.
- `schemas()` returns `['form']`, replacing the trait's `['schema']` default: the de/hydration hooks walk exactly this list, and naming a schema that has no method throws on the first render.
- `getMountedActionSchemaRecord()` answers the resource's record **class**, so a header action's modal is seeded with the class rather than the record this page has not created yet — redundantly, since the seed chain reaches `getDefaultActionRecordClass()` first and never asks ([why it is kept](#the-mounted-schemas-record-answers-for-the-top-frame-and-depends-on-the-seeds-laziness)).
- `create()` is the `#[LiveAction]` the form's submit posts.

The lifecycle, ours against Filament's:

| Step | Filament `create()` | Ours |
| --- | --- | --- |
| re-entry guard | `$this->isCreating` | — (no SPA history restore to guard) |
| authorization | `authorizeAccess()` | — deferred with the auth cluster |
| transaction | `beginDatabaseTransaction()` | — deferred |
| validate | `callHook('beforeValidate')` → `$this->form->getState()` → `callHook('afterValidate')` | the same three steps, over `$this->getSchema('form')` |
| mutate | `mutateFormDataBeforeCreate($data)` | same, an identity default a page overrides — then a path it adds that no component maps is refused by name |
| write | `callHook('beforeCreate')` → `handleRecordCreation($data)` → `saveRelationships()` → `callHook('afterCreate')` | `callHook('beforeCreate')` → `handleRecordCreation($this->mapRecordForCreation($schema, $data))`, the mapping onto the record `makeRecord($data)` builds → `callHook('afterCreate')` — `persist()` + `flush()`, no relationship pass |
| events | `RecordCreated`, `RecordSaved` | — no event layer |
| remember | `rememberData()` | same, through [`HasUnsavedDataChangesAlert`](../../../packages/panels/src/Pages/Concerns/HasUnsavedDataChangesAlert.php) |
| notify | `getCreatedNotification()?->send()` | same |
| create another | the `$another` branch | — deferred |
| redirect | `$this->redirect($this->getRedirectUrl())` | `return new RedirectResponse($this->getRedirectUrl())` |

Every step from `beforeValidate` to the write runs inside a `try { … } catch (Halt) { return null; }`, so a lifecycle hook calling [`halt()`](custom-pages.md#halts-rollback-flag-is-carried-and-ignored) stops the ladder where it threw — no mutator, no write, no notification and no redirect.
Without that catch a `Halt` from a hook would escape the action and answer 500.

Three things about that table are load-bearing.
**Validation is the `getState()` step and mapping is the `getData($data)` step**, and the split is what makes the mutator possible: `getState()` opens with `validate()`, so a failing submit throws `ValidationException` (a 422 `UnprocessableEntityHttpException`) before the mutator, the write hook or anything else runs — there is no `try`/`catch`, because ux-live-component's `LiveComponentSubscriber` re-renders the mutated component for exactly that status and the client morphs the errors in — and `Schema::getData($data)` then maps that already-validated, already-mutated array onto a fresh record.
Handing `getData()` the array rather than letting it read state back is [the schemas seam](../schemas/belongs-to-record.md) that exists for exactly this.
**`handleRecordCreation()` takes the record, not an array** — the Filament signature is `(array $data): Model` because Eloquent mass-assigns, while our `getData()` already returns the mapped object, so the hook's job shrinks to persisting; the name and the override role are kept.
And **the notification is sent before the redirect**, so it lands in the flash bag and the `SylamentNotifications:Notifications` outlet in the [document](routing.md) drains it on the page the browser lands on.

**The record is built in `makeRecord(array $data): object`.**
The create page asks one protected hook for the new record its form is mapped onto, handing it the form's state after `mutateFormDataBeforeCreate()`: every field the form maps, validated, plus any other key the browser sent in `data`, unvalidated — so an override reads only keys a field of the form maps.
It calls it once per create, with a parent or without.
The default builds the resource's record class with no arguments through the schemas mapper's `constructRecord()`, so a class whose constructor requires arguments is refused by name — *Cannot map a new record on the resource page [App\Admin\CreateRedirect]: App\Entity\Redirect requires constructor arguments, so the mapper cannot build it.
Give the constructor defaults, or override makeRecord() to build it from the form data.* — and the schema tier's "through `record()`" advice, which no page could act on, no longer reaches it.
An override calls the constructor the way it wants (`new Redirect($data['host'], $data['path'])`).
What it answers must be an instance of the resource's record class: anything else — the parent record, a draft object — is refused by name before the attach and before the mapping, so nothing is written onto it.
A key the constructor sets and the form maps then meets the mapper's [rule for a property no setter can change](../schemas/property-accessor.md#a-property-no-setter-can-change-is-left-alone-when-it-already-holds-the-value): left alone when it already holds the value the form writes, refused by name when the constructor kept it another way (`strtolower()`), whose way out is `dehydrateStateUsing()` on the field.
Filament's page has no such member ([recorded](#the-create-page-builds-its-record-in-makerecord-which-filaments-page-never-needs)).

Two things to get right on such a page.
**Never `hiddenOn('create')` a key field the constructor sets**: a taken key is filed under every key field the form maps, and a hidden field renders nowhere, so when every mapped key field is hidden the save is refused with no message at all, and when one half is hidden the message shows under the other half only; a `disabledOn('create')` one shows it under a field the user cannot change.
**Give such a key a getter or make it public**: the rule sees only a property it can read, so a `private readonly` one with neither is still Symfony's `NoSuchPropertyException`.
Pinned by [`MakeRecordTest`](../../../packages/panels/tests/Integration/Resources/Pages/MakeRecordTest.php), over a two-field key the constructor sets and lower-cases, and over a nested derived identity whose constructor takes its parent ([nested-resources.md](nested-resources.md#a-child-whose-constructor-takes-its-parent-is-built-in-makerecord-and-both-attaches-leave-that-parent-alone)).

**A path the mutator adds that no component maps is refused.**
Right after `mutateFormDataBeforeCreate()`, before the `beforeCreate` hook and `makeRecord()`, `create()` asks the form's [`findUnmappedAdditions()`](../schemas/belongs-to-record.md) for every leaf path the hook added to the state it was handed that no component maps, and refuses any with a `LogicException`: *The resource page [App\Admin\CreateArticle] added [status] in mutateFormDataBeforeCreate(), but no component of its form maps those paths, so the default write would drop them.
Map each with a component — a Hidden one for a value the user should not type, with required(false) where the record would otherwise require it — or set it on the record in makeRecord() or handleRecordCreation().*
Without the refusal such a path would be dropped while the page reported *Created*.
A key the client typed into `data` is already in the state the hook was handed, so it is never refused — and never written; an overwritten mapped key, and a key a hidden or non-dehydrated component maps, save as before.
Because the check runs before `makeRecord()`, a value only `makeRecord()` reads must be computed there: a mutator cannot hand it a key no component maps.
[Recorded as a divergence](#a-value-a-mutate-hook-adds-that-no-field-maps-is-refused-where-filaments-fill-keeps-or-drops-it); pinned by [`RecordPageHookDataTest`](../../../packages/panels/tests/Integration/Resources/Pages/RecordPageHookDataTest.php).

`getRedirectUrl()` is the `edit` page when the resource has one, else the index — Filament's chain with the panel-level `getResourceCreatePageRedirect()` setting, the `view` arm and the two `can*()` guards all absent with their features.
Both arms spread the page's `parentRecords`, so a nested create lands on the nested edit page rather than raising a missing-parameter exception.
The key the `edit` arm mints is the one the new row reads back ([record-key.md](../schemas/record-key.md#a-key-is-minted-from-the-value-its-row-would-read-back)): printing the value the record holds in memory instead would send a create whose key type rewrites the typed spelling — `new/1` read back as `NEW/1` — to a URL the strict decode answers 404 for, the record existing under its canonical key.

`handleRecordCreation()` has a parent arm: with a parent record in hand it calls [`Resource::attachRecordToParent()`](nested-resources.md#the-attach-lands-on-the-resource-and-writes-the-owning-side-in-memory) before the `persist()`/`flush()` pair, so the new record joins the parent's association in the same flush — an association the mapping refuses to leave empty therefore fails the form rather than the write.
A **modal** create on a nested page reaches the same place from the other side: the resource-page base answers `getDefaultActionRelationship()` with the parent relationship for a `CreateAction`, which is the hook [`CreateAction`](../actions/create-action.md) already consulted.

The parent is joined **before** the mapping as well.
`create()` maps through a private `mapRecordForCreation()`, which takes the record `makeRecord()` built, attaches it under a parent through `attachRecordToParent()` and maps with the form bound to that record, restoring the binding in a `finally` so a refused create re-renders exactly as before — the `hint` field of `MakeRecordTest`'s page and of `CreateRecordTakenKeyTest`'s nested one, shown only while the form is bound to no record, is what proves the binding came back.
The arm with no parent maps with the form bound to the new record too, so a closure taking `$record` sees that record while the form is mapped rather than `null`.
That is what lets the record check that ends the mapping see a key half the parent fills: a create on a taken key — nested or not, over any identifier width, a single assigned one included — is *"This value is already used."* under every key field the form maps that is no association, rather than a unique-violation 500 ([the rule](../schemas/can-be-validated.md#a-taken-assigned-key-is-a-field-error-not-a-unique-violation)).
`handleRecordCreation()`'s own attach is the *after*, so the parent still wins over a form that picks another — and the lookup runs on the form's pick, [recorded with its modal twin](../actions/create-action.md#a-create-whose-form-picks-another-owner-still-lands-under-the-relationship-and-is-probed-on-the-pick).
Pinned by [`CreateRecordTakenKeyTest`](../../../packages/panels/tests/Integration/Resources/Pages/CreateRecordTakenKeyTest.php) and [`CompositeRecordPagesTest`](../../../packages/panels/tests/Integration/Resources/Pages/CompositeRecordPagesTest.php), whose taken-key case flipped in place from pinning the unique violation.
Like [`CreateAction`](../actions/create-action.md#the-created-record-joins-the-relationship-in-the-same-flush), a refused nested create takes the record back out of a many-to-many parent's collection: `mapRecordForCreation()` hands it back in a `finally` when the mapping refuses it, and the `ValidationException` leaves unchanged.
Left there, it would stay for the rest of the request, so a later flush in that request — an exception listener's, or one in a `create()` override that caught the refusal — would fail with Doctrine's *A new entity was found through the relationship …*.
Any other parent holds nothing of the record and is left alone — unless the child's setter writes it into the parent's collection itself, which the hand-back does not undo.

The default `handleRecordCreation()` also refuses by name a record whose assigned key is still blank after `persist()`.
It asks [`AssociationReader::hasBlankAssignedIdentifier()`](../schemas/association-reader.md#a-blank-assigned-identifier-is-read-after-persist-where-a-record-is-created) on the page's own entity manager, between `persist()` and `flush()`, so every `prePersist` listener has run.
What it catches is a key field the form leaves out; a blank one the form maps is already a field error ([the rule](../schemas/can-be-validated.md#a-blank-assigned-key-is-refused-before-anything-is-written)) — unless the form switches the key rules off with [`validateRecordKey(false)`](../schemas/can-be-validated.md), when a blank mapped key whose field is not required reaches this refusal too, provided the blank reaches the record as a **string** — whitespace.
An empty field dehydrates to null instead, which never gets here: over a nullable property Doctrine refuses it inside `persist()` with its own `EntityMissingAssignedId`, and over a non-nullable one the mapping refuses it first with Symfony's `InvalidTypeException` ([the null arm](../schemas/association-reader.md#a-blank-assigned-identifier-is-read-after-persist-where-a-record-is-created)).
A key field over a non-nullable property [is required on its own](../forms/can-be-validated.md#requirement-is-derived-from-the-records-write-side-types), so until it says `required(false)` a blank in it is refused under the field first, whatever the key rules say, and the `InvalidTypeException` arm is reached only on a field that says it.
When the rules are off, the refusal's message closes with a sentence saying so: *The schema switches the key rules off with validateRecordKey(false), so a key field it maps is not refused while blank; switch them back on to have the blank field refused under it.*
With the rules on, the message is unchanged.
Pinned by [`CreateRecordKeyAdviceTest`](../../../packages/panels/tests/Integration/Resources/Pages/CreateRecordKeyAdviceTest.php).
It detaches the record from the manager and throws a `LogicException`: *The resource page [App\Admin\CreateEdition] built a [App\Entity\Edition] whose identifier is still blank after persist(), so it was not saved.
Give every identifier field a form field — a Hidden one for a value the user should not type — or fill it in a prePersist listener, in makeRecord(), or in handleRecordCreation() before calling the parent.*
Nothing is flushed.
One record cannot be detached: a single derived identity whose target's string key is still `''` flattens to an identity hash of `''`, which Doctrine can neither detach nor remove, so the page swallows that `detach()` failure and throws its own message unchanged rather than failing with Doctrine's *Entity has no identity, therefore remove from identity map cannot be performed*; that record stays scheduled in the unit of work for the rest of the request, and nothing is written, since nothing flushes after the refusal (pinned by [`CreateRecordBlankIdentityHashTest`](../../../packages/panels/tests/Integration/Resources/Pages/CreateRecordBlankIdentityHashTest.php)).
An override that sets the key before calling the parent passes, since the check reads the record the parent persists.
Under a many-to-many parent the record is handed back out of the parent's collection whenever `persist()` or this read fails — Doctrine's own `EntityMissingAssignedId` for a key half still null included, rethrown unchanged — as with the mapping's own refusal above.
Pinned by [`CreateRecordParentHandBackTest`](../../../packages/panels/tests/Integration/Resources/Pages/CreateRecordParentHandBackTest.php), over a composite record under a many-to-many parent, refused at the mapping, at this read and inside `persist()`.
Without the refusal the row would be written with its blank key half and the redirect would fail to mint its key.
Pinned by [`CompositeRecordPagesTest`](../../../packages/panels/tests/Integration/Resources/Pages/CompositeRecordPagesTest.php), which also pins that a global `prePersist` listener filling the key half lets the create save.

**Both key reads ask the form's own reader, bound to the page's manager.**
Before the mapping, `mapRecordForCreation()` sets the form's association reader to a clone of itself bound to the manager `setRecordPersistence()` injected — [`withEntityManager()`](../schemas/association-reader.md) — so the record check that ends `getData()` reads the unit of work and the database the flush writes to, and `handleRecordCreation()`'s blank-key read asks the form's reader rebound the same way rather than a `new AssociationReader($entityManager)` of its own.
So a reader service an application subclassed or decorated is the one both reads ask — a decorator that delegates must override `withEntityManager()` to rebind what it delegates to — and a page that overrides `setRecordPersistence()` with another manager is checked against that manager, where the check used to read the container's default while the flush wrote elsewhere.
The bound reader is not put back afterwards, as `CreateAction`'s is not: a later read in the request asks a clone bound to the same manager.
Pinned by [`RecordPageReaderTest`](../../../packages/panels/tests/Integration/Resources/Pages/RecordPageReaderTest.php), which hands the page a second manager and a subclassed reader.

**The template.**
`$template` points at the shared [form variant](form-actions.md), `partials/Page/form.html.twig`, which fills the chrome's `content` block with a `<form class="sy-pa-form">` bound by `live_action(this.getSubmitFormLiveComponentMethodName ~ ':prevent', event: 'submit')`, the record-level error outlet, `renderSchema('form')` and the [form actions row](form-actions.md#the-row-is-never-rendered-standalone-it-hangs-under-a-named-page-schema).
The page root, the [three-class page skeleton](routing.md), the `Header` and the `<twig:SylamentActions:Modals />` outlet are the chrome's, and the root classes come from `getPageClasses()` (`sy-pa-resource-create-record-page` plus the slug-derived `sy-pa-resource-<slug>`).
The **record-level error outlet is required, not decorative**: a class-level violation that names no field (a `UniqueEntity` on the entity) is translated onto the schema's own state path, which belongs to no input, so without `this.getErrorsForSchemaStatePath('data')` it renders nowhere and the submit appears to do nothing.
The demo hand-rolled this block for the same reason; this is the shipped version of it, in one file instead of three, wrapped in a `form_errors` block a page can replace.
One of the key rules lands there too — a key a hook moved on a related record the form never maps is refused at the schema's own path — and actions' modal carries the twin, [its own record-error list](../actions/can-open-modal.md#the-action-modal-renders-the-record-errors-that-name-no-field).

Deferred, all todo: page-level create-another (`$canCreateAnother`, `createAnother()`, `preserveFormDataWhenCreatingAnother()` — the [`CreateAction` modal](../actions/create-action.md) has its own, this is the page's submit-and-stay button), database transactions (`CanUseDatabaseTransactions`, which [`halt()`'s carried rollback flag waits on](custom-pages.md#halts-rollback-flag-is-carried-and-ignored)), `previousUrl`, the record events, the authorization arms, and `content()` and its `Form`/`EmbeddedSchema` wrapper.
The parent-record association ships, and the partial carries [the sub-navigation band](has-sub-navigation.md); the page's partial is the shared [form variant](form-actions.md), with the Create and Cancel buttons declared as real `Action`s — the cancel one [linking to the listing rather than going back in history](form-actions.md#the-cancel-action-links-to-the-index-where-filament-goes-back-in-history).

Four divergences are shared with the edit page: [the notification titles are literals](#the-create-and-save-notification-titles-are-literals-not-translation-keys), [the schema is named `form`, not `schema`](#the-record-pages-schema-is-named-form-and-schemas-is-narrowed-to-it), [a path the mutator adds that no field maps is refused](#a-value-a-mutate-hook-adds-that-no-field-maps-is-refused-where-filaments-fill-keeps-or-drops-it), and [URL generation inside a live action resolves the default panel](resource.md#url-generation-inside-a-live-action-resolves-the-default-panel).
One is its own: [it builds its record in `makeRecord()`](#the-create-page-builds-its-record-in-makerecord-which-filaments-page-never-needs).

## Semantics

### The mounted schema's record answers for the top frame, and depends on the seed's laziness

`getMountedActionSchemaRecord()` takes no arguments.
It is the last fallback in [the host's seed chain](../actions/has-actions.md#the-per-mount-schema-is-seeded-with-the-actions-record-and-the-dotted-operation), consulted only when the action, its schema container and its schema component all answer nothing — and the chain runs **per frame**, while the hook is asked without being told which frame is asking.
Filament's is frame-agnostic in exactly the same way, so the port keeps it, and the edit page's override reads `getMountedAction()` with no index: the top frame.
Under a nested modal that is the inner action, not the one that opened it.
Nothing today depends on the distinction, and recording it is cheaper than re-deriving it the first time something does.

On every resource page the port ships today, that override is in fact **dead code**.
`resolveMountedActionSchemaRecord()` consults the host hook last, after `$action->getRecordClass(withDefault: true)` — and `withDefault` reaches `Page::getDefaultActionRecordClass()`, which always answers the resource's record class.
So the chain returns one hop early and never asks the page.
All three overrides (`ListRecords`', `EditRecord`'s, `CreateRecord`'s) are therefore redundant rather than load-bearing, and they are kept on purpose: each answers what the chain would have answered anyway, so an action that *does* opt out of the default class — `record(null)`, a `schemaComponent`-owned frame, a future page whose resource declares none — lands on the same value instead of `null`.
Read the sections below as *what the hook says*, not as behaviour a shipped page exercises; nothing in the suite can distinguish them, which is why this paragraph exists rather than a test.

The override is only *able* to read the mounted action because the seed's record is a closure.
`getMountedActionSchema()` builds the schema with `->record(fn () => $this->resolveMountedActionSchemaRecord($mountedAction))` and caches it; the closure runs later, when the schema is first asked for its record.
Were the seed eager, resolving it would call `getMountedActionSchemaRecord()`, which calls `getMountedAction()`, which caches the whole mounted stack — from inside the very call that is building and caching a frame's schema.
The laziness is what keeps that from re-entering, and it is why this override is safe where an apparently equivalent eager one would not be.

## Divergences

### The create and save notification titles are literals, not translation keys

`getCreatedNotificationTitle()` returns `'Created'` and `getSavedNotificationTitle()` returns `'Saved'`, where Filament returns `__('filament-panels::resources/pages/create-record.notifications.created.title')` and its edit-page twin.
Same reason [the dashboard title is a literal](dashboard.md#the-dashboard-title-is-a-literal-not-a-translation-key): there is no translation layer in the port yet, and a `trans()` call against a catalogue nobody ships would render the key.
The **override points** are what a consumer needs and they are Filament's exactly — `getCreatedNotificationTitle()` / `getSavedNotificationTitle()` for the copy, `getCreatedNotification()` / `getSavedNotification()` for the whole notification — and each returns `null` when its title is `blank()`, which is Filament's own "no title, no toast" contract.
Filament's deprecated `get*NotificationMessage()` pair is not ported; it exists only to keep pre-v3 overrides working.

When translations land these become `trans()` calls and nothing else changes.

### The record page's schema is named `form`, and `schemas()` is narrowed to it

`HasSchemas` defaults to one schema named `schema`, discovered through a `schema()` or `schemaSchema()` method.
Both record pages instead declare `form(Schema): Schema` and `schemas(): ['form']`, so every read is `getSchema('form')` and the template says `renderSchema('form')`.

That is Filament's name — its pages carry a `form` schema and a `@property-read Schema $form` — and it is what makes room for the schemas a record page will grow (`infolist` on a view page, one per relation manager).
The narrowing of `schemas()` is not optional: the list is what the de/hydration hooks walk, so leaving the default in place would demand a `schema()` method the page does not have and throw `RuntimeException` on the first render — the same trap [`ListRecords::schemas()` returning `[]`](list-records.md) documents.

The cost is that `HasSchemas::fill()` — the trait's convenience wrapper, hardcoded to the default schema — is not usable on these pages; they call `$this->getSchema('form')->fill(…)` explicitly, which is what a multi-schema host does anyway.

### The create page builds its record in `makeRecord()`, which Filament's page never needs

Filament's `CreateRecord::handleRecordCreation(array $data): Model` builds and saves in one step, `new ($this->getModel())($data)`, because an Eloquent model takes any attribute array in its constructor.
Ours keeps [`handleRecordCreation()` taking the mapped record](#the-create-page-binds-the-resource-form-to-the-record-class-persists-and-redirects) — the parent attach and the record check sit on the record the mapping produced — so construction needed a seam of its own: `makeRecord(array $data): object`, called once, before the attach and the mapping.
The name is borrowed from Filament's `TranslatableContentDriver::makeRecord(string $model, array $data)`, which answers the same question — an unsaved record from form data — on a contract the page never reaches; the [renames table](../renames.md) carries the row.
Two refusals come with it that Filament has no need for: a default that cannot build the class names the hook, and an answer that is no instance of the resource's record class is refused before anything is mapped onto it.
`CreateAction` gains no such member, as Filament's has none: `using()` is its seam.

### A value a mutate hook adds that no field maps is refused, where Filament's fill keeps or drops it

Filament's create page builds the model with `new ($this->getModel())($data)` and its edit page calls `$record->update($data)`, so a key `mutateFormDataBeforeCreate()` or `mutateFormDataBeforeSave()` adds goes through Eloquent's `fill()`: written when the attribute is fillable, dropped in silence when `$fillable` is declared without it, thrown under `preventSilentlyDiscardingAttributes()`.
Ours maps through `Schema::getData($data)`, which writes only the paths a component maps — the record path map plays `$fillable` — so such a key would be dropped with nothing said, the page reporting *Created* or *Saved*.
Both pages instead refuse it by name right after the hook, before the `beforeCreate` hook, `makeRecord()` or any write: a `LogicException` naming the page, the paths and the ways out — map each path with a component (a `Hidden` one for a value the user should not type, with `required(false)` where the record would otherwise require it), or set the value on the record in `makeRecord()` or `handleRecordCreation()` (create) or `handleRecordUpdate()` (edit).
That is Eloquent's `preventSilentlyDiscardingAttributes()` branch, always on, and limited to what the **hook** added: the state the hook was handed is the baseline, and a key the client typed into the writable `data` prop is already in it, so no request can force the refusal — and that key is still never written, since writing state no component declares would be mass assignment.
Overwriting a mapped key is unchanged, and a key a hidden or non-dehydrated component maps is written, since the map carries it.
The query is the schema's [`findUnmappedAdditions()`](../schemas/belongs-to-record.md); the record actions and `Select`'s option forms refuse the same way, in the same words.
