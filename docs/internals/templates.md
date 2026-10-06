# Templates

The reasoning behind `.claude/rules/twig.md`. Each section answers one question; the rule file holds the imperative.

## Why does `templates/` split into `components/` and `partials/`?

Because ux-twig-component decides what is a tag by path. `ComponentTemplateFinder::findAnonymousComponentTemplate()` tries four candidates in order: `<dir>/<Name>.html.twig` and `<dir>/<Name>/index.html.twig` under the application's anonymous-template directory, then `@<Bundle>/components/<Name>.html.twig` and `@<Bundle>/components/<Name>/index.html.twig`. The `components/` segment is hard-coded, so any file under a bundle's `templates/components/` is callable as `<twig:Sylament<Pkg>:<Name> />` whether or not it was written to be. A template `ViewComponent::render()` renders expects the `component` variable that method passes (with `getExtraTemplateData()` and `templateData()` after it); invoked as a tag it has no such variable and dies.

The second finder path is also why an anonymous component gets `<Name>/index.html.twig` for free.

`packages/schemas/tests/Architecture/ComponentTemplateLocationTest.php` pins the shape of each half: every file under `components/` must be `components/<Name>/index.html.twig`, and every file under `partials/` must sit one directory deep, `partials/<Name>/<anything>.html.twig`. A component rendered from PHP can therefore grow variants without restructuring, while a second rendering of a tag component has to be a sibling component (panels' `SubNavigationSidebar`, `SubNavigationTabs` and `SubNavigationMobileMenu` beside `SubNavigation`).

## What kinds of template sit under `partials/`?

Anything rendered from PHP or from another template, never as a tag:

- a `ViewComponent::$template` (most of schemas', forms' and tables' partials; `Repeater`, `ToggleButtons` and tables' `Summarizer` carry variants selected by a `*_TEMPLATE` constant or an overridden `$defaultTemplate`);
- an `{% include %}` target (actions' `ActionGroup/*Trigger` templates, chosen by `ActionGroup::getTriggerTemplate()`);
- a controller's own `$twig->render()` (panels' `Document/index.html.twig`, the HTML document `PageController` returns);
- a live component's `template_from_method` target (panels' page and relation-manager partials, set by `PageComponentPass` and `RelationManagerComponentPass` to `getTemplate`);
- a macro file (forms' `TagsInput/Chip.html.twig`, imported by `TagsInput/index.html.twig`).

The macro file exists because `_self` inside a `<twig:>` block resolves to the embedded component template, which carries none of the outer template's macros.

A directory may have no `index.html.twig`. Actions' `partials/Action/` holds five variants and no index, because `Action::$defaultTemplate` is itself one of them (`BUTTON_TEMPLATE`).

Panels' `Page/form.html.twig` and `Page/table.html.twig` `{% extends %}` `Page/index.html.twig` and fill its `content` block, and `EditRecord/index.html.twig` extends the form variant. They are the only templates that inherit from another; that is the variant freedom `partials/` exists to give.

## Why must a class-backed component declare `template:` and `name:`?

`ComponentFactory::metadataFor()` returns a class component's compile-time configuration before it ever asks the template finder, so a class component never searches for its template. Its name, when not given, is derived in `TwigComponentPass` by matching the FQCN against the **application's** `twig_component.defaults` namespaces; no bundle prepends an entry there, so for a Sylament class derivation either throws or yields whatever name the app's configuration happens to produce. `Modals`, `Notifications` and `GlobalSearch` each declare both.

A class component exists only where PHP reaches something a template cannot. `Modals` walks ux-twig-component's `ComponentStack` to find its host live component, because inside a Twig component `this` is that component, not the host (`docs/progress/actions/has-actions.md`). `GlobalSearch` is live and keeps the panel id in a `LiveProp` it restamps from `#[PostHydrate]`, since a live request carries no `_sylament_panel` route default (`docs/progress/panels/global-search.md`). How a bundle registers its class components is in `.claude/rules/bundles.md`.

## Why does the block form leak the caller's context?

The pre-lexer compiles a self-closing `<twig:X />` to `{{ component('X', {…}) }}`, and `ComponentRenderer::render()` renders it with an empty outer context, so it inherits nothing. The block form `<twig:X>…</twig:X>` compiles to `{% component 'X' with {…} %}` with no `only` (the tag accepts `only`; the HTML syntax never emits it), and `ComponentNode` then hands the caller's whole context in.

`PropsNode` decides what a prop ends up holding:

- a prop already in the context (passed explicitly, or inherited) keeps that value; the test is `array_key_exists()`, so an explicit `null` counts;
- a prop with a default is reset to the default whenever `__context` carries its name, and `__context` is the caller's context minus the attributes the tag passed, so an inherited same-named variable never wins over a default while an explicit `:name=` still does;
- a prop without a default that is not in the context throws `Prop "<name>" should be defined.`

So the leak is exactly a defaultless prop under the block form: it is silently filled from a same-named caller variable. Under the self-closing form the same prop throws. And a nullable getter passed explicitly (`:color="component.getColor"`) replaces `color = 'primary'` with `null`, which renders uncoloured; the call site passes the default itself (`?? 'primary'`, `?? 'md'`).

## Why are bare attributes on a `<twig:>` tag strings?

The pre-lexer treats an attribute prefixed with `:` as an expression and copies it verbatim; any other value becomes a quoted string. `hasContentEl="false"` passes `'false'`, which is truthy. An attribute with no `=` at all passes `true`.

## How do the two attribute bags render a value?

Sylament's `ComponentAttributeBag` (behind `attrs()`) and ux-twig-component's `ComponentAttributes` (which renders through `twig/html-extra`'s `HtmlExtension::htmlAttrValue()`) agree:

| Value | Renders |
| --- | --- |
| `null`, `false` | nothing |
| `true` on `aria-*` | `aria-x="true"` |
| `false` on `aria-*` | `aria-x="false"` |
| `true` on `data-*` | `data-x="true"` |
| `true` elsewhere | `x=""` |
| any other scalar, `0` included | the scalar as a string |

`attrs()` merges its sources left to right: later scalars win, `class` and `style` token lists append, and a `class` value may be a string, a list or a conditional map. It also accepts `null`, `false` and `''` as a whole source, for the implicit else of a ternary.

Two shapes the bag cannot infer. An attribute guarded on truthiness needs `?: false`, or an empty placeholder renders `placeholder=""`. An attribute that must render empty rather than vanish needs `?? ''`; why `TextInput` and `Hidden` need it for `value` is in `docs/progress/forms/hidden.md`.

## Why is `style` merged by hand in a component tag?

`ComponentAttributes::defaults()` concatenates only `class`, `data-controller` and `data-action`; every other key, `style` included, is replaced by the caller's value. (It merges any key whose value implements `twig/html-extra`'s `MergeableInterface`, but `styles()` returns a plain string, so that path never applies here.) A component emitting an inline colour palette through `defaults()` would have the whole palette wiped by any caller `style`, while `sy-color` stayed on the element, so nothing looks broken until the colour is wrong. Support's `Button`, `Badge`, `Link`, `IconButton` and `DropdownItem` merge it as `attributes.without('style').defaults({style: styles(ours, attributes.all.style ?? null)})`, ours first so a caller's declaration still wins per property.

## Why are `twig/html-extra`'s helpers banned when the package is installed?

ux-twig-component requires `twig/html-extra` to render its own bag, so the package is present in every install. Sylament still declares it nowhere and keeps one attribute implementation: `.twig-cs-fixer.dist.php` adds a `ForbiddenFunctionRule` for `html_attr` and `html_classes`, the two helpers that would compete with `attrs()` and `classes()`. Support's bundle registers `AttributeExtension`, so a consuming app needs nothing else.

## Why order props by meaning?

Alphabetical order scrambles names whose order is their meaning, such as `FieldWrapper`'s child schemas `above`, `below`, `before`, `after`, and splits pairs such as `stickyHeader` and `stickyFooter`. Grouping by the order the markup renders them (identity, state, header, content, footer, layout, behaviour, passthrough) lets a reader find a prop from the markup. No formatter touches Twig, so the order is judgment; it pays on long lists such as `Section`'s and `Modal`'s.
