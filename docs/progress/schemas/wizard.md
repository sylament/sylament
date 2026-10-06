# Wizard (schemas)

`Sylament\Schemas\Components\Wizard` — the multi-step layout component: a header
stepper, one panel per [`Step`](step.md), a footer carrying its own Back/Next
pair plus caller-supplied cancel/submit slots, per-step validation on the way
forward, and optional query-string persistence of the current step. It extends
the schemas base [`Component`](component.md) and ports Filament's
[`Wizard`](../../../vendor/filament/schemas/src/Components/Wizard.php).

The switching itself is client state: the server renders the step
`getStartStep()` picks (its `sy-active` panel, the header's
`sy-active`/`sy-completed`/`disabled`/status text, the footer slots'
`sy-hidden`), and the `sylament--schemas--wizard` Stimulus controller owns every
switch after that. Filament does the same with Alpine, from an `x-data` object
plus `x-show`/`x-bind:class` expressions; here the initial state is real markup
and the transitions are class toggles UX Live's mutation tracker replays across
a morph, so a no-JS page still shows one step with every panel's inputs in the
form.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/Components/Wizard.php`](../../../vendor/filament/schemas/src/Components/Wizard.php)
  — the component, its key derivation, `nextStep()`/`goToStep()`, the two footer
  actions and the `toEmbeddedHtml()` render
- [`schemas/resources/js/components/wizard.js`](../../../vendor/filament/schemas/resources/js/components/wizard.js)
  — the Alpine component the Stimulus controller ports
- [`schemas/resources/css/components/wizard.css`](../../../vendor/filament/schemas/resources/css/components/wizard.css)
  — the stylesheet [`wizard.css`](../../../packages/schemas/assets/src/styles/components/wizard.css) ports
- [`support/src/Concerns/HasExtraAlpineAttributes.php`](../../../vendor/filament/support/src/Concerns/HasExtraAlpineAttributes.php)
  — the Alpine passthrough Filament composes and Sylament omits

## Surface

Every point of this component. _Blocked by_ names what has to land first.
_Detail_ carries a **diverges** link to the permanent note in
[Divergences](#divergences), a **detail** link to the write-up in
[Details](#details), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make(array\|Closure $steps = [])`](../../../packages/schemas/src/Components/Wizard.php) | [`Wizard::make()`](../../../vendor/filament/schemas/src/Components/Wizard.php) | done | — | — |
| [`steps(array\|Closure)`](../../../packages/schemas/src/Components/Wizard.php) | [`Wizard::steps()`](../../../vendor/filament/schemas/src/Components/Wizard.php) | done | — | — |
| [`getSteps(): list<Step>`](../../../packages/schemas/src/Components/Wizard.php) | [`Wizard::toEmbeddedHtml()` (inline filter)](../../../vendor/filament/schemas/src/Components/Wizard.php) | done | — | [diverges](#every-index-is-an-index-into-the-visible-steps) |
| [Key derivation (`wizard` / `{statePath}::wizard` / `slug(label)::…`, non-inheritable)](../../../packages/schemas/src/Components/Wizard.php) | [`Wizard::setUp()`](../../../vendor/filament/schemas/src/Components/Wizard.php) | done | — | [diverges](#the-key-keeps-filaments-state-path-segment-which-section-drops) |
| [`nextStep(int)` — `#[ExposedLiveComponentMethod]`](../../../packages/schemas/src/Components/Wizard.php) | [`Wizard::nextStep()`](../../../vendor/filament/schemas/src/Components/Wizard.php) | done | — | [diverges](#nextstep-hardens-an-out-of-range-index-and-fills-the-next-step-through-the-schema-tier-walker) · [semantics](#the-step-index-advances-before-validation-runs) |
| [`previousStep(int)` — `#[ExposedLiveComponentMethod]`](../../../packages/schemas/src/Components/Wizard.php) | [`Wizard::previousStep()`](../../../vendor/filament/schemas/src/Components/Wizard.php) | done | — | [semantics](#previousstep-moves-the-server-index-and-announces-nothing) |
| [`goToStep(string)`](../../../packages/schemas/src/Components/Wizard.php) | [`Wizard::goToStep()`](../../../vendor/filament/schemas/src/Components/Wizard.php) | done | — | — |
| [Browser events `next-wizard-step` / `go-to-wizard-step`](../../../packages/schemas/src/Components/Wizard.php) | [`Wizard::nextStep()`/`goToStep()`](../../../vendor/filament/schemas/src/Components/Wizard.php) | done | — | — |
| [`getNextAction()` / `nextAction()` / `getNextActionName()`](../../../packages/schemas/src/Components/Wizard.php) | [`Wizard::getNextAction()`](../../../vendor/filament/schemas/src/Components/Wizard.php) | done | — | [diverges](#the-footer-actions-are-inert-triggers-clicked-through-their-wrapper) |
| [`getPreviousAction()` / `previousAction()` / `getPreviousActionName()`](../../../packages/schemas/src/Components/Wizard.php) | [`Wizard::getPreviousAction()`](../../../vendor/filament/schemas/src/Components/Wizard.php) | done | — | [diverges](#the-footer-actions-are-inert-triggers-clicked-through-their-wrapper) |
| `livewireTarget('callSchemaComponentMethod')` on the next action | [`Wizard::getNextAction()`](../../../vendor/filament/schemas/src/Components/Wizard.php) | skipped | — | [diverges](#the-footer-actions-are-inert-triggers-clicked-through-their-wrapper) |
| [`cancelAction()` / `getCancelAction()` / `submitAction()` / `getSubmitAction()`](../../../packages/schemas/src/Components/Wizard.php) | [`Wizard::cancelAction()`](../../../vendor/filament/schemas/src/Components/Wizard.php) | done | — | [diverges](#the-footer-slots-take-an-action-a-markup-or-a-string-and-the-three-way-split-is-resolved-in-php) |
| [`startOnStep()` / `getStartStep()`](../../../packages/schemas/src/Components/Wizard.php) | [`Wizard::getStartStep()`](../../../vendor/filament/schemas/src/Components/Wizard.php) | done | — | [diverges](#the-query-string-arm-reads-through-an-injected-requeststack-and-compares-the-absolute-step-key) |
| [`getStartStepKey(): ?string`](../../../packages/schemas/src/Components/Wizard.php) | — | done | — | [diverges](#getstartstepkey-is-sylament-only) |
| [`persistStepInQueryString()` / `getStepQueryStringKey()` / `isStepPersistedInQueryString()`](../../../packages/schemas/src/Components/Wizard.php) | [`Wizard::persistStepInQueryString()`](../../../vendor/filament/schemas/src/Components/Wizard.php) | done | — | [diverges](#the-query-string-arm-reads-through-an-injected-requeststack-and-compares-the-absolute-step-key) |
| [`skippable()` / `isSkippable()`](../../../packages/schemas/src/Components/Wizard.php) | [`Wizard::skippable()`](../../../vendor/filament/schemas/src/Components/Wizard.php) | done | — | — |
| [`hiddenHeader()` / `isHeaderHidden()`](../../../packages/schemas/src/Components/Wizard.php) | [`Wizard::hiddenHeader()`](../../../vendor/filament/schemas/src/Components/Wizard.php) | done | — | — |
| [`getCurrentStepIndex()` / `currentStepIndex()`](../../../packages/schemas/src/Components/Wizard.php) | [`Wizard::getCurrentStepIndex()`](../../../vendor/filament/schemas/src/Components/Wizard.php) | done | — | — |
| [`CanBeContained` (`contained()`)](../../../packages/support/src/Components/Concerns/CanBeContained.php) | [`support/Concerns/CanBeContained.php`](../../../vendor/filament/support/src/Concerns/CanBeContained.php) | done | — | — |
| [`HasLabel` (the header's `aria-label`)](../../../packages/schemas/src/Components/Concerns/HasLabel.php) | [`Concerns/HasLabel.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasLabel.php) | done | — | — |
| [Per-step validation on the way forward](../../../packages/schemas/src/Components/Wizard.php) | [`Wizard::nextStep()`](../../../vendor/filament/schemas/src/Components/Wizard.php) | done | — | [can-be-validated.md](can-be-validated.md#a-components-child-schema-is-keyed-by-that-component-not-by-the-root) |
| [Rendering (`Wizard/index.html.twig`)](../../../packages/schemas/templates/partials/Wizard/index.html.twig) | [`Wizard::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Wizard.php) | done | — | [diverges](#the-server-renders-the-start-step-alpines-x-show-pairs-become-state-classes-and-css) |
| [Styling (`wizard.css`)](../../../packages/schemas/assets/src/styles/components/wizard.css) | [`components/wizard.css`](../../../vendor/filament/schemas/resources/css/components/wizard.css) | done | — | [styles.md](styles.md) |
| Enter inside a non-last step advances instead of submitting | [`Wizard::getAlpineSubmitHandler()` + `Step::formWrapper()`](../../../vendor/filament/schemas/src/Components/Wizard.php) | in progress | the `sylament--schemas--wizard` controller | [diverges](#alpinesubmithandler-and-formwrapper-are-skipped-their-behaviour-is-a-controller-keydown) |
| `alpineSubmitHandler()` / `getAlpineSubmitHandler()` | [`Wizard::alpineSubmitHandler()`](../../../vendor/filament/schemas/src/Components/Wizard.php) | skipped | — | [diverges](#alpinesubmithandler-and-formwrapper-are-skipped-their-behaviour-is-a-controller-keydown) |
| Extra Alpine attributes (`HasExtraAlpineAttributes`) | [`support/Concerns/HasExtraAlpineAttributes.php`](../../../vendor/filament/support/src/Concerns/HasExtraAlpineAttributes.php) | skipped | — | [diverges](#extra-alpine-attribute-passthrough-is-not-ported), [diverges](../support/component-attribute-bag.md#extra-alpine-attributes-are-not-ported) |
| `wire:ignore.self` on the root | [`Wizard::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Wizard.php) | skipped | — | [diverges](#the-server-renders-the-start-step-alpines-x-show-pairs-become-state-classes-and-css) |
| The `x-ref="stepsData"` hidden input | [`Wizard::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Wizard.php) | skipped | — | [diverges](#the-server-renders-the-start-step-alpines-x-show-pairs-become-state-classes-and-css) |

Coverage: the API, the key derivation, the query-string arm (present / unknown
value / no stack), the two footer actions and their modifiers, and
`previousStep()`'s clamp by
[`WizardTest`](../../../packages/schemas/tests/Unit/Components/WizardTest.php);
the whole DOM contract — the controller values and its four window listeners, the
header's `role="list"`/`aria-current`/`disabled`/status text/numbering/separators,
the panels' `role="group"` naming, the four footer slots and their `sy-hidden`
states for start steps 1, 2 and 3, `skippable()`, `hiddenHeader()`,
`persistStepInQueryString()`, a disabled footer action dropping its click, and
all three footer-slot shapes — by
[`WizardRenderTest`](../../../packages/schemas/tests/Integration/Components/WizardRenderTest.php),
which also pins the container registration of `Step` as a non-shared service;
the live loop — a failing step's `ValidationException`, its sticky errors scoped
to that step, the `next-wizard-step`/result events on a passing step, `skippable`
skipping validation entirely, a `Halt` from `beforeValidation()` and
`previousStep()` — by
[`WizardLiveTest`](../../../packages/schemas/tests/Integration/Concerns/WizardLiveTest.php).
No test exercises the Stimulus controller (there is no JS runner in this repo);
its half of the contract rests on the browser pass.

## Details

### Enter inside a non-last step

The keydown listener is on the root (`keydown->…#onKeydown`) and the behaviour it
implements is described in the
[divergence below](#alpinesubmithandler-and-formwrapper-are-skipped-their-behaviour-is-a-controller-keydown);
the PHP half — the listener attribute — ships here, the JS half with the
controller. Next step: nothing on this side.

## Semantics

Behaviour worth stating once, that the code alone does not make obvious.

### The step index advances before validation runs

`nextStep()` calls `currentStepIndex($i + 1)` **before**
`getChildSchema()->validate()`, so a failing step leaves the server-side index
pointing at the step the user could not reach. Filament does exactly this and it
is deliberate on both sides: the index only feeds `goToStep()`'s
backwards-or-skippable guard and `getCurrentStepIndex()`, both of which want
"the furthest step reached", while the *rendered* step comes from
`getStartStep()` and the client's own state. A `ValidationException` also aborts
the whole action, so nothing downstream reads the advanced index that request.

### previousStep() moves the server index and announces nothing

Filament's `previousStep()` clamps to the first step and writes the index, with
no `dispatch()` — the Alpine component moves the view itself and never calls it.
Ours is the same, kept for API parity: the Stimulus controller likewise walks
back on its own, so the only thing a `previousStep()` round trip produces is the
`callSchemaComponentMethod` result event.

## Divergences

Deliberate deviations from Filament — permanent unless the note says what would
reverse it.

### Every index is an index into the visible steps

Filament renders `array_filter(getComponents(), fn ($c) => $c instanceof Step)`
in the header but indexes `nextStep()` into `array_values(getComponents())` — the
**unfiltered** list — so a non-`Step` child, or a hidden one, silently shifts
what "step 2" means between the markup the browser counts and the object the
server validates. `Wizard::getSteps()` is the one list here: visible `Step`
children, re-indexed, used by the header, the panels, `nextStep()`,
`goToStep()`, `getStartStep()` and `getStartStepKey()` alike. Filament also
never guards the index it is handed, so an out-of-range `nextStep()` is a PHP
warning plus a null-method fatal there; see the next note.

### nextStep() hardens an out-of-range index and fills the next step through the schema-tier walker

Two departures inside an otherwise verbatim method. An index that names no step
returns early instead of reading `$steps[$i]` off the end — the argument comes
from the browser through `callSchemaComponentMethod()`, and every other
browser-reachable path in this package validates its input before acting. And
the next step's blank fill is
`$nextStep?->getChildSchema()?->fillStateWithNull()` rather than Filament's
`$nextStep?->fillStateWithNull()`: the component-tier walker here fills only the
component's own path and does not recurse into its children, while the
schema-tier one walks every descendant, which is what Filament's does.

### The key keeps Filament's state-path segment, which Section drops

`Wizard` and [`Step`](step.md) both build their key as Filament does —
`{statePath}::wizard` / `slug(label)::{statePath}::wizard-step` — so a wizard in
a `statePath('data')` form is keyed `data::wizard`. `Section`'s port
[drops that segment](section.md#the-heading-derived-key-omits-the-state-path-segment)
because the state layer did not exist when it landed. The new components take the
Filament-exact form; `Section`'s row stays open as the one place still to catch
up.

### The footer actions are inert triggers, clicked through their wrapper

`getNextAction()`/`getPreviousAction()` are Filament's actions with
`liveComponentClickHandlerEnabled(false)` — they render a `button` with no
mount wiring of its own — and the click lives on the wrapping `<div>` as
`click->sylament--schemas--wizard#requestNextStep` /
`#goToPreviousStep`, the shape forms' CheckboxList bulk toggle already uses. The
wrapper is skipped when the action is `disabled()`, so a disabled Next is inert
rather than merely styled. Filament additionally sets
`livewireTarget('callSchemaComponentMethod')` on the next action, which scopes
Livewire's loading state to that one call; UX Live's loading states are
attribute-driven and un-targeted, so the wrapper carries
`data-loading="addClass(sy-disabled)"` — the twin of Filament's
`wire:loading.class` on the same element — and `livewireTarget()` stays
unported, as it already is in [actions](../actions/action.md).

The two `modify…ActionUsing` properties are typed `Action|Closure|null` rather
than `?Closure`, even though their setters take only a closure: `evaluate()`
infers its return type by subtracting `Closure` from the property's union, so a
bare `?Closure` would type the result `Closure` and `?? $action` would be dead
code to PHPStan. Widening the property to the closure's own result type is the
[house convention](../../internals/php.md#evaluates-generic) for exactly this, and the setter signature
still keeps an `Action` from ever being assigned.

### The footer slots take an Action, a Markup or a string, and the three-way split is resolved in PHP

Filament types `cancelAction()`/`submitAction()` as `string | Htmlable | null`
and passes an `Action` through the `Htmlable` arm — its own docs hand the page's
form actions straight in. The `Htmlable` → `Markup` translation cannot carry
that, since our `Action` is a `ViewComponent` and not a `Markup`, so the
signature names all three: `string|Markup|Action|null`. Neither setter is
closure-evaluated (Filament-exact). Because Twig has no `instanceof` test, the
split is done in `getExtraTemplateData()`: an `Action` becomes
`cancelActionComponent`/`submitActionComponent` for `renderComponent()`, and a
`Markup` (as-is) or a plain string (escaped into a `Markup`) becomes
`cancelActionHtml`/`submitActionHtml` for a raw print. An `Action` handed to a
slot is **rendered, not registered** on the Wizard — its click resolves wherever
it was defined.

### The query-string arm reads through an injected RequestStack and compares the absolute step key

Filament calls Laravel's `request()` helper; there is no ambient request here, so
a nullable `RequestStack` arrives through a `#[Required] setRequestStack()`
setter (the `NavigationItem::setEvaluationServices()` shape) and a wizard built
with `new` outside the container simply skips the read. The comparison is
`$step->getKey()` — the **absolute** key, which is what Filament's own wizard JS
writes into the URL, so unlike
`Tabs`' own arm no correction was needed. The value is read off `query->all()`
rather than `query->get()`, exactly as [`Tabs`](tabs.md#the-query-string-arm-compares-the-key-the-url-actually-carries)
does: `InputBag::get()` throws on an array-shaped `?step[]=x`, and a hand-edited
URL must not take the whole render down. The read is a first-paint
optimisation: the controller re-reads the URL on connect anyway.

### getStartStepKey() is Sylament-only

Filament never needs the key of the initially active step, because Alpine
computes the active state client-side from the step list. The server renders it
here (see the render divergence below), so `getStartStepKey()` exists to answer
"which step is active right now" for the `Step` partial's `sy-active` class. It
returns `null` for a wizard with no steps.

### The server renders the start step; Alpine's x-show pairs become state classes and CSS

Filament cloaks the whole wizard (`x-cloak` on the header, the footer and every
panel) and paints nothing until Alpine evaluates `x-show`/`x-bind:class` against
its `step` variable. Here the server renders the real state — `sy-active` on the
start step's `li` and panel, `sy-completed` on earlier ones,
`aria-current="step"`, `disabled` on the header buttons that are not accessible,
the status text, and `sy-hidden` on the footer slots that should not show — and
the controller toggles those same classes afterwards. Three consequences:

- the completed tick and the number/icon are **both** in every `li`, shown and
  hidden by `wizard.css` off the `li`'s `sy-completed` class, where Filament
  pairs an `x-show` on each; nothing is added or removed from the DOM;
- there is no `x-ref="stepsData"` hidden input carrying a JSON step list — every
  panel and header row already carries `data-step-key`, which is what the
  controller reads;
- `wire:ignore.self` has no twin and needs none: UX Live's
  `ExternalMutationTracker` records the controller's class changes and replays
  them onto the morphed-in markup, the mechanism support's Section controller
  already relies on.

A header button is `disabled` for the current step too, not only for later ones —
`isStepAccessible()` is `skippable || index < current` on both sides, and
Filament's `x-bind:disabled` reads it the same way.

### alpineSubmitHandler() and formWrapper() are skipped; their behaviour is a controller keydown

`alpineSubmitHandler(string $handler)` takes a raw Alpine expression, which has
no Stimulus twin, and `Step::formWrapper()` exists only to decide whether that
handler gets a `<form>` to hang `x-on:submit.prevent` on — a nested `<form>`
inside the host's form is invalid HTML regardless. Both are **skipped**. What
they buy the user — Enter inside a step advancing rather than submitting the
whole form — is kept as controller behaviour instead: a `keydown` Enter from a
text-like `<input>` inside the active step calls `requestNextStep()` unless the
step is the last, where the enclosing form's implicit submission proceeds. This
is a **divergence in reach**, not only in mechanism: Filament wires the handler
only in its page and modal wizards, so a Wizard inside a custom Filament form
submits the whole form on Enter, while every Wizard here advances.

### Extra Alpine attribute passthrough is not ported

Filament composes `HasExtraAlpineAttributes`, letting a caller hang arbitrary
`x-*` attributes on the wizard root. There is no Alpine here and the wiring the
wizard needs is the Stimulus controller, so the trait is **skipped** — the
standing decision across this package. `extraAttributes()` still passes anything
a caller wants onto the same element.

### afterValidated() is not shipped

Filament keeps `Step::afterValidated()` as a deprecated alias of
`afterValidation()`. A port has no migration history to preserve, so only the
current name ships. Recorded on [`step.md`](step.md) too, since that is where the
method lives.

_See also: the [rename table](../renames.md) — `toEmbeddedHtml()` → Twig
template, `Htmlable` → `Markup`, `Str::slug` → `AsciiSlugger`,
`livewireClickHandlerEnabled()` → `liveComponentClickHandlerEnabled()` and the
Alpine/Livewire → Stimulus/UX-Live translations this component leans on are
systematic, not per-component divergences._
