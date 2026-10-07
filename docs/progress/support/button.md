---
description: The anonymous `<twig:SylamentSupport:Button>` text-button template and the `button.css` chrome beside it, including the button group and label-button arms.
ports:
  - vendor/filament/support/resources/views/components/button/index.blade.php
  - vendor/filament/support/resources/css/components/button.css
owns:
  - packages/support/assets/src/styles/components/button.css
  - packages/support/templates/components/Button/
---
# Button (support)

[`Button/index.html.twig`](../../../packages/support/templates/components/Button/index.html.twig) is the anonymous `<twig:SylamentSupport:Button>` template, the shared text-button widget.
It has no Sylament PHP class: it ports Filament's Blade `button/index.blade.php`, its runtime colour map is the [`ButtonComponent` policy](color-policies.md), and this file tracks the `button.css` chrome beside it, including the two arms with no template of their own: `.sy-btn-group`, which joins a row of buttons into one control, and the `input:checked + label.sy-btn` arms, which paint a label-shaped button while the input it covers is checked.
Its render tests live in the actions suite ([`ButtonRenderTest`](../../../packages/actions/tests/Integration/Components/ButtonRenderTest.php)), whose kernel registers TwigComponentBundle.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Element tag + `href`/`target`](../../../packages/support/templates/components/Button/index.html.twig) | [`<{{ $tag }}>` + `generate_href_html`](../../../vendor/filament/support/resources/views/components/button/index.blade.php) | done | — | — |
| [`type` attribute](../../../packages/support/templates/components/Button/index.html.twig) | [`'type' => …`](../../../vendor/filament/support/resources/views/components/button/index.blade.php) | done | — | — |
| [`form` / `formId`](../../../packages/support/templates/components/Button/index.html.twig) | [`'form' => $formId`](../../../vendor/filament/support/resources/views/components/button/index.blade.php) | done | — | — |
| [`disabled` + `aria-disabled`](../../../packages/support/templates/components/Button/index.html.twig) | [`'disabled' => …`](../../../vendor/filament/support/resources/views/components/button/index.blade.php) | done | — | — |
| [`size` → `sy-size-*` classes](../../../packages/support/templates/components/Button/index.html.twig) | [`->class(['fi-size-…'])`](../../../vendor/filament/support/resources/views/components/button/index.blade.php) | done | — | — |
| [`color` → `sy-color*` classes + resolved shade classes](../../../packages/support/templates/components/Button/index.html.twig) | [`->color(ButtonComponent::make($outlined), $color)`](../../../vendor/filament/support/src/View/Components/ButtonComponent.php) | done | — | [diverges](#the-outlined-variant-is-selected-by-a-prop-and-by-a-second-policy-class) |
| [`outlined`](../../../packages/support/templates/components/Button/index.html.twig) | [`ButtonComponent::make($outlined)` + `fi-outlined`](../../../vendor/filament/support/resources/views/components/button/index.blade.php) | done | — | [diverges](#the-outlined-variant-is-selected-by-a-prop-and-by-a-second-policy-class) |
| [Leading icon](../../../packages/support/templates/components/Button/index.html.twig) | [`generate_icon_html(...)`](../../../vendor/filament/support/resources/views/components/button/index.blade.php) | in progress | — | [detail](#the-icon-takes-no-size-from-the-buttons-size) |
| [`iconPosition` (before/after)](../../../packages/support/templates/components/Button/index.html.twig) | [`IconPosition::Before` / `::After`](../../../vendor/filament/support/resources/views/components/button/index.blade.php) | in progress | — | [detail](#no-render-test-pins-the-after-icon-position) |
| `badge` / `badgeColor` / `badgeSize` | [`fi-btn-badge-ctn`](../../../vendor/filament/support/resources/views/components/button/index.blade.php) | todo | — | [detail](#the-badge-and-label-props-need-no-client-layer) |
| `labelSrOnly` / `aria-label` | [`'aria-label' => …`](../../../vendor/filament/support/resources/views/components/button/index.blade.php) | todo | — | [detail](#the-badge-and-label-props-need-no-client-layer) |
| `labeledFrom` → icon-button delegation | [`<x-filament::icon-button … />`](../../../vendor/filament/support/resources/views/components/button/index.blade.php) | todo | — | [detail](#the-badge-and-label-props-need-no-client-layer) |
| `tooltip` | [`x-tooltip="…"`](../../../vendor/filament/support/resources/views/components/button/index.blade.php) | todo | — | [detail](#tooltip-key-bindings-and-loading-are-not-wired) |
| `keyBindings` | [`x-mousetrap.global.…`](../../../vendor/filament/support/resources/views/components/button/index.blade.php) | todo | — | [detail](#tooltip-key-bindings-and-loading-are-not-wired) |
| Loading indicator / `wire:target` / form processing | [`generate_loading_indicator_html(...)` + `filamentFormButton`](../../../vendor/filament/support/resources/views/components/button/index.blade.php) | todo | — | [detail](#tooltip-key-bindings-and-loading-are-not-wired) |
| `spaMode` SPA navigation | [`generate_href_html($href, …, $spaMode)`](../../../vendor/filament/support/resources/views/components/button/index.blade.php) | blocked | panels SPA navigation | — |
| [`.sy-btn-group`: the joined row of buttons](../../../packages/support/assets/src/styles/components/button.css) | [`.fi-btn-group`](../../../vendor/filament/support/resources/css/components/button.css) | done | — | [diverges](#the-button-group-arm-ships-without-its-processing-sub-rule) |
| [`input:checked + label.sy-btn`: the selected state of a label button](../../../packages/support/assets/src/styles/components/button.css) | [`input:checked + label&`](../../../vendor/filament/support/resources/css/components/button.css) | done | — | [diverges](#the-label-buttons-paint-their-colour-only-while-checked) |

## Details

### The icon takes no size from the button's size

The template renders the icon through `generateIconHtml(icon, iconAlias, size: iconSize)`, and the render test asserts an `<svg>` is emitted.
Filament defaults `iconSize` from `size` (`xs`/`sm` → `IconSize::Small`, everything else none), while the template passes the `iconSize` prop straight through with no size-derived default.
**Next step:** derive the default in the template and add a case asserting the icon's size class.

### No render test pins the after icon position

The template renders the icon before the label or, for `iconPosition` `after`, after it, and actions' button partial passes `getIconPosition`.
No render test covers the `after` arm.
**Next step:** a `ButtonRenderTest` case that renders `iconPosition: 'after'` and asserts the `<svg>` follows the label.

### The badge and label props need no client layer

`badge` / `badgeColor` / `badgeSize`, `labelSrOnly` / `aria-label` and the `labeledFrom` delegation to [`IconButton`](icon-button.md) are absent from the template's props and body.
They are static markup and need no interactivity layer.

### Tooltip, key bindings and loading are not wired

- **Tooltip.**
  The template has no `tooltip` prop; callers forward a native `title`, the toolkit's tooltip shape, as `actions`' button partial does with `getTooltip`.
- **Key bindings.**
  Filament binds an Alpine mousetrap shortcut; the port has no shared key-binding controller for the button to use.
- **Loading and form processing.**
  The loading indicator, its `wire:target` plumbing and the `filamentFormButton` form-processing behaviour have no counterpart in the template.

## Divergences

### The button-group arm ships without its processing sub-rule

`.sy-btn-group` is a rule-for-rule port of vendor's `.fi-btn-group`: the `grid grid-flow-col`, the shared ring its uncoloured children and its labels give up for it, the first and last corner rounding read through `:nth-child(1 of .sy-btn)` (which steps over the `<input>`s an option set interleaves), the `me-px` seam and its `rtl` and dark variants.
[`ToggleButtons::grouped()`](../forms/toggle-buttons.md#the-grouped-labels-must-stay-direct-children-of-the-group) and the [file upload](../forms/file-upload.md) template render it, and [`ActionGroup`'s button-group trigger](../actions/action-group.md#the-button-group-trigger-is-unported-which-is-why-hasdropdown-stands-in-for-isbuttongroup) needs no CSS of its own when it lands.

One sub-rule is not ported: `&.fi-processing:enabled { cursor-wait opacity-70 }`.
It is the only `fi-processing` rule in vendor's stylesheet, and Alpine sets the class on the button Blade (`x-bind:class="{ 'fi-processing': isProcessing }"`), the form-processing behaviour the loading row above marks todo.
Nothing in the port emits `sy-processing`, so the rule would ship a selector with no element.
It lands with that row, in the same change that first emits the class.

### The label buttons paint their colour only while checked

A `<label class="sy-btn">` over an `<input type="radio">` is the shape [`ToggleButtons`](../forms/toggle-buttons.md#the-selected-state-is-painted-from-the-sibling-input-in-both-variants) renders an option set in, and it is the one caller for which "coloured" is not a constant: the same markup is a chosen option and an unchosen one, and only the sibling input tells them apart.
Filament splits its colour block accordingly, and the port follows: the selector is support's, the tree is the field's.

The arms are vendor's, rule for rule with the prefix rename: a checked coloured label takes the palette fill and `ring-0`, a checked uncoloured one takes `bg-gray-400 text-white` (`gray-600` in dark), an unchecked label of either kind falls through to the plain white-and-ring treatment, the icon inside a checked label follows the label's text colour, and every label gets `cursor-pointer`.
Vendor's `input:focus-visible + &` / `input:checked:focus-visible + &` rings come with them, including their gap: both sit under `.fi-color`, so an uncoloured option set has no focus ring of its own.
That is worth an upstream look rather than a silent local fix, since diverging here would put the keyboard affordance somewhere Filament's users have not seen it.

What diverges is where the unchecked arm comes from.
Filament applies `bg-white … dark:bg-white/5` to every non-outlined button and overrides it for the coloured ones, so an unchecked label needs no rule at all; the ring comes from a separate `&:not(.fi-color), label&`.
This stylesheet merges those two into one `&:not(.sy-color)` branch, so it adds `label&` to that branch's selector list and qualifies the colour rule `:not(label)`, Filament's own qualifier, on the flattened rule instead of inside its nested `&.fi-color` node.
The computed result matches: a label gets the plain treatment until its input is checked, and a non-label button, which every other `.sy-btn` in the toolkit is, is untouched.

[`PolicyShadeGridTest`](../../../packages/support/tests/Unit/Colors/Components/PolicyShadeGridTest.php) locates each policy's coloured rule by its selector text, and `&.sy-color` introduces two rules here, the outlined arm and the checked label arm, so it takes a nested selector path rather than one selector, resolving each step inside the block the step before it opened.

### The outlined variant is selected by a prop, and by a second policy class

The colour map is ported, and the button is the component where it does the most work: `ButtonComponentColorMap` picks a background and a text shade together, so for a pale palette it paints `bg-400` with dark text rather than white on `bg-600` (see the [semantics note](color-policies.md#the-filled-button-picks-a-background-first-and-a-text-shade-after)).

What diverges is how the variant reaches it.
Filament makes the map's owner carry the flag (`ButtonComponent::make($outlined)`, a per-flag static instance cache) and passes the instance to its Blade `->color()` macro.
Twig cannot call a static factory and `colorAttributes()` takes a class-string, so support's `Button` takes an `outlined` prop and picks between [`ButtonComponent`](../../../packages/support/src/Colors/Components/ButtonComponent.php) and [`OutlinedButtonComponent`](../../../packages/support/src/Colors/Components/OutlinedButtonComponent.php), the same branch Filament puts behind the flag, expressed as a subclass ([renames](../renames.md), [why it is a subclass](color-policies.md#the-outlined-button-is-a-subclass-not-a-constructor-flag)).

The template emits the `sy-outlined` class from the same prop, so a caller passes `:outlined` rather than composing the class itself.
A caller that hand-writes `sy-outlined` without passing `:outlined` gets the outlined styling with the filled colour map, visually wrong in a way no test catches, since neither half errors.
