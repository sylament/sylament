# Icon button (support)

The `<twig:SylamentSupport:IconButton>` anonymous component (Filament's
`<x-filament::icon-button>`), the `sy-icon-btn` CSS, and the `Size` /
`IconPosition` enums plus the `HasIconPosition` concern that ship with it
(`IconPosition` is not consumed by the icon-only button itself — its consumers
are the future labeled button, tab, and action components).

Reference:

- [`vendor/filament/support/resources/views/components/icon-button.blade.php`](../../../vendor/filament/support/resources/views/components/icon-button.blade.php)
- [`vendor/filament/support/src/View/Concerns/CanGenerateIconButtonHtml.php`](../../../vendor/filament/support/src/View/Concerns/CanGenerateIconButtonHtml.php)
- [`vendor/filament/support/resources/css/components/icon-button.css`](../../../vendor/filament/support/resources/css/components/icon-button.css)
- [`vendor/filament/support/src/View/Components/IconButtonComponent.php`](../../../vendor/filament/support/src/View/Components/IconButtonComponent.php)
- [`vendor/filament/support/src/Enums/Size.php`](../../../vendor/filament/support/src/Enums/Size.php)
- [`vendor/filament/support/src/Enums/IconPosition.php`](../../../vendor/filament/support/src/Enums/IconPosition.php)
- [`vendor/filament/support/src/Concerns/HasIconPosition.php`](../../../vendor/filament/support/src/Concerns/HasIconPosition.php)

## Missing

- [ ] Tooltips: the `tooltip` prop (`x-tooltip` equivalent, `title`
  suppression, the disabled-with-tooltip branch — `tabindex="0"`,
  href/click-attribute stripping, keeping the element hoverable) and
  re-splitting the CSS `pointer-events-none` behind Filament's
  `:not([x-tooltip])` guard — blocked on
  [stimulus-controllers](../infrastructure.md#stimulus-controllers)
- [ ] `keyBindings` (Filament: `x-mousetrap`) — blocked on
  [stimulus-controllers](../infrastructure.md#stimulus-controllers)
- [ ] Loading indicator: the `loadingIndicator` / `form` props,
  `generate_loading_indicator_html`, and the `wire:loading` / `wire:target`
  machinery — blocked on
  [live-component-state](../infrastructure.md#live-component-state)
- [ ] SPA mode (`spaMode` prop; `generate_href_html`'s `wire:navigate`
  variants) — blocked on
  [live-component-state](../infrastructure.md#live-component-state)
- [ ] `tag: 'form'`: the self-submitting `<form>` wrapper with a CSRF field
  (Laravel `csrf_field()` → Symfony CSRF token) — revisit with the actions
  package that consumes it.
- [ ] Badge: the `badge` / `badgeColor` / `badgeSize` props and the
  `sy-icon-btn-badge-ctn` container CSS — needs the badge component port.
- [ ] Responsive labeled-button collapse: the
  `:has(+ .sy-btn.sy-labeled-from-*)` hide rules — needs the button component
  port.
- [ ] `CanGenerateIconButtonHtml` PHP-side generator — deferred to
  [actions-package](../infrastructure.md#actions-package) (`Action` /
  `ActionGroup` are its only Filament consumers; the Twig template is the
  implementation until then).

## Divergences

- **Twig anonymous component only.** Filament ships two synchronized
  implementations — the Blade view and the `CanGenerateIconButtonHtml` trait;
  we render through the template alone until the actions package needs a PHP
  generator.
- **House `.sy-color` fixed shades replace the computed color map.** Filament
  resolves per-palette WCAG-contrast text shades at runtime
  (`IconButtonComponentColorMap` → `--text` / `--hover-text` vars); our CSS
  bakes the map's standard-palette output (`text-color-600`, hover `500`, dark
  `400`, dark hover `300`) behind the `.sy-color-<name>` palette classes. Array
  (custom-palette) colors degrade to gray — [icons.md](icons.md)'s repo-wide
  gap.
- **No `disabled` attribute on `tag: 'a'`** (invalid HTML — Filament emits
  it); `aria-disabled="true"`, `.sy-disabled`, and the CSS
  `pointer-events-none` carry the disabled state, matching Filament's
  effective no-tooltip anchor behavior.
- **Attribute values are escaped at render** by ux-twig-component's attribute
  bag (Filament merges `escape: false` and hand-escapes only `aria-label`); a
  `Markup` label cannot break out of `aria-label` / `title`.
- **Unknown `iconSize` strings degrade to `Medium`** via the
  `generateIconHtml` Twig function's `tryFrom()` fallback — same behavior as
  [section.md](../schemas/section.md).
- **Enum home is `Sylament\Support\Enum`** (singular) — see
  [icons.md](icons.md).
