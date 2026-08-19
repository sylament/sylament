# SylamentFormsBundle (forms)

[`Sylament\Forms\SylamentFormsBundle`](../../../packages/forms/src/SylamentFormsBundle.php)
— the forms bundle class. It ports Filament's
[`FormsServiceProvider`](../../../vendor/filament/forms/src/FormsServiceProvider.php)
(Spatie's `PackageServiceProvider` lifecycle becomes a Symfony `AbstractBundle`
via [`SylamentBundle`](../../../packages/support/src/SylamentBundle.php)). It
does two things and inherits a third. `loadExtension()` PSR-4-loads the
`Components/` directory so every field is a container-built, non-shared service —
the toolkit-wide [component model](../../../CLAUDE.md), not anything
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
| [`sylament--forms--select`](../../../packages/forms/assets/src/controllers/select_controller.ts) — the JS select control, a port of Filament's select utility ([`utils/select.ts`](../../../packages/forms/assets/src/utils/select.ts)) bound to the live host, plus the [`callSchemaComponentMethod`](../../../packages/forms/assets/src/utils/schema_component_method.ts) helper it searches through | [`resources/js/components/select.js`](../../../vendor/filament/forms/resources/js/components/select.js) + [`support/…/js/utilities/select.js`](../../../vendor/filament/support/resources/js/utilities/select.js) | done | — | [diverges](#the-select-controller-binds-a-port-of-filaments-select-utility-to-the-live-host) |
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
pins it by resolving both built controllers through `AssetMapperInterface`. What
is still partial is the **inventory** — two of the twelve are ported
([`checkbox-list`](checkbox-list.md#the-controller-is-the-only-behaviour-and-no-test-in-this-repo-runs-it)
and [`select`](#the-select-controller-binds-a-port-of-filaments-select-utility-to-the-live-host),
the package's two Stimulus controllers) and the other ten belong to fields that
do not exist here yet. **Next step:** nothing bundle-side — each remaining
controller ships with its field and needs no further wiring, since the path is
registered once for the directory. The `select` assertion is the one that
depends on a committed artifact: the controller is a `BUNDLED_ENTRIES` build,
so its `dist/controllers/select_controller.js` exists only when the rebuilt dist
is committed, and `AssetMapperRepository::findLogicalPath()` answers `null` for
a missing file — the test fails loudly rather than the consuming app's
controllers-map generation.

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
  workspace on the second one — tables' `SelectColumn` (M4 · 4, whose vendor
  `columns/select.js` imports the same utility) — under the house promotion
  rule, the same deferral the `schema_component_method.ts` note carries.
  AssetMapper is what makes that a real move rather than a re-export: it
  resolves a bare import only through the root importmap
  (`JavaScriptImportPathCompiler::findAssetForBareImport()`), so an
  `@sylament/support/utils/select.js` import would need every consumer's
  importmap touched. The module also ships **only** inlined: it is named in the
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
  component throws. The `<select>` itself stays as the inert carrier: the
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
  from [`schema_component_method.ts`](../../../packages/forms/assets/src/utils/schema_component_method.ts);
  everything around it is the engine's, ported verbatim — the `searchDebounce`
  timer, the `activeSearchId` guard that drops a stale response, the searching /
  no-results messages, the reset to `originalOptions` when the query is emptied,
  the `console.error` and pool restore on a rejected call (the host has already
  surfaced the failure through its own error overlay), and the local
  `filterOptions()` over `searchableOptionFields` for a searchable select with
  no dynamic arm.
- **The helper** keeps a FIFO of pending resolvers per
  `${componentKey}\0${method}` on the host, listens once per component for the
  result event (ignoring nested hosts' bubbling copies), and rejects every entry
  on `response:error` or when the action's promise resolves with the entry still
  pending. It lives in forms today and moves to a schemas workspace on its
  second consumer (Wizard/Tabs, M3 · 5).
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
  `@types/sortablejs` is dev-only (sortablejs ships none). `@floating-ui/dom`
  is already inlined twice on a page — support's dropdown controller carries its
  own copy — and `sortablejs` will be once M3 · 6 lands its sortable controller
  in support: Filament's own per-bundle inlining, ~10 KB gzipped each, recorded
  and accepted.
- **No test in this repo runs the controller or the engine** — the
  [`checkbox-list` note](checkbox-list.md#the-controller-is-the-only-behaviour-and-no-test-in-this-repo-runs-it)
  applies verbatim, and a JS rig was weighed for the engine and declined on
  2026-08-19: its verification is the reviewer's method-by-method diff against
  vendor's `select.js` plus the demo's browser pass.

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
