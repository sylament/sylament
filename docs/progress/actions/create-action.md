---
description: The prebuilt "new record" action a resource's list page puts in its header. (draft)
ports:
  - vendor/filament/actions/src/CreateAction.php
  - vendor/filament/actions/src/Concerns/CanCustomizeProcess.php
owns:
  - packages/actions/src/CreateAction.php
---
# CreateAction (actions)

[`Sylament\Actions\CreateAction`](../../../packages/actions/src/CreateAction.php) — the prebuilt "new record" action a resource's list page puts in its header.
It works both of Filament's ways off one class.
With a create page to link to it renders as a link; without one it opens a modal, fills a form from whatever [`HasSchema::getSchema()`](has-schema.md#the-host-resolver-answers-only-when-the-action-declares-nothing) resolves, maps the validated state onto a new record of the action's class, persists it through Doctrine and reports `success()`.

**A page beats a modal, and the rule is Filament's — stated here once for all four record actions.**
[`CanOpenUrl::getUrl()`](../../../packages/actions/src/Concerns/CanOpenUrl.php) answers the action's own `url()` and then the host's [`getDefaultActionUrl()`](interacts-with-actions.md#the-panel-level-default-hooks-are-on-the-contract-the-authorization-pair-deferred) hook, and an action with a URL renders an anchor that is never mounted — so setting a modal heading, as this class now does, does not by itself open one.
There are exactly three ways in: the host answers no URL (a resource that registers no create page, which is all a "simple (modal) resource" is — the absence of a page, not a feature), `->modal()` forces one (a hard `true` from `hasModal()` short-circuits `getUrl()` to `null` before either arm is read), or the action names a `recordClass()` its host declines to route.

Covered by [`CreateActionTest`](../../../packages/actions/tests/Unit/CreateActionTest.php) (the chrome, the label interpolation, the create-another footer action and the three-hop relationship chain), [`CreateActionTest`](../../../packages/actions/tests/Integration/CreateActionTest.php) (the modal arm end to end against the fixture entity manager) and [`CreateActionRelationshipTest`](../../../packages/tables/tests/Integration/CreateActionRelationshipTest.php) (the relationship arm, in tables' suite because only that kernel boots a table and a forms field — the src-mirror rule is relaxed there for the same reason [`PrebuiltDeleteActionTest`](../../../packages/tables/tests/Integration/PrebuiltDeleteActionTest.php) relaxes it).
The create-path rules are pinned beside them by [`CreateActionTakenKeyTest`](../../../packages/actions/tests/Integration/CreateActionTakenKeyTest.php) (a taken key under its field, the create-another run, the order against the record's own constraints, a generated id adding no statement) and [`CreateActionRelationshipKeyTest`](../../../packages/actions/tests/Integration/CreateActionRelationshipKeyTest.php) (the attach before the mapping, the restored binding, the many-to-many give-back, the relationship winning and the lookup on the form's pick).
[`SubclassedAssociationReaderTest`](../../../packages/actions/tests/Integration/SubclassedAssociationReaderTest.php) pins the record check asking the container's own reader — a subclass of it, rebound to a second manager — rather than one the action builds, for create, edit and both replicate arms.
[`UnmappedHookDataTest`](../../../packages/actions/tests/Integration/UnmappedHookDataTest.php) pins the refusal of a key `mutateDataUsing()` adds and no component maps, and the four shapes it lets through, across create, edit and replicate.
[`KeyAdviceTest`](../../../packages/actions/tests/Integration/KeyAdviceTest.php) pins the refusals' advice — `using()` for a class whose constructor requires arguments, `validateRecordKey()` when the modal switches the key rules off.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`getDefaultName()`](../../../packages/actions/src/CreateAction.php) (`'create'`) | [`CreateAction.php`](../../../vendor/filament/actions/src/CreateAction.php) | done | — | — |
| [`setUp()` label + success-notification title](../../../packages/actions/src/CreateAction.php) | [`CreateAction.php`](../../../vendor/filament/actions/src/CreateAction.php) | done | — | [diverges](#the-strings-are-literals-not-translation-keys) |
| [`CanCustomizeProcess`](../../../packages/actions/src/Concerns/CanCustomizeProcess.php) (`using()` / `process()`) | [`Concerns/CanCustomizeProcess.php`](../../../vendor/filament/actions/src/Concerns/CanCustomizeProcess.php) | done | — | — |
| [URL mode — the host-hook fallthrough](../../../packages/actions/src/Concerns/CanOpenUrl.php) | [`Concerns/CanOpenUrl.php`](../../../vendor/filament/actions/src/Concerns/CanOpenUrl.php) `getUrl()` | done | — | [detail](can-open-url.md#geturl-carries-the-host-fallback-the-new-tab-introspection-helper-is-missing) |
| [Modal mode — the `action()` body that persists a new record](../../../packages/actions/src/CreateAction.php) | [`CreateAction.php`](../../../vendor/filament/actions/src/CreateAction.php) | done | — | [diverges](#the-modal-arm-persists-through-the-schemas-record-class) |
| [`modalHeading()` / `modalSubmitActionLabel()`](../../../packages/actions/src/CreateAction.php) | [`CreateAction.php`](../../../vendor/filament/actions/src/CreateAction.php) | done | — | [diverges](#the-strings-are-literals-not-translation-keys) |
| [`groupedIcon('heroicons:plus-20-solid')`](../../../packages/actions/src/CreateAction.php) | [`CreateAction.php`](../../../vendor/filament/actions/src/CreateAction.php) | done | — | [diverges](#icons-are-literal-heroicon-names-with-no-alias-indirection) |
| [Create-another (`createAnother()`, `canCreateAnother()`, `createAnotherAction()`, `getCreateAnotherAction()`, `preserveFormDataWhenCreatingAnother()`)](../../../packages/actions/src/CreateAction.php) | [`CreateAction.php`](../../../vendor/filament/actions/src/CreateAction.php) | done | — | [diverges](#the-modal-arm-persists-through-the-schemas-record-class) |
| [`shouldClearRecordAfter()`](../../../packages/actions/src/CreateAction.php) | [`CreateAction.php`](../../../vendor/filament/actions/src/CreateAction.php) | done | — | [detail](has-actions.md#clearrecordafter-runs-at-both-of-filaments-sites) |
| [`record(null)`](../../../packages/actions/src/CreateAction.php) (Filament-verbatim in `setUp()`; clears only a `configureUsing()`-bound record) | [`CreateAction.php`](../../../vendor/filament/actions/src/CreateAction.php) | done | — | [diverges](#the-modal-arm-persists-through-the-schemas-record-class) |
| `forceRenderAfterCreateAnother()` / `shouldForceRenderAfterCreateAnother()` | [`CreateAction.php`](../../../vendor/filament/actions/src/CreateAction.php) | skipped | — | [diverges](#two-create-another-members-are-not-ported) |
| `disableCreateAnother()` | [`CreateAction.php`](../../../vendor/filament/actions/src/CreateAction.php) | skipped | — | [diverges](#two-create-another-members-are-not-ported) |
| [`relationship()` / `getRelationship()`](../../../packages/actions/src/CreateAction.php) | [`CreateAction.php`](../../../vendor/filament/actions/src/CreateAction.php) | done | — | [semantics](#the-created-record-joins-the-relationship-in-the-same-flush) · [semantics](#a-create-whose-form-picks-another-owner-still-lands-under-the-relationship-and-is-probed-on-the-pick) |
| The `BelongsToMany` pivot-column split inside the relationship arm | [`CreateAction.php`](../../../vendor/filament/actions/src/CreateAction.php) | skipped | — | The whole pivot surface is skipped one layer down — a Doctrine association table carrying payload is an entity of its own, so there are no pivot columns to split out of the data ([`table.md`](../tables/table.md#surface)) |

## Semantics

### The created record joins the relationship in the same flush

`getRelationship()` walks three hops, Filament-verbatim: the action's own `relationship()` closure, then [the table's](../tables/has-query.md#the-relationship-query-is-the-base-query-rooted-at-the-target), then the host's [`getDefaultActionRelationship()`](interacts-with-actions.md#the-panel-level-default-hooks-are-on-the-contract-the-authorization-pair-deferred).
An action inside a relation-scoped table therefore needs no configuration at all, and one that names its own owner overrides the table it sits in.

The arm runs **before the mapping** in `createRecord()`, and again after it.
The attach before is what the record check needs: `getData()` ends by validating the record and [looking its assigned key up](../schemas/can-be-validated.md#a-taken-assigned-key-is-a-field-error-not-a-unique-violation), and an identifier field the association fills reads **unset** until the join is made.
So the record is constructed first — `PropertyAccessor::constructRecord()`, what `getData()` does for a class-string record, so a constructor that requires arguments is refused by name — attached, and only then mapped, with the schema bound to that record for the mapping alone and back to the class in a `finally`, so a refused create re-renders exactly as it did.
An application's own record constraints see the owner's half too: a `UniqueEntity` over it went from blind — a 500 — to a 422.
When the mapping refuses a **many-to-many** create, the record is detached from the owner's collection on the way out, since nothing else takes it out again; a one-to-many attach touches no owner collection.
The attach **after** keeps the relationship winning over a form field that maps the same association.
With no relationship in force the body is the one `getData()` line it always was, under the class binding — preceded by a check that refuses a class whose constructor requires arguments with the relationship arm's own `constructRecord()` message and advice, *build it in the action's using() closure*, where `getData()` answered with the schema's *through record()*, which an action cannot follow.
And the order still serves `persist()`: Doctrine's `AssignedGenerator` throws `EntityMissingAssignedId` the moment it reads an unset identifier field, so a record whose identifier association *is* the relationship cannot be persisted before it is attached.
[`CreateRecord`](../panels/create-record.md) joins a parent the same way under a nested page.
`persist()` stays explicit because the membership write is not a cascade: the owner's association may declare none, and an unpersisted record that a collection merely holds is not inserted by a mapping that does not cascade.
The attach runs on the object graph rather than as its own write — [`AssociationReader::attach()`](../schemas/association-reader.md) sets the owning collection or the target's mapped-by field through the property accessor — so the single `flush()` writes the record and its membership together, and a failure of either rolls back both.

Filament's three-way branch collapses to two.
It splits `BelongsToMany` (save with pivot data) from `HasOneOrManyThrough` (save the record alone) from everything else (save through the relation), because those arms differ in Eloquent.
Here they do not: the pivot surface is skipped whole (the row above), `through` relations have no Doctrine equivalent, and the reader already knows from the mapping which of the two shapes the write takes.
So the port branches only on whether a relationship resolved at all.

### A create whose form picks another owner still lands under the relationship, and is probed on the pick

A relation-scoped create whose form maps the relationship's own association — a pantry's kettle created through a schema that also carries a `Select::relationship('pantry')` — and picks another owner is recorded rather than refused.
The attach after the mapping makes the relationship win, so the record lands under the owner the relationship names, which is Filament's outcome too.
But the record check ran while the form's pick still stood, so the taken-key lookup asked about the **picked** owner: a key free under the relationship's owner and taken under the pick is refused as *"This value is already used."*, and the converse — free under the pick, taken under the relationship's owner — passes the check and meets the database's constraint at the flush, which follows from the order and is unpinned.
It is a misconfiguration, since the relationship already supplies the field, and refusing it by name would mean inspecting the form for an association the mapping names.
Pinned by [`CreateActionRelationshipKeyTest`](../../../packages/actions/tests/Integration/CreateActionRelationshipKeyTest.php) and, on a nested create page, by panels' [`CreateRecordTakenKeyTest`](../../../packages/panels/tests/Integration/Resources/Pages/CreateRecordTakenKeyTest.php).

### A key the modal leaves blank is refused by name after `persist()`

`createRecord()` asks [`AssociationReader::hasBlankAssignedIdentifier()`](../schemas/association-reader.md#a-blank-assigned-identifier-is-read-after-persist-where-a-record-is-created) between `persist()` and `flush()`, in both arms, on the mounted schema's own reader, rebound to the manager the flush writes through (next section).
A record whose assigned key still holds a blank string there has a key field the modal does not map, since the record check refuses a mapped one as a field error ([the rule](../schemas/can-be-validated.md#a-blank-assigned-key-is-refused-before-anything-is-written)), and every `prePersist` callback and listener has had its chance to fill it.
So it is a developer error, and the action throws a `LogicException`: *The [create] action built a [App\Entity\Seat] whose identifier is still blank after persist(), so it was not saved.
Give every identifier field a field in the action's schema() — a Hidden one for a value the user should not type — or fill it in a prePersist listener.*
Nothing is flushed.
Before the throw, a many-to-many owner gives the record back through the reader's `detach()`, as the mapping's own refusal already does, and the record is detached from the manager, so a later flush in the same request does not insert it.
Without the read, the row would be written with its blank key half.
Pinned by [`CreateActionBlankKeyTest`](../../../packages/actions/tests/Integration/CreateActionBlankKeyTest.php), which also pins that a global `prePersist` listener filling the key lets the create save, and that a blank key field the modal maps on a class carrying a `prePersist` callback is the field error rather than this refusal; the many-to-many hand-back is pinned by tables' [`CreateActionRelationshipTest`](../../../packages/tables/tests/Integration/CreateActionRelationshipTest.php).

Doctrine makes one exception to the detach.
A record whose whole key flattens to `''` — a single derived identity whose target's single string key is still `''` — is filed under an identity hash of `''`, which Doctrine can neither detach nor remove, the shape [`ReplicateAction` already meets](replicate-action.md#a-replica-keeps-its-parent-and-one-whose-key-is-still-incomplete-is-refused-by-name).
There the `detach()` raises Doctrine's *Entity has no identity, therefore remove from identity map cannot be performed* in place of the named refusal; the action catches it and throws the named refusal all the same.
Nothing is written, since nothing flushes after the refusal, but that record stays scheduled for the rest of the request.
Pinned by [`CreateActionBlankIdentityHashTest`](../../../packages/actions/tests/Integration/CreateActionBlankIdentityHashTest.php), over a record whose no-argument constructor builds the record it derives from; panels' create page catches the same refusal, pinned by its [`CreateRecordBlankIdentityHashTest`](../../../packages/panels/tests/Integration/Resources/Pages/CreateRecordBlankIdentityHashTest.php).

A key component still **null** never reaches the read: Doctrine's `AssignedGenerator` refuses it inside `persist()` with its own `EntityMissingAssignedId` (*Entity of type … is missing an assigned ID for field '…'*), once every `prePersist` filler has run, and the action lets that refusal through unchanged.
On the relationship arm the record is already in a many-to-many owner's collection by then, and left there it would fail a later flush in the same request on *A new entity was found through the relationship*.
So the hand-back runs in a `finally` around `persist()` and the read, so whatever fails there — Doctrine's refusal or the named one — gives the record back.
Pinned by [`CreateActionMissingAssignedIdTest`](../../../packages/actions/tests/Integration/CreateActionMissingAssignedIdTest.php).

A schema that switches the key rules off with [`validateRecordKey(false)`](../schemas/can-be-validated.md) skips the blank rule.
A blank key field over a non-nullable property left at the `required()` forms derives for it is still refused under the field, since `required()` trims, so only a field that is not required — one declaring `required(false)`, or one over a nullable property — reaches the save blank, and what happens there depends on what the blank reaches the record as.
A blank that reaches it as a **string** — whitespace, which no text field trims by default — meets this refusal, where the advice to give every identifier field a field would be wrong.
The message then closes with *The schema switches the key rules off with validateRecordKey(false), so a key field it maps is not refused while blank; switch them back on to have the blank field refused under it.*
With the rules on, the message is unchanged.
Pinned by [`KeyAdviceTest`](../../../packages/actions/tests/Integration/KeyAdviceTest.php), beside the plain arm's construction refusal naming `using()`.
An **empty** field dehydrates to `null` instead: over a nullable property it is Doctrine's `EntityMissingAssignedId` above, never this message, and over a non-nullable property, on a field that declares `required(false)`, the save fails earlier, at the mapping, with Symfony's `InvalidTypeException`, since `null` cannot be written there.
The reader's [own section](../schemas/association-reader.md#a-blank-assigned-identifier-is-read-after-persist-where-a-record-is-created) says why a null never reaches its read.

### The record check reads the container's reader, rebound; the relationship arm keeps the action's own

The reader the record check and the blank-key read go through is the mounted schema's own — the container's `sylament_schemas.association_reader` service, or whatever a host set on the schema — rebound to the manager the flush writes through with [`AssociationReader::withEntityManager()`](../schemas/association-reader.md), a clone, and set back on the schema for the mapping.
A `new AssociationReader($manager)` built there instead would never reach a subclassed or decorated reader service.
The relationship arm's `attach()`, `detach()` and `describeRelationship()` still go through the action's **own** reader — the container's, on its default manager — and that is recorded rather than changed: they read the owner's mapping and write the object graph through the property accessor, touching no unit of work but the `contains()` an attach's identifier guard asks, and a relation-scoped table's base query was built through that same default reader, so a second manager over the same mapping reads the same answer.
Moving them onto the rebound reader would be a behaviour change no case needs.

## Divergences

### The modal arm persists through the schema's record class

Filament builds the record with `new $model` and `$record->fill($data)`, then calls `$schema->model($record)->saveRelationships()` in a second pass.
Doctrine has no `fill()` and no second pass, so the port maps through the schema instead: the body re-points the mounted container at the action's record **class** and calls [`Schema::getData()`](../schemas/belongs-to-record.md) with the validated — and, where `mutateDataUsing()` asked, mutated — array, which constructs the record, writes every declared path onto it, answers the record's own constraints, and hands it back to be persisted and flushed through [`getEntityManagerForRecord()`](can-use-database-transactions.md#the-transaction-trio-resolves-a-doctrine-connection-instead-of-a-facade).

**A key `mutateDataUsing()` adds that no component maps is refused by name.**
`Schema::getData()` writes exactly the paths in [`getRecordPathMap()`](../schemas/belongs-to-record.md) — the paths the container's own components declare, hidden and non-dehydrated ones included — so `mutateDataUsing()` can *change* a value a component maps but cannot *introduce* one.
Filament's Eloquent `$record->fill($data)` mass-assigns any key the model permits, which is why `mutateFormDataUsing(fn ($data) => [...$data, 'position' => …])` is an idiom upstream.
Dropped in silence, such a key would fail a non-nullable column at flush and let a defaulted one quietly take its default.
So the first statement of the default write asks [`Schema::findUnmappedAdditions()`](../schemas/belongs-to-record.md) for every leaf path the hook's answer adds to the array it was handed — read back through `HasData::getUnmutatedData()` — that no component maps, and refuses a non-empty answer before anything is built:

> `The [%s] action added [%s] in mutateDataUsing(), but no component of its schema maps those paths, so the default write would drop them. Map each with a component — a Hidden one for a value the user should not type, with required(false) where the record would otherwise require it — or take the write over with using().`

The paths are dotted and comma-separated (`body, meta.source`), as Eloquent's *Add fillable property [house, year]* lists them; no value is printed.
Four shapes pass.
A key the hook **overwrites** was in the array it was handed, so it is no addition.
A key a client typed into the mounted action's data bag is in that array too — the bag accepts any nested path and `getState()` keeps it — so it passes through, unrefused and unwritten, and no client can force the refusal.
A key a hidden or `dehydrated(false)` component maps is covered by the map, so the hook's value is written.
And `using()` replaces the default write wholesale, so it is never guarded; neither is a plain `Action` whose own closure reads a hook's key.
Writing an unmapped path instead was never an option: the data bag is client-writable, so that would be mass assignment.
Filament has no counterpart — `fill()` writes the key, or with `$fillable` declared drops it in silence unless `preventSilentlyDiscardingAttributes()` is on — so this is that strict Eloquent branch, always on and narrowed to what a hook added.
The port's shape for the job stays a component that owns the path — a `Hidden::make('position')->default(…)` in the create schema, which is what the demo's sections card does.
The guard is one trait, [`RefusesUnmappedData`](../../../packages/actions/src/Concerns/RefusesUnmappedData.php), and the same call opens [`EditAction`](edit-action.md#the-modal-arm-maps-the-validated-state-onto-the-bound-record)'s and the modal arm of [`ReplicateAction`](replicate-action.md#a-key-mutatedatausing-adds-that-no-component-of-the-modal-maps-is-refused-by-name)'s default writes.
Pinned by [`UnmappedHookDataTest`](../../../packages/actions/tests/Integration/UnmappedHookDataTest.php).

Four things about that body are deliberate.

**The re-point is load-bearing, not a belt.**
The host seeds a mounted action's schema with a record ([`HasActions`](has-actions.md#the-per-mount-schema-is-seeded-with-the-actions-record-and-the-dotted-operation)), and for a host-level action — one no schema component owns — that seed reads `getRecord(withDefault: true)`, i.e. [the whole chain](interacts-with-record.md#the-record-chain-reaches-the-schema-the-component-and-the-host) down to the host's [`getDefaultActionRecord()`](has-actions.md#the-getdefaultaction-host-override-hooks-are-ported-the-authorization-pair-deferred).
On a record page that last hop answers the page's **record**, so the seed is the very object a create modal must not write into: `$schema->record($recordClass)` in the body is what re-points the container at the class, and it is the only thing standing between a create action hosted beside an edit form and the record being edited.

`setUp()`'s `record(null)` is Filament-verbatim and narrower than it reads.
The property starts `null` and `getRecord()`'s fallback chain never consults it, so the host default is inherited with or without the line.
What it clears is a record bound by a `configureUsing()` closure registered on `Action` or another ancestor: [`ComponentManager::configure()`](../support/component-manager.md) walks the hierarchy root-first, running each class's registrations at that class's position, so an ancestor's closure has already run when `CreateAction::setUp()` fires.

**`saveRelationships()` is not ported, and nothing replaces it.**
Filament needs a second pass because Eloquent must know the parent's key before it can save a `HasMany`; `getData()` writes the associations as part of the mapping and Doctrine orders the inserts itself at flush, so there is nothing left to do after `persist()`.
The absence is the port working, not a gap.

**The create-another branch sends its own notification.**
`halt()` returns from [`callMountedAction()`](has-actions.md#callmountedaction-drops-rate-limiting-and-authorization) before that method's own dispatch block, so the branch calls `callAfter()` and `sendSuccessNotification()` itself, clears the record, re-fills the schema from the class, merges back whatever `preserveFormDataWhenCreatingAnother()` kept, re-hydrates without re-firing the hydration hooks, null-fills and dispatches the client-side state reset.
Filament's trailing `return;` after `halt()` is kept for readers even though `halt()` throws.
The `Select` create-option body reached the same shape first and omits the notification, which is that path's one parity miss rather than a decision.

**The default persistence step is a typed private method, not an inline closure.**
Filament writes it inline as `function (array $data, …)`, and the port cannot: PHPStan 2.2 reads no `@param` docblock on a closure — not above the expression, not above the assignment, and not as `@phpstan-param` — so the closure's `array $data` stays `array<mixed, mixed>` and cannot be handed to `getData(array<string, mixed> $state)` at level max without the inline `@var` suppression the repo forbids.
`private createRecord(array $data, Schema $schema, string $recordClass): object` carries those types on a real signature, and the [`Invoker`](../support/component.md) resolves a first-class callable's parameters exactly as it resolves a closure's, so `using()`'s injection contract is unchanged: `data` off the action, `schema` / `form` and `recordClass` off the `process()` parameter map.

`successNotification()` and `successNotification(null)` work here as on every prebuilt action — the object arm replaces the title-only toast, `null` suppresses it — through the `CanNotify` row in [action.md](action.md), which stays in progress for the members neither arm needs.

### Two create-another members are not ported

`forceRenderAfterCreateAnother()` and its reader defeat a Livewire partial-render optimisation: Livewire can skip re-rendering a component whose props did not change, and a create-another run that resets the form back to the values it started with can look unchanged.
UX LiveComponent has no such optimisation — every action call re-renders — so the setter would be a no-op that reads as meaningful, which is the shape the repo's rules forbid.
It is **skipped**, not blocked: nothing will make it necessary.

`disableCreateAnother()` is `@deprecated` upstream in favour of `createAnother(false)`, and the port ships no deprecated surface.

### The strings are literals, not translation keys

Filament reads every user-visible string through `__('filament-actions::create.single…')`.
The port sets the English literals directly, read from [`resources/lang/en/create.php`](../../../vendor/filament/actions/resources/lang/en/create.php): `New :label` for the trigger, `Create :label` for the modal heading, `Create` for the submit button, `Create & create another` for the extra footer button and `Created` for the success toast — because no translation catalogue is ported yet (the `Translations` row in [README](README.md#supporting-surface) tracks it).

The two `:label` interpolations become string interpolation over [`Action::getRecordLabel()`](interacts-with-record.md#the-record-chain-reaches-the-schema-the-component-and-the-host) and `getTitleCaseRecordLabel()`, which is why they are closures rather than literals: neither label is known until the action is hosted.
Both carry a fallback Filament does not have — when nothing names a record class the label is `null`, and Filament would render a trailing-space `New ` and `Create `, so the port falls back to the bare verb `Create` for each.
That fallback is reachable only by an action with no record class from any source, which is a misconfiguration everywhere except url mode, where the trigger's text is all that renders.

### Icons are literal heroicon names with no alias indirection

Filament resolves every prebuilt action's icon through `FilamentIcon::resolve(ActionsIconAlias::CREATE_ACTION_GROUPED) ?? Heroicon::Plus`, so an app can re-skin all create actions at once.
`ActionsIconAlias` is unported (see [README](README.md#supporting-surface)) and support's `IconManager` is not reachable from inside an action's evaluated closures — the [Invoker's injected set](../support/component.md) carries no icon manager and the container alias is private — so the port sets the literal name the alias falls back to: `groupedIcon('heroicons:plus-20-solid')`.

The `-20-solid` suffix is not an embellishment — it is what the enum case resolves to.
`Heroicon` is a `ScalableIcon`, so an **unprefixed** case picks its set from the render size (`getIconForSize()`), and every prebuilt action's icon lands at the `generate_icon_html()` default of `IconSize::Medium`, i.e. the mini 20px solid set (`heroicon-m-plus` → Iconify `heroicons:plus-20-solid`).
A literal name carries no such adaptivity, so the port has to spell the resolved set.
An `Outlined*` case stays outline at every size and keeps the bare name — which is why `getModalIcon()`'s confirmation fallback is `heroicons:exclamation-triangle`, matching `Heroicon::OutlinedExclamationTriangle`.

The *placement* is Filament's and worth not "fixing": a create action carries a **grouped** icon only, so it shows a plus inside a dropdown and none as a header button.
The port keeps that split because it can — [`HasGroupedIcon`](action.md#hasgroupedicon-narrows-its-icon-type-to-string) is ported, and the grouped trigger reads `component.getIcon(component.getGroupedIcon)`, i.e. the plain icon wins and the grouped one is its fallback.
Setting `icon()` instead (what [`EditAction`](edit-action.md#one-plain-icon-replaces-filaments-table-and-grouped-pair) is forced into by the missing `HasTableIcon`) would put a plus on every trigger Filament leaves bare.

### Two props widen Filament's `?Closure` to the closure's own result

`$modifyCreateAnotherActionUsing` is `Action|Closure|null` here where vendor `CreateAction.php:28` has `?Closure`, and `$preserveFormDataWhenCreatingAnotherUsing` is `array|Closure|null` where vendor `:30` has `?Closure`.
Both are Filament-exact in *name* and in what a consumer passes; the widening is the level-max gate rather than an API change.

The reason is [`evaluate()`'s generic](../../internals/php.md#evaluates-generic): it infers `T` by subtracting `Closure` from the property's declared union, so a `?Closure` prop evaluates to `?Closure` — the read then has to be narrowed by hand at every call site, which is the shape an inline `@phpstan-ignore` grows out of.
Declaring the union the closure *results in* states the contract instead: the property holds either that value or a closure producing it, and `evaluate()` answers the value.
[`HasWizard`](../../../packages/actions/src/Concerns/HasWizard.php) set the precedent with `Wizard|Closure|null $modifyWizardUsing`; every prop of this shape in the port follows it.

A literal is a legitimate value on both, and Filament's is not: passing `modifyCreateAnotherActionUsing(Action::make(…))` works here and would be a type error there.
That is a widening, never a narrowing, so migrating code is unaffected.
