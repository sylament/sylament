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
| [`sylament--forms--select`](../../../packages/forms/assets/src/controllers/select_controller.ts) — the JS select control, bundled over Tom Select, plus the [`callSchemaComponentMethod`](../../../packages/forms/assets/src/utils/schema_component_method.ts) helper it searches through | [`resources/js/components/select.js`](../../../vendor/filament/forms/resources/js/components/select.js) + [`support/…/js/utilities/select.js`](../../../vendor/filament/support/resources/js/utilities/select.js) | done | — | [diverges](#the-select-controller-is-a-thin-binding-over-a-bundled-tom-select-not-a-port-of-filaments-select-utility) |
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
and [`select`](#the-select-controller-is-a-thin-binding-over-a-bundled-tom-select-not-a-port-of-filaments-select-utility),
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

### The select controller is a thin binding over a bundled Tom Select, not a port of Filament's select utility

Filament v5 ships a first-party ~2 400-line select control
([`support/…/js/utilities/select.js`](../../../vendor/filament/support/resources/js/utilities/select.js),
Floating UI + SortableJS) that the forms Alpine component
([`resources/js/components/select.js`](../../../vendor/filament/forms/resources/js/components/select.js))
instantiates with `$wire.$entangle` for state and `$wire.callSchemaComponentMethod`
for search. Sylament's
[`select_controller.ts`](../../../packages/forms/assets/src/controllers/select_controller.ts)
is instead a Stimulus controller over **Tom Select 2.6.2** (`tom-select/base` +
the `dropdown_input` and `clear_button` plugins, bundled into
`dist/controllers/select_controller.js` through the workspace's `BUNDLED_ENTRIES`
table with `@hotwired/stimulus` and `@symfony/ux-live-component` left external —
the `@floating-ui/dom` precedent in support). It is the M3 · 4 decision
(carried roadmap decision (3): Tom Select behind a thin integration; the
`symfony/ux-autocomplete` controller was weighed and set aside because its fetch
is a headerless GET no live action accepts and its only override seam replaces
everything the controller exists to build). The behaviour it owns, and where it
departs from both Filament and the vendor defaults:

- **It binds to the host by hand.** The JS-branch `<select>` carries no
  `data-model` (a `<select data-model>` under `SetValueOntoModelFieldsPlugin`
  writes the DOM value *back* into the value store after every render, resetting
  an option the render did not know about); the controller reads 24 Stimulus
  values the [Select template](../../../packages/forms/templates/partials/Select/index.html.twig)
  emits (`key`, `statePath`, `modelBinding`, `options`, `selectedOptions`,
  `canSelectPlaceholder`, `searchable`, `dynamicSearchResults`,
  `initialNoOptionsMessage`, `searchDebounce`, `optionsLimit`, `maxItems`,
  `maxItemsMessage`, `htmlAllowed`, `labelsWrap`, `position`, `searchFields`,
  `placeholder`, `searchPrompt`, `searchingMessage`, `noSearchResultsMessage`,
  `noOptionsMessage`, `loadingMessage`, `badgeClass` — `maxItems`/`position` are
  omitted when unset, so the controller reads `hasMaxItemsValue` and an empty
  `position`), and calls `component.set(statePath, value, render, debounce)`
  itself on Tom Select's `change`. `statePath` is the only string ever handed to
  the value store; `modelBinding` (`getDataModel()`'s directive) is parsed once
  for its modifiers only — `norender|` ⇒ no render, `debounce(N)|` ⇒ `parseInt`,
  `on(change)|` and the bare path ⇒ render at once. A multiple value is written
  as a **copy** of Tom Select's `items` (it hands back the live array, and
  `ValueStore.set()` reports "unchanged" on reference identity, which would stop
  a `live()` multi-select after its first change). `loadingMessage` is read and
  unused: the dynamic-options open fetch it accompanies in Filament does not
  exist here (the server render is the option truth), so it has no render site.
- **Tom Select is constructed only after the host resolved.** `connect()` waits
  for `getComponent(closest live root)` — retrying once after the root's own
  `live:connect` when the live controller has not registered yet, matched by
  `event.target` because a **nested** host's `live:connect` bubbles through the
  same element — because the host's `ExternalMutationTracker` must be observing
  when Tom Select inserts its wrapper, or the first morph removes it. There is no
  "before the host is reachable" fallback: a `<select>` outside any live component
  throws.
- **Every host render re-syncs the control** (`component.on('render:finished')`,
  plus the `sylament:select-refresh-selected-option-label` browser event,
  accepted only when `event.target` is the host root and `detail.statePath`
  matches). The cached server pool is rebuilt from `options` ∪ `selectedOptions`
  and applied through `clearOptions()` (which keeps the selected options) plus a
  content diff — an option is `addOption`ed when missing and `updateOption`ed
  only when its label, disabled flag or group changed, so an unrelated render
  does not re-render every chip. Each option also carries an explicit `$order`
  off its pool index: `clearOptions()` keeps the selected option with its
  original `$order` while every re-added option takes a fresh, higher one from
  Tom Select's own counter, and sifter sorts `$score` then `$order` — so without
  the carry a preloaded select shows its current value **first** in the dropdown
  after any render. An order-only change is written straight onto the kept
  option's data and marks the dropdown content stale, rather than paying an
  `updateOption()` re-render for a move. `$id` is carried by hand beside
  `$order`, for a sharper reason: `addOption()` mints it as
  `<inputId>-opt-<$order>` but `updateOption()` copies `$order` and **not**
  `$id`, and `_render('option')` *removes* the attribute when `$id` is
  undefined — so a label edit through `refreshSelectedOptionLabel()`, or a
  `disableOptionWhen` flip, would clear the `aria-activedescendant` target of
  the very option it re-renders. The prefix is read off the `<select>`'s own
  `id` rather than Tom Select's private `inputId`, which is the same string:
  the constructor derives one from the other through
  `getId(input, 'tomselect-N')`, which sets the attribute when it is absent.
  The sync then floors Tom Select's own counter at the pool size
  (`ts.order = Math.max(ts.order, pool.length)`), so an option a search adds
  afterwards — minting its `$order` from that counter — can never land on an
  `-opt-N` a pool index already holds. One residual stays open on purpose: an
  option kept across a pool *reorder* keeps its old `$id` (re-minting would
  desync it from Tom Select's cached rendered `div`), so a fresh option minted at
  that pool index can still share its `-opt-K` string until the next full
  re-render — closing it means `uncacheValue()` on the order-only branch, i.e.
  the re-render that branch exists to avoid, and it is left for the sweep. That whole pool re-sync is skipped
  **while** a dynamic search query is typed, in which case only the selection's
  own options are topped up (an unrelated render must not discard the results on
  screen; the search response's own results land one microtask after
  `render:finished`, so they never race it). The value is
  then `setValue(valueStore.get(statePath), silent)`, but only when it differs
  from Tom Select's items: `addItem` closes the dropdown whenever
  `closeAfterSelect` holds and the control is full, so a blind re-set would shut
  an open single select on every render. In **multi** mode that comparison is
  order-insensitive, because the server's array order need not match the chip
  order and `setValue()` is a clear plus `addItems` whose last `addItem` calls
  `refreshOptions(isFocused && multi)` — which would open a focused control's
  dropdown on an otherwise unrelated render. Disabled follows the `<select>`
  through `disable()`/`enable()`.
- **Search is a live round-trip through the schema-method seam.** With
  `dynamicSearchResults`, `load(query, cb)` calls
  `callSchemaComponentMethod(component, key, 'getSearchResultsForJs', {search})`
  from [`schema_component_method.ts`](../../../packages/forms/assets/src/utils/schema_component_method.ts)
  (`loadThrottle` = `searchDebounce`, `shouldLoad` = a non-empty query),
  blanks the list under the `searchingMessage` while it is in
  flight, `clearOptions()`s and hands the flattened results (Filament's
  `{label, options}` groups become an `optgroup` field plus `addOptionGroup`) to
  Tom Select. `refreshThrottle` is **zeroed for every select**, dynamic or
  not, because Tom Select delays its own `_onInput()` by it (300 ms by
  default) before either the throttled `load` or the local sifter filter is
  reached: on the dynamic arm the default would silently make the effective
  debounce `refreshThrottle + searchDebounce` where Filament's
  `searchDebounce` is the whole delay, and over a static option map it would
  lag the local filter 300 ms behind each keystroke where Filament's is
  instant. Blanking the list is the stale flag rather
  than the refresh: `_onInput()` has already run `refreshOptions()` for this same
  query, so `search()` would answer from `currentResults` and leave the previous
  list up — `isDropdownContentStale = true` before the refresh is what makes the
  `searchingMessage` the only thing on screen (Filament's `showLoadingState`).
  Stale results are
  dropped by a search id that also advances when the query is emptied or the
  dropdown closes; a dropped response is **settled by hand** (`loading` decremented,
  the `loading` class cleared, the open list refreshed) rather than through
  `loadCallback([], [])`, whose focus-driven `refreshOptions(true)` would reopen
  a dropdown the user has since closed. A load whose query no longer matches the
  input when its debounce fires (the query was emptied — Tom Select cancels a
  pending load only for a newer one) is discarded the same way, and its
  `loadedSearches` mark removed so the query stays searchable. `score` returns
  1 only for the values the last response carried (or for everything while no
  search is active): sifter's own scoring would re-filter the server's results by
  text, and a constant 1 would keep the selected option in every result list and
  hide `no_results` behind it. On the query's clear or the dropdown's close the
  pool is restored (Filament's `originalOptions` reset). A rejected call — the
  host's `response:error`, or a request that settled without its result event —
  falls back to the pool and clears the `role="status"` announcement (otherwise
  the region keeps saying "Searching…" for a search that has already failed), as
  Filament's catch does; the host has already surfaced the error. Non-dynamic
  searchable selects run Tom Select's own sifter over `searchFields` with no
  request.
- **The helper** keeps a FIFO of pending resolvers per
  `${componentKey}\0${method}` on the host, listens once per component for the
  result event (ignoring nested hosts' bubbling copies), and rejects every entry
  on `response:error` or when the action's promise resolves with the entry still
  pending. It lives in forms today and moves to a schemas workspace on its
  second consumer (Wizard/Tabs, M3 · 5).
- **Markup hooks.** The six class settings carry house classes beside the vendor
  ones (`sy-fo-select-ctn ts-wrapper` — plus `sy-fo-select-ctn-labels-not-wrapped`
  under `wrapOptionLabels(false)` —, `sy-fo-select-control ts-control`,
  `sy-dropdown-panel sy-fo-select-dropdown ts-dropdown` — plus `sy-position-top`
  under `position('top')` —, `sy-dropdown-list sy-fo-select-options
  ts-dropdown-content`, `sy-fo-select-item item`, `sy-dropdown-item
  sy-fo-select-option option`); the templates emit
  `<div><span class="sy-fo-select-option-label">` / `sy-fo-select-item-label`,
  `sy-fo-select-option-group optgroup` / `sy-fo-select-option-group-label
  optgroup-header`, `sy-fo-select-message` for the three messages
  (`not_loading` renders `noOptionsMessage` only when `initialNoOptionsMessage`
  holds and the pool has no selectable option, `no_results` renders
  `noSearchResultsMessage`, `loading` renders `searchingMessage`), and the multi
  chip is the Badge markup — `badgeClass` from the template (the contrast policy
  stays in charge where Filament hardcodes shades), `sy-badge-label-ctn` /
  `sy-badge-label[.sy-wrapped]`, and a `sy-badge-delete-btn` `<button>` with
  `aria-label="Remove <label>"`. That delete button is the controller's own,
  **not** the `remove_button` plugin's: the plugin's `html` callback sees no item
  data, and `updateOption()` re-renders a chip through the item template alone,
  so a label set from `onItemAdd` would vanish on the first label refresh. Labels
  are escaped unless `allowHtml()`; the `aria-label` is always plain text.
  `clear_button` (single, `canSelectPlaceholder`) renders a
  `sy-fo-select-clear-btn` `<button aria-label="Clear selection">`, hidden while
  nothing is selected; the `dropdown_input` search box gets `searchPrompt` as
  placeholder and `aria-label="Search"`, and its `items-placeholder` box — which
  stands in for the selection until there is one — is hidden once there is.
  Non-searchable selects keep Tom Select's control input (never
  `controlInput: null`, which detaches the placeholder, and Filament's
  non-searchable control has no text box at all, but the input is also the
  control's keyboard model) and instead make it `readOnly` with
  `inputmode="none"` so it never takes typing. `hidePlaceholder` is on in both
  modes so a placeholder never sits beside a selection.
- **A11y additions over Tom Select** (it lacks all three): `aria-multiselectable="true"`
  on the listbox in multi mode; a `sy-fo-sr-only` `role="status" aria-live="polite"`
  region announcing the searching, no-results and max-items messages; the
  per-chip remove `aria-label`. The max-items message is a
  `sy-fo-select-max-items-message` element under the control, shown while the
  control is full and hidden on the next removal — Tom Select refuses to open
  when full, so Filament's on-attempt message has nowhere else to appear.
- **`disconnect()`** unhooks `render:finished` and the refresh listener (the
  host outlives a hidden field, a removed repeater row, a closed modal — a stale
  hook throwing inside `processRerender()` aborts the host's render), snapshots
  the `<select>`'s current markup, `destroy()`s (which would revert to the
  construction-time markup, stale after every skip-morph render), restores the
  snapshot and points the field's `<label for>` back at the `<select>` — Tom
  Select re-targets it at its own control and never restores it, and the
  host's mutation tracker would otherwise re-apply the stale `for` on every
  later morph.
- **Known edges, accepted.** Tom Select appends its own `<option selected>` to
  the hidden `<select>` on every selection (`updateOriginalInput()`), so between
  renders the skip-morphed select can hold one duplicate `<option>` per selected
  value; nothing reads that element (no `data-model`), and each render replaces
  its children. Dropdown positioning is CSS-only (no flip) — Tom Select's
  default, kept; [select.md](select.md) carries the `position()` semantics. No
  test in this repo runs the controller — the
  [`checkbox-list` note](checkbox-list.md#the-controller-is-the-only-behaviour-and-no-test-in-this-repo-runs-it)
  applies verbatim; the demo's browser pass is the verification.

Its licence travels with it:
[`copy-licenses.mjs`](../../../packages/forms/assets/scripts/copy-licenses.mjs)
(the [`copy-fonts.mjs`](../../../packages/support/assets/scripts/copy-fonts.mjs)
mirror, `require.resolve`-anchored so a missing package throws) writes
`dist/tom-select-LICENSE.txt` before every `build:js`, because Apache-2.0 §4
wants the licence text redistributed and the split `sylament/forms` publishes
only `dist/`. The banner heading the bundle is composed from the installed
package by `build-js.mjs`'s `licenseBanner()`, whose copyright matcher now finds
an indented notice (`/^Copyright\s+(\(c\)\s*)?\d{4}/` over trimmed lines —
tom-select's `LICENSE` carries its line three spaces in, inside the Apache
appendix, and the previous `startsWith('Copyright')` threw); the five
`build-js.mjs` copies stay identical apart from their `BUNDLED_ENTRIES` table.
`package.json` declares `tom-select` a `dependencies` entry (bundled, never in a
consumer's importmap — no `symfony.importmap` line for it) and
`@symfony/ux-live-component` dev **and** peer at `^3.2.0`, the actions
precedent, since `getComponent` stays a bare specifier the consuming app's
importmap resolves.

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
