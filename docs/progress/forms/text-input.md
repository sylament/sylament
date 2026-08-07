# TextInput (forms)

`Sylament\Forms\Components\TextInput` — the single-line text field, extending
[`Field`](../../../packages/forms/src/Components/Field.php). An early subset of
Filament's `TextInput`: the type sugar
(`type`/`email`/`numeric`/`password`/`tel`/`url`), its `getType`/`getInputMode`
derivation, length constraints via the shared
[`CanBeLengthConstrained`](can-be-length-constrained.md) concern, the `Email` /
numeric `Type` / tel `Regex` / `Url` validation arms that sugar contributes to
[`CanBeValidated`](can-be-validated.md), and the `step` interval `numeric()`
seeds. The affix/mask/copy/reveal/state-cast surface is not ported yet.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`forms/src/Components/TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php)
  — the field and every trait it composes
- [`forms/src/Components/Concerns/`](../../../vendor/filament/forms/src/Components/Concerns)
  — the concerns behind the unported rows below

## Surface

Every point this field adds over `Field`. `Field`'s own surface (label, hint,
helper text, placeholder, required, slots, rendering chrome) is tracked in
[`field.md`](field.md), not here. _Blocked by_ names what has to land first.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`type()` / `getType()`](../../../packages/forms/src/Components/TextInput.php) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | — |
| [`email()` / `isEmail()`](../../../packages/forms/src/Components/TextInput.php) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | [diverges](#the-type-sugar-constraint-arms-live-in-an-override-not-in-the-setters) |
| [`numeric()` / `isNumeric()`](../../../packages/forms/src/Components/TextInput.php) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | [diverges](#the-type-sugar-constraint-arms-live-in-an-override-not-in-the-setters) · [diverges](#numeric-seeds-the-step-through-isnumeric-not-the-captured-condition) |
| [`buildValidationConstraints()` arms](../../../packages/forms/src/Components/TextInput.php) | [`TextInput::email()` / `numeric()` / `tel()` / `url()`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | [diverges](#the-type-sugar-constraint-arms-live-in-an-override-not-in-the-setters) |
| [`password()` / `isPassword()`](../../../packages/forms/src/Components/TextInput.php) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | — |
| [`getInputMode()`](../../../packages/forms/src/Components/TextInput.php) | [`Concerns/HasInputMode.php`](../../../vendor/filament/forms/src/Components/Concerns/HasInputMode.php) | done | — | [diverges](#input-mode-is-a-bespoke-getter-not-the-hasinputmode-concern) |
| `HasInputMode` (concern) | [`Concerns/HasInputMode.php`](../../../vendor/filament/forms/src/Components/Concerns/HasInputMode.php) | skipped | — | [diverges](#input-mode-is-a-bespoke-getter-not-the-hasinputmode-concern) |
| `integer()` | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | todo | — | — |
| [`tel()` / `telRegex()` / `isTel()`](../../../packages/forms/src/Components/TextInput.php) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | [diverges](#the-type-sugar-constraint-arms-live-in-an-override-not-in-the-setters) |
| [`url()` / `isUrl()`](../../../packages/forms/src/Components/TextInput.php) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | [diverges](#the-type-sugar-constraint-arms-live-in-an-override-not-in-the-setters) |
| `currentPassword()` | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | todo | authentication layer | — |
| `maxValue()` / `minValue()` / `getMaxValue()` / `getMinValue()` | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | todo | — | — |
| `mask()` / `getMask()` | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | todo | `RawJs` | — |
| `revealable()` / `isPasswordRevealable()` | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | todo | `TextInput/Actions` | — |
| `copyable()` / `isCopyable()` | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | todo | `HasAffixes`, `TextInput/Actions` | — |
| [`HasStep`](../../../packages/forms/src/Components/Concerns/HasStep.php) | [`Concerns/HasStep.php`](../../../vendor/filament/forms/src/Components/Concerns/HasStep.php) | done | — | — |
| `HasAffixes` (concern) | [`Concerns/HasAffixes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasAffixes.php) | todo | prebuilt affix actions | — |
| [`HasDatalistOptions`](../../../packages/forms/src/Components/Concerns/HasDatalistOptions.php) | [`Concerns/HasDatalistOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasDatalistOptions.php) | done | — | [diverges](#the-datalist-drops-the-arrayable-arm) |
| [`CanBeAutocapitalized`](../../../packages/forms/src/Components/Concerns/CanBeAutocapitalized.php) | [`Concerns/CanBeAutocapitalized.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeAutocapitalized.php) | done | — | — |
| `disableAutocapitalize()` | [`Concerns/CanBeAutocapitalized.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeAutocapitalized.php) | skipped | — | [diverges](can-be-autocompleted.md#the-deprecated-disable-aliases-are-not-ported) |
| [`CanBeAutocompleted`](../../../packages/forms/src/Components/Concerns/CanBeAutocompleted.php) | [`Concerns/CanBeAutocompleted.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeAutocompleted.php) | done | — | [detail](can-be-autocompleted.md) |
| [`CanBeReadOnly`](../../../packages/forms/src/Components/Concerns/CanBeReadOnly.php) | [`Concerns/CanBeReadOnly.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeReadOnly.php) | done | — | [detail](can-be-read-only.md) |
| [`HasExtraInputAttributes`](../../../packages/forms/src/Components/Concerns/HasExtraInputAttributes.php) | [`Concerns/HasExtraInputAttributes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraInputAttributes.php) | done | — | [detail](has-extra-input-attributes.md) |
| `HasExtraAlpineAttributes` (concern) | [`support/Concerns/HasExtraAlpineAttributes.php`](../../../vendor/filament/support/src/Concerns/HasExtraAlpineAttributes.php) | todo | — | — |
| State casts + trim/strip (`getDefaultStateCasts`, `mutate*State`) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | todo | schemas state-cast surface | — |

## Divergences

### The type-sugar constraint arms live in an override, not in the setters

Filament's `email()` / `numeric()` / `url()` push their validation rule into the
shared rule list from inside the setter (`->rule('email', $condition)`), and
`tel()` pushes its pattern through a `regex()` setter that reads `getTelRegex()`
back off the component. So in Filament the rule is registered eagerly and the
condition is what stays lazy. The port's setters register no constraint at all —
they flip `isEmail` / `isNumeric` / `isTel` / `isUrl`, and `numeric()`
additionally seeds the
[step](#numeric-seeds-the-step-through-isnumeric-not-the-captured-condition) —
while
[`buildValidationConstraints()`](../../../packages/forms/src/Components/TextInput.php)
overrides the parent to append `Email()` / `Type('numeric')` /
`Regex(getTelRegex())` / `Url()` behind those flags. Same observable API and same
laziness. `tel()` needs no `regex()` setter as plumbing — the override is already
where every type arm lands — which is not a claim that `regex()` will never
exist: [`can-be-validated.md`](can-be-validated.md) carries it **todo**, and when
it lands it is a peer arm in this same override rather than something `tel()`
routes through.

The reason is **composition order**. These arms must land last — the order is
`NotBlank` → length → escape hatch → type arms, pinned by `TextInputTest` — and a
`constraint()` call from `setUp()` would file them among the escape-hatch pairs
instead, ahead of anything the user registers later. The override is what keeps
them at the tail, and it keeps the four arms in one block ordered the way
`getType()` checks the same flags. Messages are keyed `Email::class` / `Type::class` /
`Regex::class` / `Url::class` and applied by the
[post-pass](can-be-validated.md#validation-messages-apply-after-the-constraints-are-built),
so no arm passes a `message:` argument.

A second reason retired when that post-pass landed: a `Constraint` baked at setter
time used to freeze the `validationMessages()` key before the user had set it,
which is why these arms — and `Checkbox`'s, `Toggle`'s and `CanBeAccepted`'s —
once deferred through closures. Applying messages after the list is built removed
that constraint everywhere; here the ordering reason still holds, so the shape
stays. The cost is unchanged: a subclass overriding
`buildValidationConstraints()` must call `parent::`.

`Url` is constructed bare, and that is not a style choice: Symfony's
`Url::__construct()` takes `?array $options` first and **throws**
`InvalidArgumentException` on any non-null value, so a positional argument is
never correct there. `requireTld` already defaults to `true`, so the arm needs no
argument for it either. `Regex` is the opposite — its first parameter *is* the
pattern — so the tel arm passes `getTelRegex()` positionally, and `telRegex()`
overrides that default, evaluated lazily like every other sugar value. Pinned by
`TextInputTest`'s `honours a custom tel regex`, `resolves a closure tel regex
through evaluate` and `drops the tel and url arms when a closure condition
resolves false`, the last of which also holds `getType()` to `text` on a false
condition, the way `numeric()`'s
[step seed](#numeric-seeds-the-step-through-isnumeric-not-the-captured-condition)
is held.

The default pattern is Filament's, one token apart: Rector's
`SimplifyRegexPatternRector` normalises the leading `[0-9]{1,4}` to `\d{1,4}`, so
a diff against Filament shows a difference that is not one. PHP's PCRE leaves
`\d` ASCII-only without the `u` modifier, which the pattern does not carry, so the
match set is identical — including that neither form accepts Eastern Arabic
digits. Don't "restore" the character class; `composer format` puts it back.

What guards that claim is a **behavioural** test, not a string comparison:
`TextInputTest`'s `adds a Regex constraint whose default pattern accepts phone
numbers and rejects prose` runs the built constraint's pattern over samples
(`+420 123 456 789`, `(123)456-7890`, `1234567890` accepted; `abc` and the empty
string rejected), so it survives the Rector normalisation while failing if anyone
changes what the default *means*. Asserting the literal against `getTelRegex()`
would be a tautology — both sides read the same getter — and pinning the literal
string would break on the next formatter pass.

One consequence of the peer-arm shape needs deciding when `regex()` is actually
built. Filament can afford routing `tel()` through `regex()` because `regex()`
writes a single `$regexPattern` property that `getValidationRules()` emits once,
so `->tel()->regex('/…/')` keeps the later call and yields **one** rule. A peer
arm built to the
[recipe](can-be-validated.md#the-unsugared-rule-families-are-reachable-through-constraint)
does not: `->tel()->regex('/…/')` stacks **two** `Regex` constraints — nothing in
`buildValidationConstraints()` de-duplicates by class — and the message post-pass
matches by class across the whole list, so a `Regex::class` message lands on both.
Whoever ports `regex()` chooses between accepting that (two patterns, both
enforced, two identical messages) and standing the tel arm down when an explicit
pattern is set. It is a `regex()` decision, not a `tel()` one.

### `numeric()` seeds the step through `isNumeric()`, not the captured condition

Filament seeds the interval by capturing the argument —
`step(static fn (): ?string => $condition ? 'any' : null)` — and `numeric()`
takes `bool | Closure`. A `Closure` is an object, so it is truthy: passing one
there yields `step="any"` unconditionally, even where `getType()` evaluates the
same flag and falls back to `text`. The port's seed takes the component and reads
the flag back,
`static fn (TextInput $component): ?string => $component->isNumeric() ? 'any' : null`,
so a closure condition is evaluated in one place —
[`isNumeric()`](../../../packages/forms/src/Components/TextInput.php) — and every
consequence of `numeric()` agrees with it. Identical to Filament for a `bool`
condition, which is every call that does not pass a closure; pinned by
`TextInputTest`'s `seeds step any from numeric so the browser accepts decimals`
and `seeds no step when a closure numeric condition resolves false`. The
divergence is the whole reason the seed is a component-reading closure rather
than the shorter capture; `getInputMode()` below reads the flag back the same
way, and gains the same correction for free.

### Input mode is a bespoke getter, not the `HasInputMode` concern

Filament composes
[`HasInputMode`](../../../vendor/filament/forms/src/Components/Concerns/HasInputMode.php)
(an `inputMode()` setter + `getInputMode()`), and `numeric()` / `integer()` push
closures into it (`'decimal'` / `'numeric'`). The port composes no such concern:
[`getInputMode()`](../../../packages/forms/src/Components/TextInput.php) is a fixed
method returning `'decimal'` when `isNumeric()` and `null` otherwise. The rendered
result matches Filament for a `bool` condition — for a closure one it reads the
flag back rather than capturing it, exactly as the
[step seed](#numeric-seeds-the-step-through-isnumeric-not-the-captured-condition)
does — but there is no user-facing `inputMode()` setter and no `'numeric'` mode
(that arrives with `integer()`). Reversible when `HasInputMode` is ported — at
which point the bespoke getter is replaced by the concern and the row above flips
to a plain compose.

### The datalist drops the `Arrayable` arm

Filament types `datalist()` as
`array<string> | Arrayable | Closure | null` and `getDatalistOptions()` unwraps
an `Illuminate\Contracts\Support\Arrayable` with `->toArray()`. That interface is
Laravel's, with no Symfony counterpart and no port here, so the type is
`array<string>|Closure|null` and the getter has no `instanceof` branch. A caller
holding a collection-like object passes `$collection->toArray()` — or a closure
returning it — which is what the `Closure` arm is for. `Select`'s `options()`
[drops the same arm](select.md#hasoptions-drops-the-enum-class-string-arm) for
the same reason.

Everything else is Filament's: the `<datalist>` is keyed `<field id>-list`, and a
single truthiness check gates both the element and the input's `list` attribute,
so a field with no options renders neither. Pinned by `TextInputTest`'s `renders
no datalist by default`, `renders a datalist and points the input at it` and
`resolves closure datalist options through evaluate`.
