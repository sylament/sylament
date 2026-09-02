# InteractsWithRelationshipTable (panels)

[`Sylament\Panels\Resources\Concerns\InteractsWithRelationshipTable`](../../../packages/panels/src/Resources/Concerns/InteractsWithRelationshipTable.php)
— one owner record's association as a table, and everything that follows from
the records also having a **resource** of their own: the association the table is
seeded with, the title the tier carries, and — with `$relatedResource` set — the
form, infolist, labels, columns and page URLs that resource answers with. It
ports the non-authorization half of
[`Resources/Concerns/InteractsWithRelationshipTable.php`](../../../vendor/filament/filament/src/Resources/Concerns/InteractsWithRelationshipTable.php).

Both hosts of the tier compose it: the embedded
[`RelationManager`](relation-manager.md), where these members lived until this
epic, and the routed [`ManageRelatedRecords`](manage-related-records.md) page,
which is why they moved. The extraction is Filament's own factoring, under
Filament's own name.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`Resources/Concerns/InteractsWithRelationshipTable.php`](../../../vendor/filament/filament/src/Resources/Concerns/InteractsWithRelationshipTable.php)
  — the whole trait
- [`Resources/Pages/ManageRelatedRecords.php`](../../../vendor/filament/filament/src/Resources/Pages/ManageRelatedRecords.php),
  [`Resources/RelationManagers/RelationManager.php`](../../../vendor/filament/filament/src/Resources/RelationManagers/RelationManager.php)
  — the two hosts, and the `getDefaultActionUrl()` Filament duplicates in both
- [`Resources/Resource.php`](../../../vendor/filament/filament/src/Resources/Resource.php)
  `configureTable()` — the label stamp the related arm reproduces

## Surface

Every point of this trait. _Blocked by_ names what has to land first. _Detail_
carries three links, any of them: **detail** → the write-up in
[Details](#details), deleted once the row is **done**; **semantics** → the
permanent note in [Semantics](#semantics); **diverges** → the permanent note in
[Divergences](#divergences). Rows never disappear — only their detail sections
do.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`$relationship` / `getRelationshipName()`](../../../packages/panels/src/Resources/Concerns/InteractsWithRelationshipTable.php) | the same static and getter | done | — | [diverges](relation-manager.md#a-manager-naming-no-relationship-throws-rather-than-reaching-for-a-related-resource) |
| [`getRelationship()`](../../../packages/panels/src/Resources/Concerns/InteractsWithRelationshipTable.php) | `getRelationship(): Relation \| Builder` | done | — | [semantics](#the-relationship-is-a-value-object-built-per-call-not-a-query) |
| [`$relationshipTitle` / `getRelationshipTitle()`](../../../packages/panels/src/Resources/Concerns/InteractsWithRelationshipTable.php) | the same static and getter | done | — | [diverges](#the-title-is-an-instance-method-and-the-static-title-chain-never-reaches-the-related-resource) |
| [`getHumanizedRelationshipName()`](../../../packages/panels/src/Resources/Concerns/InteractsWithRelationshipTable.php) | the inline `str()->kebab()->replace()->ucwords()` chain, written twice | done | — | [semantics](#the-humanized-name-is-named-because-three-callers-read-it) |
| [`$relatedResource` / `getRelatedResource()`](../../../packages/panels/src/Resources/Concerns/InteractsWithRelationshipTable.php) | the same static and getter | done | — | — |
| [`form()`](../../../packages/panels/src/Resources/Concerns/InteractsWithRelationshipTable.php) / [`infolist()`](../../../packages/panels/src/Resources/Concerns/InteractsWithRelationshipTable.php) — the related arms | the same two | done | — | [semantics](#the-related-arms-delegate-and-the-no-related-arm-is-the-identity) |
| [`makeTable()`](../../../packages/panels/src/Resources/Concerns/InteractsWithRelationshipTable.php) — the relationship seed | `makeTable()`'s `->relationship(...)` | done | — | [semantics](relation-manager.md#the-relationship-is-seeded-in-maketable-and-table-is-never-redeclared) |
| [`makeTable()`](../../../packages/panels/src/Resources/Concerns/InteractsWithRelationshipTable.php) — the related-resource stamp | `makeTable()`'s `$relatedResource` block + `Resource::configureTable()` | done | — | [diverges](#the-related-stamp-is-configuretables-four-labels-not-filaments-double-set) |
| [`getDefaultActionUrl()`](../../../packages/panels/src/Resources/Concerns/InteractsWithRelationshipTable.php) — the related create / edit / view arms | the byte-identical copies on both hosts | done | — | [diverges](#the-url-arms-are-hoisted-once-and-carry-the-owner-explicitly) |
| `defaultForm()` / `defaultInfolist()` — the two-column wrappers | the same two | skipped | — | [diverges](relation-manager.md#the-defaultform-two-column-wrapper-does-not-port) |
| [`makeTable()`](../../../packages/panels/src/Resources/Concerns/InteractsWithRelationshipTable.php) — the `recordAction()` / `recordUrl()` row-click seeding | the two closures walking `view` / `edit` | done | — | [diverges](#both-seeding-probes-clone-the-action-where-vendor-clones-in-one-of-four) · [diverges](#the-table-side-querystringidentifier-is-deliberately-not-stamped) |
| [`getTableQueryStringIdentifier()`](../../../packages/panels/src/Resources/Concerns/InteractsWithRelationshipTable.php) | `makeTable()`'s `->queryStringIdentifier(Str::lcfirst(class_basename(static::class)))` | done | — | [diverges](#the-table-side-querystringidentifier-is-deliberately-not-stamped) |
| [`makeTable()`](../../../packages/panels/src/Resources/Concerns/InteractsWithRelationshipTable.php) — the active-tab query stamp | `->modifyQueryUsing($this->modifyQueryWithActiveTab(...))` | done | — | stamped before the related-resource hop, so a tab scopes the query that resource then configures |
| [`HasTabs` on the trait](../../../packages/panels/src/Resources/Concerns/HasTabs.php) | `use HasTabs;` | done | — | [has-tabs.md](has-tabs.md) — composed here rather than on each host, so a relation manager and a `ManageRelatedRecords` page offer the same record filters. The insert stays [ungrouped](#the-composition-clauses-are-ungrouped-and-asymmetric-between-the-hosts) for the same Rector reason the `insteadof` blocks do |
| `authorizeReorder()` in `makeTable()`, `getAuthorizationResponse()` and the 30-odd `can*()` / `get*AuthorizationResponse()` members | the same cluster | todo | the authorization epic | [detail](#the-authorization-half-of-the-trait-is-inventoried-not-ported) |
| `checkPolicyExistence()` / `skipAuthorization()` / `shouldCheckPolicyExistence()` / `shouldSkipAuthorization()` | the same four | todo | the authorization epic | [detail](#the-authorization-half-of-the-trait-is-inventoried-not-ported) |

[`Unit/Resources/Concerns/InteractsWithRelationshipTableTest`](../../../packages/panels/tests/Unit/Resources/Concerns/InteractsWithRelationshipTableTest.php)
covers the trait against both host shapes over a hand-wired registry: the
nameless-relationship refusal **with a related resource set**, all three title
sources, the form/infolist delegation and its absence, the table stamp — columns,
labels and the related resource's own record actions, with the host's `table()`
hook applying on top — and six `getDefaultActionUrl()` cases: the create and edit
URLs minted under the owner, a related resource nesting under nothing carrying no
owner at all, and the three nulls (no related resource, another record class, a
page the related resource does not register).
The two hosts' own suites are what pin the composition:
[`RelationManagerTest`](../../../packages/panels/tests/Unit/Resources/RelationManagers/RelationManagerTest.php)
(Unit and Integration, unchanged through the extraction) and
[`ManageRelatedRecordsTest`](../../../packages/panels/tests/Integration/Resources/Pages/ManageRelatedRecordsTest.php).

## Details

### The authorization half of the trait is inventoried, not ported

Filament's trait is mostly authorization: `getAuthorizationResponse()` with its
related-resource delegation, twenty `get*AuthorizationResponse()` methods, their
`can*()` wrappers, the two policy-existence switches, and the
`authorizeReorder()` seed in `makeTable()`. None of it ships, with the rest of
the authorization epic. The consequence is stated in full on
[relation-manager.md](relation-manager.md#every-registered-manager-is-reachable-by-anyone-until-the-authorization-epic)
and applies unchanged to the page host: **a relationship table is reachable and
writable by anyone who can reach its host.**

One arm of the deferral is worth naming separately, because it is the only place
the related resource would change an *answer* rather than a gate: Filament's
`getAuthorizationResponse()` forwards to the **related resource's** policy when
the record belongs to it. That forwarding lands with the epic; nothing about the
trait's shape has to change for it.

## Semantics

### The relationship is a value object built per call, not a query

`getRelationship()` answers
`new Relationship($this->getOwnerRecord(), static::getRelationshipName())` — the
owner and the association name, nothing else — where Filament's answers an
Eloquent `Relation` object, which is already a query. Everything the mapping says
about the association (target, side, `mappedBy`, order) is read on demand by
[`AssociationReader`](../schemas/association-reader.md), so the value object
stays metadata-free and cheap enough to rebuild per call. The table seeds itself
with a **closure** returning it, so the owner is resolved when the table reads
rather than when it is built.

### The humanized name is named because three callers read it

`getHumanizedRelationshipName()` — the relationship name kebab-cased, dashes
spaced, title-cased word by word — is a `protected static` of its own, where
Filament writes the same four-call chain twice (in `getRelationshipTitle()` and
in `RelationManager::getTitle()`). Three callers read it here: those two and
[`ManageRelatedRecords::getNavigationLabel()`](manage-related-records.md), which
Filament routes through `getRelationshipTitle()` instead. Naming it once is what
keeps a tab label and a band label from drifting apart; the expression itself is
Filament's exactly, [pluralizing nothing](relation-manager.md#the-title-fallback-humanizes-the-relationship-name-without-pluralizing-it).

### The related arms delegate, and the no-related arm is the identity

`form()` and `infolist()` hand the schema to the related resource's own method
when one is named, and otherwise **return the schema untouched** — which is
exactly what `RelationManager::form()` did before the extraction, so every
subclass override, including the demo's two managers, is unaffected. A host with
no related resource therefore mounts an action modal on the host's own `form()`
override, or on an empty schema when it declares none: it does not error, and
`ManageRelatedRecordsTest` pins that arm because no shipped page reaches it.

## Divergences

### The title is an instance method, and the static title chain never reaches the related resource

`getRelationshipTitle()` is an **instance** method here where Filament's is
static, because its middle arm reads the related resource's
`getTitleCasePluralRecordLabel()` — and a resource is a
[service reached through the registry](README.md#resource-is-a-service-not-a-static-class-string-registry),
which no static can reach. The three sources are Filament's:
`static::$relationshipTitle`, else the related resource's title-case plural
label, else the humanized relationship name.

The consequence is that the **static** title chain does not gain the related arm.
`RelationManager::getTitle(object $ownerRecord, string $pageClass)` is static
(the tab bar reads it off the class before any instance exists) and keeps its
humanized fallback, so a manager with a related resource and no `$title` shows
the humanized relationship name where Filament would show the related resource's
plural label. Every **instance** path — the MRR page's title, its breadcrumb, the
table heading — does use the related arm. Instance-izing the static chain would
ripple through the tab component and the whole `getRelations()` read; the narrow
divergence is the cheaper half, and it disappears if that chain ever moves.

### `getRelationshipName()` stays two-arm: a related resource is not a fallback

Filament's has three arms — the static, else
`getRelatedResource()::getParentResourceRegistration()->getRelationshipName()`.
The middle arm reads the related resource's registration **statically**, which
our instance-side registration cannot answer, and the method is itself static
because the static title chain above calls it. So the port keeps two arms: the
static, else the
[`LogicException`](relation-manager.md#a-manager-naming-no-relationship-throws-rather-than-reaching-for-a-related-resource)
that already shipped — **`$relationship` stays mandatory even with a related
resource set**, which is the M6 · 1 divergence widened rather than reversed.

The affordance lost is narrow: Filament's own generator writes `$relationship`
into every generated manager and page whether or not a related resource is
named, so the fallback exists for a hand-written host that declares one and not
the other. The refusal names the class and the property, and the message is
host-neutral ("The relationship table [%s] names no relationship") because the
trait now serves an embedded manager and a routed page equally.

### The related stamp is `configureTable()`'s four labels, not Filament's double set

With `$relatedResource` set, `makeTable()` stamps `recordLabel`,
`pluralRecordLabel`, `recordTitleAttribute` and `recordTitle` as callables and
then hands the table to the resource's `table()` — which is
`Resource::configureTable()`'s body, minus its `authorizeReorder()` seed, and the
same stamp [`ListRecords::table()`](README.md#the-list-pages-table-is-stamped-query-first-and-resource-last)
already makes. Filament sets `modelLabel` and `pluralModelLabel` *and then* calls
`configureTable()`, which sets both again along with the other three: a dead
double set, not ported.

One consequence rides in from the list page unchanged: because `recordTitle` is
stamped **unconditionally** as a callable, the table's own
`recordTitleAttribute` branch can never be reached — the title read finds a
`recordTitle` closure first. That is pre-existing `ListRecords` behaviour rather
than something the related stamp introduced, and it is inert (the closure the
resource hands over reads the attribute anyway), but it means a table-level
`recordTitleAttribute()` set by the host's own `table()` hook decides nothing on
a resource-stamped table.

### The URL arms are hoisted once and carry the owner explicitly

Filament writes `getDefaultActionUrl()` **twice**, byte-identical, on
`RelationManager` and on `ManageRelatedRecords`. Ours lives on the trait, once —
Create → the related resource's `create` page, Edit and View → its `edit` / `view`
page with the action's record, each gated on `hasPage()` and on the action's
record class being the related resource's (or null).

Two differences beyond the hoist. Ours passes the **owner record explicitly** as
the related resource's parent parameter, where Filament passes nothing and leans
on `shouldGuessMissingParameters` reading the current request — a mechanism
[that cannot exist here](nested-resources.md#guessing-walks-the-records-own-associations-never-the-request).
The guess flag is passed too (Filament-exact), which only helps a deeper chain.
And the owner is matched against the related resource's parent record class with
`instanceof`, not `::class ===`, so a Doctrine proxy or a subclassed entity still
matches; an exact compare would drop the parameter silently and turn the URL into
a missing-parameter exception.

The owner is merged only when the related resource actually nests under a
resource speaking for the owner. A related resource nesting under **nothing** is
handed no owner at all (its URLs are flat, pinned by a fixture that has no
parent), and a related resource nesting under a **different** parent than the
owner gets none either — so its create URL raises Symfony's
`MissingMandatoryParametersException` rather than answering null. Filament has
the identical hole (it passes no owner anywhere), so this is inventoried rather
than fixed: the honest answer needs the caller to say which record fills the
foreign parameter, which nothing in the tier knows.

### Both seeding probes clone the action, where vendor clones in one of four

`makeTable()` seeds `recordAction()` and `recordUrl()` by walking the table's
`view` and `edit` actions, which M4 · 3 landed on this tier and on
[`ListRecords`](README.md#the-row-click-falls-through-to-the-resources-own-view-or-edit-page)
together — so a row's whole body opens the related record now, not only its Edit
button. The two closures are Filament's, member for member, with one deliberate
correction.

Both probes have to bind the record before they can ask an action whether it is
hidden or where it points, and both walk the action's **group chain** doing it,
because a hidden-or-URL arm reading the record through its group would otherwise
answer for whatever row the chain last carried. Filament writes that record onto
the action the table caches. Across its four probes — two here, two on
`ListRecords` — it does so on a **clone** exactly once, in `ListRecords`'
`recordUrl()`; the other three mutate the shared action, so a probe run for row 7
leaves the cached `edit` action carrying row 7's record for whatever reads it
next. Ours clones in all four, through one shared
`stampRecordOnActionChain(Action $action, object $record): Action` that returns a
`getClone()` and stamps the chain on it.

This is a **fix over upstream rather than a divergence for its own sake**, and it
is recorded so nobody symmetrises it back to Filament's shape: the probes run
once per rendered row, so the shared-mutation version leaves a per-request action
object whose record is the last row's — invisible until something downstream reads
`getRecord()` off the cached action, which the record-title chain does. The three
uncloned vendor probes are a bug we are not obliged to reproduce.

### The Table-side `queryStringIdentifier()` is deliberately not stamped

Filament's `makeTable()` calls `->queryStringIdentifier(Str::lcfirst(class_basename(static::class)))`
on the table object, so the `?page=` parameter of a relation-managed listing is
prefixed by the manager's class name and two listings on one page never share a
page number. The port answers the same question from the **host** instead: it
overrides `getTableQueryStringIdentifier()` with the host class's own basename,
lower-cased at the first letter, and leaves the `Table` setter alone.

That is forced rather than preferred. The prefix is read while composing the
`page` URL mapping, and a mapping is composed inside a `LiveProp` **modifier** —
which UX runs during hydration, before `#[PostHydrate]` has stamped the panel onto
the request. A modifier that built the table to read `getQueryStringIdentifier()`
off it would resolve the default panel and memoise that answer for the rest of the
request. So the identifier has to be readable without a table, and a static method
on the host is the only thing that is.

The `Table` setter still ships as parity surface, and the consequence of the two
halves swapping roles is
[recorded on the tables side](../tables/has-table.md#the-query-string-identifier-is-live-on-the-table-and-dead-in-the-modifiers):
calling `->queryStringIdentifier('foo')` inside a `table()` body changes what
`getIdentifiedTableQueryStringPropertyNameFor()` answers and does **not** change
the query-string parameter, because the mapping was resolved before the table
existed. A tier wanting a different prefix overrides the host hook.

### The composition clauses are ungrouped, and asymmetric between the hosts

Both hosts also compose traits that already declare `makeTable()` (tables'
`HasTable`) and `getDefaultActionUrl()` (actions' `HasActions`), and PHP refuses
trait-vs-trait same-name composition outright, so each host needs an `insteadof`
clause. The two are **not** the same clause, and neither may be written in the
grouped form:

```php
// RelationManager — HasActions is a trait here, so both collisions are trait-vs-trait
use HasTable;
use InteractsWithRelationshipTable {
    InteractsWithRelationshipTable::makeTable insteadof HasTable;
    InteractsWithRelationshipTable::makeTable as makeBaseRelationshipTable;
    InteractsWithRelationshipTable::getDefaultActionUrl insteadof HasActions;
}

// ManageRelatedRecords — HasActions arrives through the parent class
use HasTable;
use InteractsWithRelationshipTable {
    InteractsWithRelationshipTable::makeTable insteadof HasTable;
}
```

`ManageRelatedRecords` needs no URL exclusion because
[the actions host sits on the resource-page base](README.md#the-actions-host-sits-on-the-base-because-a-trait-outranks-an-inherited-method):
a trait on the leaf class outranks an inherited method by ordinary precedence, so
the trait's `getDefaultActionUrl()` wins with no clause at all. The manager
composes `HasActions` itself, so there it is a genuine collision.

**The grouped form (`use HasTable, InteractsWithRelationshipTable { … }`) does not
survive `composer format`, and the failure is a fatal error rather than a lint
complaint.** Rector's `SeparateMultiUseImportsRector` splits a multi-trait `use`
into one statement per trait and keeps only the `Alias` adaptations whose trait
matches the one being split — every `Precedence` (`insteadof`) clause is dropped
silently. The next run then dies with *"Trait method … has not been applied …
because of collision with …"*. PHP accepts an `insteadof` naming a trait inserted
by a **different** `use` statement in the same class, which is the only reason the
ungrouped form works at all. Both hosts carry a short comment saying so, and this
is the shape any future `insteadof` in this repo has to take.
