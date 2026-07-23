# First Forms fields — epic design

> **Scope:** the first real form fields (`Field` base + `TextInput` + `Textarea`)
> as the first *first-party* consumer of the live-state substrate
> ([live-state.md](live-state.md), landed T1–T5/T7). Until now the reactive loop
> was exercised only by test fixtures; this epic ships components that use it.
> This is the **first slice of [roadmap.md](roadmap.md) step 6** ("Forms — Field
> base + a few fields") — validation and Select/Checkbox are the deferred
> remainder (see [Non-goals](#non-goals-record-dont-build)). Task briefs:
> [forms-fields-tasks.md](forms-fields-tasks.md).
>
> Discovery read Filament v5 (`vendor/filament/forms/`, `vendor/filament/schemas/`)
> and the throwaway `origin/old` prototype's `packages/forms`. Every load-bearing
> claim is source-cited so the task briefs port without re-deriving. Reference
> roots: [`vendor/filament/forms/src`](../../vendor/filament/forms/src),
> [`packages/schemas/src`](../../packages/schemas/src).

## Sequencing — taken out of strict tracer order (deliberate)

The roadmap orders the tracer 1→2→4→5→6; this epic jumps to **step 6 ahead of
1/2/4/5** to give the just-landed reactive spine a shipped consumer. It is scoped
so that reordering costs nothing — verified there is **no** hidden dependency on
the skipped steps:

- **Step 1/2 (enums, color, shared UI kit).** Styling rides the color tokens
  **support already ships** (`--sy-primary-*` / `--sy-gray-*`, bridged to Tailwind
  in `packages/support/assets/src/styles/index.css`), so a properly-styled input
  needs no color epic. The demo's submit is a bare `<button {{ live_action('save') }}>`
  — no first-party Button/modal/dropdown required.
- **Step 4 (layout containers).** `Schema` + `Section` + `Grid` already lay out a
  form; nothing new needed to render fields in a column.
- **Step 5 (Doctrine record-binding, = live-state T6).** The demo binds an
  in-memory `#[LiveProp] public array $data` bag — no entity, no `getRecord()`.
  Record-binding stays the next thread; fields don't need it to prove the loop.

## The port shape at a glance

`Field` spans three Filament packages but grafts cleanly onto what we already have:

| Filament | Sylament (this epic) |
| --- | --- |
| `Field extends Filament\Schemas\Components\Component` (`forms/src/Components/Field.php:31`) | `Field extends Sylament\Schemas\Components\Component` — inherits state/`statePath`/id/key/hidden/columns already on main |
| `Field use HasName, HasLabel` (schemas) | `use HasName` (**new in schemas**, parity placement) + `use HasLabel` (exists, `packages/schemas/src/Components/Concerns/HasLabel.php`) |
| `Field use CanBeMarkedAsRequired, HasHelperText, HasHint, CanBeAutofocused` (forms) + `HasPlaceholder` | mirror as `forms` concerns (chrome via direct getters — see Design) |
| `TextInput implements HasEmbeddedView` + `toEmbeddedHtml()` (`forms/src/Components/TextInput.php:309`) | `TextInput` sets `$template`, renders via `ViewComponent` Twig path (as the prototype did) |
| `field-wrapper.blade.php` / inline PHP wrapper (`Field.php:402-515`) | one `field-wrapper.html.twig`, pulled in by field templates via Twig `{% embed %}` |
| `CanBeValidated` (Laravel rule DSL) | **deferred** — its own epic (Non-goals) |

## Filament reference — what we port

**`Field` base** (`forms/src/Components/Field.php`).
- `make(?string $name)` → container-resolved instance; `final __construct(string $name)`
  sets `name($name)` **and** `statePath($name)` (`:67-88`). statePath defaults to the
  field name.
- `getLabel()` returns the explicit label else `getDefaultLabel()`, which humanizes
  the name: kebab → spaces → ucfirst (`title`→"Title", `first_name`→"First name")
  (`:724-742`). Filament aliases the trait's `getLabel`→`getBaseLabel` and defines a
  new `getLabel` on `Field`.
- `getId()` = custom id ?? `getKey()`; key defaults to the (non-absolute) statePath
  → the cascade **id ← key ← statePath ← name** is already provided by the schemas
  `HasId`/`HasKey`/`HasState` concerns on main.
- `$viewIdentifier = 'field'`; `Field` itself is abstract/renderless — concrete
  subclasses supply the view.

**Wrapper chrome concerns.**
- `HasHelperText`: `helperText(string|Htmlable|Closure|null)` (`HasHelperText.php:12`)
  — Filament pushes a `Text` into the `belowContent` slot.
- `HasHint`: `hint()`/`getHint()` (`:92`/`:152`), `hintIcon(icon, tooltip=null)`
  (`:109`), `hintColor()` (`:102`), `hintIconTooltip()` (`:120`), `hintAction()`/
  `hintActions()` (`:127`/`:137`). `setUpHint()` wires these into the `afterLabel`
  slot.
- `CanBeMarkedAsRequired`: `markAsRequired(bool|Closure|null=true)` (`:11`),
  `isMarkedAsRequired()` falling back to `isRequired()` (`:18`) — **visual `*` only**,
  attaches no rule.
- `HasPlaceholder`: `placeholder(string|Closure|null)` / `getPlaceholder()` — the
  HTML `placeholder` attr.

**Field-wrapper template** (`forms/resources/views/components/field-wrapper.blade.php`,
structurally mirrored by the inline PHP at `Field.php:402-515`). Skeleton: a label
column (`aboveLabel` slot · label-ctn: `beforeLabel` · `<label for={id}>` with
`<span>` content + `<sup>*</sup>` required mark · `afterLabel` → hint renders here ·
`belowLabel`) and a content column (`aboveContent` · the input · `belowContent` →
helper text here · error region: `<ul>`/`<p>` with `data-validation-error` ·
`belowErrorMessage`). `for="{id}"` links label→input.

**`TextInput`** (`forms/src/Components/TextInput.php`).
- Type surface: `type()`/`getType()` (`:211`/`:248`), `email()` `numeric()` `integer()`
  `password()` `tel()` `url()` — each also attaches a Laravel validation rule (we take
  the **attribute/inputmode** side, drop the rule side this epic).
- Length/range: `minLength()`/`maxLength()`/`length()` (`CanBeLengthConstrained.php:16/25/32`)
  → HTML `minlength`/`maxlength` **and** validation rules; `minValue()`/`maxValue()`
  → `min`/`max`. Attribute side only this epic.
- Large deferred surface: affixes (`HasAffixes`), masks, password-reveal, datalist,
  autocomplete, copyable — clean breadth-fill later.
- Input markup (`toEmbeddedHtml`, `:347-407`): `<input class="fi-input" type inputmode
  id min max minlength maxlength placeholder required readonly ... wire:model=...>` —
  the `wire:model` binding becomes our `data-model="{{ component.getDataModel }}"`.

**`Textarea`** extends `Field` directly (not `TextInput`); adds `rows()`/`cols()`
(+ autosize, deferred).

**Validation** (`forms/src/Components/Concerns/CanBeValidated.php`) — a Laravel
rule-string DSL (`'email'`, `'max:255'`, `'required_if:other,val'`) assembled into a
flat `array<string|object|Closure>` keyed by absolute state path
(`getValidationRules()` `:849`, `dehydrateValidationRules()` `:906`). Core scalar
rules map to Symfony constraints; cross-field/relative-state rules and DB
rules (`exists`/`unique`, Eloquent) do not. This is its own translation epic — see
Non-goals. Note `markAsRequired` (visual) is **already decoupled** from `required()`
(rule) in Filament, so the required marker ports without the validation engine.

## Current graft surface (what's on main)

- `Schemas\Components\Component` (`packages/schemas/src/Components/Component.php`) —
  the base `Field` extends. Already `use`s `HasState` (statePath, casts, get/set,
  `afterStateUpdated`), `HasStateBindingModifiers` (`live`/`getDataModel`), `HasId`,
  `HasKey`, `CanBeHidden`, `HasColumns`, `HasExtraAttributes`. **No `name`, no
  `HasLabel`** on the base (both are added by `Field`).
- `Schemas\Components\Concerns\HasLabel` — full surface (`label`/`hiddenLabel`/
  `isLabelHidden`/`getLabel`/`hasCustomLabel`) but `getLabel()` returns the set label
  verbatim — **no** name-humanizing default (`Field` adds it).
- `Schemas\Components\Concerns\HasState` — `statePath(?string)` (`:55`),
  `getStatePath(bool $isAbsolute=true)` (`:63`) already present; `Field::make` seeds
  the statePath from the name.
- **Assets aggregate centrally.** `packages/support/assets/src/styles/index.css`
  `@import`s every sibling's `src/styles/index.css` (incl.
  `../../../../forms/assets/src/styles/index.css`) and compiles them in **one**
  Tailwind build under support's `@theme`. `packages/schemas/assets` is the precedent
  for a non-support package shipping component CSS: `index.css` + `components/*.css`,
  **no** `package.json`, **no** controller, **no** `dist`. The forms stub already
  exists (`packages/forms/assets/src/styles/index.css`, with the example import
  comment).
- **Template namespace** `@SylamentForms/components/<Name>.html.twig` is
  auto-registered by the bundle's `templates/` dir (as `@SylamentSchemas` is for
  `packages/schemas/templates/`). The prototype already used this path.

## `origin/old` prototype — prior art (mine for shape, merge nothing)

Faithful salvage: `Field extends schemas Component; use HasLabel; make(?string $name)
→ ->name($name)->statePath($name)` (near-identical to Filament, minus the label
default); `TextInput`/`TextArea` as thin `$template`-pointing subclasses; the
`sy-fo-field` / `sy-fo-input` CSS (`@apply` over primary/gray — ports near-verbatim);
the `ArticleForm` `#[AsLiveComponent]` demo (two `TextInput`s, `afterStateUpdated`
cross-writing). Its state wiring is the known dead end already superseded by
live-state — carry only the **forms** shape.

## Design

### `Field` base (`packages/forms/src/Components/Field.php`)

Abstract, `extends Sylament\Schemas\Components\Component`.

```
use HasName;                                   // new schemas concern
use HasLabel { getLabel as getBaseLabel; }     // schemas concern, aliased
use CanBeMarkedAsRequired, HasHelperText, HasHint, HasPlaceholder, CanBeAutofocused;

public static function make(?string $name = null): static
    => parent::make()->name($name)->statePath($name);

public function getLabel(): string|Markup|null
    => $this->getBaseLabel() ?? $this->getDefaultLabel();

protected function getDefaultLabel(): ?string   // humanize name, Filament-parity
```

`getId()`/key/statePath cascade is inherited unchanged. `getDefaultLabel()`
humanizes the name (kebab→spaces→ucfirst); pick the tool at implementation time
(verify `symfony/string` availability per the no-guessing rule, else plain PHP).

### `HasName` (`packages/schemas/src/Components/Concerns/HasName.php`, new)

Parity placement (Filament keeps it in `schemas`). `protected ?string $name`,
`name(?string): static`, `getName(): ?string`. Small, mirrors
`vendor/filament/schemas/src/Components/Concerns/HasName.php`.

### The field wrapper — direct getters, one Twig template

`packages/forms/templates/components/field-wrapper.html.twig` renders the chrome —
label (+ hidden-label sr-only variant), required mark, hint (text + icon + tooltip),
the input, helper text, and an **error region** — from **field getters**, not
Filament's slot-schema system. Field templates pull it in with Twig `{% embed %}`
placing their input in `{% block content %}` (the embed inherits `component` into
scope). Hint icon renders through support's existing icon extension.

The error region is built structurally but fed `Field::getErrorMessages(): array`
returning `[]` this epic (documented seam the validation epic fills); the template
renders it only `if component.getErrorMessages is not empty`.

**Divergence rationale:** the public API (`->label()`, `->helperText()`, `->hint()`,
`->hintIcon()`, `->markAsRequired()`) is byte-identical to Filament — only the
internal mechanism (properties+getters vs `Text`/`Icon` components pushed into 10
schema slots) is simpler. The slot system + hint **actions** land when a consumer
needs arbitrary slot injection (drags in the actions package); premature now.

### Fields

- `TextInput` (`packages/forms/src/Components/TextInput.php`) — `extends Field`,
  `$template = '@SylamentForms/components/TextInput.html.twig'`. This epic:
  `type()`/`getType()` (+ `email`/`numeric`/`password` as attribute/inputmode sugar),
  `minLength()`/`maxLength()` as **HTML attrs only**, `placeholder` via concern.
  Template: `{% embed field-wrapper %}{% block content %}<input class="sy-fo-input"
  ... data-model="{{ component.getDataModel }}">{% endblock %}{% endembed %}`.
- `Textarea` (`packages/forms/src/Components/Textarea.php`) — `extends Field`,
  `rows()`/`cols()`, its own template embedding the same wrapper with `<textarea>` —
  the second field that proves the wrapper is reused, not baked into `TextInput`.

### Assets (nearly free)

Add `packages/forms/assets/src/styles/components/{field,input}.css` (port the
prototype's `@apply` over `primary`/`gray`) and wire them from the existing
`packages/forms/assets/src/styles/index.css` stub
(`@import './components/field.css' layer(components);` …). support's build
aggregates them; **no** package.json, controller, dist, pnpm-workspace entry, or
bundle asset registration. `SylamentFormsBundle` stays bare (no new services/Twig
extensions — fields reuse support's `attrs`/`renderComponent`/icon extensions and
schemas' `renderSchema`).

### Demo + verification (mirrors live-state T2)

A fixture `#[AsLiveComponent]` host under `packages/forms/tests/Fixtures/` with two
fields — `title` (`->live(onBlur: true)`) and `slug` — where `title`'s
`afterStateUpdated` slugifies into `slug`; its Twig template renders
`renderSchema('schema')` + a bare submit button. Test Kernel registers
LiveComponent + TwigComponent + Stimulus (mirror schemas' fixture Kernel). Verify:
Unit (name/label-default/statePath/id cascade, concern setters); Integration (render
assertions on wrapper markup + `data-model` + `name`/`id`; the live loop via
`InteractsWithLiveComponents` — set `title`, assert `slug` updated); Browser (serve
the host, type into `title`, watch `slug` update live, record a GIF).

## Decisions

1. **Scope = `TextInput` + `Textarea` + real wrapper, validation deferred**
   (user-approved). The demoable reactive loop needs no validation.
2. **Wrapper chrome via direct getters, not Filament's 10-slot schema system**
   (user-approved). Public API preserved; slot system + hint actions deferred.
   **Superseded (follow-on epic):** the 10-slot system was later ported — helper
   text → `below_content`, hint text/icon → `after_label` (via `setUpHint()`), plus
   a new schemas `Icon` component and `Text::color()`; only hint **actions** remain
   deferred. Current state: [forms/field.md](forms/field.md),
   [schemas/icon.md](schemas/icon.md).
3. **`HasName` placed in `schemas`** (Filament parity placement), not `forms`.
4. **Assets ride support's central aggregation** — forms contributes CSS only, no
   build of its own (schemas precedent).
5. **Wrapper reuse via a shared `FieldWrapper` Twig component** (revised from the
   original `{% embed %}` plan) — `<twig:SylamentForms:FieldWrapper :field="component">`
   with a `content` slot, matching the support `<twig:SylamentSupport:…>` components
   and Filament v4's `<x-filament-forms::field-wrapper :field>` Blade component + slot.
6. **`Textarea extends Field`** directly (Filament-parity), not `TextInput`.
7. **Field styling rides support's existing `--sy-primary-*`/`--sy-gray-*` tokens**
   — no dependency on the (skipped) color/UI-kit epics.

## Divergences (migrate into `docs/parity/forms/*.md` when code lands)

- **Field-wrapper chrome via direct getters**, not Filament's slot-schema system
  (`aboveLabel`…`belowErrorMessage` + `Text`/`Icon`-in-slot). Public API identical;
  the slot system is deferred until a consumer needs arbitrary slot injection.
  **Reconciled in the follow-on slots epic** — the wrapper now renders the ten
  child-schema slots; see [forms/field.md](forms/field.md).
- **Hint actions omitted** (`hintAction()`/`hintActions()`) — needs the actions
  package; hint text/icon/tooltip ship now.
- **Render via Twig template**, not Filament's `HasEmbeddedView`/`toEmbeddedHtml()`
  (the general schemas→Twig porting stance, already recorded for `ViewComponent`).

## Non-goals (record, don't build)

- **Validation** — `CanBeValidated`'s Laravel-rule DSL → Symfony Validator is its
  own epic (rule→constraint translation table, error rendering into the slot this
  epic builds, `validationAttribute`/messages). The visual `required()` marker
  ships now; actual rules do not. Create the `field-validation` infrastructure
  anchor when that epic starts.
- **`Select` / `Checkbox`** — the rest of roadmap step 6. `Select` brings
  options/enum handling; `Checkbox` brings boolean state (the `BooleanStateCast`
  from live-state T3 is ready for it).
- **`TextInput` breadth** — affixes, masks, password-reveal, datalist, autocomplete,
  copyable; and each `$entangle`-style client-local field (flagged in live-state §6)
  needs a bespoke Stimulus translation when it lands.

## Task plan

Cold-session briefs in [forms-fields-tasks.md](forms-fields-tasks.md). Vertical
slice first: **F1 → F2** make one field render and prove the chrome before the
second field and the live payoff.

1. **F1 — `Field` base + `HasName` + chrome concerns.** PHP only, Unit-tested
   (name/label-default/statePath/id cascade, concern setters). No rendering yet.
2. **F2 — `TextInput` + field-wrapper template + CSS.** One field renders; render
   assertions on wrapper markup + `data-model` + `name`/`id`.
3. **F3 — `Textarea`.** Second field reusing the wrapper (small; the reuse proof).
4. **F4 — Live demo host + browser drive.** `InteractsWithLiveComponents` loop test
   (typing `title` → `slug`) + a browser-driven GIF.

## Renames to record when code lands

None expected — Filament's public field API names (`make`, `name`, `label`,
`helperText`, `hint`, `hintIcon`, `markAsRequired`, `placeholder`, `type`,
`maxLength`, `rows`) are preserved verbatim. If `getDefaultLabel`/`getBaseLabel`
aliasing needs a Sylament-specific name, record it in [renames.md](renames.md).
