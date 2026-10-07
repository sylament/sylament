---
description: The anonymous Twig component `<twig:SylamentSupport:Icon>` that ports Filament's `<x-filament::icon>`.
ports:
  - vendor/filament/support/resources/views/components/icon.blade.php
  - vendor/filament/support/resources/css/components/icon.css
owns:
  - packages/support/assets/src/styles/components/icon.css
  - packages/support/templates/components/Icon/
---
# Icon (support)

`packages/support/templates/components/Icon/index.html.twig` passes its props straight to the `generateIconHtml()` Twig function, which renders the markup; that function's port is in [helpers.md](helpers.md#generate_icon_html-moved-to-the-twig-extension).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`icon` → the icon by name](../../../packages/support/templates/components/Icon/index.html.twig) | [`'icon'`](../../../vendor/filament/support/resources/views/components/icon.blade.php) | done | — | — |
| [`alias` → a registered override](../../../packages/support/templates/components/Icon/index.html.twig) | [`'alias'`](../../../vendor/filament/support/resources/views/components/icon.blade.php) | done | — | [icon-manager.md](icon-manager.md) |
| [`size` → `sy-size-*`](../../../packages/support/templates/components/Icon/index.html.twig) | [`'size'`](../../../vendor/filament/support/resources/views/components/icon.blade.php) | done | — | — |
| [The caller's attributes, forwarded](../../../packages/support/templates/components/Icon/index.html.twig) | [`generate_icon_html($icon, $alias, $attributes, $size)`](../../../vendor/filament/support/resources/views/components/icon.blade.php) | done | — | — |
