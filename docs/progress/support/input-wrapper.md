# Input wrapper (support)

`packages/support/templates/components/InputWrapper/index.html.twig` — the
anonymous Twig component (`<twig:SylamentSupport:InputWrapper>`) that ports
Filament's `<x-filament::input.wrapper>`. It is the chrome-bearing element a
text-like control sits inside: one `<div>` carrying the background, ring,
shadow and the focus / invalid / disabled state, wrapping an optional prefix
affix, the control passed as the `content` block, and an optional suffix affix.
An affix holds up to three things — actions, an icon, a label — and is rendered
only when it holds at least one.

It lives in **support**, not forms, because Filament files it here too and
because M4 · 4's editable table columns must reach it without tables depending
on forms. The classes are `sy-input-wrp*` — no package code in the name, the
same choice `sy-btn` makes, because support is the package everything else
builds on.

Forms consumes it: every text-like control renders inside it, and
`.sy-fo-input` / `.sy-fo-select` went transparent in the same epic
([field.md](../forms/field.md#the-wrapper-owns-the-chrome-and-the-control-is-transparent)).
Its render coverage lives over there too —
[`InputWrapperRenderTest`](../../../packages/forms/tests/Integration/Components/InputWrapperRenderTest.php)
— because support's own fixture Kernel registers no TwigComponentBundle, so a
shipped component's render tests live wherever its first consumer does, the
split [pagination.md](pagination.md) and [badge.md](badge.md) record.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/resources/views/components/input/wrapper.blade.php`](../../../vendor/filament/support/resources/views/components/input/wrapper.blade.php)
  — the Blade view: the props, the affix presence tests, the render order
- [`support/resources/css/components/input/wrapper.css`](../../../vendor/filament/support/resources/css/components/input/wrapper.css)
  — the stylesheet, ported with the prefix rewrite and the deltas below
- [`forms/src/Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php)
  (`wrapInputHtml()`) — the live path that fills the props from a field, which
  forms' own affix surface will port

## Surface

Every point of this component. _Blocked by_ names what has to land first.
_Detail_ carries two links, either of them: **semantics** → the permanent note
in [Semantics](#semantics); **diverges** → the permanent note in
[Divergences](#divergences) — or a pointer to the file that owns the row.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Root `<div class="sy sy-input-wrp">` + passthrough merge](../../../packages/support/templates/components/InputWrapper/index.html.twig) | [`$attributes->except(...)->class([...])`](../../../vendor/filament/support/resources/views/components/input/wrapper.blade.php) | done | — | [diverges](#nothing-is-stripped-from-the-passthrough-attributes) |
| [`disabled` → `sy-disabled`](../../../packages/support/templates/components/InputWrapper/index.html.twig) | [`'fi-disabled' => …`](../../../vendor/filament/support/resources/views/components/input/wrapper.blade.php) | done | — | — |
| [`valid` → `sy-invalid`](../../../packages/support/templates/components/InputWrapper/index.html.twig) | [`'fi-invalid' => …`](../../../vendor/filament/support/resources/views/components/input/wrapper.blade.php) | done | — | — |
| [`prefix` / `suffix` labels](../../../packages/support/templates/components/InputWrapper/index.html.twig) | [`fi-input-wrp-label`](../../../vendor/filament/support/resources/views/components/input/wrapper.blade.php) | done | — | — |
| [`prefixIcon` / `suffixIcon` (+ `…IconAlias`)](../../../packages/support/templates/components/InputWrapper/index.html.twig) | [`generate_icon_html($prefixIcon, $prefixIconAlias, …)`](../../../vendor/filament/support/resources/views/components/input/wrapper.blade.php) | done | — | [semantics](#an-affixs-presence-is-decided-by-the-rendered-icon-not-by-the-icon-prop) |
| [`prefixIconColor` / `suffixIconColor`](../../../packages/support/templates/components/InputWrapper/index.html.twig) | [`->color(IconComponent::class, $prefixIconColor)`](../../../vendor/filament/support/resources/views/components/input/wrapper.blade.php) | done | — | [color-policies.md](color-policies.md) |
| [`Colors\Components\InputComponent\WrapperComponent\IconComponent`](../../../packages/support/src/Colors/Components/InputComponent/WrapperComponent/IconComponent.php) | [`InputComponent/WrapperComponent/IconComponent.php`](../../../vendor/filament/support/src/View/Components/InputComponent/WrapperComponent/IconComponent.php) | done | — | [color-policies.md](color-policies.md#an-empty-map-is-a-real-answer-not-a-missing-one) |
| [`prefixActions` / `suffixActions`, filtered on `isVisible()`](../../../packages/support/templates/components/InputWrapper/index.html.twig) | [`array_filter(…, fn (Action $a) => $a->isVisible())`](../../../vendor/filament/support/resources/views/components/input/wrapper.blade.php) | done | — | [semantics](#the-wrapper-filters-visibility-so-a-caller-passes-its-prepared-array-whole) |
| [`inlinePrefix` / `inlineSuffix` → `sy-inline`](../../../packages/support/templates/components/InputWrapper/index.html.twig) | [`'fi-inline' => $inlinePrefix`](../../../vendor/filament/support/resources/views/components/input/wrapper.blade.php) | done | — | — |
| [`content` block → `sy-input-wrp-content-ctn`](../../../packages/support/templates/components/InputWrapper/index.html.twig) | [`{{ $slot }}`](../../../vendor/filament/support/resources/views/components/input/wrapper.blade.php) | done | — | — |
| [`input-wrapper.css`](../../../packages/support/assets/src/styles/components/input-wrapper.css) | [`components/input/wrapper.css`](../../../vendor/filament/support/resources/css/components/input/wrapper.css) | done | — | [semantics](#the-focus-ring-stands-down-while-an-affix-action-has-focus) · [diverges](#the-hover-ring-the-text-controls-carried-moves-onto-the-wrapper) · [diverges](#the-prefixs-non-inline-arm-drops-a-padding-it-already-has) |
| Livewire loading indicator (`wire:loading` branch, `fi-input-wrp-prefix-has-content`, `fi-input-wrp-content-ctn-ps`) | [the `$hasLoadingIndicator` arms](../../../vendor/filament/support/resources/views/components/input/wrapper.blade.php) | blocked | a loading-indicator component and a live-request signal, neither ported — [README](README.md#components) | [diverges](#no-loading-indicator-and-three-css-hooks-go-with-it) |
| Click-to-focus relay (`x-on:click="$dispatch('focus-input')"`) | [`$canClickPrefixAffix` / `$canClickSuffixAffix`](../../../vendor/filament/support/resources/views/components/input/wrapper.blade.php) | todo | — | [diverges](#the-click-to-focus-relay-is-not-ported) |
| `alpineDisabled` / `alpineValid` | [the `x-bind:class` pair](../../../vendor/filament/support/resources/views/components/input/wrapper.blade.php) | skipped | — | [diverges](#alpinedisabled-and-alpinevalid-are-not-ported) |

Coverage:
[`InputWrapperRenderTest`](../../../packages/forms/tests/Integration/Components/InputWrapperRenderTest.php)
in forms — the content-only render, each affix's presence test and render order,
the `sy-inline` / `-has-label` / `sy-disabled` / `sy-invalid` toggles, the
visibility filter, and the empty-`Markup` affix that opens nothing. The
passthrough merge on the root is pinned one file over, by the checkbox list's
search box carrying its own class onto the wrapper
([`CheckboxListTest`](../../../packages/forms/tests/Integration/Components/CheckboxListTest.php)).
The colour policy is pinned separately by
[`SylamentSupportBundleTest`](../../../packages/support/tests/Integration/SylamentSupportBundleTest.php),
which resolves it by class-string — the whole of what a policy this shallow can
get wrong.

## Semantics

Faithful ports subtle enough that re-deriving them from the source would
plausibly get them wrong.

### An affix's presence is decided by the rendered icon, not by the icon prop

`hasPrefix` reads `prefixIconHtml is not empty`, not `prefixIcon`, because the
icon is resolved before it is counted:

```twig
{% set prefixIconHtml = generateIconHtml(prefixIcon, prefixIconAlias, {…}) %}
{% set hasPrefix = visiblePrefixActions|length > 0 or prefixIconHtml is not empty or hasPrefixLabel %}
```

That is Filament's own test — its `$hasPrefix` counts `$prefixIconHtml !== null`,
not the prop — and it buys two things a prop test loses. An
**alias-only** affix works: `generateIconHtml()` is the only place alias
resolution happens, so a caller naming just `prefixIconAlias` still gets an
icon, which is how a consumer overrides one of ours without knowing its default.
And an icon that resolves to nothing cannot open an empty affix div with a
border on it.

The same care applies to the labels: `prefix is filled` is the house Twig test,
which gives `Twig\Markup` text semantics, so a `Markup('')` is blank rather than
`Countable`-empty — see [helpers.md](helpers.md).

### The wrapper filters visibility, so a caller passes its prepared array whole

Both action arrays are filtered on `action.isVisible` inside the component,
matching the Blade's two `array_filter()` calls. A caller therefore hands over
the actions it prepared without pre-filtering them, and — because the filter
runs before `hasPrefix` — an affix holding nothing but hidden actions renders no
div at all rather than an empty bordered one.

### The focus ring stands down while an affix action has focus

`.sy-input-wrp:not(.sy-disabled):not(:has(.sy-ac:focus))` is Filament's
`:not(.fi-disabled):not(:has(.fi-ac-action:focus))` with our marker class
substituted: every action trigger variant in `actions` renders `sy-ac` on its
root ([action.md](../actions/action.md)), which is the class `fi-ac-action`
translates to here. Without the arm, clicking an affix action would ring the
whole wrapper as though the control itself had focus.

It is a class-name coupling across packages that no test holds: support's
stylesheet names a class `actions`' templates emit, and support cannot depend on
`actions` to check it. Renaming `sy-ac` silently restores the wrong ring.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold.

### No loading indicator, and three CSS hooks go with it

Filament's wrapper reads `wire:target` off its own attribute bag and, when it
finds one, renders a loading indicator inside the prefix affix — opening the
affix even when it has no content, so that the indicator has somewhere to sit.
Three hooks exist only to serve that: `fi-input-wrp-prefix-has-content` (the
prefix is `hidden` by default and this class flips it to `flex`),
`fi-input-wrp-content-ctn-ps` (padding the content when the indicator occupies
an otherwise absent prefix) and the CSS fallback arm
`&:not(:has(.fi-input-wrp-content-ctn)) > *`.

None is ported. Our affixes render only when they hold content, so the base
prefix rule is `flex` outright and the `-has-content` toggle has nothing to
toggle; the content container always renders, so the `:has()` fallback has
nothing to fall back for. The row is **blocked** rather than skipped: a loading
indicator is wanted at parity, and it needs both a loading-indicator component
(unported — see the [README](README.md#components)) and a signal that a live
request is in flight, which the UX LiveComponent stack expresses differently
from `wire:loading`. Reinstating it later reinstates the two classes and the
fallback arm with it.

### The click-to-focus relay is not ported

Filament makes a non-interactive affix focus the control: the wrapper dispatches
`focus-input` on click, and the field listens with `x-on:focus-input.stop`. The
whole mechanism is Alpine — `$dispatch` on the wrapper and a listener the field
binds — and the guard around it (`$canClickPrefixAffix`) exists only to avoid
stealing clicks from affix actions.

Ours has no JS at all, so the affixes are inert. It is **todo**, not skipped:
the behaviour is worth having and the Stimulus shape is obvious (a controller
on the wrapper forwarding a click to the control it contains, ignoring clicks
that originated inside `.sy-input-wrp-actions`). Nothing waits on it, so it
lands whenever a consumer asks.

### `alpineDisabled` and `alpineValid` are not ported

The Blade takes two expression strings and, when either is filled, binds
`fi-disabled` / `fi-invalid` through `x-bind:class` *instead of* rendering them
server-side — Filament's own conditional (`(! $hasAlpineClasses) && $disabled`)
makes the two mutually exclusive. It exists so a client-side-only state (a field
Alpine disables without a round trip) can repaint the chrome.

Our state is server-rendered: the disabled and invalid classes come off
`disabled` and `valid`, and a change to either arrives as a re-render. Porting
the props would mean inventing a client expression language the toolkit does not
have, so they are **skipped** rather than blocked. A consumer that genuinely
needs client-side repainting can write the classes itself — the passthrough
merge appends to `class`.

### The hover ring the text controls carried moves onto the wrapper

Filament's wrapper has no `hover:` ring; ours does —
`hover:ring-gray-950/20 dark:hover:ring-white/20` — because our `.sy-fo-input`
and `.sy-fo-select` shipped exactly that pair as a recorded divergence from
Filament's controls
([forms/styles.md](../forms/styles.md#the-input-wrapper-invents-a-hover-ring),
which owns the note now that the ring lives here). Leaving it behind on the
transparent controls would have painted nothing and dropping it would have been
a visual regression nobody decided, so the divergence moved across unchanged.

### Nothing is stripped from the passthrough attributes

Filament's root renders `$attributes->except(['wire:target', 'tabindex'])`: the
field forwards its whole attribute bag to the wrapper, so the wrapper has to
drop the two attributes that belong to the control rather than to its chrome
(`wire:target` is consumed by the loading branch above, and a `tabindex` meant
for the input would make the wrapper itself focusable).

Ours merges the bag whole, because nothing forwards a control's attributes here:
a caller writes on the tag only what it means for the wrapper, and forms' field
templates keep the control's own attributes on the control. Should a consumer
ever forward a bag wholesale, the `except()` belongs on *its* side of the call,
not here.

### The prefix's non-inline arm drops a padding it already has

Vendor's `&:not(.fi-inline)` prefix arm re-applies `ps-3`, which the base rule
already applied one line above; ours applies only `border-e border-gray-200 pe-3
dark:border-white/10`. The suffix's mirrored arm keeps its `ps-3` because there
it is the *opposite* side from the base rule's `pe-3` and does real work. The
rendered result is identical on both; recorded so a rule-by-rule diff against
the vendor stylesheet does not read the missing token as a dropped padding.
