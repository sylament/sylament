# CanBeMarkedAsRequired (schemas)

[`Sylament\Schemas\Components\Concerns\CanBeMarkedAsRequired`](../../../packages/schemas/src/Components/Concerns/CanBeMarkedAsRequired.php)
— the required-marker toggle: `markAsRequired()` sets it, `isMarkedAsRequired()`
reads it and falls back to `$this->isRequired()` when nothing explicit was set.
That fallback is a contract on the **using class**, which must declare
`isRequired()`: the forms
[`Field`](../../../packages/forms/src/Components/Field.php) gets it from
[`CanBeValidated`](../forms/can-be-validated.md) and reports the real validation
state, while schemas' [`Fieldset`](fieldset.md) declares a constant `false`, so
its legend takes an asterisk only after an explicit `markAsRequired()`. Two
consumers in two packages is what earns this its own file rather than a fold
into either — see [one row per file](../README.md#one-row-per-file-one-file-per-thing).

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`forms/src/Components/Concerns/CanBeMarkedAsRequired.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeMarkedAsRequired.php)
  — the marker property, its setter, and the `isRequired()` fallback getter

## Surface

Every point of this concern. _Detail_ carries a **diverges** link to the
permanent note in [Divergences](#divergences), a **semantics** link to
[Semantics](#semantics), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`markAsRequired()`](../../../packages/schemas/src/Components/Concerns/CanBeMarkedAsRequired.php) | [`markAsRequired()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeMarkedAsRequired.php) | done | — | — |
| [`isMarkedAsRequired()`](../../../packages/schemas/src/Components/Concerns/CanBeMarkedAsRequired.php) | [`isMarkedAsRequired()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeMarkedAsRequired.php) | done | — | [semantics](#the-native-required-attribute-is-load-bearing-inside-a-modal-form) |

## Semantics

Faithful-in-spirit behaviour a reader would plausibly misread from the source.
Permanent — these outlive the detail sections above.

### The native `required` attribute is load-bearing inside a modal form

[`isMarkedAsRequired()`](../../../packages/schemas/src/Components/Concerns/CanBeMarkedAsRequired.php)
is Filament's exactly — `$this->evaluate($this->isMarkedAsRequired) ??
$this->isRequired()` — so with no explicit `markAsRequired()` call it reads the
real validation state [`CanBeValidated`](../forms/can-be-validated.md) supplies,
and `markAsRequired(false)` still suppresses the marker on a genuinely required
field. What is easy to misread is that this getter drives **two** things, only
one of them cosmetic: the wrapper's asterisk, and the native `required`
attribute `TextInput` / `Textarea` emit
([`TextInput/index.html.twig`](../../../packages/forms/templates/partials/TextInput/index.html.twig)).

That attribute does real work now. A mounted action's modal window renders as a
real `<form>`
([support/modal.md](../support/modal.md#the-form-wrapper-carries-live_action-not-wiresubmit)),
and the browser's `submit` event only fires after native constraint validation
passes — so an empty required field blocks the request client-side and the
server-side pass never runs. Confirmed in a browser: `valueMissing: true`, **zero** network requests, modal stays open. This is
parity, not a divergence — Filament emits native `required` too
(`vendor/filament/forms/src/Components/TextInput.php:365`) and `wire:submit` has
the same event ordering. The consequence to keep in mind: `markAsRequired()` on
a field that declares no constraint still blocks submission in the browser while
validating nothing on the server, and conversely a server-side violation on a
required field is only reachable through a path the native check does not cover
(a whitespace-only value, a programmatic `set()`, a non-input field).

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold.

### The trait lives in schemas, not forms

Filament hosts this trait in `forms/src/Components/Concerns/` and imports it
from schemas' `Fieldset`
(`vendor/filament/schemas/src/Components/Fieldset.php`) — an upward import from
schemas into forms. Sylament cannot reproduce that direction: `forms` depends on
`schemas`, so the trait has to sit at or below the lower package for both
consumers to reach it. It moves down to
`Sylament\Schemas\Components\Concerns\`, and forms' `Field` imports it from
there.

This is not a style question. The CI split publishes each package standalone, so
a package that leans on a symbol only a sibling declares breaks the moment it is
installed on its own — the same failure mode that made `actions` declare
`symfony/ux-live-component` for itself. Keeping the trait in forms would leave
schemas' `Fieldset` reaching into a package schemas does not depend on at all.

The namespace change is recorded in the global
[rename table](../renames.md); the method names, signatures and body are
unchanged, so nothing a migrating user greps for moved.
