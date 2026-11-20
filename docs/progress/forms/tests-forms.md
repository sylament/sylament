---
description: The testing helpers that fill and assert a live host's form, on the tester a test drives.
ports:
  - vendor/filament/forms/src/Testing/TestsForms.php
owns:
  - packages/forms/src/Testing/TestsForms.php
---
# TestsForms (forms)

[`Sylament\Forms\Testing\TestsForms`](../../../packages/forms/src/Testing/TestsForms.php) is a trait on actions' [`Testable`](../actions/testable.md) tester, where Filament's is a Livewire `Testable` mixin.
`fillForm()` writes through live requests, as a user's typing does, and `assertFormSet()` reads the re-hydrated host through [`assertSchemaStateSet()`](../schemas/tests-schemas.md).
`assertHasFormErrors()` and `assertHasNoFormErrors()` read the error bag of the host that answered the last request.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`fillForm()`](../../../packages/forms/src/Testing/TestsForms.php) | [`Testing/TestsForms.php`](../../../vendor/filament/forms/src/Testing/TestsForms.php) | done | — | [diverges](#fillform-sends-one-live-request-per-key) |
| `fillForm()`'s uploaded-file values | [`Testing/TestsForms.php`](../../../vendor/filament/forms/src/Testing/TestsForms.php) | todo | — | — |
| [`assertFormSet()`](../../../packages/forms/src/Testing/TestsForms.php) | [`Testing/TestsForms.php`](../../../vendor/filament/forms/src/Testing/TestsForms.php) | done | — | — |
| [`assertHasFormErrors()` / `assertHasNoFormErrors()`](../../../packages/forms/src/Testing/TestsForms.php): a key alone, a constraint class or a message | [`Testing/TestsForms.php`](../../../vendor/filament/forms/src/Testing/TestsForms.php) | done | — | [diverges](#a-constraint-class-stands-where-filament-names-a-rule) · [diverges](#a-bare-call-reads-the-errors-of-the-form-under-test) · [semantics](#the-errors-are-the-ones-the-last-response-showed) |
| `assertHasFormErrors()` / `assertHasNoFormErrors()`'s list and closure values | [`TestsValidation.php`](../../../vendor/livewire/livewire/src/Features/SupportValidation/TestsValidation.php) (Livewire) | todo | — | — |
| `assertFormExists()` / `assertFormComponentExists()` / `assertFormComponentDoesNotExist()` | [`Testing/TestsForms.php`](../../../vendor/filament/forms/src/Testing/TestsForms.php) | todo | — | — |
| `assertFormFieldExists()` / `assertFormFieldDoesNotExist()` / `assertFormFieldDisabled()` / `assertFormFieldEnabled()` / `assertFormFieldReadOnly()` / `assertFormFieldHidden()` / `assertFormFieldVisible()` | [`Testing/TestsForms.php`](../../../vendor/filament/forms/src/Testing/TestsForms.php) | todo | — | — |
| `assertFormFieldIsDisabled()` / `…IsEnabled()` / `…IsReadOnly()` / `…IsHidden()` / `…IsVisible()` | [`Testing/TestsForms.php`](../../../vendor/filament/forms/src/Testing/TestsForms.php) | skipped | — | [deprecated for `assertFormFieldDisabled()` and its siblings](../README.md#statuses) |

## Semantics

### `fillForm()` with no state empties the form

`fillForm()` undots the given keys under the schema's state path, as Laravel's `Arr::undot()` does, so a key given twice keeps its later value, and with no state it writes an empty array over the state path, as Filament's does.

### The errors are the ones the last response showed

Both assertions read the host that answered the last request ([`Testable`](../actions/testable.md#the-error-assertions-read-the-component-that-answered-the-last-request)), not the re-hydrated `component()`, as Livewire's read the error bag its last request left.
A re-hydration re-runs only the sticky pass, which rebuilds a state error but not a record's refusal ([why](../schemas/can-be-validated.md#record-errors-are-sticky-but-the-silent-pass-cannot-reproduce-them)), so an edited composite key refused under its field is an error to `assertHasFormErrors()` exactly as long as the page shows it.
The form itself is resolved on the re-hydrated host, as `fillForm()` resolves it, and a key is prefixed with the form's state path, so `['title']` reads `data.title`.
A redirect proves that no validation failed, so after one `assertHasNoFormErrors()` passes and `assertHasFormErrors()` fails as an assertion, `Component has no errors.`, rather than throwing the redirect's `LogicException`; `CreateRecord::create()` always redirects, and `->call('create')->assertHasNoFormErrors()` is the commonest chain.

## Divergences

### A constraint class stands where Filament names a rule

Filament delegates to Livewire's `assertHasErrors()`, which matches a value against the names of the rules that failed and then against the messages.
A Laravel rule name (`'required'`) means nothing on Symfony's validator, and mapping each of Filament's rule methods to a constraint would leave a custom constraint without a name, so a value matches the class of a constraint the field failed, read off the [host's error bag](../schemas/can-be-validated.md#the-bag-keeps-each-errors-constraint-class-beside-its-message), or one of its messages.
`['title']` asserts any error on the field, `['title' => RequiredValue::class]` the constraint that failed, the form to prefer because it holds whatever the locale or the message's wording, and `['title' => 'This value should not be blank.']` the exact message.
A field's `required()` fails forms' own `RequiredValue`, not Symfony's `NotBlank` ([why](can-be-validated.md#required-carries-laravels-blank-set-not-symfonys)), so that is the class a test names for it.
There is no `rule:parameters` form, so nothing after a colon is cut, and a message containing one matches whole.
`assertHasNoFormErrors()` reads a value the same way, where Livewire's `assertHasNoErrors()` compares it with the failed rules only, so a message there would pass whatever the field showed.
The failure messages are Livewire's, its `rule` wording included.

### A bare call reads the errors of the form under test

`assertHasNoFormErrors()` with no keys, and the check `assertHasFormErrors()` opens with, read every error the form under test shows, where Livewire's read every error the component holds, whichever form it sits on.
The host's bag is read one state path at a time, so the form's paths are the ones enumerated: each component's own, each path the form constrains, and the form's own path, where a record violation no component maps is filed.

### `fillForm()` sends one live request per key

Filament writes each key in process and runs that key's `afterStateUpdated` hooks before the next (`fillFormDataForTesting()`), so the hooks run in the order the keys are given and a filled value wins over what an earlier field's hook wrote.
Here every write is a live request, `set()` on the key's path, one per key in the given order, which keeps both: the host runs a field's hooks after the request that changed it.
One request carrying every key would not: the host runs every changed field's hooks after all the writes, in schema order, so an earlier field's hook overwrote a later filled value, and UX's multi-key form submission merged a list into the old one by index and dropped an empty list.
A non-empty associative array is written key by key where the host's state holds an array at that path, so the keys it leaves out keep their state; a container the state lacks, such as a repeater item under a new key, is one write of the whole value, because UX's hydrator drops a write below a missing level ([why](../schemas/has-schemas.md#every-missing-or-null-level-a-fields-state-path-crosses-starts-as-an-empty-array)), and inside it the hooks run in schema order after that one request, where Filament's run per leaf in the given order; a list or an empty array is one write that replaces what it lands on; and a numeric key the filled state leaves out is removed afterwards by writing back the array that held it, as Filament's `unsetMissingNumericArrayKeys()` removes it.
The form's state path only prefixes the keys, so a form whose state sits at a list index (a mounted action's `mountedActionsData.0`) is written key by key too, and the keys the state leaves out keep their values, as Filament's per-key writes keep them.
A form under test with no state path, such as the `content` schema a page without a form tests, writes each key at the host's top level, and a key that names no `LiveProp` of the host reaches UX as an update it never reads, so nothing is written and nothing throws, where Filament's `data_set()` sets the key on the component itself.
