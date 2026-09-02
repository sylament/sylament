# NotificationsIconAlias (notifications)

[`Sylament\Notifications\Twig\NotificationsIconAlias`](../../../packages/notifications/src/Twig/NotificationsIconAlias.php)
— the overridable icon slots the notifications package exposes, ported from
Filament's
[`View\NotificationsIconAlias`](../../../vendor/filament/notifications/src/View/NotificationsIconAlias.php)
(the `View\` → `Twig\` segment is the systematic Blade → Twig translation, recorded
in [renames.md](../renames.md#namespace-segments)).

An alias is a name an application can register an icon override for under
[`sylament.icons`](../support/icon-manager.md); it does nothing until a template
passes it to `generateIconHtml()`, which is why the constant set grows with the
surface that renders the icons rather than up front.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`notifications/src/View/NotificationsIconAlias.php`](../../../vendor/filament/notifications/src/View/NotificationsIconAlias.php)
  — the six alias constants and their values

## Surface

_Blocked by_ names what has to land first. _Detail_ carries either **detail** →
the write-up in [Details](#details), deleted once the row is **done**, or
**diverges** → the permanent note in [Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`NOTIFICATION_CLOSE_BUTTON`](../../../packages/notifications/src/Twig/NotificationsIconAlias.php) | [`NotificationsIconAlias`](../../../vendor/filament/notifications/src/View/NotificationsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`NOTIFICATION_DANGER`](../../../packages/notifications/src/Twig/NotificationsIconAlias.php) | [`NotificationsIconAlias`](../../../vendor/filament/notifications/src/View/NotificationsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`NOTIFICATION_INFO`](../../../packages/notifications/src/Twig/NotificationsIconAlias.php) | [`NotificationsIconAlias`](../../../vendor/filament/notifications/src/View/NotificationsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`NOTIFICATION_SUCCESS`](../../../packages/notifications/src/Twig/NotificationsIconAlias.php) | [`NotificationsIconAlias`](../../../vendor/filament/notifications/src/View/NotificationsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`NOTIFICATION_WARNING`](../../../packages/notifications/src/Twig/NotificationsIconAlias.php) | [`NotificationsIconAlias`](../../../vendor/filament/notifications/src/View/NotificationsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| `DATABASE_MODAL_EMPTY_STATE` | [`NotificationsIconAlias`](../../../vendor/filament/notifications/src/View/NotificationsIconAlias.php) | blocked | the database-notifications panel | [detail](#the-sixth-constant-waits-for-the-panel-that-renders-it) |

All five shipped constants are read by
[the toast template](../../../packages/notifications/templates/partials/Notification/index.html.twig)
— the four status ones through the `statusAliases` map handed to
`generateIconHtml()`, and the close-button one on the `IconButton` tag — so every
constant that ships is overridable end to end. Two of those paths are exercised by
[`NotificationRenderTest`](../../../packages/notifications/tests/Integration/NotificationRenderTest.php):
the status fallback renders the aliased icon's SVG, and an explicit `icon()` wins
over it, which is the branch that passes `null` as the alias so a user-set icon is
never re-aliased.

## Details

Specifics for rows that are not finished — what is already in, what is left, and
what the next step is. Delete a subsection when its row flips to **done**.

### The sixth constant waits for the panel that renders it

`DATABASE_MODAL_EMPTY_STATE = 'notifications::database.modal.empty-state'` names
the icon of the empty state inside the database-notifications modal — the one
Filament constant with no toast-side consumer. Declaring it now would ship a name
an application could register an override for that nothing would ever read.

**Next step:** add it in the change that ports the panel's empty state, with the
de-prefixed value the five below already use (`database.modal.empty-state`). This
is the growth order
[`SupportIconAlias`](../support/support-icon-alias.md#pagination-rtl-aliases)
and [`TablesIconAlias`](../tables/tables-bundle.md#the-alias-set-is-a-subset-of-filaments-23)
already follow.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold.

### The alias values drop Filament's package prefix

Filament's values are namespaced by package —
`notifications::notification.close-button`, `notifications::notification.danger`,
and so on. Sylament's are the bare `<component>.<part>` form:
`notification.close-button`, `notification.danger`, `notification.info`,
`notification.success`, `notification.warning`.

Filament is inconsistent about this — `SupportIconAlias`'s values are bare
(`modal.close-button`, `section.collapse-button`) while every other package
namespaces its own — and Sylament follows the bare form throughout, so an
application registering overrides under
[`sylament.icons`](../support/icon-manager.md) reads one scheme rather than two.
The **constant** names are untouched, which is how both codebases write them at
every call site, so the difference is invisible to anyone who does not hardcode the
string. This is the same decision tables' `EMPTY_STATE`
[already recorded](../tables/tables-bundle.md#the-empty-state-alias-value-drops-filaments-package-prefix),
and both are in the [rename table](../renames.md#renames).
