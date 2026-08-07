# TextInput (forms)

`Sylament\Forms\Components\TextInput` — the single-line text field, extending
[`Field`](../../../packages/forms/src/Components/Field.php). An early subset of
Filament's `TextInput`: the type sugar
(`type`/`email`/`integer`/`numeric`/`password`/`tel`/`url`), its
`getType`/`getInputMode` derivation, length constraints via the shared
[`CanBeLengthConstrained`](can-be-length-constrained.md) concern, the
`maxValue()` / `minValue()` magnitude bounds, the `Email` / numeric `Type` /
integer and tel `Regex` / `Url` / comparison validation arms that sugar
contributes to [`CanBeValidated`](can-be-validated.md), and the `step` interval
`numeric()` and `integer()` seed. The affix/mask/copy/reveal/state-cast surface
is not ported yet.

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
_Detail_ links a permanent note in [Semantics](#semantics) /
[Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`type()` / `getType()`](../../../packages/forms/src/Components/TextInput.php) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | — |
| [`email()` / `isEmail()`](../../../packages/forms/src/Components/TextInput.php) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | [diverges](#the-type-sugar-constraint-arms-live-in-an-override-not-in-the-setters) |
| [`numeric()` / `isNumeric()`](../../../packages/forms/src/Components/TextInput.php) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | [diverges](#the-type-sugar-constraint-arms-live-in-an-override-not-in-the-setters) · [diverges](#numeric-seeds-the-step-through-isnumeric-not-the-captured-condition) |
| [`buildValidationConstraints()` arms](../../../packages/forms/src/Components/TextInput.php) | [`TextInput::email()` / `integer()` / `numeric()` / `tel()` / `url()`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | [diverges](#the-type-sugar-constraint-arms-live-in-an-override-not-in-the-setters) |
| [`password()` / `isPassword()`](../../../packages/forms/src/Components/TextInput.php) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | — |
| [`getInputMode()`](../../../packages/forms/src/Components/TextInput.php) | [`Concerns/HasInputMode.php`](../../../vendor/filament/forms/src/Components/Concerns/HasInputMode.php) | done | — | [diverges](#input-mode-is-a-bespoke-getter-not-the-hasinputmode-concern) |
| `HasInputMode` (concern) | [`Concerns/HasInputMode.php`](../../../vendor/filament/forms/src/Components/Concerns/HasInputMode.php) | skipped | — | [diverges](#input-mode-is-a-bespoke-getter-not-the-hasinputmode-concern) |
| [`integer()` / `isInteger()`](../../../packages/forms/src/Components/TextInput.php) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | [semantics](#integer-delegates-to-numeric-so-both-arms-ship-and-the-step-order-matters) · [diverges](#integer-validates-with-a-regex-not-a-type-constraint) |
| [`tel()` / `telRegex()` / `isTel()`](../../../packages/forms/src/Components/TextInput.php) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | [diverges](#the-type-sugar-constraint-arms-live-in-an-override-not-in-the-setters) |
| [`url()` / `isUrl()`](../../../packages/forms/src/Components/TextInput.php) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | [diverges](#the-type-sugar-constraint-arms-live-in-an-override-not-in-the-setters) |
| `currentPassword()` | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | todo | authentication layer | — |
| [`maxValue()` / `minValue()` / `getMaxValue()` / `getMinValue()`](../../../packages/forms/src/Components/TextInput.php) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | [diverges](#the-value-bounds-are-comparison-constraints-not-laravels-min-and-max) |
| `mask()` / `getMask()` | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | todo | `RawJs` | — |
| `revealable()` / `isPasswordRevealable()` | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | todo | `TextInput/Actions` | — |
| `copyable()` / `isCopyable()` | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | todo | `HasAffixes`, `TextInput/Actions` | — |
| [`HasStep`](../../../packages/forms/src/Components/Concerns/HasStep.php) | [`Concerns/HasStep.php`](../../../vendor/filament/forms/src/Components/Concerns/HasStep.php) | done | — | — |
| `HasAffixes` (concern) | [`Concerns/HasAffixes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasAffixes.php) | blocked | the input wrapper ([field.md](field.md#the-input-affix-wrapper-is-not-ported)) | — |
| [`HasDatalistOptions`](../../../packages/forms/src/Components/Concerns/HasDatalistOptions.php) | [`Concerns/HasDatalistOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasDatalistOptions.php) | done | — | [diverges](#the-datalist-drops-the-arrayable-arm) |
| [`CanBeAutocapitalized`](../../../packages/forms/src/Components/Concerns/CanBeAutocapitalized.php) | [`Concerns/CanBeAutocapitalized.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeAutocapitalized.php) | done | — | — |
| `disableAutocapitalize()` | [`Concerns/CanBeAutocapitalized.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeAutocapitalized.php) | skipped | — | [diverges](can-be-autocompleted.md#the-deprecated-disable-aliases-are-not-ported) |
| [`CanBeAutocompleted`](../../../packages/forms/src/Components/Concerns/CanBeAutocompleted.php) | [`Concerns/CanBeAutocompleted.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeAutocompleted.php) | done | — | [detail](can-be-autocompleted.md) |
| [`CanBeReadOnly`](../../../packages/forms/src/Components/Concerns/CanBeReadOnly.php) | [`Concerns/CanBeReadOnly.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeReadOnly.php) | done | — | [detail](can-be-read-only.md) |
| [`HasExtraInputAttributes`](../../../packages/forms/src/Components/Concerns/HasExtraInputAttributes.php) | [`Concerns/HasExtraInputAttributes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraInputAttributes.php) | done | — | [detail](has-extra-input-attributes.md) |
| `HasExtraAlpineAttributes` (concern) | [`support/Concerns/HasExtraAlpineAttributes.php`](../../../vendor/filament/support/src/Concerns/HasExtraAlpineAttributes.php) | todo | — | — |
| State casts + trim/strip (`getDefaultStateCasts`, `mutate*State`) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | todo | schemas state-cast surface | — |

## Semantics

Behaviour that is faithful to Filament but easy to misread here.

### `integer()` delegates to `numeric()`, so both arms ship and the step order matters

`integer()` is not a peer of `numeric()` — it calls it, exactly as Filament's
does. Two consequences a reader re-deriving them from the code would plausibly
get backwards.

**The call order is load-bearing.** `numeric()` seeds `step('any')`, so
`integer()` has to call it *first* and overwrite that seed with `step(1)`
afterwards. Written the other way round the `'any'` wins and an integer input
silently accepts decimals — with a green suite, because nothing else about the
field changes. `TextInputTest`'s _makes an integer input numeric with a step of
one_ is what holds the order.

**An integer field carries both type arms.** The delegation flips `isNumeric`
too, so `buildValidationConstraints()` appends `Type('numeric')` *and* the
integer [`Regex`](#integer-validates-with-a-regex-not-a-type-constraint), and
`getType()` reports `number` off the same flag. That is Filament: its `integer()`
likewise leaves `numeric()`'s `numeric` rule in place and adds `integer` beside
it. The pair is not two independent checks, though — the regex accepts a strict
subset of `is_numeric()`, so the `Type` arm rejects nothing the regex would
accept; what it adds is a second violation on the same value, and
[`FieldWrapper`](../../../packages/forms/templates/components/FieldWrapper/index.html.twig)
lists every message, so `abc` in an integer field renders two. Dropping the
`Type` arm would mean not calling `numeric()`, which is where the step, the
`number` type and the input mode all come from. `TextInputTest`'s _validates an
integer with the numeric arm plus a regex that accepts a numeric string_ holds
both arms and their order.

## Divergences

### The type-sugar constraint arms live in an override, not in the setters

Filament's `email()` / `integer()` / `numeric()` / `url()` push their validation
rule into the shared rule list from inside the setter
(`->rule('email', $condition)`), and
`tel()` pushes its pattern through a `regex()` setter that reads `getTelRegex()`
back off the component. So in Filament the rule is registered eagerly and the
condition is what stays lazy. The port's setters register no constraint at all —
they flip `isEmail` / `isInteger` / `isNumeric` / `isTel` / `isUrl`, and
`numeric()` and `integer()` additionally seed the
[step](#numeric-seeds-the-step-through-isnumeric-not-the-captured-condition) —
while
[`buildValidationConstraints()`](../../../packages/forms/src/Components/TextInput.php)
overrides the parent to append `Email()` / `Type('numeric')` /
[integer `Regex`](#integer-validates-with-a-regex-not-a-type-constraint) /
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
them at the tail, and it keeps the five arms in one block, ordered the way
`getType()` checks the flags they share — the integer arm slotting in beside the
`numeric()` it [delegates to](#integer-delegates-to-numeric-so-both-arms-ship-and-the-step-order-matters),
since `getType()` has no branch of its own for it. Messages are keyed
`Email::class` / `Type::class` / `Regex::class` / `Url::class` and applied by the
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

That collision is already reachable without `regex()`. `integer()` contributes a
`Regex` of its own, so `->tel()->integer()` builds two today and a `Regex::class`
message reaches both. Nothing guards it, and nothing should — an input is not
sensibly both a phone number and an integer, so the pairing is meaningless rather
than merely unsupported. Filament has no equivalent because its message keys are
rule names, and there the two arms key `regex` and `integer` separately; the
shared key is what
[class-keyed messages](can-be-validated.md#validation-messages-apply-after-the-constraints-are-built)
cost. Worth knowing before a third `Regex` arm lands on a combination that *is*
sensible.

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

### `integer()` validates with a regex, not a `Type` constraint

Filament's `integer()` applies Laravel's `integer` rule, which is
`filter_var($value, FILTER_VALIDATE_INT) !== false` and so accepts the numeric
**string** an HTML form actually submits (`"5"`). Neither Symfony counterpart
does: `Type('integer')` is `is_int()`, which rejects `"5"`, and `Type('digit')`
is `ctype_digit()`, which rejects `"-5"`. The port appends `Regex('/^[+-]?\d+$/')`
instead — the same reasoning that makes `numeric()` reach for `Type('numeric')`
(`is_numeric()`) rather than a strict numeric type. The pattern spells its digit
class `\d` rather than `[0-9]` because Rector's `SimplifyRegexPatternRector`
rewrites that class inside any string literal, so the character-class form does
not survive `composer format` — the same normalisation the
[tel pattern](#the-type-sugar-constraint-arms-live-in-an-override-not-in-the-setters)
documents.

Two edges where the regex and the rule it ports disagree, both accepted. The
pattern is the stricter on whitespace — `filter_var` trims, so Laravel accepts
`" 5 "` and the anchored pattern does not — and the looser on magnitude, since
`filter_var` rejects a digit run past `PHP_INT_MAX` and the pattern takes any
length. Neither is worth a normaliser: the strings a `type="number"` input
submits carry no padding, and a value that overflows `int` is a state-cast
problem rather than a validation one. A decimal string is rejected by both.

Worth revisiting if a state cast ever converts the field's state to a real `int`
before validation runs — a `Type('integer')` arm would then say what it means,
though the regex keeps working either way, since `RegexValidator` stringifies a
scalar before matching. Today validation reads the raw host property
([semantics](checkbox.md#validation-reads-the-raw-state-so-the-boolean-type-constraint-is-live)),
so there is nothing between the submitted string and the constraint.

### The value bounds are comparison constraints, not Laravel's `min` and `max`

Filament's `maxValue()` / `minValue()` push Laravel `max:` / `min:` rules —
`->rule(fn (TextInput $component): string => "max:{$component->getMaxValue()}", …)`
— and what those rules *mean* depends on the value **and** on the field's other
rules.
[`getSize()`](../../../vendor/laravel/framework/src/Illuminate/Validation/Concerns/ValidatesAttributes.php)
returns the number itself only when the value `is_numeric()` *and* the field also
carries one of `numeric` / `integer` / `decimal`; otherwise it falls back to
`count()` for an array, kilobytes for a file, and `mb_strlen()` for everything
else. So Filament's `maxValue(10)` on a field without `numeric()` caps the
**length** at ten characters.

The port appends
[`GreaterThanOrEqual`](../../../vendor/symfony/validator/Constraints/GreaterThanOrEqual.php)
/ [`LessThanOrEqual`](../../../vendor/symfony/validator/Constraints/LessThanOrEqual.php),
which always compare magnitude, because length already has a home:
[`CanBeLengthConstrained`](can-be-length-constrained.md) owns `maxLength()` /
`minLength()` and the `maxlength` / `minlength` attributes. Splitting the two
meanings across two APIs is what the HTML attributes do too — `max` and
`maxlength` are different attributes, and this field renders all four — so the
port matches the platform rather than the rule string. The arms are peers of the
type arms in the same
[override](#the-type-sugar-constraint-arms-live-in-an-override-not-in-the-setters),
appended after them.

Each arm is appended only when its bound is non-null, and that guard is required
rather than defensive:
[`AbstractComparison::__construct()`](../../../vendor/symfony/validator/Constraints/AbstractComparison.php)
throws `ConstraintDefinitionException` when neither `value` nor `propertyPath` is
set, so `new LessThanOrEqual(null)` never constructs. The guard reads the
*evaluated* bound, so a closure resolving to `null` drops its arm as well —
pinned by `TextInputTest`'s `adds only the bound that is set` and `drops a bound
whose closure resolves null`.

**The bounds compare the raw submitted string.** Validation reads the raw host
property ([semantics](checkbox.md#validation-reads-the-raw-state-so-the-boolean-type-constraint-is-live)),
so a browser sends `"7"` and the constraint receives a string, never an `int`.
The port adds no cast and needs none for the numeric case: PHP compares a numeric
string against an `int` or `float` bound numerically, so `"7" <= 10` holds and
`"70" <= 10` does not — pinned by `TextInputTest`'s `compares a submitted numeric
string by magnitude`. Two consequences follow from PHP's comparison rules, and
neither has a Laravel counterpart, because `getSize()` measures a non-numeric
string's *length* where PHP compares its *characters*. A non-numeric string is
compared against the stringified bound, so `"abc" <= 10` becomes `"abc" <= "10"`
→ false, and a junk value picks up a bounds violation beside whatever the
`numeric()` `Type` arm reports, if the field carries one. And the empty string a
cleared input submits is not numeric either, while
[`AbstractComparisonValidator`](../../../vendor/symfony/validator/Constraints/AbstractComparisonValidator.php)
skips only `null` — so on an optional field a *lower* bound fires against `""`
(`"" >= "1"` is false) and an *upper* bound does not (`"" <= "10"` is true). On a
`required()` field that is harmless, since `NotBlank` rejects `""` anyway; on one
without it, `minValue()` quietly makes the field mandatory. Pinned by
`TextInputTest`'s `reports a lower bound against the empty string a cleared input
submits`, which also holds a `null` state skipping both arms.
Revisit if a state cast ever converts the field's state to a real number before
validation runs — the same condition the
[integer regex](#integer-validates-with-a-regex-not-a-type-constraint) waits on.

### Input mode is a bespoke getter, not the `HasInputMode` concern

Filament composes
[`HasInputMode`](../../../vendor/filament/forms/src/Components/Concerns/HasInputMode.php)
(an `inputMode()` setter + `getInputMode()`), and `numeric()` / `integer()` push
closures into it (`'decimal'` / `'numeric'`). The port composes no such concern:
[`getInputMode()`](../../../packages/forms/src/Components/TextInput.php) is a fixed
method branching `isInteger()` → `'numeric'`, `isNumeric()` → `'decimal'`, else
`null`. The rendered result matches Filament for a `bool` condition — for a
closure one it reads the flag back rather than capturing it, exactly as the
[step seed](#numeric-seeds-the-step-through-isnumeric-not-the-captured-condition)
does — but there is no user-facing `inputMode()` setter.

What the fixed getter costs is a port-only accessor. Filament records "this field
is an integer" *inside* `HasInputMode`, as one of the closures it pushes, and
carries no `isInteger()` on `TextInput` at all — the closure is the only thing
that knows. Reading a flag back needs somewhere to read it from, so the port
keeps a `$isInteger` property and a public `isInteger()` beside the other four
`is*()` accessors. The shape is Filament's own —
[`NumberConstraint`](../../../vendor/filament/query-builder/src/Constraints/NumberConstraint.php)
in `query-builder` carries exactly this pair, and its operators call it — just
not on `TextInput`.

Reversible when `HasInputMode` is ported — at which point the bespoke getter is
replaced by the concern and the row above flips to a plain compose. `isInteger()`
stays regardless: the
[step seed](#integer-delegates-to-numeric-so-both-arms-ship-and-the-step-order-matters)
and the [validation arm](#integer-validates-with-a-regex-not-a-type-constraint)
both read it.

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
