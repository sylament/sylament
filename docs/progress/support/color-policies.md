---
description: The per-element contrast policies that pick a palette's shades, the two colour contracts and the three shade resolvers behind them.
ports:
  - vendor/filament/support/src/View/Components/BadgeComponent.php
  - vendor/filament/support/src/View/Components/ButtonComponent.php
  - vendor/filament/support/src/View/Components/CalloutComponent.php
  - vendor/filament/support/src/View/Components/CalloutComponent/IconComponent.php
  - vendor/filament/support/src/View/Components/ColorMaps/ButtonComponentColorMap.php
  - vendor/filament/support/src/View/Components/ColorMaps/ComponentColorMap.php
  - vendor/filament/support/src/View/Components/ColorMaps/IconButtonComponentColorMap.php
  - vendor/filament/support/src/View/Components/Contracts/HasColor.php
  - vendor/filament/support/src/View/Components/Contracts/HasDefaultGrayColor.php
  - vendor/filament/support/src/View/Components/DropdownComponent/HeaderComponent.php
  - vendor/filament/support/src/View/Components/DropdownComponent/ItemComponent.php
  - vendor/filament/support/src/View/Components/DropdownComponent/ItemComponent/IconComponent.php
  - vendor/filament/support/src/View/Components/IconButtonComponent.php
  - vendor/filament/support/src/View/Components/InputComponent/WrapperComponent/IconComponent.php
  - vendor/filament/support/src/View/Components/LinkComponent.php
  - vendor/filament/support/src/View/Components/ModalComponent/IconComponent.php
  - vendor/filament/support/src/View/Components/SectionComponent/IconComponent.php
  - vendor/filament/support/src/View/Components/ToggleComponent.php
owns:
  - packages/support/src/Colors/Components/BadgeComponent.php
  - packages/support/src/Colors/Components/ButtonComponent.php
  - packages/support/src/Colors/Components/DropdownComponent/
  - packages/support/src/Colors/Components/IconButtonComponent.php
  - packages/support/src/Colors/Components/InputComponent/WrapperComponent/IconComponent.php
  - packages/support/src/Colors/Components/LinkComponent.php
  - packages/support/src/Colors/Components/ModalComponent/
  - packages/support/src/Colors/Components/OutlinedButtonComponent.php
  - packages/support/src/Colors/Components/SectionComponent/
  - packages/support/src/Colors/Components/ToggleComponent.php
  - packages/support/src/Colors/Contracts/
  - packages/support/src/Colors/Maps/
---
# Color policies (support)

`Sylament\Support\Colors\Components\` holds the per-element contrast policies, beside the two contracts in `Colors\Contracts\` and the three shade resolvers in `Colors\Maps\`.
A policy is not renderable and holds no state: it answers one question, `getColorMap(array $color): array<string, int>` (*given this palette, which shade does each slot of this element paint with?*), and expresses the answer as intent (`the text must be readable against this palette's shade 50`) rather than as a shade number.
Palettes do not share a lightness curve, so no fixed shade such as `text-color-600` serves them all; the policy resolves the shade per palette instead.

The registry that calls into these is [`color-manager.md`](color-manager.md); the palette maths they measure with is [`color.md`](color.md).

Filament's `View/Components/ModalComponent.php` sits in the same directory and is **not** a colour policy: it is the static global-defaults holder for modals, tracked in [`modal.md`](modal.md).
The rows below track the **policy objects**; how each rendered element *reaches* its policy (the prop, the template call, the CSS branch) stays in that element's own file ([`badge.md`](badge.md), [`button.md`](button.md), [`link.md`](link.md), [`icon-button.md`](icon-button.md), [`dropdown-item.md`](dropdown-item.md), [`modal.md`](modal.md), [`section.md`](section.md), [`input-wrapper.md`](input-wrapper.md), [`forms/toggle.md`](../forms/toggle.md)), so no element's colour status is recorded twice.
Policies a package above support declares are tracked in that package, such as schemas' [`TextComponent`](../schemas/text.md) and [`IconComponent`](../schemas/icon.md): the split publishes each package standalone, so a package's templates may not reach a policy class only another package declares.

## Surface

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
| [`InputComponent\WrapperComponent\IconComponent`](../../../packages/support/src/Colors/Components/InputComponent/WrapperComponent/IconComponent.php) | [`InputComponent/WrapperComponent/IconComponent.php`](../../../vendor/filament/support/src/View/Components/InputComponent/WrapperComponent/IconComponent.php) | done | — | [semantics](#an-empty-map-is-a-real-answer-not-a-missing-one) |
| `DropdownComponent\HeaderComponent` | [`DropdownComponent/HeaderComponent.php`](../../../vendor/filament/support/src/View/Components/DropdownComponent/HeaderComponent.php) | blocked | the dropdown header | [detail](#three-policies-wait-on-the-elements-they-would-colour) |
| `DropdownComponent\ItemComponent\IconComponent` | [`DropdownComponent/ItemComponent/IconComponent.php`](../../../vendor/filament/support/src/View/Components/DropdownComponent/ItemComponent/IconComponent.php) | blocked | the dropdown item's `iconColor` prop | [detail](#three-policies-wait-on-the-elements-they-would-colour) |
| `CalloutComponent` + `CalloutComponent\IconComponent` | [`CalloutComponent.php`](../../../vendor/filament/support/src/View/Components/CalloutComponent.php) and its `IconComponent` | blocked | a `Callout` component | [detail](#three-policies-wait-on-the-elements-they-would-colour) |

## Details

### Three policies wait on the elements they would colour

`DropdownComponent\HeaderComponent`, `DropdownComponent\ItemComponent\IconComponent` and the two `CalloutComponent` policies are **blocked**, not skipped: each is wanted at parity and each is a handful of lines, but none has an element to paint.
Porting one before its element would register a service nothing resolves and add a row to the contrast regression suite for markup that does not exist.

The header policy waits on the dropdown header, a todo row in [`dropdown.md`](dropdown.md#surface).
The item icon policy waits on the dropdown item's `iconColor` prop, a todo row in [`dropdown-item.md`](dropdown-item.md#there-is-no-iconcolor-prop-so-the-icon-inherits-the-rows-colour); the policy is an empty map, so it lands in the same change as the prop.
The callout pair waits on a `Callout` component, which neither support nor schemas has.

## Semantics

### An empty map is a real answer, not a missing one

The three `IconComponent` policies here return `[]` from `getColorMap()`, as several policies in schemas, tables and notifications do, and that is the whole implementation, not a stub.
It means *"swap the palette for me; I pick the shades in CSS."*

It works because `ColorManager::getComponentClasses()` builds the two base classes (`sy-color`, `sy-color-<name>`) **before** it consults the map, so an empty map still puts the marker and the swap class on the element, and the component's own stylesheet does the rest: schemas' [`icon.css`](../../../packages/schemas/assets/src/styles/components/icon.css) writes `&.sy-color { @apply text-color-500 dark:text-color-400; }`.
An element whose readable shade does not vary by palette needs no policy logic; what it needs is the palette pointed at it, which the base classes do.

Reading `return []` as unfinished and "fixing" it by resolving a text slot would override the stylesheet's own choice with a contrast search the element does not want.

### The filled button picks a background first and a text shade after

Every other policy resolves each slot independently against a fixed surface, so its slots can be checked one at a time.
`ButtonComponentColorMap` cannot: it first computes a best text shade for *every* background shade in the palette, then uses whether those text shades come out light or dark to choose which background candidate to use, then reads the text shades back off the chosen backgrounds.
Background and text are one decision.

Two consequences.
The `(bg, hover, alternateHover)` candidate list is an ordered preference, not a set of rules: the **last** candidate is the guaranteed fallback for a palette where nothing produces light text, which is how a pale palette like amber ends up painting `bg-400` with `text-900` instead of white on `bg-600`.
And a text shade of **0** is not a palette shade at all: it means white, emitted as `oklch(1 0 0)` by `getComponentCustomStyles()` and carried by the `sy-text-color-0` family of utilities, which is why the text utilities have one more step than the background ones.

This is also why the regression suite tests the button separately from the other policies: its slots are checked as four `(background, text)` **pairs** (resting, hover, dark, dark hover) in [`PolicyContrastTest`](../../../packages/support/tests/Unit/Colors/Components/PolicyContrastTest.php), rather than slot by slot.

## Divergences

### The tier is filed under `Colors\`, not under `Twig\Components\`

Filament files these under `View\Components\`, and the repo's standing namespace translation is `View\` → `Twig\` ([translations.md](../translations.md#namespace-segments)).
Applying it here would land them in `Sylament\Support\Twig\Components\`, which already means something else: a **class-backed renderable** Twig component, the shape actions' `Modals` has.
A colour policy is never rendered and has no template.

So they are filed by capability at `src/` level instead (`Colors\Components\`, `Colors\Maps\`, `Colors\Contracts\`), the same rule that put `Evaluation\Invoker` and `Configuration\ComponentManager` where they are.
Recorded in [translations.md](../translations.md#namespace-segments) so a migrating user grepping `View\Components\BadgeComponent` finds the destination.

The class names themselves are Filament-verbatim, including the nesting (`DropdownComponent\ItemComponent`), so only the leading segments move.

### The outlined button is a subclass, not a constructor flag

Filament expresses the variant as constructor state (`ButtonComponent::make(isOutlined: true)`, with a static per-flag instance cache), and its Blade `->color()` macro accepts an *instance*, so the view can build one inline.

Twig cannot call a static factory, and `colorAttributes()` takes a class-string.
Sylament therefore ships [`OutlinedButtonComponent extends ButtonComponent`](../../../packages/support/src/Colors/Components/OutlinedButtonComponent.php), overriding `getColorMap()` with the branch Filament puts behind the flag, and the template picks between two class-strings on its `outlined` prop.
The two maps answer different questions (a filled button paints text *on* the palette, an outlined one paints text *in* the palette over the page surface), so the subclass is not a wrapper around a conditional.

Two things fall out of it.
The policy becomes stateless, which is what lets `ColorManager` key its cache on the class name rather than a [serialisation](color-manager.md#the-policy-cache-key-is-the-class-name-not-a-serialisation).
And Filament's `$cachedInstances` static goes away, because the container already supplies one instance per class.

The cost is that the `outlined` flag has to reach support's `Button` template from actions, which is where the variant is chosen.
`Action/Button.html.twig` and `ActionGroup/ButtonTrigger.html.twig` pass `:outlined="…isOutlined"`, and support's `Button` emits both the `sy-outlined` class and the policy choice from that one prop.
Composing the class on the actions side would let the two drift, outlined styling with the filled map, which nothing errors on.

### The icon-button map steps to the neighbouring shade, not by 100

Filament's `IconButtonComponentColorMap` computes its hover shades by literal arithmetic: `$lightShade + 100`, `$darkShade - 100`.
That assumes every palette is a 100-spaced grid with no ends, and it is wrong twice over: the grid's first step is 50 apart (50 → 100), and a search that lands on 950 or 50 walks straight off the end.
Either produces a shade number no `sy-text-color-<shade>` rule exists for, which sets no variable and paints nothing, silently.

Sylament replaces the arithmetic with `darkerShade()` / `lighterShade()`, which walk the palette's own sorted keys to the next one and return the input shade unchanged when it is already at the end.
On the eleven-step grid this agrees with Filament everywhere except at the 50/100 boundary and at the two ends, which is precisely where Filament is broken.

[`PolicyShadeGridTest`](../../../packages/support/tests/Unit/Colors/Components/PolicyShadeGridTest.php) checks the guarantee directly: it drives every slot-resolving policy with adversarial palettes (uniformly dark, pale but for the darkest shade, dark but for the lightest, and each shipped palette) and asserts that every class emitted has a matching rule in `utilities.css`.
A second case pins the emitted classes against the slot variables each component's stylesheet reads, which catches a policy resolving a slot nothing paints with.

### A policy is a public container service, resolved by class-string

Filament resolves a class-string policy with `app($component)`.
Sylament's `ColorManager` holds the container and calls `get()`, throwing a `LogicException` naming the class if what comes back does not implement `HasColor`.

That forces the one exception to the repo's private-by-default service rule: every policy glob is `->public()`, one glob per directory level because a glob matches a single depth, three of them in `SylamentSupportBundle::configureServices()` and the other packages' as [bundles.md](../../internals/bundles.md) lists them.
A private service is not reachable through `Container::get()` at all, and the call site has only a class-string: there is no tag or locator to route it through, because the class-string comes from a Twig template, not from a compiled dependency.

Resolving by service id makes **replacing a policy** a supported application-level extension point: an application redefining the service id gets its own `getColorMap()` everywhere the component renders, because Symfony re-applies application definitions after the extension merge (`MergeExtensionConfigurationPass::process()` captures them up front and calls `addDefinitions()` last).
`Integration/Colors/PolicyOverrideTest` pins both halves (shipped behaviour in the default environment, substituted behaviour in `color_policy`) and fails if the globs stop being `->public()`.
A redefinition replaces the extension's definition wholesale, `->public()` included, so it must declare itself public or `Container::get()` cannot reach it; the `color_policy` environment in the test [`Kernel`](../../../packages/support/tests/Fixtures/Kernel.php) does exactly that.

Policies are autowired but **not** autoconfigured and carry no `sylament.component` tag: they are not `Component` subclasses and never go through `make()`.
