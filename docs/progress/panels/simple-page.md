---
description: 'A panel page that renders as a centred card on an empty ground: no sidebar, no navigation, an optional top strip holding the user menu, and a header… (draft)'
ports:
  - vendor/filament/filament/src/Pages/SimplePage.php
  - vendor/filament/filament/src/Pages/Concerns/HasMaxWidth.php
  - vendor/filament/filament/src/Pages/Concerns/HasTopbar.php
  - vendor/filament/filament/src/Panel/Concerns/HasMaxContentWidth.php
  - vendor/filament/support/src/Enums/Width.php
  - vendor/filament/filament/resources/views/components/layout/simple.blade.php
  - vendor/filament/filament/resources/views/components/header/simple.blade.php
  - vendor/filament/filament/resources/views/components/page/simple.blade.php
owns:
  - packages/panels/assets/src/styles/components/layout.css
  - packages/panels/src/Pages/Concerns/HasMaxWidth.php
  - packages/panels/src/Pages/Concerns/HasTopbar.php
  - packages/panels/src/Pages/SimplePage.php
  - packages/panels/src/Panel/Concerns/HasMaxContentWidth.php
  - packages/panels/templates/components/SimpleHeader/
  - packages/panels/templates/components/SimpleLayout/
  - packages/panels/templates/partials/SimplePage/
  - packages/support/src/Enums/Width.php
---
# SimplePage and the width vocabulary (panels)

[`Sylament\Panels\Pages\SimplePage`](../../../packages/panels/src/Pages/SimplePage.php) — a panel page that renders as a **centred card on an empty ground**: no sidebar, no navigation, an optional top strip holding the user menu, and a header of logo, heading and subheading above whatever the page's `content()` returns.
It picks that shell through the [`getLayout()` seam](custom-pages.md#a-third-shell-layout-is-a-document-override-not-a-getlayout-value) and narrows its own width through [`Pages\Concerns\HasMaxWidth`](../../../packages/panels/src/Pages/Concerns/HasMaxWidth.php).

Around it this file also carries the whole **width vocabulary**, because it has no other home: support's [`Width`](../../../packages/support/src/Enums/Width.php) enum, the `sy-width-<token>` ladder both `<main>` elements read, the page-level `$maxContentWidth`, and the two panel knobs [`maxContentWidth()`](../../../packages/panels/src/Panel/Concerns/HasMaxContentWidth.php) and `simplePageMaxContentWidth()`.
The [unsaved-changes guard](unsaved-changes-alert.md) rides the simple page exactly as it rides the ordinary one.

⚠️ **Two traits named `HasMaxWidth` exist, in two object tiers.**
`Sylament\Panels\Pages\Concerns\HasMaxWidth` is this one — composed by `SimplePage` alone, a `Width|string|null $maxWidth` with a getter and **no setter**, because a page assigns the property.
The other, `Sylament\Schemas\Components\Concerns\HasMaxWidth`, is a *component*-tier concern that is still [todo](../schemas/README.md) and carries a fluent `maxWidth()` setter when it lands.
Both are Filament's names in Filament's places, so neither moves — read the trait by its **import**, never by the bare `use HasMaxWidth;` in a class body, which is the standing rule for every name this codebase carries in two tiers.

## Surface

Every point of the simple page and the width vocabulary.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`SimplePage extends Page`](../../../packages/panels/src/Pages/SimplePage.php) | `SimplePage extends BasePage` | done | — | [diverges](#simplepage-extends-page-so-it-is-routable-and-hides-itself-from-navigation) |
| [`SimplePage::LAYOUT`](../../../packages/panels/src/Pages/SimplePage.php) + `$layout` | `protected static string $layout = 'filament-panels::components.layout.simple'` | done | — | [custom-pages.md](custom-pages.md#a-third-shell-layout-is-a-document-override-not-a-getlayout-value) |
| [`$template`](../../../packages/panels/templates/partials/SimplePage/index.html.twig) | `protected string $view = 'filament-panels::pages.simple'` | done | — | [semantics](#the-simple-page-partial-copies-the-guard-preamble-rather-than-extending-the-base) |
| [`hasLogo()`](../../../packages/panels/src/Pages/SimplePage.php) | the same | done | — | — |
| [`getLayoutData()`](../../../packages/panels/src/Pages/SimplePage.php) | the same | done | — | [diverges](#getlayoutdata-answers-two-keys-where-filaments-answers-three) |
| [`$shouldRegisterNavigation = false`](../../../packages/panels/src/Pages/SimplePage.php) | no counterpart — Filament's `SimplePage` is unroutable | done | — | [diverges](#simplepage-extends-page-so-it-is-routable-and-hides-itself-from-navigation) |
| [`Pages\Concerns\HasMaxWidth`](../../../packages/panels/src/Pages/Concerns/HasMaxWidth.php) — `$maxWidth` + `getMaxWidth()` | [`Pages/Concerns/HasMaxWidth.php`](../../../vendor/filament/filament/src/Pages/Concerns/HasMaxWidth.php) | done | — | the component-tier twin stays [todo](../schemas/README.md) |
| [`Pages\Concerns\HasTopbar`](../../../packages/panels/src/Pages/Concerns/HasTopbar.php) — `$hasTopbar` + `hasTopbar()` | [`Pages/Concerns/HasTopbar.php`](../../../vendor/filament/filament/src/Pages/Concerns/HasTopbar.php) | done | — | — |
| [`SimpleLayout`](../../../packages/panels/templates/components/SimpleLayout/index.html.twig) | [`layout/simple.blade.php`](../../../vendor/filament/filament/resources/views/components/layout/simple.blade.php) | done | — | [diverges](#the-simple-layouts-auth-gates-are-absent-and-the-strip-holds-the-ordinary-user-menu) |
| [`SimpleHeader`](../../../packages/panels/templates/components/SimpleHeader/index.html.twig) | [`header/simple.blade.php`](../../../vendor/filament/filament/resources/views/components/header/simple.blade.php) | done | — | — |
| [`Support\Enums\Width`](../../../packages/support/src/Enums/Width.php) — 26 cases | [`support/Enums/Width.php`](../../../vendor/filament/support/src/Enums/Width.php) | done | — | [support README](../support/README.md) |
| [`Page::$maxContentWidth`](../../../packages/panels/src/Pages/Page.php) + `getMaxContentWidth()` | [`BasePage`](../../../vendor/filament/filament/src/Pages/BasePage.php)'s pair | done | — | [semantics](#the-width-resolution-is-one-ladder-in-the-controller) |
| [`Panel\Concerns\HasMaxContentWidth`](../../../packages/panels/src/Panel/Concerns/HasMaxContentWidth.php) — `maxContentWidth()` / `simplePageMaxContentWidth()` and their getters | [`Panel/Concerns/HasMaxContentWidth.php`](../../../vendor/filament/filament/src/Panel/Concerns/HasMaxContentWidth.php) | done | — | [semantics](#the-width-resolution-is-one-ladder-in-the-controller) |
| The `sy-width-<token>` ladder in [`layout.css`](../../../packages/panels/assets/src/styles/components/layout.css) | the `fi-width-*` rules in Filament's `layout/index.blade.php` | done | — | [semantics](#the-ladder-shares-one-class-vocabulary-with-schemas-and-cannot-collide) |
| A raw string emitted verbatim as the consumer's own class | the same escape hatch | done | — | [semantics](#the-width-resolution-is-one-ladder-in-the-controller) |
| `SimpleUserMenu` | [`Livewire/SimpleUserMenu.php`](../../../vendor/filament/filament/src/Livewire/SimpleUserMenu.php) | skipped | — | [diverges](#the-simple-layouts-auth-gates-are-absent-and-the-strip-holds-the-ordinary-user-menu) · [renames.md](../renames.md#renames) |
| The strip's database-notifications trigger | the `@livewire('database-notifications')` arm | todo | database notifications | [diverges](#the-simple-layouts-auth-gates-are-absent-and-the-strip-holds-the-ordinary-user-menu) |
| The `auth()->check()` gates on the strip | the same two | todo | auth | [diverges](#the-simple-layouts-auth-gates-are-absent-and-the-strip-holds-the-ordinary-user-menu) |
| The modal `sy-modal-<token>` width ladder | `Modal`'s `Width` resolution | todo | — | [support/modal.md](../support/modal.md#the-default-width-is-md-not-sm) |
| `Table::filtersFormWidth()`'s `Width` type | `getFiltersFormWidth(): ?Width` | todo | — | [tables/table.md](../tables/table.md#surface) |
| _rest not audited yet_ | — | — | — | — |

Covered by [`Unit/Pages/SimplePageTest`](../../../packages/panels/tests/Unit/Pages/SimplePageTest.php) (the layout it names, the navigation opt-out and the logo, and both arms of `getLayoutData()`), [`Unit/Pages/Concerns/HasMaxWidthTest`](../../../packages/panels/tests/Unit/Pages/Concerns/HasMaxWidthTest.php) and [`HasTopbarTest`](../../../packages/panels/tests/Unit/Pages/Concerns/HasTopbarTest.php), [`Unit/Panel/Concerns/HasMaxContentWidthTest`](../../../packages/panels/tests/Unit/Panel/Concerns/HasMaxContentWidthTest.php) (enum, raw class and closure arms for both knobs), [`Unit/Enums/WidthTest`](../../../packages/support/tests/Unit/Enums/WidthTest.php) (the 26 backing values), [`Integration/Twig/SimpleLayoutRenderTest`](../../../packages/panels/tests/Integration/Twig/SimpleLayoutRenderTest.php) (the card shell, the strip under `hasTopbar`, the width class, and the four `SimpleHeader` arms) and [`Integration/Controllers/PageControllerTest`](../../../packages/panels/tests/Integration/Controllers/PageControllerTest.php) (the panel default width, a page's own width, the raw-string arm asserting **no** `sy-width-` prefix anywhere, the whole `/probe/simple` document, and the refusal of an unknown layout).

## Semantics

### The width resolution is one ladder in the controller

[`PageController`](README.md#routing) resolves exactly one value per request and threads it through the Document into the shell as `maxContentWidthClass`:

1. `getLayoutData()['maxContentWidth']` — which only `SimplePage` fills, from `getMaxWidth() ?? getMaxContentWidth()`;
2. the page's own `getMaxContentWidth()`;
3. the panel's — `getSimplePageMaxContentWidth() ?? Width::Large` for the simple shell, `getMaxContentWidth() ?? Width::SevenExtraLarge` for the ordinary one.

`resolveMaxContentWidthClass()`, a private instance method on the controller, then turns that into a class: a string is first coerced with `Width::tryFrom($width) ?? $width`, and a `Width` becomes `sy-width-<value>` while **anything else is emitted verbatim** — Filament's escape hatch, so a consumer can hand a page `'max-w-[42rem]'` and get their own class on `<main>` with no ladder prefix at all.
Both `Layout` and `SimpleLayout` declare a default prop (`sy-width-7xl` / `sy-width-lg`), so a bare `<twig:SylamentPanels:Layout>` in a showcase or a test still renders a width.

`.sy-pa-main` lost its inline `max-w-7xl` in the same change, which is what makes the class the single source: a page whose width resolves to `none` genuinely gets none.

### The ladder shares one class vocabulary with schemas, and cannot collide

The 26 rules are nested under `.sy-pa-main, .sy-pa-simple-main` in [`layout.css`](../../../packages/panels/assets/src/styles/components/layout.css) and every one is a single `@apply` — all 26 tokens compile under the installed Tailwind 4.3.0, including `@apply container` (which expands to a base rule plus its five breakpoint rules, so a fresh build emits 40 `sy-width-` occurrences in support's compiled sheet rather than 26) and the `max-w-screen-*` family (which resolves to `max-width: var(--breakpoint-*)`).
No rung needed a hand-written declaration, and the lane's `build:css` run is the check that a Tailwind bump has not changed that.

The `sy-width-*` name is deliberately shared with schemas' `sy-width-full`: that rule is compound-scoped under `.sy-sc-actions-ctn` and can never match a `<main>`, and Filament likewise shares one `fi-width-*` vocabulary across six emitters.
Schemas' `ActionsRenderTest` keeps its assertion verbatim as the proof that nothing moved on that side.

### The simple page partial copies the guard preamble rather than extending the base

[`partials/SimplePage/index.html.twig`](../../../packages/panels/templates/partials/SimplePage/index.html.twig) repeats the base partial's three unsaved-changes `{% set %}` lines and its sentinel block **byte for byte**, including reading `this.getPanel` a second time in the same template.
That is a deliberate copy, not an oversight: a simple page has no header band, no breadcrumbs and no sub-navigation, so a `{% extends %}` would inherit a chrome whose every element the child would then have to empty, and the two templates would be coupled through blocks that exist only to be blanked.
What they genuinely share is nine lines, and the alternative — a third partial holding just the guard, included by both — trades a duplicated fragment for an indirection that is harder to read than the fragment.

The copy is verbatim on purpose: the two must agree, and a *reworded* copy is what would let them drift.
If the guard ever grows a fourth value, both templates change in one commit and [`PageRenderTest`](../../../packages/panels/tests/Integration/Twig/PageRenderTest.php) plus `PageControllerTest`'s `/probe/simple` case fail together if only one does.

## Divergences

### `SimplePage extends Page`, so it is routable and hides itself from navigation

Filament's `SimplePage extends BasePage` — the render half, with no panel awareness — and cannot be registered through `pages()` at all; its only consumers are the auth and tenancy pages, which the framework routes itself.
Ours extends `Pages\Page`, so `pages([Welcome::class])` mounts it like any other page, it carries a slug, a route name, breadcrumbs, header actions and the panel stamp, and it can host actions and schemas.

That is a **strictly larger** surface, and the cost of it is one line: `protected static bool $shouldRegisterNavigation = false`, so a simple page stays out of the sidebar unless it asks to be there.
Without the default, every routable simple page would appear in the navigation of the shell it deliberately does not render.

The `BasePage` split itself stays deferred and now has one clear trigger: porting auth, which is the first thing here that needs a page knowing no panel.

### `getLayoutData()` answers two keys where Filament's answers three

Filament's `SimplePage::getLayoutData()` returns `hasTopbar`, `maxContentWidth` **and** `maxWidth` — the last one assigned from the same expression as the second, an alias its simple layout reads under a different name.
Ours returns `hasTopbar` and `maxContentWidth` only: our [`SimpleLayout`](../../../packages/panels/templates/components/SimpleLayout/index.html.twig) takes one `maxContentWidthClass` prop that [the controller resolves](#the-width-resolution-is-one-ladder-in-the-controller), so a second key naming the same value would be read by nobody.

`getLayoutData()` is also **public** here where Filament's is protected: the controller reads it off the page rather than the page merging it into its own `render()`.

### The simple layout's auth gates are absent, and the strip holds the ordinary user menu

Filament's `layout/simple.blade.php` gates its top strip twice on `auth()->check()` and fills it with database notifications and a `SimpleUserMenu` — a cut-down menu built for a page with no topbar.
With authentication parked, ours gates the strip on `hasTopbar()` alone and puts the existing [`UserMenu`](README.md#layout-templates) in it, which today is the theme switcher: a simple page has no topbar, so without the strip it would have no theme affordance at all.

Two consequences follow.
`SimpleUserMenu` is **not ported** and takes a [not-ported row](../renames.md#renames) rather than a todo, because the component it would cut down is already the cut-down one.
And the database notifications trigger stays todo with the notifications-persistence surface it belongs to, not with this shell.
