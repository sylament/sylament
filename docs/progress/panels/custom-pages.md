# Custom pages (panels)

[`Sylament\Panels\Pages\Page`](../../../packages/panels/src/Pages/Page.php) as a
surface a user writes against: the plain panel page is now an **actions host and
a schemas host**, its partial is a chrome template with three named blocks, and
a body arrives either as a `content(Schema): Schema` override or as a child
template that `{% extends %}` the chrome and fills `{% block content %}`. Around
that sit the seams a hand-written page reaches for — header actions and their
alignment, `getPageClasses()`, `getExtraBodyAttributes()`, `getLayout()`,
`getMaxContentWidth()`, `halt()`, `callHook()`, `refresh()`, the `panelId`
stamp, and on a **resource** page `getResourceUrl()` plus a build-time guard
that refuses a route placeholder the page cannot mount.

It ports [`Pages/Page.php`](../../../vendor/filament/filament/src/Pages/Page.php)
and the half of
[`Pages/BasePage.php`](../../../vendor/filament/filament/src/Pages/BasePage.php)
that is not auth or widgets, with two Filament page concerns —
[`InteractsWithHeaderActions`](../../../vendor/filament/filament/src/Pages/Concerns/InteractsWithHeaderActions.php)
and (through [form-actions.md](form-actions.md))
`InteractsWithFormActions` — landing beside it under `Pages/Concerns/`. The
[`SimplePage`](simple-page.md) subclass, the width vocabulary and the
[unsaved-changes guard](unsaved-changes-alert.md) are this file's siblings.

⚠️ **Not to be confused with
[`Sylament\Panels\Resources\Pages\Page`](README.md#resource-pages)**, the
resource-page base that **extends** this one. Both are named `Page` and both
live under a `Pages\` namespace; the plain one knows a panel, the resource one
also knows a resource. Read them by their **import** — the resource base
imports the plain one as `use Sylament\Panels\Pages\Page as BasePage;`, which is
the only place in the codebase where the name `BasePage` means our own class
rather than Filament's. Every member this file describes is inherited by the
resource base and by every resource page under it.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`Pages/BasePage.php`](../../../vendor/filament/filament/src/Pages/BasePage.php)
  — the render/lifecycle half: the two host contracts, `content()`,
  `getLayoutData()`, `getExtraBodyAttributes()`, `refresh()`, `halt()`
- [`Pages/Page.php`](../../../vendor/filament/filament/src/Pages/Page.php)
  — the panel-aware half: `$layout`, `getHeader()`/`getFooter()`,
  `getPageClasses()`
- [`Pages/Concerns/InteractsWithHeaderActions.php`](../../../vendor/filament/filament/src/Pages/Concerns/InteractsWithHeaderActions.php)
  — the header list, its cache and its alignment
- [`resources/views/components/page/index.blade.php`](../../../vendor/filament/filament/resources/views/components/page/index.blade.php)
  — the chrome our base partial is
- [`Resources/Pages/Page.php`](../../../vendor/filament/filament/src/Resources/Pages/Page.php)
  — `getResourceUrl()`

## Surface

Every point of the page tier. _Blocked by_ names what
has to land first. _Detail_ carries two links, either of them:
**semantics** → the permanent note in [Semantics](#semantics); **diverges** →
the permanent note in [Divergences](#divergences). A row whose gap is written up
elsewhere links that file instead.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Page implements InteractsWithActions`](../../../packages/panels/src/Pages/Page.php) + the aliased `use HasActions` | `BasePage implements HasActions` + `InteractsWithActions` | done | — | [diverges](README.md#the-actions-host-sits-on-the-base-because-a-trait-outranks-an-inherited-method) |
| [`Page implements InteractsWithSchemas`](../../../packages/panels/src/Pages/Page.php) + `use HasSchemas` + [`schemas(): []`](../../../packages/panels/src/Pages/Page.php) | `BasePage implements HasSchemas` + `InteractsWithSchemas` | done | — | [semantics](#the-empty-discovery-list-is-mandatory-and-so-is-its-removal-from-every-leaf) |
| [`content(Schema): Schema`](../../../packages/panels/src/Pages/Page.php) | [`Page::content()`](../../../vendor/filament/filament/src/Pages/Page.php) | done | — | [semantics](#content-resolves-by-bare-name-so-an-empty-body-costs-a-live-request-nothing) |
| The `header` / `content` / `footer` blocks on [`partials/Page/index.html.twig`](../../../packages/panels/templates/partials/Page/index.html.twig) | the `header` / `footer` slots of `page/index.blade.php` and Filament's `getHeader()`/`getFooter()` | done | — | [semantics](#the-block-contract-is-the-page-bodys-public-api) · [renamed](../renames.md#renames) |
| [`Pages\Concerns\InteractsWithHeaderActions`](../../../packages/panels/src/Pages/Concerns/InteractsWithHeaderActions.php) — `getHeaderActions()`, `getCachedHeaderActions()`, `$cachedHeaderActions` | [`Pages/Concerns/InteractsWithHeaderActions.php`](../../../vendor/filament/filament/src/Pages/Concerns/InteractsWithHeaderActions.php) | done | — | [diverges](README.md#header-actions-are-bound-lazily-rather-than-at-boot) |
| [`$headerActionsAlignment`](../../../packages/panels/src/Pages/Concerns/InteractsWithHeaderActions.php) + `getHeaderActionsAlignment()` + the two-level [`Header`](../../../packages/panels/templates/components/Header/index.html.twig) DOM | the same property and the `fi-header-actions-ctn` wrapper | done | — | [semantics](#the-alignment-class-rides-the-inner-row-and-the-container-is-what-the-breadcrumb-offset-hangs-off) |
| The placement default on a placement-less header `ActionGroup` | `dropdownPlacement('bottom-end')` in the same walk | done | — | — |
| [`getPageClasses()`](../../../packages/panels/src/Pages/Page.php) | [`Page::getPageClasses()`](../../../vendor/filament/filament/src/Pages/Page.php) | done | — | [semantics](#getpageclasses-is-the-only-source-of-a-per-page-root-class) |
| [`getExtraBodyAttributes()`](../../../packages/panels/src/Pages/Page.php) → `<body>` | [`BasePage::getExtraBodyAttributes()`](../../../vendor/filament/filament/src/Pages/BasePage.php) | done | — | [semantics](#a-page-with-no-body-attributes-still-renders-a-stray-space) |
| [`$layout`](../../../packages/panels/src/Pages/Page.php) + `getLayout()` + `Page::LAYOUT` | `protected static string $layout` (a Blade view name) | done | — | [diverges](#a-third-shell-layout-is-a-document-override-not-a-getlayout-value) |
| [`getLayoutData()`](../../../packages/panels/src/Pages/Page.php) | [`BasePage::getLayoutData()`](../../../vendor/filament/filament/src/Pages/BasePage.php) | done | — | [diverges](simple-page.md#getlayoutdata-answers-two-keys-where-filaments-answers-three) |
| [`$maxContentWidth`](../../../packages/panels/src/Pages/Page.php) + `getMaxContentWidth()` | [`BasePage`](../../../vendor/filament/filament/src/Pages/BasePage.php)'s pair | done | — | [simple-page.md](simple-page.md#the-width-resolution-is-one-ladder-in-the-controller) |
| [`halt()`](../../../packages/panels/src/Pages/Page.php) + the `catch (Halt)` on both record pages | [`BasePage::halt()`](../../../vendor/filament/filament/src/Pages/BasePage.php) and Filament's `try`/`catch` around each ladder | done | — | [diverges](#halts-rollback-flag-is-carried-and-ignored) |
| [`callHook()`](../../../packages/panels/src/Pages/Page.php) | [`BasePage::callHook()`](../../../vendor/filament/filament/src/Pages/BasePage.php) | done | — | [semantics](README.md#a-page-lifecycle-hook-is-a-method-name-not-a-registration) |
| [`refresh()`](../../../packages/panels/src/Pages/Page.php) under `#[LiveListener('refresh-page')]` | [`BasePage::refresh()`](../../../vendor/filament/filament/src/Pages/BasePage.php) under `#[On('refresh')]` | done | — | [semantics](#refresh-is-a-listener-and-therefore-also-a-no-op-action-any-client-can-call) |
| [`getPanel()`](../../../packages/panels/src/Pages/Page.php) | `Filament::getCurrentOrDefaultPanel()` through the facade | done | — | [semantics](#getpanel-reads-the-sub-navigation-traits-private-context) |
| [`$panelId`](../../../packages/panels/src/Pages/Page.php) + `getPanelId()` + [`stampPanelRequestAttribute()`](../../../packages/panels/src/Pages/Page.php) at priority 20 | `original_request()` inside Filament's URL arms | done | — | [diverges](README.md#url-generation-inside-a-live-action-resolves-the-default-panel) · [semantics](#the-stamp-runs-above-actions-own-hydration-hook-and-the-priority-is-the-whole-mechanism) |
| [`Resources\Pages\Page::getResourceUrl()`](../../../packages/panels/src/Resources/Pages/Page.php) | [`Resources/Pages/Page.php`](../../../vendor/filament/filament/src/Resources/Pages/Page.php) `getResourceUrl()` | done | — | [semantics](#getresourceurl-fills-the-record-and-threads-the-parent-chain) |
| [`PanelRouteLoader`'s placeholder guard](../../../packages/panels/src/Routing/PanelRouteLoader.php) | no Filament counterpart | done | — | [diverges](#the-loader-refuses-a-placeholder-the-page-cannot-mount-which-filament-does-not) |
| `mount()` on the base | `BasePage` declares none either | skipped | — | [semantics](#a-custom-resource-page-is-a-mount-a-static-and-a-body) |
| `getHeader()` / `getFooter()` | the two `View`-returning hooks | skipped | — | [renamed](../renames.md#renames) — the `header` / `footer` blocks replace them |
| `getView()` / `getViewData()` | the same two | skipped | — | [diverges](#getview-and-getviewdata-have-no-counterpart-because-a-page-names-a-template) |
| `PageConfiguration`, `Page::make()`, `withConfiguration()` | [`Pages/PageConfiguration.php`](../../../vendor/filament/filament/src/Pages/PageConfiguration.php) | todo | — | [diverges](#the-configuration-object-is-not-ported) |
| Page-level `CanUseDatabaseTransactions` | [`Pages/Concerns/CanUseDatabaseTransactions.php`](../../../vendor/filament/filament/src/Pages/Concerns/CanUseDatabaseTransactions.php) | todo | — | [diverges](#halts-rollback-flag-is-carried-and-ignored) |
| `HasErrorNotifications` / `onValidationError()` / `$reportValidationErrorUsing` | [`Pages/Concerns/HasErrorNotifications.php`](../../../vendor/filament/filament/src/Pages/Concerns/HasErrorNotifications.php) | todo | — | the JS half is [Assets](README.md#assets)' `error-notifications.js` row |
| `CanAuthorizeAccess` | [`Pages/Concerns/CanAuthorizeAccess.php`](../../../vendor/filament/filament/src/Pages/Concerns/CanAuthorizeAccess.php) | todo | authorization | — |
| `getRenderHookScopes()` and the page render hooks | `BasePage::getRenderHookScopes()` | todo | render hooks | — |
| Clusters (`getCluster()`, the cluster breadcrumb and navigation arms) | [`Clusters/`](../../../vendor/filament/filament/src/Clusters) | todo | clusters | — |
| Widgets on a page (`getHeaderWidgets()`, `getFooterWidgets()`, `getWidgetData()`) | `BasePage`'s widget arms | todo | `sylament/widgets` | — |
| _rest not audited yet_ | | | | |

The tier is covered by
[`Unit/Pages/PageTest`](../../../packages/panels/tests/Unit/Pages/PageTest.php)
(the two hosts and the empty discovery list, the identity `content()`, the
layout seam and a page naming its own, the width/classes/body-attribute
defaults, `halt()` carrying its flag, `refresh()` as a listener, an action
resolved by name out of the form row, and the fail-fast when a `new`-built page
is asked for its panel),
[`Unit/Pages/Concerns/InteractsWithHeaderActionsTest`](../../../packages/panels/tests/Unit/Pages/Concerns/InteractsWithHeaderActionsTest.php)
(the binding-and-registration walk, its once-only read, the placement default and
the alignment),
[`Integration/Twig/PageRenderTest`](../../../packages/panels/tests/Integration/Twig/PageRenderTest.php)
(a whole plain page rendered off the **probe** panel — header actions, the
alignment DOM, the form row, the `after_form` block, the sentinel and the
negative that the live root's `data-controller` is exactly `live`; plus the two
live round trips that mount a grouped header action and a form-row action, and
the two that prove the panel stamp beats actions' own re-resolution),
[`Integration/Resources/Pages/PageTest`](../../../packages/panels/tests/Integration/Resources/Pages/PageTest.php)
(a user-authored resource page: its body block reading the record, its trail, its
band entry, its `getResourceUrl()` header link and its 404) and
[`Unit/Routing/PanelRouteLoaderTest`](../../../packages/panels/tests/Unit/Routing/PanelRouteLoaderTest.php)
(the placeholder guard's refusal and its three admits).

## Semantics

### The block contract is the page body's public API

The base partial
[`partials/Page/index.html.twig`](../../../packages/panels/templates/partials/Page/index.html.twig)
is chrome plus three blocks, and those three names are what a user's own
template overrides:

| Block | Where it sits | Default |
| --- | --- | --- |
| `header` | first child of `sy-pa-page-header-main-ctn` | the `Header` component, rendered only when the page has header actions, breadcrumbs, a heading or a subheading |
| `content` | inside `sy-pa-page-content`, between the two sub-navigation slots | `{{ renderSchema('content') }}` |
| `footer` | last child of `sy-pa-page-header-main-ctn`, after `sy-pa-page-main` | empty |

The [form variant](form-actions.md) adds three more inside its own `content`
override — `form_errors`, `form_actions` and `after_form` — so a page whose
template extends the variant can replace the error list, replace the button row,
or hang its own markup below the form without touching the rest.

Two mechanical rules hold for every one of them. **Every `{% endblock %}` is
named** (`{% endblock content %}`), which Twig-CS-Fixer's `EndBlockNameRule`
enforces across the repo — an unnamed one fails `composer test:twig`, not the
render. And a child template's own top-level `{% set %}` runs before the
parent's body and shares one context, so `this`, `attributes` and every preamble
variable the chrome computed are visible inside the child's blocks. That is what
makes `renderSchema()`, `renderTable()` and `live_action()` legal in a child at
all: all three read `this` out of the Twig context, and inside a `<twig:>`
component's block body `this` is that component rather than the page — which is
precisely why the chrome is a **template** a child extends, and not a Twig
component a page nests inside.

### `content` resolves by bare name, so an empty body costs a live request nothing

`content` is deliberately **not** listed in `schemas()`. `HasSchemas::getSchema('content')`
resolves the name against a same-named method at render time, while the list in
`schemas()` is what the de/hydration hooks force-build on every live request. So
a page that never overrides `content()` renders `renderSchema('content')` into an
empty schema at page load and builds nothing at all on a live round trip.
[`EditRecord`](README.md#editrecord) is the one page that *does* list it, because
its `content()` carries the relation managers and their tab state has to survive
a re-render.

### The empty discovery list is mandatory, and so is its removal from every leaf

`Pages\Page::schemas()` returns `[]`. Without it, `HasSchemas`' own default
`['schema']` demands a `schema()` method on every plain page and the first
`#[PreDehydrate]` throws — so the host trait and the override are one commit, not
two.

The removal on the leaves is the same rule read from the other end, and it is
load-bearing rather than tidy. PHP resolves *current class > inserted trait >
inherited class*, so a resource page that kept its own `use HasSchemas;` would
resolve `schemas()` to the **trait's** `['schema']` rather than the base's `[]`,
and force-build a schema that does not exist. All four resource pages therefore
dropped the trait and the contract in the commit that added them to the base;
`CreateRecord` and `EditRecord` keep their `schemas()` overrides in the class
body, where they win for the ordinary reason. The trait's three `LiveProp`s and
its hooks are still discovered on the concrete page through the base —
`LiveComponentMetadataFactory::propertiesFor()` recurses into parents and
`AsTwigComponent` reflects inherited methods.

### The alignment class rides the inner row, and the container is what the breadcrumb offset hangs off

The `Header` component renders two nested elements where it used to render one:
`sy-pa-header-actions-ctn` (`flex shrink-0 items-center gap-3`) wrapping
`sy-pa-header-actions` (`flex flex-wrap items-center gap-3`), Filament's own
shape. The inner row keeps the old class, so every
`.sy-pa-header-actions a` / `button` assertion in the suite still matches, and
the alignment class — `sy-align-<value>` off `getHeaderActionsAlignment()` — is
stamped on the **inner** row alone.

The container is not decoration: the `sm:mt-7` a page with breadcrumbs needs
moved onto it with the wrapper, because the offset has to push the whole actions
block down past the trail rather than push the buttons apart inside their row.
The header's own `end` arm is `flex-row justify-end` rather than
`flex-row-reverse` — Filament's value — so the declaration order of the actions
survives the alignment.

### `getPageClasses()` is the only source of a per-page root class

Every per-page class on the page root now comes from `getPageClasses()`, spread
into the root `attrs()` beside `sy sy-pa-page`. No resource partial hard-codes
its own: those partials are gone, so the four resource pages override the
method instead — `ListRecords` answers
`['sy-pa-resource-list-records-page', 'sy-pa-resource-<slug>']`, `EditRecord`
adds `'sy-pa-resource-record-<key>'`, and the slug arm (Filament's
`fi-resource-<slug>`) is new here. Every structural selector the suite already
pinned still matches byte for byte, which is what makes the partial deletion a
refactor rather than a rewrite. A hand-written page returning
`['sy-pa-settings-page']` gets the same scoping hook with no template of its own.

The record key goes into that token **raw**, and for a **composite** key that
is safe by construction rather than by luck: the
[key grammar](../schemas/record-key.md#the-grammar) admits `[A-Za-z0-9_-]`, `~`
and `=` and no whitespace at all, so a composite key cannot split one class
token into two. That is the whole of what the grammar closes. A key of **one**
identifier field is the flattened value verbatim — no escaping, no alphabet
check — so a single `string` identifier holding whitespace still splits the
token; and so does a resource declaring `$recordRouteKeyName`, which stamps an
arbitrary property
[without passing it through the codec at all](README.md#a-custom-recordroutekeyname-names-one-column-and-the-codec-never-sees-it).
Only `EditRecord` and `ManageRelatedRecords` emit the token at all — a custom
record page inherits `Pages\Page`'s empty `getPageClasses()` and has none — and
nothing in the toolkit's CSS, JavaScript or Twig selects it, so `~` and `=`
inside it cost nothing either.

### A page with no body attributes still renders a stray space

`getExtraBodyAttributes()` is read off the **un-mounted** page by
[`PageController`](README.md#routing), like `getTitle()`, and passed to the
Document as `bodyAttributes`. The Document renders `<body {{ attrs(bodyAttributes) }}>`
unconditionally, so a page that adds nothing emits `<body >` — a single stray
space inside the tag. It is valid HTML and every parser ignores it; guarding it
would mean an `{% if %}` around an attribute expression that is already a no-op,
so the space is accepted rather than spelled around.

### `refresh` is a listener, and therefore also a no-op action any client can call

`#[LiveListener('refresh-page')] public function refresh(): void {}` ports
Filament's `#[On('refresh')]` no-op one for one: the body is empty because the
re-render *is* the effect. UX's `LiveListener` extends `LiveAction`, so the
method is simultaneously a callable live action — any client can post
`refresh` and get a free re-render of the page. Nothing in the toolkit dispatches
`refresh-page` yet; the seam exists so a nested component (a relation manager, a
future widget) can ask its host page to redraw.

### `getPanel()` reads the sub-navigation trait's private context

`getPanel()` answers `$this->currentPanel?->get() ?? $this->panels?->getDefault()`
and throws a `LogicException` when a page built with `new` has neither. Both
services belong to
[`HasSubNavigation`](has-sub-navigation.md), which the base already composes and
which sets them through its own `#[Required]` setter — and a trait's **private**
members belong to the composing class, so `Pages\Page` reads them directly. That
is why the resource base's old private `$panelContext` and its
`setPanelContext()` setter did not travel up with `panelId`: the base already had
a `CurrentPanel`, and injecting a second one would have been two services for one
answer. It is also why `getPanel()` can only live on `Pages\Page` itself and
never on a subclass — a subclass cannot see the trait's private properties.

### The stamp runs above actions' own hydration hook, and the priority is the whole mechanism

`#[PostHydrate(priority: 20)] stampPanelRequestAttribute()` writes the page's
`panelId` back onto the current request. Twenty is not decorative: actions'
`afterMountedActionsHydrated()` runs at **10** and re-resolves every mounted
action, rebuilding the header list and minting the URLs inside it. UX orders
higher priorities first, so the stamp must precede it or those URLs answer for
the default panel. `PageRenderTest`'s *"stamps the panel before a mounted action
rebuilds the header, not after"* is the falsifiable form of that sentence: it
mounts and calls an action on the non-default **probe** panel and reads the
header link's `href` off the response.

The prop is minted by the getter its dehydration read runs
(`$this->panelId ??= $this->currentPanel?->get()?->getId()`) rather than in a
mount, because a page's `mount()` signature is the page's own and the base has
none. Moving both up from the resource base closed the plain-page half of the
[multi-panel URL divergence](README.md#url-generation-inside-a-live-action-resolves-the-default-panel);
the nested-live-component half stays open.

### `getResourceUrl()` fills the record and threads the parent chain

`Resources\Pages\Page::getResourceUrl(?string $name = null, array $parameters = [], …)`
is the instance-side URL helper a custom record page reaches for. When `$name` is
filled, is not `'index'`, and the page has a `getRecordKey()` (that is, composes
[`InteractsWithRecord`](interacts-with-record.md)), it defaults
`$parameters['record']` to this page's own key; then it delegates to
`Resource::getUrl()` with the page's `parentRecords` spread ahead of the caller's
parameters, exactly as every other URL arm on the base does.

Both halves are what make `$this->getResourceUrl('edit')` a complete call on a
nested page: the record comes off the page and the ancestors come off the chain,
so nothing raises `MissingMandatoryParametersException`. The **static**
`Resource::getUrl()` family stays what it was —
[the recorded wrong-answer surface](README.md#resources-are-collected-from-a-tag-not-discovered-on-the-filesystem)
for a page that has a record in hand.

### A custom resource page is a `mount()`, a static and a body

Nothing new is needed for a resource page carrying a `{record}`: routing, mount,
scoped resolution, breadcrumbs, the band and the panel stamp all work today. The
whole recipe is four declarations.

```php
#[AsLiveComponent]
final class PreviewArticle extends Sylament\Panels\Resources\Pages\Page
{
    use InteractsWithRecord;

    protected static string $resource = ArticleResource::class;

    protected static ?string $breadcrumb = 'Preview';

    protected ?string $template = 'admin/pages/preview_article.html.twig';

    public function mount(int|string $record, array $parentRecords = []): void
    {
        $this->mountRecord($record, $parentRecords);
    }
}
```

registered on the resource as `'preview' => PreviewArticle::route('/{record}/preview')`
and, to join the record band, listed in `Resource::getRecordSubNavigation()` —
explicit in Filament too. Four things the shape depends on:

- **The `mount()` is not optional and the base declares none.** `Pages\Page` has
  no `mount()`, so a page that forgets it silently receives no record and ux
  paints `record` onto the live root as an HTML attribute. The
  [loader guard](#the-loader-refuses-a-placeholder-the-page-cannot-mount-which-filament-does-not)
  is what turns that into a build-time `LogicException` instead.
- **`InteractsWithRecord` is composable on a resource page only.** It reaches
  `getResourceInstance()` for the scoped binding, which a plain `Pages\Page` does
  not have.
- **`getRecordTitle()` is the only pre-mount-safe title reader.** `PageController`
  reads `getTitle()` off an **un-mounted** page for the document `<title>`, so a
  `getTitle()` override that calls `getRecord()` 404s every GET of the page —
  [the trap the edit page's own guard exists for](README.md#the-document-title-of-an-edit-page-carries-the-record-label-not-the-record-title).
- **The body is either a `content()` override or a child template.** The fixture
  [`PreviewProbePage`](../../../packages/panels/tests/Fixtures/Resources/Pages/PreviewProbePage.php)
  takes the template route, extending the base chrome and filling
  `{% block content %}` with markup that reads `this.getRecord`.

## Divergences

### `getView()` and `getViewData()` have no counterpart, because a page names a template

Filament's `BasePage` renders through `$view` plus `getView()` / `getViewData()`,
a Blade view name and a variable bag the page's own `render()` merges. Ours
renders through UX LiveComponent's `template_from_method: getTemplate`, which
takes a template **name** and hands the component itself to Twig as `this` — so
there is no bag to fill: a template reads whatever it needs off `this` by calling
the page's own getters. `$template` and `getTemplate()` are the port of `$view`
and `getView()` under the standing Blade-view → Twig-template
[idiom translation](../renames.md#idiom); `getViewData()` has nothing left to do
and is not ported.

The same reasoning covers `getLayoutData()`'s third key —
[recorded on `SimplePage`](simple-page.md#getlayoutdata-answers-two-keys-where-filaments-answers-three),
the only class that fills it.

### A third shell layout is a `Document` override, not a `getLayout()` value

`getLayout()` returns a **Twig component name**, where Filament's `$layout` is a
Blade view name, and `PageController` accepts exactly two values —
`Page::LAYOUT` (`'SylamentPanels:Layout'`) and `SimplePage::LAYOUT`
(`'SylamentPanels:SimpleLayout'`) — throwing a `LogicException` naming the page
and its value for anything else.

The narrowness is forced by the Document template, which branches on the string
with two literal `<twig:>` tags rather than calling the `component()` function:
a function call cannot take a `<twig:block>`, and the page has to be handed to
the shell as a block. So a consumer wanting a third shell overrides
`@SylamentPanels/partials/Document/index.html.twig` — the one seam that is
already a plain Twig template a Symfony application can override by path — and
the failing `getLayout()` value is the loud form of that instruction.

The Document's branch tests the **string literal** `'SylamentPanels:SimpleLayout'`
rather than `SimplePage::LAYOUT`, since Twig has no constant import; the
controller's `in_array()` guard is *not* what pins the two sides together,
because both compare against the same constants and would move as one. What pins
them is
[`PageControllerTest`](../../../packages/panels/tests/Integration/Controllers/PageControllerTest.php)'s
`/probe/simple` case, which requests the URL and asserts the rendered
`body > .sy-pa-simple-layout` — a rename on either side fails there.

### `halt()`'s rollback flag is carried and ignored

`halt(bool $shouldRollbackDatabaseTransaction = false)` throws support's
`Halt` with the flag set on the exception, and both record pages wrap their hook
ladder in `try { … } catch (Halt) { return null; }`. Nothing reads the flag:
page-level `CanUseDatabaseTransactions` is not ported, so there is no transaction
to roll back and no honest place to consult it. It is carried rather than dropped
so that porting the transactions concern is additive — the exception already
tells it what to do.

What the catch *does* fix is real: without it a `Halt` thrown from a
`beforeSave` hook escapes `save()` and answers 500. The
[`EditHookedArticle`](../../../packages/panels/tests/Fixtures/Resources/Pages/EditHookedArticle.php)
fixture's `$haltsBeforeSave` arm pins the ladder stopping where the hook threw —
no mutator, no write, no notification, no redirect, and the row untouched.

### The loader refuses a placeholder the page cannot mount, which Filament does not

For every `{name}` in a mounted path,
[`PanelRouteLoader`](../../../packages/panels/src/Routing/PanelRouteLoader.php)
now requires one of three things: the name is a **parent parameter** the loader
folds itself, the page's `mount()` **declares a parameter** of that name, or the
page declares a **public property** of that name. Otherwise it throws a
`LogicException` naming the page, the path and the placeholder, at route-build
time — the shape its existing collision guards already use.

Filament needs no such guard because a Livewire page *is* its own route action
and an unmatched parameter is a missing-argument error. Here the parameter is
handed to ux-twig-component, which matches it against `mount()` parameters, then
against writable properties, and paints whatever is left onto the live root as an
HTML attribute — no exception, no log, and a page that silently shows the wrong
record or none at all.

The shipped guard reads placeholders with `/\{([^}]+)\}/`, so a Symfony inline
requirement is captured **whole**: a path written `/{record<\d+>}` would be
checked for a parameter literally named `record<\d+>` and refused. Nothing in the
toolkit or the demo writes one — a page path is a slug and a bare `{record}` —
so the shape is recorded rather than worked around; normalising the capture is a
one-line change if a consumer ever wants inline requirements.

### The loader narrows a record placeholder a separator follows, because the compiler's default excludes it

`RouteCompiler` compiles a placeholder to a pattern that excludes the **next
static separator** as well as `/`: `/{record}/edit` gives `[^/]++`, but
`/{record}-preview` gives `[^/\-]++` and `/{record}~preview` gives `[^/~]++`.
Four characters of the [key grammar](../schemas/record-key.md#the-grammar) are
in `RouteCompiler::SEPARATORS` — `-`, `_`, `~` and `=` — and the first two are
*safe* bytes the codec never escapes, so the trap predates composite keys
entirely: a UUID or slug `$recordRouteKeyName` already breaks against
`/{record}-preview`. And it fails at **generation**, not at match —
`UrlGenerator::generate()` raises `InvalidParameterException`, so the **listing**
that mints the row URL 500s and the record page is never reached, or, under
`framework.router.strict_requirements: null`, the link generates and the click
404s. "It 404s" sends a debugger to the wrong page.

So [`PanelRouteLoader`](README.md#routing) stamps
`requirements: ['record' => '[^/]+']` on a mounted path whose `{record}` is
followed, up to the next `/` or the end of the path, by non-empty **pure
static** text — text carrying no `{` — that opens on a separator other than
`/`. A declared requirement replaces the compiler's default wholesale,
separator exclusion included, which is the whole fix. Three properties of it
are load-bearing. It is **conditional**, so
every path in this toolkit and in the demo — all `/{record}/…` or a bare
`/{record}` — compiles to the byte-identical pattern it compiled to before. It
must stay **greedy**: the possessive `[^/]++` never gives the trailing static
text back, so it generates a URL that then matches nothing, which is exactly the
"tidy the regex to match the compiler's default" change a later reader makes.
And only `{record}` is stamped — a parent placeholder is always followed by `/`,
since the loader writes that slash itself, and any other placeholder on a page
path is the developer's own, where narrowing it would change how *their* value
splits.

The rule is *any* non-slash separator, so `/{record}.pdf` is narrowed too. No
**composite** key contains a dot and `.` is escaped into the pattern either
way, so all the stamp buys there is that a single identifier holding one stops
being refused.

**Pure static** is what keeps the stamp from taking a URL apart differently. A
greedy `[^/]+` runs to the **last** occurrence of the separator, so on a
segment carrying a second placeholder it re-splits a URL that works today:
`/{record}-{locale}` reads `/5-en-GB` as `5` and `en-GB`, and a stamped pattern
would read it `5-en` and `GB`. Such a segment is therefore left to the
compiler, byte-identical to what it compiled to before, and two residuals
follow. On a placeholder-bearing segment a key containing that separator still
cannot be generated — declare the page `/{record}/…` instead. And where the
stamp does apply, an earlier-registered `/{record}-x` claims `/5-y-x` with
`record = "5-y"`, where a later `/{record}-y-x` used to match it with
`record = "5"`: order-dependent, and visible only to an app that registered
both shapes.

`PageRegistration` grows no `requirements()` member to go with it. The narrowing
is computed from the path the page already declares, and a second place to
narrow a route from is a second place for the two to disagree; a consumer's own
placeholder is theirs to name in the path. Recorded as a decision rather than an
omission.

Three shapes it does not touch. A bare `/{record}` registered **before**
`/{record}-preview` matches `/5-preview` with `record = "5-preview"` — route
order decides it, the loader registers a resource's pages in `getPages()` order,
and that is Symfony's matching, not ours. And a Symfony inline requirement such
as `/{record<\d+>}` can never match a composite key, which is
[never numeric](../schemas/record-key.md#the-grammar) — though the guard above
refuses that path first, for its own reason. A `{record}` written with any of
Symfony's inline syntaxes — `{record<\d+>}`, `{record?}`, `{!record}` — is read
raw by the requirement test and is never stamped either.

### The configuration object is not ported

Filament's `Page::make()` returns a `PageConfiguration`, and `withConfiguration()`
lets a cluster or a plugin adjust a page's registration without subclassing it.
Neither ships here: a page is a **container service** reached by class-string
through the [page locator](README.md#pages-register-as-live-components-through-a-compiler-pass-over-a-per-class-attribute),
and a panel registers it by naming the class in `pages()`, so there is no
registration object to configure. The consumers that force the indirection over
there — clusters and plugins — are both unported. Revisit with whichever lands
first.
