# Panels — port progress

Ports [`filament/filament`](../../../vendor/filament/filament) into
[`packages/panels`](../../../packages/panels) — Filament ships its panel builder as
the `filament/filament` package. The admin shell: panel config, routing, auth,
resources, pages, navigation.

**Status:** todo — see the [status vocabulary](../README.md#statuses) and
[what goes where](../README.md#one-row-per-file-one-file-per-thing).

The tables below are structure only; nothing has been audited yet.
[`support/component.md`](../support/component.md) is the shape each detail file
follows.

## Carried decisions

Settled while porting other packages, recorded here because they constrain how
this one gets built and would otherwise be re-litigated when the panel epics
start.

### `Resource` is a service, not a static class-string registry

Filament's `Resource` is entirely static — `OrderResource::form($schema)`,
`::table($table)`, `::getUrl()` — which works because Laravel's `app()` global
lets a static method reach any service. Symfony has no such global, so a static
method cannot reach a dependency at all.

The consequence is not cosmetic. If `Resource` stays static, its `form()` and
`table()` cannot hold a dependency, so neither can the `OrderForm`/`OrdersTable`
aggregator classes they delegate to, and the **only** remaining place a
dependency can live is a per-field or per-column leaf class. That would force
every user to split everything, which is the opposite of the intent: users who
want one class per form should be able to have one.

So `Resource` is registered as a service and collected by tag — the shape
`AsController`/`AsEventListener` use — and `form()`/`table()`/`infolist()`
become instance methods.

**What survives unchanged.** The user-facing API stays a class-string:
`protected static string $resource = OrderResource::class;` on pages, and
`OrderResource::class` wherever a resource is named. Pages resolve it through a
`ServiceLocator`, exactly as `$this->createForm(OrderType::class)` resolves a
form type through Symfony Form's `FormRegistry`. That precedent is the reason
this reads as Symfony idiom rather than divergence for its own sake.

**What it costs.** `static::getResource()::form($schema)` and its siblings
become a locator lookup — 66 call sites across 10 files in
`vendor/filament/filament/src`, all framework code, none of it user code.
`getUrl()` and the `can*()` authorization statics cannot stay static either
(they need `UrlGeneratorInterface` and an authorization checker); expect
`getUrl()` to gain a Twig function and `can*()` to delegate to voters.

**Decide before writing any of it.** Whether a resource is identified by
class-string (recommended — preserves Filament's API and inherits the
`FormRegistry` precedent) or by name/slug. Pages, routes, and navigation all
hang off that choice, and it is expensive to reverse once they exist.

**Why it is safe to defer until now.** Nothing below `Resource` depends on it:
component classes get their dependencies from the container the moment they are
registered, whether a static or an instance aggregator created them.

## Components

The panel surface —
[`src/Panel.php`](../../../vendor/filament/filament/src/Panel.php),
[`src/Resources`](../../../vendor/filament/filament/src/Resources),
[`src/Pages`](../../../vendor/filament/filament/src/Pages),
[`src/Navigation`](../../../vendor/filament/filament/src/Navigation),
[`src/Auth`](../../../vendor/filament/filament/src/Auth),
[`src/Clusters`](../../../vendor/filament/filament/src/Clusters),
[`src/GlobalSearch`](../../../vendor/filament/filament/src/GlobalSearch),
[`src/Widgets`](../../../vendor/filament/filament/src/Widgets).

| Component | Ports | Status | Detail |
| --- | --- | --- | --- |
| _not audited yet_ | | | |

## Features

Cross-cutting concerns and traits — this package has no top-level `Concerns/`;
they sit per subsystem:
[`src/Panel/Concerns`](../../../vendor/filament/filament/src/Panel/Concerns),
[`src/Pages/Concerns`](../../../vendor/filament/filament/src/Pages/Concerns),
[`src/Resources/Concerns`](../../../vendor/filament/filament/src/Resources/Concerns),
[`src/Navigation/Concerns`](../../../vendor/filament/filament/src/Navigation/Concerns).

| Feature | Ports | Status | Detail |
| --- | --- | --- | --- |
| _not audited yet_ | | | |

## Supporting surface

Enums, contracts, HTTP layer, events, templates —
[`src/Enums`](../../../vendor/filament/filament/src/Enums),
[`src/Contracts`](../../../vendor/filament/filament/src/Contracts),
[`src/Http`](../../../vendor/filament/filament/src/Http),
[`src/Events`](../../../vendor/filament/filament/src/Events),
[`src/View`](../../../vendor/filament/filament/src/View).

| Item | Ports | Status | Detail |
| --- | --- | --- | --- |
| _not audited yet_ | | | |
