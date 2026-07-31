# Color policies (support)

`Sylament\Support\Colors\Components\` — the per-element contrast policies, plus
the two contracts in `Colors\Contracts\` and the three shade resolvers in
`Colors\Maps\`. A policy is not renderable and holds no state: it answers one
question, `getColorMap(array $color): array<string, int>` — *given this palette,
which shade does each slot of this element paint with?* — and expresses the
answer as intent (`the text must be readable against this palette's shade 50`)
rather than as a shade number.

This is the layer that was missing before the colour epic, and the only one that
was: the swap classes, the component CSS and the `--sy-*` token bridge all
predate it. Without it every component hardcoded a shade — `badge.css` applied
`text-color-600` — and since palettes do not share a lightness curve, one fixed
shade cannot serve them all.

The registry that calls into these is [`color-manager.md`](color-manager.md); the
palette maths they measure with is [`color.md`](color.md).

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/src/View/Components/`](../../../vendor/filament/support/src/View/Components)
  — the thirteen policy objects, the `Contracts/` pair and the `ColorMaps/` trio

Two scope notes, because that directory is not homogeneous.
`View/Components/ModalComponent.php` sits in it and is **not** a colour policy —
it is the static global-defaults holder for modals, tracked as the deferred
defaults seam in [`modal.md`](modal.md). And the rows below track the **policy
objects**; how each rendered element *reaches* its policy — the prop, the
template call, the CSS branch — stays in that element's own file
([`badge.md`](badge.md), [`button.md`](button.md), [`link.md`](link.md),
[`icon-button.md`](icon-button.md), [`dropdown-item.md`](dropdown-item.md),
[`modal.md`](modal.md), [`section.md`](section.md),
[`forms/toggle.md`](../forms/toggle.md)), so no element's colour status is
recorded twice.

## Surface

Every point of this tier. _Blocked by_ names what has to land first. _Detail_
carries three links, any of them: **detail** → the write-up in
[Details](#details), deleted once the row is **done**; **semantics** → the
permanent note in [Semantics](#semantics); **diverges** → the permanent note in
[Divergences](#divergences). Rows never disappear — only their detail sections do.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Contracts\HasColor`](../../../packages/support/src/Colors/Contracts/HasColor.php) | [`View/Components/Contracts/HasColor.php`](../../../vendor/filament/support/src/View/Components/Contracts/HasColor.php) | done | — | [diverges](#the-tier-is-filed-under-colors-not-under-twigcomponents) |
| [`Contracts\HasDefaultGrayColor`](../../../packages/support/src/Colors/Contracts/HasDefaultGrayColor.php) | [`View/Components/Contracts/HasDefaultGrayColor.php`](../../../vendor/filament/support/src/View/Components/Contracts/HasDefaultGrayColor.php) | done | — | — |
| [`Maps\ComponentColorMap`](../../../packages/support/src/Colors/Maps/ComponentColorMap.php) | [`ColorMaps/ComponentColorMap.php`](../../../vendor/filament/support/src/View/Components/ColorMaps/ComponentColorMap.php) | done | — | — |
| [`Maps\ButtonComponentColorMap`](../../../packages/support/src/Colors/Maps/ButtonComponentColorMap.php) | [`ColorMaps/ButtonComponentColorMap.php`](../../../vendor/filament/support/src/View/Components/ColorMaps/ButtonComponentColorMap.php) | done | — | [semantics](#the-filled-button-picks-a-background-first-and-a-text-shade-after) |
| [`Maps\IconButtonComponentColorMap`](../../../packages/support/src/Colors/Maps/IconButtonComponentColorMap.php) | [`ColorMaps/IconButtonComponentColorMap.php`](../../../vendor/filament/support/src/View/Components/ColorMaps/IconButtonComponentColorMap.php) | done | — | [diverges](#the-icon-button-map-steps-to-the-neighbouring-shade-not-by-100) |
| [`BadgeComponent`](../../../packages/support/src/Colors/Components/BadgeComponent.php) | [`View/Components/BadgeComponent.php`](../../../vendor/filament/support/src/View/Components/BadgeComponent.php) | done | — | — |
| [`ButtonComponent`](../../../packages/support/src/Colors/Components/ButtonComponent.php) | [`View/Components/ButtonComponent.php`](../../../vendor/filament/support/src/View/Components/ButtonComponent.php) | done | — | [diverges](#the-outlined-button-is-a-subclass-not-a-constructor-flag) |
| [`OutlinedButtonComponent`](../../../packages/support/src/Colors/Components/OutlinedButtonComponent.php) | [`ButtonComponent::make(isOutlined: true)`](../../../vendor/filament/support/src/View/Components/ButtonComponent.php) | done | — | [diverges](#the-outlined-button-is-a-subclass-not-a-constructor-flag) |
| [`IconButtonComponent`](../../../packages/support/src/Colors/Components/IconButtonComponent.php) | [`View/Components/IconButtonComponent.php`](../../../vendor/filament/support/src/View/Components/IconButtonComponent.php) | done | — | — |
| [`LinkComponent`](../../../packages/support/src/Colors/Components/LinkComponent.php) | [`View/Components/LinkComponent.php`](../../../vendor/filament/support/src/View/Components/LinkComponent.php) | done | — | — |
| [`ToggleComponent`](../../../packages/support/src/Colors/Components/ToggleComponent.php) | [`View/Components/ToggleComponent.php`](../../../vendor/filament/support/src/View/Components/ToggleComponent.php) | done | — | — |
| [`DropdownComponent\ItemComponent`](../../../packages/support/src/Colors/Components/DropdownComponent/ItemComponent.php) | [`DropdownComponent/ItemComponent.php`](../../../vendor/filament/support/src/View/Components/DropdownComponent/ItemComponent.php) | done | — | — |
| [`ModalComponent\IconComponent`](../../../packages/support/src/Colors/Components/ModalComponent/IconComponent.php) | [`ModalComponent/IconComponent.php`](../../../vendor/filament/support/src/View/Components/ModalComponent/IconComponent.php) | done | — | [semantics](#an-empty-map-is-a-real-answer-not-a-missing-one) |
| [`SectionComponent\IconComponent`](../../../packages/support/src/Colors/Components/SectionComponent/IconComponent.php) | [`SectionComponent/IconComponent.php`](../../../vendor/filament/support/src/View/Components/SectionComponent/IconComponent.php) | done | — | [semantics](#an-empty-map-is-a-real-answer-not-a-missing-one) |
| `DropdownComponent\HeaderComponent` | [`DropdownComponent/HeaderComponent.php`](../../../vendor/filament/support/src/View/Components/DropdownComponent/HeaderComponent.php) | blocked | the dropdown header, unbuilt — [dropdown.md](dropdown.md) | [detail](#four-policies-wait-on-the-elements-they-would-colour) |
| `DropdownComponent\ItemComponent\IconComponent` | [`DropdownComponent/ItemComponent/IconComponent.php`](../../../vendor/filament/support/src/View/Components/DropdownComponent/ItemComponent/IconComponent.php) | blocked | the item's `iconColor` row — [dropdown-item.md](dropdown-item.md#iconcolor) | [detail](#four-policies-wait-on-the-elements-they-would-colour) |
| `CalloutComponent` + `CalloutComponent\IconComponent` | [`CalloutComponent.php`](../../../vendor/filament/support/src/View/Components/CalloutComponent.php), [`CalloutComponent/IconComponent.php`](../../../vendor/filament/support/src/View/Components/CalloutComponent/IconComponent.php) | blocked | `Callout`, unbuilt in both support and schemas | [detail](#four-policies-wait-on-the-elements-they-would-colour) |
| `InputComponent\WrapperComponent\IconComponent` | [`InputComponent/WrapperComponent/IconComponent.php`](../../../vendor/filament/support/src/View/Components/InputComponent/WrapperComponent/IconComponent.php) | blocked | input affixes, unbuilt in forms | [detail](#four-policies-wait-on-the-elements-they-would-colour) |

Two more policies exist one package up and are tracked there, because the tier is
per package: schemas'
[`TextComponent` and `IconComponent`](../schemas/README.md#supporting-surface).
That split is not cosmetic — the CI split publishes each package standalone, so
schemas' templates may not reach a policy class only support declares.

## Details

Specifics for rows that are not finished — what is already in, what is left, and
what the next step is. Delete a subsection when its row flips to **done**; the
table row and any divergence it earned stay behind.

### Four policies wait on the elements they would colour

`DropdownComponent\HeaderComponent`, `DropdownComponent\ItemComponent\IconComponent`,
the two `CalloutComponent` policies and
`InputComponent\WrapperComponent\IconComponent` are all **blocked**, not skipped:
each is wanted at parity and each is a handful of lines, but none has an element
to paint yet. Porting one now would register a service nothing resolves and add
a row to the contrast regression suite for markup that does not exist.

Two of the four are near: the dropdown item's `iconColor` prop is already a
tracked **todo** row in [`dropdown-item.md`](dropdown-item.md#iconcolor), and
its icon policy is an empty map, so it lands in the same change as the prop. The
callout pair waits on a `Callout` component in both support and schemas, and the
input-wrapper icon on forms' affix surface.

## Semantics

Faithful ports that a reader re-deriving the behaviour from the source would
plausibly get wrong. Permanent, and true once the row is **done**.

### An empty map is a real answer, not a missing one

Three shipped policies return `[]` from `getColorMap()` — both `IconComponent`s
here and schemas' — and that is the whole implementation, not a stub. It means
*"swap the palette for me; I pick the shades in CSS."*

It works because `ColorManager::getComponentClasses()` builds the two base
classes (`sy-color`, `sy-color-<name>`) **before** it consults the map, so an
empty map still puts the marker and the swap class on the element, and the
component's own stylesheet does the rest —
[`icon.css`](../../../packages/schemas/assets/src/styles/components/icon.css)
writes `&.sy-color { @apply text-color-500 dark:text-color-400; }`. An element
whose readable shade genuinely does not vary by palette needs no policy logic;
what it needs is the palette pointed at it, which the base classes do.

Reading `return []` as unfinished and "fixing" it by resolving a text slot would
override the stylesheet's own choice with a contrast search the element does not
want.

### The filled button picks a background first and a text shade after

Every other policy resolves each slot independently against a fixed surface, so
its slots can be checked one at a time. `ButtonComponentColorMap` cannot: it
first computes a best text shade for *every* background shade in the palette,
then uses whether those text shades come out light or dark to choose which
background candidate to use, then reads the text shades back off the chosen
backgrounds. Background and text are one decision.

Two consequences. The `(bg, hover, alternateHover)` candidate list is an ordered
preference, not a set of rules — the **last** candidate is the guaranteed
fallback for a palette where nothing produces light text, which is how a pale
palette like amber ends up painting `bg-400` with `text-900` instead of white on
`bg-600`. And a text shade of **0** is not a palette shade at all: it means
white, emitted as `oklch(1 0 0)` by `getComponentCustomStyles()` and carried by
the `sy-text-color-0` family of utilities, which is why the text utilities have
twelve steps where the background ones have eleven.

This is also why the regression suite tests the button separately from the other
policies: its slots are checked as four `(background, text)` **pairs** — resting,
hover, dark, dark hover — in
[`PolicyContrastTest`](../../../packages/support/tests/Unit/Colors/Components/PolicyContrastTest.php),
rather than slot by slot.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold.

### The tier is filed under `Colors\`, not under `Twig\Components\`

Filament files these under `View\Components\`, and the repo's standing namespace
translation is `View\` → `Twig\` ([renames.md](../renames.md#namespace-segments)).
Applying it here would land them in `Sylament\Support\Twig\Components\`, which
already means something else: a **class-backed renderable** Twig component, the
shape actions' `Modals` has. A colour policy is never rendered and has no
template.

So they are filed by capability at `src/` level instead — `Colors\Components\`,
`Colors\Maps\`, `Colors\Contracts\` — the same rule that put `Evaluation\Invoker`
and `Configuration\ComponentManager` where they are. Recorded in
[renames.md](../renames.md#namespace-segments) so a migrating user grepping
`View\Components\BadgeComponent` finds the destination.

The class names themselves are Filament-verbatim, including the nesting
(`DropdownComponent\ItemComponent`), so only the leading segments move.

### The outlined button is a subclass, not a constructor flag

Filament expresses the variant as constructor state —
`ButtonComponent::make(isOutlined: true)`, with a static per-flag instance cache
— and its Blade `->color()` macro accepts an *instance*, so the view can build
one inline.

Twig cannot call a static factory, and `colorAttributes()` takes a class-string.
Sylament therefore ships
[`OutlinedButtonComponent extends ButtonComponent`](../../../packages/support/src/Colors/Components/OutlinedButtonComponent.php),
overriding `getColorMap()` with the branch Filament puts behind the flag, and the
template picks between two class-strings on its `outlined` prop. The two maps are
genuinely different questions — a filled button paints text *on* the palette, an
outlined one paints text *in* the palette over the page surface — so the subclass
is not a wrapper around a conditional.

Two things fall out of it, both good. The policy becomes stateless, which is what
lets `ColorManager` key its cache on the class name rather than a
[serialisation](color-manager.md#the-policy-cache-key-is-the-class-name-not-a-serialisation).
And Filament's `$cachedInstances` static goes away — the container already
supplies one instance per class.

The cost is that the `outlined` flag has to reach support's `Button` template
from actions, which is where the variant is chosen. `Action/Button.html.twig` and
`ActionGroup/ButtonTrigger.html.twig` used to compose the `sy-outlined` class
into their own class list; they now pass `:outlined="…isOutlined"` instead, and
support's `Button` emits both the class and the policy choice from that one prop.
Keeping the class on the actions side would have let the two drift — outlined
styling with the filled map, which nothing errors on.

### The icon-button map steps to the neighbouring shade, not by 100

Filament's `IconButtonComponentColorMap` computes its hover shades by literal
arithmetic — `$lightShade + 100`, `$darkShade - 100`. That assumes every palette
is a 100-spaced grid with no ends, and it is wrong twice over: the grid's first
step is 50 apart (50 → 100), and a search that lands on 950 or 50 walks straight
off the end. Either produces a shade number no `sy-text-color-<shade>` rule
exists for, which sets no variable and paints nothing, silently.

Sylament replaces the arithmetic with `darkerShade()` / `lighterShade()`, which
walk the palette's own sorted keys to the next one and return the input shade
unchanged when it is already at the end. On the eleven-step grid this agrees with
Filament everywhere except at the 50/100 boundary and at the two ends, which is
precisely where Filament is broken.

The guarantee this buys is checked directly:
[`PolicyShadeGridTest`](../../../packages/support/tests/Unit/Colors/Components/PolicyShadeGridTest.php)
drives all seven slot-resolving policies with adversarial palettes — uniformly
dark, pale but for the darkest shade, dark but for the lightest, and each shipped
palette — and asserts that every class emitted has a matching rule in
`utilities.css`. A second case goes further and pins the emitted classes against
the slot variables each component's stylesheet actually reads, which is what
catches a policy resolving a slot nothing paints with.

### A policy is a public container service, resolved by class-string

Filament resolves a class-string policy with `app($component)`. Sylament's
`ColorManager` holds the container and calls `get()`, throwing a `LogicException`
naming the class if what comes back does not implement `HasColor`.

That forces one wiring detail worth stating, because it is the single exception
to the repo's private-by-default service rule: both policy globs in
`SylamentSupportBundle::loadExtension()` (and the one in
`SylamentSchemasBundle`) are `->public()`. A private service is not reachable
through `Container::get()` at all, and the call site has only a class-string —
there is no tag or locator to route it through, because the class-string comes
from a Twig template, not from a compiled dependency.

Policies are autowired but **not** autoconfigured and carry no
`sylament.component` tag: they are not `Component` subclasses and never go
through `make()`.
