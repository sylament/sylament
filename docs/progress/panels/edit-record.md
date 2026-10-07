---
description: 'The resource''s edit page: the resource form bound to a resolved record and saved in place.'
ports:
  - vendor/filament/filament/src/Resources/Pages/EditRecord.php
owns:
  - packages/panels/src/Resources/Pages/EditRecord.php
---
# EditRecord (panels)

`Sylament\Panels\Resources\Pages\EditRecord` extends [`Resources\Pages\Page`](resource-pages.md) and composes [`InteractsWithRecord`](interacts-with-record.md) (the route-key prop, the record, its title and crumb), [`HasRelationManagers`](has-relation-managers.md) and [`HasUnsavedDataChangesAlert`](unsaved-changes-alert.md).
Its form actions and the form variant its template extends are [form-actions.md](form-actions.md)'s.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`$data`](../../../packages/panels/src/Resources/Pages/EditRecord.php) — the writable `LiveProp` every field binds into | `public ?array $data` | done | — | — |
| [`setRecordPersistence()`](../../../packages/panels/src/Resources/Pages/EditRecord.php) — the `#[Required]` entity manager | no counterpart — an Eloquent model saves itself | done | — | — |
| [`mount()`](../../../packages/panels/src/Resources/Pages/EditRecord.php) | `mount(int \| string $record)` | done | — | [interacts-with-record.md](interacts-with-record.md#mountrecord-is-the-one-place-the-assignment-order-lives) |
| [`fillForm()`](../../../packages/panels/src/Resources/Pages/EditRecord.php) + [`mutateFormDataBeforeFill()`](../../../packages/panels/src/Resources/Pages/EditRecord.php) | `fillForm()` + `fillFormWithDataAndCallHooks()` + `mutateFormDataBeforeFill()` | done | — | — |
| `refreshFormData()` | the same | blocked | a partial fill on `Schema` (`fillPartially()`) | — |
| [`form()`](../../../packages/panels/src/Resources/Pages/EditRecord.php) | `form()` + `defaultForm()` | done | — | [list-records.md](list-records.md#a-modals-schema-keeps-the-resource-forms-own-column-count) |
| [`content()`](../../../packages/panels/src/Resources/Pages/EditRecord.php) + [`schemas()`](../../../packages/panels/src/Resources/Pages/EditRecord.php) | `content()` | done | — | [has-relation-managers.md](has-relation-managers.md#content-ships-in-a-narrow-shape) · [create-record.md](create-record.md#the-record-pages-schema-is-named-form-and-schemas-is-narrowed-to-it) |
| `getFormContentComponent()` | the same | skipped | — | [form-actions.md](form-actions.md#the-form-element-stays-in-twig-because-the-schemas-form-component-is-unported) |
| `getContentTabComponent()` / `getContentTabLabel()` | the same two | skipped | — | [has-relation-managers.md](has-relation-managers.md#the-combined-content-tab-does-not-port) |
| [`save()`](../../../packages/panels/src/Resources/Pages/EditRecord.php) | `save()` | done | — | [semantics](#the-save-ladder-runs-filaments-hooks-in-filaments-order) · [semantics](#getdata-writes-onto-the-managed-entity-before-it-can-refuse) |
| `save()`'s `$shouldRedirect` / `$shouldSendSavedNotification` flags | the same two parameters | todo | — | — |
| `saveFormComponentOnly()` | the same | todo | — | — |
| Database transactions around `save()` | `CanUseDatabaseTransactions` | todo | — | — |
| `RecordUpdated` / `RecordSaved` events | the two `Event::dispatch()` calls | todo | — | — |
| [`mutateFormDataBeforeSave()`](../../../packages/panels/src/Resources/Pages/EditRecord.php) | the same | done | — | [create-record.md](create-record.md#a-value-a-mutate-hook-adds-that-no-field-maps-is-refused-where-filaments-fill-keeps-or-drops-it) |
| [`handleRecordUpdate()`](../../../packages/panels/src/Resources/Pages/EditRecord.php) | `handleRecordUpdate(Model $record, array $data)` | done | — | [diverges](#handlerecordupdate-takes-the-mapped-record-not-the-record-and-an-array) |
| [`getSavedNotification()`](../../../packages/panels/src/Resources/Pages/EditRecord.php) + [`getSavedNotificationTitle()`](../../../packages/panels/src/Resources/Pages/EditRecord.php) | the same two | done | — | [create-record.md](create-record.md#the-create-and-save-notification-titles-are-literals-not-translation-keys) |
| `getSavedNotificationMessage()` | the `@deprecated` alias of `getSavedNotificationTitle()` | skipped | — | [renames.md](../renames.md#renames) |
| [`getRedirectUrl()`](../../../packages/panels/src/Resources/Pages/EditRecord.php) | `getRedirectUrl()` | done | — | [diverges](#staying-on-the-page-after-a-save-is-a-null-redirect-not-a-caught-authorization-exception) |
| `getRedirectUrl()`'s panel arm — `resourceEditPageRedirect()` — and `getRedirectUrlParameters()` | the same | todo | — | — |
| `authorizeAccess()` + `hydrate()` | the same two | blocked | authorization | — |
| `shouldRegisterNavigation()`'s `canEdit()` term | the same | blocked | authorization | — |
| `getNavigationLabel()` / `getNavigationIcon()` overrides | the same two | todo | — | [detail](#an-editrecord-in-a-band-is-labelled-by-its-class-name) |
| [`getTitle()`](../../../packages/panels/src/Resources/Pages/EditRecord.php) | the same | done | — | [diverges](#the-document-title-of-an-edit-page-carries-the-record-label-not-the-record-title) |
| [`getBreadcrumb()`](../../../packages/panels/src/Resources/Pages/EditRecord.php) | the same | done | — | — |
| [`getPageClasses()`](../../../packages/panels/src/Resources/Pages/EditRecord.php) | the same | done | — | — |
| `getSubmitFormAction()` | the same, read only by the resource `HasWizard` concern | todo | — | — |
| [`getDefaultActionRecord()`](../../../packages/panels/src/Resources/Pages/EditRecord.php) | `InteractsWithRecord::getDefaultActionRecord()` | done | — | [diverges](#a-create-modal-on-a-record-page-is-anonymous) |
| [`getDefaultActionRecordTitle()`](../../../packages/panels/src/Resources/Pages/EditRecord.php) | `InteractsWithRecord::getDefaultActionRecordTitle()` | done | — | — |
| [`getDefaultActionSuccessRedirectUrl()`](../../../packages/panels/src/Resources/Pages/EditRecord.php) | `InteractsWithRecord::getDefaultActionSuccessRedirectUrl()` | done | — | [resource.md](resource.md#url-generation-inside-a-live-action-resolves-the-panel-the-page-was-opened-in) |
| The success redirect's `ForceDeleteAction` arm | the same `match` arm | skipped | — | [delete-action.md](../actions/delete-action.md#there-is-no-soft-delete-arm-to-hide-behind) |
| [`getDefaultActionSchemaResolver()`](../../../packages/panels/src/Resources/Pages/EditRecord.php) | the same | done | — | [semantics](#the-edit-pages-own-edit-modal-is-the-page-form-itself) · [list-records.md](list-records.md#a-modals-schema-keeps-the-resource-forms-own-column-count) |
| [`getMountedActionSchemaRecord()`](../../../packages/panels/src/Resources/Pages/EditRecord.php) | `InteractsWithRecord::getMountedActionSchemaModel()` | done | — | [create-record.md](create-record.md#the-mounted-schemas-record-answers-for-the-top-frame-and-depends-on-the-seeds-laziness) |
| `afterActionCalled()` | `InteractsWithRecord::afterActionCalled()` | skipped | — | [interacts-with-record.md](interacts-with-record.md#afteractioncalled-is-not-ported-because-the-delete-redirect-ends-the-request-first) |

## Details

### An `EditRecord` in a band is labelled by its class name

Filament's `EditRecord` overrides `getNavigationLabel()` to *Edit* and `getNavigationIcon()` to a pencil-square alias — both exist **for** the [sub-navigation band](has-sub-navigation.md), and neither is ported.
The band therefore labels an edit page through the inherited chain on [`Pages\Page`](page.md): `$navigationLabel`, then `$title`, then the humanized class basename — so an `EditArticle` page reads *Edit Article* in the band where Filament reads *Edit*, and carries no icon unless the page declares one.
Declaring the two statics on the page is the whole workaround, and porting Filament's overrides (with the `view-record` pair beside them — `CreateRecord` declares neither, and `PanelsIconAlias` ships no `create-record` navigation-item constant to port) is what makes the Filament look the default.
The gap shows only in the band: nothing else reads those methods on a resource page.

## Semantics

### The save ladder runs Filament's hooks in Filament's order

`save()` is the `#[LiveAction]` the form's submit posts, and its steps against Filament's:

| Step | Filament `save()` | Ours |
| --- | --- | --- |
| validate | `callHook('beforeValidate')` → `$this->form->getState(afterValidate: …)` | same, and the callback is Filament's pair: `callHook('afterValidate')` then `callHook('beforeSave')` |
| mutate | `mutateFormDataBeforeSave($data)` | same — **after** `beforeSave`, which fired inside the state read — then a path it adds that no component maps is refused by name |
| write | `handleRecordUpdate($record, $data)` → `callHook('afterSave')` | the form's reader bound to the page's manager → `handleRecordUpdate($schema->getData($data))` → `$schema->loadStateFromRelationships()` → `callHook('afterSave')` |
| remember | `rememberData()` | same |
| notify | `getSavedNotification()?->send()` | same |
| redirect | `redirect($url)` when `getRedirectUrl()` is filled | a `RedirectResponse` when `getRedirectUrl()` is non-null, else `null` |

Re-deriving the order from the method body invites normalising `beforeSave` to sit after the mutator; it fires inside the state read, before it, as in Filament.
A `Halt` thrown anywhere in the ladder returns `null` and skips every step after it: on the `EditHookedArticle` fixture's `$haltsBeforeSave` arm the hooks that ran are `beforeValidate`, `afterValidate`, `beforeSave`, and the row is untouched.

Before the mapping, `save()` binds the form's association reader to the manager the page writes through — a [`withEntityManager()`](../schemas/association-reader.md) clone of whatever reader the form holds — so the changed-, blank- and taken-key passes that end `getData()` read the unit of work and the database the flush writes to, not the container's default manager.
After the flush, `$schema->loadStateFromRelationships()` — Filament's post-save step, [ported as the host's](../schemas/belongs-to-record.md#loadstatefromrelationships-is-the-hosts-reload-after-its-flush-and-takes-no-parameter) — keys a relationship repeater's added items by the identifiers the flush gave them, so the next save updates what this one created (`EditRecordRelationshipsTest`).

A save that stays on the page answers `null` from the live action, so the response is the re-rendered component: notifications' `LiveDeliverySubscriber` drains the flash bag during that re-render and dispatches `sylament:notification` as a browser event, which the document outlet's controller shows.

### `getData()` writes onto the managed entity before it can refuse

`getData($data)` is where the state reaches the record, and it **mutates before it can refuse**: a record-level violation (`UniqueEntity`) is raised after the state has been written onto the managed entity, so the unit of work holds a dirty entity that is never flushed.
Nothing else in the request may flush, which is why `handleRecordUpdate()` is the only write and why the demo's `testInvalidEditSubmitLeavesTheRowUntouched` pins it.

The record's **key** is the one field that does not stay mutated: the record check refuses an edited identifier field and puts the stored value back, so even a flush later in the request has no key to write ([the rule](../schemas/can-be-validated.md#a-changed-key-is-refused-and-put-back-not-handed-to-doctrine)).
Without that, a composite or assigned key would re-key the row and strand the page's record prop until the next request 404ed, a taken one would close the entity manager, and an IDENTITY one would report *Saved* while Doctrine dropped the edit.
[`EditRecordChangedKeyTest`](../../../packages/panels/tests/Integration/Resources/Pages/EditRecordChangedKeyTest.php) and `CompositeRecordPagesTest`'s key-edit case pin it.

### The edit page's own edit modal is the page form itself

`EditRecord::getDefaultActionSchemaResolver()` gives an `EditAction` `fn (Schema $schema): Schema => $schema->components([EmbeddedSchema::make('form')])` — Filament's own body, one line for one line.
The modal renders the page's **own** `form` schema: the same object the page beneath it is bound to, on the same state path, with the same error bag.
One tree cannot drift from itself, which is why `afterActionCalled()` is not overridden to re-fill the page form after a modal edit: a re-fill would discard what the user just typed.

The cost, and it is Filament's too: **the modal's own submit writes nothing.**
An embed carries no state of its own, so the action's schema — bound to `mountedActionsData.0` — is empty, and `EditAction`'s write maps that empty array onto the record before reporting success.
The typing is real, because it went straight into the page's `data`; it is the page's own **Save** that persists it.
A header `EditAction` on an edit page is therefore a magnified view of the form already on the page rather than a second way to save it, which is what Filament's resolver makes it.
`EditRecordTest` pins exactly that sequence, so the day the behaviour changes it changes visibly.

The second cost, also Filament's: **while the modal is open, the page carries the form's ids twice.**
One schema rendered in two places emits one set of ids in two places — on `EditArticle` with the modal mounted, the document holds `id="form.title"` and `id="form.slug"` twice each, and `label[for="form.title"]` twice.
That is invalid HTML, `document.getElementById()` collides, and clicking the modal's own label focuses the input *behind* the modal, because the browser matches the first element in document order.
It follows from what an embed is: the schema has one id path, not one per place it renders.
Closing it needs a render-time id scope on `EmbeddedSchema`, which Filament has no counterpart for either, so it is recorded rather than papered over — and "open the header Edit modal, then click a field label" belongs on the browser checklist of any change that touches this arm.

## Divergences

### Staying on the page after a save is a `null` redirect, not a caught authorization exception

Filament's `EditRecord::getRedirectUrl()` is a `try { $this->authorizeAccess(); return null; } catch (AuthorizationException | HttpExceptionInterface) { … }` — it stays on the page when the user may still view it, and redirects to the index when saving has just revoked their own access.
With authorization deferred, the predicate has nothing to test, so ours is `return null;` and `save()` reads it as "stay":

```php
$redirectUrl = $this->getRedirectUrl();

return $redirectUrl === null ? null : new RedirectResponse($redirectUrl);
```

The nullable return type is Filament's, and the override point is unchanged — a user returning a URL gets a redirect.
The exception-as-predicate returns with authorization and is not emulated meanwhile.
The create page's `getRedirectUrl(): string` is **not** nullable, matching Filament: a create always goes somewhere.

The visible half of this is that a successful save returns `null` from the `#[LiveAction]`, so the response is the re-rendered component rather than a redirect — which is what lets notifications' `LiveDeliverySubscriber` dispatch the toast as a browser event on the same round trip (`EditRecordTest`).
The create page's redirect takes the other delivery path, the flash bag drained by the document's outlet.

### The document title of an edit page carries the record label, not the record title

Filament's edit page titles the document `Edit {getRecordTitle()}` — `Edit Alpha`.
Ours shows `Edit Article` in the browser tab while the page's own `<h1>` shows `Edit Alpha`, and the split is [`PageController`](routing.md)'s doing: it reads `$page->getTitle()` off the **locator instance** to build the document, before the page component is mounted and therefore before `$this->record` holds anything.
`InteractsWithRecord::getRecordTitle()` guards on exactly that and answers the resource's title-case record label instead; `getDefaultActionRecord()` and `getMountedActionSchemaRecord()` carry the same guard.

The guard is not cosmetic.
Without it, `getTitle()` on an un-mounted page calls `getRecord()`, which finds a null key and throws `NotFoundHttpException` — so **every** edit-page GET would answer 404.

Revisit when `PageController` can mount the component before reading its title.
It cannot: mounting happens inside `{{ component(componentName, componentProps) }}` during the Twig render, and the title is needed to build the document that contains it.
A controller that resolved props and called the page's `mount()` itself would have both, at the cost of mounting twice or handing ux-twig-component a pre-mounted instance.

### A create modal on a record page is anonymous

`EditRecord::getDefaultActionRecord()` answers `null` for a `CreateAction` and the page's record for everything else.
Filament's record-page concern has no such arm — its `getDefaultActionRecord()` is `$this->hasRecord() ? $this->getRecord() : null`, so a `CreateAction` mounted on an edit page is handed the record that page is editing.

That is coherent in Filament because a create modal on a record page is nearly always a *relationship* create, where the page record is the parent the new row hangs off, and Eloquent's relationship arm reads it as exactly that.
Our relationship arm exists — `CreateAction::relationship()` ships and a relation manager answers `getDefaultActionRelationship()` — but it hangs off the *table* and the manager, never off an ambient page record, so on the page itself the same default would still be read the only other way it can be: as the record the create is *about*.
A `CreateAction` seeded with an existing record fills its modal from that record and, on submit, maps state onto it.
The create modal would silently become an edit of the page's record.

Filament's own `ManageRelatedRecords` is the shape borrowed here, and ours ([manage-related-records.md](manage-related-records.md)) and the [relation manager](relation-manager.md) are that shape: each hands a create action the *parent*, deliberately and explicitly, through `getDefaultActionRelationship()`, rather than letting the ambient record stand in.
On the record page itself `null` stays the honest answer — a create modal there belongs to no record, and the resource's record class (which `getMountedActionSchemaRecord()` still answers) is all the schema needs to build a blank form.

### `handleRecordUpdate()` takes the mapped record, not the record and an array

Filament's `handleRecordUpdate(Model $record, array $data): Model` is `$record->update($data)`, because an Eloquent model mass-assigns an array.
Ours is `handleRecordUpdate(object $record): object`, handed the record `$schema->getData($data)` has already mapped the state onto, and its default only persists and flushes through the page's entity manager.
The mapping is the schema's, with the refusals that end it, so the hook receives a record that is ready to write rather than an array it would have to map again; a page that sets a value the form does not map sets it on the record here.
The create page makes the same move for `handleRecordCreation()` ([create-record.md](create-record.md#the-create-page-builds-its-record-in-makerecord-which-filaments-page-never-needs)).
