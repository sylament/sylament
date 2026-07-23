# Text (schemas)

Renders a run of text in a `<span>`. Also the coercion target
`Schema::getComponents()` normalizes string children to (see
[schema.md](schema.md)).

Ported: `content`, `size` (+ the support `TextSize` enum), `weight` (+ the
support `FontWeight` enum / `HasWeight` concern), `fontFamily` (+ the support
`FontFamily` enum / `HasFontFamily` concern). The size/weight/font-family CSS
lives in `packages/support/assets/src/styles/components/text.css`.

Reference:

- [`vendor/filament/schemas/src/Components/Text.php`](../../../vendor/filament/schemas/src/Components/Text.php)
- [`vendor/filament/schemas/resources/css/components/text.css`](../../../vendor/filament/schemas/resources/css/components/text.css)

## Missing

Blocked on infrastructure:

- [ ] `color()` (`HasColor`) — the non-badge span resolves its text color through
  `->color(TextComponent::class, …)`; blocked on the `ColorManager` port
  ([support-managers](../infrastructure.md#support-managers)).
- [ ] `badge()` mode — and, with it, the icon (`HasIcon` / `HasIconPosition` /
  `HasIconSize`): Filament renders the icon **only** in the badge branch, so the
  non-badge `Text` shows no icon. The badge markup needs `ColorManager`
  ([support-managers](../infrastructure.md#support-managers)) plus a
  `BadgeComponent` / badge-CSS port.
- [ ] `tooltip()` (`HasTooltip`) — renders an Alpine `x-tooltip`; blocked on
  [stimulus-controllers](../infrastructure.md#stimulus-controllers) (a tooltip
  controller).
- [ ] Copyable (`CanBeCopied` — `copyable()`, `copyableState()`, `copyMessage()`,
  `copyMessageDuration()`) — clipboard JS plus a confirmation tooltip; blocked on
  [stimulus-controllers](../infrastructure.md#stimulus-controllers).
- [ ] `js()` (`JsContent`) — renders an Alpine `x-text="() => eval(…)"` span;
  blocked on [stimulus-controllers](../infrastructure.md#stimulus-controllers).
- [ ] The `$state` evaluation parameter on `getSize()` / `getWeight()` /
  `getFontFamily()` (Filament threads `['state' => $state]` into `evaluate()`) —
  blocked on [live-component-state](../infrastructure.md#live-component-state);
  the support `HasWeight` / `HasFontFamily` concerns drop the parameter until
  then.

## Divergences

- **Content is escaped at render** (`{{ component.getContent }}`) — Filament's
  `e($content)` does the same; a `Markup` value renders raw.
- **`new static()` over `app()`**; two-layer Twig template over Filament v5's
  inlined `toEmbeddedHtml()` (same rationale as [section.md](section.md)).
- **The faithful `size()` union keeps `Size`** (`TextSize | Size | string`) even
  though `Size` only fed the (blocked) badge path — so the public signature does
  not change when badge mode lands. `getSize()` normalizes a string to `TextSize`.
- **Text CSS is flattened.** Filament scopes weights/sizes under
  `:not(.fi-badge)`; with badge mode unported, the `.sy-sc-text` rules carry no
  such guard yet — restore it when badge lands.
