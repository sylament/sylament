# Actions — base + modal + mounting-lite — task plan

> Task breakdown for the epic designed in [actions.md](actions.md). The Design,
> Decisions, Divergences, and Non-goals there are **settled** — these briefs
> execute it, they do not re-open it. Each brief is self-contained for a cold
> session: goal, the vendor source to port from, the exact current-`main` files
> to touch, the locked decisions, and acceptance criteria. Honor the
> [Shared conventions](#shared-conventions) once; briefs don't restate them.

## Sequencing — spike-first (roadmap ★)

The ★ risk is one coupling: a click reaching a PHP closure + opening a modal over
the UX round-trip. Front-load the thinnest vertical that proves it, then breadth.

```
AC1  Modal + Button primitives (support)   ─┐  support-only, no live component
AC2  ⚡ mount/call SPIKE (actions host)      ─┤  depends on AC1 (needs the modal)
AC3  Action styling                          ─┼─ depends on AC2 (Action skeleton)
AC4  Confirm lifecycle + record + notify     ─┘  depends on AC2 (+ AC3 templates)
```

AC1→AC2 is the spike. AC3 (styling) and AC4 (lifecycle) parallelize once AC2
lands the `Action` skeleton + host loop. **Do AC2 immediately after AC1** — if the
argument-carrying `#[LiveAction]` + modal round-trip doesn't work, the epic's
shape changes, so prove it before investing in breadth.

## Shared conventions

Every task satisfies these (from [CLAUDE.md](../../CLAUDE.md)); not repeated per
brief.

- **DoD gates:** `composer test` green (Pest + Rector dry-run + PHPStan **level
  max**) and `pnpm test:types` + `pnpm test:lint` green. Run `composer format`
  (Rector + PHP-CS-Fixer) — **never hand-format**. No PHPStan baseline / ignore
  (`reportUnmatchedIgnoredErrors` is on); only `trait.unused` is globally ignored.
- **Reference Filament first:** `vendor/filament/{actions,support}/` is the source
  of truth; preserve public API names, translate Laravel→Symfony idiom
  (`getLivewire()`→`getLiveComponent()`, `view()`→`template()`,
  `dispatch()`→`dispatchBrowserEvent()`, `Enums\`→`Enum\`, `Exceptions\`→`Exception\`).
- **Closure typing:** setters take native `Closure` unions, direct assignment, no
  param PHPDoc, no arity specs. Props carry native types only; `evaluate()` (from
  `EvaluatesClosures`) supplies the generics. `evaluationIdentifier = 'action'` so
  `fn ($action) => …` resolves the instance.
- **Test layout:** Pest; `Unit/` (no kernel) + `Integration/` (boots the kernel)
  mirror `src/`; `Fixtures/` mirrors `src/`. Inside `it()`/`test()` reach static
  helpers via `$this::`, never `self::`/`static::`. Add fixtures by dropping
  classes under `tests/Fixtures/…`, never by editing the Kernel's service load.
- **CSS:** semantic `sy-*` classes only (no utilities — scanning is off); `@apply`
  authoring under `assets/src/styles/components/<name>.css`; every template root
  carries the bare `sy` marker. Full contract: [docs/styling.md](../styling.md).
- **Parity docs in the same change:** land the [renames.md](renames.md) rows
  (AC2), the [Divergences](actions.md#divergences-record-when-code-lands), and the
  [roadmap.md](roadmap.md) step-7 flip (AC4, the last to land). Create the
  per-component `docs/parity/actions/action.md` when the code lands.
- **Self-review pass** (>~30 lines / >2 files): run static analysis + tests,
  re-read each modified file end-to-end, grep callers of any changed signature,
  report findings before declaring done.

---

## AC1 — Modal + Button primitives (`support`) + the actions test kernel

- [ ] **AC1** — a native-`<dialog>` `<twig:SylamentSupport:Modal>` + a
  `<twig:SylamentSupport:Button>`, their Stimulus modal controller and CSS, and
  the actions Integration test kernel that render-tests them.

**Goal.** Ship the support-level UI-kit primitives the actions confirm-modal
composes (roadmap step-2 slices, pulled forward), LiveComponent-**agnostic**, plus
the actions package's Integration test kernel. No actions PHP yet.

**Port from.** Filament's modal is `vendor/filament/support/resources/views/components/modal/index.blade.php`
+ `resources/js/components/modal.js` — port the **DOM roles + interactivity
contract** (overlay/window/header/footer; open/close/ESC/click-away/scroll-lock),
**not** the Alpine object: native `<dialog>` replaces the focus-trap, ESC,
top-layer and `::backdrop` (Decision 2). In-repo templates to mirror exactly:
[`IconButton/index.html.twig`](../../packages/support/templates/components/IconButton/index.html.twig)
(the `{% props %}` + `attributes.defaults({class: classes(…)})` shape) and the
[`section_controller.ts`](../../packages/support/assets/src/controllers/section_controller.ts)
(`static values`, `connect()`, `@window` methods + `#matches(event)` id-matching,
its registration in [`package.json`](../../packages/support/assets/package.json)).
The actions kernel mirrors [`forms/tests/Fixtures/Kernel.php`](../../packages/forms/tests/Fixtures/Kernel.php).

**Touch (current `main`) — new files.**

- `packages/support/templates/components/Button/index.html.twig` — a `{% props %}`
  text button (`tag`/`type`/`href`/`disabled`/`color`/`size`/`icon`), root class
  `classes('sy sy-btn', { 'sy-disabled':…, ('sy-size-'~sizeValue):…, 'sy-color':…,
  ('sy-color-'~colorName):… })` via `attributes.defaults({...})` — copy
  `IconButton`'s exact color/size/disabled derivation. Sibling of IconButton.
- `packages/support/templates/components/Modal/index.html.twig` — a native
  `<dialog class="sy sy-modal" data-controller="sylament--support--modal"
  data-sylament--support--modal-id-value="{{ id }}">` with `{% props id, heading,
  description, icon, iconColor, width %}` and **slots** for the body and footer
  (`{% block content %}`/`{{ content }}` via the ux-twig-component slot idiom used
  by Section). Header = `generateIconHtml(icon,…)` + `<h2>{{ heading }}</h2>` +
  `<p>{{ description }}</p>` (each rendered only when filled — the
  `x is not empty`/`filled` test, as Section does). Rendered **closed** (no
  `open` attr; no `sy-cloak` — closed is server-truth). Footer is a slot the
  actions layer fills. LiveComponent-agnostic: **no** `live_action`/`wire:` here.
- `packages/support/assets/src/controllers/modal_controller.ts` — `export default
  class extends Controller<HTMLDialogElement>`; `static targets = []` (the root
  element **is** the `<dialog>`), `static values = { id: String, returnsFocus:
  { type: Boolean, default: true } }`. Methods: `open(e)` / `close(e)` gated by
  `#matches(e)` (match `event.detail.id` to `idValue`, exactly like
  `section_controller`'s `#matches`), calling `this.element.showModal()` /
  `this.element.close()`. Wire `@window` listeners for `sylament-open-modal` /
  `sylament-close-modal`. On `close` (native `cancel`/`close` events) restore focus
  if `returnsFocusValue`. Native `<dialog>` handles ESC/focus-trap/backdrop/
  top-layer — **do not** hand-roll them. Keep it ~30 lines. `declare readonly`
  every value (Biome/tsc strict); relative imports carry `.js`.
- `packages/support/assets/src/styles/components/modal.css` and `button.css` —
  `@apply` authoring for `.sy-modal`/`.sy-modal-*`/`::backdrop` and `.sy-btn`/
  `.sy-btn-*` (respecting the `sy-size-*`/`sy-color` token pattern). Add `@import`
  lines for both to support's `assets/src/styles/index.css` aggregator (beside the
  existing `components/section.css` import).
- `packages/actions/tests/Fixtures/Kernel.php` — **mirror the forms kernel**:
  bundles `FrameworkBundle, TwigBundle, TwigComponentBundle, UXIconsBundle,
  StimulusBundle, LiveComponentBundle, SylamentSupportBundle, SylamentActionsBundle`
  (**not** schemas — confirm-only needs none); `configureRoutes` imports
  `@LiveComponentBundle/config/routes.php`; `twig_component.anonymous_template_directory:
  'components'`; PSR-4-load `Sylament\Actions\Tests\Fixtures\` excluding `Kernel.php`.
  Also port `packages/actions/tests/{TestCase.php,Pest.php,bootstrap.php}` to the
  schemas/forms shape (Integration bucket attaches `TestCase`; Unit uses Pest
  default + the manager teardowns).

**Touch — registration.**
- `packages/support/assets/package.json` — add a `modal` entry to
  `symfony.controllers` (`"main": "dist/controllers/modal_controller.js", "fetch":
  "eager", "enabled": true`), beside `core`/`section`.

**Locked decisions.** Native `<dialog>` (Decision 2) — no focus-trap dependency
(adding one needs approval). Chrome lives in **support**, LiveComponent-agnostic
(the actions layer injects `live_action()` later). Button/Modal are
`<twig:SylamentSupport:…>` anonymous components (IconButton precedent), reached via
the `@SylamentSupport` namespace.

**Acceptance criteria.**
- **Render (Integration, in the new actions kernel)** — `packages/actions/tests/Integration/`:
  render `<twig:SylamentSupport:Button>` and `<twig:SylamentSupport:Modal>` via a
  fixture template, assert the root classes (`sy sy-btn`, `sy sy-modal`), the
  `data-controller="sylament--support--modal"` + `-id-value`, header pieces render
  only when props are filled, and the `<dialog>` has **no** `open` attribute
  (closed by default). (Support Twig components are render-tested from a downstream
  kernel that registers TwigComponentBundle — here, actions' own kernel — per the
  CLAUDE.md convention; `composer test:support` alone won't exercise them.)
- **JS** — `pnpm test:types` (tsc) + `pnpm test:lint` (Biome) green for
  `modal_controller.ts`; no DOM/interaction test (no JS harness exists — noted
  limitation).
- **Asset build (required this task):** run `pnpm build`; commit the regenerated
  `packages/support/assets/dist` (controller JS + compiled CSS). CI fails if the
  committed `dist` doesn't match a fresh build — unlike the PHP-only enablers epic,
  **this task rebuilds assets**.
- DoD gates green.

**Notes / pitfalls.** `<dialog>` morphdom interplay is AC2's concern — here the
modal is static. Slots: match Section's ux-twig-component slot idiom, not a Blade
`$slot`. Keep `sy-cloak` **out** (closed is server-truth; `sy-cloak` is for
client-truth-hidden). The `returnsFocus` default is `true` for the standalone
primitive; AC4's action-modal usage may pass `false` if the host manages focus.

---

## AC2 — the ⚡ mount/call spike (actions host round-trip)

- [ ] **AC2** — a skeletal `Action` + the `HasActions` host trait +
  `InteractsWithActions` contract, proving click → `mountAction` → modal →
  `callMountedAction` → closure over the UX round-trip on a synthetic host.

**Goal.** Prove the dominant-risk coupling with the thinnest vertical: an
argument-carrying `#[LiveAction]` mounts an action into a `#[LiveProp]`, opens the
AC1 modal, and a second `#[LiveAction]` runs the action's closure. Style/lifecycle
breadth is AC3/AC4 — keep the `Action` a stub here.

**Port from.**
- `vendor/filament/actions/src/Concerns/InteractsWithActions.php` — `mountAction`
  (`:111`), `callMountedAction` (`:197`), `getMountedAction` (`:466`),
  `resolveAction` (`:547`), `unmountAction` (`:732`), the `sync-action-modals`
  dispatch (`:789`). Collapse to **single-level** (no stack/nesting index).
- `vendor/filament/actions/src/Action.php` — the class head (`:51` extends
  ViewComponent, `evaluationIdentifier='action'`, `final __construct(?string
  $name)`, `make()`+`configure()`, `HasAction`/`getAction`).
- **In-repo host shape to mirror:** [`LiveStateHost`](../../packages/schemas/tests/Fixtures/Twig/Components/LiveStateHost.php)
  (`#[AsLiveComponent]` + `DefaultActionTrait` + `#[LiveProp]` + `#[LiveAction]`),
  [`BelongsToLiveComponent`](../../packages/schemas/src/Concerns/BelongsToLiveComponent.php)
  (the `getLiveComponent()` precedent), and the render/eval spine on support's
  [`ViewComponent`](../../packages/support/src/Component/ViewComponent.php).
- **UX (verified):** `live_action()` → `LiveComponentRuntime::liveAction(name,
  parameters, modifiers, event)` (`vendor/symfony/ux-live-component/src/Twig/LiveComponentRuntime.php:51`);
  `#[LiveArg]`, `#[LiveAction]`, `#[LiveProp]`, `DefaultActionTrait`;
  `ComponentToolsTrait::dispatchBrowserEvent(name, payload)` (`ComponentToolsTrait.php:49`).

**Touch (current `main`) — new files.**
- `packages/actions/src/Action.php` — `class Action extends
  Sylament\Support\Component\ViewComponent`; `evaluationIdentifier='action'`;
  `final __construct(?string $name)` → `$this->name($name)`; `make(?string $name)`
  → `new static($name)` + `configure()` (the `Field::make` idiom, **not** `app()`);
  `protected $template = '@SylamentActions/components/button-action.html.twig'`.
  For the spike it needs only: `use HasName`, `use HasAction` (the `action()`
  closure + `getAction()`), and a minimal `requiresConfirmation(bool $condition =
  true)` + `isConfirmationRequired()` (AC4 ports the full `CanRequireConfirmation`).
- `packages/actions/src/Concerns/HasName.php` — `name()`/`getName()` (**throws** on
  blank — port `vendor/filament/actions/src/Concerns/HasName.php`, unlike schemas'
  null-returning one).
- `packages/actions/src/Concerns/HasAction.php` — `action(?Closure)` + `getAction()`.
- `packages/actions/src/Concerns/BelongsToLiveComponent.php` — port
  schemas' trait but typed against `Sylament\Actions\Contracts\InteractsWithActions`
  (`liveComponent()`/`getLiveComponent()`).
- `packages/actions/src/Contracts/InteractsWithActions.php` — the host **contract**
  (the swap: Filament's contract is `HasActions`). Names the behavior:
  `mountAction`, `callMountedAction`, `unmountAction`, `getMountedAction`, `getAction`.
- `packages/actions/src/Concerns/HasActions.php` — the host **trait** (the swap:
  Filament's trait is `InteractsWithActions`):
  - `#[LiveProp] public array $mountedActions = [];` — **not writable** (mutated
    only server-side; Decision 7); holds one `['name'=>…, 'arguments'=>…,
    'context'=>…]` descriptor.
  - `#[LiveAction] public function mountAction(#[LiveArg] string $name, #[LiveArg]
    array $arguments = [], #[LiveArg] array $context = []): void` — set
    `$mountedActions = [[...]]`; resolve the action; if `isConfirmationRequired()`
    (⇒ `shouldOpenModal()`) `$this->dispatchBrowserEvent('sylament-open-modal',
    ['id' => $this->getMountedModalId()])`.
  - `#[LiveAction] public function callMountedAction(): mixed` — resolve the
    mounted action, `evaluate()` its `getAction()` closure (inject `$arguments`;
    `$record` arrives in AC4), then `unmountAction()`.
  - `#[LiveAction] public function unmountAction(): void` — `$mountedActions = []`;
    `dispatchBrowserEvent('sylament-close-modal', ['id' => …])`.
  - `getMountedAction(): ?Action` (the single entry), `getAction(string $name):
    ?Action` / `resolveAction(array $descriptor): ?Action` — call a host
    `{$name}Action(): Action` method, re-instantiate each round-trip, attach via
    `->liveComponent($this)`.
- `packages/actions/templates/components/button-action.html.twig` — a `<button>`
  carrying `{{ live_action('mountAction', { name: component.getName }) }}` (+ the
  root `sy sy-btn sy-ac` classes; full styling is AC3) and `{{ component.getLabel
  ?? component.getName }}`.
- `packages/actions/tests/Fixtures/…` — a synthetic `#[AsLiveComponent]` host:
  `implements InteractsWithActions; use DefaultActionTrait; use HasActions;` a
  `deleteAction(): Action` method returning `Action::make('delete')->action(fn () =>
  $this->called = true)`, a public `bool $called = false` flag, and a template
  rendering `{{ renderComponent($this->deleteAction()) }}` + the modal when
  `mountedActions` is non-empty.

**Locked decisions.** `Action` = config object; behavior on the host (Decision 5).
Single non-writable `$mountedActions`, single-level (Decisions 6/7). `new static()`
+ Twig template + `renderComponent()` (Decision 8). The name **swap** — trait
`HasActions`, contract `InteractsWithActions` (get it backwards and the whole
convention inverts; grep `renames.md`).

**Acceptance criteria.**
- **Integration (the spike, `InteractsWithLiveComponents`)** — in `packages/actions/tests/Integration/`,
  mirror [`LiveFieldTest`](../../packages/forms/tests/Integration/LiveFieldTest.php)
  (`uses(InteractsWithLiveComponents::class)`): `$this->createLiveComponent('ActionHost')`
  — the **registered** `#[AsLiveComponent(name: 'ActionHost')]` name string, not the
  class (`createLiveComponent(string $name, …)`); `->call('mountAction', ['name' =>
  'delete', 'arguments' => [], 'context' => []])` (the array maps to `#[LiveArg]`
  params by name) ⇒ `mountedActions` has one entry
  and the rendered HTML now contains the modal markup; `->call('callMountedAction')`
  ⇒ the host's `called` flag is `true` and `mountedActions` is empty again. This
  proves the first argument-carrying `#[LiveAction]` + the mount→call round-trip.
- **Render-assert the wiring** — the trigger button's HTML carries the
  `live_action('mountAction')` output (`data-action` containing `live#action` + a
  `data-live-*-param` for `name`). `->call()` invokes LiveActions **server-side** and
  does **not** simulate the client click, so assert the emitted attributes
  separately from the server logic above.
- **Browser verify** (the forms "F4 browser payoff" precedent — `/run` or
  `/verify`): manually drive click → modal opens → confirm → closure runs in a real
  browser. This is the only check that exercises the client click reaching the host
  `live` controller through a top-layer `<dialog>` — the `<dialog>` stays in the
  host DOM tree so the action should bubble/resolve, but no JS/DOM harness proves
  it. Verify once by hand; note the result in the epic wrap-up.
- **Unit** — `HasName` throws on blank `getName()`; `HasAction` round-trips the
  closure; `Action::make('x')->getName()` is `'x'`.
- **Renames** — add the two swap rows to [renames.md](renames.md) (the table in
  [actions.md](actions.md#renames-to-record-when-code-lands)).
- DoD gates green (the actions Integration kernel from AC1 boots LiveComponentBundle).

**Notes / pitfalls.** `live_action()` is `is_safe html_attr` and independent of
`twig/html-extra` (no template-ban violation). The action button **must** render
inside the host's live-component DOM or `data-controller="live"` isn't present and
the click won't dispatch. Morphdom re-renders the whole host each call — the modal
markup must render whenever `mountedActions` is non-empty so it survives the
re-render; the `sylament-open-modal` event re-opens the `<dialog>` after morph.
Don't try to serialize the `Action` — only the descriptor travels; re-resolve via
`{name}Action()`.

---

## AC3 — Action styling (presentation concerns + button template)

- [ ] **AC3** — the full static presentation surface on `Action`, reusing support
  concerns and adding the actions-level identity/size/visibility/url concerns, with
  a styled button template + `sy-ac` CSS.

**Goal.** Make `Action` render a fully-styled trigger (label/color/icon/size/
disabled, `<a>`-via-url), reusing the shipped support concerns. Orthogonal to
AC2's interactivity — layer onto the `Action` skeleton.

**Port from.**
- Reuse verbatim (already in `Sylament\Support\Component\Concerns\`): `HasColor`,
  `HasIcon`, `HasIconPosition`, `HasIconSize`, `HasExtraAttributes`, `HasTooltip`;
  enums `Size`/`IconPosition`/`IconSize`.
- New actions concerns from Filament (`vendor/filament/actions/src/Concerns/`):
  `HasLabel` (`getLabel()` default `str($name)->before('.')->kebab()->ucfirst()` —
  `:47`; **not** Field's `afterLast('.')`), `HasSize` (`size()`/`defaultSize()`/
  `getSize()`), `CanBeDisabled` (**simplified** `isDisabled()` = `evaluate($isDisabled)
  || isHidden()`, auth branches stubbed — Decision 9), `CanBeHidden` (the schemas
  simple core `hidden()`/`visible()`/`isHidden()`, no table cache/group/auth),
  `CanOpenUrl` (`url()`/`openUrlInNewTab()` → render `<a>`).
- In-repo test/fixture templates: [`HasAlignmentTest`](../../packages/support/tests/Unit/Component/Concerns/HasAlignmentTest.php)
  + its fixture, and [`IconButton/index.html.twig`](../../packages/support/templates/components/IconButton/index.html.twig)
  for the class-list derivation.

**Touch (current `main`).**
- `packages/actions/src/Concerns/{HasLabel,HasSize,CanBeDisabled,CanBeHidden,CanOpenUrl}.php`
  and `use` them on `Action` (plus the six support concerns).
- Flesh out `packages/actions/templates/components/button-action.html.twig` — root
  `<{{ tag }}>` (`tag` = `a` when `getUrl()` set, else `button`) with
  `classes('sy sy-btn sy-ac', { 'sy-disabled': component.isDisabled,
  ('sy-size-'~size): …, 'sy-color': …, ('sy-color-'~name): … })` + `color(...)` +
  `generateIconHtml(component.getIcon, …, component.getIconSize)` positioned by
  `getIconPosition()`, `href`/`target` when url, `disabled`/`aria-disabled`,
  `{{ attrs(component.getExtraAttributes) }}`, and the AC2 `live_action('mountAction')`
  (skipped when `getUrl()` is set — a URL action is a plain link, no mount).
- `packages/actions/assets/src/styles/index.css` (already compiled by support) —
  author `.sy-ac`/`.sy-ac-*` hooks (or a `components/action.css` it imports).

**Locked decisions.** Reuse support concerns; button variant only (the
`ActionVariant` enum + `button()`/`link()`/`iconButton()` are deferred, actions.md
Design §1). `getLabel()` uses `before('.')`. `isDisabled()` simplified (auth
deferred).

**Acceptance criteria.**
- **Unit** — one test per new concern (mirror `HasAlignmentTest`): `HasLabel`
  name-default (`'delete'` → `'Delete'`, `'user.edit'` → `'Edit'` via `before('.')`),
  explicit label wins; `HasSize` default/size/`defaultSize` precedence; `CanBeDisabled`
  `isDisabled()` true when disabled **or** hidden; `CanBeHidden` visible/hidden;
  `CanOpenUrl` url + newTab.
- **Render (Integration)** — an `Action::make('delete')->color('danger')->icon('trash')
  ->size('sm')` renders `sy sy-btn sy-ac sy-size-sm sy-color sy-color-danger`, the
  icon HTML, and `type="button"`; `->url('/x')->openUrlInNewTab()` renders `<a
  href="/x" target="_blank">` with **no** `live_action`; `->disabled()` renders
  `disabled`/`aria-disabled`.
- **Asset build** — if `action.css` is added, `pnpm build` + commit `dist`.
- DoD gates green.

**Notes / pitfalls.** Keep the union on `HasSize` off the `Size` enum only where
Filament does; match `HasAlignment`'s spacing after `composer format`. `getLabel()`
must not be copied from `Field` (`afterLast` vs `before`). A URL action renders a
link and must **not** emit the mount wiring. **Button-component reuse (revisit —
AC2 follow-up):** AC2 shipped a raw `<button>` that hand-derives `sy sy-btn`,
because `live_action()` returns an `html_attr` string that can't splat onto a
`<twig:…>` tag (the tag's only spread form, `{{ ...map }}`, needs a key/value map —
verified in `TwigPreLexer::consumeAttributes`) and AC1 locked `Button`
LiveComponent-agnostic. Before hand-rolling a third `sy-btn` derivation here,
decide between (a) sharing the derivation across `Button`/`IconButton`/
`button-action` via a Twig macro or include, or (b) giving `Button` an explicit
`liveAction` prop so it emits the mount wiring internally and stays the single
button primitive — (b) is the only way live wiring composes onto the component tag.

---

## AC4 — Confirm lifecycle + record binding + notification stub

- [ ] **AC4** — the confirm-modal config, lifecycle hooks, synthetic record
  resolver, and notification-config stub, proving a Delete-shaped action
  end-to-end. Last task — lands the roadmap flip.

**Goal.** Complete the confirm-only vertical: `requiresConfirmation()` opens a
config-driven confirm modal; confirm runs `before → closure(record) → after` with
success/failure branching (delivery stubbed); a synthetic `resolveRecordUsing`
supplies the `$record`.

**Port from** (`vendor/filament/actions/src/Concerns/`): `CanRequireConfirmation`
(the `requiresConfirmation` + confirm-default getters), `CanOpenModal` **subset**
(`modalHeading`/`modalDescription`/`modalIcon`/`modalIconColor`/`modalSubmitActionLabel`/
`modalCancelActionLabel`/`modalWidth` + the `isConfirmationRequired` default
branches — do **not** model "confirm modal" as a separate class), `HasLifecycleHooks`
(`before`/`after`/`cancel`; drop the Laravel `Event::dispatch` calls),
`HasArguments`, `CanNotify` (**config only**: `successNotificationTitle`/
`failureNotificationTitle` + `sendSuccessNotification`/`sendFailureNotification` as
**no-op seams**), `InteractsWithRecord` (**trimmed** to `record()`/`getRecord()` +
a host `resolveRecordUsing`). `Halt`/`Cancel` from `vendor/filament/support/src/Exceptions/`.

**Touch (current `main`).**
- `packages/support/src/Exception/{Halt,Cancel}.php` — new `Sylament\Support\Exception`
  namespace (Filament places these in support; `Exceptions\`→`Exception\`
  singularization).
- `packages/actions/src/Concerns/{CanRequireConfirmation,CanOpenModal,HasLifecycleHooks,HasArguments,CanNotify,InteractsWithRecord}.php`
  and `use` them on `Action`.
- `packages/actions/src/Concerns/HasActions.php` (from AC2) — `callMountedAction()`
  now runs `before → try { evaluate(action closure with $record, $arguments) →
  after → sendSuccessNotification() } catch (Halt) {} catch (Cancel) {}`, honoring
  `getRecord()` via the host `resolveRecordUsing`; failure path calls
  `sendFailureNotification()`. Add `resolveRecordUsing(Closure)` + a
  `getMountedActionRecord()` reinflating `context['recordKey']`.
- `packages/actions/templates/components/…` — a confirm-modal template composing
  `<twig:SylamentSupport:Modal>` (from AC1) with `component.getModalHeading` /
  `getModalDescription` and a **footer slot** carrying the confirm button
  (`{{ live_action('callMountedAction') }}`) + cancel (`{{ live_action('unmountAction') }}`).
  The host renders it when `mountedActions` is non-empty and the mounted action
  `isConfirmationRequired()`.
- `packages/actions/src/Enum/ActionStatus.php` — `Success`/`Failure` (drives the
  branch), if the lifecycle needs it.

**Locked decisions.** Confirm-only, no form (Decision 1). Synthetic
`resolveRecordUsing` (Decision 3). Notifications config-only, delivery stubbed
(Decision 4). `Halt`/`Cancel` in support.

**Acceptance criteria.**
- **Integration (end-to-end Delete)** — a synthetic host with `deleteAction(): Action`
  = `Action::make('delete')->requiresConfirmation()->modalHeading('Delete?')
  ->action(fn ($record) => $this->deleted[] = $record)`, and `resolveRecordUsing(fn
  ($key) => $this->rows[$key])` over an in-memory `rows` array:
  `->call('mountAction', ['name'=>'delete','arguments'=>[],'context'=>['recordKey'=>1]])`
  ⇒ the rendered HTML contains the confirm modal with heading "Delete?";
  `->call('callMountedAction')` ⇒ the row is in `$this->deleted`, `mountedActions`
  is empty. Prove `->cancel()`/`Halt` keeps or clears state as ported.
- **Unit** — `CanRequireConfirmation` default getters; `CanOpenModal` heading/
  description round-trip + confirm defaults; `CanNotify` setters store titles and
  `sendSuccessNotification()` is a safe no-op; `InteractsWithRecord` record round-trip.
- **Epic wrap-up** — flip the [roadmap.md](roadmap.md) Milestone-1 step-7 row to
  **done**, link [actions.md](actions.md) + this file; confirm the
  [Divergences](actions.md#divergences-record-when-code-lands) landed (create
  `docs/parity/actions/action.md`; add the modal note under
  [infrastructure.md#stimulus-controllers](infrastructure.md#stimulus-controllers)
  and the ⚡ mounting sub-thread under
  [infrastructure.md#live-component-state](infrastructure.md#live-component-state)).
- DoD gates green.

**Notes / pitfalls.** `getState(afterValidate:)` does **not** exist
([`HasState.php:162`](../../packages/schemas/src/Concerns/HasState.php)) — that's
the form-in-modal boundary; confirm-only never calls it. Keep the notification
send methods no-op **seams** (a later notifications bundle fills them) — don't
`throw`. `Halt` stops the action cleanly (no failure notification); `Cancel`
aborts. The record never hydrates as an object — only `context['recordKey']`
(scalar) travels and is reinflated by `resolveRecordUsing`.
