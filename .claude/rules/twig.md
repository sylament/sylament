---
paths:
  - "packages/*/templates/**"
description: Template layout, component invocation, props and attribute rendering in the bundles' Twig templates.
---
# Twig rules

- **Two halves.** A template invoked as a `<twig:Sylament<Pkg>:<Name> />` tag lives at `templates/components/<Name>/index.html.twig`, anything rendered from PHP under `templates/partials/<Name>/`; ux-twig-component autodiscovers every file under `components/`, and a PHP-rendered template called as a tag dies on the `component` variable only `ViewComponent::render()` supplies.
- **Variants only under `partials/`** (`partials/<Name>/<Variant>.html.twig`); a second rendering of a tag component is a sibling component (`SubNavigationTabs` beside `SubNavigation`), because `ComponentTemplateLocationTest` admits nothing but `components/<Name>/index.html.twig`.
- **`partials/` holds** a `ViewComponent::$template`, an `{% include %}` target, a controller's `$twig->render()` target, a live component's `template_from_method` target, or a macro file; a macro used inside a `<twig:>` block goes in its own file and is `{% import %}`ed, since `_self` there resolves to the embedded component template.
- **Prefer an anonymous component** unless it needs PHP, and a component over `{% include %}`; `{% props %}` states a contract an include's `with {}` only implies.
- **A class-backed component declares both `template:` and `name:`** in its attribute; it never consults the template finder, and name derivation reads the application's `twig_component.defaults`, not ours.
- **Invoke a component as `<twig:Sylament<Pkg>:<Name> />`, never `{% component %}`**; spread a computed map with `{{ ...map }}` and fill slots with `<twig:block name="…">`, so one form stays the house form.
- **Two runtime-name escapes only:** `{% include %}` in actions' `partials/ActionGroup/index.html.twig` (`getTriggerTemplate()`) and the `component()` function in panels' `partials/Document/` and schemas' `partials/LiveComponent/`; a `<twig:>` tag name must be a literal.
- **`:name="expr"` for anything evaluated, bare `name="text"` only for a string literal**; a bare `hasContentEl="false"` passes the truthy string `"false"`, so booleans and numbers take the colon. A plain HTML element interpolates with `{{ }}`.
- **Give every prop a default** and never read a variable the template does not declare; the block form `<twig:X>…</twig:X>` inherits the caller's context (`<twig:>` has no `only`), so a defaultless prop is silently filled there from a same-named caller variable, while the self-closing form throws `Prop "<name>" should be defined.`
- **An explicit `:name=` wins even when it is `null`**, so pass the default at the call site (`:color="group.getColor ?? 'gray'"`); `PropsNode` tests the key with `array_key_exists()`, and a nullable getter otherwise replaces the prop's default with `null`.
- **Order `{% props %}` by meaning**: identity, state, header, content, footer, layout, behaviour, passthrough, a blank line between groups; `{% set %}` follows the same shape with dependency order winning, because position-ordered names (`above`/`below`/`before`/`after`) scramble when alphabetised. Nothing enforces it.
- **Leave one blank line** between the `{% set %}` preamble and the markup; it is the only break between computing values and emitting them.
- **Build a conditional attribute set with `attrs()`**, not `{% if %}` chains: `null` and `false` drop an attribute, `true` renders it empty, every other scalar renders including `0`, later sources win and `class`/`style` append, so `rows: component.getRows` already is the null guard.
- **An `aria-*` boolean stringifies** (`false` renders `aria-x="false"`) in `attrs()` and in ux-twig-component's bag alike, so fall back to `null` when the attribute must vanish.
- **Guard a truthiness attribute with `?: false`**, or an empty string renders `placeholder=""`; **render an attribute that must stay present with `?? ''`**, as `TextInput`'s `value` does, because idiomorph does not reset a typed value against a missing attribute.
- **Merge `style` by hand in a component's `attributes.defaults()`**: `attributes.without('style').defaults({style: styles(ours, attributes.all.style ?? null)})`, ours first so the caller still wins per property; `ComponentAttributes::defaults()` appends only `class`, `data-controller` and `data-action`, so a caller's `style` would silently wipe an inline colour palette. `classes()` and `styles()` build the token and declaration lists for that foreign bag.
- **Never call `twig/html-extra`'s `html_attr()` or `html_classes()`**; Twig-CS-Fixer's `ForbiddenFunctionRule` rejects them, and `attrs()` is the one attribute implementation every consumer app gets.
- **Emit only semantic `sy-` classes** (`sy-<package code>-<component>`), never Tailwind utilities, because templates are not scanned (`source(none)`) and a utility silently does nothing; a root that can render standalone carries the bare `sy` marker, which the scoped preflight matches.
- **Before changing a support component template,** find its suites with `grep -rlE 'SylamentSupport:<Name>([ />]|$)' packages/*/templates packages/*/tests` and run each hit's suite plus those of packages rendering the hit's templates; `InputWrapper` alone is rendered directly by forms, tables and panels.

Background: `docs/internals/templates.md`, `docs/progress/support/component-attribute-bag.md`, `docs/progress/forms/hidden.md`, `docs/styling.md`.
