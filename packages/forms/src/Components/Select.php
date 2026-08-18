<?php

declare(strict_types=1);

namespace Sylament\Forms\Components;

use BackedEnum;
use Closure;
use LogicException;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Forms\Components\Concerns\CanDisableOptions;
use Sylament\Forms\Components\Concerns\CanSelectPlaceholder;
use Sylament\Forms\Components\Concerns\HasAffixes;
use Sylament\Forms\Components\Concerns\HasExtraInputAttributes;
use Sylament\Forms\Components\Concerns\HasOptions;
use Sylament\Forms\Components\Concerns\HasRelationship;
use Sylament\Forms\Twig\FormsIconAlias;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Casts\BooleanStateCast;
use Sylament\Schemas\State\Casts\Contracts\StateCast;
use Sylament\Schemas\State\Casts\EnumArrayStateCast;
use Sylament\Schemas\State\Casts\EnumStateCast;
use Sylament\Schemas\State\Casts\OptionsArrayStateCast;
use Sylament\Schemas\State\Casts\OptionStateCast;
use Sylament\Support\Icons\IconManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Contracts\Service\Attribute\Required;
use Twig\Markup;
use TypeError;

use function Sylament\Support\blank;
use function Sylament\Support\filled;
use function Sylament\Support\wrap;

class Select extends Field implements Contracts\CanDisableOptions, Contracts\HasAffixes
{
    use CanDisableOptions;
    use CanSelectPlaceholder;
    use HasAffixes;
    use HasExtraInputAttributes;
    use HasOptions;
    use HasRelationship {
        relationship as traitRelationship;
    }

    protected string|Closure|null $template = '@SylamentForms/partials/Select/index.html.twig';

    protected bool|Closure $isMultiple = false;

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

    private ?IconManager $iconManager = null;

    protected function setUp(): void
    {
        parent::setUp();

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
     * Beyond the association wiring the shared trait installs, a select binds the
     * three Doctrine defaults its option actions need: creating a target row,
     * filling the edit modal from the selected one, and flushing an update. They
     * are installed unconditionally, so a `createOptionUsing()` of your own has to
     * follow `relationship()` to survive.
     */
    public function relationship(string|Closure|null $name = null, string|Closure|null $titleAttribute = null, ?Closure $modifyQueryUsing = null): static
    {
        $this->traitRelationship($name, $titleAttribute, $modifyQueryUsing);

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
     * The target entity the current selection stands for, as a managed reference
     * rather than a fetch — the edit modal reads its state off the instance, which
     * is what initializes it. Null for a select bound to no association, and for a
     * blank selection.
     */
    public function getSelectedRecord(): ?object
    {
        if ($this->cachedSelectedRecord !== null) {
            return $this->cachedSelectedRecord;
        }

        if (! $this->hasRelationship()) {
            return null;
        }

        $state = $this->getState();

        if (blank($state) || (! is_int($state) && ! is_string($state))) {
            return null;
        }

        return $this->cachedSelectedRecord = $this->getAssociationReader()->getReference(
            $this->getRelationshipTarget()->targetClass,
            $state,
        );
    }

    public function getOptionLabel(): string|Markup|null
    {
        $state = $this->getOptionComparisonState();

        if (! is_string($state) && ! is_int($state)) {
            return null;
        }

        foreach ($this->getOptions() as $value => $label) {
            $group = is_array($label) ? $label : [$value => $label];

            if (array_key_exists($state, $group)) {
                return $group[$state];
            }
        }

        return null;
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
     * Validation runs against the raw state, and `Choice(multiple: true)` is only
     * defined over an array — its validator throws on any other value rather than
     * failing it — so a multiple select whose raw state is not an array is failed
     * by a type constraint instead of being handed to the Choice.
     *
     * @return list<Constraint>
     */
    protected function buildValidationConstraints(): array
    {
        $constraints = parent::buildValidationConstraints();

        $rawState = $this->getRawState();

        if (blank($rawState)) {
            return $constraints;
        }

        if ($this->options === null && $this->getEnum() === null) {
            throw new LogicException(
                "Sylament failed to validate the [{$this->getStatePath()}] select because it has no options() configuration. Call options() so Sylament knows which values are valid.",
            );
        }

        $isMultiple = $this->isMultiple();

        if ($isMultiple && ! is_array($rawState)) {
            $constraints[] = new Type('array');

            return $constraints;
        }

        $constraints[] = new Choice(
            choices: $this->getEnabledOptionValues(),
            multiple: $isMultiple,
            strict: true,
        );

        return $constraints;
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
