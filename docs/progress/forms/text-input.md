---
description: The single-line text field, with its type sugar, value bounds, reveal and copy affix actions, and trim and strip state hooks.
ports:
  - vendor/filament/forms/src/Components/TextInput.php
  - vendor/filament/forms/src/Components/TextInput/Actions/
  - vendor/filament/forms/src/Components/Concerns/CanBeAutocapitalized.php
  - vendor/filament/forms/src/Components/Concerns/HasInputMode.php
owns:
  - packages/forms/src/Components/Concerns/CanBeAutocapitalized.php
  - packages/forms/src/Components/TextInput.php
  - packages/forms/src/Components/TextInput/
  - packages/forms/templates/partials/TextInput/
---
# TextInput (forms)

`Sylament\Forms\Components\TextInput` extends [`Field`](field.md), whose shared surface (label, hint, helper text, placeholder, required, slots, rendering chrome) is tracked there.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`type()` / `getType()`](../../../packages/forms/src/Components/TextInput.php) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | — |
| [`email()` / `isEmail()`](../../../packages/forms/src/Components/TextInput.php) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | [diverges](#the-type-sugar-constraint-arms-live-in-an-override-not-in-the-setters) |
| [`numeric()` / `isNumeric()`](../../../packages/forms/src/Components/TextInput.php) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | [diverges](#the-type-sugar-constraint-arms-live-in-an-override-not-in-the-setters) · [diverges](#numeric-seeds-the-step-through-isnumeric-not-the-captured-condition) |
| [`buildValidationConstraints()` arms](../../../packages/forms/src/Components/TextInput.php) | [`TextInput::email()` / `integer()` / `numeric()` / `url()`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | [diverges](#the-type-sugar-constraint-arms-live-in-an-override-not-in-the-setters) |
| [`password()` / `isPassword()`](../../../packages/forms/src/Components/TextInput.php) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | — |
| [`getInputMode()`](../../../packages/forms/src/Components/TextInput.php) | [`Concerns/HasInputMode.php`](../../../vendor/filament/forms/src/Components/Concerns/HasInputMode.php) | done | — | [diverges](#input-mode-is-a-bespoke-getter-not-the-hasinputmode-concern) |
| `HasInputMode` (concern) | [`Concerns/HasInputMode.php`](../../../vendor/filament/forms/src/Components/Concerns/HasInputMode.php) | skipped | — | [diverges](#input-mode-is-a-bespoke-getter-not-the-hasinputmode-concern) |
| [`integer()` / `isInteger()`](../../../packages/forms/src/Components/TextInput.php) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | [semantics](#integer-delegates-to-numeric-so-both-arms-ship-and-the-step-order-matters) · [diverges](#integer-validates-with-a-regex-not-a-type-constraint) · [diverges](#numeric-seeds-the-step-through-isnumeric-not-the-captured-condition) |
| [`tel()` / `telRegex()` / `getTelRegex()` / `isTel()`](../../../packages/forms/src/Components/TextInput.php) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | [diverges](#tel-writes-the-shared-regex-slot) |
| [`url()` / `isUrl()`](../../../packages/forms/src/Components/TextInput.php) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | [diverges](#the-type-sugar-constraint-arms-live-in-an-override-not-in-the-setters) |
| [`currentPassword()`](../../../packages/forms/src/Components/TextInput.php) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | [semantics](#currentpassword-names-security-core-while-the-validator-is-securitybundles) · [diverges](#currentpassword-drops-filaments-guard-argument) |
| [`maxValue()` / `minValue()` / `getMaxValue()` / `getMinValue()`](../../../packages/forms/src/Components/TextInput.php) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | [diverges](#the-value-bounds-are-comparison-constraints-not-laravels-min-and-max) |
| `mask()` / `getMask()` | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | blocked | a masking-engine decision | [detail](#input-masking-waits-on-an-engine-decision-and-rawjs-is-not-it) |
| [`revealable()` / `isPasswordRevealable()`](../../../packages/forms/src/Components/TextInput.php) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | [semantics](#both-reveal-buttons-render-and-css-hides-the-inapplicable-one) · [diverges](#a-revealable-input-is-rendered-as-a-password-server-side) |
| [`copyable()` / `isCopyable()`](../../../packages/forms/src/Components/TextInput.php) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | [semantics](#the-copy-message-closure-sees-the-render-time-state) · [diverges](#copy-confirms-through-a-status-region-not-a-tooltip) |
| [`TextInput\Actions\ShowPasswordAction`](../../../packages/forms/src/Components/TextInput/Actions/ShowPasswordAction.php) | [`TextInput/Actions/ShowPasswordAction.php`](../../../vendor/filament/forms/src/Components/TextInput/Actions/ShowPasswordAction.php) | done | — | [semantics](#the-affix-actions-resolve-their-own-icons) · [diverges](#a-revealable-input-is-rendered-as-a-password-server-side) |
| [`TextInput\Actions\HidePasswordAction`](../../../packages/forms/src/Components/TextInput/Actions/HidePasswordAction.php) | [`TextInput/Actions/HidePasswordAction.php`](../../../vendor/filament/forms/src/Components/TextInput/Actions/HidePasswordAction.php) | done | — | [semantics](#the-affix-actions-resolve-their-own-icons) · [diverges](#a-revealable-input-is-rendered-as-a-password-server-side) |
| [`TextInput\Actions\CopyAction`](../../../packages/forms/src/Components/TextInput/Actions/CopyAction.php) | [`TextInput/Actions/CopyAction.php`](../../../vendor/filament/forms/src/Components/TextInput/Actions/CopyAction.php) | done | — | [semantics](#the-affix-actions-resolve-their-own-icons) · [diverges](#copy-confirms-through-a-status-region-not-a-tooltip) |
| [`CopyAction::copyMessage()` / `getCopyMessage()`](../../../packages/forms/src/Components/TextInput/Actions/CopyAction.php) | [`TextInput/Actions/CopyAction.php`](../../../vendor/filament/forms/src/Components/TextInput/Actions/CopyAction.php) | done | — | [semantics](#the-copy-message-closure-sees-the-render-time-state) |
| [`CopyAction::copyMessageDuration()` / `getCopyMessageDuration()`](../../../packages/forms/src/Components/TextInput/Actions/CopyAction.php) | [`TextInput/Actions/CopyAction.php`](../../../vendor/filament/forms/src/Components/TextInput/Actions/CopyAction.php) | done | — | [semantics](#the-copy-message-closure-sees-the-render-time-state) |
| [`HasStep`](../../../packages/forms/src/Components/Concerns/HasStep.php) | [`Concerns/HasStep.php`](../../../vendor/filament/forms/src/Components/Concerns/HasStep.php) | done | — | [detail](has-step.md) |
| [`HasAffixes`](../../../packages/forms/src/Components/Concerns/HasAffixes.php) (concern) | [`Concerns/HasAffixes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasAffixes.php) | done | — | [detail](has-affixes.md) |
| [`CanBeLengthConstrained`](../../../packages/forms/src/Components/Concerns/CanBeLengthConstrained.php) | [`Concerns/CanBeLengthConstrained.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeLengthConstrained.php) | done | — | [detail](can-be-length-constrained.md) |
| [`HasDatalistOptions`](../../../packages/forms/src/Components/Concerns/HasDatalistOptions.php) | [`Concerns/HasDatalistOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasDatalistOptions.php) | done | — | [detail](has-datalist-options.md) |
| [`CanBeAutocapitalized`](../../../packages/forms/src/Components/Concerns/CanBeAutocapitalized.php) | [`Concerns/CanBeAutocapitalized.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeAutocapitalized.php) | done | — | — |
| `disableAutocapitalize()` | [`Concerns/CanBeAutocapitalized.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeAutocapitalized.php) | skipped | — | [diverges](can-be-autocompleted.md#the-deprecated-disable-aliases-are-not-ported) |
| [`CanBeAutocompleted`](../../../packages/forms/src/Components/Concerns/CanBeAutocompleted.php) | [`Concerns/CanBeAutocompleted.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeAutocompleted.php) | done | — | [detail](can-be-autocompleted.md) |
| [`CanBeReadOnly`](../../../packages/forms/src/Components/Concerns/CanBeReadOnly.php) | [`Concerns/CanBeReadOnly.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeReadOnly.php) | done | — | [detail](can-be-read-only.md) |
| [`HasExtraInputAttributes`](../../../packages/forms/src/Components/Concerns/HasExtraInputAttributes.php) | [`Concerns/HasExtraInputAttributes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraInputAttributes.php) | done | — | [detail](has-extra-input-attributes.md) |
| `HasExtraAlpineAttributes` (concern) | [`support/Concerns/HasExtraAlpineAttributes.php`](../../../vendor/filament/support/src/Concerns/HasExtraAlpineAttributes.php) | skipped | — | [diverges](../support/component-attribute-bag.md#extra-alpine-attributes-are-not-ported) |
| [Trim state (`CanTrimState`)](../../../packages/schemas/src/Components/Concerns/CanTrimState.php) | [`schemas/Concerns/CanTrimState.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanTrimState.php) | done | — | [semantics](textarea.md#trim-reaches-the-saved-value-strip-reaches-it-through-a-cast-neither-reaches-the-raw-bag) |
| [Strip characters (`CanStripCharactersFromState`)](../../../packages/schemas/src/Components/Concerns/CanStripCharactersFromState.php) | [`schemas/Concerns/CanStripCharactersFromState.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanStripCharactersFromState.php) | done | — | [semantics](textarea.md#trim-reaches-the-saved-value-strip-reaches-it-through-a-cast-neither-reaches-the-raw-bag) |
| [State casts + `mutate*` / `mutates*` hooks](../../../packages/forms/src/Components/TextInput.php) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | [semantics](textarea.md#trim-reaches-the-saved-value-strip-reaches-it-through-a-cast-neither-reaches-the-raw-bag) |
| `NumberStateCast` arm of `getDefaultStateCasts()` | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | blocked | schemas' `NumberStateCast` | [detail](#the-numberstatecast-arm-waits-on-schemas-cast) · [diverges](#integer-validates-with-a-regex-not-a-type-constraint) · [diverges](#the-value-bounds-are-comparison-constraints-not-laravels-min-and-max) |
| [Native constraint attributes drop when concealed](../../../packages/forms/templates/partials/TextInput/index.html.twig) — `required`, `min`, `max`, `minlength`, `maxlength` | [`toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | [can-be-concealed.md](../schemas/can-be-concealed.md) |

## Details

### Input masking waits on an engine decision, and `RawJs` is not it

`mask()` and `getMask()` ship nothing.
`RawJs` is a subclass of Laravel's `Illuminate\Support\Js` whose whole job is to mark a string as JavaScript rather than as text; porting it would import a Laravel idiom for no reason beyond Filament's mask API accepting one, so it is not the prerequisite.

The real blocker is the engine.
Filament's `mask()` emits `x-mask` / `x-mask:dynamic`, handled by `@alpinejs/mask`, which is nowhere on disk here, and the toolkit ships no masking of its own.
Three routes exist and all three need a decision before any code: grant a JavaScript dependency (a Martin-only call under the dependency policy), write a first-party masker (a *Replace* under the ecosystem principle, which needs a named gap), or keep shipping nothing.
Under either of the first two the dynamic arm still needs a Sylament-shaped replacement for `RawJs`, most likely a pattern-resolving callback rather than a marker type.

The other half of Filament's masking story already ships: [`stripCharacters()`](textarea.md#trim-reaches-the-saved-value-strip-reaches-it-through-a-cast-neither-reaches-the-raw-bag) is what removes a mask's decorative characters before the value is validated and saved, so a consumer masking with their own JavaScript already has the save path.

### The `NumberStateCast` arm waits on schemas' cast

Filament's `getDefaultStateCasts()` appends a `NumberStateCast(isNullable: true, isInteger: isInteger())` on a numeric field, casting a filled state to an `int` under `integer()` and to a `float` otherwise.
The port appends only the `StripCharactersStateCast`: schemas ships no `NumberStateCast`, and adding one is a type-derived-cast question of the `DateTimeStateCast` kind rather than a text-input one.
Two divergences here wait on it: the [integer regex](#integer-validates-with-a-regex-not-a-type-constraint) and the [comparison bounds](#the-value-bounds-are-comparison-constraints-not-laravels-min-and-max) both say what changes once a real number reaches validation.

## Semantics

### `integer()` delegates to `numeric()`, so both arms ship and the step order matters

`integer()` is not a peer of `numeric()` — it calls it, exactly as Filament's does.
Two consequences a reader re-deriving them from the code would plausibly get backwards.

**The call order is load-bearing.**
`numeric()` seeds `step('any')`, so `integer()` has to call it *first* and overwrite that seed with `step(1)` afterwards.
Written the other way round the `'any'` wins and an integer input silently accepts decimals — with a green suite, because nothing else about the field changes.
`TextInputTest`'s _makes an integer input numeric with a step of one_ is what holds the order.

**An integer field carries both type arms.**
The delegation flips `isNumeric` too, so `buildValidationConstraints()` appends `Type('numeric')` *and* the integer [`Regex`](#integer-validates-with-a-regex-not-a-type-constraint), and `getType()` reports `number` off the same flag.
That is Filament: its `integer()` likewise leaves `numeric()`'s `numeric` rule in place and adds `integer` beside it.
The pair is not two independent checks, though — the regex accepts a strict subset of `is_numeric()`, so the `Type` arm rejects nothing the regex would accept; what it adds is a second violation on the same value, and [`FieldWrapper`](../../../packages/forms/templates/components/FieldWrapper/index.html.twig) [lists every message](field.md#error-display-shows-every-message-for-the-path), so `abc` in an integer field renders two.
Dropping the `Type` arm would mean not calling `numeric()`, which is where the step, the `number` type and the input mode all come from.
`TextInputTest`'s _validates an integer with the numeric arm plus a regex that accepts a numeric string_ holds both arms and their order.

### Both reveal buttons render, and CSS hides the inapplicable one

`revealable()` registers `ShowPasswordAction` **and** `HidePasswordAction` as suffix actions, and both render on every revealable field — the server cannot know which state the input is in, and nothing on the client templates a second button in later.
`text-input.css` hides whichever does not apply off a `sy-revealed` class the `sylament--forms--text-input` controller toggles on the wrapper root: without it the hide button is `display: none`, with it the show button is.
The controller's `reveal()` / `conceal()` set `input.type` in the same call, so the attribute and the class always move together — which is what lets the live component's mutation tracker replay both across a morph.

Neither button carries a live mount attribute: each action is `liveComponentClickHandlerEnabled(false)`, so `Action`'s IconButton template emits only the `data-action` its `extraAttributes()` supplies, and a click costs no round trip.
The same holds for the copy button.

The controller binds only when `revealable()` or `copyable()` is on, and so does the input's `input` target — a plain text input renders the `sy-fo-text-input` class and nothing else, the way [`CheckboxList`](checkbox-list.md), [`Select`](select.md) and [`Repeater`](repeater.md) each gate theirs.
Filament likewise renders its wrapper's `x-data` only when the field needs one.

### The copy message closure sees the render-time state

`CopyAction::getCopyMessage()` and `getCopyMessageDuration()` are evaluated when the field renders, with `state` injected — Filament-exact, and the injection works because an affix action is prepared through [`HasAffixes::cacheSuffixActions()`](has-affixes.md) → `prepareAction()`, which is what gives the action its schema component.
So `copyMessage(fn ($state) => "Copied {$state}")` describes the value the server rendered, not whatever has been typed since; the value that reaches the clipboard is the input's live one, in Filament as here.

Inside a [`Repeater`](repeater.md) item it is still the **field's own** value, not the repeater's array.
`Action::getSchemaComponentState()` walks up to the nearest ancestor with a state path only while it has a schema *container*, and `prepareAction()` sets `schemaComponent()` alone — an affix action never gets a container, so `getSchemaContainer()` is null, the walk does not run, and the field's `getState()` is what comes back.
The walk is there for the modal and group actions that do carry one.
`TextInputTest`'s _sees the field own state in a copy message closure inside a repeater item_ pins it.

On the client the message survives one more step: Stimulus JSON-parses every action param, so a message of `'2024'` or `'null'` reaches `copy()` as a number or as `null`.
The controller coerces whatever arrives back to a string, and only an absent param falls back to 'Copied'.

### The affix actions resolve their own icons

Each of the three actions carries a `#[Required] setIconManager(IconManager)` and resolves its `FormsIconAlias::COMPONENTS_TEXT_INPUT_ACTIONS_*` override itself, falling back to the shipped heroicon.
That is the shape [`Repeater`](repeater.md) uses on the **field**, moved onto the action, and it works only because [the bundle's](forms-bundle.md) component glob `{*,Repeater/*,TextInput/Actions/*}` makes the classes container-built.
An action built outside DI falls back to a bare `IconManager` and resolves no user override — which is why `TextInputTest`'s alias case reaches the container before it builds anything.

### `currentPassword()` names security-core while the validator is SecurityBundle's

`currentPassword()` appends Symfony's `UserPassword` constraint behind a `class_exists()` guard whose `LogicException` names **`symfony/security-core`**, the package the constraint class ships in.
The validator behind it is SecurityBundle's `security.validator.user_password`, resolved through the same `ContainerConstraintValidatorFactory` route forms' own validators take.
So an application that installs security-core but configures no firewall clears the guard and fails later, at validate time, with the factory's `ValidatorException`.
Both packages are `suggest`s on `sylament/forms`; neither is a `require`, since a form that never calls `currentPassword()` needs neither.

## Divergences

### The type-sugar constraint arms live in an override, not in the setters

Filament's `email()` / `integer()` / `numeric()` / `url()` push their validation rule into the shared rule list from inside the setter (`->rule('email', $condition)`), and `tel()` pushes its pattern through a `regex()` setter that reads `getTelRegex()` back off the component.
So in Filament the rule is registered eagerly and the condition is what stays lazy.
The port's setters register no constraint at all — they flip `isEmail` / `isInteger` / `isNumeric` / `isUrl`, and `numeric()` and `integer()` additionally seed the [step](#numeric-seeds-the-step-through-isnumeric-not-the-captured-condition) — while [`buildValidationConstraints()`](../../../packages/forms/src/Components/TextInput.php) overrides the parent to append `Email()` / `Type('numeric')` / [integer `Regex`](#integer-validates-with-a-regex-not-a-type-constraint) / `Url()` behind those flags.
Same observable API and same laziness.
`tel()` is the one arm not in the override: it [writes the shared `regex()` slot](#tel-writes-the-shared-regex-slot), as Filament's does, rather than appending a second `Regex` beside it.

The reason is **composition order**.
These arms must land last — the order is `RequiredValue` → length → the `in` and `regex()` slots → escape hatch → type arms, pinned by `TextInputTest` — and a `constraint()` call from `setUp()` would file them among the escape-hatch pairs instead, ahead of anything the user registers later.
The override is what keeps them at the tail, and it keeps the type arms in one block, ordered the way `getType()` checks the flags they share — the integer arm slotting in beside the `numeric()` it [delegates to](#integer-delegates-to-numeric-so-both-arms-ship-and-the-step-order-matters), since `getType()` has no branch of its own for it.
Messages are keyed `Email::class` / `Type::class` / `Regex::class` / `Url::class` and applied by the [post-pass](can-be-validated.md#validation-messages-apply-after-the-constraints-are-built), so no arm passes a `message:` argument.

The cost is that a subclass overriding `buildValidationConstraints()` must call `parent::`.

`Url` is constructed bare, and that is not a style choice: Symfony's `Url::__construct()` takes `?array $options` first and **throws** `InvalidArgumentException` on any non-null value, so a positional argument is never correct there.
`requireTld` already defaults to `true`, so the arm needs no argument for it either.
`Regex` is the opposite — its first parameter *is* the pattern — so the tel arm passes `getTelRegex()` positionally, and `telRegex()` overrides that default, evaluated lazily like every other sugar value.
Pinned by `TextInputTest`'s `honours a custom tel regex`, `resolves a closure tel regex through evaluate` and `drops the tel and url arms when a closure condition resolves false`, the last of which also holds `getType()` to `text` on a false condition, the way `numeric()`'s [step seed](#numeric-seeds-the-step-through-isnumeric-not-the-captured-condition) is held.

The default pattern is Filament's, one token apart: the leading `[0-9]{1,4}` is spelled `\d{1,4}`, so a diff against Filament shows a difference that is not one.
PHP's PCRE leaves `\d` ASCII-only without the `u` modifier, which the pattern does not carry, so the match set is identical — including that neither form accepts Eastern Arabic digits.

What guards that claim is a **behavioural** test, not a string comparison: `TextInputTest`'s `adds a Regex constraint whose default pattern accepts phone numbers and rejects prose` runs the built constraint's pattern over samples (`+420 123 456 789`, `(123)456-7890`, `1234567890` accepted; `abc` and the empty string rejected), so it survives a respelling of the pattern while failing if anyone changes what the default *means*.
Asserting the literal against `getTelRegex()` would be a tautology, because both sides read the same getter.

### `tel()` writes the shared regex slot

`tel()` appends no `Regex` of its own in the override.
It writes the same single `$regexPattern` slot [`regex()`](can-be-validated.md) writes, as a closure answering the tel pattern while `isTel()` holds — so `->tel()->regex('/…/')` keeps the later call and `->regex('/…/')->tel()` keeps the tel pattern, yielding exactly **one** `Regex` either way.
That is Filament's own behaviour.

So the tel pattern lands where the regex slot sits, after the `in` `Choice` and before the escape-hatch pairs, rather than among the type arms at the tail of the override, and `TextInputTest`'s pinned emission order holds it there.
Message order is the only thing a consumer can see of that position.

The class-key collision sits one arm over.
`integer()` contributes a `Regex` of its own — upstream it is `rule('integer')`, not the slot, so it stays in the override — and `->tel()->integer()` therefore builds two, with a `Regex::class` message reaching both.
Nothing guards it, and nothing should: an input is not sensibly both a phone number and an integer, so the pairing is meaningless rather than merely unsupported.
Filament has no equivalent because its message keys are rule names, and there the two arms key `regex` and `integer` separately; the shared key is what [class-keyed messages](can-be-validated.md#validation-messages-apply-after-the-constraints-are-built) cost.
The same cost applies to [`gte()`/`minValue()`](can-be-validated.md#the-ordering-arms-compare-with-php-not-with-laravels-size-table), which share `GreaterThanOrEqual::class`.

### `numeric()` seeds the step through `isNumeric()`, not the captured condition

Filament seeds the interval by capturing the argument — `step(static fn (): ?string => $condition ? 'any' : null)` — and `numeric()` takes `bool | Closure`.
A `Closure` is an object, so it is truthy: passing one there yields `step="any"` unconditionally, even where `getType()` evaluates the same flag and falls back to `text`.
The port's seed takes the component and reads the flag back, `static fn (TextInput $component): ?string => $component->isNumeric() ? 'any' : null`, so a closure condition is evaluated in one place — [`isNumeric()`](../../../packages/forms/src/Components/TextInput.php) — and every consequence of `numeric()` agrees with it.
`integer()`'s `step(1)` seed reads `isInteger()` back the same way, where Filament's captures its condition too.
Identical to Filament for a `bool` condition, which is every call that does not pass a closure; pinned by `TextInputTest`'s `seeds step any from numeric so the browser accepts decimals` and `seeds no step when a closure numeric condition resolves false`.
The divergence is the whole reason the seed is a component-reading closure rather than the shorter capture; `getInputMode()` below reads the flag back the same way, and gains the same correction for free.

### `integer()` validates with a regex, not a `Type` constraint

Filament's `integer()` applies Laravel's `integer` rule, which is `filter_var($value, FILTER_VALIDATE_INT) !== false` and so accepts the numeric **string** an HTML form actually submits (`"5"`).
Neither Symfony counterpart does: `Type('integer')` is `is_int()`, which rejects `"5"`, and `Type('digit')` is `ctype_digit()`, which rejects `"-5"`.
The port appends `Regex('/^[+-]?\d+$/')` instead — the same reasoning that makes `numeric()` reach for `Type('numeric')` (`is_numeric()`) rather than a strict numeric type.
The pattern spells its digit class `\d`, which matches the same ASCII digits as `[0-9]` without the `u` modifier, as the [tel pattern](#the-type-sugar-constraint-arms-live-in-an-override-not-in-the-setters) does.

Two edges where the regex and the rule it ports disagree, both accepted.
The pattern is the stricter on whitespace — `filter_var` trims, so Laravel accepts `" 5 "` and the anchored pattern does not — and the looser on magnitude, since `filter_var` rejects a digit run past `PHP_INT_MAX` and the pattern takes any length.
Neither is worth a normaliser: the strings a `type="number"` input submits carry no padding, and a value that overflows `int` is a state-cast problem rather than a validation one.
A decimal string is rejected by both.

Worth revisiting if a state cast ever converts the field's state to a real `int` before validation runs — a `Type('integer')` arm would then say what it means, though the regex keeps working either way, since `RegexValidator` stringifies a scalar before matching.
Validation reads the raw host property ([semantics](checkbox.md#validation-reads-the-raw-state-so-the-boolean-type-constraint-is-live)), so there is nothing between the submitted string and the constraint.

### The value bounds are comparison constraints, not Laravel's `min` and `max`

Filament's `maxValue()` / `minValue()` push Laravel `max:` / `min:` rules — `->rule(fn (TextInput $component): string => "max:{$component->getMaxValue()}", …)` — and what those rules *mean* depends on the value **and** on the field's other rules.
[`getSize()`](../../../vendor/laravel/framework/src/Illuminate/Validation/Concerns/ValidatesAttributes.php) returns the number itself only when the value `is_numeric()` *and* the field also carries one of `numeric` / `integer` / `decimal`; otherwise it falls back to `count()` for an array, kilobytes for a file, and `mb_strlen()` for everything else.
So Filament's `maxValue(10)` on a field without `numeric()` caps the **length** at ten characters.

The port appends [`GreaterThanOrEqual`](../../../vendor/symfony/validator/Constraints/GreaterThanOrEqual.php) / [`LessThanOrEqual`](../../../vendor/symfony/validator/Constraints/LessThanOrEqual.php), which always compare magnitude, because length already has a home: [`CanBeLengthConstrained`](can-be-length-constrained.md) owns `maxLength()` / `minLength()` and the `maxlength` / `minlength` attributes.
Splitting the two meanings across two APIs is what the HTML attributes do too — `max` and `maxlength` are different attributes, and this field renders all four — so the port matches the platform rather than the rule string.
The arms are peers of the type arms in the same [override](#the-type-sugar-constraint-arms-live-in-an-override-not-in-the-setters), appended after them.

Each arm is appended only when [`filled()`](../support/helpers.md) accepts its *evaluated* bound, and that guard is required rather than defensive: [`AbstractComparison::__construct()`](../../../vendor/symfony/validator/Constraints/AbstractComparison.php) throws `ConstraintDefinitionException` when neither `value` nor `propertyPath` is set, so `new LessThanOrEqual(null)` never constructs.
`filled()` rather than `!== null` because Filament guards its rule with `filled()` too, and the case is real: `maxValue(fn () => $settings->get('max_qty'))` resolving to `''` — an unset setting, an empty admin field — would otherwise build `LessThanOrEqual('')`, which every non-empty submission fails with the message *"This value should be less than or equal to ."*.
Zero is a value and not a blank, so `maxValue(0)` and `minValue('0')` both build their arm: [`blank()`](../../../packages/support/src/helpers.php) answers `false` for an `is_numeric()` value, and a numeric *string* never reaches that arm because the `mb_trim` string arm above it already answers.
That is the opposite of [`CanBeLengthConstrained`](can-be-length-constrained.md), which drops a zero bound with `> 0`, and deliberately: `Length(max: 0)` forbids all input and is never what a caller meant, while `maxValue(0)` ("at most zero") is an ordinary constraint on a signed field.
Pinned by `TextInputTest`'s `adds a comparison constraint for each bound that is set`, `drops a bound whose closure resolves null`, `drops a bound that resolves blank rather than building an empty comparison` and `keeps a zero bound, which is a value rather than a blank`.

The setters narrow Filament's `scalar | Closure | null` to `int|float|string|Closure|null`, so `maxValue(true)` is a `TypeError` here where Filament interpolated it as `max:1`.
`scalar` is a PHPDoc pseudo-type with no native equivalent, so a port that types its setters natively ([convention](../../../.claude/rules/php.md)) has to enumerate the arms — and `bool` is the one left out, having no meaning as a numeric bound.
The enumerated union is also what lets `getMaxValue()` carry a native return type at level max, since `evaluate()` infers it by subtracting `Closure` from the property's union.

**The bounds compare the raw submitted string.**
Validation reads the raw host property ([semantics](checkbox.md#validation-reads-the-raw-state-so-the-boolean-type-constraint-is-live)), so a browser sends `"7"` and the constraint receives a string, never an `int`.
The port adds no cast and needs none for the numeric case: PHP compares a numeric string against an `int` or `float` bound numerically, so `"7" <= 10` holds and `"70" <= 10` does not — pinned by `TextInputTest`'s `compares a submitted numeric string by magnitude`.
A **non-numeric** string is the case that parts from Laravel, and `getSize()` is why: it measures such a string's *length* (3 for `"abc"`, which clears `max:10`) where PHP compares its *characters*, stringifying the bound — so `"abc" <= 10` becomes `"abc" <= "10"` → false, and a junk value picks up a bounds violation beside whatever the `numeric()` `Type` arm reports, if the field carries one.
Revisit if a state cast ever converts the field's state to a real number before validation runs — the same condition the [integer regex](#integer-validates-with-a-regex-not-a-type-constraint) waits on.

The **empty** string is not this divergence's doing and is not confined to these arms: Laravel never runs `min:` / `max:` on a blank string at all, because `presentOrRuleIsImplicit()` gates every non-implicit rule on presence, and the port does the same one layer up — schemas' [presence gate](../schemas/presence-gate.md) filters both comparison arms out before the runner sees them, so a cleared input reports neither bound.
The arms themselves are unguarded, so an asymmetry is latent in them: reached with `''`, `GreaterThanOrEqual` violates for any bound while `LessThanOrEqual` passes for any bound, and only the gate in front stands between that and a user's screen.
`TextInputTest`'s `reports neither bound against the empty string a cleared input submits` pins the outcome, holding a `null` state skipping both arms beside it, and `still reports both bounds on a required field, whose RequiredValue survives the gate` pins that `required()` is untouched.

### Input mode is a bespoke getter, not the `HasInputMode` concern

Filament composes [`HasInputMode`](../../../vendor/filament/forms/src/Components/Concerns/HasInputMode.php) (an `inputMode()` setter + `getInputMode()`), and `numeric()` / `integer()` push closures into it (`'decimal'` / `'numeric'`).
The port composes no such concern: [`getInputMode()`](../../../packages/forms/src/Components/TextInput.php) is a fixed method branching `isInteger()` → `'numeric'`, `isNumeric()` → `'decimal'`, else `null`.
The rendered result matches Filament for a `bool` condition — for a closure one it reads the flag back rather than capturing it, exactly as the [step seed](#numeric-seeds-the-step-through-isnumeric-not-the-captured-condition) does — but there is no user-facing `inputMode()` setter.

The getter reads `isInteger()`, which both sides carry: Filament's `TextInput` keeps the same `$isInteger` flag and public accessor, so the branch needs no state Filament lacks.

Reversible when `HasInputMode` is ported, at which point the concern replaces the bespoke getter and the skipped row flips to a plain compose.

### A revealable input is rendered as a password server-side

Filament renders **no** `type` attribute at all on a revealable input and lets Alpine bind it (`x-bind:type="isPasswordRevealed ? 'text' : 'password'"`).
An input with no `type` is a text input, so a page whose JavaScript never boots shows the password in clear.
The port renders `type="password"` and lets the controller swap it, which costs nothing — the buttons are inert until the controller connects, and CSS shows only the reveal one — and fails safe.

### Copy confirms through a status region, not a tooltip

Filament's `CopyAction` builds an Alpine click handler that writes the wrapper's input value, `$el.closest('.fi-input-wrp').querySelector('input').value`, to the clipboard and confirms through its `$tooltip` magic (tippy), passing the message as `Js::from($this->getCopyMessage($state))`.
The port copies `inputTarget.value`, the same live value, and confirms by writing the message into a `<span role="status" aria-live="polite">` the wrapper carries, clearing it again after `copyMessageDuration` milliseconds.

That span is rendered **empty rather than `hidden`**, and the distinction is the whole point of it: `[hidden]` is `display: none`, which keeps the element out of the accessibility tree, and a live region that enters the tree already holding its text is not reliably announced.
So the region ships on every copyable field and only its text moves; `text-input.css` hides it while it is `:empty`, through `opacity` rather than `display` or `visibility`, both of which would take it out of the tree again.

Filament's tooltip has no equivalent here: `support` ships no tooltip primitive, and the only notification API is server-rendered, so saying "Copied" through it would buy a live round trip for a purely client-side event.
The status region is announced to a screen reader, which the tooltip was not.

`navigator.clipboard` is `undefined` outside a secure context, so the controller checks for it and returns; a rejected `writeText()` is caught the same way.
A plain-HTTP page therefore gets a button that does nothing rather than a console error, and the message is written only after a copy that happened.

### `currentPassword()` drops Filament's guard argument

Filament's signature is `currentPassword(bool | Closure $condition = true, ?string $guard = null)`, the guard naming the Laravel auth guard its `current_password` rule validates against.
Symfony's `UserPassword` takes no such argument: the token it compares against comes from the token storage of whichever firewall handled the request, so the choice belongs in `security.yaml` rather than on the field.
The parameter is dropped rather than mapped onto anything.

There is no public `isCurrentPassword()` either, matching Filament — the flag is protected and only `buildValidationConstraints()` reads it.
