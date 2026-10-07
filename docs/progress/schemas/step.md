---
description: 'One panel of a `Wizard`: a required label, an optional description and icon pair for the header stepper, before/after validation callbacks the wizard… (draft)'
ports:
  - vendor/filament/schemas/src/Components/Wizard/Step.php
owns:
  - packages/schemas/src/Components/Wizard/
  - packages/schemas/templates/partials/Step/
---
# Wizard\Step (schemas)

`Sylament\Schemas\Components\Wizard\Step` — one panel of a [`Wizard`](wizard.md): a required label, an optional description and icon pair for the header stepper, before/after validation callbacks the wizard runs on the way forward, and a child schema holding the step's own components.
It extends the schemas base [`Component`](component.md), implements [`CanConcealComponents`](component.md) and ports Filament's [`Wizard\Step`](../../../vendor/filament/schemas/src/Components/Wizard/Step.php).

A step renders as a `role="group"` panel named by its header button — never `role="tabpanel"`, since the header is an `<ol>` stepper of plain buttons with `aria-current="step"` rather than a `tablist`, so a role implying an owning tab would be incoherent.
Filament reasons the same way in its own comment; when the wizard hides its header there is no button to name the panel, so it falls back to its own label.

## Surface

_Detail_ carries a **diverges** link to the permanent note in [Divergences](#divergences), a link to the wizard's own note, or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make(string $label)`](../../../packages/schemas/src/Components/Wizard/Step.php) | [`Step::make()`](../../../vendor/filament/schemas/src/Components/Wizard/Step.php) | done | — | — |
| [Key derivation (`slug(label)::{statePath}::wizard-step`, non-inheritable)](../../../packages/schemas/src/Components/Wizard/Step.php) | [`Step::setUp()`](../../../vendor/filament/schemas/src/Components/Wizard/Step.php) | done | — | [diverges](wizard.md#the-key-keeps-filaments-state-path-segment-which-section-drops) |
| [`beforeValidation()` / `callBeforeValidation()`](../../../packages/schemas/src/Components/Wizard/Step.php) | [`Step::beforeValidation()`](../../../vendor/filament/schemas/src/Components/Wizard/Step.php) | done | — | — |
| [`afterValidation()` / `callAfterValidation()`](../../../packages/schemas/src/Components/Wizard/Step.php) | [`Step::afterValidation()`](../../../vendor/filament/schemas/src/Components/Wizard/Step.php) | done | — | — |
| `afterValidated()` (deprecated alias) | [`Step::afterValidated()`](../../../vendor/filament/schemas/src/Components/Wizard/Step.php) | skipped | — | [diverges](wizard.md#aftervalidated-is-not-shipped) |
| [`description()` / `getDescription()`](../../../packages/schemas/src/Components/Wizard/Step.php) | [`Step::description()`](../../../vendor/filament/schemas/src/Components/Wizard/Step.php) | done | — | — |
| [`icon()` / `getIcon()`](../../../packages/schemas/src/Components/Wizard/Step.php) | [`Step::icon()`](../../../vendor/filament/schemas/src/Components/Wizard/Step.php) | done | — | — |
| [`completedIcon()` / `getCompletedIcon()`](../../../packages/schemas/src/Components/Wizard/Step.php) | [`Step::completedIcon()`](../../../vendor/filament/schemas/src/Components/Wizard/Step.php) | done | — | [diverges](wizard.md#the-server-renders-the-start-step-alpines-x-show-pairs-become-state-classes-and-css) |
| [`getAllColumns()` container fallback](../../../packages/schemas/src/Components/Wizard/Step.php) | [`Step::getAllColumns()`](../../../vendor/filament/schemas/src/Components/Wizard/Step.php) | done | — | — |
| [`canConcealComponents(): true`](../../../packages/schemas/src/Components/Wizard/Step.php) | [`Step::canConcealComponents()`](../../../vendor/filament/schemas/src/Components/Wizard/Step.php) | done | — | — |
| [`HasLabel` (incl. `hiddenLabel()`)](../../../packages/schemas/src/Components/Concerns/HasLabel.php) | [`Concerns/HasLabel.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasLabel.php) | done | — | [diverges](#a-hidden-step-label-stays-in-the-dom-as-screen-reader-only-text) |
| [`getWizard(): Wizard`](../../../packages/schemas/src/Components/Wizard/Step.php) | [`Step::toEmbeddedHtml()` (inline `$wizard`)](../../../vendor/filament/schemas/src/Components/Wizard/Step.php) | done | — | [diverges](#the-owning-wizard-is-a-method-that-throws-rather-than-a-docblock-assertion) |
| [Rendering (`Step/index.html.twig`)](../../../packages/schemas/templates/partials/Step/index.html.twig) | [`Step::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Wizard/Step.php) | done | — | [diverges](#the-panel-is-always-a-div-and-its-tabindex-is-fixed-at-0) |
| `formWrapper()` / `hasFormWrapper()` | [`Step::formWrapper()`](../../../vendor/filament/schemas/src/Components/Wizard/Step.php) | skipped | — | [diverges](wizard.md#alpinesubmithandler-and-formwrapper-are-skipped-their-behaviour-is-a-controller-keydown) |
| The `x-ref="step-{key}"` reference | [`Step::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Wizard/Step.php) | skipped | — | [diverges](wizard.md#the-server-renders-the-start-step-alpines-x-show-pairs-become-state-classes-and-css) |

Coverage: the key derivation, both icons, the description, the validation callbacks (including a `Halt` escaping one), the columns fallback, the concealment contract and the template data by [`StepTest`](../../../packages/schemas/tests/Unit/Components/Wizard/StepTest.php); the rendered panel — `role="group"`, the `aria-labelledby`/`aria-label` split, `tabindex`, `data-step-key`, the target and the `expand` action, plus the header half (label, hidden label, description, icons, numbering) — by [`WizardRenderTest`](../../../packages/schemas/tests/Integration/Components/WizardRenderTest.php).

## Divergences

### The owning wizard is a method that throws rather than a docblock assertion

Filament's `toEmbeddedHtml()` opens with `/** @var Wizard $wizard */ $wizard = $this->getContainer()->getParentComponent();` — an unchecked assertion that would be a fatal one line later for a `Step` used anywhere else.
`getWizard()` makes the same lookup a real method and throws a `LogicException` naming both classes when the parent is not a `Wizard`, the shape [`BelongsToSchemaComponent::getSchemaComponentOrFail()`](../../../packages/actions/src/Concerns/BelongsToSchemaComponent.php) set for this package (no shipped source carries an inline `@phpstan-ignore`).

### The panel is always a div, and its tabindex is fixed at 0

Filament emits `<form>` instead of `<div>` when the step has a form wrapper and an Alpine submit handler is set — both [skipped](wizard.md#alpinesubmithandler-and-formwrapper-are-skipped-their-behaviour-is-a-controller-keydown) — so the tag is always a `div` and the trailing `<input type="submit" hidden />` that pairs with it is gone too.
Filament also binds `x-bind:tabindex="$el.querySelector('[autofocus]') ? '-1' : '0'"`; the server renders `tabindex="0"` and the controller applies the `-1` arm when it re-asserts step state, so the initial markup is the common case and the autofocus case corrects on connect.

### A hidden step label stays in the DOM as screen-reader-only text

`hiddenLabel()` renders the label with `sy-sc-wizard-header-step-label-hidden` (`sr-only`) rather than dropping it, so the header button — and the panel it names through `aria-labelledby` — keeps an accessible name.
Filament does the same thing with its own `fi-sr-only` class and says so in a comment; the class name and the stylesheet rule are ours, matching the [Section precedent](styles.md#section-css-adds-the-sr-only-hidden-label-rule).

_See also: the [rename table](../renames.md) — `toEmbeddedHtml()` → Twig template, `Htmlable` → `Markup` and `Str::slug` → `AsciiSlugger` are systematic translations recorded there._
