---
description: The schemas bundle class, its asset workspace and utilities, the icon aliases, and the Stimulus controllers that replace Filament's schemas Alpine scripts.
ports:
  - vendor/filament/schemas/src/SchemasServiceProvider.php
  - vendor/filament/schemas/src/View/SchemaIconAlias.php
  - vendor/filament/schemas/resources/js/index.js
  - vendor/filament/schemas/resources/js/components/actions.js
  - vendor/filament/schemas/resources/js/components/tabs.js
  - vendor/filament/schemas/resources/js/components/wizard.js
owns:
  - packages/schemas/assets/src/controllers/
  - packages/schemas/assets/src/utils/cloak.ts
  - packages/schemas/assets/src/utils/live_host.ts
  - packages/schemas/assets/src/utils/schema_component_method.ts
  - packages/schemas/src/SylamentSchemasBundle.php
  - packages/schemas/src/Twig/SchemaIconAlias.php
---
# SylamentSchemasBundle (schemas)

[`Sylament\Schemas\SylamentSchemasBundle`](../../../packages/schemas/src/SylamentSchemasBundle.php) ports Filament's [`SchemasServiceProvider`](../../../vendor/filament/schemas/src/SchemasServiceProvider.php) (recorded rename `SchemasServiceProvider` → `SylamentSchemasBundle`; Spatie's `PackageServiceProvider` lifecycle becomes a Symfony `AbstractBundle` via [`SylamentBundle`](../../../packages/support/src/SylamentBundle.php)).
It registers the components and `Schema` as autowired services, the two colour policies, the [`SchemaExtension`](schema-extension.md) Twig extension, the state [`PropertyAccessor`](property-accessor.md), the [`RecordTypeReader`](record-type-reader.md) and the [`AssociationReader`](association-reader.md), maps the built JS under `@sylament/schemas`, and inherits the `@SylamentSchemas` Twig namespace by AbstractBundle/TwigBundle convention.
The reader rows live in their own files.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [SchemaExtension → `twig.extension`](../../../packages/schemas/src/SylamentSchemasBundle.php) | [`configurePackage()`](../../../vendor/filament/schemas/src/SchemasServiceProvider.php) | done | — | — |
| [state PropertyAccessor service](../../../packages/schemas/src/SylamentSchemasBundle.php) | — | done | — | [diverges](#the-bundle-wires-a-state-propertyaccessor-that-filaments-provider-never-had) |
| [`Components/`, `Tabs/`, `Wizard/` and `Schema` as autowired services](../../../packages/schemas/src/SylamentSchemasBundle.php) | — | done | — | [component.md](../support/component.md#newinstance-routes-every-make-through-a-locator) |
| [`Colors/Components/` policies as public services](../../../packages/schemas/src/SylamentSchemasBundle.php) | — | done | — | — |
| [`@SylamentSchemas` view namespace](../../../packages/schemas/src/SylamentSchemasBundle.php) | [`->hasViews()`](../../../vendor/filament/schemas/src/SchemasServiceProvider.php) | done | — | [diverges](#the-view-namespace-is-registered-by-symfony-convention-not-by-an-explicit-provider-call) |
| [Asset registration](../../../packages/schemas/src/SylamentSchemasBundle.php) (the `@sylament/schemas` workspace + `prependExtension()`) | [`packageBooted()`](../../../vendor/filament/schemas/src/SchemasServiceProvider.php) | done | — | [semantics](#the-asset-prepend-needs-the-built-dist-to-exist) · [diverges](#the-schemas-package-has-a-js-workspace-of-its-own) |
| [`sylament--schemas--schema`](../../../packages/schemas/assets/src/controllers/schema_controller.ts) — the root schema's validation-error reveal and client-state reset | [`resources/js/index.js`](../../../vendor/filament/schemas/resources/js/index.js) (`filamentSchema`) | done | — | [diverges](#the-controllers-are-the-alpine-data-objects-rewritten-as-stimulus) · [semantics](#no-test-in-this-repo-runs-the-controllers) · [contract](#the-controllers-values-targets-and-actions) |
| [`sylament--schemas--tabs`](../../../packages/schemas/assets/src/controllers/tabs_controller.ts) — the active tab, the overflow dropdown, the deferred badges | [`resources/js/components/tabs.js`](../../../vendor/filament/schemas/resources/js/components/tabs.js) | done | — | [diverges](#the-controllers-are-the-alpine-data-objects-rewritten-as-stimulus) · [semantics](#no-test-in-this-repo-runs-the-controllers) · [contract](#the-controllers-values-targets-and-actions) |
| [`sylament--schemas--wizard`](../../../packages/schemas/assets/src/controllers/wizard_controller.ts) — the active step, per-step validation on next | [`resources/js/components/wizard.js`](../../../vendor/filament/schemas/resources/js/components/wizard.js) | done | — | [diverges](#the-controllers-are-the-alpine-data-objects-rewritten-as-stimulus) · [diverges](#two-deliberate-deltas-from-vendors-runtime-behaviour) · [semantics](#no-test-in-this-repo-runs-the-controllers) · [contract](#the-controllers-values-targets-and-actions) |
| [`utils/schema_component_method.ts`, `utils/live_host.ts`, `utils/cloak.ts`](../../../packages/schemas/assets/src/utils/schema_component_method.ts) | — | done | — | [diverges](#the-schema-component-method-helper-and-two-utilities-live-in-schemas-assets) |
| `actions` AlpineComponent (the sticky actions row) | [`resources/js/components/actions.js`](../../../vendor/filament/schemas/resources/js/components/actions.js) | todo | — | [actions.md](actions.md#sticky-mode-is-not-ported) |
| [`SchemaIconAlias`](../../../packages/schemas/src/Twig/SchemaIconAlias.php): `COMPONENTS_TABS_DROPDOWN_TRIGGER_BUTTON`, `COMPONENTS_TABS_MORE_TABS_BUTTON`, `COMPONENTS_WIZARD_COMPLETED_STEP` | [`View/SchemaIconAlias.php`](../../../vendor/filament/schemas/src/View/SchemaIconAlias.php) | done | — | [diverges](#the-icon-alias-values-drop-filaments-schema-prefix) |
| `SchemaIconAlias::COMPONENTS_CALLOUT_DANGER` / `_INFO` / `_SUCCESS` / `_WARNING` | [`View/SchemaIconAlias.php`](../../../vendor/filament/schemas/src/View/SchemaIconAlias.php) | blocked | the unported `Callout` component | — |
| `composer.json` requires `sylament/actions` | — | in progress | — | [detail](#no-test-pins-the-sylamentactions-require) |
| `Make*` commands + stub publishing | [`configurePackage()` / `packageBooted()`](../../../vendor/filament/schemas/src/SchemasServiceProvider.php) | todo | — | [detail](#the-make-commands-and-stub-publishing-are-not-ported) |
| Translations (`hasTranslations`) | [`configurePackage()`](../../../vendor/filament/schemas/src/SchemasServiceProvider.php) | todo | — | [detail](#the-translations-namespace-is-not-registered) |
| `validate` / `validateOnly` call abort (`on('call')`) | [`packageBooted()`](../../../vendor/filament/schemas/src/SchemasServiceProvider.php) | skipped | — | [diverges](#livewire-specific-provider-responsibilities-are-dropped-not-ported) |
| File-upload restriction hook (`on('call')` abort) | [`packageBooted()`](../../../vendor/filament/schemas/src/SchemasServiceProvider.php) | skipped | — | [diverges](#livewire-specific-provider-responsibilities-are-dropped-not-ported) |
| `Testable::mixin(new TestsSchemas)` | [`packageBooted()`](../../../vendor/filament/schemas/src/SchemasServiceProvider.php) | skipped | — | [diverges](#livewire-specific-provider-responsibilities-are-dropped-not-ported) |

## Details

### No test pins the `sylament/actions` require

[`packages/schemas/composer.json`](../../../packages/schemas/composer.json) requires `sylament/actions`, because schemas' own source imports `Action` and `ActionGroup` throughout the schemas↔actions integration; the reverse edge and Filament's matching cycle are recorded in [actions-bundle.md](../actions/actions-bundle.md#every-package-declares-what-its-own-source-needs-transitives-notwithstanding).
The monorepo resolves every package out of one root `vendor/`, so dropping the line fails nothing in the schemas suite and breaks only the split repository.
What is missing is a manifest test in the schemas suite asserting the require, the shape actions' [`ManifestTest`](../../../packages/actions/tests/Architecture/ManifestTest.php) has.

### The `Make*` commands and stub publishing are not ported

`MakeComponentCommand` / `MakeLivewireSchemaCommand` / `MakeSchemaCommand` and the `stubs/` publishing loop have no counterpart.
Next step: port them with the `Commands/` subtree.

### The translations namespace is not registered

`->hasTranslations()` registers Filament's `resources/lang` namespace, and the bundle registers no translation domain.
Next step: port it with the translations subtree.

## Semantics

### The asset prepend needs the built `dist` to exist

Filament's `packageBooted()` calls `FilamentAsset::register([...])` with the schemas `index.js` plus the `actions`/`tabs`/`wizard` `AlpineComponent`s.
Sylament's translation is a `prependExtension()` that registers `packages/schemas/assets/dist` as `@sylament/schemas`, mirroring [`SylamentFormsBundle`](../../../packages/forms/src/SylamentFormsBundle.php)'s and guarded by [`isAssetMapperAvailable()`](../../../packages/support/src/SylamentBundle.php) so a consumer without AssetMapper is not forced to install it.
The hook and the built `dist` must stay together: `AssetMapperRepository::getDirectories()` throws `InvalidArgumentException` in debug for an absolute path that does not exist, and in prod registers a bogus `0 => '@sylament/schemas'` entry — there is no skip-if-missing branch, and git cannot track an empty directory.

### The `expand` walk, and the two window events either side of it

Three event names cross the PHP/JS boundary here, and only one of them is a `@window` event in both directions.

- **`form-validation-error@window`** — dispatched by the host after a failed `validateSchema()`, with no payload the controller reads.
  The root controller checks that `event.target` is its own `[data-controller~="live"]` root, then `queueMicrotask`s (the 422 re-render has to land first) and works on the first `[data-validation-error]` in its subtree: the `expand` walk, then a 200 ms `setTimeout` scrolling `error.closest('[data-field-wrapper]') ?? error` into view.
  The `?? error` is ours — vendor calls `.scrollIntoView()` straight off a `closest()` that can be `null`.
- **`expand`** — the reveal itself, and the one that is neither a `@window` event nor a bubbling one.
  The root controller walks `error.parentNode` to the top of the document and dispatches a **fresh, non-bubbling** `CustomEvent('expand')` on every node on the way (vendor's shape in `index.js`).
  Every ancestor that can conceal answers it on its own element with a `data-action` of its own — a Tab panel with `expand->sylament--schemas--tabs#expand`, a Wizard step panel with `expand->sylament--schemas--wizard#expand`, and support's Section with `expand->sylament--support--section#reveal`.
  Per-node dispatch rather than one bubbling event is what keeps the listener on the concealing element itself: a bubbling `expand` would also fire those same handlers for any *other* descendant that dispatched one.
- **`reset-schema-component-state@window`** — dispatched by the host after a client-side state reset, carrying `{schemaKey}`.
  The root, tabs and wizard controllers listen, each with the same two-part guard (`event.target` is its own live root, and `detail.schemaKey` equals its `schemaKey` value): the root controller refocuses the first visible `[autofocus]` field, the tabs controller returns to `activeTab` (unless the tab is persisted or in the query string), and the wizard controller returns to `startStep` (unless it is in the query string).

### No test in this repo runs the controllers

The [`checkbox-list` note](../forms/checkbox-list.md#the-controller-is-the-only-behaviour-and-no-test-in-this-repo-runs-it) applies verbatim: there is no JS test rig, so the controllers' verification is a reviewer's method-by-method diff against vendor's `tabs.js`, `wizard.js` and `index.js`, plus the demo's browser pass.
What *is* pinned from PHP is everything either side of them — the DOM contract the templates emit (the Tabs, Wizard and Schema render tests) and the AssetMapper path the built files resolve through.

### A morph reverts the wizard's per-step status text

The wizard's per-step status text (`Completed` / `Not completed`, `sr-only`) is written with `textContent`, and UX Live's `ExternalMutationTracker` observes `childList`/`attributes` but **not** `characterData`, and keeps only `Element` nodes from a childList mutation — so a morph that re-renders the header reverts the status text to the server's start-step values while the classes, `aria-current` and `disabled` (all attributes) survive.
`headerStepTargetConnected` repairs it whenever the morph *replaces* the `li`, not when it morphs it in place.
It is recorded rather than worked around.

## Divergences

### The bundle wires a state PropertyAccessor that Filament's provider never had

`SylamentSchemasBundle` registers `sylament_schemas.property_accessor` (aliased to [`State\PropertyAccessor`](../../../packages/schemas/src/State/PropertyAccessor.php)) — a service with no counterpart in `SchemasServiceProvider`.
It exists because Sylament reads and writes component state through Symfony's PropertyAccess component instead of Livewire's data entanglement.
This is bundle-visible fallout of the state-layer divergence; the accessor itself is audited under [property-accessor.md](property-accessor.md).

### The view namespace is registered by Symfony convention, not by an explicit provider call

Filament calls `->hasViews()` to register its Blade namespace.
Sylament registers nothing in the bundle file: `AbstractBundle::getPath()` returns `packages/schemas`, and TwigBundle auto-maps `packages/schemas/templates` → `@SylamentSchemas`.
The wiring is therefore invisible in the bundle — intentional, and the reason component templates can reference `@SylamentSchemas/...` with no explicit twig path config.

### Livewire-specific provider responsibilities are dropped, not ported

The provider's `Testable::mixin(new TestsSchemas)` and its two `on('call', ...)` hooks are Livewire-runtime concerns.
The Testable mixin is a deliberate non-goal (Sylament tests are Pest/`KernelTestCase`, not Livewire `Testable`).
The first hook aborts a browser call to `validate` / `validateOnly` on a schema host, because Livewire lets the client call any public method; UX Live calls only a `#[LiveAction]`, and [`HasSchemas`](has-schemas.md)' validation methods are not live actions, so there is nothing to fence.
The second hook aborts unauthorized `_startUpload` / `_finishUpload` / `_uploadErrored` / `_removeUpload` calls; uploads reach a schema component through a single `#[LiveAction]`, [`uploadSchemaComponentFile()`](interacts-with-schemas.md#uploadschemacomponentfile-carries-the-bytes-beside-the-seam), which is gated on the `AcceptsUploadedFiles` contract and reads one named multipart part.
There are no `_startUpload` / `_finishUpload` / `_uploadErrored` / `_removeUpload` endpoints to abort, so the hook — and `RestrictsFileUploadsToSchemaComponents`, the flag it reads — fence a surface that does not exist here.

### The schemas package has a JS workspace of its own

`packages/schemas/assets/` is a pnpm workspace (`@sylament/schemas`, `private`), and it is where the schemas Stimulus controllers live.
Filament's equivalent JS ships from `filament/schemas` too, so the placement is parity — and the split follows the seams: support ships the chrome (`Tabs`/`TabsItem`/`LoadingIndicator` + `tabs.css`), and the *switching* controller has to be here, because deferred badges go through the schemas seam and support must not import schemas' helper.

- **`scripts/build-js.mjs` is a byte-for-byte copy of actions' with an empty `BUNDLED_ENTRIES`** — the package bundles no third-party library, so every `src/**/*.ts` builds per-file and unbundled, import specifiers shipping verbatim to the browser.
  `@hotwired/stimulus` and `@symfony/ux-live-component` are peer **and** dev dependencies (the actions/forms shape: `autoInstallPeers: false` and `strictPeerDependencies: true` mean `tsc` only sees a peer that is also a dev dependency), and both stay bare specifiers the consuming app's importmap resolves.
- **`package.json` declares an `exports` map.**
  `{"./utils/*.js": "./src/utils/*.ts"}` is what lets forms import `@sylament/schemas/utils/schema_component_method.js`: under `moduleResolution: "Bundler"` TypeScript resolves the subpath through `exports` and lands on the `.ts` source, and esbuild does the same, so **schemas' `dist` need not exist for forms to typecheck or bundle**.
  The specifier keeps the house `.js` extension even though `useImportExtensions` governs only relative imports.
- **`.gitattributes` export-ignores `/assets/scripts`** beside the `/assets/src` and `/assets/tsconfig.json` lines, mirroring forms'.
  `assets/package.json` is deliberately **not** export-ignored — StimulusBundle's `UxPackageReader` reads its `symfony` key out of the installed composer package.
  The `"@sylament/schemas": "workspace:*"` devDependency that reaches the split `sylament/forms` archive is inert: nobody `npm install`s a Sylament package, the reader only `json_decode`s and reads `symfony`, and both packages are `private`.
- **A consuming app opts the controllers in** through its `assets/controllers.json`:

  ```json
  "@sylament/schemas": {
      "schema": { "enabled": true, "fetch": "eager" },
      "tabs": { "enabled": true, "fetch": "eager" },
      "wizard": { "enabled": true, "fetch": "eager" }
  }
  ```

  All three are `fetch: eager` like every other Sylament controller — a lazily fetched tabs controller would leave the server's tab active until the chunk arrived, and `sy-cloak` hides the Tabs root meanwhile when `persistTab()` is on.

### The controllers are the Alpine data objects rewritten as Stimulus

Filament's schemas JS is four Alpine pieces: the `filamentSchema` root data in `index.js`, and the `tabs`/`wizard`/`actions` `AlpineComponent`s.
The first three are ported here, method by method (with [two named deltas](#two-deliberate-deltas-from-vendors-runtime-behaviour)), keeping vendor's names wherever a name survives the translation (`updateTabsWithinDropdown`, `findOverflowIndex`, `calculateAvailableWidth`/`ContainerGap`/`DropdownIconWidth`/`TabItemGap`/`TabItemPadding`, `isDropdownButtonVisible`, `getStepIndex` → `#stepIndex`, `isStepAccessible`, `goToNextStep`/`goToPreviousStep`/`goToStep`, `updateQueryString`, `autofocusFields` → `#autofocus`).
The systematic translations:

- **`x-data` state becomes private fields, `x-bind`/`x-show` become imperative writes.**
  Alpine re-evaluates its bindings when `tab`/`step` changes; Stimulus has no reactivity, so `#apply()` is the single place that writes every class, ARIA attribute and inline `display` for the current state.
  It is called from `connect()`, from every setter, and from the target-connected callbacks.
- **`$watch('tab')` / `$watch('step')` become the setter's own tail.**
  `#setTab()` is `#apply()` + `#updateQueryString()` + `#persistTab()` + `#autofocus()`; `#setStep()` is `#apply()` + `#updateQueryString()` + `#autofocus()` + `#scroll()` — the last of those being one of the [two deltas](#two-deliberate-deltas-from-vendors-runtime-behaviour), since vendor scrolls from its `goTo*` paths rather than from the assignment.
- **`$nextTick` becomes `queueMicrotask`** in the places vendor defers (the autofocus scan, the wizard's header scroll, the root controller's two handlers).
- **`Livewire.interceptMessage` becomes the target callbacks.**
  `tabTargetConnected`/`Disconnected` and `stepTargetConnected`/`Disconnected` re-validate the remembered key against what the server just rendered and fall back to `activeTab`/`startStep`, which is vendor's post-message hook without a message hook; `panelTargetConnected` and `headerStepTargetConnected` re-assert the state onto elements the morph *replaced* rather than morphed (UX Live's `ExternalMutationTracker` replays a client class/attribute change only onto a node it morphs in place).
  All four are inert until `connect()` has run, because Stimulus fires them for pre-existing targets *before* `connect()`.
- **`this.$refs.tabsData` / `stepsData` (a hidden input holding a JSON blob) is dropped.**
  The keys are read off the `tab`/`step` targets' `data-tab-key` / `data-step-key` in DOM order, so there is no second copy of the key list to keep in sync.
- **The `livewireId` half of every guard is dropped**, because no producer of a live component id exists on this side: `reset-schema-component-state` and `form-validation-error` are matched by `event.target === this.element.closest('[data-controller~="live"]')` — the host that dispatched is the host that owns this schema — plus, for `reset-schema-component-state`, the `schemaKey` payload it carries; `form-validation-error` is matched by the target alone.
- **`window.tippy` is dropped**: a deferred badge's `badgeTooltip` renders as a native `title`, the house tooltip shape.

Three details worth naming:

- **The deferred badge DOM is built by hand, then frozen.**
  The tabs controller calls `getDeferredTabBadges` once on connect through [`callSchemaComponentMethod`](#the-schema-component-method-helper-and-two-utilities-live-in-schemas-assets), and for each `badgePlaceholder` target replaces the loading indicator with support's Badge markup (`sy sy-badge sy-size-sm` + the server's colour classes and inline styles, `sy-badge-label-ctn`/`sy-badge-label` inside) — the badge text through `textContent`, only the server-generated `badgeIconHtml` through `insertAdjacentHTML`.
  The placeholder is then stamped `data-live-ignore` so later morphs leave the client-built DOM alone; an appended sibling would be duplicated instead, since UX Live inserts the server's node *after* a client-added one.
  A placeholder the morph replaces re-applies from the cached map through `badgePlaceholderTargetConnected`, and a rejected call empties every placeholder instead of leaving it spinning, which is what the `finally` in vendor's inline `x-data` does.
  `disconnect()` bumps a `#generation` counter so an in-flight answer to a disconnected controller is dropped.
  An entry's `badge` is read only when it is a string, matching Filament's `?string` typing of the property — a numeric badge would be dropped rather than coerced.
- **The overflow measurement runs synchronously.**
  Vendor's `updateTabsWithinDropdown()` awaits `$nextTick` before measuring, because Alpine has to flush the `x-show` bindings it just reset; ours writes the same inline `display` values itself, so it measures straight after `#apply()`.
  Everything else is vendor's: force every inline tab visible, read `offsetHeight` for the reflow, compute the four widths, walk `findOverflowIndex`, restore the original `display` strings, and debounce the window `resize` by 150 ms.
  It is skipped entirely without a `nav` and a `moreButton` target (i.e. under the default `scrollable(true)`), and the `nav` carries `sy-invisible` from `connect()` until the first measurement completes — added by the controller, never by the server, so a no-JS page keeps a visible nav.
- **Enter advances a wizard step.**
  Filament gets this from `alpineSubmitHandler()`/`formWrapper()`, which wrap a step in its own `<form>`; a nested `<form>` inside the host's is invalid HTML, so the behaviour is the controller's instead: a `keydown` Enter from an `<input>` whose `type` is in an allowlist (`text`, `email`, `number`, `password`, `search`, `tel`, `url`, `date`, `datetime-local`, `month`, `time`, `week`), inside the active step, on a non-last step, `preventDefault()`s and calls `requestNextStep()`.
  Textareas, buttons, checkboxes and `defaultPrevented`/`isComposing` events are left alone, and the last step falls through to the enclosing form's implicit submission.

### Two deliberate deltas from vendor's runtime behaviour

The controllers are otherwise method-by-method ports, so the places where the behaviour differs are worth naming rather than leaving to a diff:

1. **A client-state reset scrolls the wizard header.**
   `#scroll()` sits in `#setStep()`, so every step change scrolls the header `li` into view, the reset included; vendor's reset handler assigns `step` without scrolling, and only its `goTo*` paths scroll.
   Moving `#scroll()` out to the three `goTo*` callers is the vendor-exact shape if the difference ever matters.
2. **`#goToStep()` on an unknown key is a no-op.**
   Vendor guards with `getStepIndex(stepKey) <= -1`, which can never be true, because its own `getStepIndex()` maps a miss to `0` — so vendor silently jumps to the first step.
   Ours compares the key against `#stepKeys()` with `indexOf` and returns.

### The schema-component-method helper and two utilities live in schemas' assets

[`utils/schema_component_method.ts`](../../../packages/schemas/assets/src/utils/schema_component_method.ts) is the browser half of the [schemas host seam](has-schemas.md), so it ships from schemas; Wizard's `nextStep` and Tabs' `getDeferredTabBadges` call it, and so do forms' `select` and `file-upload` controllers.
Forms imports it as `@sylament/schemas/utils/schema_component_method.js` through the `exports` map and a `workspace:*` devDependency; esbuild inlines it into forms' bundled controllers, and the standalone copy ships from schemas' `dist`.

Two more utilities sit beside it.
[`utils/live_host.ts`](../../../packages/schemas/assets/src/utils/live_host.ts) exports `resolveLiveHost(element)` — the closest `[data-controller~="live"]` ancestor resolved through UX Live's registry, retried once after that root's `live:connect` because `getComponent()` polls for only ~55 ms and the live controller may connect after ours.
It is the select controller's private `resolveHost()` lifted and generalised (that controller keeps a private copy, the same logic as forms' own `utils/live_host.ts` beside it; [forms-bundle.md](../forms/forms-bundle.md#the-select-controller-binds-a-port-of-filaments-select-utility-to-the-live-host)).
[`utils/cloak.ts`](../../../packages/schemas/assets/src/utils/cloak.ts) is a duplicate of support's: the per-file build ships specifiers verbatim, so a cross-package import would have to be a bare specifier every consumer's importmap resolves.
The duplicate is deliberate and cheap; it collapses if a shared `@sylament/support` importmap entry is ever introduced.

### The icon alias values drop Filament's `schema::` prefix

Filament's values are namespaced, `schema::components.tabs.dropdown-trigger-button`; ours are the bare `components.tabs.dropdown-trigger-button`.
The constant names are Filament's, and the bare value follows the scheme [renames.md](../renames.md#icon-alias-values-drop-filaments-package-prefix) records, so an app registering overrides under `sylament.icons` reads one scheme rather than two.

## The controllers' values, targets and actions

The contract the templates emit — the Tabs and Wizard partials for the two component controllers, the Schema partial for the root one.
Names below are the Stimulus spellings; the markup carries them kebab-cased under the controller identifier (`data-sylament--schemas--tabs-active-tab-value`, `data-sylament--schemas--tabs-target="tab"`).

**`sylament--schemas--schema`** — on the Schema partial's root `<div>`, and only when the schema is a root schema *and* a live component sits above it (a nested schema carries nothing, so the walk runs once per host).

| Kind | Name | Meaning |
| --- | --- | --- |
| value | `schemaKey` (String) | this schema's key, matched against the `schemaKey` payload of `reset-schema-component-state` |
| action | `form-validation-error@window` → `onValidationError` | reveal + scroll to the first `[data-validation-error]` |
| action | `reset-schema-component-state@window` → `onStateReset` | refocus the first *visible* `[autofocus]` field |

It declares no targets: both handlers `querySelector` over their own subtree.

**`sylament--schemas--tabs`** — on the Tabs component's root, in client mode only.

| Value | Type | Meaning |
| --- | --- | --- |
| `activeTab` | Number, default `1` | the server's active tab, 1-based; the fallback on connect and on every revalidation |
| `key` | String | the Tabs component's key — the second argument to `callSchemaComponentMethod` |
| `schemaKey` | String | the owning schema's key — the `reset-schema-component-state` guard |
| `persist` | Boolean | `persistTab()`: read on connect, written on every change |
| `persistId` | String | the `localStorage` key's suffix (`tabs-<persistId>`) |
| `queryStringKey` | String | non-empty ⇒ read on connect (highest priority) and `history.replaceState`d on change |
| `scrollable` | Boolean, default `true` | `false` ⇒ run the overflow measurement and the visibility rules |
| `deferredBadges` | Boolean | `true` ⇒ call `getDeferredTabBadges` once on connect |

| Target | What it is |
| --- | --- |
| `nav` | the `<nav>` holding the inline tabs followed by the dropdown root; the measured container, and it carries `sy-invisible` until the first measurement completes |
| `tab` | one per inline tab button; toggled `sy-active` + `aria-selected`, and its `data-tab-key` is the key list's only source of truth |
| `panel` | one per tab panel; toggled `sy-active` |
| `badgePlaceholder` | one per deferred-badge slot; its `data-tab-key` selects the entry |
| `dropdown` | the overflow dropdown's root; declared because the template emits it, never dereferenced (the measurement leaves out the `nav`'s last child, as vendor does) |
| `triggerTab` | one per tab, rendered inside the dropdown trigger; mirrors `sy-active`/`aria-selected` |
| `moreButton` | the dropdown's trigger button; its icon width feeds the overflow maths, and its absence (with `nav`'s) is what makes the measurement a no-op under `scrollable(true)` |
| `dropdownItem` | one per tab, inside the dropdown panel; toggled `sy-selected` |

| Action | On | Effect |
| --- | --- | --- |
| `click` → `select` | each inline tab button | `#setTab(dataset.tabKey)` |
| `click` → `selectFromDropdown` | each dropdown item (paired with `click->sylament--support--dropdown#close`) | the same, then the dropdown closes itself |
| `expand` → `expand` | each panel | the root controller's reveal walk |
| `reset-schema-component-state@window` → `resetState` | the root | back to `activeTab`, unless persisted or in the query string |
| `resize@window` → `resize` | the root, emitted only under `scrollable(false)` | the 150 ms-debounced re-measurement |

**`sylament--schemas--wizard`** — on the Wizard component's root.

| Value | Type | Meaning |
| --- | --- | --- |
| `key` | String | the Wizard component's key — the second argument to `callSchemaComponentMethod`, and the match for both window events' `key` payload |
| `schemaKey` | String | the owning schema's key — the `reset-schema-component-state` guard |
| `startStep` | Number, default `1` | the server's active step, 1-based; the server has already resolved the query string into it, so the controller never reads the URL on connect |
| `skippable` | Boolean | `true` ⇒ every step is reachable from the header; otherwise only steps before the current one are |
| `queryStringKey` | String | non-empty ⇒ `history.replaceState`d on change (and the reset handler stands down) |

| Target | What it is |
| --- | --- |
| `header` | the header `<ol>`; absent when the header is not rendered, and the element `#scroll()` reads the current step off (its child at the step index, as vendor does) |
| `headerStep` | one `<li>` per step; toggled `sy-active`/`sy-completed` |
| `headerButton` | one `<button>` per step; `aria-current` + `disabled` |
| `stepStatus` | one `sr-only` span per step (`Completed` / `` / `Not completed`) |
| `step` | one per step panel; its `data-step-key` is the key list's source of truth, and it is toggled `sy-active` + `tabIndex` |
| `previousSlot` | the previous action's wrapper; hidden on the first step, and its `data-action` presence is what tells `#apply()` whether the action is enabled |
| `cancelSlot` | the cancel action's wrapper; hidden after the first step |
| `nextSlot` | the next action's wrapper; hidden on the last step |
| `submitSlot` | the submit action's wrapper; hidden before the last step |

| Action | On | Effect |
| --- | --- | --- |
| `click` → `goToStepFromHeader` | each header button | `#goToStep(dataset.stepKey)` |
| `click` → `goToPreviousStep` | `previousSlot`, unless the action is disabled | one step back |
| `click` → `requestNextStep` | `nextSlot`, unless the action is disabled | `nextStep` through the seam; the advance itself arrives as `next-wizard-step` |
| `expand` → `expand` | each step panel | the reveal walk, guarded by `#isStepAccessible()` |
| `next-wizard-step@window` → `onNextStepEvent` | the root | `key` match ⇒ advance |
| `go-to-wizard-step@window` → `onGoToStepEvent` | the root | `key` match ⇒ go to `detail.step` |
| `reset-schema-component-state@window` → `resetState` | the root | back to `startStep`, unless in the query string |
| `keydown` → `onKeydown` | the root | the Enter rule above |
