---
description: 'The resource''s edit page: the resource form bound to a resolved record and saved in place.'
ports:
  - vendor/filament/filament/src/Resources/Pages/EditRecord.php
owns:
  - packages/panels/src/Resources/Pages/EditRecord.php
---
# EditRecord (panels)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Resources\Pages\EditRecord`](../../../packages/panels/src/Resources/Pages/EditRecord.php) | [`Resources/Pages/EditRecord.php`](../../../vendor/filament/filament/src/Resources/Pages/EditRecord.php) | in progress | — | — |
| [EditRecord template](../../../packages/panels/templates/partials/EditRecord/index.html.twig) | same — the one-block child of the form variant, overriding `after_form` to hold the relation managers below the form | in progress | — | — |
| _rest not audited yet_ | — | — | — | — |

## Details

### The edit page binds the resource form to a resolved record and saves it in place

The resource's edit page: the same form schema bound to a resolved record, saved in place.

```php
abstract class EditRecord extends Page
{
    use HasRelationManagers;          // Sylament\Panels\Resources\Pages\Concerns
    use HasUnsavedDataChangesAlert;   // Sylament\Panels\Pages\Concerns
    use InteractsWithRecord;          // Sylament\Panels\Resources\Pages\Concerns
}
```

Two `LiveProp`s: `$data` here, and `public ?string $record` — **the route key, never the record** — on [`InteractsWithRecord`](interacts-with-record.md), which was extracted out of this class unchanged so `ManageRelatedRecords` (and `ViewRecord`, once the view page lands) could compose the same six members.
Both halves of the record prop are constrained by the wire, and each has its own divergence: [the record prop carries the key and dehydrates through a bridge](interacts-with-record.md#the-record-prop-carries-the-route-key-and-needs-a-dehydration-bridge).
`protected ?object $resolvedRecord` memoises the resolution for the request.
`#[Required] setRecordPersistence()` is the create page's, verbatim.

What moved to the trait, verbatim: the `$record` prop and `dehydrateRecord()`, `hasRecord()`, `getRecordKey()`, `getRecord()` and `getRecordTitle()`.
What the trait adds beyond them is this page's too — the record's own breadcrumb and the `getSubNavigation()` / `getSubNavigationParameters()` bridge to [the band](has-sub-navigation.md).
The extraction landed with **zero test edits**, which is the whole evidence that it preserved behaviour.

- `mount(int|string $record, array $parentRecords = [])` delegates to the trait's `mountRecord()` — the parent keys assigned first, then the record key — and calls `fillForm()`: `callHook('beforeFill')`, a fill from `mutateFormDataBeforeFill($schema->getStateFromRecord($record))`, and `callHook('afterFill')`.
  `fillForm()` is Filament's own method name, kept as a method of its own so a page that mutates or hooks on fill has one place to reach — it had a second caller until the page's own edit modal became [the page's own form](#the-edit-pages-own-edit-modal-is-the-page-form-itself).
  The union is on the **mount parameter**, which is Filament's signature and is legal there; the property underneath it cannot be one.
  Route parameters arrive as strings ([`componentProps`](routing.md)), so the cast is a formality for a hand-mounted component.
- `getRecord(): object` resolves through `Resource::resolveRecordRouteBinding($key, $this->getParentRecordScope())` and memoises, throwing `NotFoundHttpException` for a missing key and for a key that matches nothing.
  That is the whole 404 story: `PageController` [unwraps an `HttpExceptionInterface` out of Twig's render error](routing.md#a-mount-time-http-exception-is-unwrapped-from-twigs-render-error), so the throw from `mount()` answers 404 rather than 500.
  Filament's `InteractsWithRecord::getRecord()` does `abort_unless(… instanceof Model, 404)` for the same effect.
- `form(Schema)` is the create page's with `record($this->getRecord())` — the **instance** arm — and `Operation::Edit`.
- `content(Schema)` holds the one component [`HasRelationManagers`](has-relation-managers.md) builds out of the resource's `getRelations()`, and `schemas()` is `['form', 'content']`.
  The template renders it with a single `renderSchema('content')` after `</form>`, which is [a deliberately narrow reading of Filament's `content()`](has-relation-managers.md#content-ships-in-a-narrow-shape).
  Naming it in `schemas()` also means it is rebuilt on **every** live request of the page — [the tab statics are on that path](has-relation-managers.md#the-content-schema-is-built-on-every-live-request).
- `hasRecord()` and `getRecordKey()` answer off the raw `$record` prop, which is what an embedded relation manager is mounted with — [the record probes read the wire value](has-relation-managers.md#the-record-probes-read-the-wire-value-not-the-resolved-record).
- `save()` is the `#[LiveAction]`.

The lifecycle, ours against Filament's:

| Step | Filament `save()` | Ours |
| --- | --- | --- |
| authorization | `authorizeAccess()` | — deferred |
| transaction | `beginDatabaseTransaction()` | — deferred |
| validate | `callHook('beforeValidate')` → `$this->form->getState(afterValidate: …)` | same, and the callback is Filament's pair: `callHook('afterValidate')` then `callHook('beforeSave')` |
| mutate | `mutateFormDataBeforeSave($data)` | same — **after** `beforeSave`, which fired inside the state read — then a path it adds that no component maps is refused by name |
| write | `handleRecordUpdate($record, $data)` → `callHook('afterSave')` | the form's reader bound to the page's manager → `handleRecordUpdate($schema->getData($data))` → `$schema->loadStateFromRelationships()` → `callHook('afterSave')` — `persist()` + `flush()`, then the relationship-backed re-read |
| events | `RecordUpdated`, `RecordSaved` | — no event layer |
| remember | `rememberData()` | same, through [`HasUnsavedDataChangesAlert`](../../../packages/panels/src/Pages/Concerns/HasUnsavedDataChangesAlert.php) |
| notify | `getSavedNotification()?->send()` when `$shouldSendSavedNotification` | `getSavedNotification()?->send()` |
| redirect | `redirect($url)` when `$shouldRedirect` and `getRedirectUrl()` is filled | `getRedirectUrl()` is `null`, so `save()` returns `null` and the page stays |

The ladder is wrapped in the create page's `catch (Halt)` exactly as described there, and the `EditHookedArticle` fixture's `$haltsBeforeSave` arm is what pins it: the hooks that ran are `beforeValidate`, `afterValidate`, `beforeSave`, and the row is untouched.

`mutateFormDataBeforeSave()` meets the create page's refusal: a leaf path it adds to the state it was handed that no component of the form maps is refused by name, right after the hook and before the reader is bound or anything mapped, with *set it on the record in handleRecordUpdate()* as this page's way out ([the create page's paragraph](create-record.md), [the divergence](create-record.md#a-value-a-mutate-hook-adds-that-no-field-maps-is-refused-where-filaments-fill-keeps-or-drops-it)).

Before the mapping, `save()` binds the form's association reader to the manager the page writes through, as the create page does — a [`withEntityManager()`](../schemas/association-reader.md) clone of whatever reader the form holds — so the changed-, blank- and taken-key passes that end `getData()` read the unit of work and the database the flush writes to, not the container's default manager the form's unbound reader would ask.

`getData($data)` is where the state reaches the record, and it **mutates before it can refuse**: a record-level violation (`UniqueEntity`) is raised after the state has been written onto the managed entity, so the unit of work holds a dirty entity that is never flushed.
Nothing else in the request may flush, which is why `handleRecordUpdate()` is the only write and why the demo's `testInvalidEditSubmitLeavesTheRowUntouched` is the carried pin for it.

The record's **key** is the one field that does not stay mutated.
The record check refuses an edited identifier field — *"This value cannot be changed."* under it, whatever generator fills it, IDENTITY included — and puts the stored value back, so even a flush later in the request has no key to write ([the rule](../schemas/can-be-validated.md#a-changed-key-is-refused-and-put-back-not-handed-to-doctrine)).
Without that, a composite or assigned key would re-key the row and strand the page's record prop until the next request 404ed, a taken one would close the entity manager, and an IDENTITY one would report *Saved* while Doctrine dropped the edit.
Pinned by [`EditRecordChangedKeyTest`](../../../packages/panels/tests/Integration/Resources/Pages/EditRecordChangedKeyTest.php) and by `CompositeRecordPagesTest`'s key-edit case, flipped in place from pinning the re-key.

**The reload.**
Once `handleRecordUpdate()` has flushed, `save()` calls `$schema->loadStateFromRelationships()` — Filament's post-save step, [ported as the host's](../schemas/belongs-to-record.md#loadstatefromrelationships-is-the-hosts-reload-after-its-flush-and-takes-no-parameter) — so a relationship repeater's added items are keyed by the identifiers the flush gave them and the next save updates what this one created, where it used to build them again.
Pinned by [`EditRecordRelationshipsTest`](../../../packages/panels/tests/Integration/Resources/Pages/EditRecordRelationshipsTest.php).

Staying on the page is expressed as [a `null` redirect rather than Filament's exception-as-predicate](#staying-on-the-page-after-a-save-is-a-null-redirect-not-a-caught-authorization-exception).
The toast then arrives on the same round trip: notifications' `LiveDeliverySubscriber` drains the flash bag during the live re-render and dispatches `sylament:notification` as a browser event, which the document outlet's controller shows.

`getRecordTitle()` is the trait's, and it is Filament's plus one guard: it answers the resource's title-case record label when `$this->record` is null, because `getTitle()` is read off an **un-mounted** page for the document `<title>`.
That guard is not a nicety — [without it every edit-page GET 404s](#the-document-title-of-an-edit-page-carries-the-record-label-not-the-record-title), and the visible consequence is that the browser tab says `Edit Article` while the page heading says `Edit Alpha`.

**The action host hooks.**
The edit page is the resource page that answers the record family, and every answer is guarded on `$this->record` being filled — `PageController` reads a page's title, and through it these hooks' neighbours, off an **un-mounted** instance, so an unguarded `getRecord()` would 404 every edit-page GET.

- `getDefaultActionRecord()` is the page's record for every action **except a `CreateAction`**, which gets `null` — [a create modal on a record page is anonymous](#a-create-modal-on-a-record-page-is-anonymous).
- `getDefaultActionRecordTitle()` is the already-guarded `getRecordTitle()`, so a mounted action's modal heading names the record rather than the record type.
- `getDefaultActionSuccessRedirectUrl()` answers the resource's index URL for a `DeleteAction` and `null` for everything else — spreading the page's `parentRecords` into it, so a delete on a nested page lands on the parent's page rather than on a URL missing an ancestor — the one non-null body of the hook anywhere in Filament's panels (it lives on the record-page concern edit and view share, and `ManageRelatedRecords` only *narrows* it, returning null for a table-context action), and what sends a header delete back to the list instead of re-rendering a page whose record is gone.
  Filament's `match` carries a `ForceDeleteAction` arm too, absent here with soft deletes.
  The redirect ends the request before any re-render, which is also why Filament's other use of `afterActionCalled()` on that concern — nulling `$this->record` after a delete so Livewire does not try to dehydrate a row that no longer exists — is recorded rather than ported: there is no re-render left to protect.
  Its one caveat is inherited rather than new: the URL is generated inside a `#[LiveAction]`, so [the default panel answers](resource.md#url-generation-inside-a-live-action-resolves-the-default-panel) — right for every single-panel application, wrong for a resource mounted only in a non-default panel, and one more instance of that defect.
- `getDefaultActionSchemaResolver()` is the list page's create and view arms over the **resource's** `form()`, and an edit arm that is not: an `EditAction` on this page resolves to `$schema->components([EmbeddedSchema::make('form')])`, which is Filament's own body and mounts [the page's own form rather than a second tree](#the-edit-pages-own-edit-modal-is-the-page-form-itself).
- `getMountedActionSchemaRecord()` is the resource's record class for a `CreateAction` and the guarded record otherwise — [it answers for the top frame, depends on the seed being lazy, and is unreachable on every page shipped today](create-record.md#the-mounted-schemas-record-answers-for-the-top-frame-and-depends-on-the-seeds-laziness).
- `afterActionCalled()` is **not** overridden.
  It re-filled the page form after a modal edit while the modal was a second tree over the same record; with one tree there is nothing to re-fill and a re-fill would discard what the user just typed.
  Filament's own use of the hook here — nulling the record after a delete — stays unported for the reason above: the redirect ends the request first.

**The template.**
`partials/EditRecord/index.html.twig` is now seven lines: it `{% extends %}` the shared [form variant](form-actions.md) and overrides one block, `after_form`, with `{{ renderSchema('content') }}` — where the relation managers render.
Everything the create page has, it inherits: the same `<form>` bound through `getSubmitFormLiveComponentMethodName()` (`save` here), the same required record-level error outlet, the same actions row, the same chrome, the same `Modals` outlet and the same [sub-navigation band](has-sub-navigation.md#an-empty-band-never-resolves-a-panel), with the root class family (`sy-pa-resource-edit-record-page`, the slug and `sy-pa-resource-record-<key>`) coming from `getPageClasses()`.
The managers render **after** the form element rather than inside it, because a manager hosts forms of its own and a nested `<form>` is invalid HTML the browser silently unnests — which is what the `after_form` block exists for.

Deferred, all todo: `refreshFormData()` (it needs a partial fill), `saveFormComponentOnly()`, database transactions, `previousUrl`, the record events, the authorization arms, the combined content tab that puts the form itself in the relation-manager tab bar ([HasRelationManagers](has-relation-managers.md#the-combined-content-tab-does-not-port)), and the `getNavigationIcon()` / `getNavigationLabel()` overrides Filament's edit page carries **for** sub-navigation — which now have a consumer, so the gap is visible: an edit page in a band is labelled by [the inherited chain](page.md) (`$navigationLabel`, then `$title`, then the humanized class basename, i.e. *Edit Article* for an `EditArticle` page) where Filament labels it *Edit* and gives it a pencil-square glyph.
A page declaring the two statics gets Filament's look today; porting the overrides is what makes it the default.

Divergences, the four shared with the create page plus five of its own: [the notification titles are literals](create-record.md#the-create-and-save-notification-titles-are-literals-not-translation-keys), [the schema is named `form`](create-record.md#the-record-pages-schema-is-named-form-and-schemas-is-narrowed-to-it), [a path the mutator adds that no field maps is refused](create-record.md#a-value-a-mutate-hook-adds-that-no-field-maps-is-refused-where-filaments-fill-keeps-or-drops-it), [URL generation inside a live action resolves the default panel](resource.md#url-generation-inside-a-live-action-resolves-the-default-panel), [the record prop carries the route key and needs a dehydration bridge](interacts-with-record.md#the-record-prop-carries-the-route-key-and-needs-a-dehydration-bridge), [staying on the page is a `null` redirect](#staying-on-the-page-after-a-save-is-a-null-redirect-not-a-caught-authorization-exception), [the document title carries the record label](#the-document-title-of-an-edit-page-carries-the-record-label-not-the-record-title), [a create modal on it is anonymous](#a-create-modal-on-a-record-page-is-anonymous), and [a modal's schema keeps the resource form's own column count](list-records.md#a-modals-schema-keeps-the-resource-forms-own-column-count).
Its own edit modal is no longer one of them — [it is the page's own form](#the-edit-pages-own-edit-modal-is-the-page-form-itself), and the relation managers under it are [HasRelationManagers](has-relation-managers.md)'.

## Semantics

### The edit page's own edit modal is the page form itself

`EditRecord::getDefaultActionSchemaResolver()` gives an `EditAction` `fn (Schema $schema): Schema => $schema->components([EmbeddedSchema::make('form')])` — Filament's own body, one line for one line.
The modal renders the page's **own** `form` schema: the same object the page beneath it is bound to, on the same state path, with the same error bag.
The arm waited on [`EmbeddedSchema`](../schemas/embedded-schema.md), which was ported early to mount relation managers and which this was the first consumer of.

What that retires is a former divergence: the modal used to be a **second component tree** over the same record — its own state path, its own validation — which was correct for the modal and wrong for the page behind it, still showing the values it was filled with at mount.
`afterActionCalled()` closed that gap by re-filling the page form through `fillForm()` whenever a modal edit of the page's own record succeeded.
Both are gone: one tree cannot drift from itself, and a re-fill would now discard what the user just typed.

The cost, and it is Filament's too: **the modal's own submit writes nothing.**
An embed carries no state of its own, so the action's schema — bound to `mountedActionsData.0` — is empty, and `EditAction`'s write maps that empty array onto the record before reporting success.
The typing is real, because it went straight into the page's `data`; it is the page's own **Save** that persists it.
A header `EditAction` on an edit page is therefore a magnified view of the form already on the page rather than a second way to save it, which is what Filament's resolver makes it.
`EditRecordTest` pins exactly that sequence, so the day the behaviour changes it changes visibly.

The second cost, also Filament's: **while the modal is open, the page carries the form's ids twice.**
One schema rendered in two places emits one set of ids in two places — on `EditArticle` with the modal mounted, the document holds `id="form.title"` and `id="form.slug"` twice each, and `label[for="form.title"]` twice.
That is invalid HTML, `document.getElementById()` collides, and clicking the modal's own label focuses the input *behind* the modal, because the browser matches the first element in document order.
It is new at this commit — while the modal was a second tree on `mountedActionsData.0.*` the two sets of ids were distinct — and it follows from what an embed is: the schema has one id path, not one per place it renders.
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
What is gone is the exception-as-predicate, and it comes back with the auth cluster rather than being emulated now.
Note the create page's `getRedirectUrl(): string` is **not** nullable, matching Filament: a create always goes somewhere.

The visible half of this is that a successful save returns `null` from the `#[LiveAction]`, so the response is the re-rendered component rather than a redirect — which is what lets notifications' `LiveDeliverySubscriber` dispatch the toast as a browser event on the same round trip.
The create page's redirect takes the other delivery path, the flash bag drained by the document's outlet.
Both are pinned.

### The document title of an edit page carries the record label, not the record title

Filament's edit page titles the document `Edit {getRecordTitle()}` — `Edit Alpha`.
Ours shows `Edit Article` in the browser tab while the page's own `<h1>` shows `Edit Alpha`, and the split is [`PageController`](routing.md)'s doing: it reads `$page->getTitle()` off the **locator instance** to build the document, before the page component is mounted and therefore before `$this->record` holds anything.
`getRecordTitle()` guards on exactly that and answers the resource's title-case record label instead.

The guard is not cosmetic.
Without it, `getTitle()` on an un-mounted page calls `getRecord()`, which finds a null key and throws `NotFoundHttpException` — so **every** edit-page GET would answer 404.
That is what the mutation test shows when the guard is removed, and it is why this is recorded as a divergence rather than left as an oddity to be tidied later.

Revisit when `PageController` can mount the component before reading its title.
Today it cannot: mounting happens inside `{{ component(componentName, componentProps) }}` during the Twig render, and the title is needed to build the document that contains it.
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
