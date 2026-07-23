# First Forms fields — task plan

> Task breakdown for the epic designed in [forms-fields.md](forms-fields.md). The
> Design, Decisions, Divergences, and Non-goals in that file are **settled** —
> these briefs execute it, they do not re-open it. Each brief is self-contained for
> a cold session: goal, the vendor/prototype source to port from, the exact
> current-`main` files to touch, the locked decisions, and acceptance criteria.
> Honor the [Shared conventions](#shared-conventions) once; briefs don't restate
> them.

## Sequencing

Vertical-slice-first: **F1 → F2** make one field render and prove the chrome, then
F3 adds the second field (reuse proof) and F4 lands the live payoff + browser drive.

```
F1 Field base + HasName + concerns ──▶ F2 TextInput + wrapper + CSS ──▶ F3 Textarea
                                                    └──────────────────▶ F4 live demo + browser
```

- **F1** blocks everything (defines `Field`).
- **F2** needs F1 (renders a concrete `Field`) + the wrapper template.
- **F3** needs F2 (reuses the wrapper).
- **F4** needs F2 (a renderable live-bound field); F3 optional for the demo.

## Shared conventions

Every task satisfies these (from [CLAUDE.md](../../CLAUDE.md)); not repeated per
brief.

- **DoD gates:** `composer test` green (Pest + Rector dry-run + PHPStan **level
  max**) and `pnpm test:types` + `pnpm test:lint` green. Run `composer format`
  (Rector + PHP-CS-Fixer) — never hand-format. No PHPStan baseline / ignore
  (`reportUnmatchedIgnoredErrors` is on).
- **Reference Filament first:** `vendor/filament/forms/` + `vendor/filament/schemas/`
  are the source of truth; preserve public API names, translate Laravel→Symfony.
- **Closure typing:** setters take native `Closure` unions, direct assignment, no
  param PHPDoc, no arity specs (e.g. `helperText(string|Markup|Closure|null $text)`).
  Props carry native types only; `evaluate()` supplies the generics. `Markup`
  wherever Filament types `string|Htmlable` (the escaping contract).
- **No `app()->make()`:** construct utilities with `new …($this)`. No service
  threading — reach shared services via `::getInstance()`.
- **Test layout:** Pest; mirror `src/` under `tests/Unit` (no kernel) and
  `tests/Integration` (kernel/container); `Fixtures/` mirrors `src/`. Inside
  `it()`/`test()` reach static helpers via `$this::`, never `self::`/`static::`.
  Don't call `bootKernel()` explicitly; `$this::getContainer()` auto-boots. Add
  fixtures by dropping classes under `tests/Fixtures/…`, never by editing the
  Kernel. `forms` currently ships a placeholder `Feature/ExampleTest.php` — adopt
  the Unit/Integration split as real tests land; don't entrench `Feature/`.
- **CSS:** semantic `sy-fo-*` classes only, every template root carries the bare
  `sy` marker class, `@apply` authoring in
  `packages/forms/assets/src/styles/components/<name>.css`, colors through the
  `--sy-*` runtime tokens. Full contract: [docs/styling.md](../styling.md).
- **Parity docs in the same change:** create/extend
  [forms/field.md](forms/field.md), [forms/text-input.md](forms/text-input.md),
  [forms/textarea.md](forms/textarea.md) (per-component convention:
  [README.md](README.md)); record the design's
  [Divergences](forms-fields.md#divergences-migrate-into-docsparityformsmd-when-code-lands)
  in those files; add the three to the [README index](README.md#index); flip the
  [roadmap.md](roadmap.md) step-6 note as the slice lands.
- **Self-review pass** (>~30 lines / >2 files): run static analysis + tests,
  re-read each modified file end-to-end, grep callers of any changed signature,
  report findings before declaring done.

---

## F1 — `Field` base + `HasName` + chrome concerns

- [ ] **F1** — the abstract `Field` (name/label-default/statePath/id cascade) and
  its wrapper-chrome concerns, deterministic, Pest Unit-verified with no rendering.

**Goal.** Give `forms` an abstract `Field` extending the schemas `Component`, with
`make(?string $name)` seeding name+statePath, name-humanized label default, and the
visual chrome setters (`markAsRequired`, `helperText`, `hint`+icon, `placeholder`,
`autofocus`) — all as native-`Closure`-union setters with getters. No template yet.

**Port from.**
- `vendor/filament/forms/src/Components/Field.php` — `make`/`__construct` (:73-88,
  :67-71), `getLabel`/`getDefaultLabel` (:724-742, the humanize), `$viewIdentifier`
  (:45). The 10 slot-key constants and slot setters (:47-65, :133-228) are **not**
  ported (Design divergence — direct getters).
- `vendor/filament/schemas/src/Components/Concerns/HasName.php` — `name`/`getName`.
- Chrome concerns: `forms/src/Components/Concerns/HasHelperText.php:12`,
  `HasHint.php` (:92/:102/:109/:120/:152/:160/:170/:175 — **omit** `hintAction`/
  `hintActions` :127/:137), `CanBeMarkedAsRequired.php:11/18`, `HasPlaceholder.php`,
  `CanBeAutofocused.php`.
- **Prototype salvage:** `git show origin/old:packages/forms/src/Components/Field.php`
  (the `make → ->name()->statePath()` shape; it lacks the label default — add it).

**Touch (current `main`).**
- **New** `packages/schemas/src/Components/Concerns/HasName.php` — `protected ?string
  $name = null`, `name(?string): static`, `getName(): ?string`.
- **New** `packages/forms/src/Components/Field.php` — abstract; `extends
  Sylament\Schemas\Components\Component`; `use HasName`, `use HasLabel { getLabel as
  getBaseLabel; }`, `use CanBeMarkedAsRequired, HasHelperText, HasHint,
  HasPlaceholder, CanBeAutofocused`; `make(?string $name = null)`;
  `getLabel(): string|Markup|null` = `getBaseLabel() ?? getDefaultLabel()`;
  `protected getDefaultLabel(): ?string` (humanize the name — verify
  `symfony/string` is installed before using `u()`/`AbstractString`, else plain
  `str_replace`/`ucfirst`; **no-guessing rule**). Also
  `getErrorMessages(): array` returning `[]` (the validation seam F2's wrapper reads).
- **New** `packages/forms/src/Components/Concerns/` — `CanBeMarkedAsRequired.php`,
  `HasHelperText.php`, `HasHint.php`, `HasPlaceholder.php`, `CanBeAutofocused.php`
  (direct-getter chrome; `HasHint` carries text/icon/color/tooltip getters, **no**
  actions).

**Locked decisions.** Divergence §2 (direct getters, no slot system, no hint
actions). `HasName` in `schemas` (Decision §3). Label default humanizes the name
(Filament `getDefaultLabel`).

**Acceptance criteria.**
- Fixture concrete field under `packages/forms/tests/Fixtures/Components/` (a bare
  `class FixtureField extends Field {}` — no template needed for Unit).
- **Unit** (`tests/Unit/Components/FieldTest.php`, mirror src): `Field::make('title')`
  ⇒ `getName() === 'title'`, `getStatePath(false) === 'title'`; `getLabel() ===
  'Title'` with no explicit label, `->label('X')` wins; `first_name` ⇒ "First name";
  `->markAsRequired()` ⇒ `isMarkedAsRequired()`; `helperText`/`hint`/`hintIcon`/
  `placeholder`/`autofocus` setters round-trip through getters (incl. `Closure`
  values resolved via `evaluate()`); `getErrorMessages() === []`.
- DoD gates green. After editing `packages/schemas` for `HasName`, remember it's a
  same-vendor path package — no composer step needed for a new class (autoload
  already covers `Sylament\Schemas\`).

**Notes / pitfalls.** `Field` stays abstract/renderless — do **not** give it a
`$template`. Keep `getLabel` override thin; humanizing belongs in `getDefaultLabel`.
PHPStan max: `getDefaultLabel(): ?string` (name may be null).

---

## F2 — `TextInput` + field-wrapper template + CSS

- [ ] **F2** — a rendering `TextInput` wrapped in the shared `field-wrapper.html.twig`
  chrome, styled, with `data-model` live binding; Integration render-verified.

**Goal.** Make one concrete field render end-to-end: the `TextInput` PHP class, the
reusable `field-wrapper.html.twig` (label/required-mark/hint/helper/error via direct
getters), the `TextInput.html.twig` embedding it, and the `sy-fo-*` CSS.

**Port from.**
- `vendor/filament/forms/src/Components/TextInput.php` — `type`/`getType`
  (:211/:248), `minLength`/`maxLength` (`CanBeLengthConstrained.php:16/25/32` — take
  the HTML-attr side only), input markup skeleton (`toEmbeddedHtml` :347-407). Affix/
  mask/reveal/datalist surface is **deferred** (Non-goals).
- `vendor/filament/forms/resources/views/components/field-wrapper.blade.php` (+ inline
  PHP `Field.php:402-515`) — the wrapper skeleton to translate to Twig.
- **Prototype salvage:** `git show origin/old:packages/forms/templates/components/TextInput.html.twig`
  (the `sy-fo-field`/`sy-fo-input` markup + `data-model`), and
  `…/assets/src/styles/components/{field,input}.css` (port near-verbatim).
- Twig `{% embed %}` + `{% block %}` (core Twig). Wrapper-reuse mechanism (Decision §5).
- Existing render precedent: `packages/schemas/templates/components/Section.html.twig`
  (`attrs()`, `renderComponent`, icon usage), and how `$template` renders via
  `ViewComponent` (`packages/support/src/Component/ViewComponent.php`).

**Touch (current `main`).**
- **New** `packages/forms/src/Components/TextInput.php` — `extends Field`,
  `protected string|Closure|null $template = '@SylamentForms/components/TextInput.html.twig'`,
  `type()`/`getType()` (+ `email`/`numeric`/`password` attribute sugar),
  `minLength()`/`maxLength()` (HTML attrs only). Add `getExtraTemplateData()` **only**
  for values the template can't reach via a single getter (keep minimal per CLAUDE.md).
- **New** `packages/forms/templates/components/field-wrapper.html.twig` — root
  `<div class="sy sy-fo-field" {{ attrs(...) }}>`; label (`if not
  component.isLabelHidden and component.getLabel is not null`), required `*` (`if
  component.isMarkedAsRequired`), hint in the label row (text + `component.getHintIcon`
  rendered through support's icon mechanism — follow `Section.html.twig`),
  `{% block content %}{% endblock %}`, helper text (`if
  component.getHelperText`), error region (`if component.getErrorMessages is not
  empty`). `for="{{ component.getId }}"` links label→input.
- **New** `packages/forms/templates/components/TextInput.html.twig` —
  `{% embed '@SylamentForms/components/field-wrapper.html.twig' %}{% block content %}
  <input class="sy-fo-input" type="{{ component.getType }}" id="{{ component.getId }}"
  name="{{ component.getName }}" data-model="{{ component.getDataModel }}"
  ... placeholder/maxlength/required/autofocus ...>{% endblock %}{% endembed %}`.
- **New** `packages/forms/assets/src/styles/components/field.css` + `input.css`
  (port prototype); wire from `packages/forms/assets/src/styles/index.css`
  (`@import './components/field.css' layer(components);` + input). Run `pnpm build`
  (support aggregates) and commit the regenerated `packages/support/assets/dist`
  (CI checks it matches a fresh build).

**Locked decisions.** Divergences §1/§2/§3 (direct-getter wrapper, no slot system,
Twig template not `toEmbeddedHtml`). Assets via support aggregation, no forms build
(Decision §4). `data-model` binds the absolute state path verbatim (live-state T2).

**Acceptance criteria.**
- Render-tested from **forms' own** Integration suite (needs a fixture Kernel
  registering TwigComponent + Stimulus + LiveComponent — mirror
  `packages/schemas/tests/Fixtures/Kernel.php`; note the CLAUDE.md caveat that
  support's Twig components are render-tested from schemas because only schemas'
  Kernel wires those bundles — forms gets its own here).
- **Integration** (`tests/Integration/Components/TextInputTest.php`): rendering
  `TextInput::make('title')->label('Title')->maxLength(120)->required()` emits a
  `sy-fo-field` wrapper with a `<label for="title">Title</label>`, a required `*`, a
  `<input class="sy-fo-input" name="title" id="title" maxlength="120" required
  data-model="…">`; `->helperText('h')` and `->hint('n')` render in the right slots;
  `->hiddenLabel()` makes the label sr-only. Assert `data-model` matches `live()` vs
  default (reuse live-state T2's expectations).
- `pnpm test:types`/`test:lint` green; committed `assets/dist` matches a fresh
  `pnpm build`. DoD gates green.

**Notes / pitfalls.** The wrapper is authored once and reused — do **not** inline
chrome into `TextInput.html.twig`. `{% embed %}` inherits `component` into the block;
confirm the render path passes `component` (it does — `ViewComponent::render`).
`sy-fo-input` uses `ring-primary-*`/`gray-*` which resolve via support's `@theme` at
aggregation — no forms-local theme needed.

---

## F3 — `Textarea`

- [ ] **F3** — a second field reusing the wrapper unchanged (the reuse proof).

**Goal.** Add `Textarea` to demonstrate the wrapper is shared chrome, not
`TextInput`-specific.

**Port from.** `vendor/filament/forms/src/Components/Textarea.php` — `extends Field`
(not `TextInput`), `rows()`/`cols()` (autosize/`HasExtraInputAttributes` deferred).
Prototype: `git show origin/old:packages/forms/src/Components/TextArea.php` +
`templates/components/TextArea.html.twig` (note: Filament spells it `Textarea`; use
`Textarea` — preserve Filament's casing, not the prototype's `TextArea`).

**Touch (current `main`).**
- **New** `packages/forms/src/Components/Textarea.php` — `extends Field`,
  `$template = '@SylamentForms/components/Textarea.html.twig'`, `rows(int|Closure)`,
  `cols(int|Closure)` + getters.
- **New** `packages/forms/templates/components/Textarea.html.twig` — same
  `{% embed field-wrapper %}` with `<textarea class="sy-fo-input" rows cols
  data-model>` in `{% block content %}`. The `textarea.sy-fo-input` rule already
  exists in `input.css` (ported in F2).

**Locked decisions.** `Textarea extends Field` (Decision §6). Filament casing
`Textarea`.

**Acceptance criteria.**
- **Integration** (`tests/Integration/Components/TextareaTest.php`): renders the same
  `sy-fo-field` wrapper (label/hint/helper/error identical to F2) around a
  `<textarea class="sy-fo-input" rows="…" data-model="…">`.
- DoD gates green.

**Notes / pitfalls.** If F2's wrapper needs any change to accommodate `<textarea>`,
that's a smell — the block should be the only difference. Keep the wrapper untouched.

---

## F4 — Live demo host + browser drive

- [ ] **F4** — a shipped-shape `#[AsLiveComponent]` host proving real fields consume
  the live-state loop, verified with the UX harness **and** a browser drive.

**Goal.** Close the epic's thesis: typing in one real field drives another via
`afterStateUpdated`, end to end, in a browser.

**Port from.**
- **Prototype salvage:** `git show origin/old:packages/forms/tests/Fixtures/Twig/Components/ArticleForm.php`
  + its template — the `#[AsLiveComponent]` + `HasSchemas` + `#[LiveProp] $data` +
  `schema()` + `#[LiveAction] save()` shape. Replace the prototype's odd
  `$set('slug', $oldState)` with a real slugify (`title` → `slug`).
- live-state T2's Integration test (`packages/schemas/tests/Integration/LiveStateTest.php`)
  and browser approach — mirror the `InteractsWithLiveComponents` usage and the
  Chrome drive + GIF.

**Touch (current `main`).**
- **New fixtures** under `packages/forms/tests/Fixtures/`: an `#[AsLiveComponent]`
  host (`use DefaultActionTrait, HasSchemas`; `#[LiveProp(writable: true)] public
  array $data = []`; `schema()` with `TextInput::make('title')->live(onBlur: true)
  ->afterStateUpdated(fn (?string $state, Set $set) => $set('slug', slugify($state)))`
  and `TextInput::make('slug')`), plus a `Textarea` to exercise F3; its Twig template
  (`renderSchema('schema')` + bare `<button {{ live_action('save') }}>`).
- **New** `packages/forms/tests/Integration/LiveFieldTest.php` — mount the host via
  `InteractsWithLiveComponents`; `->set('data.title', 'Hello World')` then `->render()`
  shows `slug === 'hello-world'`; the no-change case doesn't fire.
- **Browser drive:** serve the fixture host (throwaway `public/index.php` against the
  test Kernel, or `symfony server:start`), drive with `mcp__claude-in-chrome__*` —
  type into `title`, confirm the debounced round-trip updates `slug` live; record a
  GIF. If standing up a served app is blocked, the harness Integration test is the
  required gate and the browser drive is deferred with a **prominent note** — don't
  silently skip it.

**Locked decisions.** Monolithic-host topology + whole-schema re-render (live-state
§3). `afterStateUpdated` fires via the T2 `#[PreDehydrate]`/`#[PostHydrate]` bridge —
this task **consumes** it, adds nothing to the state layer.

**Acceptance criteria.**
- The live loop passes under `InteractsWithLiveComponents`; the browser drive shows
  `slug` updating as `title` is typed; a GIF is recorded (or the deferral is flagged).
- Epic wrap-up: `forms/field.md`/`text-input.md`/`textarea.md` created with the
  divergences; README index rows added; roadmap step-6 note updated to reflect the
  landed slice (validation + Select/Checkbox remaining). DoD gates green.

**Notes / pitfalls.** `->live(onBlur: true)` (or a debounce) is required for the
demo to feel live — a bare `afterStateUpdated` without `live()` only syncs on submit
(`norender`). Provide a small `slugify()` helper local to the fixture (don't add a
support helper for a test-only need).
