# Sylament port roadmap — epic index

The epic-level view that sits **above** the per-component parity trackers
([README.md](README.md)) and the cross-cutting blockers
([infrastructure.md](infrastructure.md)). Those files track *what* remains
inside a component or subsystem; this file tracks *how the whole port is
sectioned into epics and in what order they run*.

## How this is sectioned (settled)

**Strategy: thin admin slice first.** The spine of the roadmap is a **tracer
bullet** — the thinnest cut through *every* package needed to render one working
admin Resource (a Doctrine entity, a List + Create/Edit path, behind Symfony
Security, at a route). Everything else is **breadth-fill** layered onto that
proven spine.

Chosen deliberately over "libraries-first" to reach a demoable admin panel
fastest. **Accepted tradeoff:** the three hardest threads —
[Doctrine record-binding](#-doctrine), [panel routing, and
Auth→Security](#-framework-shaped-panels) — land **early**, on the tracer's
critical path, before the component catalogs are broad. The tracer is scoped to
survive that: each layer ports only the sliver the slice touches, and the risk
threads below are spiked inside the tracer rather than fully built.

## How epics run

- Each epic gets a **design doc + task-plan pair**, the pattern set by the
  live-component-state epic ([live-state.md](live-state.md) design +
  [live-state-tasks.md](live-state-tasks.md) task breakdown). The design settles
  decisions/divergences/renames; the task plan is the cold-session-executable
  brief. Link both from the epic's row here when it starts.
- Every task satisfies the **DoD gates** (CLAUDE.md): `composer test` green
  (Pest + Rector dry-run + PHPStan max) and `pnpm test:types`/`test:lint` green;
  `composer format` applied.
- Parity docs move **in the same change**: divergences into the component file,
  renames into [renames.md](renames.md), scope items ticked under the relevant
  [infrastructure.md](infrastructure.md) anchor.
- **Reference Filament first** (CLAUDE.md) — `vendor/filament/<pkg>/` is the
  source of truth; preserve public API names, translate Laravel→Symfony idiom.

Status legend: **done** · **in progress** · **next** · **later**.

---

## Cross-cutting risk threads

These do not respect package boundaries — they recur across many epics and are
where the port stops being a mechanical translation. Track each as a thread;
the tracer bullet spikes the first four.

| Thread | What it is | Anchor | Status |
| --- | --- | --- | --- |
| **⚡ Livewire → UX LiveComponent** | schema state loop, action mounting stack, table runtime host, panel pages | [live-component-state](infrastructure.md#live-component-state) | in progress (T1–T4) |
| **🔶 Doctrine** | Eloquent → Doctrine everywhere records appear: record-binding (`BelongsToModel`→`BelongsToEntity`), table query/aggregates/summaries, record actions, tenancy scopes, DB notifications, relationship fields | *new anchor when the binding epic starts* | not started |
| **🎨 Alpine → Stimulus** | ~20 interactive behaviors: modal, dropdown, tooltip, editable columns, charts, file-upload, rich-editor, date-picker | [stimulus-controllers](infrastructure.md#stimulus-controllers) | pattern set (section collapse) |
| **🎨 Color / theming** | palettes + color math + ColorManager + ColorMaps + `attrs().color()`; unblocks every colored component | [support-managers](infrastructure.md#support-managers) (partial) | not started |
| **📡 Real-time / queue** | Laravel Echo/broadcasting → Mercure; Laravel queue → Messenger (DB notifications, import/export) | *new anchor when notifications real-time starts* | not started |
| **🔒 Framework-shaped panels** | imperative route-builder → Symfony route loader; Laravel Auth → Security/voters; tenancy global-scopes → Doctrine filters. A rewrite, not a port. | *new anchor when panels start* | not started |

---

## Milestone 1 — Tracer bullet (thin admin Resource, end to end)

Ordered by dependency. Each step ports **only the sliver the slice needs**;
the "defers" column is breadth-fill (Milestone 2+). The goal state: a developer
defines a `Resource` for a Doctrine entity and gets an auto-routed List page
(table) + Create/Edit pages (form), behind a login, in a panel shell.

| # | Epic (thin cut) | Package | Threads | Defers to breadth-fill |
| --- | --- | --- | --- | --- |
| 1 | **Support enablers (slice subset)** — Operation/Width/VerticalAlignment enums; a minimal color layer (primary/gray CSS vars); the shared `Has*/Can*` traits the slice touches | support | 🎨 color | full color math/palettes, full enum batch, full trait roster |
| 2 | **Shared UI kit (slice subset)** — button, input + field wrapper, modal, dropdown, badge, plus their CSS; Stimulus for modal + dropdown | support | 🎨 Stimulus | link, pagination, breadcrumbs, tabs, callout, avatar, toggle, tooltip |
| 3 | **Finish live-state** — T5 (`$get`/`$set` component resolution), T7 (poll/renderless) | schemas | ⚡ | — (epic already scoped) |
| 4 | **Layout containers (slice subset)** — Grid (Section exists); enough to lay out a form | schemas | — | Tabs, Wizard, Flex, Fieldset, Group |
| 5 | **Doctrine record-binding** — `BelongsToEntity`, `getRecord()`, fill-from-entity + save-to-entity, `$record` closure param (this is live-state **T6** promoted onto the critical path) | schemas | 🔶 ⚡ | relationship/association hydration depth, nested records |
| 6 | **Forms — Field base + a few fields** — Field base + wrapper chrome + validation (Symfony Validator mapping); TextInput, Textarea, Select, Checkbox | forms | ⚡ | full field catalog (date/time, file, rich, repeater, …) |
| 7 | **Actions — base + modal + mounting-lite** — Action base/styling; the modal system; a confirm-modal + minimal mount/call path for record actions | actions | ⚡ ★ 🎨 | full mounting stack, ActionGroup, bulk, import/export |
| 8 | **Tables — core + display + row actions** — Doctrine query + pagination + sort; TextColumn; Edit/Delete row actions | tables | 🔶 ★ | filters, summaries, grouping, editable columns, layout, search |
| 9 | **Panels — config + routing + shell** — Panel value object + bundle skeleton; a Symfony route loader for panel pages; branding/nav shell (topbar/sidebar) | panels | 🔒 ★ | tenancy, clusters, SPA, multi-panel, render-hook breadth |
| 10 | **Panels — Auth (thin)** — lean on Symfony Security (app firewall) + a login page + `canAccessPanel` voter | panels | 🔒 ★ | registration, password reset, email verification, MFA |
| 11 | **Panels — Resources CRUD spine** — `Resource` + List/Create/Edit pages + record routing + minimal authorization | panels | 🔶 🔒 ★ | ViewRecord, relation managers, global search, resource nav depth |

★ = dominant-risk step; spike it first within its epic.

**Not in the tracer at all** (whole packages deferred to Milestone 3): infolists,
widgets, notifications real-time. Flash-toast notifications (success on save) are
an optional thin add (notification core + toast JS) if the demo wants them.

---

## Milestone 2 — Breadth-fill: complete the tracer's packages

Layer the full catalogs onto the proven spine. Grouped by package; each becomes
its own design + task-plan pair when picked up.

**Forms** (dep: tracer forms + schemas)
- Choice inputs + relationships `[🔶]` · Date/time · Color + Slider `[🎨]` ·
  Tags + KeyValue `[🎨]` · File upload `[🎨 heavy]` · Rich/Markdown/Code editors
  `[🎨 heaviest]` · Repeater/Builder (nested schemas) · Table-select `[dep: tables]`

**Tables** (dep: tracer tables)
- Filters `[dep: forms]` · Table + bulk actions `[dep: actions]` ·
  Grouping + summaries `[🔶 aggregate subqueries]` · Editable columns `[🎨]` ·
  Responsive layout + column manager · Global/column search polish

**Actions** (dep: tracer actions)
- Full mounting lifecycle `[⚡ ★]` · ActionGroup · Record-bound prebuilt actions
  `[🔶]` · Bulk actions · Import/Export `[🎨 queue, deferrable]`

**Schemas / support** (parallel, unblock the above)
- Layout containers: Tabs, Wizard, Flex, Fieldset, Group ·
  Base-`Component` parity remainder (HasMeta, Cloneable, CanBeDisabled, finish
  CanBeHidden render-skipping) · Full color system (palettes + math + ColorMaps)
  `[🎨]` · Full shared UI kit + Stimulus queue `[🎨]` · Render hooks / ViewManager ·
  HTML sanitizer `[🔒 security]` · Full enum + trait batches

---

## Milestone 3 — Remaining packages

Independent of the tracer; slot in when a consumer needs them or the panel
demands them.

**Infolists** (dep: schemas + the shared display machinery)
- Entry base + slots · **Shared display machinery** (`CanFormatState`/`HasColor`/
  `HasIcon` — *port once, also consumed by table columns*) · TextEntry · simple
  entries (Icon/Color/KeyValue/Code/View) · ImageEntry · RepeatableEntry

**Notifications** (dep: support + actions)
- Builder core · Toast + JS runtime `[🎨]` · Database notifications `[🔶]` ·
  Real-time `[📡 Mercure]`

**Widgets** (dep: schemas)
- Widget foundation · StatsOverview · ChartWidget `[🎨 Chart.js]` ·
  TableWidget `[dep: tables]`

---

## Milestone 4 — Panels: full admin surface

Complete the admin layer onto the CRUD spine.

- Multi-factor auth · Tenancy `[🔶 Doctrine filters + 🔒 security-critical]` ·
  Relation managers `[dep: tables]` · Clusters · Global search ·
  User-menu / notifications / dashboard-widget integration ·
  Navigation depth (groups/badges/sub-nav) · Branding/theme/SPA breadth ·
  MakerBundle codegen (`make:resource`, etc.)

---

_Living document. When an epic starts, add its design + task-plan links and flip
its status; when it finishes, flip to done and prune the tracer/breadth detail it
subsumed. Keep the risk-thread anchors current — create the missing
infrastructure.md anchors (🔶 Doctrine, 📡 real-time, 🔒 panels) as those epics begin._
