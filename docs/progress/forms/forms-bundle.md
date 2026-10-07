---
description: The forms bundle class and its service and asset wiring, plus the select, repeater and file-upload controllers and the shared scripts of the forms workspace.
ports:
  - vendor/filament/forms/src/FormsServiceProvider.php
  - vendor/filament/forms/src/FakerProvider.php
  - vendor/filament/forms/resources/js/components/select.js
  - vendor/filament/support/resources/js/utilities/select.js
  - vendor/filament/forms/resources/js/components/file-upload.js
owns:
  - packages/forms/assets/src/controllers/file_upload_controller.ts
  - packages/forms/assets/src/controllers/repeater_controller.ts
  - packages/forms/assets/src/controllers/select_controller.ts
  - packages/forms/assets/src/utils/cloak.ts
  - packages/forms/assets/src/utils/live_host.ts
  - packages/forms/assets/src/utils/select.ts
  - packages/forms/src/SylamentFormsBundle.php
---
# SylamentFormsBundle (forms)

[`Sylament\Forms\SylamentFormsBundle`](../../../packages/forms/src/SylamentFormsBundle.php) ports Filament's [`FormsServiceProvider`](../../../vendor/filament/forms/src/FormsServiceProvider.php): Spatie's `PackageServiceProvider` lifecycle becomes a Symfony `AbstractBundle` through [`SylamentBundle`](../../../packages/support/src/SylamentBundle.php).
`configure()` declares the `sylament_forms` tree that [temporary uploads](temporary-uploads.md#the-config-tree-keeps-three-of-livewires-seven-keys) read.
`loadExtension()` loads the `Components/` classes and the `Validation/*Validator.php` constraint validators as services and wires the temporary upload store; `FormsServiceProvider` binds no container services of its own.
`prependExtension()` registers the built assets under `@sylament/forms`, and the `@SylamentForms` Twig namespace arrives by AbstractBundle/TwigBundle convention with no call at all.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Bundle registration + `@SylamentForms` view namespace](../../../packages/forms/src/SylamentFormsBundle.php) | [`->hasViews()`](../../../vendor/filament/forms/src/FormsServiceProvider.php) | done | — | [diverges](#the-view-namespace-is-registered-by-symfony-convention-not-by-an-explicit-provider-call) |
| [`Components/` loaded as container-built, non-shared services](../../../packages/forms/src/SylamentFormsBundle.php) | — | done | — | [component model](../../internals/php.md#the-component-model) |
| [`Validation/*Validator.php` loaded as container services](../../../packages/forms/src/SylamentFormsBundle.php) | — | done | — | [semantics](probes-records.md#unique-and-exists-are-one-dql-probe-behind-a-container-service) |
| [Asset registration under `@sylament/forms`](../../../packages/forms/src/SylamentFormsBundle.php) | [`packageBooted()`](../../../vendor/filament/forms/src/FormsServiceProvider.php) — `FilamentAsset::register()` | done | — | [semantics](#each-fields-script-ships-with-its-field-and-the-bundle-registers-the-built-directory-once) |
| [`sylament--forms--select`](../../../packages/forms/assets/src/controllers/select_controller.ts) over [`utils/select.ts`](../../../packages/forms/assets/src/utils/select.ts) | [`components/select.js`](../../../vendor/filament/forms/resources/js/components/select.js) + [`utilities/select.js`](../../../vendor/filament/support/resources/js/utilities/select.js) | done | — | [diverges](#the-select-controller-binds-a-port-of-filaments-select-utility-to-the-live-host) |
| [`sylament--forms--repeater`](../../../packages/forms/assets/src/controllers/repeater_controller.ts) | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) — the embedded view's Alpine object | done | — | [diverges](#the-repeater-controller-owns-the-items-collapse-state-and-bridges-the-drag-control-to-the-reorder-action) |
| [`sylament--forms--file-upload`](../../../packages/forms/assets/src/controllers/file_upload_controller.ts) | [`components/file-upload.js`](../../../vendor/filament/forms/resources/js/components/file-upload.js) | done | — | [diverges](#the-file-upload-controller-bundles-filepond-and-is-the-only-lazy-controller) |
| `FakerProvider` and its registration | [`packageRegistered()`](../../../vendor/filament/forms/src/FormsServiceProvider.php) + [`FakerProvider.php`](../../../vendor/filament/forms/src/FakerProvider.php) | blocked | the MarkdownEditor and RichEditor fakers | [detail](#the-faker-provider-returns-the-markdown-and-rich-content-fakers) |
| `Make*` commands + stub publishing | [`configurePackage()`](../../../vendor/filament/forms/src/FormsServiceProvider.php) | todo | — | — |
| Translations (`hasTranslations`) | [`configurePackage()`](../../../vendor/filament/forms/src/FormsServiceProvider.php) | todo | — | [i18n](../../internals/i18n.md) |
| `Livewire::component('table-select', …)` | [`packageBooted()`](../../../vendor/filament/forms/src/FormsServiceProvider.php) | blocked | the TableSelect field | [detail](#the-table-select-registration-waits-on-the-tableselect-field) |
| `Testable::mixin(new TestsForms)` / `TestsFormComponentActions` | [`packageBooted()`](../../../vendor/filament/forms/src/FormsServiceProvider.php) | skipped | — | [diverges](#livewire-specific-provider-responsibilities-are-dropped-not-ported) |

## Details

### The Faker provider returns the markdown and rich content fakers

`packageRegistered()` adds [`FakerProvider`](../../../vendor/filament/forms/src/FakerProvider.php) to every resolved Faker `Generator`.
Its two methods return `MarkdownFaker` and `RichContentFaker`, which live in the `MarkdownEditor` and `RichEditor` subtrees, and neither field has a port.

### The table-select registration waits on the TableSelect field

`packageBooted()` registers the `table-select` Livewire component (`Livewire::component('table-select', TableSelectLivewireComponent::class)`).
It is wanted for parity once the TableSelect field exists: the field, its `ModalTableSelect`, and a UX LiveComponent host in place of the Livewire component.

## Semantics

### Each field's script ships with its field, and the bundle registers the built directory once

Filament's `packageBooted()` registers one `AlpineComponent` per field script with `FilamentAsset::register([...], 'filament/forms')`: `builder`, `checkbox-list`, `code-editor`, `color-picker`, `date-time-picker`, `file-upload`, `key-value`, `markdown-editor`, `rich-editor`, `select`, `slider`, `tags-input` and `textarea`.
The port's translation is one `prependExtension()` that registers `packages/forms/assets/dist` as `@sylament/forms` in AssetMapper, guarded by [`isAssetMapperAvailable()`](../../../packages/support/src/SylamentBundle.php) so a consumer without AssetMapper is not forced to install it.
Each script is a Stimulus controller that ships with its field and needs no further wiring.
The ported ones are `checkbox-list` ([checkbox-list.md](checkbox-list.md#the-controller-is-the-only-behaviour-and-no-test-in-this-repo-runs-it)), [`file-upload`](#the-file-upload-controller-bundles-filepond-and-is-the-only-lazy-controller), `key-value` ([key-value.md](key-value.md#the-controller-is-a-reorder-bridge-and-a-focus-guard-not-a-row-store)), [`select`](#the-select-controller-binds-a-port-of-filaments-select-utility-to-the-live-host), `tags-input` ([tags-input.md](tags-input.md#the-chip-list-is-client-owned-after-the-first-paint)) and `textarea` ([textarea.md](textarea.md#the-sizer-grows-on-input-floors-at-the-initial-height-and-never-measures-a-hidden-control)).
`date-time-picker` belongs to a field that exists and waits with [the JS calendar](can-be-native.md#nativefalse-throws-rather-than-falling-back-to-the-native-control); the rest belong to fields with no port.
The `repeater` and `text-input` controllers have no entry in that list: Filament embeds the repeater's Alpine object in the component's own view, and its reveal and copy are `x-data` on the field plus an `alpineClickHandler()` per action.

The `select` and `file-upload` controllers are `BUNDLED_ENTRIES` builds, so their `dist/controllers/*.js` files exist only when the rebuilt dist is committed, and `AssetMapperRepository::findLogicalPath()` answers `null` for a missing file.

[`utils/cloak.ts`](../../../packages/forms/assets/src/utils/cloak.ts) is a byte-for-byte copy of [support's](../../../packages/support/assets/src/utils/cloak.ts), and [`utils/live_host.ts`](../../../packages/forms/assets/src/utils/live_host.ts) a copy of [schemas' `resolveLiveHost()`](../../../packages/schemas/assets/src/utils/live_host.ts).
The per-file build ships import specifiers verbatim, so a cross-package import from the per-file `repeater`, `key-value` and `tags-input` controllers would be a bare specifier every consumer's importmap has to resolve.
A bundled entry has no such cost, which is why `file_upload_controller.ts` imports schemas' `resolveLiveHost()` directly and esbuild inlines it.

## Divergences

### The view namespace is registered by Symfony convention, not by an explicit provider call

Filament calls `->hasViews()` to register its Blade namespace.
The bundle registers nothing for it: `AbstractBundle::getPath()` returns `packages/forms`, and TwigBundle auto-maps `packages/forms/templates` → `@SylamentForms`.
The wiring is therefore invisible in the bundle, which is intentional and the reason the field classes can set `$template = '@SylamentForms/partials/TextInput/index.html.twig'` with no explicit twig path config.
Same divergence as [schemas-bundle.md](../schemas/schemas-bundle.md#the-view-namespace-is-registered-by-symfony-convention-not-by-an-explicit-provider-call).

### The select controller binds a port of Filament's select utility to the live host

Filament ships a first-party select control of about 2,450 lines ([`support/…/js/utilities/select.js`](../../../vendor/filament/support/resources/js/utilities/select.js), Floating UI + SortableJS) that the forms Alpine component ([`resources/js/components/select.js`](../../../vendor/filament/forms/resources/js/components/select.js)) instantiates with `$wire.$entangle` for state and `$wire.callSchemaComponentMethod` for search.
Sylament ports that utility as [`utils/select.ts`](../../../packages/forms/assets/src/utils/select.ts), every vendor method under its own name in vendor order, and replaces only the Alpine wrapper: [`select_controller.ts`](../../../packages/forms/assets/src/controllers/select_controller.ts) is the Stimulus binding that constructs the engine and joins it to the host live component.
A port beats an integration over a third-party engine such as Tom Select, because the vendor utility answers by construction what such an integration pays for by hand: the flip-and-shift positioning, the chip reorder, the max-items announcement, the `$order`/`$id` carry, and the class settings that skin a foreign DOM.
The modal escape is not the engine's doing: vendor clips inside its modals, and ours escapes them through [support's fixed positioning context](../support/modal.md#the-modal-is-a-fixed-positioning-context).

`symfony/ux-autocomplete` was weighed for the select control and declined.
Its Stimulus controller fetches results with a headerless GET, which cannot reach a live action; its one override seam would replace everything this controller exists to build; and it would put its engine into every consumer's importmap, where the house bundles a library into the owning dist (`BUNDLED_ENTRIES`).
Its `{value, text}` payload shape is not adopted either: the results never leave our own JavaScript, so the option payload stays Filament's ([select.md](select.md#transformoptionsforjs-casts-a-markup-label-to-text)).

- **The engine lives in forms, and only inside the bundle.**
  `utils/select.ts` is filed here because forms is its only consumer; it moves to a support workspace on the second one, under [the promotion rule](../../internals/assets.md#when-does-a-utility-move-to-a-shared-workspace).
  Tables' `SelectColumn` does not trigger the promotion: tables ships it [native-only](../tables/select-column.md), a plain `<select>` with no JS control of its own, so the engine has exactly one consumer.
  Vendor's `columns/select.js` does import the same utility, so the trigger returns when the searchable and relationship arms land, which is the deferral that row is blocked on, not a separate one.
  AssetMapper is what makes that a real move rather than a re-export: it resolves a bare import only through the root importmap (`JavaScriptImportPathCompiler::findAssetForBareImport()`), so an `@sylament/support/utils/select.js` import from a per-file module would need every consumer's importmap touched, unlike a **bundled** entry's import, which esbuild resolves at build time (see the helper bullet below).
  The module also ships **only** inlined: it is named in the `BUNDLED_ENTRIES` entry's `inlines` list, which excludes it from the per-file build, because a per-file `dist/utils/select.js` would carry bare `@floating-ui/dom` / `sortablejs` imports that 404 in a browser.
- **It binds to the host by hand.**
  The JS-branch `<select>` carries no `data-model` (a `<select data-model>` under `SetValueOntoModelFieldsPlugin` writes the DOM value *back* into the value store after every render, resetting an option the render did not know about); the controller reads the Stimulus values the [Select template](../../../packages/forms/templates/partials/Select/index.html.twig) emits (`key`, `statePath`, `modelBinding`, `options`, `selectedOptions`, `canSelectPlaceholder`, `searchable`, `dynamicSearchResults`, `initialNoOptionsMessage`, `searchDebounce`, `optionsLimit`, `maxItems`, `maxItemsMessage`, `htmlAllowed`, `labelsWrap`, `position`, `reorderable`, `searchFields`, `placeholder`, `searchPrompt`, `searchingMessage`, `noSearchResultsMessage`, `noOptionsMessage`, `loadingMessage`, `badgeClass`; `maxItems` and `position` are omitted when unset, so the controller reads `hasMaxItemsValue` / `hasPositionValue`), and calls `component.set(statePath, value, render, debounce)` itself from the engine's `onStateChange`.
  `statePath` is the only string ever handed to the value store; `modelBinding` (`getDataModel()`'s directive) is parsed once for its modifiers only: `norender|` ⇒ no render, `debounce(N)|` ⇒ `parseInt`, `on(change)|` and the bare path ⇒ render at once.
  A multiple value is written as a **copy** of the engine's array (`ValueStore.set()` reports "unchanged" on reference identity, which would stop a `live()` multi-select after its first change) and a null single state as `''`.
  The engine is constructed only after `getComponent(closest live root)` resolves, retrying once after the root's own `live:connect` when the live controller has not registered yet, matched by `event.target` because a **nested** host's `live:connect` bubbles through the same element; the host's `ExternalMutationTracker` must be observing when the engine inserts its container, or the first morph removes it.
  That retry is a private `resolveHost()`, the same logic as forms' own [`resolveLiveHost()`](../../../packages/forms/assets/src/utils/live_host.ts) beside it.
  There is no "before the host is reachable" fallback: a `<select>` outside any live component throws.
  Nor is there one for a missing controller: a page whose `assets/controllers.json` leaves out forms' `select` block renders a JS-branch select (`searchable()`, `multiple()`, `native(false)` or `allowHtml()`) as a plain `<select>` that neither searches nor sends what the user picked, with no error; a select that is none of those four is native, binds through its own `data-model`, and needs no JavaScript.
  The `<select>` itself stays as the inert carrier: the controller takes its `id` for the engine's combobox button and restores it on disconnect ([the id handoff](select.md#the-hidden-select-lends-its-id-to-the-combobox-button)), hides it with an inline `display: none` (the stylesheet's `select.sy-fo-select` rule sets `display: block`, which outranks the UA's `[hidden]` rule), and the engine inserts its container as the `<select>`'s next sibling, so the tracker records a client-added sibling and every morph keeps it.
  When the `<select>` carries `sy-fo-select-has-inline-prefix` the controller puts `sy-fo-select-ctn-has-inline-prefix` on that container, which carries the inline-prefix padding onto the engine's markup.
- **The server render is the option and selection truth.**
  The controller passes `hasDynamicOptions: false` and `getOptionsUsing` / `getOptionLabelUsing` / `getOptionLabelsUsing` all `null`, so the engine's own fetch fallbacks are never reached; only `getSearchResultsUsing` leaves the browser.
  Labels arrive through the render instead: `selectedOptions` seeds `initialOptionLabel` (single) or `initialOptionLabels` (multiple) at construction and is written into the engine's `labelRepository`, its value→label memo, by `rememberLabels()` at construction and again on every re-sync.
  That is also why [`loadingMessage()` has no render site](select.md#loadingmessage-has-no-render-site): the dynamic-options open fetch it accompanies in Filament does not exist here.
- **Every host render re-syncs the control** (`component.on('render:finished')`, plus the `sylament:select-refresh-selected-option-label` browser event, accepted only when `event.target` is the host root and `detail.statePath` matches).
  The re-sync is four steps.
  `replaceOptions()` hands the engine the freshly rendered pool: it always becomes `originalOptions` and refills the label repository, but the visible list is re-rendered only when the pool actually changed *and* no search query is typed; under an active query the results on screen are left alone and the new pool waits for the next clear, and the changed-pool guard is what stops an unrelated render from dropping keyboard focus out of an open list.
  `rememberLabels()` then refills the repository from `selectedOptions`.
  The value store's entry for `statePath` is normalised to the strings the engine compares (single: `''` or `String(v)`; multiple: the non-blank entries as strings) and written onto the engine only when it differs **structurally**: as an ordered list under `reorderable()`, where the order is state and a server render holding the same values in another order must re-sync the chips, and as a multiset otherwise, so an unrelated render does not rebuild the chips over a mere reordering; after a drag the engine has already written the new order out, so the next render matches and nothing is rebuilt.
  Finally `disabled` follows the `<select>` through `disable()` / `enable()`, and the `<select>`'s `aria-invalid` and `aria-required` are mirrored onto the combobox button (here and at construction), since the hidden `<select>` is out of the accessibility tree and Filament's button carries neither.
  The refresh event runs that same re-sync and then `refreshLabels()`, which repaints the selection and its option row from the repository the render just refilled.
- **Search is a live round-trip through the schema-method seam.**
  With `dynamicSearchResults`, `getSearchResultsUsing` is `callSchemaComponentMethod(component, key, 'getSearchResultsForJs', {search})`; everything around it is the engine's, ported verbatim: the `searchDebounce` timer, the `activeSearchId` guard that drops a stale response, the searching / no-results messages, the reset to `originalOptions` when the query is emptied, the `console.error` and pool restore on a rejected call (the host has already surfaced the failure through its own error overlay), and the local `filterOptions()` over `searchableOptionFields` for a searchable select with no dynamic arm.
- **The helper is schemas', imported across workspaces.**
  [`callSchemaComponentMethod`](../schemas/schemas-bundle.md#the-schema-component-method-helper-lives-here-now) is imported as the bare specifier `@sylament/schemas/utils/schema_component_method.js`, resolved through schemas' `exports` map (`{"./utils/*.js": "./src/utils/*.ts"}`) and a `"@sylament/schemas": "workspace:*"` devDependency of this workspace.
  It costs a consumer nothing: this entry is a `BUNDLED_ENTRIES` build, so esbuild inlines the helper at build time and no bare `@sylament/schemas/...` specifier ever reaches a browser or an importmap, which is exactly why the same move is *not* available to `utils/select.ts`'s eventual promotion under the per-file build.
- **Reorder is the `reorderable` value.**
  The controller passes it as `isReorderable` at construction and, because Stimulus calls a value's changed callback on every morph that rewrites it, `reorderableValueChanged()` flips the engine's flag and rebuilds the badges (Filament re-keys the whole control on that flag instead).
  The engine does the rest: a SortableJS instance on the badges container, `animation: 150`, whose `onEnd` reads the `[data-value]` order back out of the DOM and reports it through `onStateChange`, so the drag writes straight to the value store like any other change.
- **The port corrects seven vendor defects and drops one listener.**
  The seven (the badge remove buttons' dead disabled selector, `enable()` adding the disabled class to the clear button, the un-destroyed `Sortable` and a positioning frame left queued past `destroy()`, `form.submit()` where `requestSubmit()` is meant, the label `<span>` dropped from an updated plain-text option, an option value interpolated raw into a `querySelector` selector, and an emptied search box that let an in-flight search land on it) are recorded with their reasoning in [select.md](select.md#the-engine-port-corrects-defects-the-vendor-engine-carries).
  The drop is vendor's `filament-forms::select.refreshSelectedOptionLabel` **window** listener and its `livewireId`: the refresh reaches us as a host browser event the controller matches, so the engine has no Livewire-era hook at all.
  Positioning is otherwise vendor's, including the two opt-in context classes; support's modal stamps `sy-fixed-positioning-context`, which is what lets the dropdown [flip and escape a modal's overflow](select.md#the-dropdown-flips-and-escapes-the-modal).
- **`disconnect()`** unhooks `render:finished` and the refresh listener (the host outlives a hidden field, a removed repeater row, a closed modal, and a stale hook throwing inside `processRerender()` aborts the host's render), `destroy()`s the engine (which removes its own listeners, its Sortable and its container), and restores the `<select>`'s `id` and default `display`.
  No markup snapshot is needed: the engine never touches the `<select>`'s children, which is the whole reason its container is a sibling.
- **Dependencies and licence.**
  `@floating-ui/dom` and `sortablejs`, both MIT, are `dependencies` of the workspace and inlined into `dist/controllers/select_controller.js` through `BUNDLED_ENTRIES`, with `@hotwired/stimulus` and `@symfony/ux-live-component` left external (the latter dev **and** peer at `^3.2.0`, the actions precedent, since `getComponent` stays a bare specifier the consuming app's importmap resolves); neither is in a consumer's importmap, so neither has a `symfony.importmap` line.
  The entry's `license` field is a **list**, and `licenseBanner()` heads the bundle with one `/*! … */` line per package composed from the installed `package.json` and `LICENSE`; no licence file sits beside the bundle, because MIT is served by the banner, the way support's bundled `@floating-ui/dom` is.
  `@types/sortablejs` is dev-only (sortablejs ships none), as is the `@sylament/schemas` workspace link the helper import needs, a `workspace:*` specifier that reaches the split `sylament/forms` archive and is inert there (nobody `npm install`s a Sylament package, and StimulusBundle reads only the `symfony` key).
  `@floating-ui/dom` is inlined twice on a page, since support's dropdown controller carries its own copy, and so is `sortablejs`, since support's sortable controller carries its own: Filament's own per-bundle inlining, recorded and accepted.
- **No test in this repo runs the controller or the engine.**
  The [`checkbox-list` note](checkbox-list.md#the-controller-is-the-only-behaviour-and-no-test-in-this-repo-runs-it) applies verbatim, and a JS rig was weighed for the engine and declined: its verification is the reviewer's method-by-method diff against vendor's `select.js` plus the demo's browser pass.

### The repeater controller owns the items' collapse state and bridges the drag control to the reorder action

[`repeater_controller.ts`](../../../packages/forms/assets/src/controllers/repeater_controller.ts) is `sylament--forms--repeater`, the port of the client half of Filament's repeater Alpine object ([`Repeater.php`'s embedded view](../../../vendor/filament/forms/src/Components/Repeater.php)).
It reads two Stimulus values, `key` (the schema component key, the only string ever handed to the host) and `persist` (whether collapse state survives a reload), and one target, `item`.
The default-mode [Repeater partial](../../../packages/forms/templates/partials/Repeater/index.html.twig) stamps that target, and the collapse actions, on every item `<li>` of a collapsible repeater; the simple and table partials bind the controller for the drag bridge alone.
These methods carry the behaviour:

- `toggleItemFromHeader`, bound on the item header's `click`, ignores clicks that landed inside `.sy-fo-repeater-item-header-start-actions` or `.sy-fo-repeater-item-header-end-actions`, so the reorder, clone and delete buttons are not also a collapse toggle.
  The [Section controller's `toggleFromHeader()`](../../../packages/support/assets/src/controllers/section_controller.ts) is the precedent.
- `toggleItemFromButton`, bound on the collapse/expand button pair's `click`, calls `event.stopPropagation()` **by hand** rather than taking the action option: `:stop` needs Stimulus 3.2 and the workspace's peer range is `^3.0`.
  That is the one comment kept in the file body, for parity with the Section controller, which carries the same line for the same reason.
- `expandItem`, bound on the item's own `expand` event, is the non-bubbling per-ancestor reveal the [schema root dispatches after a validation failure](../schemas/schemas-bundle.md#the-expand-walk-and-the-two-window-events-either-side-of-it).
  `event.currentTarget` is the element carrying the `data-action`, so the item resolves without a detail payload.
- `collapseAll` / `expandAll`, bound on the two actions-bar buttons, walk `this.itemTargets`.
  Stimulus scopes targets and actions to the nearest controller of the same identifier, so a nested repeater collapses only its own rows.
- `reorder` is the drag bridge, below.

**The collapse state is an attribute the controller stamps, not a class the server owns.**
Each item carries `data-collapsed="true"|"false"` from the moment this controller sees it; the server-rendered `sy-collapsed` class is **first paint only** and is read exactly once, as the seed for that stamp.
The reason is UX LiveComponent's `ExternalMutationTracker`: it replays a client mutation over a morph only when it recorded one, and it records neither of the two writes the obvious implementation would make.
`handleGenericAttributeMutation()` bails on `newValue === oldValue`, so re-`setAttribute`ing the value already there is nothing; `handleClassAttributeMutation()` diffs the old and new token lists, so `classList.add()` of a class the element already carries is nothing either.
A `null → "true"` attribute write is the one shape the tracker reliably records, which is why `#stamp()` writes the attribute on first sight even when the value it writes is `"false"`, and why `#setCollapsed()` never touches the class.
The stamp is guarded on `hasAttribute()`, so an item the morph patched **in place** keeps whatever the user last chose, while an item the morph **re-created** arrives unstamped and is re-seeded from its server class.
`#isCollapsed()` mirrors that fallback order (attribute, then class).

Items are stamped from two places, and the redundancy is deliberate: `itemTargetConnected()` covers rows the server adds later, and the `connect()` loop is the belt-and-braces pass.
On Stimulus 3.2.2 the `connect()` loop always runs second, because `Context.connect()` starts the target observer *before* it calls `controller.connect()`, but `#stamp()` is idempotent, so the order is not load-bearing either way.
`connect()` also `uncloak()`s the root, which is how a `persist`ing repeater avoids showing every item expanded for one frame before storage is read.

Persistence is `window.localStorage` under `repeater-<key>-<itemKey>-isCollapsed` (`itemKey` from the item's `data-item-key`), written on every `#setCollapsed()` and read once per item in `#stamp()`.
Both accessors are the Section controller's `try`/`catch` helpers verbatim, including their two comments: a browser with storage disabled falls back to the server's default and still collapses, it just does not remember.

**Reorder goes through the host, not through the DOM.**
The `<ul>` / `<tbody>` carries `data-action="sylament--support--sortable:end->sylament--forms--repeater#reorder"`, so [support's drag control](../support/sortable.md) hands this controller the new key order in the event's `detail.items`.
A non-array detail returns silently; otherwise the controller resolves the closest `[data-controller~="live"]` root, throwing "The sylament--forms--repeater controller needs a live component host to reorder." when there is none (the select controller's message shape), and calls `component.action("mountAction", { name: "reorder", arguments: { items }, context: { schemaComponent: this.keyValue } })`, the repeater's own `reorder` action mounted against its schema component.
`getComponent()` is called at drag time rather than at connect, so there is no `live:connect` race to retry around: a user cannot drag a row before the host has connected, which is why this controller needs no [`resolveLiveHost()`](../../../packages/forms/assets/src/utils/live_host.ts).

It ships **per-file** (no `BUNDLED_ENTRIES` entry, no new dependency): the built `dist/controllers/repeater_controller.js` keeps `@hotwired/stimulus`, `@symfony/ux-live-component` and `../utils/cloak.js` as verbatim specifiers, the first two resolved by the consuming app's importmap; `@symfony/ux-live-component` is both a dev and a peer dependency of this workspace.
Its `symfony.controllers` entry is `fetch: eager`.

**No test in this repo runs it**: the [`checkbox-list` note](checkbox-list.md#the-controller-is-the-only-behaviour-and-no-test-in-this-repo-runs-it) applies verbatim, and verification is the reviewer's line-by-line read against the vendor Alpine object plus the demo's browser pass.

### The file-upload controller bundles FilePond and is the only lazy controller

Filament's `file-upload.js` is an Alpine component over FilePond that reaches Livewire's temporary-upload pipeline (`$wire.upload()`, `$wire.cancelUpload()`) and entangles the field's state.
Ours is [`file_upload_controller.ts`](../../../packages/forms/assets/src/controllers/file_upload_controller.ts), and its shape differs in these ways.

- **`fetch: lazy`, the only one in the toolkit.**
  The bundle is about 760 KB un-minified (FilePond core plus eight of its plugins and Cropper.js, ten MIT banners composed by `build-js.mjs`) and appears on few pages; every other Sylament controller is `eager`.
  StimulusBundle therefore fetches it only when a `data-controller="sylament--forms--file-upload"` element appears, which is exactly the trade this size justifies.
  A consuming app that copies another controller's `controllers.json` entry will make it eager by accident.
  Leaving the entry out fails total rather than partial: what remains is the bare `<input type="file">` the template renders under the label, with no `name` and no `data-model`, and nothing listening to it, so there is no drop zone, no preview, and a picked file never leaves the page.
- **Filament's own media-preview plugin is not bundled**, and its stylesheet waits on it ([styles.md](styles.md#the-unported-stylesheets-wait-on-what-they-style)).
- **No `mime` package.**
  Ours resolves `mimeTypeMap[extension] || detectedType` and stops there; the degraded case is [file-upload.md's](file-upload.md#the-bundle-drops-fileponds-mime-database).
- **Upload is one live round trip, so there is no byte progress and no true abort.**
  `uploadSchemaComponentFile()` posts the file as a multipart part of a live action; `fetch()` reports no upload progress, so `progress(false, 0, 0)` puts FilePond's indicator in indeterminate mode, and FilePond's abort can only mark the key cancelled: when the request lands, the controller calls `removeUploadedFile` and *then* aborts the item.
- **The state is reconciled by hand.**
  Filament entangles `state` through Alpine and re-reads the server on every change; our pond subtree is `data-live-ignore`, so the controller re-reads the `state` and `uploadedFiles` Stimulus values after each morph and rebuilds `pond.files` only when the state JSON changed and holds no `sylament-file:` token.
  That is the twin of Filament's `$watch('state')`, keeping its token and last-state guards and adding two of its own: no upload in flight and no editor swap pending.
  The gate is keyed on **state** alone: `uploadedFiles` carries temporary URLs that can regenerate on every render, and keying on those would rebuild the pond continuously.
- **`disabled()` flows through an option, not a key bust.**
  Filament hashes `isDisabled()` into its `wire:key` so Livewire destroys and rebuilds the whole subtree; `disabledValueChanged()` calls `pond.setOptions({disabled})` instead.
- **The `<input>` is captured once.**
  `FilePond.create()` detaches the element it replaces, so `this.inputTarget` throws afterwards while `FilePond.destroy()` needs that same element (and restores it, which is what lets a later `connect()` find the target again).
  Everything downstream reads the captured reference.
- **Four JS-owned literals join the four PHP-side ones** [file-upload.md](file-upload.md#literals-not-translation-keys) opens with.
  `The file could not be uploaded.` is Filament's; `The file could not be removed.`, `The file could not be loaded.` and `The files could not be reordered.` are ours, because Filament's `revert`/`remove` never call FilePond's `error` at all, its `load` hands FilePond whatever body a failed response carries, and its `reorderfiles` handler `await`s the server inside an event listener nothing catches, a silent failure every time.
  Literals, not translation keys, per the [toolkit-wide i18n deferral](../translations.md#idiom).

Two shipped-type gaps are worked around in the file and will bite anyone upgrading FilePond: `initfile` is a real event (`filepond.esm.js:8842`) missing from the `FilePondEvent` union, and `maxFileSize`/`minFileSize` are `Type.INT` options declared `string`, so the controller passes the byte ceiling as a decimal string, which FilePond parses back to exactly that integer.
The `error` event's first argument is `{main, sub}` for a validation failure and `{type, code, body}` for a transport one, so the message is composed defensively rather than rendered as Filament's `undefined: undefined`.

The following details in the same file are worth reading as a set, because each is a shape that reappears whenever a seam call or a FilePond option is added.
A cancelled upload's key is consumed on **both** settle arms and the item is `abort()`ed rather than `error()`ed, so the cancelled set cannot leak and a cancelled item leaves no error chrome behind.
The two `void` seam calls carry rejection arms: a failed `reorderUploadedFiles` surfaces `The files could not be reordered.` and clears `#lastState`, the reconcile memo, so the next morph rebuilds the list in the server's order, and the cancel-time `removeUploadedFile` surfaces `The file could not be removed.`.
`FilePond.create()` runs inside `try/finally` so the `#initializing` re-entrance flag cannot stick after a throw, `maxFileSize` is passed only under `hasMaxFileSizeValue` (an absent value is not a zero ceiling), and `resolveLiveHost()`'s rejection surfaces through the error banner instead of an unhandled promise rejection.
`#detectType` resolves `mimeTypeMap[extension] || detectedType` with `||`, not `??`, so a map entry deliberately set to the empty string falls through to the browser's detected type: that is Filament's operator, and the reason an empty entry is a "let the browser decide" instruction rather than a hard "no type".

### Livewire-specific provider responsibilities are dropped, not ported

`packageBooted()` calls `Testable::mixin()` twice (`TestsForms`, `TestsFormComponentActions`).
The Testable mixins are a deliberate non-goal: Sylament tests are Pest/`KernelTestCase`, not Livewire `Testable`, and [`LiveFieldTest`](../../../packages/forms/tests/Integration/LiveFieldTest.php) drives fields through UX LiveComponent's `InteractsWithLiveComponents` instead.
