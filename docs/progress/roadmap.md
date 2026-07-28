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

**M1 · 1 — form validation vertical.** The next epic to pick up.

## Pulled forward

Epics taken out of milestone order by decision — recorded here so the thin-path
story stays honest.

| # | Epic | Packages | Delivers | Status |
| --- | --- | --- | --- | --- |
| 1 | Dropdown primitive + ActionGroup base | [support](support/README.md), [actions](actions/README.md) | `<twig:SylamentSupport:Dropdown>` with a bundled Floating-UI Stimulus controller; `ActionGroup` model (eager `getFlatActions()`, visibility composition, propagation), icon-button/button triggers, grouped item rendering. Trigger-view catalog, submenus, badges, table/bulk deferred. | done |
| 2 | Schemas↔actions integration | [schemas](schemas/README.md), [actions](actions/README.md) | Actions as schema children, `prepareAction`/`prepareActionGroup`, `Schema::getAction()`, Section header/footer actions — flips the three blocked Section rows. | done |
| 3 | Actions core polish | [actions](actions/README.md), [schemas](schemas/README.md), [support](support/README.md) | The finishable core around the `Action` base, no prebuilt actions. View variants: `badge()`/`button()`/`grouped()`/`iconButton()`/`link()` switchers with icon-button/link/badge templates (icon-button composes support `IconButton`), `hiddenLabel()`, and the Section label-slot `LINK_VIEW` divergence flipped. General modal presence + content: `modal()`/`hasModal()`/`shouldOpenModal()` (host gate delegates; fixes the auto-run-on-`modalHeading()` gap and `getUrl()`'s missing modal-precedence guard), `modalContent`/`modalContentFooter`, footer as real `Action`s (`modalSubmitAction`/`modalCancelAction`/`extraModalFooterActions`/`getModalFooterActions`/`prepareModalAction`), `getModalAction()` — which also unlocks the dotted descent deferred in schemas' component `getAction()`. Host side: `cachedActions`/`cacheAction()` (closes the two-instance identity gap), bare-method `getAction()` fallback with return-type reflection, `ActionNotResolvableException`, `afterActionCalled()`. `HasMountableArguments` (`__invoke`) with arguments emitted by the trigger templates. Small independents: `HasId`, `CanBeOutlined`, `HasGroupedIcon`, group `extraAttributes`/`extraDropdownAttributes` rendering. A Sylament-only `renderAction(name)` Twig helper (actions `ActionExtension`, mirroring `renderSchema()`): resolves through the context host's `getAction()` (bound + cached), renders nothing when hidden or unresolved; instance rendering stays `renderComponent()`. Deferred: modal chrome (slide-over, sticky panes, alignment, close/click-through/autofocus toggles — support `Modal` surface), authorization, rate limiting, transactions, redirects, events, nested action stack, submenus, icon-alias registry, `toArray()`/`fromArray()`. | done |

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
| 1 | Form validation vertical | [forms](forms/README.md), [schemas](schemas/README.md) | `CanBeValidated` subset mapped to Symfony Validator — `required`, `maxLength`, and what `TextInput`/`Textarea` carry — host-side `validate()` on submit, error display in `FieldWrapper`. | todo |
| 2 | Entity round-trip | **demo**, plus package gaps it surfaces | `fill()` from an Article entity, `getState()` back onto it and flush; create and edit pages sharing one form component. | todo |
| 3 | Slice field catalog | [forms](forms/README.md) | `Select` (native, static options) and `Checkbox`; `Toggle` as stretch. | todo |
| 4 | Slice layout catalog | [schemas](schemas/README.md) | `Grid`, `Group`, and `Fieldset` — the remaining basic layout components. `Section` is already demo-ready; its open rows are all blocked (see out of scope). | todo |
| 5 | Minimal tables vertical | [tables](tables/README.md) | `Table` and `TextColumn`, Doctrine-backed records, empty state — no pagination, sorting, or filters. | todo |
| 6 | Record actions in the list | [tables](tables/README.md), [actions](actions/README.md) | Per-row actions with record context: Edit linking to the edit page, Delete behind `requiresConfirmation`. | todo |
| 7 | Minimal notifications vertical | [notifications](notifications/README.md) | `Notification::make()->title()->success()->send()` over session flash, rendered as a stack in the layout. | todo |
| 8 | Demo slice assembly | **demo** | `/articles` list, new, and edit pages; seed data; a presentable page shell. | todo |

**Out of scope:** panels; forms-in-modals (Edit navigates to a page — modal
forms wait on actions' `HasSchema`); table pagination, sorting, and search;
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
| 5 | Forms-in-modals (stretch) | [actions](actions/README.md), [schemas](schemas/README.md) | Actions' `HasSchema` — modal-hosted forms, unlocking Filament-style create/edit-in-modal. | todo |
| 6 | Demo panel assembly | **demo** | Mount the panel, register the Articles resource, seed and polish. | todo |

**Out of scope:** login/auth (the demo runs open); widgets and dashboard
stats; global search; relation managers; infolists and the view page;
multi-panel; tenancy.

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
