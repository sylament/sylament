<?php

declare(strict_types=1);

namespace Sylament\Forms\Components;

use BackedEnum;
use Closure;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use Stringable;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Forms\Components\Concerns\CanAllowHtml;
use Sylament\Forms\Components\Concerns\CanBeNative;
use Sylament\Forms\Components\Concerns\CanBePreloaded;
use Sylament\Forms\Components\Concerns\CanBeSearchable;
use Sylament\Forms\Components\Concerns\CanDisableOptions;
use Sylament\Forms\Components\Concerns\CanLimitItemsLength;
use Sylament\Forms\Components\Concerns\CanSelectPlaceholder;
use Sylament\Forms\Components\Concerns\HasAffixes;
use Sylament\Forms\Components\Concerns\HasExtraInputAttributes;
use Sylament\Forms\Components\Concerns\HasLoadingMessage;
use Sylament\Forms\Components\Concerns\HasOptions;
use Sylament\Forms\Components\Concerns\HasRelationship;
use Sylament\Forms\Components\Contracts\HasNestedRecursiveValidationConstraints;
use Sylament\Forms\Twig\FormsIconAlias;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Query\SearchConstraint;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Casts\BooleanStateCast;
use Sylament\Schemas\State\Casts\Contracts\StateCast;
use Sylament\Schemas\State\Casts\EnumArrayStateCast;
use Sylament\Schemas\State\Casts\EnumStateCast;
use Sylament\Schemas\State\Casts\OptionsArrayStateCast;
use Sylament\Schemas\State\Casts\OptionStateCast;
use Sylament\Support\Components\Attributes\ExposedLiveComponentMethod;
use Sylament\Support\Icons\IconManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Contracts\Service\Attribute\Required;
use Twig\Markup;
use TypeError;

use function Sylament\Support\blank;
use function Sylament\Support\filled;
use function Sylament\Support\wrap;

class Select extends Field implements Contracts\CanDisableOptions, Contracts\HasAffixes, HasNestedRecursiveValidationConstraints
{
    use CanAllowHtml;
    use CanBeNative;
    use CanBePreloaded;
    use CanBeSearchable;
    use CanDisableOptions;
    use CanLimitItemsLength;
    use CanSelectPlaceholder;
    use Concerns\HasNestedRecursiveValidationConstraints;
    use HasAffixes;
    use HasExtraInputAttributes;
    use HasLoadingMessage;
    use HasOptions;
    use HasRelationship {
        relationship as traitRelationship;
    }

    protected string|Closure|null $template = '@SylamentForms/partials/Select/index.html.twig';

    protected bool|Closure $isMultiple = false;

    protected ?Closure $getOptionLabelUsing = null;

    protected ?Closure $getOptionLabelsUsing = null;

    protected ?Closure $getSearchResultsUsing = null;

    protected ?Closure $getSelectedRecordUsing = null;

    protected ?Closure $transformOptionsForJsUsing = null;

    /**
     * @var list<string>|null
     */
    protected ?array $searchColumns = null;

    protected string|Closure|null $maxItemsMessage = null;

    protected string|Closure|null $position = null;

    protected int|Closure $optionsLimit = 50;

    protected bool|Closure|null $isSearchForcedCaseInsensitive = null;

    protected bool|Closure $canOptionLabelsWrap = true;

    protected bool|Closure $isReorderable = false;

    protected bool|Closure|null $hasDynamicOptions = null;

    /**
     * @var array<Component|Action|ActionGroup>|Closure|null
     */
    protected array|Closure|null $createOptionActionForm = null;

    protected ?Closure $createOptionUsing = null;

    protected string|Markup|Closure|null $createOptionModalHeading = null;

    protected string|Markup|Closure|null $editOptionModalHeading = null;

    protected ?Closure $modifyCreateOptionActionUsing = null;

    protected ?Closure $modifyManageOptionActionsUsing = null;

    /**
     * @var array<Component|Action|ActionGroup>|Closure|null
     */
    protected array|Closure|null $editOptionActionForm = null;

    protected ?Closure $fillEditOptionActionFormUsing = null;

    protected ?Closure $updateOptionUsing = null;

    protected ?Closure $modifyEditOptionActionUsing = null;

    protected ?object $cachedSelectedRecord = null;

    private mixed $cachedSelectedRecordState = null;

    private ?IconManager $iconManager = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transformOptionsForJsUsing(static function (Select $component, array $options): array {
            $transformed = [];

            /** @var array<string|int, string|Markup|array<string|int, string|Markup>> $options */
            foreach ($options as $value => $label) {
                $transformed[] = is_array($label)
                    ? ['label' => $value, 'options' => $component->transformOptionsForJs($label)]
                    : ['label' => (string) $label, 'value' => (string) $value, 'isDisabled' => $component->isOptionDisabled($value, $label)];
            }

            return $transformed;
        });

        $this->placeholder('Select an option');

        $this->suffixActions([
            static fn (Select $component): ?Action => $component->getCreateOptionAction(),
            static fn (Select $component): ?Action => $component->getEditOptionAction(),
        ]);
    }

    #[Required]
    public function setIconManager(IconManager $iconManager): void
    {
        $this->iconManager = $iconManager;
    }

    public function boolean(?string $trueLabel = null, ?string $falseLabel = null, ?string $placeholder = null): static
    {
        $this->options([
            1 => $trueLabel ?? 'Yes',
            0 => $falseLabel ?? 'No',
        ]);

        $this->placeholder($placeholder ?? '-');

        $this->stateCast(new BooleanStateCast(isStoredAsInt: true));

        return $this;
    }

    public function multiple(bool|Closure $condition = true): static
    {
        $this->isMultiple = $condition;

        return $this;
    }

    public function isMultiple(): bool
    {
        return (bool) $this->evaluate($this->isMultiple);
    }

    /**
     * @param bool|list<string>|Closure|null $condition
     */
    public function searchable(bool|array|Closure|null $condition = true): static
    {
        if (is_array($condition)) {
            $this->isSearchable = true;
            $this->searchColumns = $condition;
        } else {
            $this->isSearchable = $condition;
            $this->searchColumns = null;
        }

        return $this;
    }

    public function isSearchable(): bool
    {
        return $this->evaluate($this->isSearchable) ?? $this->isMultiple();
    }

    /**
     * @return list<string>|null
     */
    public function getSearchColumns(): ?array
    {
        $columns = $this->searchColumns;

        if ($this->hasRelationship() && filled($titleAttribute = $this->getRelationshipTitleAttribute())) {
            $columns ??= [$titleAttribute];
        }

        return $columns;
    }

    public function forceSearchCaseInsensitive(bool|Closure|null $condition = true): static
    {
        $this->isSearchForcedCaseInsensitive = $condition;

        return $this;
    }

    public function isSearchForcedCaseInsensitive(): ?bool
    {
        return $this->evaluate($this->isSearchForcedCaseInsensitive);
    }

    public function getSearchResultsUsing(?Closure $callback): static
    {
        $this->getSearchResultsUsing = $callback;

        return $this;
    }

    public function getOptionLabelUsing(?Closure $callback): static
    {
        $this->getOptionLabelUsing = $callback;

        return $this;
    }

    public function getOptionLabelsUsing(?Closure $callback): static
    {
        $this->getOptionLabelsUsing = $callback;

        return $this;
    }

    public function getSelectedRecordUsing(?Closure $callback): static
    {
        $this->getSelectedRecordUsing = $callback;

        return $this;
    }

    public function transformOptionsForJsUsing(?Closure $callback): static
    {
        $this->transformOptionsForJsUsing = $callback;

        return $this;
    }

    public function optionsLimit(int|Closure $limit): static
    {
        $this->optionsLimit = $limit;

        return $this;
    }

    public function getOptionsLimit(): int
    {
        return $this->evaluate($this->optionsLimit);
    }

    public function maxItemsMessage(string|Closure|null $message): static
    {
        $this->maxItemsMessage = $message;

        return $this;
    }

    public function getMaxItemsMessage(): string
    {
        return $this->evaluate($this->maxItemsMessage)
            ?? str_replace(':count', (string) $this->getMaxItems(), 'Only :count can be selected.');
    }

    public function position(string|Closure|null $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getPosition(): ?string
    {
        return $this->evaluate($this->position);
    }

    public function wrapOptionLabels(bool|Closure $condition = true): static
    {
        $this->canOptionLabelsWrap = $condition;

        return $this;
    }

    public function canOptionLabelsWrap(): bool
    {
        return (bool) $this->evaluate($this->canOptionLabelsWrap);
    }

    public function reorderable(bool|Closure $condition = true): static
    {
        $this->isReorderable = $condition;

        return $this;
    }

    public function isReorderable(): bool
    {
        return (bool) $this->evaluate($this->isReorderable);
    }

    public function dynamicOptions(bool|Closure|null $condition = true): static
    {
        $this->hasDynamicOptions = $condition;

        return $this;
    }

    public function hasDynamicOptions(): bool
    {
        if (($condition = $this->evaluate($this->hasDynamicOptions)) !== null) {
            return $condition;
        }

        if ($this->hasDynamicDisabledOptions()) {
            return true;
        }

        if ($this->hasRelationship()) {
            return (! $this->isSearchable()) || $this->isPreloaded();
        }

        if ($this->isPreloaded()) {
            return true;
        }

        return $this->options instanceof Closure;
    }

    public function hasDynamicSearchResults(): bool
    {
        if ($this->hasRelationship() && blank($this->getSearchColumns())) {
            return false;
        }

        return $this->getSearchResultsUsing instanceof Closure;
    }

    public function hasInitialNoOptionsMessage(): bool
    {
        if ($this->hasRelationship()) {
            return (! $this->isSearchable()) || $this->isPreloaded();
        }

        if ($this->isPreloaded()) {
            return true;
        }

        return ! $this->hasDynamicSearchResults();
    }

    /**
     * Beyond the association wiring the shared trait installs, a select binds the
     * search, label and selected-record arms of the association and the three
     * Doctrine defaults its option actions need: creating a target row, filling
     * the edit modal from the selected one, and flushing an update. They are
     * installed unconditionally, so a `createOptionUsing()` — or a
     * `getOptionLabelUsing()` — of your own has to follow `relationship()` to
     * survive.
     */
    public function relationship(string|Closure|null $name = null, string|Closure|null $titleAttribute = null, ?Closure $modifyQueryUsing = null): static
    {
        $this->traitRelationship($name, $titleAttribute, $modifyQueryUsing);

        $this->getSearchResultsUsing(static fn (Select $component, ?string $search): array => $component->getSearchResultsFromRelationship($search));

        $this->getOptionLabelUsing(static fn (Select $component): string|Markup|null => $component->getOptionLabelFromRelationship());

        $this->getSelectedRecordUsing(static function (Select $component, mixed $state): ?object {
            if (! is_int($state) && ! is_string($state)) {
                return null;
            }

            $record = $component->createScopedOptionQuery()
                ->andWhere("record.{$component->getRelationshipIdentifierFieldName()} = :sylament_selected")
                ->setParameter('sylament_selected', $state)
                ->getQuery()
                ->getOneOrNullResult();

            return is_object($record) ? $record : null;
        });

        $this->getOptionLabelsUsing(static fn (Select $component, array $values): array => $component->getOptionLabelsFromRelationship($values));

        $this->createOptionUsing(static function (Select $component, Schema $schema): string {
            $reader = $component->getAssociationReader();
            $record = $schema->getData();

            $reader->persist($record);
            $reader->flush();

            $component->forgetCachedRelationshipOptions();

            return $reader->getRecordKey($record);
        });

        $this->fillEditOptionActionFormUsing(static fn (Select $component): ?object => $component->getSelectedRecord());

        $this->updateOptionUsing(static function (Select $component, Schema $schema): void {
            $schema->getData();

            $component->getAssociationReader()->flush();
            $component->forgetCachedRelationshipOptions();
        });

        return $this;
    }

    /**
     * The option map a searchable select renders: nothing until the user types,
     * unless `preload()` asks for the (limited) enumeration up front. A limit the
     * query scope set wins over `optionsLimit()` and is written back to it; the
     * field's own limit applies only to a searchable select with search columns,
     * as Filament's does. Rows are ordered by the title attribute when the scope
     * declared no ordering — on the title-attribute path only; a record-label
     * callback labels rows in the order the database returns them.
     *
     * @return array<string|int, string|Markup>
     */
    public function getOptionsFromRelationship(): array
    {
        if ($this->isSearchable() && ! $this->isPreloaded()) {
            return [];
        }

        if (!$this->modifyRelationshipQueryUsing instanceof Closure && $this->cachedRelationshipOptions !== null) {
            return $this->cachedRelationshipOptions;
        }

        $query = $this->createScopedOptionQuery();

        $this->limitOptionQuery($query, shouldLimit: $this->isSearchable() && filled($this->getSearchColumns()));
        $this->orderOptionQueryByTitleAttribute($query);

        /** @var list<object> $records */
        $records = $query->getQuery()->getResult();

        $options = $this->getOptionsFromRecords($records);

        if (!$this->modifyRelationshipQueryUsing instanceof Closure) {
            $this->cachedRelationshipOptions = $options;
        }

        return $options;
    }

    /**
     * The rows whose search columns match the term, as an option map: a `LIKE`
     * per column, `OR`ed, over the scoped query, limited by the scope's own limit
     * or `optionsLimit()`, and ordered by the title attribute unless the scope
     * ordered already. Search columns are DQL field paths on the target entity
     * (`name`, or `address.city` into an embeddable); a path that hops an
     * association, or names no mapped field, is refused up front.
     *
     * @return array<string|int, string|Markup>
     *
     * @throws LogicException when a search column is not a mapped field of the
     *                        target entity
     */
    public function getSearchResultsFromRelationship(?string $search): array
    {
        $columns = $this->getSearchColumns();

        if (blank($columns)) {
            return [];
        }

        $query = $this->createScopedOptionQuery($search);
        $isForcedCaseInsensitive = $this->isSearchForcedCaseInsensitive();
        $fieldNames = $this->getRelationshipFieldNames($query);

        $query->andWhere($query->expr()->orX(...array_map(function (string $column) use ($query, $search, $isForcedCaseInsensitive, $fieldNames): Comparison {
            if (! in_array($column, $fieldNames, true)) {
                throw new LogicException(sprintf(
                    'The [%s] field searches relationship("%s") by [%s], which is not a mapped field of %s. A search column must be a field the target entity maps (or an embeddable\'s, as address.city) — searching through an association or a virtual property is not supported.',
                    (string) $this->getName(),
                    $this->getRelationshipNameOrFail(),
                    $column,
                    $this->getRelationshipTarget()->targetClass,
                ));
            }

            return SearchConstraint::like($query, "record.{$column}", (string) $search, $isForcedCaseInsensitive);
        }, $columns)));

        $this->limitOptionQuery($query);
        $this->orderOptionQueryByTitleAttribute($query);

        /** @var list<object> $records */
        $records = $query->getQuery()->getResult();

        return $this->getOptionsFromRecords($records);
    }

    /**
     * The selected record's label — through the record-label callback when one
     * is declared, else its title attribute — or null when the selection names
     * no row the option query admits.
     */
    public function getOptionLabelFromRelationship(): string|Markup|null
    {
        $record = $this->getSelectedRecord();

        if ($record === null) {
            return null;
        }

        return $this->resolveOptionLabelFromRecord($record);
    }

    /**
     * The labels of the given identifiers, keyed by identifier, for every row
     * the option query admits — an identifier outside its scope is simply absent.
     *
     * @param array<mixed> $values
     *
     * @return array<string|int, string|Markup>
     */
    public function getOptionLabelsFromRelationship(array $values): array
    {
        if ($values === []) {
            return [];
        }

        $query = $this->createScopedOptionQuery();

        $query
            ->andWhere("record.{$this->getRelationshipIdentifierFieldName()} IN (:sylament_values)")
            ->setParameter('sylament_values', array_values($values));

        /** @var list<object> $records */
        $records = $query->getQuery()->getResult();

        return $this->getOptionsFromRecords($records);
    }

    /**
     * @return array<string|int, string|Markup|array<string|int, string|Markup>>
     */
    public function getSearchResults(string $search): array
    {
        if (! $this->getSearchResultsUsing instanceof Closure) {
            return [];
        }

        $results = $this->requireArrayFromClosure($this->evaluate($this->getSearchResultsUsing, [
            'query' => $search,
            'search' => $search,
            'searchQuery' => $search,
        ]), 'getSearchResultsUsing');

        /** @var array<string|int, string|Markup|array<string|int, string|Markup>> $results */
        return $results;
    }

    /**
     * @return array<array<string, mixed>>
     */
    #[ExposedLiveComponentMethod]
    public function getSearchResultsForJs(string $search): array
    {
        return $this->transformOptionsForJs($this->getSearchResults($search));
    }

    /**
     * @return array<array<string, mixed>>
     */
    #[ExposedLiveComponentMethod]
    public function getOptionsForJs(): array
    {
        return $this->transformOptionsForJs($this->getOptions());
    }

    /**
     * @return array<array<string, mixed>>
     */
    #[ExposedLiveComponentMethod]
    public function getOptionLabelsForJs(): array
    {
        return $this->transformOptionsForJs($this->getOptionLabels());
    }

    /**
     * The option map in the shape the JS control speaks — `{label, value,
     * isDisabled}` per option, `{label, options}` per group by default.
     *
     * @param array<string|int, string|Markup|array<string|int, string|Markup>> $options
     *
     * @return array<array<string, mixed>>
     */
    protected function transformOptionsForJs(array $options): array
    {
        if ($options === []) {
            return [];
        }

        if (! $this->transformOptionsForJsUsing instanceof Closure) {
            throw new LogicException(sprintf(
                'The [%s] select has no transformOptionsForJsUsing() closure, so its options cannot reach the JS control. Leave the default installed, or pass a closure rather than null.',
                (string) $this->getStatePath(),
            ));
        }

        $transformed = $this->requireArrayFromClosure($this->evaluate($this->transformOptionsForJsUsing, [
            'options' => $options,
        ]), 'transformOptionsForJsUsing');

        /** @var array<array<string, mixed>> $transformed */
        return $transformed;
    }

    /**
     * Ask the JS control to re-read the selected option's label — the two option
     * actions call it once their write is flushed.
     */
    public function refreshSelectedOptionLabel(): void
    {
        $this->getLiveComponent()->dispatchBrowserEvent('sylament:select-refresh-selected-option-label', [
            'statePath' => $this->getStatePath(),
        ]);
    }

    public function createOptionAction(?Closure $callback): static
    {
        $this->modifyCreateOptionActionUsing = $callback;

        return $this;
    }

    /**
     * One closure both option actions run through, before the modifier each of
     * them carries of its own.
     */
    public function manageOptionActions(?Closure $callback): static
    {
        $this->modifyManageOptionActionsUsing = $callback;

        return $this;
    }

    /**
     * @param array<Component|Action|ActionGroup>|Closure|null $schema
     */
    public function manageOptionForm(array|Closure|null $schema): static
    {
        $this->createOptionForm($schema);
        $this->editOptionForm($schema);

        return $this;
    }

    /**
     * @param array<Component|Action|ActionGroup>|Closure|null $schema
     */
    public function createOptionForm(array|Closure|null $schema): static
    {
        $this->createOptionActionForm = $schema;

        return $this;
    }

    public function createOptionUsing(?Closure $callback): static
    {
        $this->createOptionUsing = $callback;

        return $this;
    }

    public function getCreateOptionUsing(): ?Closure
    {
        return $this->createOptionUsing;
    }

    public function getCreateOptionActionName(): string
    {
        return 'createOption';
    }

    public function getCreateOptionAction(): ?Action
    {
        if ($this->isDisabled()) {
            return null;
        }

        if (! $this->hasCreateOptionActionFormSchema()) {
            return null;
        }

        $action = Action::make($this->getCreateOptionActionName())
            ->label('Create')
            ->schema(static fn (Select $component, Schema $schema): array|Schema|null => $component->getCreateOptionActionForm(
                $schema->record($component->hasRelationship()
                    ? $component->getRelationshipTarget()->targetClass
                    : ($component->getRecord() ?? $component->getRecordClass())),
            ))
            ->action(static function (Action $action, array $arguments, Select $component, array $data, Schema $schema): void {
                $createOptionUsing = $component->getCreateOptionUsing() ?? throw new LogicException(sprintf(
                    'The [%s] select declares a createOptionForm() but no createOptionUsing() closure, so a submitted option would have nowhere to be created. Call createOptionUsing(), or declare relationship(), which installs a Doctrine default.',
                    (string) $component->getStatePath(),
                ));

                $createdOptionKey = $component->evaluate($createOptionUsing, [
                    'data' => $data,
                    'form' => $schema,
                    'schema' => $schema,
                ]);

                $component->state($component->isMultiple()
                    ? [...wrap($component->getState()), $createdOptionKey]
                    : $createdOptionKey);

                $component->callAfterStateUpdatedHooks();
                $component->forgetCachedRelationshipOptions();

                $component->refreshSelectedOptionLabel();

                if (! ($arguments['another'] ?? false)) {
                    return;
                }

                $action->callAfter();

                $schema->fill();

                $action->halt();
            })
            ->color('gray')
            ->icon($this->resolveOptionActionIcon(FormsIconAlias::COMPONENTS_SELECT_ACTIONS_CREATE_OPTION, 'heroicons:plus-20-solid'))
            ->iconButton()
            ->modalHeading($this->getCreateOptionModalHeading() ?? 'Create')
            ->modalSubmitActionLabel('Create')
            ->extraModalFooterActions(static fn (Action $action, Select $component): array => $component->isMultiple() ? [
                $action->makeModalSubmitAction('createAnother', arguments: ['another' => true])
                    ->label('Create & create another'),
            ] : []);

        return $this->applyOptionActionModifiers($action, $this->modifyCreateOptionActionUsing);
    }

    public function createOptionModalHeading(string|Markup|Closure|null $heading): static
    {
        $this->createOptionModalHeading = $heading;

        return $this;
    }

    public function editOptionModalHeading(string|Markup|Closure|null $heading): static
    {
        $this->editOptionModalHeading = $heading;

        return $this;
    }

    public function editOptionAction(?Closure $callback): static
    {
        $this->modifyEditOptionActionUsing = $callback;

        return $this;
    }

    /**
     * @return array<Component|Action|ActionGroup>|Schema|null
     */
    public function getCreateOptionActionForm(Schema $schema): array|Schema|null
    {
        return $this->evaluate($this->createOptionActionForm, ['form' => $schema, 'schema' => $schema]);
    }

    public function hasCreateOptionActionFormSchema(): bool
    {
        return (bool) $this->createOptionActionForm;
    }

    /**
     * @return array<Component|Action|ActionGroup>|Schema|null
     */
    public function getEditOptionActionForm(Schema $schema): array|Schema|null
    {
        return $this->evaluate($this->editOptionActionForm, ['form' => $schema, 'schema' => $schema]);
    }

    public function hasEditOptionActionFormSchema(): bool
    {
        return (bool) $this->editOptionActionForm;
    }

    /**
     * @param array<Component|Action|ActionGroup>|Closure|null $schema
     */
    public function editOptionForm(array|Closure|null $schema): static
    {
        $this->editOptionActionForm = $schema;
        $this->live();

        return $this;
    }

    public function updateOptionUsing(?Closure $callback): static
    {
        $this->updateOptionUsing = $callback;

        return $this;
    }

    public function getUpdateOptionUsing(): ?Closure
    {
        return $this->updateOptionUsing;
    }

    public function getEditOptionActionName(): string
    {
        return 'editOption';
    }

    public function getEditOptionAction(): ?Action
    {
        if ($this->isDisabled()) {
            return null;
        }

        if ($this->isMultiple()) {
            return null;
        }

        if (! $this->hasEditOptionActionFormSchema()) {
            return null;
        }

        $action = Action::make($this->getEditOptionActionName())
            ->label('Edit')
            ->schema(static fn (Select $component, Schema $schema): array|Schema|null => $component->getEditOptionActionForm(
                $schema->record($component->getSelectedRecord()),
            ))
            ->fillForm(static fn (Select $component): object|array|null => $component->getEditOptionActionFormData())
            ->action(static function (Select $component, array $data, Schema $schema): void {
                $updateOptionUsing = $component->getUpdateOptionUsing() ?? throw new LogicException(sprintf(
                    'The [%s] select declares an editOptionForm() but no updateOptionUsing() closure, so a submitted edit would have nowhere to be written. Call updateOptionUsing(), or declare relationship(), which installs a Doctrine default.',
                    (string) $component->getStatePath(),
                ));

                $component->evaluate($updateOptionUsing, [
                    'data' => $data,
                    'form' => $schema,
                    'schema' => $schema,
                ]);

                $component->forgetCachedRelationshipOptions();

                $component->refreshSelectedOptionLabel();
            })
            ->color('gray')
            ->icon($this->resolveOptionActionIcon(FormsIconAlias::COMPONENTS_SELECT_ACTIONS_EDIT_OPTION, 'heroicons:pencil-square-20-solid'))
            ->iconButton()
            ->modalHeading($this->getEditOptionModalHeading() ?? 'Edit')
            ->modalSubmitActionLabel('Save')
            ->visible(fn (): bool => filled($this->getState()));

        return $this->applyOptionActionModifiers($action, $this->modifyEditOptionActionUsing);
    }

    /**
     * What the edit modal is filled from — an array of state, or the record the
     * schema reads its mapped state off.
     *
     * @return object|array<string, mixed>|null
     */
    public function getEditOptionActionFormData(): object|array|null
    {
        return $this->evaluate($this->fillEditOptionActionFormUsing);
    }

    public function fillEditOptionActionFormUsing(?Closure $callback): static
    {
        $this->fillEditOptionActionFormUsing = $callback;

        return $this;
    }

    public function getCreateOptionModalHeading(): string|Markup|null
    {
        return $this->evaluate($this->createOptionModalHeading);
    }

    public function getEditOptionModalHeading(): string|Markup|null
    {
        return $this->evaluate($this->editOptionModalHeading);
    }

    /**
     * The record the current selection stands for, as `getSelectedRecordUsing()`
     * resolves it — memoized on the field for as long as the selection stays the
     * same, null for a blank selection or when no resolver is declared.
     */
    public function getSelectedRecord(): ?object
    {
        $state = $this->getState();

        if (blank($state)) {
            return null;
        }

        if ($this->cachedSelectedRecord !== null && $this->cachedSelectedRecordState === $state) {
            return $this->cachedSelectedRecord;
        }

        $this->cachedSelectedRecordState = $state;

        return $this->cachedSelectedRecord = $this->requireSelectedRecord($this->evaluate($this->getSelectedRecordUsing));
    }

    #[ExposedLiveComponentMethod]
    public function getOptionLabel(bool $withDefault = true): string|Markup|null
    {
        return $this->resolveOptionLabel($withDefault);
    }

    /**
     * The selected option's label: off the option map — the one given, else the
     * field's own — or through `getOptionLabelUsing()`, which receives the state
     * lazily as `$value`. Without a match, the state itself when `$withDefault`,
     * else null — the answer validation probes.
     *
     * @param array<string|int, string|Markup|array<string|int, string|Markup>>|null $options
     */
    protected function resolveOptionLabel(bool $withDefault = true, ?array $options = null): string|Markup|null
    {
        if (! $this->getOptionLabelUsing instanceof Closure) {
            $state = $this->getOptionComparisonState();

            if (! is_string($state) && ! is_int($state)) {
                return null;
            }

            $label = $this->findOptionLabel($options ?? $this->getOptions(), $state);

            if ($label !== null) {
                return $label;
            }

            return $withDefault ? (string) $state : null;
        }

        $state = null;

        $label = $this->toOptionLabel($this->evaluate($this->getOptionLabelUsing, [
            'value' => function () use (&$state): mixed {
                return $state = $this->getState();
            },
        ]));

        if ($withDefault) {
            $label ??= $this->toOptionLabel($state ?? $this->getState());
        }

        return $label;
    }

    /**
     * The labels of a multiple selection keyed by value: off the option map — the
     * one given, else the field's own — or through `getOptionLabelsUsing()`,
     * which receives the state lazily as `$values`. A value without a label
     * becomes its own label when `$withDefaults`, else drops out — the answer
     * validation probes.
     *
     * @param array<string|int, string|Markup|array<string|int, string|Markup>>|null $options
     *
     * @return array<string|int, string|Markup>
     */
    public function getOptionLabels(bool $withDefaults = true, ?array $options = null): array
    {
        $labels = [];

        if (! $this->getOptionLabelsUsing instanceof Closure) {
            $options ??= $this->getOptions();

            foreach (wrap($this->getOptionComparisonState()) as $value) {
                $label = $this->findOptionLabel($options, $value);

                if ($label !== null) {
                    $labels[$value] = $label;
                } elseif ($withDefaults) {
                    $labels[$value] = (string) $value;
                }
            }

            return $labels;
        }

        $resolved = $this->requireArrayFromClosure($this->evaluate($this->getOptionLabelsUsing, [
            'values' => fn (): array => wrap($this->getState()),
        ]), 'getOptionLabelsUsing');

        foreach ($resolved as $value => $label) {
            $label = $this->toOptionLabel($label);

            if (filled($label)) {
                $labels[$value] = $label;
            } elseif ($withDefaults) {
                $labels[$value] = (string) $value;
            }
        }

        return $labels;
    }

    /**
     * @return array<StateCast>
     */
    public function getDefaultStateCasts(): array
    {
        if ($this->hasCustomStateCasts() || filled($this->getEnum())) {
            return parent::getDefaultStateCasts();
        }

        if ($this->isMultiple()) {
            return [new OptionsArrayStateCast(isNullable: false)];
        }

        return [new OptionStateCast(isNullable: true)];
    }

    public function getEnumDefaultStateCast(): ?StateCast
    {
        $enum = $this->getEnum();

        if ($enum === null) {
            return null;
        }

        $this->assertBackedEnum($enum);

        return $this->isMultiple()
            ? new EnumArrayStateCast($enum)
            : new EnumStateCast($enum);
    }

    public function hasNullableBooleanState(): bool
    {
        return true;
    }

    /**
     * The one `getOptions()` read a render makes, and what both template branches
     * derive from it: the map itself, its JS shape, the selected values with
     * their labels — the JS control's initial state — and whether the native
     * control renders at all. Nothing forces the JS control unless the select is
     * searchable, multiple or renders HTML labels, and `native(false)` asks for it
     * outright.
     *
     * @return array<array-key, mixed>
     */
    public function getExtraTemplateData(): array
    {
        $options = $this->getOptions();
        $isNative = ! ($this->isSearchable() || $this->isMultiple() || $this->isHtmlAllowed()) && $this->isNative();

        return [
            'options' => $options,
            'optionsForJs' => $isNative ? [] : $this->transformOptionsForJs($options),
            'selectedOptions' => $isNative ? [] : $this->getSelectedOptions($options),
            'isNative' => $isNative,
        ];
    }

    public function hasInValidationOnMultipleValues(): bool
    {
        return $this->isMultiple();
    }

    /**
     * Validation runs against the raw state. A multiple select whose raw state is
     * not an array is failed by a type constraint alone — `Choice(multiple: true)`
     * throws on anything else — and its item bounds apply to every array, the
     * empty one included. Then, once the payload left anything there: a select
     * whose option map is the whole truth is guarded by a `Choice` over its
     * enabled options, and one whose map is not — a searchable relationship
     * select, or a select labelling values through a closure with no `options()`
     * — by probing the label of what was submitted, Filament's rule, where a
     * failed probe is a `Choice` over nothing.
     *
     * @return list<Constraint>
     */
    protected function buildValidationConstraints(): array
    {
        $constraints = parent::buildValidationConstraints();

        $rawState = $this->getRawState();
        $isMultiple = $this->isMultiple();

        if ($isMultiple) {
            if ($rawState !== null && ! is_array($rawState)) {
                $constraints[] = new Type('array');

                return $constraints;
            }

            $minItems = $this->getMinItems();
            $maxItems = $this->getMaxItems();

            if (($minItems !== null) || ($maxItems !== null)) {
                $constraints[] = new Count(
                    min: ($minItems === null) ? null : max(0, $minItems),
                    max: ($maxItems === null) ? null : max(0, $maxItems),
                );
            }
        }

        if (blank($rawState)) {
            return $constraints;
        }

        // A user `in()` list replaces the derived membership check, which is what
        // Filament's overrides do by returning the parent's list first — before the
        // options are ever consulted, so `in()` on an option-less field is valid.
        if ($this->getInValidationConstraintValues() !== null) {
            return $constraints;
        }

        if ($this->hasFullOptionMap()) {
            $constraints[] = new Choice(
                choices: $this->getEnabledOptionValues(),
                multiple: $isMultiple,
                strict: true,
            );

            return $constraints;
        }

        if ($this->isSubmittedOptionUnlabelled($isMultiple)) {
            $constraints[] = new Choice(
                choices: [],
                multiple: $isMultiple,
                strict: true,
            );
        }

        return $constraints;
    }

    /**
     * Whether `getOptions()` enumerates every value the field accepts — the map
     * of a static `options()` or an enum, or a relationship select that is not
     * searchable. A searchable relationship select's map is empty or limited by
     * design, so it is never the truth about a submitted value.
     */
    protected function hasFullOptionMap(): bool
    {
        if ($this->options === null && $this->getEnum() === null) {
            return false;
        }

        return !$this->hasRelationship() || !$this->isSearchable();
    }

    /**
     * The label probe over the submitted state: a value is legal when it resolves
     * a label and that option is not disabled — for a multiple selection, every
     * value must.
     *
     * @throws LogicException when the field has neither options nor the label
     *                        closure of its multiplicity to probe with
     */
    private function isSubmittedOptionUnlabelled(bool $isMultiple): bool
    {
        $hasNoOptions = $this->options === null && $this->getEnum() === null;

        if ($isMultiple) {
            if ($hasNoOptions && ! $this->getOptionLabelsUsing instanceof Closure) {
                throw new LogicException(
                    "Sylament failed to validate the [{$this->getStatePath()}] select because it has neither an options() nor a getOptionLabelsUsing() configuration. Call one of them so Sylament knows which values are valid.",
                );
            }

            $labels = $this->getOptionLabels(withDefaults: false);
            $state = wrap($this->getOptionComparisonState());

            if (count($state) > count($labels) || array_diff($state, array_keys($labels)) !== []) {
                return true;
            }

            if ($this->hasDisabledOptions()) {
                foreach ($labels as $value => $label) {
                    if ($this->isOptionDisabled($value, $label)) {
                        return true;
                    }
                }
            }

            return false;
        }

        if ($hasNoOptions && ! $this->getOptionLabelUsing instanceof Closure) {
            throw new LogicException(
                "Sylament failed to validate the [{$this->getStatePath()}] select because it has neither an options() nor a getOptionLabelUsing() configuration. Call one of them so Sylament knows which values are valid.",
            );
        }

        $label = $this->getOptionLabel(withDefault: false);

        if (blank($label)) {
            return true;
        }

        $state = $this->getOptionComparisonState();

        return $this->hasDisabledOptions() && (is_string($state) || is_int($state)) && $this->isOptionDisabled($state, $label);
    }

    /**
     * The selected values with their labels, in the JS control's `{value, label}`
     * shape — the single selection's one label, or the multiple selection's map.
     *
     * @param array<string|int, string|Markup|array<string|int, string|Markup>> $options
     *
     * @return list<array{value: string, label: string|Markup}>
     */
    private function getSelectedOptions(array $options): array
    {
        if (blank($this->getRawState())) {
            return [];
        }

        if ($this->isMultiple()) {
            $selected = [];

            foreach ($this->getOptionLabels(options: $options) as $value => $label) {
                $selected[] = ['value' => (string) $value, 'label' => $label];
            }

            return $selected;
        }

        $state = $this->getOptionComparisonState();
        $label = $this->resolveOptionLabel(options: $options);

        if ($label === null || (! is_string($state) && ! is_int($state))) {
            return [];
        }

        return [['value' => (string) $state, 'label' => $label]];
    }

    /**
     * The label an option map carries at a value, walking both the flat and the
     * grouped shape — an array-key lookup, as Filament's `$options[$state]` is,
     * so a numeric-string state finds the int key PHP canonicalizes it to.
     *
     * @param array<string|int, string|Markup|array<string|int, string|Markup>> $options
     */
    private function findOptionLabel(array $options, string|int $value): string|Markup|null
    {
        foreach ($options as $key => $label) {
            $group = is_array($label) ? $label : [$key => $label];

            if (array_key_exists($value, $group)) {
                return $group[$value];
            }
        }

        return null;
    }

    /**
     * What a label closure answered, as the text an option label is: `Markup`
     * as-is, a scalar or an enum's backing value as a string, anything else null.
     */
    private function toOptionLabel(mixed $label): string|Markup|null
    {
        if ($label instanceof Markup) {
            return $label;
        }

        if ($label instanceof BackedEnum) {
            $label = $label->value;
        }

        return (is_scalar($label) || $label instanceof Stringable) ? (string) $label : null;
    }

    /**
     * @return array<array-key, mixed>
     *
     * @throws LogicException when the closure answered something other than an
     *                        array
     */
    private function requireArrayFromClosure(mixed $result, string $setter): array
    {
        if (is_array($result)) {
            return $result;
        }

        throw new LogicException(sprintf(
            "The [%s] select's %s closure returned %s where an array was expected.",
            (string) $this->getStatePath(),
            $setter,
            get_debug_type($result),
        ));
    }

    /**
     * @throws LogicException when the resolver answered something other than a
     *                        record or null
     */
    private function requireSelectedRecord(mixed $record): ?object
    {
        if ($record === null || is_object($record)) {
            return $record;
        }

        throw new LogicException(sprintf(
            "The [%s] select's getSelectedRecordUsing closure returned %s where a record or null was expected.",
            (string) $this->getStatePath(),
            get_debug_type($record),
        ));
    }

    /**
     * A limit the query scope already set wins and is written back through
     * `optionsLimit()`, so the JS control's own cap agrees with it; otherwise the
     * field's limit applies when asked to.
     */
    private function limitOptionQuery(QueryBuilder $query, bool $shouldLimit = true): void
    {
        $limit = $query->getMaxResults();

        if ($limit !== null) {
            $this->optionsLimit($limit);

            return;
        }

        if ($shouldLimit) {
            $query->setMaxResults($this->getOptionsLimit());
        }
    }

    /**
     * Order by the title attribute unless the scope ordered already — and only
     * where the title attribute is a mapped field the query can order by; a
     * record-label callback, or a title read through an association or a
     * virtual property, leaves the database's order alone.
     */
    private function orderOptionQueryByTitleAttribute(QueryBuilder $query): void
    {
        if ($this->hasOptionLabelFromRecordUsingCallback() || $query->getDQLPart('orderBy') !== []) {
            return;
        }

        $titleAttribute = $this->getRelationshipTitleAttributeOrFail();

        if (in_array($titleAttribute, $this->getRelationshipFieldNames($query), true)) {
            $query->orderBy("record.{$titleAttribute}");
        }
    }

    /**
     * The mapped field names of the association's target — the DQL paths a
     * search or an ordering may name, embeddable fields spelled `address.city`.
     *
     * @return list<string>
     */
    private function getRelationshipFieldNames(QueryBuilder $query): array
    {
        return array_values($query->getEntityManager()->getClassMetadata($this->getRelationshipTarget()->targetClass)->getFieldNames());
    }

    private function getRelationshipIdentifierFieldName(): string
    {
        return $this->getAssociationReader()->getIdentifierFieldName($this->getRelationshipTarget()->targetClass);
    }

    /**
     * The manage-level modifier first, then the action's own — each replacing the
     * action only when it returns one, so a modifier that mutates in place and
     * returns nothing still applies.
     */
    private function applyOptionActionModifiers(Action $action, ?Closure $modifyActionUsing): Action
    {
        foreach ([$this->modifyManageOptionActionsUsing, $modifyActionUsing] as $callback) {
            if (!$callback instanceof Closure) {
                continue;
            }

            $action = $this->modifiedOptionAction($this->evaluate($callback, ['action' => $action]), $action);
        }

        return $action;
    }

    private function modifiedOptionAction(mixed $modifiedAction, Action $action): Action
    {
        if ($modifiedAction === null) {
            return $action;
        }

        if (! $modifiedAction instanceof Action) {
            throw new TypeError(sprintf('An option action modifier on %s must return an %s instance or null.', static::class, Action::class));
        }

        return $modifiedAction;
    }

    private function resolveOptionActionIcon(string $alias, string $fallback): string|BackedEnum|Markup
    {
        return ($this->iconManager ??= new IconManager())->resolve($alias) ?? $fallback;
    }
}
