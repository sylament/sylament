---
paths:
  - "packages/*/src/**"
  - "packages/*/templates/**"
  - "packages/*/assets/src/**"
  - "packages/*/translations/**"
description: What each shipped surface promises extenders, how a new name is marked, and how a promised name is deprecated.
---
# Stability rules

- **Know the tiers:** Stable, Evolving, Internal and Experimental, defined in `docs/internals/stability.md`; during 0.x every surface is Experimental, so a rename needs no alias yet, but every name below is chosen as a 1.0 promise.
- **Pick every promised name as one extenders will write,** because it outlives the change that adds it: class and public or protected member names, closure-injection names per evaluation context, config keys, DI tags, documented service ids, route names, URL query parameters, `<twig:>` tags and their `{% props %}`, every `{% block %}`, partial paths, the context variable names a template receives, global Twig names, `sy-` classes of a done component, `--sy-` tokens, the colour contract, layer names, controller identifiers, Contract items, `data-sylament-action-*`, `#[LiveAction]` names, colour palette names, stylesheet entry paths, render-hook names, catalogue domains and icon aliases.
- **Record a Sylament-only, dropped or re-specified `sy-` hook in its component's trail entry,** because consumers restyle by selecting it from unlayered CSS.
- **Mark what an application must not rely on with `@internal`,** adding the reason after it when the name does not say it, because a PHP class or member without it is covered by the promise.
- **Mark every new Sylament-own machinery class `@internal` when you add it** (compiler passes, subscribers, resolvers, Twig runtime classes), because a class with no Filament counterpart is public otherwise.
- **Keep Filament's visibility and openness:** never make a member private, and never make a class `final`, where Filament leaves it protected or open, because Filament's extensibility is its protected surface; the debug class loader already warns whoever overrides an `@internal` member.
- **Never add a constructor to a class an application extends** (`Component` and its subclasses, `Page`, `Resource`, `PanelProvider`), because the application's subclass takes its own services through its constructor; take a service through a `#[Required]` setter (`php.md`, "Services reach a component through a `#[Required]` setter").
- **Never add a parameter to a listed hook** (`setUp()`, `configureUsing()`, a documented page hook) **or a method or parameter to an interface applications implement by hand** (`HasLabel`, `HasColor`, `HasIcon`, `HasDescription`, `StateCast`, `GlobalSearchProvider`, the colour-policy `HasColor`); add a new hook, getter or interface beside it, because an implementer fails to load.
- **Never change the type of a protected property an application redeclares** (a resource's or page's statics such as `$recordClass`, `$navigationIcon` and `$slug`; `ViewComponent`'s `$template`), because PHP property types are invariant and every application resource redeclares them.
- **Name a new global Twig function, filter or test `sy_<snake_case>`,** because an unprefixed name collides with a host application's own.
- **Name a new component-local CSS custom property `--_sy-<name>` and a new token applications set or read `--sy-<name>`,** because only the `--sy-` set is promised.
- **List a controller's public values, targets, actions and events in its trail file's `## Contract` section,** because anything not listed is Internal.
- **Deprecate with both halves:**
  - `@deprecated since sylament/<pkg> <version>, use <new> instead` in the docblock;
  - `trigger_deprecation('sylament/<pkg>', '<version>', …)` as the body's first statement;
  - `<version>` is the release that first ships the deprecation, which is always a tagged one, since before the first tag a replaced name is simply removed;
  - the package's `composer.json` requires `symfony/deprecation-contracts`;
  - a test that calls the member expects the deprecation with `expectUserDeprecationMessage()`.

  Without the runtime half, an extender never sees the notice.
- **Never port a member Filament marks `@deprecated`;** port the replacement it names, and when Filament deprecates a member Sylament already ships, replace it before the first tag and deprecate it through the channel above after it (`docs/progress/README.md`, Statuses), because a migrating application should meet Filament's current API only; `FilamentDeprecationsTest` enforces it.
- **From the first tag on, change a promised name only by adding the new name beside the old, and a method's signature only by an optional last parameter on a public method that is no listed hook nor declared by an interface applications implement by hand,** with the artefact for its kind in `docs/internals/stability.md`, "Mechanics", because a caret constraint skips no minor.

Background: `docs/internals/stability.md`.
