---
description: The select field, over a static option map, an enum, or a Doctrine association, rendered as the native `<select>` or — once it is searchable… (draft)
ports:
  - vendor/filament/forms/src/Components/Select.php
  - vendor/filament/forms/src/Components/Concerns/HasOptions.php
  - vendor/filament/forms/src/Components/Concerns/CanSelectPlaceholder.php
  - vendor/filament/forms/src/Components/Concerns/CanDisableOptions.php
  - vendor/filament/forms/src/Components/Concerns/CanBeSearchable.php
  - vendor/filament/forms/src/Components/Concerns/CanBePreloaded.php
  - vendor/filament/forms/src/Components/Concerns/HasLoadingMessage.php
  - vendor/filament/forms/src/Components/Concerns/CanBeNative.php
  - vendor/filament/forms/src/Components/Concerns/CanAllowHtml.php
  - vendor/filament/forms/src/Components/Concerns/CanLimitItemsLength.php
  - vendor/filament/support/resources/js/utilities/select.js
  - vendor/filament/forms/resources/js/components/select.js
  - vendor/filament/forms/resources/lang/en/components.php
owns:
  - packages/forms/src/Components/Concerns/CanBePreloaded.php
  - packages/forms/src/Components/Concerns/HasLoadingMessage.php
  - packages/forms/src/Components/Select.php
  - packages/forms/templates/partials/Select/
---
# Select (forms)

`Sylament\Forms\Components\Select` — the select field, over a static option map, an enum, or a Doctrine association, rendered as the native `<select>` or — once it is searchable, multiple, renders HTML labels or declares `native(false)` — as the JS control forms' `select` Stimulus controller builds — a port of Filament's own select utility.
It composes the shared option pair ([`HasOptions`](has-options.md), [`CanDisableOptions`](can-disable-options.md) and its [contract](../../../packages/forms/src/Components/Contracts/CanDisableOptions.php)), the searchable trio it shares with [`CheckboxList`](checkbox-list.md) ([`CanBeSearchable`](can-be-searchable.md), [`CanAllowHtml`](can-allow-html.md), [`CanLimitItemsLength`](can-limit-items-length.md)), the native switch it shares with the date pickers ([`CanBeNative`](can-be-native.md)), three single-use concerns folded here — [`CanSelectPlaceholder`](../../../packages/forms/src/Components/Concerns/CanSelectPlaceholder.php), [`CanBePreloaded`](../../../packages/forms/src/Components/Concerns/CanBePreloaded.php), [`HasLoadingMessage`](../../../packages/forms/src/Components/Concerns/HasLoadingMessage.php) — and [`HasRelationship`](../../../packages/forms/src/Components/Concerns/HasRelationship.php), which it shares with [`CheckboxList`](checkbox-list.md#the-to-many-relationship-arm-serves-this-field-unchanged) and whose write-up stays here as the arm's home.
It casts its state through schemas' [`OptionStateCast`](../schemas/option-state-cast.md) — or, under `multiple()`, [`OptionsArrayStateCast`](../schemas/options-array-state-cast.md), or, under an [enum](has-enum.md), [`EnumStateCast`](../schemas/enum-state-cast.md) / [`EnumArrayStateCast`](../schemas/enum-array-state-cast.md) — and guards a submitted value server-side by a Symfony `Choice` over its enabled options where the option map is the whole truth, and by [probing the value's label](#the-choice-constraint-is-the-fields-only-server-side-guard) where it is not.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Native `<select>` rendering | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#the-template-renders-the-selected-state-itself) · [diverges](#the-default-placeholder-is-an-untranslated-literal) |
| The JS control — [`CanBeNative`](can-be-native.md) and the `native(false)` / searchable / multiple / HTML branch | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) · [`support/…/select.js`](../../../vendor/filament/support/resources/js/utilities/select.js) | done | — | [semantics](#getextratemplatedata-makes-the-one-getoptions-read-both-branches-share) · [diverges](#nativefalse-switches-to-the-js-control) |
| [`HasOptions`](../../../packages/forms/src/Components/Concerns/HasOptions.php) | [`Concerns/HasOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasOptions.php) | done | — | [detail](has-options.md) |
| [`HasEnum`](../../../packages/forms/src/Components/Concerns/HasEnum.php) — inherited from `Field`; `Select` overrides `getEnumDefaultStateCast()` for the `multiple()` arm | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [detail](has-enum.md) |
| [`CanSelectPlaceholder`](../../../packages/forms/src/Components/Concerns/CanSelectPlaceholder.php) | [`Concerns/CanSelectPlaceholder.php`](../../../vendor/filament/forms/src/Components/Concerns/CanSelectPlaceholder.php) | done | — | [diverges](#disableplaceholderselection-is-not-ported) |
| [`CanDisableOptions`](../../../packages/forms/src/Components/Concerns/CanDisableOptions.php) + [contract](../../../packages/forms/src/Components/Contracts/CanDisableOptions.php) | [`Concerns/CanDisableOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptions.php) | done | — | [detail](can-disable-options.md) |
| [`hasNullableBooleanState()`](../../../packages/forms/src/Components/Select.php) | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [field.md](field.md) |
| [`HasExtraInputAttributes`](../../../packages/forms/src/Components/Concerns/HasExtraInputAttributes.php) | [`Concerns/HasExtraInputAttributes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraInputAttributes.php) | done | — | [detail](has-extra-input-attributes.md) |
| `boolean()` | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | — |
| `getOptionLabel(withDefault)` / `resolveOptionLabel()` / `getOptionLabelUsing()` | [`resolveOptionLabel()`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#getoptionlabel-resolves-off-the-map-or-through-getoptionlabelusing) |
| `getOptionLabels(withDefaults)` / `getOptionLabelsUsing()` | [`getOptionLabels()`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#getoptionlabel-resolves-off-the-map-or-through-getoptionlabelusing) |
| Option validation (`Choice`, and the label probe where the map is partial) — replaced by a user `in()` list | [`getInValidationRuleValues()`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#the-choice-constraint-is-the-fields-only-server-side-guard) · [semantics](can-be-validated.md#a-user-in-list-is-emitted-in-both-spellings-and-replaces-the-derived-check) |
| [`CanBeNative`](can-be-native.md) | [`Concerns/CanBeNative.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeNative.php) | done | — | [detail](can-be-native.md) · [diverges](#nativefalse-switches-to-the-js-control) |
| `multiple()` / [`OptionsArrayStateCast`](../schemas/options-array-state-cast.md) — the chips control | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#a-multiple-select-validates-element-by-element) · [semantics](#multiple-forces-the-js-control-and-is-searchable-by-default) |
| [`CanBeSearchable`](can-be-searchable.md) — `searchable(bool\|array\|Closure\|null)`, `isSearchable()` `?? isMultiple()`, `getSearchColumns()`, the four messages, `searchDebounce()`, the label/value switches | [`Concerns/CanBeSearchable.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeSearchable.php) · [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [detail](can-be-searchable.md) |
| `getSearchResultsUsing()` / `getSearchResults()` — `query` / `search` / `searchQuery` injections | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#the-search-round-trip-is-a-live-action-that-re-renders-the-host) |
| [`CanBePreloaded`](../../../packages/forms/src/Components/Concerns/CanBePreloaded.php) — `preload()` / `isPreloaded()` | [`Concerns/CanBePreloaded.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBePreloaded.php) | done | — | [semantics](#preload-enumerates-the-limited-map-up-front) |
| `optionsLimit()` / `getOptionsLimit()` — 50 | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#the-relationship-search-arm-runs-a-like-per-column-over-the-scoped-option-query) |
| [`HasLoadingMessage`](../../../packages/forms/src/Components/Concerns/HasLoadingMessage.php) — `loadingMessage()` / `getLoadingMessage()` | [`Concerns/HasLoadingMessage.php`](../../../vendor/filament/forms/src/Components/Concerns/HasLoadingMessage.php) | done | — | [diverges](#loadingmessage-has-no-render-site) · [diverges](#the-two-new-strings-are-untranslated-literals) |
| [`CanLimitItemsLength`](can-limit-items-length.md) — `maxItems()` as a `Count` and the control's cap, `maxItemsMessage()` / `getMaxItemsMessage()` | [`Concerns/CanLimitItemsLength.php`](../../../vendor/filament/forms/src/Components/Concerns/CanLimitItemsLength.php) · [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#a-multiple-select-validates-element-by-element) · [diverges](#the-two-new-strings-are-untranslated-literals) · [diverges](#a-composite-relationship-select-past-maxitems-answers-the-count-alone) |
| [`CanAllowHtml`](can-allow-html.md) — `allowHtml()` | [`Concerns/CanAllowHtml.php`](../../../vendor/filament/forms/src/Components/Concerns/CanAllowHtml.php) | done | — | [detail](can-allow-html.md) · [semantics](#transformoptionsforjs-casts-a-markup-label-to-text) |
| `transformOptionsForJsUsing()` / `transformOptionsForJs()` and the four exposed methods — `getSearchResultsForJs()`, `getOptionsForJs()`, `getOptionLabelsForJs()`, `getOptionLabel()` | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#transformoptionsforjs-casts-a-markup-label-to-text) · [semantics](#the-search-round-trip-is-a-live-action-that-re-renders-the-host) |
| `dynamicOptions()` / `hasDynamicOptions()` / `hasDynamicSearchResults()` / `hasInitialNoOptionsMessage()` | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#the-search-round-trip-is-a-live-action-that-re-renders-the-host) |
| `position()` / `getPosition()`, `wrapOptionLabels()` / `canOptionLabelsWrap()`, `forceSearchCaseInsensitive()` / `isSearchForcedCaseInsensitive()` | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#the-dropdown-flips-and-escapes-the-modal) |
| `reorderable()` / `isReorderable()` — chips dragged into order, the order written back | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#reorderable-drags-the-chips-through-sortablejs-and-writes-the-order-back) |
| `getSelectedRecordUsing()` / `getSelectedRecord()` | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#the-option-actions-are-suffix-actions-with-doctrine-defaults) |
| [`relationship()`](../../../packages/forms/src/Components/Concerns/HasRelationship.php) — to-one and to-many, over the [association reader](../schemas/association-reader.md) | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [detail](#relationship-is-three-lazy-closures-over-the-association-reader) · [semantics](#a-relationship-map-is-full-or-an-exception-except-where-search-makes-it-partial-by-design) · [diverges](#the-save-arm-writes-a-desired-list-not-attachdetach) |
| The relationship search and label arms — `getSearchResultsFromRelationship()`, `getOptionsFromRelationship()` (searchable / preload / limit / ordering), `getOptionLabelFromRelationship()`, `getOptionLabelsFromRelationship()`, and the shared `getOptionLabelFromRecordUsing()` trio | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#the-relationship-search-arm-runs-a-like-per-column-over-the-scoped-option-query) · [diverges](#a-search-column-is-a-mapped-field-and-the-title-ordering-skips-what-it-cannot-order) |
| `createOptionForm()` / `createOptionUsing()` / `createOptionAction()` / `getCreateOptionAction()` and the create modal chain | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#the-option-actions-are-suffix-actions-with-doctrine-defaults) · [diverges](#the-non-relationship-schema-record-is-read-off-the-field-not-off-a-getactionschemamodel) · [diverges](#a-key-an-option-actions-mutation-adds-that-no-component-maps-is-refused) |
| `editOptionForm()` / `updateOptionUsing()` / `fillEditOptionActionFormUsing()` / `editOptionAction()` / `getEditOptionAction()` | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#the-option-actions-are-suffix-actions-with-doctrine-defaults) · [diverges](#geteditoptionactionformdata-also-answers-an-object) · [diverges](#a-key-an-option-actions-mutation-adds-that-no-component-maps-is-refused) |
| `refreshSelectedOptionLabel()` | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [diverges](#refreshselectedoptionlabel-dispatches-a-browser-event-the-control-answers-by-re-syncing) · [renames](../translations.md#names-with-no-filament-source) |
| `manageOptionForm()` / `manageOptionActions()` / the two modal headings | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#the-option-actions-are-suffix-actions-with-doctrine-defaults) · [diverges](#the-option-action-strings-are-untranslated-literals) |
| `relationship()` `ignoreRecord:` | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#ignorerecord-narrows-the-two-option-arms-and-neither-label-arm) · [diverges](#a-record-of-another-class-than-the-association-target-is-refused) |
| `pivotData()` | [`Concerns/HasPivotData.php`](../../../vendor/filament/forms/src/Components/Concerns/HasPivotData.php) | skipped | — | [diverges](#the-save-arm-writes-a-desired-list-not-attachdetach) |
| `CanFixIndistinctState` | [`Concerns/CanFixIndistinctState.php`](../../../vendor/filament/forms/src/Components/Concerns/CanFixIndistinctState.php) | done | — | [detail](can-fix-indistinct-state.md) |
| `CanDisableOptionsWhenSelectedInSiblingRepeaterItems` | [`Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php) | done | — | [detail](can-disable-options-when-selected-in-sibling-repeater-items.md) |
| Affixes ([`HasAffixes`](../../../packages/forms/src/Components/Concerns/HasAffixes.php), `wrapInputHtml`) | [`Concerns/HasAffixes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasAffixes.php) | done | — | [detail](has-affixes.md) · [semantics](#the-inline-prefix-class-counts-visible-actions-where-the-text-inputs-counts-all-of-them) |
| [`required` drops when concealed](../../../packages/forms/templates/partials/Select/index.html.twig) — the native branch only; `aria-required` is ungated | [`toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [can-be-concealed.md](../schemas/can-be-concealed.md) |

Coverage: the concern surface — flattening, per-option disabling, merge/replace, placeholder selectability and dynamic options — by [`CanDisableOptionsTest`](../../../packages/forms/tests/Unit/Components/Concerns/CanDisableOptionsTest.php); the rendered markup (including a disabled option in both the flat and the grouped shape), the state round-trip, `boolean()`, `getOptionLabel()` over flat, grouped and boolean options, the no-options `LogicException` and the `Choice` guard — the tampered-value, disabled-option and int-keyed cases plus both stored forms of a `boolean()` option, all run through the container's real validator — the enum arm (its options rendered, the enum-valued state's option marked `selected`, the single and `multiple()` round trips against an enum-typed record property, an enum-only select validating rather than throwing, and — driven end to end through a host, so the tampered string is the field's *own* raw state — the [filled gate](#the-filled-gate-reads-the-raw-state-because-a-cast-can-null-a-tampered-value-out) rejecting a value the enum cast nulls out, in both multiplicities) — and the extra input attributes reaching the `<select>` rather than its `<option>`s, and the searchable surface — `searchable()`'s five arms and the column array, `isSearchable()` reading `isMultiple()`, the fifty-option limit, the position / wrap / case switches, `getMaxItemsMessage()`'s substitution, the default and a replaced `transformOptionsForJs()` (flat, grouped, disabled, `strval`'d values, a `Markup` label cast to text), the three search-term injections, `getOptionLabel(withDefault)` off the map and through the lazy `$value` closure, `getOptionLabels()` in both arms, the three predicates' truth tables, `getSelectedRecordUsing()` and its memo, the effective-native table read off `getExtraTemplateData()['isNative']`, the four extra-template-data keys off one `getOptions()` read, the refresh event on a `LiveResponder`, and the label probe (the widened `LogicException`, a labelled value accepted, an unlabelled and a disabled one rejected by an empty `Choice`) — by [`SelectTest`](../../../packages/forms/tests/Integration/Components/SelectTest.php); the multi-value arm — `multiple()` as literal, `false` and closure, the cast swap (and a custom cast still winning), the state round-trip, the `Choice(multiple: true)` guard against a tampered element, the empty-selection and no-options cases, the rendered `multiple` attribute with its selected options in both the flat and the grouped shape, the `Count` from `minItems()` / `maxItems()` built ahead of the blank return so `[]` fails a minimum, the widened `LogicException` naming `getOptionLabelsUsing()`, and the multiple probe (count, diff, disabled) — by [`SelectMultipleTest`](../../../packages/forms/tests/Integration/Components/SelectMultipleTest.php); the relationship arm — the to-one fill/option-map/`getOptionLabel` trio, the selected-id write, the empty-submission null, the `Choice` rejection of an id outside the map, `modifyQueryUsing` scoping, the memoized map (a row inserted between two `getOptions()` calls surfaces only on the scoped select), a clone reading its own configuration rather than the original's, the missing-`titleAttribute` throw, a relationship named apart from the field, and the to-many arm's fill, collection sync (the `PersistentCollection` instance surviving, the dropped `Tag` row surviving, `multiple()` declared on either side of `relationship()`), empty submission, a round-trip against a reloaded post whose collection is still lazy, and both multiplicity refusals (a to-many association without `multiple()`, a to-one association with it, each asserted from the option map and from `fill()`), and the search arm — the `LIKE` over the title attribute and its parameter, `searchable(['name', 'id'])` `OR`ing a column each, an untitled relationship answering `[]`, a search column that hops an association refused with a `LogicException`, `optionsLimit()` and a scope's own `setMaxResults()` winning and written back, `modifyQueryUsing` receiving the term (and `null` elsewhere), `forceSearchCaseInsensitive()` in all three arms asserted on the DQL and the parameter (SQLite's `LIKE` being case-insensitive anyway, the results prove nothing there), the searchable select enumerating nothing and the preloaded one the limited ordered map, the record-label callback labelling in database order beside the title path's title order, both label arms' two branches, the selected record fetched (identity-map instance), `null` on a miss and outside the scope, the probe accepting the real key and rejecting a missing and an out-of-scope one through `getData()`, and the non-searchable select keeping its full-map `Choice` — by [`SelectRelationshipTest`](../../../packages/forms/tests/Integration/Components/SelectRelationshipTest.php), against the in-memory SQLite rig ([`EntityManagerFactory`](../../../packages/forms/tests/Fixtures/Doctrine/EntityManagerFactory.php), smoke-tested by [`DoctrineRigTest`](../../../packages/forms/tests/Integration/Fixtures/DoctrineRigTest.php)); the wiring that needs no database — `hasRelationship()`, the name fallback, the title attribute, the no-entity-manager throw, the unconditional install order of the three option defaults and of the search and label arms (a closure declared before `relationship()` overwritten, one declared after kept), and `getSearchColumns()` defaulting to the title attribute — by [`HasRelationshipTest`](../../../packages/forms/tests/Unit/Components/Concerns/HasRelationshipTest.php); the two new concerns — `preload()` and `loadingMessage()` in every arm — by [`CanBePreloadedTest`](../../../packages/forms/tests/Unit/Components/Concerns/CanBePreloadedTest.php) and [`HasLoadingMessageTest`](../../../packages/forms/tests/Unit/Components/Concerns/HasLoadingMessageTest.php); and the two option actions — the dotted-key mount rendering the modal with its form, the create round-trip (row persisted, key selected, the outer schema's own `getState()` clearing the `Choice` against the re-enumerated map, the new option rendered), the `multiple()` append with its create-another footer action (present only there, and submitting with `another: true` keeping the modal mounted with a reset form), the edit round-trip (modal pre-filled from the selected record, the update flushed), the missing-`createOptionUsing` throw driven through a non-relationship select, `forgetCachedRelationshipOptions()` itself, and the gates (no form, `disabled()`, `multiple()` closing the edit action, the blank-state hide, `editOptionForm()` making the field live, the manage-level modifier running before the per-action one, the ported strings and chrome, and both triggers rendered in the suffix slot with the field's mount context) — by [`SelectCreateOptionTest`](../../../packages/forms/tests/Integration/Components/SelectCreateOptionTest.php), against the same SQLite rig through the [`SelectOptionForm`](../../../packages/forms/tests/Fixtures/Twig/Components/SelectOptionForm.php) live host.

## Details

Specifics for rows that are not finished, and the standing write-up of the relationship arm.

### `relationship()` is three lazy closures over the association reader

[`HasRelationship`](../../../packages/forms/src/Components/Concerns/HasRelationship.php) stores the title attribute and the optional query scope, then wires three closures and nothing else.
Each takes its component as `self`, which resolves to whichever class composed the trait, so [`CheckboxList`](checkbox-list.md#the-to-many-relationship-arm-serves-this-field-unchanged) takes the same three unchanged; the option query the first of them runs is built and scoped in one place — `createScopedOptionQuery()`, the builder *before* it runs, which `getRecordsFromRelationship()` executes for the trait's own map and which this field's [search, label and selected-record arms](#the-relationship-search-arm-runs-a-like-per-column-over-the-scoped-option-query) constrain, limit and order first.
Those four arms are `Select`'s, installed by its own `relationship()` override on top of the trait's three; the trait keeps what both fields use, which also includes the record-label trio (`getOptionLabelFromRecordUsing()` / `hasOptionLabelFromRecordUsingCallback()` / `getOptionLabelFromRecord()`, moved here from `CheckboxList` — Filament duplicates it on both fields because it has no shared trait) and the one record→label mapping both maps are built from, `resolveOptionLabelFromRecord()`: the callback when declared, the title attribute read through the accessor otherwise.
The association *name* and the target resolution below moved one level down into [`BelongsToRelationship`](../../../packages/forms/src/Components/Concerns/BelongsToRelationship.php) when [`Repeater::relationship()`](repeater.md#a-relationship-repeater-keys-its-items-by-identity-and-diffs-the-collection) needed the same three-arm refusal, and one package down into [schemas' trait of the same name](../schemas/entangles-state-with-singular-relationship.md#the-association-core-is-shared-with-forms-relation-backed-fields) when the entangled layout components needed it too — what is left in forms is the adapter that says "The [name] field" and that a field has its own name to fall back on.
The clauses that differ per field — why an unresolvable relationship is fatal for *this* field, and what to do instead — stay with each field as small hooks, so no message lost a word to the sharing:

- **`options()`** — [`AssociationReader::describe()`](../schemas/association-reader.md) resolves the record class's mapping at that property, `createOptionQuery()` builds `SELECT record FROM <target> record`, `modifyQueryUsing` scopes it, and each row becomes its `getRecordKey()` mapped to the title attribute, read through [`PropertyAccessor::getFromRecord()`](../schemas/property-accessor.md).
  The keys are the identifier strings the client speaks, so PHP canonicalizes numeric ones to `int` array keys — the same vocabulary [`getEnabledOptionValues()`](#the-choice-choices-cover-both-forms-the-raw-state-can-take) already reconciles, which is why the `Choice` guard needed no relationship arm.
  A key of two or more identifier fields always carries `~`, so it is never numeric and never coerced — [the codec's grammar](../schemas/record-key.md#the-grammar) is what makes that a guarantee rather than a hope; a **single derived identity** is one field and coerces exactly as a single `id` does.
- **`loadStateFromRelationshipsUsing()`** — the association value's record key (null when unset), or the list of keys for a to-many.
  This is the [hook pair](../schemas/belongs-to-record.md) `fill()` consults instead of the ordinary record read, which would have skipped the path entirely: an object-valued leaf is skipped by `readStateFromRecord()`, and a collection is refused by `setOnRecord()`.
- **`saveRelationshipsUsing()`** — the reverse, through [`AssociationReader::getReference()`](../schemas/association-reader.md#getreference-decodes-first-and-then-picks-its-route), so `getData()` writes managed references without loading the target rows — except where an identifier field is itself an association, which cannot be referenced without a load and costs one `SELECT` per key.
  The to-one arm is pinned across three flavours by *writes a single composite selection back onto its record*, and that case also records what the cost is **not**: by the time a to-one write resolves its reference, `getData()`'s validation pass has already enumerated the options and hydrated the chosen row, so a forms-level write never takes the reader's cold route.
  The cold route is the reader's own, and schemas' `AssociationReaderCompositeKeyTest` is where it is pinned.

Every one of them resolves the record class and the reader **at evaluation time**, never at `relationship()` time — the same lazy shape [`Repeater::refuseObjectCollectionRecord()`](repeater.md) uses, and what lets `relationship()` be declared before the component is attached to a schema.
The to-one/to-many branch is read off the mapping on each call rather than captured: all three go through `getMultiplicityMatchedRelationshipTarget()`, which reads `isMultiple()` on the same call and refuses the two shapes where the field and the mapping disagree — a to-many association the field never declared `multiple()` for (every selection but one would be dropped on the way in, and the save would write a single reference over the collection), and a to-one association it did (a list of selections has no single record to map onto).
Both sides are still read per call rather than snapshotted, so `multiple()` may be declared on either side of `relationship()`; it is the *pairing* that is fixed, not the declaration order.

All three take the component as an injected parameter (`static function (Select $component)`, Filament-exact) rather than closing over `$this`.
[`getClone()`](../schemas/component.md) is a shallow `clone`, so a closure bound to `$this` would keep pointing at the *original* component after the clone — every per-item schema a `Repeater` builds is a clone of the child schema, so a relationship select inside one would read the template's configuration, not the item's.
The values it reads happen to be instance-invariant today, which is precisely why the binding has to be right before something instance-varying (`ignoreRecord:`, a per-item scope) makes it observable.

`$name` defaults to the field's own name at declaration time (`$name ?? $this->getName()`, Filament-exact), which is what keeps `hasRelationship()` false for a select that never declared one — the fallback lives in the assignment, not in the getter.

The option map is memoized on the component, and reset by `relationship()` itself — again Filament-exact, and load-bearing here because a non-searchable select's map is the *whole* target table: `getOptions()` runs at least twice per request (once through `getEnabledOptionValues()` during validation, once from the template) and without the memo each is another unbounded `SELECT`.
A field declaring `modifyQueryUsing` neither reads nor writes the memo, because a scoping closure may read state that changes within the request and a cached first answer would freeze it.
The map is read exactly once per render, in [`getExtraTemplateData()`](#getextratemplatedata-makes-the-one-getoptions-read-both-branches-share), for the same reason.

The concern imports `Doctrine\ORM\QueryBuilder` directly, so `forms` declares `doctrine/orm` in its own `require` rather than inheriting it through `schemas` — the split-repo rule, since each package is published standalone.

## Semantics

### The `Choice` constraint is the field's only server-side guard

A native `<select>` gives no server-side guarantee at all: the submitted value is an ordinary string a client can set to anything, and `disabled` on an `<option>` is a browser-side courtesy — and the JS control gives less still, since its value never touches a form control the browser validates.
[`buildValidationConstraints()`](../../../packages/forms/src/Components/Select.php) therefore guards the [filled raw state](#the-filled-gate-reads-the-raw-state-because-a-cast-can-null-a-tampered-value-out) one of two ways, and the split is `hasFullOptionMap()`: whether `getOptions()` enumerates every value the field accepts.

**The enumerable arm** — a static `options()` map, an enum, or a relationship select that is not searchable — appends a `Choice` over [`getEnabledOptions()`](../../../packages/forms/src/Components/Concerns/CanDisableOptions.php); that one constraint is what rejects both a tampered value and a value whose option is disabled.
Symfony's `Choice` is always strict (`ChoiceValidator` throws on any other setting), so the comparison is identity, which is why the choices are built in the field's *stored* form (see below).
This is exactly the guard the relation layer shipped, and it is untouched: a static map is the whole truth even when a `getOptionLabelUsing()` is declared beside it — the closure customizes the *label*, the map still says which values exist — where Filament would probe the closure and never consult the map.
The stricter reading is kept because it is tamper-proof without asking anything of a user closure.

**The partial arm** — a searchable relationship select, preloaded or not, whose map is empty or limited by design; or a select with no `options()` at all but a `getOptionLabelUsing()` (single) / `getOptionLabelsUsing()` (multiple) — ports Filament's `getInValidationRuleValues()`: the submitted value is legal when it **resolves a label** and that option is not disabled.
Single ⇒ [`getOptionLabel(withDefault: false)`](#getoptionlabel-resolves-off-the-map-or-through-getoptionlabelusing), blank ⇒ invalid; multiple ⇒ `getOptionLabels(withDefaults: false)`, then Filament's three checks in order — more values than labels, a value the labels do not key, any label whose option is disabled — each ⇒ invalid.
An invalid probe appends `Choice(choices: [])` (Filament's `[]` in-rule), so the value fails closed with the ordinary "not a valid choice" violation rather than being waved through; a valid probe appends nothing.
For a relationship select the probe's record source is [`getSelectedRecordUsing()`'s query](#the-option-actions-are-suffix-actions-with-doctrine-defaults), so a key outside `modifyQueryUsing`'s scope is as invalid as a missing one — Filament's semantics.
The probe resolves state at constraint-build time, the precedent [`DateComparison`](can-be-validated.md) set.

The `LogicException` raised when a filled select has nothing to validate with exists so that "validates nothing" can never be the silent default, and it widens per arm exactly as Filament's does: single ⇒ no `options()`, no enum **and** no `getOptionLabelUsing()`; multiple ⇒ … and no `getOptionLabelsUsing()` — so a `multiple()` select carrying only the single-arm closure throws, as upstream.
`hasFullOptionMap()` is `protected` and Sylament-only; Filament's `null` map return plays that role.

One shape is guarded by a different constraint, because `Choice` cannot express it: a `multiple` select whose raw state is not an array ([below](#a-non-array-raw-state-is-failed-by-a-type-constraint)).

The constraint carries no message argument: one is written afterwards by the [post-pass](can-be-validated.md#validation-messages-apply-after-the-constraints-are-built), keyed `validationMessages([Choice::class => …])` where Filament keyed the `in` rule.
`Choice` declares four message options (`message`, `multipleMessage`, `minMessage`, `maxMessage`), so the string shorthand sets `message` — the one a single-value select violates — and an option map reaches the others.

The *whenever state is filled* half of that has an upstream twin now, and the two do not stack.
`Choice` is not implicit, so the [presence gate](../schemas/presence-gate.md#the-gate-is-a-no-op-for-select-which-already-guarded-itself) would skip it against the `''` a placeholder submits — except this builder never emits one for a blank state in the first place, so the gate finds nothing to filter and select validation is unchanged in both directions.
The guard here is not redundant with it either: it exists because there are no choices to enumerate for a value that is not there, which is a different question from whether a constraint should run.

### The *filled* gate reads the raw state, because a cast can null a tampered value out

All four option fields open `buildValidationConstraints()` with the same short-circuit — there are no choices to enumerate for a value that is not there — and the state it asks about has to be the **raw** one, `blank($this->getRawState())`.
Reading `getState()` there was a validation bypass.

The two readings differ for exactly one input, and it is the one that matters.
A state cast may find nothing it can represent in what a payload wrote: [`EnumStateCast`](../schemas/enum-state-cast.md) fails closed to `null` for a value matching no case, and [`EnumArrayStateCast`](../schemas/enum-array-state-cast.md) drops every such element.
So a tampered `'deleted-by-hand'` on an enum-backed field casts to `null`, a *cast* gate reads "nothing selected", the `Choice` is never appended, and the field is left holding the required constraint alone — which a non-blank string passes.
[`HasSchemas::validateSchema()`](../../../packages/schemas/src/Concerns/HasSchemas.php) then validates the **raw** bag entry against that empty guard, so the tampered value clears validation and reaches the record write, where a typed setter throws a `TypeError` — a 500 where a rejection belonged.

The raw gate splits the two cases the cast gate had conflated:

- **Nothing was submitted** — the raw state is `null`, `''` or `[]` — and the `Choice` is still skipped, so an unanswered required field is failed by the required constraint alone and an optional one passes untouched.
  Unchanged, and pinned by the required and blank-state cases in all four suites.
- **Something was submitted that resolves to nothing** — the raw state is filled, so the `Choice` runs and [`getEnabledOptionValues()`](#the-choice-choices-cover-both-forms-the-raw-state-can-take), which enumerates both stored forms of every enabled option, rejects it.

Reading raw is the only reading consistent with what is validated: the constraint list guards the raw bag entry, so the gate deciding whether to build it must ask about that same value.
The non-enum arms never showed the hole — [`OptionStateCast`](../schemas/option-state-cast.md) passes a tampered scalar straight through, so its cast state was filled and the `Choice` was built — which is why the defect surfaced only once an enum reached a field.

The widening adds rejections without removing any.
Every cast these fields install — the option, options-array, enum, enum-array and `boolean()` casts — maps a blank raw state to a blank cast state, so nothing that used to reach the `Choice` stops reaching it.
What it newly catches is the fail-closed arm of each cast: besides the enum case above, a raw `false` on a single field (`OptionStateCast` stringifies it to `''`) and a raw array on a single field (no scalar to read, so `null`).
The array one lands on `Choice(multiple: false)`, whose validator does an `in_array()` and fails it rather than throwing, so it is a violation and not a 500.

The gate is four one-line reads rather than a shared helper, deliberately.
`getRawState()` is already the shared accessor ([schemas' component-level `HasState`](../schemas/component.md)); each builder puts its gate at a different point in a different sequence — [`CheckboxList`](checkbox-list.md#the-three-server-side-guards-are-ordered-so-they-never-collide) runs two constraints ahead of it — and a name like `hasBlankState()` would hide the one thing a reader of this method has to see, which state is being asked about.

### The `Choice` choices cover both forms the raw state can take

`getEnabledOptions()` returns PHP array keys, while validation runs against the **raw** state a payload wrote — [`HasSchemas::validateSchema()`](../../../packages/schemas/src/Concerns/HasSchemas.php) reads the host property, not `getState()`.
Two different values can legitimately sit there, and a strict `Choice` has to admit both:

- **The cast form** — what `state()` and `Schema::fill()` write, each key run through the reversed state-cast chain.
  With `options([1 => 'One'])` the key is `int 1` while `OptionStateCast::set()` stores the string `'1'`; a `Choice` over the bare keys would reject the field's own filled value.
- **The submitted form** — the option's `value` attribute, which the template renders from the array key, so it is always `(string) $key`.
  A single `<select>` binds through `live_controller`'s `getValueFromElement()`, which writes `element.value` — a string — straight into the host's bag, and nothing re-normalizes it afterwards: `hydrateState()` is reached from `Schema::fill()` only, i.e. at mount.

[`getEnabledOptionValues()`](can-disable-options.md#the-choice-choice-list-lives-here-not-on-the-field) therefore emits the cast value and, when it differs, the submitted string.
For string options the two coincide and the list is the plain key list; for `boolean()`, whose `BooleanStateCast(isStoredAsInt: true)` stores `int 1`, the choices are `[1, '1', 0, '0']` — without the string arm a `boolean()` select rejects its own value the moment it round-trips through the browser.
An enum'd field is the coinciding case again, since [`EnumStateCast::set()`](../schemas/enum-state-cast.md) stores `(string) $case->value`, which is the option key the template rendered.
Nothing is loosened by the extra arm: `Choice` stays strict and a tampered or disabled value is still absent from the list.

The method itself lives on [`CanDisableOptions`](can-disable-options.md#the-choice-choice-list-lives-here-not-on-the-field) rather than on `Select`, so every option field builds its choice list the same way; the reasoning above is what it implements.

### A multiple select validates element by element

`multiple()` swaps the field's default cast for [`OptionsArrayStateCast`](../schemas/options-array-state-cast.md), so the state is a list and the `Choice` gains `multiple: true` — which is what makes Symfony validate each submitted element against the choices instead of the array as a whole.
Three consequences worth stating, because each looks like an omission in the code:

- **An empty selection builds no membership constraint.**
  The builder's existing [`blank($this->getRawState())`](#the-filled-gate-reads-the-raw-state-because-a-cast-can-null-a-tampered-value-out) early return already covers it — [`blank([])`](../support/helpers.md) is true — so the empty-selection case needs no branch of its own, and validation of "must this field be filled?" stays with the presence/required layer where it belongs.
  The one constraint built *ahead* of that return is the item-count `Count` from `minItems()` / `maxItems()` ([`CanLimitItemsLength`](can-limit-items-length.md)), exactly as `CheckboxList` orders it, so a `minItems(1)` select fails `[]` — the empty list is a count question, not a membership one.
- **The choices stay scalar.**
  `getEnabledOptionValues()` runs each option key through the cast chain, and a multi-value cast turns one key into a *one-element list*; the builder spreads that list rather than pushing it, since `Choice(multiple: true)` compares elements and a value wrapped in an array would match nothing.
  Both stored forms of a key are still emitted — the reasoning is [unchanged](#the-choice-choices-cover-both-forms-the-raw-state-can-take).
- **The cast is non-nullable.**
  `OptionsArrayStateCast(isNullable: false)` writes `[]` for an empty selection, not `null`, so the host's bag keeps an array the template can iterate and the browser can write back.

### A non-array raw state is failed by a `Type` constraint

`Choice(multiple: true)` is defined over arrays only: [`ChoiceValidator::validate()`](../../../vendor/symfony/validator/Constraints/ChoiceValidator.php) throws `UnexpectedValueException` for any other value rather than building a violation.
Validation runs against the **raw** state, which a tampered payload controls, and the field's [filled gate](#the-filled-gate-reads-the-raw-state-because-a-cast-can-null-a-tampered-value-out) does not stop a scalar from reaching the builder — a non-blank string is exactly what that gate lets through.
Handing it to the `Choice` would end the request in a 500 instead of a rejection.

The builder therefore branches before it appends the `Choice`: when the field is `multiple` and the raw state is not an array, it appends a `Type('array')` and returns.
The payload is failed, with a violation on the field, and every array-shaped state takes the `Choice` (or [probe](#the-choice-constraint-is-the-fields-only-server-side-guard)) path unchanged.
The `Type` check comes first, ahead of the item-count `Count` and the no-`options()` `LogicException` — the order [`CheckboxList`](checkbox-list.md#the-three-server-side-guards-are-ordered-so-they-never-collide) already had, since `Count` throws on a non-array too.

### The template renders the selected state itself

The **native** branch's template emits `selected` per option, comparing the option's key against [`getOptionComparisonState()`](has-options.md#the-option-vocabulary-is-normalized-once-rather-than-per-template) — `in` when the state is a list, a loose `==` otherwise.
For a single select this is redundant with the live binding on a first render and becomes load-bearing on any server-rendered non-live one.
The JS branch's hidden `<select>` renders the same option loop and, beside it, every `selectedOptions` entry the map does not carry as a `selected` option — so a non-preloaded searchable select's DOM holds its selection with a label and degrades to a readable value without JS — while the control itself paints from the `selectedOptions` value; the comparison below is the native branch's.
The comparison is deliberately loose because the two sides speak different vocabularies — an option key is an `int` where the state may hold the `'1'` a browser submitted — the same mismatch [`getEnabledOptionValues()`](#the-choice-choices-cover-both-forms-the-raw-state-can-take) answers on the validation side.
Nothing is loosened security-wise: `selected` is presentation, and the strict `Choice` is what admits or rejects a value.

What the comparison never sees is a raw `getState()`: the normalization is one method on [`HasOptions`](has-options.md), so an enum-valued or boolean state reaches the template already spelled in the option vocabulary, and `getOptionLabel()` resolves through the same method rather than repeating the bool-to-int coercion it used to carry.

### `getOptionLabel()` resolves off the map or through `getOptionLabelUsing()`

`getOptionLabel(bool $withDefault = true)` is a thin `#[ExposedLiveComponentMethod]` wrapper over `resolveOptionLabel($withDefault, ?array $options)`, Filament's shape, and the two arms are Filament's too.

**The map arm** — no `getOptionLabelUsing()` — looks the selected value up in the option map (the one given, else `getOptions()`), walking both the flat and the grouped shape.
The match is an array-key lookup rather than a comparison, which is what Filament's `$options[$state]` is: PHP canonicalizes a numeric-string key to an int, so a state of `'1'` finds the `1` option however the field's casts spell it.
The state is read through [`getOptionComparisonState()`](has-options.md#the-option-vocabulary-is-normalized-once-rather-than-per-template), so an enum unwraps and a `bool` normalizes to `int` first — the coercion PHP would apply to an array offset anyway, and the one `boolean()` depends on to resolve `true` to its `1 => 'Yes'` option.
Without a match, `$withDefault` answers the state itself as text (Filament's `return $state`, made a string because the return type is `string|Markup|null`) and `false` answers `null` — the reading validation probes.

**The closure arm** — `getOptionLabelUsing()` declared — evaluates it with `'value'` injected as a **closure** returning `getState()`, so a `fn ($value) => …` reads the state lazily and a closure that never asks for it never triggers the read; the by-reference capture is Filament's, so the `$withDefault` fallback reuses the state the closure read rather than reading it twice.
What the closure answers is coerced the one way an option label can be: `Markup` as-is, a scalar or an enum's backing value as a string, anything else `null`.
`relationship()` installs this arm, so a relationship select resolves its label from the **record** ([the selected-record query, then the callback or the title attribute](#the-relationship-search-arm-runs-a-like-per-column-over-the-scoped-option-query)) even when its map would have held it — Filament-exact, and one extra query per render on a preloaded select that the memo on `getSelectedRecord()` bounds to one.

`getOptionLabels(bool $withDefaults = true, ?array $options = null)` is the plural twin: the map arm walks the comparison state's list and defaults an unlabelled value to itself when asked, and the closure arm evaluates `getOptionLabelsUsing()` with `'values'` injected lazily, then defaults a **blank** label to its value or drops the key.
A value the closure did not return at all is absent either way — Filament's shape, and the reason a relationship select's `selectedOptions` carries only the rows the scope admits.
`getOptionLabelsForJs()` takes no argument for the same reason Filament's does not: it reads the state itself.

### `getExtraTemplateData()` makes the one `getOptions()` read both branches share

The class hook — `docs/internals/php.md`'s Twig-side port of the `toEmbeddedHtml()` local-variable preamble — performs the render's single `getOptions()` read (a `modifyQueryUsing` relationship map is not memoized, so three reads would be three queries and three maps that could disagree) and exposes four values both template branches read as plain variables: `options` (the raw map, which the `<option>` loop of **both** branches iterates — never `component.getOptions`), `optionsForJs` (`transformOptionsForJs($options)`), `selectedOptions` (the current selection as a `{value, label}` list — Filament's `initialOptionLabel` / `initialOptionLabels`, resolved through `resolveOptionLabel(options: $options)` for a single select and `getOptionLabels(options: $options)` for a multiple one, so the JS control paints its closed state with zero round-trips), and `isNative` — `! (isSearchable() || isMultiple() || isHtmlAllowed()) && isNative()`, [the exact `Select.php:1838` rule](can-be-native.md#a-select-is-native-only-when-nothing-forces-the-js-control).
Two of the four are computed only for the JS branch: a native select gets `optionsForJs` and `selectedOptions` as `[]`, because Filament computes both inside its JS branch too and a native relationship select would otherwise pay the selected-record query for a value nothing renders.
None of the four is a public method, so there are no rename rows.

### `transformOptionsForJs()` casts a `Markup` label to text

`setUp()` installs the default `transformOptionsForJsUsing` closure Filament's does — a flat option becomes `{label, value: (string) $value, isDisabled}` and a group `{label: <group key>, options: […]}` — and it is what the four exposed methods and `optionsForJs` map through.
One port clause: a `Markup` label is cast to a plain string here, so raw HTML travels to the control only under `allowHtml()`, whose controller-side switch decides whether to escape it.
That bends the codebase's [`Markup`-renders-raw contract](can-allow-html.md#the-switch-opens-exactly-one-hole-per-field) in one place, deliberately: the JS payload is data, not a Twig sink, and the control has exactly one switch for whether it treats a label as markup.
`transformOptionsForJsUsing(null)` un-installs the default and makes `transformOptionsForJs()` throw, naming the setter — Filament's would `TypeError` on the null return; either way there is no shape to send.

### `preload()` enumerates the limited map up front

Under the port's [server-rendered options](#nativefalse-switches-to-the-js-control), `preload()` means *the server enumerates the (limited) option map into the markup*: `getOptionsFromRelationship()` returns `[]` for a searchable select that is not preloaded and the limited, title-ordered map for one that is; a non-searchable relationship select enumerates as before, and a static `options()` map is always in the markup regardless.
`hasDynamicOptions()` and `hasInitialNoOptionsMessage()` are ported verbatim and read `isPreloaded()` the same way Filament's do, though the first buys nothing here — see the [divergence](#the-search-round-trip-is-a-live-action-that-re-renders-the-host).

### `multiple()` forces the JS control, and is searchable by default

Filament couples `multiple()` to its JS control — chips, a remove affordance per value, `maxItems()` — and the port now does the same: [`isNative`](can-be-native.md#a-select-is-native-only-when-nothing-forces-the-js-control) is false for every multiple select, and [`Select::isSearchable()`](can-be-searchable.md#select-widens-searchable-to-a-column-array-and-defaults-it-to-multiple) defaults to `isMultiple()`.
The native `<select multiple>` the relation layer shipped is gone from the shipped surface, its parts — the cast swap, the `multiple` attribute, the multi-value `Choice`, the selected state — carried by the JS branch's hidden `<select>` instead.
Filament's `dehydrated(false)` arm is still not among them and never will be: it exists for the relationship-saving path rather than for multi-value state, and that path works the other way round here ([below](#the-save-arm-writes-a-desired-list-not-attachdetach)).
The one consequence worth saying out loud is the relationship one ([below](#a-relationship-map-is-full-or-an-exception-except-where-search-makes-it-partial-by-design)): a `multiple()` relationship select that calls neither `searchable()` nor `preload()` enumerates no options until the user types.

### A relationship map is full or an exception, except where search makes it partial by design

The `Choice` guard enumerates `getOptions()`, so an option map that comes back empty does not fail open — it fails *closed*, rejecting every value the user can pick, with a violation that names nothing wrong with the submission.
That is the one outcome a relationship select must never produce **by accident**, and it is exactly what a silently-degrading options closure would produce: no entity manager, no record class, or a property Doctrine maps nothing at, each yielding `[]`.
So the trait's closure throws instead.
Each arm names which of the three it hit and what would fix it, and the reader's own [`getReference()`](../schemas/association-reader.md) and `createOptionQuery()` throw on the same missing entity manager from the write side.
There is no fallback option map anywhere in the concern; a select whose options the port cannot enumerate is a configuration error, not a runtime state.

One empty-map arm is **not** an accident: `Select::getOptionsFromRelationship()` answers `[]` for a searchable select that is not preloaded (Filament returns `null` there; ours keeps the `array` type and the validation builder distinguishes the two by predicate), and a limited map for one that is.
Neither is the truth about a submitted value, and neither reaches the `Choice`: `hasFullOptionMap()` is false for every searchable relationship select, and the [label probe](#the-choice-constraint-is-the-fields-only-server-side-guard) — which fetches the submitted key through the same scoped query — guards it instead.
So the invariant is unchanged where it matters: a full map is validated as a full map, and a partial one is never validated as if it were full.
Because `isSearchable()` [defaults to `isMultiple()`](#multiple-forces-the-js-control-and-is-searchable-by-default), a `multiple()` relationship select is on this arm unless it says `preload()` — the four shipped tests that build one gained the call, and the trait's "always a full map" docblock became this paragraph.

One consequence worth stating: on either arm the save hook receives an identifier that has already been checked against the target table — through the map or through the probe — by the time it runs.
Its own "not an identifier" throw is therefore a belt-and-braces guard for a state that reached `getData()` without validation, not the primary defence.

### The relationship search arm runs a `LIKE` per column over the scoped option query

Every searchable, preload, limit and label arm lives on `Select`, not in the shared trait — as in Filament, where every one of these members sits on `Select` — because [`CheckboxList`](checkbox-list.md#the-to-many-relationship-arm-serves-this-field-unchanged) composes the same trait and has none of `isPreloaded()`, `getSearchColumns()`, `getOptionsLimit()` or the closures.
The trait keeps what both use: the cardinality guard, `createScopedOptionQuery(?string $search)` (create + scope, with `modifyQueryUsing` now receiving the term as `$search` — `null` outside a search — beside `$query`), and the record→label mapping.
`Select::relationship()` calls the trait's, then installs four arms unconditionally on top of the three option-action defaults:

- **`getSearchResultsUsing` ⇒ `getSearchResultsFromRelationship(?string $search)`.**
  Blank `getSearchColumns()` ⇒ `[]` (an untitled relationship has nothing to search; Filament's empty nested `where` is a no-op, and an empty `orX()` would be malformed DQL).
  Otherwise the scoped query gains `andWhere(orX(SearchConstraint::like(...) per column))` — schemas' [`SearchConstraint::like()`](../schemas/search-constraint.md), the engine tables' filter chain moved down for this, which lowers both sides on Postgres or when `forceSearchCaseInsensitive()` says so — then a limit **unconditionally**: a `setMaxResults()` the scope set wins and is written back through `optionsLimit()` (so the control's own cap agrees), else `getOptionsLimit()` (50); then the title ordering; then execute and map.
  Search columns are DQL field paths on the target (`name`, or `address.city` into an embeddable), and `getSearchColumns()` defaults to `[$titleAttribute]` when `searchable()` named none.
- **`getOptionLabelUsing` ⇒ `getOptionLabelFromRelationship()`.**
  `getSelectedRecord()` first, `null` when it answers none, else the record's label — the record-label callback when declared, the title attribute through the accessor otherwise, the same two branches Filament's install has.
- **`getSelectedRecordUsing` ⇒ the scoped query narrowed by the record key**, `null` on a miss ([above](#the-option-actions-are-suffix-actions-with-doctrine-defaults)).
- **`getOptionLabelsUsing` ⇒ `getOptionLabelsFromRelationship(array $values)`.**
  The scoped query narrowed by [`IdentifierConstraint::in()`](../schemas/record-key.md#the-operations-and-their-dql) under the base `sylament_values`, mapped the same two ways.
  The raw client list is filtered to its `int` and `string` entries first — it is client state, so a nested array or an object is dropped rather than failing the batch — and an empty result returns `[]` **before** the query is built, so a list with nothing scalar in it issues no statement at all.
  No ordering, as Filament's has none.

`getOptionsFromRelationship()` is overridden on `Select` too, composing gates → query → limit → ordering → execute → map so the limit and the ordering are on the query before it runs: the searchable-and-not-preloaded gate, the memo, then the limit under Filament's narrower condition (`isSearchable() && filled(getSearchColumns())`, the scope's own limit still winning and writing back), then the title ordering, then the trait's mapping.
Two Filament-parity details ride along.
Both relationship option arms **order by the title attribute** when the scoped query carries no `orderBy` — on the title-attribute path only, exactly where Filament puts it; the record-label-callback branch orders nothing, so a `CheckboxList` with a callback and no title attribute is untouched, and the trait's own map is unordered ([recorded on that field](checkbox-list.md#the-relationship-option-map-is-unordered)).
And the callback branch is what the moved `getOptionLabelFromRecordUsing()` trio makes reachable on `Select`: with it, both the map and the search results label rows through the callback, in the order the database returns them.

### The option actions are suffix actions with Doctrine defaults

`setUp()` registers two closures on the field's own suffix slot — `suffixActions([fn ($c) => $c->getCreateOptionAction(), fn ($c) => $c->getEditOptionAction()])` — and each getter answers `null` until its form is declared, which [`wrap(null)`](../support/helpers.md) drops on the floor.
So every select carries the registration and none of the chrome: no form, no action; `disabled()`, no action either way; `multiple()`, no edit action, because a list has no single record to edit.
Because the suffix cache [splices into the component's action map](../schemas/has-actions.md#the-affix-contract-is-typed-as-a-name-keyed-map), a trigger reaches them by the field's own dotted key (`context.schemaComponent = 'schema.author'`) with no pipeline of its own, and the modal schema lands on `mountedActionsData.<n>` like any other mounted action's.
That composition — a component-registered action *with a modal*, mounted by dotted key — had no test anywhere until this field needed it.

The chain each getter builds is Filament's, in Filament's order, with two things set explicitly that the cache does not supply: `color('gray')` and `iconButton()`.
The [cache's two defaults](has-affixes.md#the-cache-contributes-two-defaults-and-nothing-else) are the small size and the icon-button *template*, and neither is a colour — an affix action that wants gray says so.

Both getters close through `applyOptionActionModifiers()`, which runs the manage-level modifier and then the action's own, each replacing the action only when it returns one — Filament's two `?? $action` guards folded into one loop.
The modifiers still go through [`evaluate()`](../../../packages/support/src/Concerns/EvaluatesClosures.php), so a closure can ask for `Select $component` alongside `Action $action` exactly as Filament's can, and a modifier returning something that is neither an `Action` nor `null` raises a named `TypeError` the way schemas' [`CanModifyActions`](../../../packages/schemas/src/Concerns/CanModifyActions.php) does, rather than surfacing as a return-type failure one frame out.
The callback parameter is typed `?Closure` and the narrowing happens on the **result**, because `evaluate()`'s generic reads T off the value union — a `Closure`-only argument would make it infer `Closure`, which is why the pair of Filament's `?? $action` guards could not simply be kept as they were written.

`relationship()` is overridden here rather than widened in the shared trait: [`CheckboxList`](checkbox-list.md#the-to-many-relationship-arm-serves-this-field-unchanged) composes the same trait and has no option actions, so the override aliases the trait body in (`use HasRelationship { relationship as traitRelationship; }`), calls it, installs the [four search and label arms](#the-relationship-search-arm-runs-a-like-per-column-over-the-scoped-option-query), then three Doctrine defaults:

- **`createOptionUsing`** — `Schema::getData($data)` builds the target entity from the modal schema's class-string record (through `PropertyAccessor::constructRecord()`, so a target whose constructor requires arguments is refused by name) and maps the option action's data onto it: the state the host validated, after the action's `mutateDataUsing()`.
  The reader `persist()`s and `flush()`es it, and the key it returns is `getRecordKey()`.
  The modal schema validates itself and nothing else: its state path scopes the map, so the outer form is untouched.
  The refusal advises handing the schema a record built through `record()`, which an option form cannot follow — the arguments are the data its modal has yet to collect — so for such a target a `createOptionUsing()` of the application's own, declared after `relationship()`, replaces this step and is the way out.
- **`fillEditOptionActionFormUsing`** — the selected record itself, as an object.
  `Schema::fill(object)` runs `readStateFromRecord()`, so the modal's fields arrive filled without a hand-built array.
- **`updateOptionUsing`** — `Schema::getData($data)` maps the option action's data back onto the bound instance and the reader `flush()`es it.

Both write defaults read that data off the option action, which the action's closure hands them by name as `action`, beside Filament's `data`, `form` and `schema`.
Both first refuse, by name, a path the action's `mutateDataUsing()` added that no component of the option form maps ([why](#a-key-an-option-actions-mutation-adds-that-no-component-maps-is-refused)).
Calling `getData()` with no state would re-read the modal's own state and discard a `mutateDataUsing()` on either option action whole; Filament's defaults write `$data`.
Pinned by [`SelectOptionDataTest`](../../../packages/forms/tests/Integration/Components/SelectOptionDataTest.php).
Because the defaults do not re-run `getState()`, the option form validates once per submit, and so does every `unique()`/`exists()` probe it declares.

Both write defaults run `getData()`, so both end in the record check and in its two key rules.
A created option on a key a record already holds is *"This value is already used."* under the option form's own field, with nothing written ([the rule](../schemas/can-be-validated.md#a-taken-assigned-key-is-a-field-error-not-a-unique-violation)); an edit-option save that changes the selected record's key is *"This value cannot be changed."*, the key put back and the select still naming the same record ([the rule](../schemas/can-be-validated.md#a-changed-key-is-refused-and-put-back-not-handed-to-doctrine)) — and the field's to-one relationship arm, writing a different reference onto an identifier association of an existing record, is refused the same way.
The key `createOptionUsing` hands back is minted after the flush as the new row would read it back ([why](../schemas/record-key.md#a-key-is-minted-from-the-value-its-row-would-read-back)): before, a key type that rewrites what was typed — `new-1` read back as `NEW-1` — answered a key the option list did not hold, so the created option was not selected and the parent's save refused it.
Pinned by [`SelectKeyWritesTest`](../../../packages/forms/tests/Integration/Components/SelectKeyWritesTest.php).

All three are installed **unconditionally**, exactly as Filament does it, so a `createOptionUsing()` of your own has to be declared **after** `relationship()` to survive.
That is a real ordering trap and it is pinned in both directions rather than smoothed over — a conditional install would silently change which closure wins for anyone carrying the Filament habit.
The four search and label installs — `getSearchResultsUsing`, `getOptionLabelUsing`, `getSelectedRecordUsing`, `getOptionLabelsUsing` — follow the same rule and the same precedent, and are pinned the same way: a `getOptionLabelUsing()` declared before `relationship()` is overwritten, one declared after wins.

Both write arms end by calling `forgetCachedRelationshipOptions()`, which only clears the [memoized option map](#relationship-is-three-lazy-closures-over-the-association-reader).
The create action's own closure calls it too, one line after the state write, and that call is the load-bearing one: a user-supplied `createOptionUsing` that inserts a row knows nothing about the memo, and without the bust the freshly created key is validated against the map built before the insert and rejected by the `Choice`.
The one on the relationship default is belt and braces for a `createOptionUsing` that is replaced wholesale later in the chain.

`getSelectedRecord()` is nothing but `evaluate()` over `getSelectedRecordUsing()` — Filament's shape, and the seam the port keeps — memoized on the field for as long as the selection stays the same, null for a blank selection or when no resolver is declared, so a **non-relationship** select with an `editOptionForm()` binds a record to its modal by declaring the resolver.
The default `relationship()` installs is a **query**, not a reference: the [scoped option query](#the-relationship-search-arm-runs-a-like-per-column-over-the-scoped-option-query) narrowed by [`IdentifierConstraint::equals()`](../schemas/record-key.md#the-operations-and-their-dql) under the base `sylament_selected` — one predicate per identifier field, and today's `record.id = :sylament_selected` for a single-`id` target — executed with `getOneOrNullResult()`.
A state the codec cannot decode answers `null` **without querying**, since the predicate is `null` and nothing is bound, which is what keeps a forged option value a validation error rather than a 500.
That replaces the [`getReference()`](../schemas/association-reader.md#getreference-decodes-first-and-then-picks-its-route) proxy the relation layer shipped, and both consequences the trail recorded against the proxy go with it: a key whose row was deleted concurrently — or tampered with on the wire — now answers `null` where the proxy raised `EntityNotFoundException` late, so the edit modal mounts empty as Filament's does; and a key outside `modifyQueryUsing`'s scope answers `null` too, which is what makes the same closure the [validation probe's](#the-choice-constraint-is-the-fields-only-server-side-guard) record source — a selection the scope does not admit is not a legal option.
A non-scalar state (a `multiple()` list) answers `null` from the default rather than binding an array to the identifier.
The memo is keyed on the selection because it is consulted at two points in a request where the state may differ — the render before an option action and the validation after it — and Filament's unconditional memo would answer the first selection's record for the second.

### The inline-prefix class counts visible actions where the text input's counts all of them

`sy-fo-select-has-inline-prefix` strips the `<select>`'s left padding so an inline prefix can occupy it, and the template computes it from the **visibility-filtered** prefix actions ([`Select::toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/Select.php) filters before deriving `$hasPrefix`).
The text input and the picker family compute their `sy-fo-input-has-inline-prefix` from the **unfiltered** arrays instead, which is Filament's own asymmetry and is kept: a select whose only prefix action is hidden keeps its padding and renders no gap, while a text input in the same state keeps `ps-0` and the wrapper's own padding carries the space.
There is no suffix twin here — the `<select>` keeps its `pe-8` for the chevron whatever the suffix side holds, so suffix actions (which is where `createOption` / `editOption` live) never touch the control's padding.

### The dropdown flips and escapes the modal

Positioning is Filament's, ported verbatim: `computePosition()` with `offset(4)`, `shift({padding: 5})` and `flip()` — so the list opens above the control when there is no room below and slides to stay inside the viewport — unless `position()` pins `'top'` or `'bottom'`, which drops `flip()` and takes the pinned placement.
The strategy is `absolute` by default and `fixed` when the button sits under a `sy-fixed-positioning-context` ancestor with no nearer `sy-absolute-positioning-context` (Filament's two opt-in classes, prefix-renamed).

One thing is faithful-plus rather than faithful: support's modal stamps `sy-fixed-positioning-context` on its `<dialog>` ([modal.md](../support/modal.md#the-modal-is-a-fixed-positioning-context)), where Filament stamps the *absolute* context on its own, so a select inside a scrolling modal window — sticky header/footer panes, slide-overs — escapes that window's `overflow-y: auto` where Filament's dropdown is clipped by it.

### `reorderable()` drags the chips through SortableJS and writes the order back

`reorderable()` rides in with the same engine: the selected chips are dragged through SortableJS inside it, and `onEnd` rebuilds the state from the badges' `[data-value]` order and reports it through the same state-change callback a click does, so the reorder is one live write like any other.
Because the order is then part of the value, the controller's re-sync compares a reorderable multiple selection against the server's as an **ordered list**, and a server render carrying the same values in another order re-syncs the chips; a non-reorderable multiple compares as a multiset, so an unrelated render does not rebuild its chips over a mere reordering.

### The hidden select lends its id to the combobox button

Filament gives the field's id to its `button[role="combobox"]`, so the field's `<label for>` focuses the control.
Ours does the same at runtime: the controller reads `element.id`, removes the attribute from the hidden `<select>` at construction — a tracked attribute change the host's `ExternalMutationTracker` re-applies after every morph — and restores it on disconnect; the engine sets it on the button.
The server-side contract is unchanged, which is why the render tests still assert `id` on the `<select>`; the handoff is runtime-only and invisible to PHP.
The button also mirrors the `<select>`'s `aria-invalid` and `aria-required` — at construction and after every host render — because the hidden `<select>` is `display: none` and out of the accessibility tree, while Filament's own button carries neither; the JS branch renders `aria-required` on the `<select>` for exactly that mirror, where the native branch renders `required`.

### The engine port corrects seven vendor defects

The port is method-for-method vendor's, with seven behavioural fixes — each a bug in `support/resources/js/utilities/select.js` (5.7.6) rather than a divergence we wanted:

1. `applyDisabledState()` toggles `.fi-select-input-badge-remove` (2292, 2325), a class nothing renders — the badge remove button is `fi-badge-delete-btn` — so a multi-select's remove buttons are never disabled.
   Ours toggles `.sy-badge-delete-btn`.
2. Same method's *enable* branch runs `classList.add('fi-disabled')` on the single-select clear button (2340) where `remove` is meant, so re-enabling a control leaves its clear button styled disabled.
   Ours removes the class.
   Both branches also have a hole ahead of them: `applyDisabledState()` runs synchronously from `render()`, while the badges and the clear button are appended only after `updateSelectedDisplay()`'s `await`, and `disable()` returns early once the flag is set — so a control disabled at construction never revisits them.
   Ours creates those buttons already disabled when the control is.
3. `addBadgesForSelectedOptions()` constructs a new `Sortable` on every full display update (868) and never destroys the previous one, leaking an instance per render.
   Ours destroys it before every rebuild — reorderable or not — when the last chip goes and the placeholder returns, and in `destroy()`, which also cancels a positioning frame still queued by `deferPositionDropdown()` (vendor's `destroy()` at 2352 leaves it to run against the removed container).
4. `handleDropdownKeydown()`'s Enter with no focused option calls `form.submit()` (1297), which fires no `submit` event; under a `live_action('save:prevent', event: 'submit')` form that navigates away instead of saving.
   Ours calls `form.requestSubmit()`.
5. `updateOptionLabelInList()`'s plain-text arm appends a bare text node (1141), dropping the `<span>` its own stylesheet's `.fi-select-input-option > span` arm selects, so a refreshed label loses the option row's truncation.
   Ours keeps the `span.sy-fo-select-option-label` in both arms.
6. `selectOption()` finds the badge to remove with `querySelector(\`[data-value="${value}"]\`)` (2094), the value interpolated raw — a value holding a double quote or a backslash throws a `SyntaxError` before the state changes, so that chip can never be deselected. Ours passes the value through `CSS.escape()`.
7. `handleSearch()`'s empty-query branch (1799) restores the original options but leaves `activeSearchId` alone, so a dynamic search already in flight still commits its results over the emptied box when it lands, and the stale list stays until the dropdown is closed and reopened.
   Ours bumps the id and clears the searching flag there, the way `closeDropdown()` does.

### `ignoreRecord:` narrows the two option arms, and neither label arm

`relationship(ignoreRecord: true)` excludes the bound record from the *pickable* set of a self-referential association, so a row cannot point at itself.
It is applied at exactly two sites — `getOptionsFromRelationship()` and `getSearchResultsFromRelationship()` — and never inside `createScopedOptionQuery()`, which four arms share.

The asymmetry is load-bearing rather than an oversight of Filament's.
The two label arms (`getSelectedRecordUsing` and `getOptionLabelsFromRelationship()`) must keep resolving a value that already names the excluded row — legacy data, or a row written before the flag was added — or the control renders no label at all *and* the [`Choice` guard](#the-choice-constraint-is-the-fields-only-server-side-guard) rejects an untouched submission, so a self-parented row could not be saved at all.
Narrowing the choke point instead would be one line shorter and corrupting.

The option memo is bypassed whenever the flag is on, for the same reason a `modifyQueryUsing()` one is: the map is record-dependent, and [`getClone()`](../schemas/component.md) is a shallow clone, so a memoised map would be served to every repeater item that cloned the field.

The bypass is written twice — the read is guarded and so is the write — and each half is redundant on its own: an instance that never writes the memo has none to read, and one that never reads it does not serve a stale map either.
Only the pair is pinned by a test, because no public call sequence can populate the memo while the flag is on.
The redundancy is kept for fidelity to Filament, whose own guard is at four sites.

Note the default: `relationship(ignoreRecord:)` is `false` while [`unique(ignoreRecord:)`](can-be-validated.md) is `true`.
Both are Filament's, and the contrast is deliberate — a uniqueness probe on an edit page almost always wants to skip the row being edited, while an option map almost never needs to.

The exclusion is `andWhere`d **only when the predicate is non-null**, which is the contract's binding rule (asking for the text is what binds the parameters, so dropping the text leaves parameters bound to nothing).
That `null` arm is unreachable from this call site and is kept anyway: the key it passes was just minted by `findRecordKey()` for a record the method has already proved is an instance of the association's target, so it always decodes.
No test can pin it, and it is correct for the seam rather than for the caller — do not "simplify" it into an unconditional `andWhere`.

`HasRelationship::toRelationshipIdentifier()`'s narrowing to `?string`, with its `(string)` cast, is the same shape one step further out: the cast is an **equivalent** mutation, because its sole consumer `AssociationReader::getReference()` itself opens with `(string) $id`, so no test can observe it.
It is kept as a type-level statement — the relation layer speaks record keys, not `int|string` — and the sentence exists so that a sweep does not restore the wider union and a later reviewer does not re-report the unkillable cast as a gap.

### A composite label read runs one statement per batch, and a composite submission past `maxItems()` reads no label

`getOptionLabelsFromRelationship()` reads one statement per batch of `IdentifierConstraint::batch()`, and merges the answers into a map keyed by record key.
That is the whole list for a single identifier, and at most 500 keys a statement for a composite one.
A `multiple()` relationship select whose target is keyed by more than one field, and whose submission holds more values than `maxItems()` allows, answers with the `Count` violation alone.
The label read behind the membership check is skipped: the submission is refused either way, and that read is what a forged list would make expensive.

The short-circuit is composite-only, and on purpose.
A composite label read is the one a forged list makes expensive — a balanced OR of ANDs per batch, a statement per batch of at most 500 keys.
A single identifier's is one statement over one array parameter, as cheap for a long list as for a short one, so there is nothing to save by skipping it.
Every other `multiple()` select — a single-identifier relationship, static `options()`, an enum — therefore keeps reporting both the `Count` and the membership `Choice` violation.
`hasCompositeRelationshipTarget()` asks the target's class metadata (`isIdentifierComposite`) and issues no statement.
[`SelectCompositeRelationshipTest`](../../../packages/forms/tests/Integration/Components/SelectCompositeRelationshipTest.php) pins both arms, and the membership violation alone for a forged key with no limit and at the limit, and [`SelectMultipleTest`](../../../packages/forms/tests/Integration/Components/SelectMultipleTest.php) keeps the static-options `[Count, Choice]` pin.

### A created option whose identifier is incomplete is refused before it is written

The Doctrine `createOptionUsing` default mints the new record's key *after* it persists and flushes, because a generated identifier does not exist until the insert.
Where the identifier is **assigned** — which is every composite key, since Doctrine refuses an id generator on one — that order commits a row first and then fails minting its key, leaving a record no option can ever name and a create modal that has already written it.
So the default asks [`AssociationReader::hasIncompleteAssignedIdentifier()`](../schemas/association-reader.md#an-incomplete-assigned-identifier-is-the-question-the-create-paths-ask) **before** `persist()`, and [`hasBlankAssignedIdentifier()`](../schemas/association-reader.md#a-blank-assigned-identifier-is-read-after-persist-where-a-record-is-created) again **after** it and before the flush, and refuses by name:

> `The [%s] select created a [%s] whose identifier is incomplete, so no option could name it. Put every identifier field in createOptionForm().`

When the option form's schema turns the key rules off with [`validateRecordKey(false)`](../schemas/can-be-validated.md), a blank key field the form maps is no longer refused under that field and reaches this check instead.
The bare advice would then be false for a field already in the form, so the message gains one sentence after it when the rules are off: *The schema switches the key rules off with validateRecordKey(false), so a key field it maps is not refused while blank; switch them back on to have the blank field refused under it.*
With the rules on, the message is the one above, byte for byte.
`SelectOptionDataTest` pins both forms.
The switch lifts the key pass alone: a key field over a non-nullable property is still [required by the record](can-be-validated.md#requirement-is-derived-from-the-records-write-side-types), so it reaches this check blank only when it is not required — it declares `required(false)` (over a non-nullable property its blank must also survive dehydration as a string, as the test's whitespace does, since a null cannot be written there), or its property is nullable.
What reaches it then depends on what the blank reaches the record as.
A blank that reaches it as a **string** — whitespace — meets this check and the message above whatever the class declares, since the check after `persist()` reads it too.
An empty field over a nullable property reaches it as `null`: the check before `persist()` refuses that with the same message for a class with no `prePersist` callback or entity listener, and for one that declares either the check short-circuits, so `persist()` itself refuses the null with Doctrine's `EntityMissingAssignedId` — the post-persist read is never reached ([why](../schemas/association-reader.md#a-blank-assigned-identifier-is-read-after-persist-where-a-record-is-created)).

It is a developer error rather than validation — the fix is a missing field in `createOptionForm()`, not something an end user can supply — so nothing is written and nothing is restored; a record refused after `persist()` is detached first.
Three shapes pass.
A **pending generated** identifier does, the record's own or one behind an identifier association: that is Doctrine's to fill at the flush two lines later.
So does an identifier a `#[PrePersist]` callback of the class or a mapped entity listener assigns — the check after `persist()` reads it filled.
A callback or listener that fills **no** key half exempts the class from the first check, so the second one is what refuses a half it left blank; without it that half would be flushed, and minting the new option's key would then fail on the row already written.
And so does a **plain single scalar** identifier, always: the member answers `false` for one before it reads anything, because the relation layer keys such a class with no check, and a create option over one needs none.
Validating a key that is *taken*, as opposed to one that is incomplete, is the record check's, which this default reaches through `getData()` ([above](#the-option-actions-are-suffix-actions-with-doctrine-defaults)).
An assigned single scalar key left blank still passes both: the taken-key rule treats a blank component as incomplete and looks nothing up, so it reaches Doctrine as it did.
Pinned by [`SelectCompositeRelationshipTest`](../../../packages/forms/tests/Integration/Components/SelectCompositeRelationshipTest.php), the after-`persist()` arm over a `Planter` whose callback fills neither key half.

### The relation tier reads the owner's class and never its identity

`relationship()` on this field and on [`CheckboxList`](checkbox-list.md#the-to-many-relationship-arm-serves-this-field-unchanged), and the relationship arms of [`Repeater`](repeater.md#a-relationship-repeater-keys-its-items-by-identity-and-diffs-the-collection) beside them, reach the owner record for exactly two things: the class its mapping is read off, and the collection or reference the value is written onto.
None of them builds an owner-scoped query — the option list is [`createOptionQuery()`](../schemas/association-reader.md#the-option-query-is-a-starting-point-not-a-result) over the whole target class, and the loaded state is the association value read off the record — so the width of the owner's identifier is not a question this tier asks, and a record keyed by two scalars, by an association plus a scalar or by an association alone carries these fields as any record does, though the owner half of the relationship queries is composite-aware.

That is recorded rather than left implicit because the obvious "make it composite-aware" sweep is a regression here: routing a to-many field's load through [`createRelationshipQuery()`](../schemas/association-reader.md#an-owner-of-any-width-is-scoped-through-its-own-collection-rather-than-through-a-mapped-by-path) would buy a statement and a correlated subselect for a collection the owner is already holding.
The one owner-facing member the tier does call is `assertRelationshipAcceptsAssociate()`, which reads the owner's **class** and the target's identifier — [the re-keying refusal](../schemas/association-reader.md#no-to-many-relation-verb-moves-a-primary-key), not a scope.
An inverse-side many-to-many save calls the reader's `detach()` and `attach()` as well — [the write half](../schemas/association-reader.md#a-write-lands-on-the-side-doctrine-persists), which mints no key and binds nothing, so the tier still never asks the owner's identity.
Tests pin the whole tier under a composite-owner record so the sweep has a failing test to meet rather than a silent one to pass.

## Divergences

### `native(false)` switches to the JS control

Filament's `native(false)` switches `Select` from a real `<select>` to its own first-party JavaScript control — `support/resources/js/utilities/select.js`, some 2 400 lines on `@floating-ui/dom` + `sortablejs` (the trail once said Choices.js; v5 dropped it, and the premise was stale).
The port switches to **that same control**, ported to TypeScript as `packages/forms/assets/src/utils/select.ts` and driven by the thin first-party Stimulus controller `sylament--forms--select`, both bundled into forms' `dist` with `@floating-ui/dom` and `sortablejs`.

A thin integration around **Tom Select** was the alternative to porting the control, and it was dropped for the friction it accumulated, every item of it the cost of an engine that does not share the server's contract: an own delete button rendered beside Tom Select's, the dropdown settled by hand, a membership score written to keep the server's option order, `$order` and `$id` kept in sync per option, a duplicate `<option selected>` for the value the map did not carry, the selected label re-targeted after a refresh, and the local filter's throttle zeroed.
The port's costs (a 2 400-line hand-port with no JS test rig — the engine's verification is a method-by-method vendor diff plus the browser pass) were accepted against exact DOM/CSS parity, `reorderable()`, Floating UI positioning and one shared vocabulary with Filament.
The controller, its values and its CSS are the template's and the stylesheet's trail ([forms-bundle.md](forms-bundle.md), [styles.md](styles.md)); this file carries what the PHP side promised it and where that differs from Filament:

- **The server render is the option and selection truth.**
  Filament's control fetches `getOptionsForJs` on every open when `hasDynamicOptions()` and probes labels lazily.
  Ours renders `optionsForJs` and `selectedOptions` into the markup on every render, and every live round-trip re-renders, so a "fresh options at open" round-trip buys nothing; the JS asks the server for search results only.
  Dependent options (`options(fn (Get $get) => …)`) therefore follow the last render, so the dependency must be `live()` — which Filament's own dependent-select docs require anyway.
  The four exposed methods are still ported for API parity; only `getSearchResultsForJs()` is called by our JS.
- **In the JS branch the hidden `<select>` carries no `data-model`.**
  UX Live's `SetValueOntoModelFieldsPlugin` writes a single `<select data-model>`'s DOM value back into the value store after every render, so an option the user just searched in — which the in-flight render did not know about — would reset the model to `""` silently.
  The controller owns the binding instead, calling `component.set()` itself from the `statePath` and `modelBinding` values the template hands it; the native branch keeps `data-model` unchanged.
- **The dropdown flips and escapes the modal** ([below](#the-dropdown-flips-and-escapes-the-modal)).
- **The JS-branch `<select>` carries no `required`.**
  Filament's JS branch renders no native constraint attribute at all — it renders no `<select>`.
  Ours keeps a hidden one as the carrier of the Stimulus values and the no-JS fallback, and `required` is the one attribute with a native effect on a hidden control: Enter in the open dropdown with no focused option submits through `form.requestSubmit()`, which runs interactive constraint validation, and a hidden `<select required>` sitting on the placeholder is invalid *and* unfocusable — the submit is blocked with a console error and no visible message, where the server-side required message is the contract.
  The template emits `required` in the native branch only; everything else the hidden `<select>` carries stays, because the engine reads it (`multiple`, `disabled`, `autofocus`, `id`) or a test pins it (`name`, `class`, `aria-invalid`).

### The search round-trip is a live action that re-renders the host

Filament's control reaches `getSearchResultsForJs()` through Livewire's `callSchemaComponentMethod`, which *returns* the value and is `#[Renderless]`.
UX LiveComponent has no return channel, so the port's [`callSchemaComponentMethod()`](../schemas/interacts-with-schemas.md) is a live action that re-renders normally and hands the result back through `dispatchBrowserEvent()` — the same channel actions' `mountAction()` and notifications use.
Every search round-trip therefore re-renders the host; the search debounce (`searchDebounce()`, Filament's default 1000 ms) bounds it, and it is the same cost every `live()` field already pays per keystroke.
The three predicates — `hasDynamicOptions()`, `hasDynamicSearchResults()`, `hasInitialNoOptionsMessage()` — are ported verbatim for the control's contract; `hasDynamicSearchResults()` is the one that decides whether typing asks the server at all, and it is false for a relationship select with no search columns.

### `loadingMessage()` has no render site

The API is ported whole (`HasLoadingMessage`, `'Loading...'`), but under the server-rendered-options rule the only load the control ever performs is a search, which shows `getSearchingMessage()`; the dynamic-options open fetch that `loadingMessage()` accompanies in Filament does not exist here.
The setter and getter stand, the string reaches the controller as a value, and nothing renders it — recorded rather than smoothed over, so the gap is visible from the API.

### `refreshSelectedOptionLabel()` dispatches a browser event the control answers by re-syncing

Filament's two option-action closures end by dispatching a **Livewire** event, `filament-forms::select.refreshSelectedOptionLabel`, carrying `livewireId` + `statePath` so the one Alpine control among many can match itself and re-fetch its selected label.
The port's closures call the same method at the same two points — the create action right after the state write and its hooks, before the create-another branch (`Select.php:272`), the edit action after `updateOptionUsing` (`:417`) — and it dispatches a **browser** event on the host's own root, `sylament:select-refresh-selected-option-label`, payload `{statePath}` alone (the id is unnecessary on the host's element).
The controller matches on `event.target === component.element` and `detail.statePath === statePathValue`, and answers by re-syncing from the values the same response re-rendered — no second label round-trip.
A channel change and a name change together, so it has a ["no Filament source" row](../translations.md#names-with-no-filament-source).
What sat in its place before the JS control was the [option-map bust](#the-option-actions-are-suffix-actions-with-doctrine-defaults), which still runs and is still what makes a re-render show a new row.

### A search column is a mapped field, and the title ordering skips what it cannot order

Filament's `applySearchConstraint()` qualifies whatever column string it is given and lets the database reject a bad one.
Here a search column has to be a field the target entity **maps** — read off the class metadata's field names, which spell an embeddable's as `address.city` — and one that is not (an association hop such as `author.name`, or a virtual property the accessor could read but DQL cannot) is refused up front with a `LogicException` naming the column and the limitation, because a Doctrine `QueryException` from inside a search request is a 500 with no name in it.
Searching through an association is the recorded gap; a `modifyQueryUsing` that joins can still constrain by hand.
The title ordering both option arms add applies the same test the other way: a title attribute that is not a mapped field — one read through an association or a getter — leaves the database's order alone rather than throwing, since the label read still works and the ordering was never part of the shipped contract (the relation layer ordered nothing).

### A composite relationship select past `maxItems()` answers the count alone

Laravel's validator, which Filament validates through, reports every failing rule, so an oversized submission carrying an unknown value answers both the `max` and the `in` message.
A `multiple()` relationship select whose target is keyed by more than one field answers only the `Count` violation once the submission is longer than `maxItems()`.
The membership check behind it is skipped: for a searchable select it is a batched label read that a forged list would make expensive, and for one with a full option map it is a `Choice` over every option, skipped with it.
The submission is refused either way ([why](#a-composite-label-read-runs-one-statement-per-batch-and-a-composite-submission-past-maxitems-reads-no-label)).
Single-identifier relationship, static-options and enum selects report both, as Filament does.
Reversible if the composite label read ever costs what the single-identifier one does.

### The two new strings are untranslated literals

`Loading...` (`filament-forms::components.select.loading_message`) and `Only :count can be selected.` (`…select.max_items_message`, rendered by a plain `:count` substitution — Filament's `trans_choice()` has plural forms, the literal has none) are hardcoded English read verbatim from [`en/components.php`](../../../vendor/filament/forms/resources/lang/en/components.php), literals and not translation keys, the same call as [the option-action strings](#the-option-action-strings-are-untranslated-literals) and [`CanBeSearchable`'s four](can-be-searchable.md#the-four-message-literals-are-untranslated).
The three strings Filament's JS hardcodes (`Search`, `Remove <label>`, `Clear selection`) stay JS literals in the controller.

### The save arm writes a desired list, not attach/detach

Filament's `saveStateToRelationship()` speaks Eloquent relation verbs: `associate()` for a `BelongsTo`, and for a `BelongsToMany` a `detach()` of the diff followed by `sync(detaching: false)` — plus `unsetRelation()` to drop the stale in-memory copy, and `syncWithPivotValues()` when `pivotData()` is set.
Doctrine has no equivalent verbs on the entity: the association *is* the property, and the unit of work computes the difference at flush.

The port therefore writes the whole desired list onto the property, through [`PropertyAccessor::setAssociationOnRecord()`](../schemas/property-accessor.md) — Symfony's accessor diffs it against the collection by strict identity and calls the entity's own `addX()`/`removeX()` per element, so the collection instance survives, the owning side is maintained by the adder the maker generated — or, where the field stands on the **inverse** side of a many-to-many, [by a reconcile through the association reader](#an-inverse-side-many-to-many-is-saved-through-the-owning-side-and-still-written-on-its-own) — and Doctrine emits only the join rows that actually changed ([characterized in `DoctrineCollectionWriteTest`](../../../packages/schemas/tests/Integration/State/DoctrineCollectionWriteTest.php)).
Kept elements diff as kept because a reference by key answers from the identity map with the very instances the loaded collection holds — on **both** of [`getReference()`'s routes](../schemas/association-reader.md#getreference-decodes-first-and-then-picks-its-route), the no-load reference and the `find()` an identifier association forces.
Two things follow from the mechanism rather than from a choice of ours: removing an element from a `ManyToMany` deletes the join row only — the target entity survives, since nothing declares `orphanRemoval` there — while a `OneToMany` that does declare it deletes the child, which is Doctrine's contract and not something the select decides.

`pivotData()` has no home in this shape at all, and is **skipped** rather than blocked — for `Select` and for [`CheckboxList`](checkbox-list.md) alike.
Filament's concern names extra columns to write onto a `BelongsToMany` pivot row.
A Doctrine many-to-many has no columns to address: a join table the mapping owns is not a row object anything can set a column on, and a join carrying columns of its own is by definition a join *entity*.
The recipe for one already ships: `Repeater::relationship()` over the join entity, with a nested relation select inside the item schema for the far side, and one `Hidden::make('role')->default(…)` for a constant column.
A `pivotData()` shim on top of that would be a second, worse spelling of the same thing, so none is added — the fatal names the missing method, which is the clearest signal available.

The single-flush shape also removes Filament's `dehydrated(false)` for the multiple arm.
Upstream it exists because a `BelongsToMany` cannot be written as a model attribute and is saved afterwards by a separate `saveRelationships()` pass; here the save hook fires *from* the dehydrated bag ([`getData()`](../schemas/belongs-to-record.md)), so switching dehydration off would silence the hook instead of protecting it — the multiple relationship select stays dehydrated, deliberately.

### An inverse-side many-to-many is saved through the owning side, and still written on its own

Doctrine persists a many-to-many from its **owning** side alone; an inverse collection is ignored at flush.
So a to-many `relationship()` field on the inverse side — a [`CheckboxList`](checkbox-list.md) over a tag's posts is the likely one — would write the desired list onto a collection nothing persists, and unless the entity's adder happened to call its counterpart back (the shape `make:entity` generates), the save would report success and write **no join row at all**, whatever the width of the record's identifier.
Filament never meets it: `BelongsToMany` writes the pivot from either end.

The to-many arm therefore asks the mapping which side persists.
On the inverse side of a many-to-many it first reconciles the desired list onto the owning side through the [association reader](../schemas/association-reader.md#a-write-lands-on-the-side-doctrine-persists) — every current member no longer desired is `detach()`ed and every new one `attach()`ed, both of which already flip to the related record's own collection — and **then** writes the record's own collection exactly as before.
Kept members are touched by neither, so dropping one member and adding another costs one join-row `DELETE` and one `INSERT`, for a single-identifier record and a composite one alike.
The second write is not redundant: it is what the form re-fills from when it re-renders in the same request, and reconciling the owning side alone re-rendered the **old** selection; it costs no statement, the reconcile's own read having loaded that collection already.
The owning side is untouched, statement for statement, and a one-to-many never reaches the reconcile — the guard names a many-to-many, since a one-to-many is always inverse and routing it through `detach()` would refuse an `orphanRemoval` mapping by name, which the composite-owner one-to-many saves of [`CompositeOwnerRelationshipTest`](../../../packages/forms/tests/Integration/Components/CompositeOwnerRelationshipTest.php) would meet at once.

One save is refused rather than written, and it is the step's one added refusal.
Reaching the owning side means `detach()`, which takes **this** record out of the target's collection; where that collection declares `orphanRemoval` Doctrine treats its members as privately owned and deletes the one taken out rather than unlinking it — here the record the form is saving, with every join row it had.
So the arm computes its removals first and, only when there is one, reads the owning side's own mapping and throws:

> `[%s::$%s] declares orphanRemoval, so a member dropped from it is deleted rather than unlinked — deselecting one in the [%s] field would delete the record this form is saving rather than its join row. Put the field on the owning side of the association, or drop orphanRemoval.`

A save that only **adds** never reaches it, which is why the refusal is computed from the removals and not from the mapping alone.
It costs one statement and writes nothing at all — not even a transaction — so the record and every join row it had survive; an owning side without the flag still deletes the dropped member's join row beside it.
Nothing that would otherwise work is refused, since without the reconcile such a save would write nothing either — the mapping is named instead of being ignored in silence.
The reader refuses the analogous **one-to-many** by name and cannot see this one ([why](../schemas/association-reader.md#a-write-lands-on-the-side-doctrine-persists)), which is what puts the refusal here.

Two costs come with it.
The adder-and-remover contract now reaches the **related** records' collections, so a getter-only owning collection behind an inverse-side field throws Symfony's `NoSuchPropertyException` where the save used to write nothing in silence — the right direction, executed while the arm was designed, and unpinned, since every owning collection the fixtures carry has its adder.
And each added member is read before it is written: `N + 1` reads for `N` added members in a real form, whose options are already loaded, and `2N + 1` cold.
Adding to each related record's `PersistentCollection` directly would skip those reads, but it bypasses the entity's own adder, which is what this whole arm rests on, so it is not taken.

Pinned by [`InverseSideRelationshipTest`](../../../packages/forms/tests/Integration/Components/InverseSideRelationshipTest.php), nine cases — a `Select` and a `CheckboxList` on the inverse side, the same-request re-fill, an empty submission, a record persisted only after the field was filled (the create page's path, and the only case that writes a record's first join row), a record keyed by two columns holding the inverse side, the refusal with the record and its join rows read back through the connection, the add-only save that rides past it, and the owning side as a control — over fixtures whose inverse adders deliberately heal nothing, since a healing adder makes the base pass too, and whose `orphanRemoval` many-to-many is a pair of its own, the only one the forms tree carries.

### A record of another class than the association target is refused

Filament's exclusion clause is `where($record->getQualifiedKeyName(), '!=', $record->getKey())` — a fully qualified column from the *record's own* table.
Pointed at a query over another table it produces `article_contributors.id != 7` inside a select over `authors`, which the database rejects.
Loudly wrong.

Our clause is [`IdentifierConstraint::notEquals()`](../schemas/record-key.md#the-operations-and-their-dql) under the base `sylament_ignored_record` — `record.<identifier> <> :sylament_ignored_record` for a single-`id` target, a negated per-field group for any other width — over the option query's own alias, so the same mistake produces perfectly valid DQL that silently excludes an unrelated row of the *target* table.
The Doctrine shape is more dangerous than the original, so it carries a guard the original does not need: a bound record that is not an instance of the association's target throws a `LogicException` naming both classes.
`ignoreRecord: true` only ever means a self-referential association, and a repeater item schema stamped with the child record is exactly the shape that would otherwise hit it.

The quiet arms stay quiet: `null`, an array and a class-string record exclude nothing — a create page has no row to exclude — and neither does an entity whose identifier is not complete yet, which [`findRecordKey()`](../schemas/association-reader.md#findrecordkey-answers-null-where-getrecordkey-throws) answers `null` for — a generated id still pending, and an **assigned** string component still sitting blank, which is a create form's normal state for a composite key.

### The non-relationship schema record is read off the field, not off a `getActionSchemaModel()`

Filament's create modal binds `$schema->model($component->getActionSchemaModel())` for a select with no relationship, where `getActionSchemaModel()` is a schemas concern method resolving `actionSchemaModel() ?? getRecord() ?? getModel()`.
Neither the setter nor the getter is ported — schemas' component [`HasActions`](../schemas/has-actions.md) has no such surface — so the arm reads what our components do expose: `$component->getRecord() ?? $component->getRecordClass()`, which is the same "the field's own record, or the class it declares" answer minus the per-action override.
A select with no relationship needs `createOptionUsing()` anyway, and that closure receives the modal schema, so a form wanting a different record binds it there.
The setter lands if and when schemas grows the concern.

### A key an option action's mutation adds that no component maps is refused

Filament's create-option default builds `newModelInstance()` and `fill($data)`s it, and its edit default calls `update($data)`.
Eloquent's `fill()` drops a key the target's `$fillable` lacks in silence, or throws under `preventSilentlyDiscardingAttributes()`.
The port maps through the option form's record path map instead, which plays `$fillable`'s part, so a key no component maps could only be dropped in silence.
So both defaults refuse it.
A path the option action's `mutateDataUsing()` added, present in what the action returned but absent from the validated state it was handed ([`getUnmutatedData()`](../actions/README.md)), that no record path of the option form covers is a `LogicException` before anything is mapped:

> `The [%s] select's [%s] action added [%s] in mutateDataUsing(), but no component of its schema maps those paths, so the default write would drop them. Map each with a component — a Hidden one for a value the user should not type, with required(false) where the record would otherwise require it — or take the write over with %s().`

The first `%s` is the select's state path and the second the option action's name.
The paths are listed comma-separated and never with their values.
The last `%s` is `createOptionUsing` or `updateOptionUsing`.
It is the same message the resource pages and the record actions print, with the select as its owner.
Only an **addition** is refused.
A key the client sent is in the state the action was handed, so it is never refused, and it is never written either, since no component maps it.
An overwrite of a mapped key is written as before, and so is a value set on a path a `Hidden` field maps.
A `createOptionUsing()` or `updateOptionUsing()` of your own is never guarded, because the check lives in the defaults.
It is the rule the resource pages and the record actions apply at their own default write seams ([belongs-to-record.md](../schemas/belongs-to-record.md)).
To reach the action's snapshot, the defaults are handed the option action itself, by name, as `action`.
Filament's evaluation passes `data`, `form` and `schema` only, so a closure of your own that declares `$action` now receives it.

### `getEditOptionActionFormData()` also answers an object

Filament types it `?array`, because Eloquent fills a form from `$record->attributesToArray()`.
The Doctrine default hands back the **record** instead — `Schema::fill(object)` runs `readStateFromRecord()`, which is the only reading that goes through the same mapped-state path `getData()` writes back through, association hooks included.
The return type is therefore `object|array<string, mixed>|null`; an array still works and is still what a hand-written `fillEditOptionActionFormUsing()` will usually return.

### The option-action strings are untranslated literals

The seven strings — create label, modal heading and submit label all `Create`, create-another `Create & create another`, edit label and modal heading `Edit`, edit submit `Save` — are hardcoded English, read verbatim from [`en/components.php`](../../../vendor/filament/forms/resources/lang/en/components.php).
Same call as [the placeholder below](#the-default-placeholder-is-an-untranslated-literal) and [`getDefaultLabel`](field.md#getdefaultlabel-drops-label-translation): the translation surface is unported toolkit-wide, so a literal is the honest form rather than a half-built lookup.
The two icons are overridable, though — they resolve through [`FormsIconAlias`](forms-icon-alias.md)'s `COMPONENTS_SELECT_ACTIONS_CREATE_OPTION` and `COMPONENTS_SELECT_ACTIONS_EDIT_OPTION`, falling back to `heroicons:plus-20-solid` and `heroicons:pencil-square-20-solid`.

Resolving them needs an `IconManager`, and Filament reaches its `FilamentIcon` facade.
A component cannot: the manager is a private service the container's `has()` does not answer for, so the closure form (`fn (IconManager $icons) => …`, which [`NavigationItem`](../panels/README.md) can use because it injects the manager itself) resolves nothing here.
`Select` therefore takes the manager through a `#[Required]` setter and falls back to a bare `new IconManager()` when it was built with `new` rather than through the container — the same shape [`CanAccessProperties`](../schemas/property-accessor.md#the-accessor-is-injected-not-reached-for) uses, and the same trade: a hand-built field resolves no registered overrides and renders the default icon.

### The default placeholder is an untranslated literal

`setUp()` sets the placeholder to the literal `'Select an option'`, and `boolean()` to `'-'`.
Filament reads `__('filament-forms::components.select.placeholder')`; the translation surface is unported, matching [`getDefaultLabel`](field.md#getdefaultlabel-drops-label-translation).

### `disablePlaceholderSelection()` is not ported

Filament keeps it as a deprecated alias for `selectablePlaceholder()`.
Porting a method that is already deprecated upstream would import a migration the toolkit never had.
