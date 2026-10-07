---
description: 'The row of buttons below a page''s form: InteractsWithFormActions, the form and table template variants, and the record pages'' Save, Create and Cancel actions.'
ports:
  - vendor/filament/filament/src/Pages/Concerns/InteractsWithFormActions.php
  - vendor/filament/filament/src/Resources/Pages/CreateRecord.php
  - vendor/filament/filament/src/Resources/Pages/EditRecord.php
  - vendor/filament/filament/src/Pages/BasePage.php
owns:
  - packages/panels/src/Pages/Concerns/InteractsWithFormActions.php
  - packages/panels/templates/partials/EditRecord/
  - packages/panels/templates/partials/Page/form.html.twig
---
# Form actions (panels)

[`InteractsWithFormActions`](../../../packages/panels/src/Pages/Concerns/InteractsWithFormActions.php) is the row of buttons below a page's form: the declaration hook, the bound and memoised list, the one `Actions` component that carries it, the named `formActions` page schema the template renders it through, the submit method name both the `<form>` element and the submit action read, and the process-global alignment statics.
Beside it sit the two template variants the concern exists for, [`partials/Page/form.html.twig`](../../../packages/panels/templates/partials/Page/form.html.twig) and [`partials/Page/table.html.twig`](../../../packages/panels/templates/partials/Page/table.html.twig), and the create and edit pages' own `getFormActions()`, which declare the Save, Create and Cancel buttons the toolkit ships.

It ports [`Pages/Concerns/InteractsWithFormActions.php`](../../../vendor/filament/filament/src/Pages/Concerns/InteractsWithFormActions.php) by name only: nothing in Filament composes that trait (it carries `@phpstan-ignore trait.unused`), and every real consumer hand-rolls its own protected `getFormActions()` and helpers, so the port keeps the name and fills it with the shape those consumers share.
The alignment, sticky and inline-label statics live on Filament's `BasePage`, and here on the trait.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`getFormActions()`](../../../packages/panels/src/Pages/Concerns/InteractsWithFormActions.php) | the same, `protected` on every Filament consumer | done | — | — |
| [`getCachedFormActions()`](../../../packages/panels/src/Pages/Concerns/InteractsWithFormActions.php) + `$cachedFormActions` | no counterpart — Filament binds at boot through `cacheTraitActions()` | done | — | [diverges](custom-pages.md#header-actions-are-bound-lazily-rather-than-at-boot) |
| [`getFormActionsContentComponent()`](../../../packages/panels/src/Pages/Concerns/InteractsWithFormActions.php) | [`CreateRecord::getFormActionsContentComponent()`](../../../vendor/filament/filament/src/Resources/Pages/CreateRecord.php) | done | — | [semantics](#the-row-is-never-rendered-standalone-it-hangs-under-a-named-page-schema) |
| [`formActions(Schema): Schema`](../../../packages/panels/src/Pages/Concerns/InteractsWithFormActions.php) | no counterpart — Filament's row is a child of `content()` | done | — | [semantics](#the-row-is-never-rendered-standalone-it-hangs-under-a-named-page-schema) |
| [`hasFullWidthFormActions()`](../../../packages/panels/src/Pages/Concerns/InteractsWithFormActions.php) | the same | done | — | — |
| [`getSubmitFormLiveComponentMethodName()`](../../../packages/panels/src/Pages/Concerns/InteractsWithFormActions.php) | `getSubmitFormLivewireMethodName()` | done | — | [renamed](../translations.md#idiom) · [semantics](#one-method-name-drives-both-the-form-element-and-the-submit-action) |
| [`$formActionsAlignment`](../../../packages/panels/src/Pages/Concerns/InteractsWithFormActions.php) + `getFormActionsAlignment()` + `formActionsAlignment()` + `alignFormActionsStart()` / `Center()` / `End()` | the same six on [`BasePage.php`](../../../vendor/filament/filament/src/Pages/BasePage.php) | done | — | [semantics](#the-alignment-statics-are-process-global-exactly-as-filaments-are) |
| [`partials/Page/form.html.twig`](../../../packages/panels/templates/partials/Page/form.html.twig) — the `<form>`, `form_errors`, `renderSchema('form')`, `form_actions`, `after_form` | no counterpart — v5's create and edit pages have no view; `content()` returns a `Form` wrapping an `EmbeddedSchema` and the row | done | — | [diverges](#the-form-element-stays-in-twig-because-the-schemas-form-component-is-unported) |
| [`partials/Page/table.html.twig`](../../../packages/panels/templates/partials/Page/table.html.twig) — the tabs strip and `renderTable()` | same — `ListRecords::content()` returns an `EmbeddedTable` | done | — | [semantics](#the-table-variant-presumes-two-host-traits-and-says-so-only-by-failing) |
| [`partials/EditRecord/index.html.twig`](../../../packages/panels/templates/partials/EditRecord/index.html.twig) — the child of the form variant | same | done | — | [has-relation-managers.md](has-relation-managers.md#content-ships-in-a-narrow-shape) |
| [`CreateRecord::getFormActions()`](../../../packages/panels/src/Resources/Pages/CreateRecord.php) + `getCreateFormAction()` | the same two | done | — | — |
| [`EditRecord::getFormActions()`](../../../packages/panels/src/Resources/Pages/EditRecord.php) + `getSaveFormAction()` | the same two | done | — | [semantics](#one-method-name-drives-both-the-form-element-and-the-submit-action) |
| `getCancelFormAction()` on both record pages | the same, over `alpineClickHandler()` | done | — | [diverges](#the-cancel-action-links-to-the-index-where-filament-goes-back-in-history) |
| `inlineLabels()` / `hasInlineLabels()` | the pair on [`BasePage.php`](../../../vendor/filament/filament/src/Pages/BasePage.php) | skipped | — | [diverges](#inlinelabels-is-not-ported-because-nothing-reads-it) |
| `hasFormWrapper()` | the same | skipped | — | [diverges](#hasformwrapper-is-not-ported-because-the-form-element-is-the-variants) |
| `alignFormActionsLeft()` / `alignFormActionsRight()` | the two `@deprecated` aliases on [`BasePage.php`](../../../vendor/filament/filament/src/Pages/BasePage.php) | skipped | — | [deprecated for `alignFormActionsStart()` / `alignFormActionsEnd()`](../renames.md#renames) |
| `Actions::sticky()` on the row (`areFormActionsSticky()`, `stickyFormActions()`) | the pair on [`BasePage.php`](../../../vendor/filament/filament/src/Pages/BasePage.php) | blocked | the Stimulus sticky controller | [schemas/actions.md](../schemas/actions.md#sticky-mode-is-not-ported) |
| `keyBindings(['mod+s'])` on the submit action | `getCreateFormAction()` / `getSaveFormAction()` | blocked | `HasKeyBindings` | [actions README](../actions/README.md) |
| `previousUrl` and the history-back cancel | `public ?string $previousUrl` | skipped | — | [diverges](manage-related-records.md#previousurl-is-not-ported-anywhere-in-the-port) |
| _rest not audited yet_ | — | — | — | — |

## Semantics

### The row is never rendered standalone; it hangs under a named page schema

`getFormActionsContentComponent()` returns `Actions::make($this->getCachedFormActions())->alignment(…)->fullWidth(…)->key('form-actions')`, and `formActions(Schema $schema): Schema` wraps that one component in a schema.
The template renders `{{ renderSchema('formActions') }}` — never the component on its own.
Three separate mechanics force that shape:

- **A keyed component needs a container.**
  The `Actions` partial reads `component.getId` on its first line, `getKey()` walks `getContainer()`, and `BelongsToContainer::$container` is a typed property no constructor fills, so a container-less `Actions` fatals on render.
- **A mounted row action resolves back through the schema's name.**
  The trigger's context reads `schemaComponent: formActions.form-actions`, which `HasActions::resolveSchemaComponentAction()` splits into the schema `formActions` and the nested key `form-actions`.
  A standalone `Actions` keyed `form-actions` would stamp `schemaComponent: form-actions`, name a schema that does not exist, and mount nothing — silently.
- **`formActions` is resolved by bare name, and never listed in `schemas()`** — the same arrangement [`content`](custom-pages.md#content-resolves-by-bare-name-so-an-empty-body-costs-a-live-request-nothing) uses, so the row costs a live request nothing when a page declares none.

Binding is separate from all three and comes first: `getCachedFormActions()` stamps `liveComponent($this)` and registers each action through `cacheAction()` before the component is built, and that explicit binding survives the `schemaContainer` stamp the child schema applies.
An `Actions::make([...])` built inside a template would bind to nothing at all, which is why the component is minted in PHP.

The two paths are used by different callers and both are needed.
The schema path above is what a **browser** mount travels; the form-list arm of [`Page::getAction()`](page.md#the-actions-host-sits-on-the-base-because-a-trait-outranks-an-inherited-method) serves `getAction()` callers by name, running the two walks once when the trait's own lookup misses.

### One method name drives both the `<form>` element and the submit action

`getSubmitFormLiveComponentMethodName()` returns `'save'` on the trait and `'create'` on `CreateRecord`.
The form variant binds `live_action(this.getSubmitFormLiveComponentMethodName ~ ':prevent', event: 'submit')`, and each page's submit action reads the **same** getter — `Action::make('save')->submit($this->getSubmitFormLiveComponentMethodName())` on `EditRecord`, and the create page's equivalent — rather than repeating the literal.
So a page overriding the getter moves both ends together; overriding one end alone is what would give it a button that posts to a method the form does not name.

`Action::submit()` renders `type="submit"` with the click handler suppressed, and needs no `formId` because the button sits inside the `<form>`.
That keeps Enter-to-submit and native constraint validation working, which a `->action('save')` button would not, and is why the submit arm rather than the action arm is the default the record pages ship.

`live_action()` is emitted as its own expression, outside the `attrs()` call: `ComponentAttributeBag::merge()` appends only `class` and `style`, so folding a `data-action` into an `attrs()` map would replace whatever was already there.

### The table variant presumes two host traits and says so only by failing

`partials/Page/table.html.twig` fills `content` with `this.getCachedTabs`, the tabs schema and `renderTable()` — so the page pointing `$template` at it must be both a [`HasTabs`](has-tabs.md) host and a `Tables\Contracts\InteractsWithTable`.
Both shipped pages that use it ([`ListRecords`](list-records.md) and [`ManageRelatedRecords`](manage-related-records.md)) are, and a page that is not fails at render on the missing method rather than at any earlier point.
The presumption is stated here rather than guarded in the template: a Twig-side `instanceof` check would only turn one loud failure into a silent empty page.

### The alignment statics are process-global, exactly as Filament's are

`public static string|Alignment $formActionsAlignment = Alignment::Start` and its four setters are statics declared once, through the trait, on `Pages\Page`, so `alignFormActionsEnd()` called on any page class moves every page that does not redeclare the static, for the rest of the process.
That is Filament's own shape, where the static sits on `BasePage`, and is kept; the cost is that a test setting one must reset it.

## Divergences

### The form element stays in Twig, because the schemas `Form` component is unported

Filament v5 gives its create and edit pages no view at all: `content()` returns a schema holding a [`Form`](../../../vendor/filament/schemas/src/Components/Form.php) component wrapping an `EmbeddedSchema` and the form `Actions`, and the `<form>` element is that component's own markup, with `livewireSubmitHandler()` naming the action.
`Form` is [todo in schemas](../schemas/README.md), so the `<form>` here is written in Twig, in one variant the record pages share.

The variant is a strict subset rather than a different design: the same three blocks a `Form` component would expose (`form_errors`, `form_actions`, `after_form`) are named blocks, so a page customising one keeps that override when the component lands and the template's inner half is replaced.
The record-level error outlet inside `form_errors` is required rather than decorative — a class-level violation that names no field folds onto the schema's own state path, which belongs to no input, so without it a refused submit appears to do nothing.

### The cancel action links to the index where Filament goes back in history

Filament's `getCancelFormAction()` is `alpineClickHandler('document.referrer ? window.history.back() : (window.location.href = …)')` — a client-side arm that returns the user to wherever they came from.
The port has no seam for an inline JS click handler on an action, so both record pages hand the cancel action a plain `url($this->getResourceInstance()->getIndexUrl([...$this->parentRecords]))` instead: an ordinary `<a>` to the resource's listing, parent keys threaded so a nested page lands on the nested listing.
Filament's `previousUrl` prop, the other half of the same idea, [is not ported anywhere](manage-related-records.md#previousurl-is-not-ported-anywhere-in-the-port).

The visible consequence is narrow: cancelling from a create page reached through a modal or a deep link goes to the listing rather than back one step.
It is also what makes the button a link rather than a button, so middle-click and open-in-new-tab work where Filament's do not.

**The default cancel URL fails fast on a resource that registers no index page.**
`Resource::getIndexUrl()` mints the `index` route, and a resource without one raises a `LogicException` naming the resource and pointing at `getIndexUrl()` the moment the form row is built — before any route is consulted.
Filament fails identically — its `CanGenerateUrls::getUrl(null)` fails on the same missing route, there as a `RouteNotFoundException` — so this is parity rather than a new constraint, but it is the one shape a custom record page hits: a page whose resource has no listing overrides `getFormActions()` to return the submit action alone.

### `inlineLabels()` is not ported, because nothing reads it

Filament's `BasePage` carries `inlineLabels(bool)` / `hasInlineLabels()`, a page-level default its record pages hand to `Schema::inlineLabel()`.
`Schema::inlineLabel()` has no port here, so a setter on the page would write a flag nothing consults.
It returns with the schema-level surface, not before.

### `hasFormWrapper()` is not ported, because the form element is the variant's

Filament's flag lets a page render its form schema **without** the surrounding `<form>`, for a page whose submit lives elsewhere.
Here the `<form>` is the template variant's, not the schema's: a page that wants no form element points `$template` at the base chrome instead of the form variant and renders its schema from its own `content` block.
That is the same capability reached through a seam the port has, so a second flag would be a second way to say it.
