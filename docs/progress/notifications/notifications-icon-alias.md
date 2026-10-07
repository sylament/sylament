---
description: The overridable icon aliases the notifications package exposes, ported from Filament's `View\NotificationsIconAlias`.
ports:
  - vendor/filament/notifications/src/View/NotificationsIconAlias.php
owns:
  - packages/notifications/src/Twig/NotificationsIconAlias.php
---
# NotificationsIconAlias (notifications)

[`Sylament\Notifications\Twig\NotificationsIconAlias`](../../../packages/notifications/src/Twig/NotificationsIconAlias.php) holds the overridable icon slots the notifications package exposes, ported from Filament's [`View\NotificationsIconAlias`](../../../vendor/filament/notifications/src/View/NotificationsIconAlias.php) (the `View\` → `Twig\` segment is the systematic Blade → Twig translation, recorded in [translations.md](../translations.md#namespace-segments)).

An alias is a name an application can register an icon override for under [`sylament.icons`](../support/icon-manager.md); it does nothing until a template passes it to `generateIconHtml()`, which is why the constant set grows with the surface that renders the icons rather than up front.
Every shipped constant is read by [the toast template](../../../packages/notifications/templates/partials/Notification/index.html.twig): the status ones through the `statusAliases` map handed to `generateIconHtml()`, and the close-button one on the `IconButton` tag.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`NOTIFICATION_CLOSE_BUTTON`](../../../packages/notifications/src/Twig/NotificationsIconAlias.php) | [`NotificationsIconAlias`](../../../vendor/filament/notifications/src/View/NotificationsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`NOTIFICATION_DANGER`](../../../packages/notifications/src/Twig/NotificationsIconAlias.php) | [`NotificationsIconAlias`](../../../vendor/filament/notifications/src/View/NotificationsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`NOTIFICATION_INFO`](../../../packages/notifications/src/Twig/NotificationsIconAlias.php) | [`NotificationsIconAlias`](../../../vendor/filament/notifications/src/View/NotificationsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`NOTIFICATION_SUCCESS`](../../../packages/notifications/src/Twig/NotificationsIconAlias.php) | [`NotificationsIconAlias`](../../../vendor/filament/notifications/src/View/NotificationsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`NOTIFICATION_WARNING`](../../../packages/notifications/src/Twig/NotificationsIconAlias.php) | [`NotificationsIconAlias`](../../../vendor/filament/notifications/src/View/NotificationsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| `DATABASE_MODAL_EMPTY_STATE` | [`NotificationsIconAlias`](../../../vendor/filament/notifications/src/View/NotificationsIconAlias.php) | blocked | the database-notifications panel | [detail](#the-sixth-constant-waits-for-the-panel-that-renders-it) |

## Details

### The sixth constant waits for the panel that renders it

`DATABASE_MODAL_EMPTY_STATE = 'notifications::database.modal.empty-state'` names the icon of the empty state inside the database-notifications modal, the one Filament constant with no toast-side consumer.
Declaring it before the panel would ship a name an application could register an override for that nothing ever reads.

**Next step:** add it in the change that ports the panel's empty state, with the de-prefixed value the shipped constants use (`database.modal.empty-state`).
This is the growth order [`SupportIconAlias`](../support/support-icon-alias.md#an-alias-constant-lands-with-the-template-that-reads-it) and [`TablesIconAlias`](../tables/tables-bundle.md#an-alias-lands-with-the-component-that-reads-it) follow.

## Divergences

### The alias values drop Filament's package prefix

Filament's values are namespaced by package: `notifications::notification.close-button`, `notifications::notification.danger`, and so on.
Sylament's are the bare `<component>.<part>` form: `notification.close-button`, `notification.danger`, `notification.info`, `notification.success`, `notification.warning`.
The **constant** names are untouched, so the difference is invisible to anyone who does not hardcode the string.
Why every package drops the prefix is recorded once, in [renames.md](../renames.md#icon-alias-values-drop-filaments-package-prefix).
