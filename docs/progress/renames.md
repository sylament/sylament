# Rename table

The single global record of API renames.
Preserve Filament's API names by default, because migrating users grep for them.
Rename only when Filament itself is inconsistent and the better name already exists in their codebase; record every rename here in the same change.

A Filament name missing from [the Renames table](#renames) carries over unchanged.
The systematic idiom and namespace translations, which are not renames, and the names with no Filament source are in [translations.md](translations.md).

## Renames

Every row is a name a migrating user could **not** derive from [the translations](translations.md).

| Filament | Sylament | Kind | Home | Why |
| --- | --- | --- | --- | --- |
| `Filament\Actions\Concerns\InteractsWithActions` | `HasActions` | trait | `Sylament\Actions\Concerns\HasActions` | [Swapped](#interface-and-trait-names-are-swapped): `Has*` is the house trait prefix. |
| `Filament\Actions\Contracts\HasActions` | `InteractsWithActions` | interface | `Sylament\Actions\Contracts\InteractsWithActions` | [Swapped](#interface-and-trait-names-are-swapped): the interface names the behaviour the host implements. |
| `Filament\Forms\Components\Contracts\HasValidationRules` | `HasValidationConstraints` | interface | `Sylament\Schemas\Components\Contracts\HasValidationConstraints` | Relocated into schemas, whose schema walk type-checks it and which may not reference a forms symbol; renamed per the [constraint vocabulary](#laravel-rule-vocabulary-becomes-symfony-constraint-vocabulary). |
| `unique(modifyRuleUsing:)` / `exists(modifyRuleUsing:)` (forms `CanBeValidated`) | `modifyQueryUsing:` | parameter | `Sylament\Forms\Components\Concerns\CanBeValidated` | [The payload changes](#a-name-changes-when-its-payload-changes): the closure receives a Doctrine `QueryBuilder`. |
| `unique(table:)` / `exists(table:)` (forms `CanBeValidated`) | `entity:` | parameter | `Sylament\Forms\Components\Concerns\CanBeValidated` | [The payload changes](#a-name-changes-when-its-payload-changes): the probe takes an entity class-string. |
| `unique(column:)` / `exists(column:)` (forms `CanBeValidated`) | `property:` | parameter | `Sylament\Forms\Components\Concerns\CanBeValidated` | [The payload changes](#a-name-changes-when-its-payload-changes): the probe names a mapped field ([probes-records.md](forms/probes-records.md#unique-and-exists-are-one-dql-probe-behind-a-container-service)). |
| `Filament\Schemas\Components\Concerns\BelongsToModel` | `BelongsToRecord` | trait | `Sylament\Schemas\Components\Concerns\BelongsToRecord` | [Record vocabulary](#the-model-vocabulary-collapses-onto-record). |
| `Filament\Schemas\Concerns\BelongsToModel` | `BelongsToRecord` | trait | `Sylament\Schemas\Concerns\BelongsToRecord` | [Record vocabulary](#the-model-vocabulary-collapses-onto-record). |
| `Filament\Schemas\Concerns\InteractsWithSchemas` | `HasSchemas` | trait | `Sylament\Schemas\Concerns\HasSchemas` | [Swapped](#interface-and-trait-names-are-swapped): `Has*` is the house trait prefix. |
| `Filament\Schemas\Contracts\HasSchemas` | `InteractsWithSchemas` | interface | `Sylament\Schemas\Contracts\InteractsWithSchemas` | [Swapped](#interface-and-trait-names-are-swapped): the interface names the behaviour. |
| `Filament\Support\Concerns\CanAggregateRelatedModels` | `CanAggregateRelatedRecords` | trait | `Sylament\Tables\Columns\Concerns\CanAggregateRelatedRecords` | [Record vocabulary](#the-model-vocabulary-collapses-onto-record), and [moved into tables](translations.md#namespace-segments); its setters and getters keep Filament's names ([can-aggregate-related-records.md](tables/can-aggregate-related-records.md#an-aggregate-column-names-itself-freely-where-filaments-must-match-laravels-alias)). |
| `Filament\Tables\Columns\BadgeColumn` | — not ported | class | — | [Deprecated](#a-deprecated-filament-member-is-not-ported): `TextColumn::make('x')->badge()` ships ([text-column.md](tables/text-column.md)). |
| `Filament\Tables\Columns\BooleanColumn` | — not ported | class | — | [Deprecated](#a-deprecated-filament-member-is-not-ported): `IconColumn::make('x')->boolean()` ships ([icon-column.md](tables/icon-column.md)). |
| `Filament\Tables\Columns\Concerns\CanBeCopied` | — not ported | trait | — | [Deprecated](#a-deprecated-filament-member-is-not-ported): the support-tier [`CanBeCopied`](support/can-be-copied.md) it re-exports ships. |
| `Filament\Tables\Columns\TagsColumn` | — not ported | class | — | [Deprecated](#a-deprecated-filament-member-is-not-ported): `TextColumn` with `badge()` and `limitList()` ships ([text-column.md](tables/text-column.md)). |
| `Filament\Tables\Concerns\InteractsWithTable` | `HasTable` | trait | `Sylament\Tables\Concerns\HasTable` | [Swapped](#interface-and-trait-names-are-swapped): `Has*` is the house trait prefix. |
| `Filament\Tables\Contracts\HasTable` | `InteractsWithTable` | interface | `Sylament\Tables\Contracts\InteractsWithTable` | [Swapped](#interface-and-trait-names-are-swapped): the interface names the behaviour the host implements. |
| `Filament\Tables\Filters\MultiSelectFilter` | — not ported | class | — | [Deprecated](#a-deprecated-filament-member-is-not-ported): `SelectFilter::make('x')->multiple()` ships ([select-filter.md](tables/select-filter.md)). |
| `afterCreate()` / `afterUpdate()` / `afterDelete()` (`Repeater`'s relationship surface) | — not ported | method | — | The single flush has no per-item save to fire them around; Doctrine's lifecycle events are the seam ([repeater-relationship.md](forms/repeater-relationship.md#the-relationship-family-loses-its-caches-its-lifecycle-hooks-and-its-query-scope)). |
| `alpineClickHandler()` (`Actions\Action`) | — not ported | method | — | [No expression language takes the string](#alpineclickhandler-has-no-expression-language-to-hand-a-string-to). |
| `associateRecordWithParent()` (`Resources\Pages\CreateRecord`) | `attachRecordToParent()` | method | `Sylament\Panels\Resources\Resource\Concerns\BelongsToParent`, composed by `Resource` | Moves onto the resource and writes the owning side in memory ([nested-resources.md](panels/nested-resources.md#the-attach-lands-on-the-resource-and-writes-the-owning-side-in-memory)). |
| `column()` / `getColumn()` (`SelectFilter`) | — not ported | method | — | [Deprecated](#a-deprecated-filament-member-is-not-ported) aliases of `attribute()` / `getAttribute()` ([select-filter.md](tables/select-filter.md#the-deprecated-column-alias-is-not-ported)). |
| `dehydrated(false)`, applied by `relationship()` (`Repeater`, `EntanglesStateWithSingularRelationship`) | — not applied | behaviour | — | The dehydration walk routes the value to the save hook, so pruning it would switch the feature off ([entangles](schemas/entangles-state-with-singular-relationship.md#subtree-ownership-not-dehydratedfalse-is-what-makes-the-hooks-fire), [repeater-relationship.md](forms/repeater-relationship.md#the-relationship-family-loses-its-caches-its-lifecycle-hooks-and-its-query-scope)). |
| `disableAttachAnother()` (`AttachAction`) / `disableAssociateAnother()` (`AssociateAction`) | — not ported | method | — | [Deprecated](#a-deprecated-filament-member-is-not-ported) aliases of `attachAnother(false)` / `associateAnother(false)` ([attach-action.md](actions/attach-action.md), [associate-action.md](actions/associate-action.md)). |
| `disableClick()` (`Columns\Concerns\CanBeDisabled`) | — not ported | method | — | [Deprecated](#a-deprecated-filament-member-is-not-ported) alias of `disabledClick()` ([column.md](tables/column.md)). |
| `size()` / `height()` / `rounded()` (`ImageColumn`) | — not ported | method | — | [Deprecated](#a-deprecated-filament-member-is-not-ported) aliases of `imageSize()` / `imageHeight()` / `circular()` ([image-column.md](tables/image-column.md)). |
| `fi-fo-field-wrp-error-list` / `fi-fo-field-wrp-error-message` (CSS classes) | `sy-fo-field-error-list` / `sy-fo-field-error-message` | CSS class | the [`FieldWrapper`](../../packages/forms/templates/components/FieldWrapper/index.html.twig) template and [`field.css`](../../packages/forms/assets/src/styles/components/field.css) | [The `wrp` segment names nothing](#the-error-list-classes-drop-the-wrapper-segment-filament-renders-nowhere) ([field.md](forms/field.md#error-display-shows-every-message-for-the-path)). |
| `form()` / `getFormSchema()` (`Filters\Concerns\HasSchema`) | — not ported | method | — | [Deprecated](#a-deprecated-filament-member-is-not-ported) aliases of `schema()` / `getSchemaComponents()` ([base-filter.md](tables/base-filter.md#the-form-alias-is-not-ported)). |
| `forms::components.*` (the `FormsIconAlias` values) | `components.*` | icon-alias value | `Sylament\Forms\Twig\FormsIconAlias` | [Prefix dropped](#icon-alias-values-drop-filaments-package-prefix) ([forms-icon-alias.md](#icon-alias-values-drop-filaments-package-prefix)). |
| `getDefaultActionModel()` / `getDefaultActionModelLabel()` (`Contracts\HasActions`) | `getDefaultActionRecordClass()` / `getDefaultActionRecordLabel()` | method | `Sylament\Actions\Contracts\InteractsWithActions` | [Record vocabulary](#the-model-vocabulary-collapses-onto-record) on two [host override hooks](actions/interacts-with-actions.md#the-panel-level-default-hooks-are-on-the-contract-the-authorization-pair-deferred). |
| `getDescriptionUsing()` (`Grouping\Group`) | — not ported | method | — | [Deprecated](#a-deprecated-filament-member-is-not-ported) alias of `getDescriptionFromRecordUsing()` ([grouping.md](tables/grouping.md#surface)). |
| `getEloquentQuery()` (`Resources\Resource`) | `getRecordQuery()` | method | `Sylament\Panels\Resources\Resource` | ["Eloquent" goes with its ecosystem](#eloquent-in-a-method-name-goes-with-its-ecosystem) ([resource.md](panels/resource.md)). |
| `getExtraFieldWrapperAttributesBag()` (`HasExtraFieldWrapperAttributes`) | `getExtraFieldWrapperAttributeBag()` | method | `Sylament\Forms\Components\Concerns\HasExtraFieldWrapperAttributes` | [Filament's majority spelling](#getextrafieldwrapperattributebag-takes-filaments-majority-spelling). |
| `getExtraRecordLinkAttributeBag()` (`Table\Concerns\HasRecordUrl`) | — not ported | method | — | [Nothing consumes the bag](#getextrarecordlinkattributebag-has-no-consumer) ([table.md](tables/table.md#surface)). |
| `getRelationship()` / `getRelationshipName()` / `getRelationshipAttribute()` (`Grouping\Group`) | — not ported | method | — | [`RelationPathResolver`](tables/has-query.md#a-dotted-name-is-resolved-against-the-mapping-before-it-is-refused) reads the split off the mapping, as the [column tier](tables/column.md#relationship-traversal-ships-as-a-value-walk-a-side-store-and-one-kept-member) does. |
| `getTabsContentComponent()` (`Resources\Concerns\HasTabs`) | `getTabsSchema()` | method | `Sylament\Panels\Resources\Concerns\HasTabs` | [The return type changes](#a-name-changes-when-its-payload-changes) from a component to a schema ([has-tabs.md](panels/has-tabs.md#the-strip-is-a-schema-a-partial-renders-not-a-component-a-content-schema-holds)). |
| `getGlobalSearchEloquentQuery()` (`Resource\Concerns\HasGlobalSearch`) | `getGlobalSearchRecordQuery()` | method | `Sylament\Panels\Resources\Resource\Concerns\HasGlobalSearch` | ["Eloquent" goes with its ecosystem](#eloquent-in-a-method-name-goes-with-its-ecosystem) ([global-search.md](panels/global-search.md#getglobalsearcheloquentquery-is-getglobalsearchrecordquery)). |
| `getGlobalSearchProvider()` (`Panel\Concerns\HasGlobalSearch`) | `getGlobalSearchProviderClass()` | method | `Sylament\Panels\Panel\Concerns\HasGlobalSearch` | [The return type changes](#a-name-changes-when-its-payload-changes) from an instance to a class-string ([global-search.md](panels/global-search.md#the-provider-is-resolved-through-a-tagged-registry-rather-than-app)). |
| `getModel()` (`BelongsToModel`, both tiers) | `getRecordClass()` | method | both tiers of `…\BelongsToRecord` | [Record vocabulary](#the-model-vocabulary-collapses-onto-record). |
| `getModel()` (`Table\Concerns\HasRecords`) | `getRecordClass()` | method | `Sylament\Tables\Table\Concerns\HasRecords` | [Record vocabulary](#the-model-vocabulary-collapses-onto-record) ([has-query.md](tables/has-query.md#the-record-class-is-read-off-the-base-querys-root-entity)). |
| `getModel()` (`Resources\Resource`) | `getRecordClass()` | method | `Sylament\Panels\Resources\Resource` | [Record vocabulary](#the-model-vocabulary-collapses-onto-record); the class is [declared, never derived](panels/resource.md#the-record-class-is-declared-never-derived-from-an-application-namespace). |
| `getModel()` / `getCustomModel()` (actions `InteractsWithRecord`) | `getRecordClass()` / `getCustomRecordClass()` | method | `Sylament\Actions\Concerns\InteractsWithRecord` | [Record vocabulary](#the-model-vocabulary-collapses-onto-record) ([action.md](actions/action.md#the-record-chain-reaches-the-schema-the-component-and-the-host)). |
| `getModelInstance()` (`BelongsToModel`, both tiers) | `getRecordInstance()` | method | both tiers of `…\BelongsToRecord` | [Record vocabulary](#the-model-vocabulary-collapses-onto-record). |
| `getModelLabel()` / `getPluralModelLabel()` / `getTitleCaseModelLabel()` / `getTitleCasePluralModelLabel()` (`Resource\Concerns\HasLabels`) | `getRecordLabel()` / `getPluralRecordLabel()` / `getTitleCaseRecordLabel()` / `getTitleCasePluralRecordLabel()` | method | `Sylament\Panels\Resources\Resource` | [Record vocabulary](#the-model-vocabulary-collapses-onto-record) ([pluralization](panels/resource.md#pluralization-takes-the-inflectors-last-candidate)). |
| `getModelLabel()` / `getTitleCaseModelLabel()` / `getCustomModelLabel()` / `getPluralModelLabel()` / `getTitleCasePluralModelLabel()` / `getCustomPluralModelLabel()` (actions `InteractsWithRecord`) | `getRecordLabel()` / `getTitleCaseRecordLabel()` / `getCustomRecordLabel()` / `getPluralRecordLabel()` / `getTitleCasePluralRecordLabel()` / `getCustomPluralRecordLabel()` | method | `Sylament\Actions\Concerns\InteractsWithRecord` | [Record vocabulary](#the-model-vocabulary-collapses-onto-record) ([action.md](actions/action.md#the-record-chain-reaches-the-schema-the-component-and-the-host)). |
| `get_model_label()` (`Filament\Support`) | `get_record_label()` | function | `Sylament\Support` ([`helpers.php`](../../packages/support/src/helpers.php)) | [Record vocabulary](#the-model-vocabulary-collapses-onto-record) ([helpers.md](support/helpers.md#get_record_label-is-a-rename-and-it-is-what-put-symfonystring-in-supports-manifest)). |
| `getMountedActionSchemaModel()` (`Concerns/InteractsWithActions.php`) | `getMountedActionSchemaRecord()` | method | `Sylament\Actions\Concerns\HasActions` | [Record vocabulary](#the-model-vocabulary-collapses-onto-record); it answers an instance or a class-string as Filament's does, and also an [array record](../../packages/support/src/ArrayRecord.php) ([has-actions.md](actions/has-actions.md#the-getdefaultaction-host-override-hooks-are-ported-the-authorization-pair-deferred)). |
| `getHeader()` / `getFooter()` (`Pages\Page`) | — not ported | method | — | The header and footer are named Twig blocks a page template overrides ([custom-pages.md](panels/custom-pages.md#the-block-contract-is-the-page-bodys-public-api)). |
| `getRecordRouteBindingEloquentQuery()` (`Resource\Concerns\HasRoutes`) | — not ported | method | — | Route binding reads [`getRecordQuery()`](panels/resource.md), so the record a URL resolves and the records a list shows cannot drift apart ([under a parent too](panels/nested-resources.md#the-parent-scope-is-the-readers-narrowing-of-the-resources-own-query-not-a-predicate-we-write)). |
| `globalSearchSort(?int $sort): void` (`Resource\Concerns\HasGlobalSearch`) | — not ported | method | — | A resource redeclares the `$globalSearchSort` static instead ([global-search.md](panels/global-search.md#the-resource-opt-in-mode-and-the-globalsearchsort-setter-are-not-ported)). |
| `groupQuery()` / `groupQueryUsing()` (`Grouping\Group`) | — not ported | method | — | Superseded by the per-group scoped query ([grouping.md](tables/grouping.md#groupquery-is-superseded-by-the-per-group-scoped-query)). |
| `groupsInDropdownOnDesktop()` (`Table\Concerns\CanGroupRecords`) | — not ported | method | — | [Deprecated](#a-deprecated-filament-member-is-not-ported) alias of `groupingSettingsInDropdownOnDesktop()` ([grouping.md](tables/grouping.md#surface)). |
| `headingTag` (`tables::components.filters` view prop) | `headingLevel` | template prop | the `Filters` template (`Sylament\Tables`) | [Narrowed to the level](#a-tag-name-template-prop-narrows-to-what-it-selects). |
| `hiddenOn(livewireComponents:)` / `visibleOn(livewireComponents:)` (the three tables `CanBeHidden` traits) | `hosts:` | parameter | `Sylament\Tables\Columns\Concerns\CanBeHidden`, `Sylament\Tables\Filters\Concerns\CanBeHidden`, `Sylament\Tables\Columns\Summarizers\Concerns\CanBeHidden` | [The parameter takes host class-strings](#hiddenon-takes-host-class-strings-so-its-parameter-is-hosts). |
| `inverseRelationship()` (`Support\Concerns\HasCellState`, the column tier) | — not ported | method | — | The summarizer tier, its only consumer, [refuses a to-many path](tables/summarizer.md#the-batch-keeps-a-to-one-relation-column-and-refuses-a-to-many-one) ([column.md](tables/column.md#relationship-traversal-ships-as-a-value-walk-a-side-store-and-one-kept-member)). |
| `inverseRelationship()` / `getInverseRelationship()` / `getInverseRelationshipFor()` (`Table\Concerns\HasQuery`) | — not ported | method | — | Doctrine's mapping states the counterpart ([has-query.md](tables/has-query.md#the-inverse-relationship-is-read-off-the-mapping-not-derived)). |
| `labelTag` (`Field::wrapEmbeddedHtml()` parameter) | `labelAsHeading` | template prop | the `FieldWrapper` template (`Sylament\Forms`) | [Narrowed to a boolean](#a-tag-name-template-prop-narrows-to-what-it-selects) ([field.md](forms/field.md#labeltag-is-a-boolean-labelasheading)). |
| `model()` (`BelongsToModel`, both tiers) | `record()` | method | both tiers of `…\BelongsToRecord` | [Record vocabulary](#the-model-vocabulary-collapses-onto-record); `record()` also takes the class-string. |
| `model()` / `modelLabel()` (actions `InteractsWithRecord`) | `recordClass()` / `recordLabel()` | method | `Sylament\Actions\Concerns\InteractsWithRecord` | [Record vocabulary](#the-model-vocabulary-collapses-onto-record); the action's `record()` may carry a bare key, so the class has a setter of its own. |
| `modelLabel()` / `pluralModelLabel()` / `getModelLabel()` / `getPluralModelLabel()` (`Table\Concerns\HasRecords`) | `recordLabel()` / `pluralRecordLabel()` / `getRecordLabel()` / `getPluralRecordLabel()` | method | `Sylament\Tables\Table\Concerns\HasRecords` | [Record vocabulary](#the-model-vocabulary-collapses-onto-record) ([table.md](tables/table.md#the-record-title-reads-the-attribute-through-the-accessor-and-falls-back-to-the-label)). |
| `$model` / `$modelLabel` / `$pluralModelLabel` (`Resources\Resource` statics) | `$recordClass` / `$recordLabel` / `$pluralRecordLabel` | property | `Sylament\Panels\Resources\Resource` | [Record vocabulary](#the-model-vocabulary-collapses-onto-record); the deprecated `$label` / `$pluralLabel` are [not ported](#a-deprecated-filament-member-is-not-ported). |
| `$model` (closure parameter) | `$recordClass` | closure param | `Sylament\Actions\Action`, `Sylament\Actions\ActionGroup` | [Record vocabulary](#the-model-vocabulary-collapses-onto-record) ([action.md](actions/action.md#the-record-chain-reaches-the-schema-the-component-and-the-host)). |
| `mountedActions.{i}.data` (state path) | `mountedActionsData.{i}` | state path | `Sylament\Actions\Concerns\HasActions` | [A same-named getter would shadow it](#a-path-or-prop-a-same-named-getter-would-shadow-is-renamed) ([has-actions.md](actions/has-actions.md#per-mount-form-state-lives-in-a-sibling-bag-not-in-the-stack-descriptor)). |
| `mountHasUnsavedDataChangesAlert()` (`Pages\Concerns\HasUnsavedDataChangesAlert`) | — not ported | method | — | [A Livewire hook convention](#a-livewire-hook-named-by-convention-has-no-ux-counterpart) ([unsaved-changes-alert.md](panels/unsaved-changes-alert.md#the-alert-is-armed-by-explicit-rememberdata-calls-because-ux-has-no-mounttrait-hook)). |
| `mutateFormDataUsing()` / `formData()` / `resetFormData()` / `getFormData()` / `getRawFormData()` (`Concerns\HasData`) | — not ported | method | — | [Deprecated](#a-deprecated-filament-member-is-not-ported) aliases of `mutateDataUsing()` / `data()` / `resetData()` / `getData()` / `getRawData()` ([actions README](actions/README.md)). |
| `notifications::notification.*` (the five `NotificationsIconAlias` notification values) | `notification.*` | icon-alias value | `Sylament\Notifications\Twig\NotificationsIconAlias` | [Prefix dropped](#icon-alias-values-drop-filaments-package-prefix) ([notifications-icon-alias.md](notifications/notifications-icon-alias.md#the-alias-values-drop-filaments-package-prefix)). |
| `open-global-search-results` (Livewire event) | — not ported | event | — | A live morph reopens the results panel, so nothing dispatches ([global-search.md](panels/global-search.md#nothing-dispatches-an-open-event-a-morph-reopens-the-panel)). |
| `SimpleUserMenu` (`Livewire\SimpleUserMenu`) | — not ported | class | — | With authentication parked, the simple layout's strip holds the ordinary `UserMenu` ([simple-page.md](panels/simple-page.md#the-simple-layouts-auth-gates-are-absent-and-the-strip-holds-the-ordinary-user-menu)). |
| `options()` (`IconColumn`) | — not ported | method | — | [Deprecated](#a-deprecated-filament-member-is-not-ported) alias of `icons()` ([icon-column.md](tables/icon-column.md)). |
| `panels::*` (the `PanelsIconAlias` values) | `*` | icon-alias value | `Sylament\Panels\Twig\PanelsIconAlias` | [Prefix dropped](#icon-alias-values-drop-filaments-package-prefix); `USER_MENU_TRIGGER` is [an addition](panels/layout-templates.md#user_menu_trigger-is-an-addition-not-a-rename) ([layout-templates.md](panels/layout-templates.md)). |
| `$relatedModel` (`EntanglesStateWithSingularRelationship::relationship()`, third argument) | — not ported | method param | — | The mapping names the target ([entangles](schemas/entangles-state-with-singular-relationship.md#the-relatedmodel-argument-is-dropped-the-mapping-names-the-target)). |
| `$draggedRecordKey` (`reorderTable()`'s second argument, `Tables\Concerns\CanReorderRecords`) | — not ported | method param | — | Filament's body never reads it, so the live action takes the order alone ([host-can-reorder-records.md](tables/host-can-reorder-records.md#the-reorder-write-is-a-unit-of-work-flush-not-one-mass-case-update)). |
| `removeLivewireClickHandler()` (`Filters\Indicator`) | — not ported | method | — | Removal is a structured action name and argument map ([indicator.md](tables/indicator.md#removal-is-structured-not-a-click-handler-string)). |
| `resetState(['value' => null])` in `SelectFilter::setUp()` | reset key follows `isMultiple()` | behaviour | `Sylament\Tables\Filters\SelectFilter` | A multiple filter resets `['values' => []]` ([select-filter.md](tables/select-filter.md#the-reset-state-clears-the-key-the-state-actually-uses)). |
| `rule()` / `rules()` (forms `CanBeValidated`) | `constraint()` / `constraints()` | method | `Sylament\Forms\Components\Concerns\CanBeValidated` | [Constraint vocabulary](#laravel-rule-vocabulary-becomes-symfony-constraint-vocabulary): the payload is Symfony `Constraint` objects. |
| `saveRelationships()` (both tiers, plus `saveRelationshipsBeforeChildren()` and `castStateAfterLoadingFromRelationships()`) | — not ported | method | — | The third pass collapses into one flush, and the `…Using` hooks [are consulted by the two mapping edges](schemas/belongs-to-record.md#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk); `loadStateFromRelationships()` [is ported](schemas/belongs-to-record.md#loadstatefromrelationships-is-the-hosts-reload-after-its-flush-and-takes-no-parameter). |
| `selectPivotDataInQuery()` (`Table\Concerns\HasQuery`) | — not ported | method | — | An association table carrying payload is an entity of its own, so there are no pivot columns ([association-reader.md](schemas/association-reader.md#only-the-shapes-doctrine-states-in-one-mapping-are-read), [table.md](tables/table.md#surface)). |
| `$selectedTableRecords` (`Tables\Concerns\HasBulkActions` property) | `$selectedTableRecordKeys`, with `fieldName: 'selectedTableRecords'` | property | `Sylament\Tables\Concerns\HasBulkActions` | [A same-named getter would shadow it](#a-path-or-prop-a-same-named-getter-would-shadow-is-renamed) ([host-can-paginate-records.md](tables/host-can-paginate-records.md#the-writable-props-dehydrate-in-their-elements-wire-vocabulary)). |
| `$shouldHydrate` (`Schema::loadStateFromRelationships()`'s argument) | — not ported | method param | — | The one caller always hydrates ([belongs-to-record.md](schemas/belongs-to-record.md#loadstatefromrelationships-is-the-hosts-reload-after-its-flush-and-takes-no-parameter)). |
| `schema()` (`HasChildComponents` / root `HasComponents`) | — not ported | method | — | `components()` is the sole spelling ([component.md](schemas/component.md#the-schema-alias-is-not-ported)). |
| `tables::*` (the `TablesIconAlias` values) | `*` | icon-alias value | `Sylament\Tables\Twig\TablesIconAlias` | [Prefix dropped](#icon-alias-values-drop-filaments-package-prefix). |
| `tables::empty-state` (`TablesIconAlias::EMPTY_STATE` value) | `table.empty-state` | icon-alias value | `Sylament\Tables\Twig\TablesIconAlias` | [Prefix dropped](#icon-alias-values-drop-filaments-package-prefix) and a `table.` component segment added ([tables-bundle.md](#icon-alias-values-drop-filaments-package-prefix)). |
| The unqualified attribute in `TernaryFilter::boolean()` / `nullable()` | qualified through `ColumnPathResolver` | behaviour | `Sylament\Tables\Filters\TernaryFilter` | A joined query gets no ambiguous column ([ternary-filter.md](tables/ternary-filter.md#the-arms-qualify-the-attribute-where-filaments-leave-it-bare)). |
| `toggleColumnsTriggerAction()` / `getToggleColumnsTriggerAction()` / `columnToggleFormColumns()` / `getColumnToggleFormColumns()` / `columnToggleFormMaxHeight()` / `getColumnToggleFormMaxHeight()` / `columnToggleFormWidth()` / `getColumnToggleFormWidth()` (`Table\Concerns\HasColumnManager`) | — not ported | method | — | [Deprecated](#a-deprecated-filament-member-is-not-ported) aliases of the `columnManager*()` spellings ([column-manager.md](tables/column-manager.md#surface)). |
| `translateLabel()` (`Columns\Summarizers\Concerns\HasLabel`) | — not ported | method | — | Every label is a [literal, not a translation key](translations.md#idiom), until the [i18n pass](../internals/i18n.md) ([summarizer.md](tables/summarizer.md#surface)). |
| `updatedTableColumnSearches($value = null, ?string $key = null)` (`Concerns\CanSearchRecords`) | — not ported | method | — | [A Livewire hook convention](#a-livewire-hook-named-by-convention-has-no-ux-counterpart) ([host-can-search-records.md](tables/host-can-search-records.md#a-full-seed-replaces-filaments-reserved-key-list-and-a-blank-is-kept)). |
| `updatedTableGroupColumn()` (`Concerns\CanGroupRecords`) | — not ported | method | — | [A Livewire hook convention](#a-livewire-hook-named-by-convention-has-no-ux-counterpart), and dead upstream ([grouping.md](tables/grouping.md#a-grouping-change-resets-the-page-and-flushes-the-record-cache)). |
| `validationMessages()` keys (`required`, `max`, `email`, …) | `class-string<Constraint>` keys (`RequiredValue::class`, `Length::class`, …) | array keys | `Sylament\Forms\Components\Concerns\CanBeValidated` | [Constraint vocabulary](#laravel-rule-vocabulary-becomes-symfony-constraint-vocabulary) ([can-be-validated.md](forms/can-be-validated.md#validation-messages-apply-after-the-constraints-are-built)). |
| `x-sortable` (the Alpine directive) | `data-controller="sylament--support--sortable"` | template attribute | the `sortable` controller (`Sylament\Support`, `assets/src/controllers/sortable_controller.ts`) | [The directive becomes a controller](#the-sortable-directive-becomes-a-controller-whose-markers-stay-plain-attributes). |
| `x-sortable-item` | `data-sortable-item` | template attribute | the `sortable` controller (`Sylament\Support`) | [A plain attribute, not a target](#the-sortable-directive-becomes-a-controller-whose-markers-stay-plain-attributes). |
| `x-sortable-handle` | `data-sortable-handle` | template attribute | the `sortable` controller (`Sylament\Support`) | [A plain attribute, not a target](#the-sortable-directive-becomes-a-controller-whose-markers-stay-plain-attributes). |
| `x-sortable-group` | the `group` Stimulus value (`data-sylament--support--sortable-group-value`) | template attribute | the `sortable` controller (`Sylament\Support`) | [A declared controller value](#the-sortable-directive-becomes-a-controller-whose-markers-stay-plain-attributes). |
| `data-sortable-animation-duration` | the `animationDuration` Stimulus value (`data-sylament--support--sortable-animation-duration-value`) | template attribute | the `sortable` controller (`Sylament\Support`) | [A declared controller value](#the-sortable-directive-becomes-a-controller-whose-markers-stay-plain-attributes). |
| `Section::formBefore()` / `isFormBefore()` | `contentBefore()` / `hasContentBefore()` | method | `Sylament\Schemas\Components\Concerns\HasContentBefore` | Adopts the `contentBefore` prop name of Filament's own support-layer section component. |
| `ButtonComponent::make(isOutlined: true)` | `OutlinedButtonComponent` | class | `Sylament\Support\Colors\Components\OutlinedButtonComponent` | The outlined variant is a subclass, not constructor state ([color-policies.md](support/color-policies.md#the-outlined-button-is-a-subclass-not-a-constructor-flag)). |

### Interface and trait names are swapped

⚠️ **Grep trap.**
For schemas, actions and tables alike, `Has*` and `InteractsWith*` exist on **both** sides with **opposite** meanings:

| | Filament | Sylament |
| --- | --- | --- |
| **interface** | `HasSchemas` / `HasActions` / `HasTable` | `InteractsWithSchemas` / `InteractsWithActions` / `InteractsWithTable` |
| **trait** | `InteractsWithSchemas` / `InteractsWithActions` / `InteractsWithTable` | `HasSchemas` / `HasActions` / `HasTable` |

Here `Has*` is reserved for traits, matching every other trait in the codebase (`HasName`, `HasComponents`, `HasRecords`, …), and the interface names the behaviour the host implements.
Porting code that mentions either name means checking which one it is first.

### Laravel rule vocabulary becomes Symfony constraint vocabulary

The validation payload changed ecosystems — Laravel rule strings and rule objects became Symfony `Constraint` instances — and the API vocabulary follows it: a migrating user's `->rules('required|max:255')` cannot work under any name, and a missing method is a louder, more searchable break than a kept name that greps clean and then type-errors.
Names derived from the same vocabulary translate systematically and carry no table rows of their own: `getValidationRules()` → `getValidationConstraints()` (field tier and `Schema` alike), `dehydrateValidationRules()` → `dehydrateValidationConstraints()`, `getLengthValidationRules()` → `getLengthValidationConstraints()`, `nestedRecursiveRule()` / `nestedRecursiveRules()` → `nestedRecursiveConstraint()` / `nestedRecursiveConstraints()`, `getNestedRecursiveValidationRules()` → `getNestedRecursiveValidationConstraints()`, the trait and contract name `HasNestedRecursiveValidationRules` → `HasNestedRecursiveValidationConstraints`, and `getInValidationRule()` / `getInValidationRuleValues()` → `getInValidationConstraint()` / `getInValidationConstraintValues()`.
Names that are not rule vocabulary keep Filament's spelling: `required()`, `nullable()`, `validationMessages()` (the method name — its *keys* are re-vocabularied, row above), `validate()`, `in()`, `notIn()`, `hasInValidationOnMultipleValues()`.

The **column** tier reaches the same answer from Filament's tables-tier `Columns\Concerns\CanBeValidated`.
`rules()` becomes `constraint()` / `constraints()` — the same pair the forms row above names, reached from a setter that takes only the array form — and `getRules()` becomes `getValidationConstraints()` under the systematic translation listed at the top of this section.
Two members have **no** Sylament counterpart at all, rather than a renamed one.
`validationAttribute()` and its `getValidationAttribute()` name the attribute a Laravel message interpolates into `:attribute`; a Symfony violation message carries no such placeholder, so there is nothing for the setter to fill and neither is ported.
And Filament's implicit `'nullable'`, appended to every column rule list that lacks `'required'`, has no constraint to become: nullability here is the **absence** of a presence constraint, so the port seeds one only when the record's declared write-side type refuses `null` ([has-columns.md](tables/has-columns.md#a-record-type-that-rejects-null-seeds-a-presence-constraint)).
Both omissions follow this section's own rule — a missing method reads louder than a kept name that means something else.

The message keys move with the payload.
A rule name was the only handle Laravel gave a message, so Filament keys `validationMessages()` by it; Symfony puts the message on the constraint object, so the constraint class is the handle and the option names are its own properties.
`Length`'s three keys (`size`, `max`, `min`) collapse onto one class with an option map, and `Toggle`'s `required` becomes `NotNull::class` rather than the `RequiredValue::class` every other field emits, because that is the constraint it emits.

### The model vocabulary collapses onto record

Filament itself is inconsistent here: root-tier `record()` aliases `model()` onto one last-write-wins property with divergent payload types, the component tier ships `model()` but no `record()`, and the rest of Filament — panels (`EditRecord`, `getRecord()`), tables, actions — speaks "record".
Sylament collapses onto Filament's own more prevalent name, which also removes a real trap (`->record($x)->model(Y::class)` looks additive but silently unbinds the instance) and converges with actions' record-only `InteractsWithRecord`.
"Model" is additionally an Eloquent concept-name with no Doctrine referent — the concept-name goes with its ecosystem, the constraint-vocabulary precedent.
Migration cost, accepted: `->model(...)` and `fn ($model)` break loudly (missing method / unresolvable parameter) instead of grepping clean.
Kept names: `record()`, `getRecord()`, and the record-side hooks Filament already spells with "record" (`getDefaultActionRecord()`, `getDefaultActionRecordTitle()`).
Full rationale and surface: [belongs-to-record.md](schemas/belongs-to-record.md).

### A name changes when its payload changes

A kept name whose argument or return value changes type is the failure a rename exists to prevent: it greps clean and then breaks at runtime.
So a parameter or method whose payload moves from a Laravel or Eloquent shape to a Doctrine or Symfony one is renamed, and named-argument-visible parameters count, because PHP checks a named argument's name.
- `unique()` / `exists()` take an entity class-string as `entity:` where Filament takes a table name as `table:`: DQL names classes, and the table is Doctrine's business.
- They take a mapped field as `property:` where Filament takes `column:`: a mapped field and its column part company the moment `#[ORM\Column(name:)]` is used.
  A migrating `unique(column: 'email')` therefore fails with PHP's `Unknown named parameter $column` error rather than probing nothing.
- Their `modifyQueryUsing:` closure receives a Doctrine `QueryBuilder` where Filament's `modifyRuleUsing:` receives a Laravel `Unique` / `Exists` rule object; `modifyQueryUsing` is also the name [`Select::relationship()`](forms/select.md) uses for the same job.
- `getGlobalSearchProviderClass()` answers a `class-string<GlobalSearchProvider>|null` where Filament's `getGlobalSearchProvider()` answers an `app()`-resolved instance, because Symfony's container resolves no arbitrary class-string; [`GlobalSearchProviderRegistry`](translations.md#names-with-no-filament-source) resolves it.
- `getTabsSchema()` answers a whole `Schema` a page partial renders where Filament's `getTabsContentComponent()` answers a `Tabs` component for `ListRecords::content()`, which no list page has here.
  [`getRelationManagersContentComponent()`](panels/has-relation-managers.md) is placed in a content schema and keeps Filament's name: the name follows the shape.

### Eloquent in a method name goes with its ecosystem

"Eloquent" is a Laravel concept-name with no Doctrine referent, so it goes on the same grounds as the [record vocabulary](#the-model-vocabulary-collapses-onto-record) and the [constraint vocabulary](#laravel-rule-vocabulary-becomes-symfony-constraint-vocabulary).
`getEloquentQuery()` becomes `getRecordQuery()`, still the single override point for the base query of every resource read path, and `getGlobalSearchEloquentQuery()` becomes `getGlobalSearchRecordQuery()`, which defaults to `getRecordQuery()`.
The [`Eloquent Builder` → `Doctrine QueryBuilder` idiom row](translations.md#idiom) translates what both return.

### A deprecated Filament member is not ported

Filament keeps `@deprecated` aliases and classes as one-line delegates or one-method overrides, and its deprecation notice names the spelling that replaces each.
The port ships the replacement and never the deprecated member, so a migrating call to it breaks loudly as a missing class or method, and the fix is the spelling the notice names.
- `BadgeColumn` and `BooleanColumn` are one `isBadge()` / `isBoolean()` override each; `TextColumn::badge()` and `IconColumn::boolean()` ship.
- `MultiSelectFilter` is one `isMultiple()` override; `SelectFilter::multiple()` ships.
- `TagsColumn` calls `badge()` in its `setUp()` and adds a deprecated `limit()` alias of `limitList()`; `TextColumn` ships both.
- The tables-tier `Columns\Concerns\CanBeCopied` only re-exports `Filament\Support\Concerns\CanBeCopied`, which ships as `Sylament\Support\Components\Concerns\CanBeCopied`.
- The resource statics `$label` / `$pluralLabel` (`Resource\Concerns\HasLabels`) give way to `$modelLabel` / `$pluralModelLabel`, which ship as `$recordLabel` / `$pluralRecordLabel`.
- The member aliases are the rest of the table's deprecated rows.

### Icon-alias values drop Filament's package prefix

Filament is inconsistent about the prefix: `SupportIconAlias`'s values are bare (`modal.close-button`, `section.collapse-button`) while every other package namespaces its own (`tables::`, `actions::`, `schema::`).
Sylament's values drop the `<package>::` segment throughout, so an app registering overrides under [`sylament.icons`](support/icon-manager.md) reads one scheme rather than two.
Only the prefix goes, so forms' values keep their `components.` segment and sit one level deeper than the other packages'.
`TablesIconAlias::EMPTY_STATE` is the one value that changes further: `tables::empty-state` becomes `table.empty-state`, gaining a component segment.
Every **constant** name is untouched, so a user reaching an alias through its constant, as both codebases do, never meets the difference.
`PanelsIconAlias::USER_MENU_TRIGGER` has no Filament counterpart at all and is [an addition](panels/layout-templates.md#user_menu_trigger-is-an-addition-not-a-rename), not a rename.

### A Livewire hook named by convention has no UX counterpart

Livewire calls `mount<TraitBasename>()` and `updated<Property>()` automatically, and Filament names hooks after that convention; UX LiveComponent has no such convention, so there is no hook name to preserve.
- `mountHasUnsavedDataChangesAlert()` fills `$savedDataHash` at mount; the port writes the `rememberData()` calls out at Filament's own positions instead.
- `updatedTableColumnSearches()` receives the dotted sub-key so Filament can forget one column's blank term; the port keeps a blank, and the rest of the method's work (reset the page, flush the records, persist, deselect) survives as `handleTableColumnSearchUpdates()`, called from the three places a term changes.
- `updatedTableGroupColumn()` is named for a `$tableGroupColumn` property Filament does not declare, so it never fires upstream; its page reset is inline in `groupTable()` here.

### A path or prop a same-named getter would shadow is renamed

Symfony's `PropertyAccessor` resolves a path segment through the same-named getter before the property.
- The mounted-action form state lives at `mountedActionsData.{i}`, because `mountedActions.{i}.data` resolves its first segment through `getMountedActions()` and cannot be read or written.
- The selection prop is `$selectedTableRecordKeys`, because `LiveComponentHydrator::dehydrate()` reads every `LiveProp` through the accessor, so a prop named `$selectedTableRecords` would run `getSelectedTableRecords()`'s full selection resolution on every render.
  `fieldName: 'selectedTableRecords'` keeps the wire and model name Filament's, so the checkbox markup, the Stimulus controller and every `->set('selectedTableRecords', …)` are unchanged.
  `$deselectedTableRecords` and `$isTrackingDeselectedTableRecords` keep Filament's names, since no same-named getter exists for either.

### A tag-name template prop narrows to what it selects

Filament passes a tag-name string that its template interpolates straight into markup, so any string a caller passes becomes an element name.
The port's prop carries only the choice the string encodes.
- `headingTag` (`'h3'`) on the filters template becomes `headingLevel`, an integer from which the template builds `h1`–`h6`.
- `labelTag` on `Field::wrapEmbeddedHtml()` is only ever `'label'` or `'div'`, and Filament's own template branches on `$labelTag === 'label'` to pick `for="…"` against `id="…-label"`; it becomes the boolean `labelAsHeading`.

### The sortable directive becomes a controller whose markers stay plain attributes

The [Alpine → Stimulus](translations.md#idiom) idiom takes `x-sortable` to the `sortable` controller in `packages/support/assets/src/controllers/sortable_controller.ts`, and the identifier follows the file.
Filament's directive is a globally registered Alpine plugin every element can carry; the controller reaches an application through its `assets/controllers.json`, which Symfony Flex fills from the package's `enabled: true` entry ([how a controller reaches an application](../internals/assets.md#how-does-a-controller-reach-a-consuming-application)), so an application whose file lacks the entry gets inert drag surfaces with no error.
- `x-sortable-item` and `x-sortable-handle` become `data-sortable-item` and `data-sortable-handle`, plain data attributes rather than Stimulus targets, because SortableJS is handed the attribute name as its `draggable` and `handle` selectors and the item attribute as its `dataIdAttr`.
  A target attribute would bake the controller identifier into the library's selectors and into the key it reports; the item marker carries the item's key as its value, as Filament's does.
- `x-sortable-group` becomes the declared `group` Stimulus value, typed and defaulted by Stimulus where Filament reads it with `el.getAttribute('x-sortable-group')`, so its attribute name carries the controller identifier.
- `data-sortable-animation-duration` becomes the `animationDuration` Stimulus value, because a Stimulus value namespaces its attribute under the controller identifier; the default of `300` carries over.

### `getExtraFieldWrapperAttributeBag()` takes Filament's majority spelling

Thirteen of Filament's fifteen `getExtra…Bag()` getters are `…AttributeBag` (`getExtraAttributeBag()`, `getExtraInputAttributeBag()`, `getExtraCellAttributeBag()`, …), and only `getExtraFieldWrapperAttributesBag()` and its infolists twin `getExtraEntryWrapperAttributesBag()` pluralize.
The port singularizes the forms one, so a migrating user typing the form Filament uses everywhere else lands on the right name.

### `getExtraRecordLinkAttributeBag()` has no consumer

Filament's wrapper exists so a Blade row anchor can echo `{{ $getExtraRecordLinkAttributeBag($record) }}` and get a Laravel `ComponentAttributeBag` back.
The port's row and cell anchors spread `getExtraRecordLinkAttributes()` through [`attrs()`](support/component-attribute-bag.md), which builds the bag itself and merges it with the anchor's own attributes in one call.
Porting the getter would hand callers a second, unmergeable way to say the same thing.

### `alpineClickHandler()` has no expression language to hand a string to

Filament's `alpineClickHandler()` hands an action a raw Alpine expression to run on click; its column manager wires its modal footer with `alpineClickHandler('applyTableColumnManager')` and a `$dispatch(…); $wire.resetTableColumnManager()` pair.
Here a click is either a Stimulus descriptor the template composes (`stimulus_action('live', 'action', parameters: {action: 'resetTableColumnManager'})`) or a live action the action's own `action()` names, both structured where Filament's is interpolated markup, the same call [`removeLivewireClickHandler()`](tables/indicator.md#removal-is-structured-not-a-click-handler-string) got.
The column manager's chrome is the table partial's rather than the trigger action's modal, so it needs neither ([column-manager.md](tables/column-manager.md#the-trigger-drops-vendors-modal-footer-machinery-because-the-chrome-is-the-partials)).

### The error-list classes drop the wrapper segment Filament renders nowhere

`fi-fo-field-wrp-error-list` and `fi-fo-field-wrp-error-message` are the only names in Filament's vendor tree containing `fi-fo-field-wrp`; no element carries `fi-fo-field-wrp` itself, because the field wrapper's root is `fi-fo-field`.
Porting the segment would name an element neither codebase renders, so the port's classes are `sy-fo-field-error-list` and `sy-fo-field-error-message`.
Everything else is Filament's, names and rule bodies alike, and both classes are [public hook surface](../internals/styling.md#why-are-the-emitted-classes-public).

### `hiddenOn()` takes host class-strings, so its parameter is `$hosts`

Filament's three tables `CanBeHidden` traits (column, filter, summarizer) declare `hiddenOn(string | array $livewireComponents)` and `visibleOn(string | array $livewireComponents)`.
The port's take `string|array $hosts`, because the argument is a list of host class-strings matched with `instanceof`, which is what the port's own [`hiddenOn()`](tables/column.md#surface) prose calls them; the [Livewire → UX idiom](translations.md#idiom) would have given `$liveComponents`.
A named-argument call such as `hiddenOn(livewireComponents: …)` breaks loudly.
