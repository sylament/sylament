<?php

declare(strict_types=1);

namespace Sylament\Forms\Components;

use Closure;
use LogicException;
use Sylament\Actions\Action;
use Sylament\Forms\Components\Concerns\CanAllowHtml;
use Sylament\Forms\Components\Concerns\CanBeSearchable;
use Sylament\Forms\Components\Concerns\CanDisableOptions;
use Sylament\Forms\Components\Concerns\CanDisableOptionsWhenSelectedInSiblingRepeaterItems;
use Sylament\Forms\Components\Concerns\CanFixIndistinctState;
use Sylament\Forms\Components\Concerns\CanLimitItemsLength;
use Sylament\Forms\Components\Concerns\HasDescriptions;
use Sylament\Forms\Components\Concerns\HasExtraInputAttributes;
use Sylament\Forms\Components\Concerns\HasGridDirection;
use Sylament\Forms\Components\Concerns\HasOptions;
use Sylament\Forms\Components\Concerns\HasRelationship;
use Sylament\Forms\Components\Contracts\HasNestedRecursiveValidationConstraints;
use Sylament\Schemas\State\Casts\Contracts\StateCast;
use Sylament\Schemas\State\Casts\EnumArrayStateCast;
use Sylament\Schemas\State\Casts\OptionsArrayStateCast;
use Sylament\Support\Enums\Size;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Type;
use Twig\Markup;

use function Sylament\Support\blank;
use function Sylament\Support\filled;
use function Symfony\Component\String\u;

class CheckboxList extends Field implements Contracts\CanDisableOptions, HasNestedRecursiveValidationConstraints
{
    use CanAllowHtml;
    use CanBeSearchable;
    use CanDisableOptions;
    use CanDisableOptionsWhenSelectedInSiblingRepeaterItems;
    use CanFixIndistinctState;
    use CanLimitItemsLength;
    use Concerns\HasNestedRecursiveValidationConstraints;
    use HasDescriptions;
    use HasExtraInputAttributes;
    use HasGridDirection;
    use HasOptions;
    use HasRelationship {
        getOptionsFromRelationship as getTitleAttributeOptionsFromRelationship;
    }

    protected string|Closure|null $template = '@SylamentForms/partials/CheckboxList/index.html.twig';

    protected bool|Closure $isBulkToggleable = false;

    protected Action|Closure|null $modifySelectAllActionUsing = null;

    protected Action|Closure|null $modifyDeselectAllActionUsing = null;

    protected string|Markup|Closure|null $getOptionDescriptionFromRecordUsing = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->searchDebounce(0);

        $this->registerActions([
            fn (CheckboxList $component): Action => $component->getSelectAllAction(),
            fn (CheckboxList $component): Action => $component->getDeselectAllAction(),
        ]);
    }

    /**
     * A list always speaks a list, so the association it may be bound to has to be
     * a to-many one. Filament reads the same fact off the `BelongsToMany` return
     * type of its own `getRelationship()`; here it answers the multiplicity match
     * the shared relationship arm runs.
     */
    public function isMultiple(): bool
    {
        return true;
    }

    public function bulkToggleable(bool|Closure $condition = true): static
    {
        $this->isBulkToggleable = $condition;

        return $this;
    }

    public function isBulkToggleable(): bool
    {
        return (bool) $this->evaluate($this->isBulkToggleable);
    }

    public function getSelectAllAction(): Action
    {
        $action = Action::make($this->getSelectAllActionName())
            ->label('Select all')
            ->liveComponentClickHandlerEnabled(false)
            ->link()
            ->size(Size::Small);

        return $this->applyActionModifier($this->modifySelectAllActionUsing, $action);
    }

    public function selectAllAction(?Closure $callback): static
    {
        $this->modifySelectAllActionUsing = $callback;

        return $this;
    }

    public function getSelectAllActionName(): string
    {
        return 'selectAll';
    }

    public function getDeselectAllAction(): Action
    {
        $action = Action::make($this->getDeselectAllActionName())
            ->label('Deselect all')
            ->liveComponentClickHandlerEnabled(false)
            ->link()
            ->size(Size::Small);

        return $this->applyActionModifier($this->modifyDeselectAllActionUsing, $action);
    }

    public function deselectAllAction(?Closure $callback): static
    {
        $this->modifyDeselectAllActionUsing = $callback;

        return $this;
    }

    public function getDeselectAllActionName(): string
    {
        return 'deselectAll';
    }

    public function getOptionDescriptionFromRecordUsing(?Closure $callback): static
    {
        $this->getOptionDescriptionFromRecordUsing = $callback;
        $this->cachedRelationshipOptions = null;

        return $this;
    }

    public function hasOptionDescriptionFromRecordUsingCallback(): bool
    {
        return $this->getOptionDescriptionFromRecordUsing !== null;
    }

    public function getOptionDescriptionFromRecord(object $record): string|Markup|null
    {
        return $this->evaluate($this->getOptionDescriptionFromRecordUsing, [
            'record' => $record,
        ], [
            $record::class => $record,
        ]);
    }

    /**
     * The option map the relationship builds. A declared record-shaped label or
     * description callback takes the records themselves rather than a title
     * column, and installing the descriptions is a side effect of building the
     * labels — Filament's shape, because the two maps come off one query.
     *
     * @return array<string|int, string|Markup>
     */
    public function getOptionsFromRelationship(): array
    {
        if (! $this->hasOptionLabelFromRecordUsingCallback() && ! $this->hasOptionDescriptionFromRecordUsingCallback()) {
            return $this->getTitleAttributeOptionsFromRelationship();
        }

        if (!$this->modifyRelationshipQueryUsing instanceof Closure && $this->cachedRelationshipOptions !== null) {
            return $this->cachedRelationshipOptions;
        }

        $reader = $this->getAssociationReader();
        $records = $this->getRecordsFromRelationship();

        if ($this->hasOptionDescriptionFromRecordUsingCallback()) {
            $descriptions = [];

            foreach ($records as $related) {
                $description = $this->getOptionDescriptionFromRecord($related);

                if (filled($description)) {
                    $descriptions[$reader->getRecordKey($related)] = $description;
                }
            }

            $this->descriptions($descriptions);
        }

        if (! $this->hasOptionLabelFromRecordUsingCallback()) {
            return $this->getTitleAttributeOptionsFromRelationship();
        }

        $options = [];

        foreach ($records as $related) {
            $options[$reader->getRecordKey($related)] = $this->getOptionLabelFromRecord($related);
        }

        if (!$this->modifyRelationshipQueryUsing instanceof Closure) {
            $this->cachedRelationshipOptions = $options;
        }

        return $options;
    }

    /**
     * A relation-backed list labels itself by the association rather than by the
     * field name, so `CheckboxList::make('tagIds')->relationship('tags')` reads as
     * "Tags". An explicit `label()` still outranks it — this is the default the
     * base label chain falls through to.
     */
    protected function getDefaultLabel(): ?string
    {
        $relationshipName = $this->getRelationshipName();

        if (filled($relationshipName)) {
            return (string) u($relationshipName)->before('.')->kebab()->replace('-', ' ')->title();
        }

        return parent::getDefaultLabel();
    }

    public function getEnumDefaultStateCast(): ?StateCast
    {
        $enum = $this->getEnum();

        if ($enum === null) {
            return null;
        }

        $this->assertBackedEnum($enum);

        return new EnumArrayStateCast($enum);
    }

    /**
     * @return array<StateCast>
     */
    public function getDefaultStateCasts(): array
    {
        if ($this->hasCustomStateCasts() || filled($this->getEnum())) {
            return parent::getDefaultStateCasts();
        }

        return [new OptionsArrayStateCast()];
    }

    public function hasInValidationOnMultipleValues(): bool
    {
        return true;
    }

    /**
     * The list's three server-side guards, in the order a tampered payload meets
     * them: a non-array raw state is failed by type alone, because
     * `Choice(multiple: true)` is undefined over anything else and `Count` throws
     * on it; the item bounds apply to every array including the empty one, which
     * the blank short-circuit below would otherwise skip; and the membership check
     * runs once the payload left anything there, which is a question about the raw
     * state — a cast that drops every element still leaves a selection to reject.
     *
     * @return list<Constraint>
     */
    protected function buildValidationConstraints(): array
    {
        $constraints = parent::buildValidationConstraints();

        $rawState = $this->getRawState();

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

        if (blank($rawState)) {
            return $constraints;
        }

        // A user `in()` list replaces the derived membership check, which is what
        // Filament's overrides do by returning the parent's list first — before the
        // options are ever consulted, so `in()` on an option-less field is valid.
        if ($this->getInValidationConstraintValues() !== null) {
            return $constraints;
        }

        if ($this->options === null && $this->getEnum() === null) {
            throw new LogicException(
                "Sylament failed to validate the [{$this->getStatePath()}] checkbox list because it has no options() configuration. Call options() so Sylament knows which values are valid.",
            );
        }

        $constraints[] = new Choice(
            choices: $this->getEnabledOptionValues(),
            multiple: true,
            strict: true,
        );

        return $constraints;
    }

    /**
     * Run a declared `…Action()` modifier over a freshly built action, keeping the
     * built one when the modifier declares no replacement.
     */
    private function applyActionModifier(Action|Closure|null $callback, Action $action): Action
    {
        return $this->evaluate($callback, ['action' => $action]) ?? $action;
    }
}
