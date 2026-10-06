# SylamentFormsBundle (forms)

[`Sylament\Forms\SylamentFormsBundle`](../../../packages/forms/src/SylamentFormsBundle.php)
— the forms bundle class. It ports Filament's
[`FormsServiceProvider`](../../../vendor/filament/forms/src/FormsServiceProvider.php)
(Spatie's `PackageServiceProvider` lifecycle becomes a Symfony `AbstractBundle`
via [`SylamentBundle`](../../../packages/support/src/SylamentBundle.php)). It
does two things and inherits a third. `loadExtension()` PSR-4-loads the
`Components/` directory so every field is a container-built, non-shared service —
the toolkit-wide [component model](../../internals/php.md#the-component-model), not anything
`FormsServiceProvider` does, which binds no container services of its own.
`prependExtension()` registers the built assets under `@sylament/forms`, the
translation of Filament's asset registration. And the `@SylamentForms` Twig
namespace arrives by AbstractBundle/TwigBundle convention with no call at all.
`Make*` commands, translations, the `table-select` Livewire component and the
`Testable` mixins are not ported yet.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`forms/src/FormsServiceProvider.php`](../../../vendor/filament/forms/src/FormsServiceProvider.php)
  — the provider whose `configurePackage()` / `packageBooted()` this bundle ports

## Surface

Every provider responsibility. _Blocked by_ names what has to land first.
_Detail_ carries a **detail** link to a [Details](#details) write-up, a
**diverges** link to [Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Bundle registration + `@SylamentForms` view namespace](../../../packages/forms/src/SylamentFormsBundle.php) | [`->hasViews()`](../../../vendor/filament/forms/src/FormsServiceProvider.php) | done | — | [diverges](#the-view-namespace-is-registered-by-symfony-convention-not-by-an-explicit-provider-call) |
| Asset registration (12 field AlpineComponents) | [`packageBooted()`](../../../vendor/filament/forms/src/FormsServiceProvider.php) | in progress | — | [detail](#asset-registration) |
| [`sylament--forms--select`](../../../packages/forms/assets/src/controllers/select_controller.ts) — the JS select control, a port of Filament's select utility ([`utils/select.ts`](../../../packages/forms/assets/src/utils/select.ts)) bound to the live host, plus the [`callSchemaComponentMethod`](../../../packages/schemas/assets/src/utils/schema_component_method.ts) helper it searches through, now imported from `@sylament/schemas` | [`resources/js/components/select.js`](../../../vendor/filament/forms/resources/js/components/select.js) + [`support/…/js/utilities/select.js`](../../../vendor/filament/support/resources/js/utilities/select.js) | done | — | [diverges](#the-select-controller-binds-a-port-of-filaments-select-utility-to-the-live-host) |
| [`sylament--forms--repeater`](../../../packages/forms/assets/src/controllers/repeater_controller.ts) — the client half of the repeater's chrome: per-item collapse state (`data-collapsed`), collapse-all/expand-all, the validation `expand` reveal, and the bridge from support's drag control to the host's `reorder` action | [`Components/Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) (the embedded view's Alpine object) | done | — | [diverges](#the-repeater-controller-owns-the-items-collapse-state-and-bridges-the-drag-control-to-the-reorder-action) |
| [`sylament--forms--file-upload`](../../../packages/forms/assets/src/controllers/file_upload_controller.ts) — FilePond 4.32 plus its eight plugins (`file-validate-size`, `file-validate-type`, `image-crop`, `image-edit`, `image-exif-orientation`, `image-preview`, `image-resize`, `image-transform`) and Cropper.js 1.6, all bundled; uploads ride the schemas seam ([`uploadSchemaComponentFile`](../../../packages/schemas/assets/src/utils/schema_component_method.ts)) and the three mutators ride `callSchemaComponentMethod`; its skin is [`file-upload.css`](../../../packages/forms/assets/src/styles/components/file-upload.css) over the scoped `filepond.generated.css` and `cropper.generated.css` | [`resources/js/components/file-upload.js`](../../../vendor/filament/forms/resources/js/components/file-upload.js) | done | — | [diverges](#the-file-upload-controller-bundles-filepond-and-is-the-only-lazy-controller) |
| [`sylament--forms--key-value`](../../../packages/forms/assets/src/controllers/key_value_controller.ts) — the reorder bridge (support's drag control's `end` event → the host's `reorder` action) plus the capture-phase blur that keeps WebKit's unfocused button click from letting the morph restore a stale cell value | [`resources/js/components/key-value.js`](../../../vendor/filament/forms/resources/js/components/key-value.js) | done | — | [diverges](key-value.md#the-controller-is-a-reorder-bridge-and-a-focus-guard-not-a-row-store) |
| [`sylament--forms--tags-input`](../../../packages/forms/assets/src/controllers/tags_input_controller.ts) — the chip list, client-owned behind `data-live-ignore` after the first paint: it clones a server-rendered `<template>` badge per tag, reconciles by value on every `render:finished`, and writes the array through the host's value store rather than any `data-model` | [`resources/js/components/tags-input.js`](../../../vendor/filament/forms/resources/js/components/tags-input.js) | done | — | [diverges](tags-input.md#the-alpine-data-object-becomes-a-stimulus-controller-over-a-cloned-twig-chip) |
| [`sylament--forms--textarea`](../../../packages/forms/assets/src/controllers/textarea_controller.ts) — the autosize sizer: it grows the control on the `input` event bound from the template, floors at the initial height, re-measures on a `window` resize and once through an `IntersectionObserver`, and never measures a hidden control | [`resources/js/components/textarea.js`](../../../vendor/filament/forms/resources/js/components/textarea.js) | done | — | [semantics](textarea.md#the-sizer-grows-on-input-floors-at-the-initial-height-and-never-measures-a-hidden-control) · [diverges](textarea.md#only-the-keyboard-re-measures-a-server-set-state-change-does-not) |
| [`sylament--forms--text-input`](../../../packages/forms/assets/src/controllers/text_input_controller.ts) — the client half of the text input's affix actions: `reveal()` / `conceal()` swap the input's `type` and toggle `sy-revealed` on the wrapper root, `copy()` writes the input's live value through a guarded `navigator.clipboard` and writes the confirmation into an always-rendered `role="status"` region for the action's duration parameter. Bound only on a field that is `revealable()` or `copyable()` | — | done | — | [diverges](text-input.md#copy-writes-the-live-input-value-and-confirms-through-a-status-region) |
| `Make*` commands + stub publishing | [`configurePackage()`](../../../vendor/filament/forms/src/FormsServiceProvider.php) | todo | — | [detail](#make-commands--stub-publishing) |
| Translations (`hasTranslations`) | [`configurePackage()`](../../../vendor/filament/forms/src/FormsServiceProvider.php) | todo | — | [detail](#translations) |
| `Livewire::component('table-select', …)` | [`packageBooted()`](../../../vendor/filament/forms/src/FormsServiceProvider.php) | blocked | TableSelect field feature | [diverges](#livewire-specific-provider-responsibilities-are-dropped-not-ported) |
| `Testable::mixin(new TestsForms)` / `TestsFormComponentActions` | [`packageBooted()`](../../../vendor/filament/forms/src/FormsServiceProvider.php) | skipped | — | [diverges](#livewire-specific-provider-responsibilities-are-dropped-not-ported) |

The bundle-load + view-namespace row is covered indirectly:
[`LiveFieldTest`](../../../packages/forms/tests/Integration/LiveFieldTest.php)
boots [`tests/Fixtures/Kernel.php`](../../../packages/forms/tests/Fixtures/Kernel.php)
(which registers `SylamentFormsBundle`) and renders `TextInput`/`Textarea` through
a live component, and
[`TextInputTest`](../../../packages/forms/tests/Integration/Components/TextInputTest.php)
/ [`TextareaTest`](../../../packages/forms/tests/Integration/Components/TextareaTest.php)
render the field templates — proving the bundle loads and its
`@SylamentForms/components/...` templates resolve. The asset-mapper prepend has a
test of its own,
[`SylamentFormsBundleTest`](../../../packages/forms/tests/Integration/SylamentFormsBundleTest.php),
which resolves the built `checkbox_list_controller.js` through
`AssetMapperInterface` and asserts its `@sylament/forms/...` logical path — the
same shape the actions, panels and support bundle tests use. The unported
responsibilities have no coverage because they have no code.

## Configuration

The bundle's semantic config lives under the derived `sylament_forms` alias (a
bundle cannot extend another bundle's tree). One node ships:
`sylament_forms.temporary_file_upload.{disk, directory, cleanup}`, the store
behind `FileUpload`'s temporary uploads — written up in
[temporary-uploads.md](temporary-uploads.md). The toolkit-wide storage keys
(`sylament.default_filesystem_disk`, `sylament.temporary_file_url_expiry_minutes`)
sit on support's `sylament` tree instead, because tables' `ImageColumn` and
infolists' `ImageEntry` will read them too —
[filesystem-registry.md](../support/filesystem-registry.md).

## Details

### Asset registration

Filament's `packageBooted()` calls `FilamentAsset::register([...],
'filament/forms')` with 12 field `AlpineComponent`s (`checkbox-list`,
`code-editor`, `color-picker`, `date-time-picker`, `file-upload`, `key-value`,
`markdown-editor`, `rich-editor`, `select`, `slider`, `tags-input`, `textarea`).
Sylament's translation is a `prependExtension()` registering
`packages/forms/assets/dist` as `@sylament/forms`, mirroring
[`SylamentNotificationsBundle`](../../../packages/notifications/src/SylamentNotificationsBundle.php)
and guarded by
[`isAssetMapperAvailable()`](../../../packages/support/src/SylamentBundle.php) so
a consumer without AssetMapper is not forced to install it.

That registration now exists, which is what moves the row to **in progress**: it
is the whole path for the package's built JS, and
[`SylamentFormsBundleTest`](../../../packages/forms/tests/Integration/SylamentFormsBundleTest.php)
pins it by resolving the built `checkbox-list` and `select` controllers through
`AssetMapperInterface`. What is still partial is the **inventory** — six of the
twelve are ported
([`checkbox-list`](checkbox-list.md#the-controller-is-the-only-behaviour-and-no-test-in-this-repo-runs-it),
[`select`](#the-select-controller-binds-a-port-of-filaments-select-utility-to-the-live-host),
[`file-upload`](#the-file-upload-controller-bundles-filepond-and-is-the-only-lazy-controller),
and M3 · 9's
[`key-value`](key-value.md#the-controller-is-a-reorder-bridge-and-a-focus-guard-not-a-row-store),
[`tags-input`](tags-input.md#the-chip-list-is-client-owned-after-the-first-paint)
and [`textarea`](textarea.md#the-sizer-grows-on-input-floors-at-the-initial-height-and-never-measures-a-hidden-control)).
Of the six left, five belong to fields that do not exist here yet
(`code-editor`, `color-picker`, `markdown-editor`, `rich-editor`, `slider`); the
sixth, `date-time-picker`, belongs to a field that does, and waits with
[the JS calendar](can-be-native.md#nativefalse-throws-rather-than-falling-back-to-the-native-control)
rather than with its field. Two of the workspace's eight controllers are not
among the twelve at all:
[`repeater`](#the-repeater-controller-owns-the-items-collapse-state-and-bridges-the-drag-control-to-the-reorder-action),
because Filament embeds the repeater's Alpine object in the component's own view
rather than registering it as an asset, and
[`text-input`](text-input.md#copy-writes-the-live-input-value-and-confirms-through-a-status-region),
because Filament's reveal and copy are `x-data` on the field plus an
`alpineClickHandler()` per action — so neither has a vendor counterpart in this
inventory, and neither counts against the arithmetic above.
**Next step:** nothing bundle-side — each remaining controller ships with its
field and needs no further wiring, since the path is registered once for the
directory. The `select` assertion is the one that
depends on a committed artifact: the controller is a `BUNDLED_ENTRIES` build,
so its `dist/controllers/select_controller.js` exists only when the rebuilt dist
is committed, and `AssetMapperRepository::findLogicalPath()` answers `null` for
a missing file — the test fails loudly rather than the consuming app's
controllers-map generation. `file-upload` is a `BUNDLED_ENTRIES` build too, and
carries the same dependency.

### Make* commands + stub publishing

`MakeRichContentCustomBlockCommand` / `MakeFieldCommand` / `MakeFormCommand` /
`MakeLivewireFormCommand` and the `stubs/` publishing loop are unported — the port
has no `Commands/` subtree. **Next step:** port when the `Commands/` subtree is
tackled (tracked as the `Commands / Make*` and `Commands / FileGenerators` todo
rows in the [package README](README.md)).

### Translations

`->hasTranslations()` registers Filament's `resources/lang` namespace. Unported.
**Next step:** port when the translations subtree is tackled.

## Divergences

### The view namespace is registered by Symfony convention, not by an explicit provider call

Filament calls `->hasViews()` to register its Blade namespace. Sylament registers
nothing in the bundle file (its body is a bare `//`): `AbstractBundle::getPath()`
returns `packages/forms`, and TwigBundle auto-maps `packages/forms/templates` →
`@SylamentForms`. The wiring is therefore invisible in the bundle — intentional,
and the reason the field classes can set `$template =
'@SylamentForms/partials/TextInput/index.html.twig'` with no explicit twig path
config. Same divergence as
[schemas-bundle.md](../schemas/schemas-bundle.md#the-view-namespace-is-registered-by-symfony-convention-not-by-an-explicit-provider-call).

### The select controller binds a port of Filament's select utility to the live host

Filament v5 ships a first-party ~2 400-line select control
([`support/…/js/utilities/select.js`](../../../vendor/filament/support/resources/js/utilities/select.js),
Floating UI + SortableJS) that the forms Alpine component
([`resources/js/components/select.js`](../../../vendor/filament/forms/resources/js/components/select.js))
instantiates with `$wire.$entangle` for state and `$wire.callSchemaComponentMethod`
for search. Sylament ports that utility —
[`utils/select.ts`](../../../packages/forms/assets/src/utils/select.ts), every
vendor method under its own name in vendor order — and replaces only the Alpine
wrapper: [`select_controller.ts`](../../../packages/forms/assets/src/controllers/select_controller.ts)
is the Stimulus binding that constructs the engine and joins it to the host live
component. That is the 2026-08-19 swap of the M3 · 4 decision (carried roadmap
decision (3): Tom Select behind a thin integration), taken because the vendor
utility answers by construction what the Tom Select integration was paying for
by hand — the flip-and-shift positioning, the chip reorder, the max-items
announcement, the `$order`/`$id` carry, and the six class settings that skinned
a foreign DOM (the modal escape is not the engine's doing: vendor clips inside
its modals, and ours escapes them through
[support's fixed positioning context](../support/modal.md#the-modal-is-a-fixed-positioning-context)).

- **The engine lives in forms, and only inside the bundle.** `utils/select.ts`
  is filed here because forms is its only consumer; it moves to a support
  workspace on the second one — under the house promotion rule, the same
  deferral the `schema_component_method.ts` note carries. Tables'
  `SelectColumn` was the candidate, and it did **not** trigger the promotion:
  the column catalog (M4 · 4) shipped it
  [native-only](../tables/select-column.md), a plain `<select>` with no JS
  control of its own, so the engine still has exactly one consumer. Vendor's
  `columns/select.js` does import the same utility, so the trigger comes back
  the day the searchable/relationship arms land — which is the deferral that
  row is blocked on, not a separate one.
  AssetMapper is what makes that a real move rather than a re-export: it
  resolves a bare import only through the root importmap
  (`JavaScriptImportPathCompiler::findAssetForBareImport()`), so an
  `@sylament/support/utils/select.js` import would need every consumer's
  importmap touched — which is not what the `schema_component_method.ts` move
  did, since a **bundled** entry's import is resolved at build time (see the
  helper bullet below). The module also ships **only** inlined: it is named in the
  `BUNDLED_ENTRIES` entry's `inlines` list, which excludes it from the per-file
  build, because a per-file `dist/utils/select.js` would carry bare
  `@floating-ui/dom` / `sortablejs` imports that 404 in a browser.
- **It binds to the host by hand.** The JS-branch `<select>` carries no
  `data-model` (a `<select data-model>` under `SetValueOntoModelFieldsPlugin`
  writes the DOM value *back* into the value store after every render, resetting
  an option the render did not know about); the controller reads 25 Stimulus
  values the [Select template](../../../packages/forms/templates/partials/Select/index.html.twig)
  emits (`key`, `statePath`, `modelBinding`, `options`, `selectedOptions`,
  `canSelectPlaceholder`, `searchable`, `dynamicSearchResults`,
  `initialNoOptionsMessage`, `searchDebounce`, `optionsLimit`, `maxItems`,
  `maxItemsMessage`, `htmlAllowed`, `labelsWrap`, `position`, `reorderable`,
  `searchFields`, `placeholder`, `searchPrompt`, `searchingMessage`,
  `noSearchResultsMessage`, `noOptionsMessage`, `loadingMessage`, `badgeClass` —
  `maxItems`/`position` are omitted when unset, so the controller reads
  `hasMaxItemsValue` / `hasPositionValue`), and calls `component.set(statePath,
  value, render, debounce)` itself from the engine's `onStateChange`.
  `statePath` is the only string ever handed to the value store; `modelBinding`
  (`getDataModel()`'s directive) is parsed once for its modifiers only —
  `norender|` ⇒ no render, `debounce(N)|` ⇒ `parseInt`, `on(change)|` and the
  bare path ⇒ render at once. A multiple value is written as a **copy** of the
  engine's array (`ValueStore.set()` reports "unchanged" on reference identity,
  which would stop a `live()` multi-select after its first change) and a null
  single state as `''`. The engine is constructed only after
  `getComponent(closest live root)` resolves — retrying once after the root's
  own `live:connect` when the live controller has not registered yet, matched by
  `event.target` because a **nested** host's `live:connect` bubbles through the
  same element — because the host's `ExternalMutationTracker` must be observing
  when the engine inserts its container, or the first morph removes it. There is
  no "before the host is reachable" fallback: a `<select>` outside any live
  component throws. Nor is there one for a missing controller: a page whose
  `assets/controllers.json` leaves out forms' `select` block renders a JS-branch
  select (`searchable()`, `multiple()`, `native(false)` or `allowHtml()`) as a
  plain `<select>` that neither searches nor sends what the user picked, with no
  error; a select that is none of those four is native, binds through its own
  `data-model`, and needs no JavaScript. The `<select>` itself stays as the inert carrier: the
  controller takes its `id` for the engine's combobox button and restores it on
  disconnect ([the id handoff](select.md#the-hidden-select-lends-its-id-to-the-combobox-button)),
  hides it with an inline `display: none` (the stylesheet's `select.sy-fo-select
  { display: block }` outranks the UA's `[hidden]` rule), and the engine inserts
  its container as the `<select>`'s next sibling — the slot Tom Select used, so
  the tracker records a client-added sibling and every morph keeps it. When the
  `<select>` carries `sy-fo-select-has-inline-prefix` the controller puts
  `sy-fo-select-ctn-has-inline-prefix` on that container, which is how the
  inline-prefix padding fix survives the engine swap.
- **The server render is the option and selection truth.** The controller passes
  `hasDynamicOptions: false` and `getOptionsUsing` / `getOptionLabelUsing` /
  `getOptionLabelsUsing` all `null`, so the engine's own fetch fallbacks are
  never reached — only `getSearchResultsUsing` leaves the browser. Labels arrive
  through the render instead: `selectedOptions` seeds `initialOptionLabel`
  (single) or `initialOptionLabels` (multiple) at construction and is written
  into the engine's `labelRepository` — its value→label memo — by
  `rememberLabels()` at construction and again on every re-sync. That is also
  why [`loadingMessage()` still has no render site](select.md#loadingmessage-has-no-render-site):
  the dynamic-options open fetch it accompanies in Filament does not exist here.
- **Every host render re-syncs the control** (`component.on('render:finished')`,
  plus the `sylament:select-refresh-selected-option-label` browser event,
  accepted only when `event.target` is the host root and `detail.statePath`
  matches). The re-sync is four steps. `replaceOptions()` hands the engine the
  freshly rendered pool: it always becomes `originalOptions` and refills the
  label repository, but the visible list is re-rendered only when the pool
  actually changed *and* no search query is typed — under an active query the
  results on screen are left alone and the new pool waits for the next clear
  (M3 · 4's unrelated-render rule, now expressed in the engine's own
  vocabulary), and the changed-pool guard is what stops an unrelated render from
  dropping keyboard focus out of an open list. `rememberLabels()` then refills
  the repository from `selectedOptions`. The value store's entry for `statePath`
  is normalised to the strings the engine compares (single: `''` or `String(v)`;
  multiple: the non-blank entries as strings) and written onto the engine only
  when it differs **structurally** — as an ordered list under `reorderable()`,
  where the order is state and a server render holding the same values in
  another order must re-sync the chips, and as a multiset otherwise, so an
  unrelated render does not rebuild the chips over a mere reordering; after a
  drag the engine has already written the new order out, so the next render
  matches and nothing is rebuilt. Finally `disabled` follows the `<select>`
  through `disable()` / `enable()`, and the `<select>`'s `aria-invalid` and
  `aria-required` are mirrored onto the combobox button (here and at
  construction), since the hidden `<select>` is out of the accessibility tree
  and Filament's button carries neither.
  The refresh event runs that same re-sync and then `refreshLabels()`, which
  repaints the selection and its option row from the repository the render just
  refilled.
- **Search is a live round-trip through the schema-method seam.** With
  `dynamicSearchResults`, `getSearchResultsUsing` is
  `callSchemaComponentMethod(component, key, 'getSearchResultsForJs', {search})`
  from [`schema_component_method.ts`](../../../packages/schemas/assets/src/utils/schema_component_method.ts);
  everything around it is the engine's, ported verbatim — the `searchDebounce`
  timer, the `activeSearchId` guard that drops a stale response, the searching /
  no-results messages, the reset to `originalOptions` when the query is emptied,
  the `console.error` and pool restore on a rejected call (the host has already
  surfaced the failure through its own error overlay), and the local
  `filterOptions()` over `searchableOptionFields` for a searchable select with
  no dynamic arm.
- **The helper now lives in schemas**, and this controller imports it across
  workspaces. It keeps a FIFO of pending resolvers per
  `${componentKey}\0${method}` on the host, listens once per component for the
  result event (ignoring nested hosts' bubbling copies), and rejects every entry
  on `response:error` or when the action's promise resolves with the entry still
  pending — all unchanged. What changed in M3 · 5 is its home: the deferral this
  bullet used to carry ("moves to a schemas workspace on its second consumer")
  came due when Wizard's `nextStep` and Tabs' `getDeferredTabBadges` became that
  consumer, so the file moved to
  [`packages/schemas/assets/src/utils/`](../../../packages/schemas/assets/src/utils/schema_component_method.ts)
  and this import became the bare specifier
  `@sylament/schemas/utils/schema_component_method.js`, resolved through schemas'
  `exports` map (`{"./utils/*.js": "./src/utils/*.ts"}`) and a
  `"@sylament/schemas": "workspace:*"` devDependency of this workspace. It costs a
  consumer nothing: this entry is a `BUNDLED_ENTRIES` build, so esbuild inlines
  the helper at build time and no bare `@sylament/schemas/...` specifier ever
  reaches a browser or an importmap — which is exactly why the same move is *not*
  available to `utils/select.ts`'s eventual promotion under the per-file build.
  `packages/forms/assets/dist/utils/schema_component_method.{js,js.map}` were
  deleted in the same commit; the standalone copy ships from schemas' `dist` now,
  and the whole workspace shape is written up in
  [schemas-bundle.md](../schemas/schemas-bundle.md#the-schema-component-method-helper-lives-here-now).
  The controller's own private `resolveHost()` did **not** move: schemas ships the
  generalised [`resolveLiveHost()`](../../../packages/schemas/assets/src/utils/live_host.ts)
  its three controllers use, and collapsing the two is a sweep item — it would be
  a second cross-workspace import for no build-time gain, since this controller
  already has the code inlined.
- **Reorder is the `reorderable` value.** The controller passes it as
  `isReorderable` at construction and, because Stimulus calls a value's changed
  callback on every morph that rewrites it, `reorderableValueChanged()` flips
  the engine's flag and rebuilds the badges (Filament re-keys the whole control
  on that flag instead). The engine does the rest: a SortableJS instance on the
  badges container, `animation: 150`, whose `onEnd` reads the `[data-value]`
  order back out of the DOM and reports it through `onStateChange` — so the drag
  writes straight to the value store like any other change.
- **The port corrects seven vendor defects and drops one listener.** The seven —
  the badge remove buttons' dead disabled selector, `enable()` adding the
  disabled class to the clear button, the un-destroyed `Sortable` (and a
  positioning frame left queued past `destroy()`), `form.submit()` where
  `requestSubmit()` is meant, the label `<span>` dropped from an updated
  plain-text option, an option value interpolated raw into a `querySelector`
  selector, and an emptied search box that let an in-flight search land on it —
  are recorded with their reasoning in
  [select.md](select.md#the-engine-port-corrects-seven-vendor-defects). The drop
  is vendor's `filament-forms::select.refreshSelectedOptionLabel` **window**
  listener and its `livewireId`: the refresh reaches us as a host browser event
  the controller matches, so the engine has no Livewire-era hook at all.
  Positioning is otherwise vendor's, including the two opt-in context classes —
  and support's modal now stamps `sy-fixed-positioning-context`, which is what
  lets the dropdown [flip and escape a modal's overflow](select.md#the-dropdown-flips-and-escapes-the-modal).
- **`disconnect()`** unhooks `render:finished` and the refresh listener (the
  host outlives a hidden field, a removed repeater row, a closed modal — a stale
  hook throwing inside `processRerender()` aborts the host's render),
  `destroy()`s the engine (which removes its own listeners, its Sortable and its
  container), and restores the `<select>`'s `id` and default `display`. No
  markup snapshot is needed: the engine never touches the `<select>`'s children,
  which is the whole reason its container is a sibling.
- **Dependencies and licence.** `@floating-ui/dom` and `sortablejs` — both MIT —
  are `dependencies` of the workspace and inlined into
  `dist/controllers/select_controller.js` through `BUNDLED_ENTRIES`, with
  `@hotwired/stimulus` and `@symfony/ux-live-component` left external (the
  latter dev **and** peer at `^3.2.0`, the actions precedent, since
  `getComponent` stays a bare specifier the consuming app's importmap resolves);
  neither is in a consumer's importmap, so neither has a `symfony.importmap`
  line. The entry's `license` field is a **list**, and `licenseBanner()` heads
  the bundle with one `/*! … */` line per package composed from the installed
  `package.json` and `LICENSE` — there is no licence *file* beside the bundle
  any more, because that was Apache-2.0 §4's demand for Tom Select and MIT is
  served by the banner, the way support's bundled `@floating-ui/dom` already is.
  `@types/sortablejs` is dev-only (sortablejs ships none), as is the
  `@sylament/schemas` workspace link the helper import needs — a `workspace:*`
  specifier that reaches the split `sylament/forms` archive and is inert there
  (nobody `npm install`s a Sylament package, and StimulusBundle reads only the
  `symfony` key). `@floating-ui/dom`
  is already inlined twice on a page — support's dropdown controller carries its
  own copy — and `sortablejs` is too, since M3 · 6's sortable controller in
  support carries its own: Filament's own per-bundle inlining, ~10 KB gzipped
  each, recorded and accepted.
- **No test in this repo runs the controller or the engine** — the
  [`checkbox-list` note](checkbox-list.md#the-controller-is-the-only-behaviour-and-no-test-in-this-repo-runs-it)
  applies verbatim, and a JS rig was weighed for the engine and declined on
  2026-08-19: its verification is the reviewer's method-by-method diff against
  vendor's `select.js` plus the demo's browser pass.

### The repeater controller owns the items' collapse state and bridges the drag control to the reorder action

[`repeater_controller.ts`](../../../packages/forms/assets/src/controllers/repeater_controller.ts)
is `sylament--forms--repeater`, the third controller in this workspace and the
port of the client half of Filament's repeater Alpine object
([`Repeater.php`'s embedded view](../../../vendor/filament/forms/src/Components/Repeater.php)).
It reads two Stimulus values — `key` (the schema component key, the only string
ever handed to the host) and `persist` (whether collapse state survives a
reload) — and one target, `item`, which the
[Repeater partials](../../../packages/forms/templates/partials/Repeater) stamp on
every item element (the `<li>` in default and simple mode, the `<tr>` in table
mode). Six methods carry the behaviour:

- `toggleItemFromHeader` — bound on the item header's `click`, ignoring clicks
  that landed inside `.sy-fo-repeater-item-header-start-actions` or
  `.sy-fo-repeater-item-header-end-actions` so the reorder/clone/delete buttons
  are not also a collapse toggle. The [Section controller's
  `toggleFromHeader()`](../../../packages/support/assets/src/controllers/section_controller.ts)
  is the precedent, selector for selector.
- `toggleItemFromButton` — bound on the collapse/expand button pair's `click`,
  and it calls `event.stopPropagation()` **by hand** rather than taking the
  action option: `:stop` needs Stimulus 3.2 and the workspace's peer range is
  `^3.0`. That is the one comment kept in the file body, for parity with the
  Section controller, which carries the same line for the same reason.
- `expandItem` — bound on the item's own `expand` event, the non-bubbling
  per-ancestor reveal the [schema root dispatches after a validation
  failure](../schemas/schemas-bundle.md#the-expand-walk-and-the-two-window-events-either-side-of-it).
  `event.currentTarget` is the element carrying the `data-action`, so the item
  resolves without a detail payload.
- `collapseAll` / `expandAll` — bound on the two actions-bar buttons, walking
  `this.itemTargets`. Stimulus scopes targets and actions to the nearest
  controller of the same identifier, so a nested repeater collapses only its own
  rows.
- `reorder` — the drag bridge, below.

**The collapse state is an attribute the controller stamps, not a class the
server owns.** Each item carries `data-collapsed="true"|"false"` from the moment
this controller sees it; the server-rendered `sy-collapsed` class is **first
paint only** and is read exactly once, as the seed for that stamp. The reason is
UX LiveComponent's `ExternalMutationTracker`: it replays a client mutation over
a morph only when it recorded one, and it records neither of the two writes the
obvious implementation would make. `handleGenericAttributeMutation()` bails on
`newValue === oldValue`, so re-`setAttribute`ing the value already there is
nothing; `handleClassAttributeMutation()` diffs the old and new token lists, so
`classList.add()` of a class the element already carries is nothing either. A
`null → "true"` attribute write is the one shape the tracker reliably records,
which is why `#stamp()` writes the attribute on first sight even when the value
it writes is `"false"`, and why `#setCollapsed()` never touches the class. The stamp is guarded on `hasAttribute()`, so an item
the morph patched **in place** keeps whatever the user last chose, while an item
the morph **re-created** arrives unstamped and is re-seeded from its server
class. `#isCollapsed()` mirrors that fallback order (attribute, then class).

Items are stamped from two places, and the redundancy is deliberate:
`itemTargetConnected()` covers rows the server adds later, and the `connect()`
loop is the belt-and-braces pass. On the current Stimulus (3.2.2) the
`connect()` loop is in fact always the second of the two —
`Context.connect()` starts the target observer *before* it calls
`controller.connect()`, so every initial item has already been stamped by the
time the loop runs — but `#stamp()` is idempotent, so the order is not
load-bearing either way. `connect()` also `uncloak()`s the root, which is how a
`persist`ing repeater avoids showing every item expanded for one frame before
storage is read; [`utils/cloak.ts`](../../../packages/forms/assets/src/utils/cloak.ts)
is a **byte-for-byte duplicate** of support's, for the same reason schemas
carries one — the per-file build ships specifiers verbatim, so a cross-package
import would have to be a bare specifier every consumer's importmap resolves
([schemas-bundle.md](../schemas/schemas-bundle.md#the-schema-component-method-helper-lives-here-now)).

Persistence is `window.localStorage` under `repeater-<key>-<itemKey>-isCollapsed`
(`itemKey` from the item's `data-item-key`), written on every `#setCollapsed()`
and read once per item in `#stamp()`. Both accessors are the Section
controller's `try`/`catch` helpers verbatim, including their two comments — a
browser with storage disabled falls back to the server's default and still
collapses, it just does not remember.

**Reorder goes through the host, not through the DOM.** The `<ul>` / `<tbody>`
carries `data-action="sylament--support--sortable:end->sylament--forms--repeater#reorder"`,
so [support's drag control](../support/sortable.md) hands this controller the
new key order in the event's `detail.items`. A non-array detail returns
silently; otherwise the controller resolves the closest `[data-controller~="live"]`
root — throwing "The sylament--forms--repeater controller needs a live component
host to reorder." when there is none, the select controller's message shape —
and calls `component.action("mountAction", { name: "reorder", arguments: { items },
context: { schemaComponent: this.keyValue } })`, i.e. the repeater's own
`reorder` action mounted against its schema component. `getComponent()` is
called at drag time rather than at connect, so there is no `live:connect` race
to retry around: a user cannot drag a row before the host has connected, which
is why this controller needs neither the select controller's private
`resolveHost()` nor schemas'
[`resolveLiveHost()`](../../../packages/schemas/assets/src/utils/live_host.ts).

It ships **per-file** (no `BUNDLED_ENTRIES` entry, no new dependency): the built
`dist/controllers/repeater_controller.js` keeps `@hotwired/stimulus`,
`@symfony/ux-live-component` and `../utils/cloak.js` as verbatim specifiers, the
first two resolved by the consuming app's importmap — `@symfony/ux-live-component`
being already both a dev and a peer dependency of this workspace for the select
controller's sake. Its `symfony.controllers` entry is `fetch: eager`, matching
the other two.

**No test in this repo runs it** — the
[`checkbox-list` note](checkbox-list.md#the-controller-is-the-only-behaviour-and-no-test-in-this-repo-runs-it)
applies verbatim: verification is the reviewer's line-by-line read against the
vendor Alpine object plus the demo's browser pass.

### The file-upload controller bundles FilePond and is the only lazy controller

Filament's `file-upload.js` is an Alpine component over FilePond that reaches
Livewire's temporary-upload pipeline (`$wire.upload()`, `$wire.cancelUpload()`)
and entangles the field's state. Ours is
[`file_upload_controller.ts`](../../../packages/forms/assets/src/controllers/file_upload_controller.ts),
and the shape differs in seven ways worth knowing before touching it.

- **`fetch: lazy`, the only one in the toolkit.** The bundle is ~743 KB
  un-minified (FilePond core plus its eight plugins and Cropper.js, ten MIT
  banners composed by `build-js.mjs`) and appears on few pages; every other
  Sylament controller is `eager`. StimulusBundle therefore fetches it only when
  a `data-controller="sylament--forms--file-upload"` element appears, which is
  exactly the trade this size justifies. Nothing else in the toolkit is lazy, so
  a consuming app that copies another controller's `controllers.json` entry will
  make it eager by accident. Leaving the entry out fails total rather than
  partial: what remains is the bare `<input type="file">` the template renders
  under the label, with no `name` and no `data-model`, and nothing listening to
  it — no drop zone, no preview, and a picked file never leaves the page.
- **No `mime` package.** Filament's `fileValidateTypeDetectType` falls back to the
  npm `mime` database, which is the single largest block of its 373 KB bundle.
  Ours resolves `mimeTypeMap[extension] || detectedType` and stops there, so the
  one degraded case is a file whose browser type is empty and whose extension the
  field's `mimeTypeMap()` does not name — which is what that setter exists for.
- **Upload is one live round trip, so there is no byte progress and no true
  abort.** `uploadSchemaComponentFile()` posts the file as a multipart part of a
  live action; `fetch()` reports no upload progress, so `progress(false, 0, 0)`
  puts FilePond's indicator in indeterminate mode, and FilePond's abort can only
  mark the key cancelled — when the request lands, the controller calls
  `removeUploadedFile` and *then* aborts the item.
- **The state is reconciled by hand.** Filament entangles `state` through Alpine
  and re-reads the server on every change; our pond subtree is `data-live-ignore`,
  so the controller re-reads the `state` and `uploadedFiles` Stimulus values after
  each morph and rebuilds `pond.files` only when the state JSON changed and holds
  no `sylament-file:` token — the twin of Filament's `$watch('state')`, gated on
  the same four short-circuits plus an active-upload counter. Note the gate is
  keyed on **state** alone: `uploadedFiles` carries temporary URLs that can
  regenerate on every render, and keying on those would rebuild the pond
  continuously.
- **`disabled()` flows through an option, not a key bust.** Filament hashes
  `isDisabled()` into its `wire:key` so Livewire destroys and rebuilds the whole
  subtree; `disabledValueChanged()` calls `pond.setOptions({disabled})` instead.
- **The `<input>` is captured once.** `FilePond.create()` detaches the element it
  replaces, so `this.inputTarget` throws afterwards while `FilePond.destroy()`
  needs that same element (and restores it, which is what lets a later `connect()`
  find the target again). Everything downstream reads the captured reference.
- **Four JS-owned literals join the four PHP-side ones**
  [file-upload.md](file-upload.md#literals-not-translation-keys) opens with.
  `The file could not be uploaded.` is Filament's; `The file could not be
  removed.`, `The file could not be loaded.` and `The files could not be
  reordered.` are ours, because Filament's
  `revert`/`remove` never call FilePond's `error` at all, its `load` resolves a
  failed fetch as an empty blob, and its `reorderfiles` handler `await`s the
  server inside an event listener nothing catches — a silent failure every time.
  Literals, not translation keys, per the epic's carried decision (4).

Two shipped-type gaps are worked around in the file and will bite anyone
upgrading FilePond: `initfile` is a real event (`filepond.esm.js:8842`) missing
from the `FilePondEvent` union, and `maxFileSize`/`minFileSize` are `Type.INT`
options declared `string` — the controller passes the byte ceiling as a decimal
string, which FilePond parses back to exactly that integer. The `error` event's
first argument is `{main, sub}` for a validation failure and `{type, code, body}`
for a transport one, so the message is composed defensively rather than rendered
as Filament's `undefined: undefined`.

Six sweep items the image-editor epic folded into the same file are worth
reading as a set, because each is a shape that reappears whenever a seam call or
a FilePond option is added. A cancelled upload's key is consumed on **both**
settle arms and the item is `abort()`ed rather than `error()`ed, so the
cancelled set cannot leak and a cancelled item leaves no error chrome behind.
The two `void` seam calls grew rejection arms: a failed `reorderUploadedFiles`
surfaces `The files could not be reordered.` and clears `#lastState`, the
reconcile memo, so the next morph rebuilds the list in the server's order, and
the cancel-time `removeUploadedFile` surfaces `The file could not be removed.`.
`FilePond.create()` runs inside `try/finally` so the `#initializing` re-entrance
flag cannot stick after a throw, `maxFileSize` is passed only under
`hasMaxFileSizeValue` (an absent value is not a zero ceiling), and
`resolveLiveHost()`'s rejection surfaces through the error banner instead of an
unhandled promise rejection. The sixth is a Filament fidelity note rather than a
bug: `#detectType` resolves `mimeTypeMap[extension] || detectedType` with `||`,
not `??`, so a map entry deliberately set to the empty string falls through to
the browser's detected type — Filament's operator, and the reason an empty entry
is a "let the browser decide" instruction rather than a hard "no type".

### Livewire-specific provider responsibilities are dropped, not ported

`packageBooted()` registers the `table-select` Livewire component
(`Livewire::component('table-select', TableSelectLivewireComponent::class)`) and
two `Testable::mixin()` calls (`TestsForms`, `TestsFormComponentActions`). The
Testable mixins are a deliberate non-goal (**skipped** — Sylament tests are
Pest/`KernelTestCase`, not Livewire `Testable`;
[`LiveFieldTest`](../../../packages/forms/tests/Integration/LiveFieldTest.php)
drives fields through UX LiveComponent's `InteractsWithLiveComponents` instead).
The `table-select` registration is **blocked** on the TableSelect field feature
landing (the field, its `ModalTableSelect`, and a UX LiveComponent host
equivalent), not skipped — it is wanted for parity once table-select exists. Both
are tracked as their own rows in the [package README](README.md) supporting
surface (`TableSelectLivewireComponent`, `TestsForms`,
`TestsFormComponentActions`).

_The `FormsServiceProvider` → `SylamentFormsBundle` swap (and the underlying
Spatie `PackageServiceProvider` → Symfony `AbstractBundle` move) is a systematic
Symfony-idiom translation, like those the [rename table](../renames.md) collects
under "Not renames" — Laravel package tooling with no per-name Sylament equivalent
to enumerate — not a per-item rename or divergence._
