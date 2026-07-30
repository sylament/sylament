# Roadmap

The build-order overlay on the parity tables. The [progress tables](README.md)
are the breadth ledger — every Filament surface, audited package by package;
this file orders a thin path through them: which vertical slice to build next,
and toward what demo.

Rows here are **epics** — shippable vertical slices of one to a few PRs each —
not files, so the [one-row-per-file rule](README.md#one-row-per-file-one-file-per-thing)
does not apply. An epic's status describes that slice only, never a component's
full parity — the component's own row in the parity tables keeps that — so
nothing has a status in two places. Epics use the shared
[status vocabulary](README.md#statuses), and the
[same-commit rule](README.md#update-the-trail-in-the-same-commit) extends here:
the commit that finishes an epic flips its row and moves the [Now](#now) cursor.

Milestones prove out in the **demo app** — a separate Symfony project in a
sibling checkout, consuming the bundles through Composer path repositories
(`../sylament`, `../sylament/packages/*`). Epics whose Packages column says
**demo** land there rather than in `packages/`; they carry rows here because
the milestone is not demoable without them, but they never appear in the parity
tables.

Milestones are ordered, not dated, and each is independently demoable: if work
stops mid-M2, M1 still demos on its own.

## Now

**M1 · 4 — Slice layout catalog.** The next epic to pick up: `Grid`, `Group` and
`Fieldset` — the remaining basic layout components.

## Pulled forward

Epics taken out of milestone order by decision — recorded here so the thin-path
story stays honest.

| # | Epic | Packages | Delivers | Status |
| --- | --- | --- | --- | --- |
| 1 | Dropdown primitive + ActionGroup base | [support](support/README.md), [actions](actions/README.md) | `<twig:SylamentSupport:Dropdown>` with a bundled Floating-UI Stimulus controller; `ActionGroup` model (eager `getFlatActions()`, visibility composition, propagation), icon-button/button triggers, grouped item rendering. Trigger-view catalog, submenus, badges, table/bulk deferred. | done |
| 2 | Schemas↔actions integration | [schemas](schemas/README.md), [actions](actions/README.md) | Actions as schema children, `prepareAction`/`prepareActionGroup`, `Schema::getAction()`, Section header/footer actions — flips the three blocked Section rows. | done |
| 3 | Actions core polish | [actions](actions/README.md), [schemas](schemas/README.md), [support](support/README.md) | The finishable core around the `Action` base, no prebuilt actions. View variants: `badge()`/`button()`/`grouped()`/`iconButton()`/`link()` switchers with icon-button/link/badge templates (icon-button composes support `IconButton`), `hiddenLabel()`, and the Section label-slot `LINK_VIEW` divergence flipped. General modal presence + content: `modal()`/`hasModal()`/`shouldOpenModal()` (host gate delegates; fixes the auto-run-on-`modalHeading()` gap and `getUrl()`'s missing modal-precedence guard), `modalContent`/`modalContentFooter`, footer as real `Action`s (`modalSubmitAction`/`modalCancelAction`/`extraModalFooterActions`/`getModalFooterActions`/`prepareModalAction`), `getModalAction()` — which also unlocks the dotted descent deferred in schemas' component `getAction()`. Host side: `cachedActions`/`cacheAction()` (closes the two-instance identity gap), bare-method `getAction()` fallback with return-type reflection, `ActionNotResolvableException`, `afterActionCalled()`. `HasMountableArguments` (`__invoke`) with arguments emitted by the trigger templates. Small independents: `HasId`, `CanBeOutlined`, `HasGroupedIcon`, group `extraAttributes`/`extraDropdownAttributes` rendering. A Sylament-only `renderAction(name)` Twig helper (actions `ActionExtension`, mirroring `renderSchema()`): resolves through the context host's `getAction()` (bound + cached), renders nothing when hidden or unresolved; instance rendering stays `renderComponent()`. Deferred: modal chrome (slide-over, sticky panes, alignment, close/click-through/autofocus toggles — support `Modal` surface), authorization, rate limiting, transactions, redirects, events, nested action stack, submenus, icon-alias registry, `toArray()`/`fromArray()`. | done |
| 4 | Modal chrome | [support](support/README.md), [actions](actions/README.md) | The deferred modal surface, wired end to end from `CanOpenModal` through support `Modal` props/CSS/Stimulus: `slideOver()` + `slideOverPosition()`, `stickyModalHeader()`/`stickyModalFooter()`, `modalAlignment()` and `modalFooterActionsAlignment()` (restoring the center-reverse dropped from `getModalFooterActions()`), `modalCloseButton()` with the support header close button, `closeModalByClickingAway()`/`closeModalByEscaping()`, `modalAutofocus()`, `modalClickThrough()`, and `HasExtraModalOverlayAttributes`/`HasExtraModalWindowAttributes`. Plus the three structural decisions it rests on: the dialog-as-container restructure (the `<dialog>` paints the dim around an inner `.sy-modal-window`), full Filament defaults parity (close button on, start-aligned footer, confirmation modals centered and cancel-first), and CSS-only `@starting-style` transitions replacing Alpine's — with the controller's `dismissed` event bound to `unmountAction` so a client-side close stays in server sync. Deferred: the `ModalComponent` global-defaults seam, a `Width` enum (widths stay strings), the icon-alias registry. | done |
| 5 | Nested actions + submenus | [actions](actions/README.md), [support](support/README.md) | The mounted-action stack: multi-slot `$mountedActions` with a `resolveActions()` loop and parent-modal descent through `getModalAction()`, `cachedMountedActions` plus a `#[PostHydrate]` stale-stack self-heal, the nesting parameters back on the host contract (`getMountedAction(?int)`, `callMountedAction(array $arguments)`, `unmountAction(bool\|string\|null $cancelParentActions)` cascade), `HasParentActions`/`CanCallParentAction` + `makeModalSubmitAction()` and the `parentAction` linkage `prepareModalAction()` stamps, `replaceMountedAction()`, host- and nesting-scoped modal ids (`getActionsHostId()`), and the Modals renderer loop. Choreography is **declarative**, not event-driven: per-frame open state is computed server-side by the Sylament-only `isMountedActionModalOpen()`, mirrored into the native `open` attribute so a morph cannot close a live dialog, with parent-overlay dim suppression stamped as `sy-modal-overlaid` and a closing trigger dismissing client-side so the `dismissed` round-trip can carry `cancelParentActions`. Submenus on `ActionGroup`: `getDropdownActionLists()` flattens non-dropdown child groups into divider-separated `sy-dropdown-list` sections and embeds dropdown-in-dropdown with `defaultDropdownPlacement('right-start')` (Filament writes `right-top`, which is not a Floating-UI placement) and trigger-template-default propagation to nested groups, replacing the nested-group `LogicException`; dropdown panels became conditionally scrollable so a submenu is not clipped. Deferred: table/bulk contexts, `#[Url]` deep-link mounting, `boot()` per resolved frame, `resetErrorBag()`, and the per-frame `Schema` (epic 6). Unverified at merge: everything in the modal Stimulus controller and the dropdown scroll CSS — no JS test rig, no browser run. | done |
| 6 | Forms-in-modals, unvalidated | [actions](actions/README.md), [schemas](schemas/README.md), [support](support/README.md) | Actions' `HasSchema` and the mounted-action form pipeline, minus validation: `schema()` on `Action`, host-side `getMountedActionSchema()` binding a per-mount `Schema` into a new client-writable mounted-action data bag (deliberately reversing the server-controlled-slot divergence), `mountedActionHasSchema()` feeding the already-plumbed `shouldOpenModal(checkForSchemaUsing:)` arm, schema fill at mount with the `beforeFormFilled`/`afterFormFilled` hook pair, the `ActionModal` schema slot between content and content footer, `HasData` + the `data` closure-injection arm, `callMountedAction()` merging `$schema->getState()` into `$action->data()` at Filament's pipeline position, and the modal form wrapper + `CanSubmitForm` submit wiring. Boundary: validation lands in M1 · 1 (`getState(afterValidate: …)`, the `ValidationException` keep-modal-open loop) — until then a submit accepts any input. **Shipped differently from this text in three places, and closed two gaps it did not name.** The data bag landed as the **sibling** prop `mountedActionsData.{i}`, not Filament's `mountedActions.{i}.data` — Symfony's getter precedence makes the nested path unreadable ([divergence](actions/has-actions.md#per-mount-form-state-lives-in-a-sibling-bag-not-in-the-stack-descriptor), [rename](renames.md)) — so the client-writable surface is exactly form state and the descriptor stack stayed server-controlled: the narrowest possible reversal, not the one the epic text implies. `hasFormWrapper()` defaults to plain `true` rather than `! isWizard()`, there being no `Wizard`, so a schema-bearing action always renders inside a real `<form>` and `getModalSubmitAction()` never takes its `->action()` arm ([divergence](actions/has-schema.md#hasformwrapper-defaults-to-plain-true)). The schemas [`CanBeDisabled`](schemas/can-be-disabled.md) pair came along as a prerequisite of `disabledSchema()`, plus native `disabled` rendering on both fields. And two adjacent gaps were closed that the epic text did not name: a `live()` field inside a modal now fires `afterStateUpdated` (an explicit `#[PostHydrate]` [priority pair](actions/has-actions.md#the-mounted-action-schema-re-cache-runs-above-callafterschemastateupdated-by-explicit-posthydrate-priorities)), and an `Action` declared inside a mounted-action schema now resolves. **Verified at merge**, unlike epic 5: all five §8 demo-browser checks were run live against the peer demo app and passed — focus survives a foreign re-render (same DOM node, value intact), Enter submits exactly once with no navigation, an empty `required` field blocks submit client-side with zero requests, a nested modal keeps its parent's state with no invalid `<form>` nesting, and no `getDeepData()` `TypeError` across any open/submit/close/nest cycle. That covers the forms-in-modals paths only — the rest of epic 5's modal JS is still unverified. One limitation found and documented rather than fixed: an action inside a mounted-action schema cannot inject `Set`/`Get` ([Filament parity](actions/has-actions.md#set-and-get-are-unavailable-to-an-action-inside-a-mounted-action-schema)). | done |

## Milestones

| # | Milestone | Outcome | Status |
| --- | --- | --- | --- |
| M1 | [Entity CRUD slice](#m1--entity-crud-slice) | A plain Symfony app doing full CRUD on one entity through Sylament components alone. | todo |
| M2 | [Mini admin panel](#m2--mini-admin-panel) | The same CRUD delivered as a Filament-style panel: navigation, a resource, generated pages. | todo |
| — | [Beyond](#beyond) | Unordered candidates, promoted into milestones as M2 closes. | — |

## M1 — Entity CRUD slice

The demo script: _in a plain Symfony app, create an Article through a
declarative Sylament form — live slug generation, validation errors on submit —
see it appear in a Sylament table, edit it, delete it behind a confirm modal,
and get a success notification for each. All defined in PHP the way Filament
defines it._

| # | Epic | Packages | Delivers | Status |
| --- | --- | --- | --- | --- |
| 1 | Form validation vertical | [forms](forms/README.md), [schemas](schemas/README.md), [actions](actions/README.md) | `CanBeValidated` subset on Symfony Validator, shipped in the **constraint vocabulary** (`constraint()`/`constraints()`/`getValidationConstraints()` — [renames](renames.md#laravel-rule-vocabulary-becomes-symfony-constraint-vocabulary)): `required()`/`nullable()`, `length()` + bounds, `email()`/`numeric()` arms, `validationMessages()` baked into constraint options at build time (no separate messages/attributes pipeline; `validationAttribute()` dropped — Symfony messages are value-centric). Schema-tier walk + `validate(): void`; host side on `HasSchemas`: `validateSchema()`, a path-keyed error bag, `ValidationException extends UnprocessableEntityHttpException` riding ux-live-component's 422 re-render, and scoped `clearSchemaValidation()` replacing Livewire's global `resetErrorBag()` (a nested mount keeps the parent's messages). **Sticky re-validation** — deliberate UX divergence from Filament's submit-only model: a failed schema re-validates every request at `PostHydrate(priority: -20)` (below the afterStateUpdated diff), so errors clear field-by-field as the user fixes them. `getState(shouldCallHooksBefore, afterValidate)` now validates with `getStateSnapshot()` split out; the mounted-action retrofit landed the `*FormValidated` hook pair, both keep-modal-open catch arms, and remount/unmount bag cleanup. The `HasValidationConstraints` contract lives in **schemas** (split-repo rule). `FieldWrapper` renders every message for the path plus `aria-invalid` on the inputs. **Verified at merge**: full gates plus five live browser checks against the peer demo — page-form 422 with value/focus preserved, sticky clear via an unrelated debounce request, modal keep-open on invalid submit, fresh frame on remount, and a console clean of everything but the two designed 422 logs. Deferred: the spec's out-of-scope list (comparison/date/unique/exists rules, `regex()`/`in()`, display flags, digit-count lengths, per-field live-validation opt-ins). | done |
| 2 | Entity round-trip | **demo**, [schemas](schemas/README.md), [support](support/README.md) | The `BelongsToRecord` port (Filament: `BelongsToModel`, both tiers) under the **record-only vocabulary** — `record()` binds an instance, raw array, or class-string (declares the class, binds no instance), `getRecordClass()`/`getRecordInstance()`, guarded container fallbacks, root-memoized vs component-re-evaluated closure bindings ([renames](renames.md#the-model-vocabulary-collapses-onto-record)) — plus `HasOperation` (`operation()`/`getOperation()`, the support `Operation` enum, `hiddenOn()`/`visibleOn()`/`disabledOn()`) and the restored closure injection: `$record` by name and by type (`instanceof` replacing Filament's `Model` gate), `$operation`/`$context`. Two Sylament-only mapping edges replace Eloquent's half: `fill($object)` reads the schema's own paths off the object (read-lenient — one `AccessException` catch, object-valued reads skipped), and `getData(): object` validates through `getState()` then maps top-level state onto the bound record or a fresh `getRecordInstance()` (write-strict; `dehydrated(false)` is the page-only-field escape). Demo: `/articles/new` + `/articles/{id}/edit` sharing one `ArticleForm` live component — entity LiveProp hydration, live slug generation on create only (the `$operation` proof in the wild), unique slug via an M1 · 1 `Callback` escape-hatch constraint, `getData()` → `persist`/`flush`, redirect-on-create — with a DAMA-isolated `Articles/` functional suite, the demo's own PHPStan added to the gate (six pre-existing errors frozen in a baseline), and the schemas `RecordRoundTrip` DTO card. **Verified at merge**: full gates in both repos plus six live browser checks — create round-trip with live slug and redirect, in-place edit leaving a single DB row, native client-side block for blank required input, server 422 on a duplicate slug with sticky field-by-field clearing, no slug regeneration on edit, and a console clean but for the designed 422. Deferred: the relationship layer (`saveRelationships()`/`loadStateFromRelationships()`), the Action-side record hop to the schema container (M1 · 6), model-sourced constraints implementation (direction decided — see below), nested record paths and Doctrine-type state casts. | done |
| 3 | Slice field catalog | [forms](forms/README.md), [schemas](schemas/README.md), **demo** | `Select` (native, static options), `Checkbox` and `Toggle` — the stretch item included. Three prerequisites the epic text did not name came with them: schemas' [`OptionStateCast`](schemas/option-state-cast.md), the `labelPrefix` / `labelSuffix` slots on forms' `FieldWrapper` (a boolean field renders its control **inside** the field label), and the shared [`CanBeInline`](forms/can-be-inline.md) / [`CanBeAccepted`](forms/can-be-accepted.md) concerns. `Select` carries `HasOptions` (static arrays and one optgroup level), `CanSelectPlaceholder`, `CanDisableOptions` + contract, `boolean()` and `getOptionLabel()`, and **auto-validates its value with a `Choice` constraint** over the enabled options — the port of Filament's `in:` rule and the field's only server-side guard, with Filament's no-options `LogicException` ported alongside. **`Toggle` diverges deliberately**: a real `<input type="checkbox" role="switch">` visually replaced by a CSS track and thumb rather than Filament's Alpine-driven `<button>`, so the epic ships **no JavaScript at all** and gets the live binding, keyboard semantics and native form participation for free ([divergence](forms/toggle.md#the-toggle-is-a-styled-checkbox-not-a-button)). `CanBeNative` is deliberately **not** ported — with no non-native renderer `native(false)` could only lie or throw. **Shipped differently from this text in three ways, each found by adversarial review rather than by the plan.** (1) The spec claimed `Type('bool')` was unfalsifiable under the boolean cast; it is not — `validateSchema()` validates the **raw** host property and no state cast runs in front of it, so the constraint is a live guard ([semantics](forms/checkbox.md#validation-reads-the-raw-state-so-the-boolean-type-constraint-is-live)). (2) `required()` therefore meant `accepted()` on a `Toggle`, since `NotBlank` rejects `false`; fixed at the cause with a `getRequiredConstraint()` seam on `CanBeValidated` (default unchanged, the port of Filament's `getRequiredValidationRule()`), which `Toggle` overrides with `NotNull` — `Checkbox` keeps `NotBlank` on purpose, because it emits the native `required` attribute and the toggle does not ([both sides cross-referenced](forms/toggle.md#required-means-presence-not-truth)). (3) `Select::boolean()` rejected its own value on every round trip, its `Choice` choices being ints while a `<select>` submits strings; the choice set now covers both forms. Demo: `Article` gains `status` (select) and `featured` (a real boolean column — the property is `featured`, not `isFeatured`, or `symfony/property-access` cannot reach `isFeatured()`), three showcase cards, and a `Select` + `Checkbox` inside the forms-in-modals card. **Verified at merge**: full gates in both repos plus six live browser checks — create round-trip with the option persisted, `pg_typeof(featured) = boolean` rather than the string `"on"`, a tampered `<option value>` rejected server-side with the row unchanged, a boolean binding to `mountedActionsData.0.*` inside a modal and submitting as a real JSON `true`, inline-vs-stacked placement with the label text toggling the control, and a console clean but for the designed 422. Deferred: `multiple()`, `searchable()`/`preload()` and the JS select, relationship options and the create/edit-option actions, `HasEnum`/`EnumStateCast`, `allowHtml()`, affixes, `CanFixIndistinctState`, `HasExtraInputAttributes`, `HasToggleColors` (blocked on the colour registry) and schemas' `HasInlineLabel`. | done |
| 4 | Slice layout catalog | [schemas](schemas/README.md) | `Grid`, `Group`, and `Fieldset` — the remaining basic layout components. `Section` is already demo-ready; its open rows are all blocked (see out of scope). | todo |
| 5 | Minimal tables vertical | [tables](tables/README.md) | `Table` and `TextColumn`, Doctrine-backed records, empty state — no pagination, sorting, or filters. | todo |
| 6 | Record actions in the list | [tables](tables/README.md), [actions](actions/README.md) | Per-row actions with record context: Edit linking to the edit page, Delete behind `requiresConfirmation`. | todo |
| 7 | Minimal notifications vertical | [notifications](notifications/README.md) | `Notification::make()->title()->success()->send()` over session flash, rendered as a stack in the layout. | todo |
| 8 | Demo slice assembly | **demo** | `/articles` list, new, and edit pages; seed data; a presentable page shell. | todo |

**Carried threads — both decided with M1 · 2 (2026-07-30).**
(1) **Model-sourced constraints**: direction fixed as the fuller Symfony
story — validate the mapped object after `getData()` maps and fold
violations back into the error bag by path (class-level constraints like
`UniqueEntity` come free); the metadata-pull alternative is rejected (typed
constraints misfire against raw pre-cast state). Implementation deferred to
the epic that needs it; verdict and the identity-map caveat live in
[schemas/can-be-validated.md](schemas/can-be-validated.md).
(2) **The typed boundary product** resolved as the `BelongsToRecord` port
rather than a new `dataClass()` concept: with POPO payloads,
`record($dto)` / `record(SomeDto::class)` subsumes the declared-data-class
idea — the entity round-trip is just the case where the bound object is the
managed entity — and the vocabulary collapsed onto record-only
([renames](renames.md#the-model-vocabulary-collapses-onto-record)). The
mapper is the existing PropertyAccessor (accessor walk; Serializer
rejected — a denormalizer constructs where edit must mutate the managed
entity), and the state-snapshot closure injection is rejected in favor of
Filament's persisted-record semantics. Full rationale:
[schemas/belongs-to-record.md](schemas/belongs-to-record.md).

**Out of scope:** panels; forms-in-modals (pulled forward as
[epic 6](#pulled-forward); the M1 demo script still navigates to a page for
Edit); table pagination, sorting, and search;
relation-backed selects; file uploads; date pickers; i18n; advanced layouts
(`Flex`, `FusedGroup`, `Tabs`, `Wizard`); [`Section`](schemas/section.md)'s
one remaining blocked row — singular-relationship state entanglement, on the
Doctrine relation layer (header/footer actions landed with the schemas↔actions
integration).

**Done when:** the demo script runs end to end in the demo app,
`composer test` is green, and the parity tables reflect everything the slice
touched.

## M2 — Mini admin panel

The demo script: _open `/admin`: a Sylament panel with sidebar navigation and
an Articles resource — list page with pagination and sorting, create and edit
pages, delete with confirm and notification. Define a resource class, get the
whole UI — Filament's pitch, running on Symfony._

| # | Epic | Packages | Delivers | Status |
| --- | --- | --- | --- | --- |
| 1 | Panel shell | [panels](panels/README.md) | Layout Twig components — sidebar, topbar, content frame — the navigation model, and the page base class. | todo |
| 2 | Panel definition + routing | [panels](panels/README.md) | The `Panel` config object (id, path, navigation), a route loader mounting pages under `/admin`, a static Dashboard page. | todo |
| 3 | Resource vertical | [panels](panels/README.md) | The `Resource` base — `form()`, `table()`, `getPages()` — List/Create/Edit pages composed from M1 pieces, navigation derived from resources. | todo |
| 4 | Table upgrades | [tables](tables/README.md) | Pagination and column sorting; search as stretch. | todo |
| 5 | Demo panel assembly | **demo** | Mount the panel, register the Articles resource, seed and polish. | todo |

**Out of scope:** login/auth (the demo runs open); widgets and dashboard
stats; global search; relation managers; infolists and the view page;
multi-panel; tenancy.

**Carried decision — read before epic 3.** `Resource` is a service collected by
tag, not a static class-string registry, and `form()`/`table()` are instance
methods; the class-string stays the user-facing identifier. Rationale, cost,
and the one open question are in
[panels/README.md](panels/README.md#carried-decisions).

**Done when:** the demo app serves `/admin` — sidebar navigation, an Articles
resource with a paginated, sorted list, create, edit, and confirmed delete
with notifications — end to end.

## Beyond

Unordered and uncommitted; promote one into a milestone when M2 closes.

- Breadth parity per package — working down the [progress tables](README.md).
- Auth: login page, user menu, panel access control.
- Relation-backed fields (`Select` from Doctrine relations, repeaters).
- Infolists and the resource view page.
- Widgets and dashboard stats.
- Table search and filters.
- Theming and dark-mode polish.
- Testing-helper ports (`TestsForms`, `TestsActions`, `TestsSchemas`).
- A docs site.
- First tagged release through the split repos.
- Pilot on a real internal tool.
