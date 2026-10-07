---
description: The panel page as a surface a user writes against, with its action and schema hosts, the chrome blocks, header actions and the page-level seams.
ports:
  - vendor/filament/filament/src/Pages/BasePage.php
  - vendor/filament/filament/src/Pages/Page.php
  - vendor/filament/filament/src/Pages/Concerns/InteractsWithHeaderActions.php
  - vendor/filament/filament/resources/views/components/page/index.blade.php
  - vendor/filament/filament/src/Resources/Pages/Page.php
owns:
  - packages/panels/src/Pages/Concerns/InteractsWithHeaderActions.php
  - packages/panels/templates/components/Header/
  - packages/panels/templates/partials/Page/index.html.twig
---
# Custom pages (panels)

[`Sylament\Panels\Pages\Page`](../../../packages/panels/src/Pages/Page.php) is an **actions host and a schemas host**, its partial is a chrome template with three named blocks, and a body arrives either as a `content(Schema): Schema` override or as a child template that `{% extends %}` the chrome and fills `{% block content %}`.
The [`SimplePage`](simple-page.md) subclass and the [unsaved-changes guard](unsaved-changes-alert.md) are this file's siblings, and [page.md](page.md) holds the base's declarative half.

**Not to be confused with [`Sylament\Panels\Resources\Pages\Page`](resource-pages.md)**, the resource-page base that **extends** this one.
Both are named `Page` and both live under a `Pages\` namespace; the plain one knows a panel, the resource one also knows a resource.
The resource base imports the plain one as `use Sylament\Panels\Pages\Page as BasePage;`, the only place in the codebase where the name `BasePage` means our own class rather than Filament's.
Every member this file describes is inherited by the resource base and by every resource page under it.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Page implements InteractsWithActions`](../../../packages/panels/src/Pages/Page.php) + the aliased `use HasActions` | `BasePage implements HasActions` + `InteractsWithActions` | done | — | [semantics](page.md#the-actions-host-sits-on-the-base-because-a-trait-outranks-an-inherited-method) |
| [`Page implements InteractsWithSchemas`](../../../packages/panels/src/Pages/Page.php) + `use HasSchemas` + [`schemas(): []`](../../../packages/panels/src/Pages/Page.php) | `BasePage implements HasSchemas` + `InteractsWithSchemas` | done | — | [semantics](#the-empty-discovery-list-is-mandatory-and-so-is-its-removal-from-every-leaf) |
| [`content(Schema): Schema`](../../../packages/panels/src/Pages/Page.php) | [`Page::content()`](../../../vendor/filament/filament/src/Pages/Page.php) | done | — | [semantics](#content-resolves-by-bare-name-so-an-empty-body-costs-a-live-request-nothing) |
| The `header` / `content` / `footer` blocks on [`partials/Page/index.html.twig`](../../../packages/panels/templates/partials/Page/index.html.twig) | the `header` / `footer` slots of `page/index.blade.php` and Filament's `getHeader()`/`getFooter()` | done | — | [semantics](#the-block-contract-is-the-page-bodys-public-api) · [renamed](../renames.md#renames) |
| [`Pages\Concerns\InteractsWithHeaderActions`](../../../packages/panels/src/Pages/Concerns/InteractsWithHeaderActions.php) — `getHeaderActions()`, `getCachedHeaderActions()`, `$cachedHeaderActions` | [`Pages/Concerns/InteractsWithHeaderActions.php`](../../../vendor/filament/filament/src/Pages/Concerns/InteractsWithHeaderActions.php) | done | — | [diverges](#header-actions-are-bound-lazily-rather-than-at-boot) · [diverges](#getheaderactions-is-public-so-a-page-copying-filaments-protected-override-fatals) |
| `getActions()` (`@deprecated` for `getHeaderActions()`) | [`Pages/Concerns/InteractsWithHeaderActions.php`](../../../vendor/filament/filament/src/Pages/Concerns/InteractsWithHeaderActions.php) | skipped | — | [renames.md](../renames.md#a-deprecated-filament-member-is-not-ported) |
| [`$headerActionsAlignment`](../../../packages/panels/src/Pages/Concerns/InteractsWithHeaderActions.php) + `getHeaderActionsAlignment()` + the two-level [`Header`](../../../packages/panels/templates/components/Header/index.html.twig) DOM | the same property and the `fi-header-actions-ctn` wrapper | done | — | [semantics](#the-alignment-class-rides-the-inner-row-and-the-container-is-what-the-breadcrumb-offset-hangs-off) |
| The placement default on a placement-less header `ActionGroup` | `dropdownPlacement('bottom-end')` in the same walk | done | — | — |
| [`getPageClasses()`](../../../packages/panels/src/Pages/Page.php) | [`Page::getPageClasses()`](../../../vendor/filament/filament/src/Pages/Page.php) | done | — | [semantics](#getpageclasses-is-the-only-source-of-a-per-page-root-class) |
| [`getExtraBodyAttributes()`](../../../packages/panels/src/Pages/Page.php) → `<body>` | [`BasePage::getExtraBodyAttributes()`](../../../vendor/filament/filament/src/Pages/BasePage.php) | done | — | [semantics](#a-page-with-no-body-attributes-still-renders-a-stray-space) |
| [`$layout`](../../../packages/panels/src/Pages/Page.php) + `getLayout()` + `Page::LAYOUT` | `protected static string $layout` (a Blade view name) | done | — | [diverges](#a-third-shell-layout-is-a-document-override-not-a-getlayout-value) |
| [`getLayoutData()`](../../../packages/panels/src/Pages/Page.php) | [`BasePage::getLayoutData()`](../../../vendor/filament/filament/src/Pages/BasePage.php) | done | — | [diverges](simple-page.md#getlayoutdata-answers-two-keys-where-filaments-answers-three) |
| [`$maxContentWidth`](../../../packages/panels/src/Pages/Page.php) + `getMaxContentWidth()` | [`BasePage`](../../../vendor/filament/filament/src/Pages/BasePage.php)'s pair | done | — | [simple-page.md](simple-page.md#the-width-resolution-is-one-ladder-in-the-controller) |
| [`halt()`](../../../packages/panels/src/Pages/Page.php) | [`BasePage::halt()`](../../../vendor/filament/filament/src/Pages/BasePage.php) | done | — | [diverges](#halts-rollback-flag-is-carried-and-ignored) |
| [`callHook()`](../../../packages/panels/src/Pages/Page.php) | [`BasePage::callHook()`](../../../vendor/filament/filament/src/Pages/BasePage.php) | done | — | [semantics](page.md#a-page-lifecycle-hook-is-a-method-name-not-a-registration) |
| [`refresh()`](../../../packages/panels/src/Pages/Page.php) under `#[LiveListener('refresh-page')]` | [`BasePage::refresh()`](../../../vendor/filament/filament/src/Pages/BasePage.php) under `#[On('refresh')]` | done | — | [semantics](#refresh-is-a-listener-and-therefore-also-a-no-op-action-any-client-can-call) |
| [`getPanel()`](../../../packages/panels/src/Pages/Page.php) | `Filament::getCurrentOrDefaultPanel()` through the facade | done | — | [semantics](#getpanel-reads-the-sub-navigation-traits-private-context) |
| [`$panelId`](../../../packages/panels/src/Pages/Page.php) + `getPanelId()` + [`stampPanelRequestAttribute()`](../../../packages/panels/src/Pages/Page.php) at priority 20 | `original_request()` inside Filament's URL arms | done | — | [diverges](resource.md#url-generation-inside-a-live-action-resolves-the-panel-the-page-was-opened-in) · [semantics](#the-stamp-runs-above-actions-own-hydration-hook-and-the-priority-is-the-whole-mechanism) |
| [`Resources\Pages\Page::getResourceUrl()`](../../../packages/panels/src/Resources/Pages/Page.php) | [`Resources/Pages/Page.php`](../../../vendor/filament/filament/src/Resources/Pages/Page.php) `getResourceUrl()` | done | — | [semantics](#getresourceurl-fills-the-record-and-threads-the-parent-chain) |
| `mount()` on the base | `BasePage` declares none either | skipped | — | [semantics](#a-custom-resource-page-is-a-mount-a-static-and-a-body) |
| `getHeader()` / `getFooter()` | the two `View`-returning hooks | skipped | — | [renamed](../renames.md#renames) · [semantics](#the-block-contract-is-the-page-bodys-public-api) |
| `getView()` / `getViewData()` | the same two | skipped | — | [diverges](#getview-and-getviewdata-have-no-counterpart-because-a-page-names-a-template) |
| `PageConfiguration`, `Page::make()`, `withConfiguration()` | [`Pages/PageConfiguration.php`](../../../vendor/filament/filament/src/Pages/PageConfiguration.php) | blocked | clusters or plugins | [detail](#the-configuration-object-is-not-ported) |
| Page-level `CanUseDatabaseTransactions` | [`Pages/Concerns/CanUseDatabaseTransactions.php`](../../../vendor/filament/filament/src/Pages/Concerns/CanUseDatabaseTransactions.php) | todo | — | [diverges](#halts-rollback-flag-is-carried-and-ignored) |
| `HasErrorNotifications` / `onValidationError()` / `$reportValidationErrorUsing` | [`Pages/Concerns/HasErrorNotifications.php`](../../../vendor/filament/filament/src/Pages/Concerns/HasErrorNotifications.php) | todo | — | [assets.md](assets.md#surface) |
| `CanAuthorizeAccess` | [`Pages/Concerns/CanAuthorizeAccess.php`](../../../vendor/filament/filament/src/Pages/Concerns/CanAuthorizeAccess.php) | blocked | authorization | — |
| `getRenderHookScopes()` and the page render hooks | `BasePage::getRenderHookScopes()` | blocked | render hooks | — |
| Clusters (`getCluster()`, the cluster breadcrumb and navigation arms) | [`Clusters/`](../../../vendor/filament/filament/src/Clusters) | blocked | clusters | — |
| Widgets on a page (`getHeaderWidgets()`, `getFooterWidgets()`, `getWidgetData()`) | `BasePage`'s widget arms | blocked | `sylament/widgets` | — |
| _rest not audited yet_ | — | — | — | — |

## Details

### The configuration object is not ported

Filament's `Page::make()` returns a `PageConfiguration`, and `withConfiguration()` lets a cluster or a plugin adjust a page's registration without subclassing it.
Neither ships here: a page is a **container service** reached by class-string through the [page locator](page.md#pages-register-as-live-components-through-a-compiler-pass-over-a-per-class-attribute), and a panel registers it by naming the class in `pages()`, so there is no registration object to configure.
The consumers that force the indirection over there, clusters and plugins, are both unported, and the row waits on whichever lands first.

## Semantics

### The block contract is the page body's public API

The base partial [`partials/Page/index.html.twig`](../../../packages/panels/templates/partials/Page/index.html.twig) is chrome plus three blocks, and those three names are what a user's own template overrides:

| Block | Where it sits | Default |
| --- | --- | --- |
| `header` | first child of `sy-pa-page-header-main-ctn` | the `Header` component, rendered only when the page has header actions, breadcrumbs, a heading or a subheading |
| `content` | inside `sy-pa-page-content`, between the two sub-navigation slots | `{{ renderSchema('content') }}` |
| `footer` | last child of `sy-pa-page-header-main-ctn`, after `sy-pa-page-main` | empty |

The [form variant](form-actions.md) adds three more inside its own `content` override (`form_errors`, `form_actions` and `after_form`), so a page whose template extends the variant can replace the error list, replace the button row, or hang its own markup below the form without touching the rest.

Two mechanical rules hold for every one of them.
**Every `{% endblock %}` is named** (`{% endblock content %}`), which Twig-CS-Fixer's `EndBlockNameRule` enforces across the repo: an unnamed one fails `composer test:twig`, not the render.
And a child template's own top-level `{% set %}` runs before the parent's body and shares one context, so `this`, `attributes` and every preamble variable the chrome computed are visible inside the child's blocks.
That is what makes `renderSchema()`, `renderTable()` and `live_action()` legal in a child at all: all three read `this` out of the Twig context, and inside a `<twig:>` component's block body `this` is that component rather than the page, which is why the chrome is a **template** a child extends, and not a Twig component a page nests inside.

### `content` resolves by bare name, so an empty body costs a live request nothing

`content` is deliberately **not** listed in `schemas()`.
`HasSchemas::getSchema('content')` resolves the name against a same-named method at render time, while the list in `schemas()` is what the de/hydration hooks force-build on every live request.
So a page that never overrides `content()` renders `renderSchema('content')` into an empty schema at page load and builds nothing at all on a live round trip.
[`EditRecord`](edit-record.md) is the one page that *does* list it, because its `content()` carries the relation managers and their tab state has to survive a re-render.

### The empty discovery list is mandatory, and so is its removal from every leaf

`Pages\Page::schemas()` returns `[]`.
Without it, `HasSchemas`' own default `['schema']` demands a `schema()` method on every plain page and the first `#[PreDehydrate]` throws, so the host trait and the override go together.

The leaves follow the same rule from the other end.
PHP resolves *current class > inserted trait > inherited class*, so a resource page that composed `HasSchemas` itself would resolve `schemas()` to the **trait's** `['schema']` rather than the base's `[]`, and force-build a schema that does not exist.
No resource page composes the trait; `CreateRecord` and `EditRecord` declare their `schemas()` overrides in the class body, where they win for the ordinary reason.
The trait's three `LiveProp`s and its hooks are still discovered on the concrete page through the base: `LiveComponentMetadataFactory::propertiesFor()` recurses into parents and `AsTwigComponent` reflects inherited methods.

### The alignment class rides the inner row, and the container is what the breadcrumb offset hangs off

The `Header` component renders two nested elements, Filament's own shape: `sy-pa-header-actions-ctn` (`flex shrink-0 items-center gap-3`) wrapping `sy-pa-header-actions` (`flex flex-wrap items-center gap-3`).
The alignment class, `sy-align-<value>` off `getHeaderActionsAlignment()`, is stamped on the **inner** row alone.

The container is not decoration: the `sm:mt-7` a page with breadcrumbs needs sits on it, because the offset has to push the whole actions block down past the trail rather than push the buttons apart inside their row.
The header's own `end` arm is `flex-row justify-end` rather than Filament's `flex-row-reverse`, so the declaration order of the actions survives the alignment.

### `getPageClasses()` is the only source of a per-page root class

Every per-page class on the page root comes from `getPageClasses()`, spread into the root `attrs()` beside `sy sy-pa-page`.
No resource partial hard-codes its own: the resource pages override the method instead (`ListRecords` answers `['sy-pa-resource-list-records-page', 'sy-pa-resource-<slug>']`, `EditRecord` adds `'sy-pa-resource-record-<key>'`), the slug arm being Filament's `fi-resource-<slug>`.
A hand-written page returning `['sy-pa-settings-page']` gets the same scoping hook with no template of its own.

The record key goes into that token **raw**, and for a **composite** key that is safe by construction: the [key grammar](../schemas/record-key-contract.md#the-grammar) admits `[A-Za-z0-9_-]`, `~` and `=` and no whitespace at all, so a composite key cannot split one class token into two.
That is the whole of what the grammar closes.
A key of **one** identifier field is the flattened value verbatim, with no escaping and no alphabet check, so a single `string` identifier holding whitespace still splits the token; and so does a resource declaring `$recordRouteKeyName`, which stamps an arbitrary property [without passing it through the codec at all](resource.md#a-custom-recordroutekeyname-names-one-column-and-the-codec-never-sees-it).
Only `EditRecord` and `ManageRelatedRecords` emit the token (a custom record page inherits `Pages\Page`'s empty `getPageClasses()` and has none), and nothing in the toolkit's CSS, JavaScript or Twig selects it, so `~` and `=` inside it cost nothing either.

### A page with no body attributes still renders a stray space

`getExtraBodyAttributes()` is read off the **un-mounted** page by [`PageController`](routing.md), like `getTitle()`, and passed to the Document as `bodyAttributes`.
The Document renders `<body {{ attrs(bodyAttributes) }}>` unconditionally, so a page that adds nothing emits `<body >`, a single stray space inside the tag.
It is valid HTML and every parser ignores it; guarding it would mean an `{% if %}` around an attribute expression that is already a no-op, so the space is accepted rather than spelled around.

### `refresh` is a listener, and therefore also a no-op action any client can call

`#[LiveListener('refresh-page')] public function refresh(): void {}` ports Filament's `#[On('refresh')]` no-op one for one: the body is empty because the re-render *is* the effect.
UX's `LiveListener` extends `LiveAction`, so the method is also a callable live action: any client can post `refresh` and get a free re-render of the page.
Nothing in the toolkit dispatches `refresh-page`; the seam exists so a nested component (a relation manager, a widget) can ask its host page to redraw.

### `getPanel()` reads the sub-navigation trait's private context

`getPanel()` answers `$this->currentPanel?->get() ?? $this->panels?->getDefault()` and throws a `LogicException` when a page built with `new` has neither.
Both services belong to [`HasSubNavigation`](has-sub-navigation.md), which the base composes and which sets them through its own `#[Required]` setter, and a trait's **private** members belong to the composing class, so `Pages\Page` reads them directly instead of injecting a second `CurrentPanel`.
It is also why `getPanel()` can only live on `Pages\Page` itself and never on a subclass: a subclass cannot see the trait's private properties.

### The stamp runs above actions' own hydration hook, and the priority is the whole mechanism

`#[PostHydrate(priority: 20)] stampPanelRequestAttribute()` writes the page's `panelId` back onto the current request.
Twenty is not decorative: actions' `afterMountedActionsHydrated()` runs at **10** and re-resolves every mounted action, rebuilding the header list and minting the URLs inside it.
UX orders higher priorities first, so the stamp must precede it or those URLs answer for the default panel.
`PageRenderTest`'s *"stamps the panel before a mounted action rebuilds the header, not after"* is the falsifiable form of that sentence: it mounts and calls an action on the non-default **probe** panel and reads the header link's `href` off the response.

The prop is minted by the getter its dehydration read runs (`$this->panelId ??= $this->currentPanel?->get()?->getId()`) rather than in a mount, because a page's `mount()` signature is the page's own and the base has none.
That closes the page half of the [multi-panel URL divergence](resource.md#url-generation-inside-a-live-action-resolves-the-panel-the-page-was-opened-in); a nested live component's own request stays open.

### `getResourceUrl()` fills the record and threads the parent chain

`Resources\Pages\Page::getResourceUrl(?string $name = null, array $parameters = [], …)` is the instance-side URL helper a custom record page reaches for.
When `$name` is filled, is not `'index'`, and the page has a `getRecordKey()` (that is, composes [`InteractsWithRecord`](interacts-with-record.md)), it defaults `$parameters['record']` to this page's own key; then it delegates to `Resource::getUrl()` with the page's `parentRecords` spread ahead of the caller's parameters, exactly as every other URL arm on the base does.

Both halves are what make `$this->getResourceUrl('edit')` a complete call on a nested page: the record comes off the page and the ancestors come off the chain, so nothing raises `MissingMandatoryParametersException`.
The **static** `Resource::getUrl()` family is [the recorded wrong-answer surface](resource.md#resources-are-collected-from-a-tag-not-discovered-on-the-filesystem) for a page that has a record in hand.

### A custom resource page is a `mount()`, a static and a body

A resource page carrying a `{record}` needs nothing new: routing, mount, scoped resolution, breadcrumbs, the band and the panel stamp all work.
The recipe is four declarations.

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

It is registered on the resource as `'preview' => PreviewArticle::route('/{record}/preview')` and, to join the record band, listed in `Resource::getRecordSubNavigation()`, explicit in Filament too.
Four things the shape depends on:

- **The `mount()` is not optional and the base declares none.**
  `Pages\Page` has no `mount()`, so a page that forgets it silently receives no record and ux paints `record` onto the live root as an HTML attribute.
  The [loader guard](routing.md#the-loader-refuses-a-placeholder-the-page-cannot-mount-which-filament-does-not) turns that into a build-time `LogicException` instead.
- **`InteractsWithRecord` is composable on a resource page only.**
  It reaches `getResourceInstance()` for the scoped binding, which a plain `Pages\Page` does not have.
- **`getRecordTitle()` is the only pre-mount-safe title reader.**
  `PageController` reads `getTitle()` off an **un-mounted** page for the document `<title>`, so a `getTitle()` override that calls `getRecord()` 404s every GET of the page, [the trap the edit page's own guard exists for](edit-record.md#the-document-title-of-an-edit-page-carries-the-record-label-not-the-record-title).
- **The body is either a `content()` override or a child template.**
  The fixture [`PreviewProbePage`](../../../packages/panels/tests/Fixtures/Resources/Pages/PreviewProbePage.php) takes the template route, extending the base chrome and filling `{% block content %}` with markup that reads `this.getRecord`.

## Divergences

### `getView()` and `getViewData()` have no counterpart, because a page names a template

Filament's `BasePage` renders through `$view` plus `getView()` / `getViewData()`, a Blade view name and a variable bag the page's own `render()` merges.
Ours renders through UX LiveComponent's `template_from_method: getTemplate`, which takes a template **name** and hands the component itself to Twig as `this`, so there is no bag to fill: a template reads whatever it needs off `this` by calling the page's own getters.
`$template` and `getTemplate()` are the port of `$view` and `getView()` under the standing Blade-view → Twig-template [idiom translation](../translations.md#idiom); `getViewData()` has nothing left to do and is not ported.

The same reasoning covers `getLayoutData()`'s third key, [recorded on `SimplePage`](simple-page.md#getlayoutdata-answers-two-keys-where-filaments-answers-three), the only class that fills it.

### A third shell layout is a `Document` override, not a `getLayout()` value

`getLayout()` returns a **Twig component name**, where Filament's `$layout` is a Blade view name, and `PageController` accepts exactly two values, `Page::LAYOUT` (`'SylamentPanels:Layout'`) and `SimplePage::LAYOUT` (`'SylamentPanels:SimpleLayout'`), throwing a `LogicException` naming the page and its value for anything else.

The narrowness is forced by the Document template, which branches on the string with two literal `<twig:>` tags rather than calling the `component()` function: a function call cannot take a `<twig:block>`, and the page has to be handed to the shell as a block.
So a consumer wanting a third shell overrides `@SylamentPanels/partials/Document/index.html.twig`, the one seam that is already a plain Twig template a Symfony application can override by path, and the failing `getLayout()` value is the loud form of that instruction.

The Document's branch tests the **string literal** `'SylamentPanels:SimpleLayout'` rather than `SimplePage::LAYOUT`, because Twig has no constant import; the controller's `in_array()` guard is *not* what pins the two sides together, because both compare against the same constants and would move as one.
What pins them is [`PageControllerTest`](../../../packages/panels/tests/Integration/Controllers/PageControllerTest.php)'s `/probe/simple` case, which requests the URL and asserts the rendered `body > .sy-pa-simple-layout`: a rename on either side fails there.

### `halt()`'s rollback flag is carried and ignored

`halt(bool $shouldRollbackDatabaseTransaction = false)` throws support's `Halt` with the flag set on the exception, and both record pages wrap their hook ladder in `try { … } catch (Halt) { return null; }`.
Nothing reads the flag: page-level `CanUseDatabaseTransactions` is not ported, so there is no transaction to roll back and no honest place to consult it.
It is carried rather than dropped so that porting the transactions concern is additive: the exception already tells it what to do.

What the catch *does* fix is real: without it a `Halt` thrown from a `beforeSave` hook escapes `save()` and answers 500.
The [`EditHookedArticle`](../../../packages/panels/tests/Fixtures/Resources/Pages/EditHookedArticle.php) fixture's `$haltsBeforeSave` arm pins the ladder stopping where the hook threw (no mutator, no write, no notification, no redirect, and the row untouched); the create page's arm is [its own row](create-record.md#no-test-halts-the-create-ladder).

### Header actions are bound lazily rather than at boot

Filament caches header actions into the page's action registry at boot, through three hops: Livewire's `bootedInteractsWithActions()` (`vendor/filament/actions/src/Concerns/InteractsWithActions.php:120`) calls `cacheTraitActions()` (`:542`), which `class_uses_recursive()`-walks the class for every `cache<TraitBasename>` method and so reaches `cacheInteractsWithHeaderActions()` (`vendor/filament/filament/src/Pages/Concerns/InteractsWithHeaderActions.php:18`).
That last one walks `getHeaderActions()`, stamps `->livewire($this)` on each group, sets a dropdown placement, and registers every action through `cacheAction()`; Blade then renders `getCachedHeaderActions()`, the already-bound list.
UX LiveComponent has no `booted*()`/`cache*()` trait hook to hang that on, so [`Pages\Concerns\InteractsWithHeaderActions`](../../../packages/panels/src/Pages/Concerns/InteractsWithHeaderActions.php), composed by [`Pages\Page`](page.md) so **every** panel page has it, does the same work **lazily**, at whichever of the two reads comes first.
[`InteractsWithFormActions`](form-actions.md) carries the identical walk for the form row one list over.

`getCachedHeaderActions()` is that walk.
It memoises into a `?array $cachedHeaderActions`, set to `[]` *before* `getHeaderActions()` is called so a declaration that itself reaches `getAction()` cannot recurse, stamps every `ActionGroup` with `liveComponent($this)` (which propagates to its members) and `cacheAction()`s every plain entry and every `getFlatActions()` member of a group, then keeps and returns the declared list.
`cacheAction()` does both halves in one call: it stamps `liveComponent($this)` *and* registers the action under its own name.

The two reads are the two directions, and each needs a different half:

- **Out**, at render: the base page chrome reads `this.getCachedHeaderActions` rather than `this.getHeaderActions`, once, for every page that extends it, because the *binding* is what a trigger renders from.
  `CanOpenUrl::getUrl()` reaches the page through `getLiveComponentOrNull()`, so an unbound `CreateAction` renders as a `<button>` with no href instead of an `<a>` to the resource's create page, and `getDefaultActionRecordClass()`, `getDefaultActionRecordLabel()` and `getDefaultActionRecordTitle()` are skipped with it.
  No error anywhere, which is why the shipped fixture pins the rendered `href`.
- **In**, when the browser posts a mount back: `getAction()` is overridden on the base to try the trait's own lookup first (the action cache, then [actions' method discovery](../actions/has-actions.md), which finds a `<name>Action()` or `<name>()` method returning an `Action`) and, **only when that misses**, to run the header and form walks once and look again.
  Render-time reading alone could never cover this: the request that *calls* a mounted action renders no header at all, so nothing would have populated the cache by the time the name was looked up.

So every declaration form works and none of them is stamped by hand.
`$this->fooAction()` listed in `getHeaderActions()` is the Filament shape and resolves through discovery; an action built **inline** in the list resolves too, and renders its URL, because the list the template renders is the bound one.
A table **record** action needs none of this either way: `BelongsToLiveComponent` falls through the action's table to the table's host.

The walk also stamps `dropdownPlacement('bottom-end')` on an `ActionGroup` whose placement is null, Filament's own default, so a bare header group opens under the end of its trigger rather than wherever the component default put it.

The residue is the memoisation's scope.
Filament's cache is per boot and ours is per request object, which is the same thing for a page, but a `getHeaderActions()` that returns a *different* list depending on state read after the first call would be read once and frozen, where Filament would have frozen it at boot instead.
Both are caches of a declaration, and a declaration that varies mid-request is out of contract in either framework.

### `getHeaderActions()` is public, so a page copying Filament's protected override fatals

Filament declares `getHeaderActions()` `protected`; the port's `Pages\Concerns\InteractsWithHeaderActions::getHeaderActions()` is `public`.
PHP refuses to narrow visibility in an override, so a user copying Filament's `protected function getHeaderActions()` gets a fatal at class load rather than a silently empty header.
The trait's docblock gives the template as the reason, but the page chrome reads `this.getCachedHeaderActions`, so nothing outside the class reads the hook; declaring it `protected`, as Filament does, would reverse this.
