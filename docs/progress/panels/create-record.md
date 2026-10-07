---
description: The resource's create page, which binds the resource form to the record class, builds and persists the record, then redirects.
ports:
  - vendor/filament/filament/src/Resources/Pages/CreateRecord.php
owns:
  - packages/panels/src/Resources/Pages/CreateRecord.php
---
# CreateRecord (panels)

`Sylament\Panels\Resources\Pages\CreateRecord` extends the [resource-page base](resource-pages.md) and composes [the unsaved-changes marker](unsaved-changes-alert.md); its form row is [form-actions.md](form-actions.md)'s.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`CreateRecord`](../../../packages/panels/src/Resources/Pages/CreateRecord.php) — `$data` and the plain `$record` | [`Resources/Pages/CreateRecord.php`](../../../vendor/filament/filament/src/Resources/Pages/CreateRecord.php) | done | — | [diverges](#the-page-holds-its-record-in-a-plain-property-and-writes-through-an-injected-entity-manager) |
| [`setRecordPersistence()`](../../../packages/panels/src/Resources/Pages/CreateRecord.php) | no Filament counterpart — an Eloquent model saves itself | done | — | [diverges](#the-page-holds-its-record-in-a-plain-property-and-writes-through-an-injected-entity-manager) |
| [`mount()`](../../../packages/panels/src/Resources/Pages/CreateRecord.php) | `mount()` | done | — | [nested-resources.md](nested-resources.md#the-chain-rides-one-liveprop-of-keys-assigned-inside-mount) |
| [`fillForm()`](../../../packages/panels/src/Resources/Pages/CreateRecord.php) | `fillForm()` | done | — | — |
| [`form()`](../../../packages/panels/src/Resources/Pages/CreateRecord.php) + [`schemas()`](../../../packages/panels/src/Resources/Pages/CreateRecord.php) | `form()` + `defaultForm()` | done | — | [diverges](#the-record-pages-schema-is-named-form-and-schemas-is-narrowed-to-it) · [list-records.md](list-records.md#a-modals-schema-keeps-the-resource-forms-own-column-count) |
| [`create()`](../../../packages/panels/src/Resources/Pages/CreateRecord.php) | `create()` | done | — | [semantics](#create-runs-filaments-ladder-in-filaments-order-and-maps-after-the-mutator) |
| `create()`'s `catch (Halt)` | the same catch | in progress | — | [detail](#no-test-halts-the-create-ladder) |
| `$isCreating` — the re-entry guard | `public bool $isCreating` | todo | — | — |
| [`mutateFormDataBeforeCreate()`](../../../packages/panels/src/Resources/Pages/CreateRecord.php) | the same | done | — | [diverges](#a-value-a-mutate-hook-adds-that-no-field-maps-is-refused-where-filaments-fill-keeps-or-drops-it) |
| [`makeRecord()`](../../../packages/panels/src/Resources/Pages/CreateRecord.php) | no counterpart on the page | done | — | [diverges](#the-create-page-builds-its-record-in-makerecord-which-filaments-page-never-needs) · [semantics](#a-key-field-the-constructor-sets-must-stay-visible-and-readable) |
| [`handleRecordCreation()`](../../../packages/panels/src/Resources/Pages/CreateRecord.php) | `handleRecordCreation(array $data): Model` | done | — | [diverges](#the-create-page-builds-its-record-in-makerecord-which-filaments-page-never-needs) · [semantics](#the-record-joins-its-parent-before-the-mapping-and-leaves-it-when-the-create-is-refused) · [semantics](#a-child-whose-constructor-takes-its-parent-is-built-in-makerecord-and-both-attaches-leave-that-parent-alone) · [semantics](#a-record-whose-assigned-key-is-still-blank-after-persist-is-refused-before-the-flush) · [semantics](#both-key-reads-ask-the-forms-own-reader-rebound-to-the-pages-manager) |
| [`getCreatedNotification()`](../../../packages/panels/src/Resources/Pages/CreateRecord.php) + [`getCreatedNotificationTitle()`](../../../packages/panels/src/Resources/Pages/CreateRecord.php) | the same two | done | — | [diverges](#the-create-and-save-notification-titles-are-literals-not-translation-keys) |
| `getCreatedNotificationMessage()` (`@deprecated` for `getCreatedNotificationTitle()`) | [`Resources/Pages/CreateRecord.php`](../../../vendor/filament/filament/src/Resources/Pages/CreateRecord.php) | skipped | — | [renames.md](../renames.md#a-deprecated-filament-member-is-not-ported) |
| [`getRedirectUrl()`](../../../packages/panels/src/Resources/Pages/CreateRecord.php) | `getRedirectUrl()` | done | — | [semantics](#the-redirect-mints-the-key-the-new-row-reads-back) |
| `getRedirectUrlParameters()` | the same | todo | — | — |
| [`getTitle()`](../../../packages/panels/src/Resources/Pages/CreateRecord.php) + [`getBreadcrumb()`](../../../packages/panels/src/Resources/Pages/CreateRecord.php) | the same two | done | — | [diverges](#the-create-and-save-notification-titles-are-literals-not-translation-keys) |
| [`getMountedActionSchemaRecord()`](../../../packages/panels/src/Resources/Pages/CreateRecord.php) | `getMountedActionSchemaModel()` | in progress | — | [detail](#no-test-reaches-the-mounted-schema-record-override) · [renames.md](../renames.md#the-model-vocabulary-collapses-onto-record) · [semantics](#the-mounted-schemas-record-answers-for-the-top-frame-and-depends-on-the-seeds-laziness) |
| [`getPageClasses()`](../../../packages/panels/src/Resources/Pages/CreateRecord.php) | the same | done | — | [custom-pages.md](custom-pages.md#getpageclasses-is-the-only-source-of-a-per-page-root-class) |
| `getSubmitFormAction()` | the same | todo | — | — |
| Create another — `$canCreateAnother`, `canCreateAnother()`, `disableCreateAnother()`, `createAnother()`, `preserveFormDataWhenCreatingAnother()`, `getCreateAnotherFormAction()` | the same six | todo | — | — |
| `RecordCreated` / `RecordSaved` | the two `Event::dispatch()` calls in `create()` | todo | — | — |
| `authorizeAccess()` / `hydrate()` | the same two | blocked | authorization | — |
| `content()` / `getFormContentComponent()` | the same two | blocked | schemas' `Form` component | [form-actions.md](form-actions.md#the-form-element-stays-in-twig-because-the-schemas-form-component-is-unported) |

## Details

### No test halts the create ladder

`create()` wraps every step from `beforeValidate` to the write in `try { … } catch (Halt) { return null; }`, so a lifecycle hook calling [`halt()`](custom-pages.md#halts-rollback-flag-is-carried-and-ignored) stops the ladder where it threw: no mutator, no write, no notification and no redirect.
What is missing is a test: only the edit page's arm is pinned, by `EditRecordTest`'s halted `beforeSave` hook, and no create fixture halts.

### No test reaches the mounted-schema record override

`getMountedActionSchemaRecord()` answers the resource's record class, which is what `getDefaultActionRecordClass()` would have answered, for a frame whose action a schema component owns and whose component carries neither a record nor a record class.
No shipped page builds such a frame, so no test in `packages/panels/tests/` calls the override; only the actions fixture `ActionSchemaHost` overrides the host's hook, and it is no resource page.

## Semantics

### `create()` runs Filament's ladder in Filament's order, and maps after the mutator

| Step | Filament `create()` | Ours |
| --- | --- | --- |
| re-entry guard | `$this->isCreating` | — (todo row) |
| authorization | `authorizeAccess()` | — (blocked row) |
| transaction | `beginDatabaseTransaction()` | — ([custom-pages.md](custom-pages.md#halts-rollback-flag-is-carried-and-ignored)) |
| validate | `callHook('beforeValidate')` → `$this->form->getState()` → `callHook('afterValidate')` | the same three steps, over `$this->getSchema('form')` |
| mutate | `mutateFormDataBeforeCreate($data)` | the same, then a path it adds that no component maps is refused by name |
| write | `callHook('beforeCreate')` → `handleRecordCreation($data)` → `saveRelationships()` → `callHook('afterCreate')` | `callHook('beforeCreate')` → `handleRecordCreation()` over the record `makeRecord()` builds and the form maps → `callHook('afterCreate')` |
| events | `RecordCreated`, `RecordSaved` | — (todo row) |
| remember | `rememberData()` | the same |
| notify | `getCreatedNotification()?->send()` | the same |
| create another | the `$another` branch | — (todo row) |
| redirect | `$this->redirect($this->getRedirectUrl())` | `return new RedirectResponse($this->getRedirectUrl())` |

Validation is the `getState()` step and mapping is a separate step after the mutator, and that split is what makes the mutator possible.
`getState()` opens with `validate()`, so a failing submit throws `ValidationException` (a 422 `UnprocessableEntityHttpException`) before the mutator or the write hook runs; there is no `try`/`catch` for it, because ux-live-component's `LiveComponentSubscriber` re-renders the component for exactly that status and the client morphs the errors in.
`Schema::getData($data)` then maps the already-validated, already-mutated array onto the new record, through [the schemas seam](../schemas/belongs-to-record.md) that takes the array rather than reading state back.
The notification is sent before the redirect, so it lands in the flash bag and the notifications outlet in the [document](routing.md) drains it on the page the browser lands on.

### The redirect mints the key the new row reads back

`getRedirectUrl()` answers the `edit` page when the resource has one, else the index; Filament's panel-level `getResourceCreatePageRedirect()` setting, its `view` arm and its two `can*()` guards are absent with their features.
Both arms spread the page's `parentRecords`, so a nested create lands on the nested edit page rather than raising a missing-parameter exception.
The key the `edit` arm mints is the one the new row reads back ([record-key.md](../schemas/record-key.md#a-key-is-minted-from-the-value-its-row-would-read-back)): printing the value the record holds in memory instead would send a create whose key type rewrites the typed spelling (`new/1` read back as `NEW/1`) to a URL the strict decode answers 404 for.

### A key field the constructor sets must stay visible and readable

A key the constructor of a [`makeRecord()`](#the-create-page-builds-its-record-in-makerecord-which-filaments-page-never-needs) override sets and the form maps meets the mapper's [rule for a property no setter can change](../schemas/property-accessor.md#a-property-no-setter-can-change-is-left-alone-when-it-already-holds-the-value): left alone when it already holds the value the form writes, refused by name when the constructor kept it another way (`strtolower()`), whose way out is `dehydrateStateUsing()` on the field.
Two consequences are easy to miss.
**Never `hiddenOn('create')` such a key field**: a taken key is filed under every key field the form maps, and a hidden field renders nowhere, so when every mapped key field is hidden the save is refused with no message at all, and when one half is hidden the message shows under the other half only; a `disabledOn('create')` one shows it under a field the user cannot change.
**Give such a key a getter or make it public**: the rule sees only a property it can read, so a `private readonly` one with neither is still Symfony's `NoSuchPropertyException`.
[`MakeRecordTest`](../../../packages/panels/tests/Integration/Resources/Pages/MakeRecordTest.php) pins both over a two-field key the constructor sets and lower-cases, and over a nested derived identity whose constructor takes its parent ([below](#a-child-whose-constructor-takes-its-parent-is-built-in-makerecord-and-both-attaches-leave-that-parent-alone)).

### The record joins its parent before the mapping, and leaves it when the create is refused

`create()` maps through a private `mapRecordForCreation()`, which takes the record `makeRecord()` built, attaches it under a parent through [`Resource::attachRecordToParent()`](nested-resources.md#the-attach-lands-on-the-resource-and-writes-the-owning-side-in-memory) and maps with the form bound to that record, restoring the previous binding in a `finally` so a refused create re-renders exactly as before.
The arm with no parent binds the form to the new record too, so a closure taking `$record` sees that record while the form is mapped rather than `null`.
That is what lets the record check that ends the mapping see a key half the parent fills: a create on a taken key, nested or not and over any identifier width, is *"This value is already used."* under every key field the form maps that is no association, rather than a unique-violation 500 ([the rule](../schemas/can-be-validated.md#a-taken-assigned-key-is-a-field-error-not-a-unique-violation)).
`handleRecordCreation()` attaches again before its `persist()`/`flush()` pair, so the parent still wins over a form that picks another, and the new record joins the parent's association in the same flush; the lookup runs on the form's pick, [recorded with its modal twin](../actions/create-action.md#a-create-whose-form-picks-another-owner-still-lands-under-the-relationship-and-is-probed-on-the-pick).
A **modal** create on a nested page reaches the same place from the other side: the resource-page base answers `getDefaultActionRelationship()` with the parent relationship for a [`CreateAction`](../actions/create-action.md).

Like [`CreateAction`](../actions/create-action.md#the-created-record-joins-the-relationship-in-the-same-flush), a refused nested create takes the record back out of a many-to-many parent's collection: `handRecordBackToParent()` runs in a `finally` when the mapping refuses it, or when `persist()` or the blank-key read below fails, and the exception leaves unchanged.
Left there, the record would stay for the rest of the request, so a later flush in that request (an exception listener's, or one in a `create()` override that caught the refusal) would fail with Doctrine's *A new entity was found through the relationship …*.
Any other parent holds nothing of the record and is left alone, unless the child's setter writes it into the parent's collection itself, which the hand-back does not undo.

### A child whose constructor takes its parent is built in `makeRecord()`, and both attaches leave that parent alone

A nested create page attaches the new record to its parent twice — before the mapping, so the record check sees the key half the parent fills, and again in `handleRecordCreation()`, so the parent wins over a form that picks another.
The record both attach is the one `makeRecord()` built, so a child whose constructor takes its parent — a derived identity whose parent half no setter can change, `new Translation($this->getParentRecord(), $data['locale'])` — is built there with the page's own parent record.
Each attach writes the child's mapped-by property through the schemas mapper, and on a property no setter can change that write is [left alone when it already holds the same record](../schemas/property-accessor.md#a-property-no-setter-can-change-is-left-alone-when-it-already-holds-the-value), compared by identity: `getParentRecord()` answers the one instance the page resolved for the request, so both attaches pass.
A constructor handed **another** parent is refused by name at the first attach — *Cannot write association …: no setter can change that property, and it already holds another value* — before anything is mapped or persisted, so nothing is saved under either parent; the attach's own identifier guard does not see it first, because it reads only a record the manager already holds.
A `makeRecord()` that answers the parent record itself, or anything else that is no instance of the resource's record class, is refused before the first attach.
Pinned by [`MakeRecordTest`](../../../packages/panels/tests/Integration/Resources/Pages/MakeRecordTest.php).

### A record whose assigned key is still blank after `persist()` is refused before the flush

The default `handleRecordCreation()` asks [`AssociationReader::hasBlankAssignedIdentifier()`](../schemas/association-reader.md#a-blank-assigned-identifier-is-read-after-persist-where-a-record-is-created) between `persist()` and `flush()`, so every `prePersist` listener has run, then detaches the record and throws a `LogicException`: *The resource page [App\Admin\CreateEdition] built a [App\Entity\Edition] whose identifier is still blank after persist(), so it was not saved.
Give every identifier field a form field — a Hidden one for a value the user should not type — or fill it in a prePersist listener, in makeRecord(), or in handleRecordCreation() before calling the parent.*
Nothing is flushed, and an override that sets the key before calling the parent passes, because the check reads the record the parent persists.

What it catches is a key field the form leaves out; a blank one the form maps is already a field error ([the rule](../schemas/can-be-validated.md#a-blank-assigned-key-is-refused-before-anything-is-written)), unless the form switches the key rules off with [`validateRecordKey(false)`](../schemas/can-be-validated.md).
Then a blank mapped key whose field is not required reaches this refusal too, provided the blank reaches the record as a **string** (whitespace), and the message closes with: *The schema switches the key rules off with validateRecordKey(false), so a key field it maps is not refused while blank; switch them back on to have the blank field refused under it.*
An empty field dehydrates to null instead, which never gets here: over a nullable property Doctrine refuses it inside `persist()` with its own `EntityMissingAssignedId`, and over a non-nullable one the mapping refuses it first with Symfony's `InvalidTypeException`.
A key field over a non-nullable property [is required on its own](../forms/can-be-validated.md#requirement-is-derived-from-the-records-write-side-types), so the `InvalidTypeException` arm is reached only on a field that says `required(false)`.

One record cannot be detached: a single derived identity whose target's string key is still `''` flattens to an identity hash of `''`, which Doctrine can neither detach nor remove.
The page swallows that `ORMInvalidArgumentException` and throws its own message rather than Doctrine's *Entity has no identity, therefore remove from identity map cannot be performed*; the record stays scheduled in the unit of work for the rest of the request, and nothing is written, because nothing flushes after the refusal ([`CreateRecordBlankIdentityHashTest`](../../../packages/panels/tests/Integration/Resources/Pages/CreateRecordBlankIdentityHashTest.php)).

### Both key reads ask the form's own reader, rebound to the page's manager

Before the mapping, `mapRecordForCreation()` sets the form's association reader to a clone of itself bound to the manager `setRecordPersistence()` injected ([`withEntityManager()`](../schemas/association-reader.md)), so the record check that ends `getData()` reads the unit of work and the database the flush writes to; `handleRecordCreation()`'s blank-key read asks the form's reader rebound the same way rather than a reader of its own.
So a reader service an application subclassed or decorated is the one both reads ask (a decorator that delegates must override `withEntityManager()` to rebind what it delegates to), and a page that overrides `setRecordPersistence()` with another manager is checked against that manager.
The bound reader is not put back afterwards, as `CreateAction`'s is not: a later read in the request asks a clone bound to the same manager.

### The mounted schema's record answers for the top frame, and depends on the seed's laziness

`getMountedActionSchemaRecord()` takes no arguments.
It is the last fallback in [the host's seed chain](../actions/has-actions.md#the-per-mount-schema-is-seeded-with-the-actions-record-and-the-dotted-operation), and the chain runs **per frame** while the hook is asked without being told which frame is asking.
Filament's is frame-agnostic in exactly the same way, so the port keeps it, and the edit page's override reads `getMountedAction()` with no index: the top frame, which under a nested modal is the inner action rather than the one that opened it.
Nothing depends on the distinction.

On a frame whose action belongs to no schema component, the hook is never asked: `resolveMountedActionSchemaRecord()` consults `$action->getRecordClass(withDefault: true)` first, which reaches `getDefaultActionRecordClass()`, and the resource-page base always answers the resource's record class there.
The hook is reached only for an action a schema component owns, whose chain runs without the default (`HasActions::resolveMountedActionSchemaRecord()` sets `$withDefault` from the component) and finds neither a record nor a class on the component.
The overrides on `ListRecords`, `EditRecord` and `CreateRecord` answer what the default would have answered, so such a frame lands on the resource's record class instead of `null`; no shipped page builds such a frame, so no test reaches them.

The override is only *able* to read the mounted action because the seed's record is a closure.
`getMountedActionSchema()` builds the schema with `->record(fn () => $this->resolveMountedActionSchemaRecord($mountedAction))` and caches it, and the closure runs when the schema is first asked for its record.
Were the seed eager, resolving it would call `getMountedActionSchemaRecord()`, which calls `getMountedAction()`, which caches the whole mounted stack from inside the very call that is building and caching a frame's schema.

## Divergences

### The page holds its record in a plain property and writes through an injected entity manager

Filament's page declares `public ?Model $record`; ours declares `protected ?object $record`, a plain property rather than a `LiveProp`, set by `create()` and read by `getRedirectUrl()` in the same request, which ends with the redirect.
The only `LiveProp` of the page's own is the writable `$data` bag every field binds into.
`#[Required] setRecordPersistence(EntityManagerInterface)` is the persistence hop Filament does not need, because an Eloquent model saves itself.
It is a setter rather than a constructor argument, so a subclass declaring its own constructor never repeats it, and a save that finds it unset throws a `LogicException` naming the page and the container registration, before anything is mapped.
`EditRecord` declares the same setter rather than sharing a trait, as Filament keeps its record handlers per page.

### The create and save notification titles are literals, not translation keys

`getCreatedNotificationTitle()` returns `'Created'` and `getSavedNotificationTitle()` returns `'Saved'`, where Filament returns `__('filament-panels::resources/pages/create-record.notifications.created.title')` and its edit-page twin.
`getTitle()`'s `'Create %s'` and `getBreadcrumb()`'s `'Create'` follow the same rule.
The reason is [the dashboard title's](dashboard.md#the-dashboard-title-is-a-literal-not-a-translation-key): the port has no translation layer, and a `trans()` call against a catalogue nobody ships would render the key.
The **override points** are Filament's exactly (`getCreatedNotificationTitle()` / `getSavedNotificationTitle()` for the copy, `getCreatedNotification()` / `getSavedNotification()` for the whole notification), and each returns `null` when its title is `blank()`, Filament's own "no title, no toast" contract.
A translation layer reverses it: the literals become `trans()` calls and nothing else changes.

### The record page's schema is named `form`, and `schemas()` is narrowed to it

`HasSchemas` defaults to one schema named `schema`, discovered through a `schema()` or `schemaSchema()` method.
Both record pages instead declare `form(Schema): Schema` and `schemas(): ['form']`, so every read is `getSchema('form')` and the template says `renderSchema('form')`.

That is Filament's name (its pages carry a `form` schema and a `@property-read Schema $form`), and it makes room for the schemas a record page grows (`infolist` on a view page).
The narrowing of `schemas()` is not optional: the list is what the de/hydration hooks walk, so leaving the default in place would demand a `schema()` method the page does not have and throw `RuntimeException` on the first render, the same trap [`ListRecords::schemas()` returning `[]`](list-records.md) documents.

The cost is that `HasSchemas::fill()`, the trait's convenience wrapper hardcoded to the default schema, is not usable on these pages; they call `$this->getSchema('form')->fill(…)` explicitly, which is what a multi-schema host does anyway.
`fillForm()` calls it with no argument, so `fill(null)` hydrates with `applyDefaults: true` and every component's `default()` lands; there is no fill mutator, because Filament's create page has none either.

### The create page builds its record in `makeRecord()`, which Filament's page never needs

Filament's `CreateRecord::handleRecordCreation(array $data): Model` builds and saves in one step, `new ($this->getModel())($data)`, because an Eloquent model takes any attribute array in its constructor.
Ours keeps the name and the override role but takes the mapped record, `handleRecordCreation(object $record): object`, because `Schema::getData()` already returns the mapped object and the parent attach and the record check sit on it; the hook's job shrinks to persisting.
So construction needs a seam of its own: `makeRecord(array $data): object`, called once per create, before the attach and the mapping, and handed the form's state after `mutateFormDataBeforeCreate()` — every field the form maps, validated, plus any other key the browser sent in `data`, unvalidated, so an override reads only keys a field of the form maps.
The name is borrowed from Filament's `TranslatableContentDriver::makeRecord(string $model, array $data)`, which answers the same question on a contract the page never reaches; [translations.md](../translations.md#names-with-no-filament-source) carries the row.

Two refusals come with it that Filament has no need for.
The default builds the resource's record class with no arguments through the schemas mapper's `constructRecord()`, so a class whose constructor requires arguments is refused by name: *Cannot map a new record on the resource page [App\Admin\CreateRedirect]: App\Entity\Redirect requires constructor arguments, so the mapper cannot build it.
Give the constructor defaults, or override makeRecord() to build it from the form data.*
And an answer that is no instance of the resource's record class (the parent record, a draft object) is refused by name before the attach and the mapping, so nothing is written onto it.
`CreateAction` gains no such member, as Filament's has none: `using()` is its seam.

### A value a mutate hook adds that no field maps is refused, where Filament's fill keeps or drops it

Filament's create page builds the model with `new ($this->getModel())($data)` and its edit page calls `$record->update($data)`, so a key `mutateFormDataBeforeCreate()` or `mutateFormDataBeforeSave()` adds goes through Eloquent's `fill()`: written when the attribute is fillable, dropped in silence when `$fillable` is declared without it, thrown under `preventSilentlyDiscardingAttributes()`.
Ours maps through `Schema::getData($data)`, which writes only the paths a component maps (the record path map plays `$fillable`), so such a key would be dropped with nothing said, the page reporting *Created* or *Saved*.
Both pages instead ask the form's [`findUnmappedAdditions()`](../schemas/belongs-to-record.md) right after the hook, before the `beforeCreate` hook, `makeRecord()` or any write, and refuse every added leaf path by name with a `LogicException`: *The resource page [App\Admin\CreateArticle] added [status] in mutateFormDataBeforeCreate(), but no component of its form maps those paths, so the default write would drop them.
Map each with a component — a Hidden one for a value the user should not type, with required(false) where the record would otherwise require it — or set it on the record in makeRecord() or handleRecordCreation().*
The edit page's way out names `handleRecordUpdate()` instead.
That is Eloquent's `preventSilentlyDiscardingAttributes()` branch, always on, and limited to what the **hook** added: the state the hook was handed is the baseline, and a key the client typed into the writable `data` prop is already in it, so no request can force the refusal, and that key is still never written, because writing state no component declares would be mass assignment.
Overwriting a mapped key is unchanged, and a key a hidden or non-dehydrated component maps is written, because the map carries it.
Because the check runs before `makeRecord()`, a value only `makeRecord()` reads must be computed there.
The record actions and `Select`'s option forms refuse the same way, in the same words.
