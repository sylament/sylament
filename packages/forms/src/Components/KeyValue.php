<?php

declare(strict_types=1);

namespace Sylament\Forms\Components;

use BackedEnum;
use Closure;
use LogicException;
use Sylament\Actions\Action;
use Sylament\Forms\Twig\FormsIconAlias;
use Sylament\Schemas\State\Casts\Contracts\StateCast;
use Sylament\Schemas\State\Casts\KeyValueStateCast;
use Sylament\Support\Components\Concerns\HasReorderAnimationDuration;
use Sylament\Support\Enums\Size;
use Sylament\Support\Icons\IconManager;
use Symfony\Contracts\Service\Attribute\Required;
use Twig\Markup;

class KeyValue extends Field
{
    use HasReorderAnimationDuration;

    private const array BLANK_ROW = ['key' => '', 'value' => ''];

    protected string|Closure|null $template = '@SylamentForms/partials/KeyValue/index.html.twig';

    protected string|Closure|null $addActionLabel = null;

    protected string|Closure|null $deleteActionLabel = null;

    protected string|Closure|null $reorderActionLabel = null;

    protected bool|Closure $isAddable = true;

    protected bool|Closure $isDeletable = true;

    protected bool|Closure $canEditKeys = true;

    protected bool|Closure $canEditValues = true;

    protected bool|Closure $isReorderable = false;

    protected string|Closure|null $keyLabel = null;

    protected string|Closure|null $valueLabel = null;

    protected string|Closure|null $keyPlaceholder = null;

    protected string|Closure|null $valuePlaceholder = null;

    protected Action|Closure|null $modifyAddActionUsing = null;

    protected Action|Closure|null $modifyDeleteActionUsing = null;

    protected Action|Closure|null $modifyReorderActionUsing = null;

    private ?IconManager $iconManager = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->default([]);

        $this->afterStateHydrated(static function (KeyValue $component): void {
            $component->hydrateRows();
        });

        $this->registerActions([
            fn (KeyValue $component): Action => $component->getAddAction(),
            fn (KeyValue $component): Action => $component->getDeleteAction(),
            fn (KeyValue $component): Action => $component->getReorderAction(),
        ]);
    }

    #[Required]
    public function setIconManager(IconManager $iconManager): void
    {
        $this->iconManager = $iconManager;
    }

    public function getAddAction(): Action
    {
        $action = Action::make($this->getAddActionName())
            ->label(fn (KeyValue $component): string => $component->getAddActionLabel())
            ->color('gray')
            ->action(function (KeyValue $component): void {
                $rows = $component->getRawRows();
                $rows[] = self::BLANK_ROW;

                $component->rawState($rows);

                $component->callAfterStateUpdatedHooks();
            })
            ->link()
            ->visible(fn (KeyValue $component): bool => $component->isAddable());

        return $this->applyActionModifier($this->modifyAddActionUsing, $action);
    }

    public function addAction(?Closure $callback): static
    {
        $this->modifyAddActionUsing = $callback;

        return $this;
    }

    public function getAddActionName(): string
    {
        return 'add';
    }

    public function getDeleteAction(): Action
    {
        $action = Action::make($this->getDeleteActionName())
            ->label(fn (KeyValue $component): string => $component->getDeleteActionLabel())
            ->icon($this->resolveActionIcon(FormsIconAlias::COMPONENTS_KEY_VALUE_ACTIONS_DELETE, 'heroicons:trash-20-solid'))
            ->color('danger')
            ->action(function (array $arguments, KeyValue $component): void {
                $row = $component->getRowArgument($arguments);

                $rows = $component->getRawRows();
                unset($rows[$row]);

                $component->rawState($component->padRows(array_values($rows)));

                $component->callAfterStateUpdatedHooks();
            })
            ->iconButton()
            ->size(Size::Small)
            ->visible(fn (KeyValue $component): bool => $component->isDeletable());

        return $this->applyActionModifier($this->modifyDeleteActionUsing, $action);
    }

    public function deleteAction(?Closure $callback): static
    {
        $this->modifyDeleteActionUsing = $callback;

        return $this;
    }

    public function getDeleteActionName(): string
    {
        return 'delete';
    }

    public function getReorderAction(): Action
    {
        $action = Action::make($this->getReorderActionName())
            ->label(fn (KeyValue $component): string => $component->getReorderActionLabel())
            ->icon($this->resolveActionIcon(FormsIconAlias::COMPONENTS_KEY_VALUE_ACTIONS_REORDER, 'heroicons:arrows-up-down-20-solid'))
            ->color('gray')
            ->action(function (array $arguments, KeyValue $component): void {
                $order = $arguments['items'] ?? null;

                if (! is_array($order) || ! array_is_list($order)) {
                    throw new LogicException('The key-value reorder action was mounted without an "items" argument listing the row indexes in their new order.');
                }

                $rows = $component->getRawRows();
                $reordered = [];

                foreach ($order as $index) {
                    $index = $component->normalizeRowIndex($index);

                    if (($index !== null) && array_key_exists($index, $rows)) {
                        $reordered[$index] = $rows[$index];
                    }
                }

                $component->rawState(array_values($reordered + $rows));

                $component->callAfterStateUpdatedHooks();
            })
            ->liveComponentClickHandlerEnabled(false)
            ->extraAttributes(['data-sortable-handle' => true], merge: true)
            ->iconButton()
            ->size(Size::Small)
            ->visible(fn (KeyValue $component): bool => $component->isReorderable());

        return $this->applyActionModifier($this->modifyReorderActionUsing, $action);
    }

    public function reorderAction(?Closure $callback): static
    {
        $this->modifyReorderActionUsing = $callback;

        return $this;
    }

    public function getReorderActionName(): string
    {
        return 'reorder';
    }

    public function addActionLabel(string|Closure|null $label): static
    {
        $this->addActionLabel = $label;

        return $this;
    }

    public function deleteActionLabel(string|Closure|null $label): static
    {
        $this->deleteActionLabel = $label;

        return $this;
    }

    public function reorderActionLabel(string|Closure|null $label): static
    {
        $this->reorderActionLabel = $label;

        return $this;
    }

    public function addable(bool|Closure $condition = true): static
    {
        $this->isAddable = $condition;

        return $this;
    }

    public function deletable(bool|Closure $condition = true): static
    {
        $this->isDeletable = $condition;

        return $this;
    }

    public function editableKeys(bool|Closure $condition = true): static
    {
        $this->canEditKeys = $condition;

        return $this;
    }

    public function editableValues(bool|Closure $condition = true): static
    {
        $this->canEditValues = $condition;

        return $this;
    }

    public function reorderable(bool|Closure $condition = true): static
    {
        $this->isReorderable = $condition;

        return $this;
    }

    public function keyLabel(string|Closure|null $label): static
    {
        $this->keyLabel = $label;

        return $this;
    }

    public function valueLabel(string|Closure|null $label): static
    {
        $this->valueLabel = $label;

        return $this;
    }

    public function keyPlaceholder(string|Closure|null $placeholder): static
    {
        $this->keyPlaceholder = $placeholder;

        return $this;
    }

    public function valuePlaceholder(string|Closure|null $placeholder): static
    {
        $this->valuePlaceholder = $placeholder;

        return $this;
    }

    public function isAddable(): bool
    {
        if ($this->isDisabled()) {
            return false;
        }

        return (bool) $this->evaluate($this->isAddable);
    }

    public function isDeletable(): bool
    {
        if ($this->isDisabled()) {
            return false;
        }

        return (bool) $this->evaluate($this->isDeletable);
    }

    public function canEditKeys(): bool
    {
        return (bool) $this->evaluate($this->canEditKeys);
    }

    public function canEditValues(): bool
    {
        return (bool) $this->evaluate($this->canEditValues);
    }

    public function isReorderable(): bool
    {
        if ($this->isDisabled()) {
            return false;
        }

        return (bool) $this->evaluate($this->isReorderable);
    }

    public function getAddActionLabel(): string
    {
        return $this->evaluate($this->addActionLabel) ?? 'Add row';
    }

    public function getDeleteActionLabel(): string
    {
        return $this->evaluate($this->deleteActionLabel) ?? 'Delete row';
    }

    public function getReorderActionLabel(): string
    {
        return $this->evaluate($this->reorderActionLabel) ?? 'Reorder row';
    }

    public function getKeyLabel(): string
    {
        return $this->evaluate($this->keyLabel) ?? 'Key';
    }

    public function getValueLabel(): string
    {
        return $this->evaluate($this->valueLabel) ?? 'Value';
    }

    public function getKeyPlaceholder(): ?string
    {
        return $this->evaluate($this->keyPlaceholder);
    }

    public function getValuePlaceholder(): ?string
    {
        return $this->evaluate($this->valuePlaceholder);
    }

    public function hydrateRows(): void
    {
        $this->rawState($this->padRows($this->getRawRows()));
    }

    /**
     * @return array<StateCast>
     */
    public function getDefaultStateCasts(): array
    {
        return [
            ...parent::getDefaultStateCasts(),
            new KeyValueStateCast(),
        ];
    }

    public function mutateStateForValidation(mixed $state): mixed
    {
        foreach ($this->getStateCasts() as $stateCast) {
            $state = $stateCast->get($state);
        }

        return parent::mutateStateForValidation($state);
    }

    public function mutatesStateForValidation(): bool
    {
        return true;
    }

    /**
     * The raw rows, normalised: a list of `key`/`value` pairs whatever the bag
     * holds, so an index is always the row's position and a row always answers
     * both cells.
     *
     * @return list<array{key: mixed, value: mixed}>
     */
    private function getRawRows(): array
    {
        $rawState = $this->getRawState();

        $rows = [];

        foreach (is_array($rawState) ? $rawState : [] as $row) {
            $rows[] = [
                'key' => is_array($row) ? ($row['key'] ?? null) : null,
                'value' => is_array($row) ? ($row['value'] ?? null) : null,
            ];
        }

        return $rows;
    }

    /**
     * The per-row preamble a template cannot express in a single getter call:
     * every row's delete trigger is the shared action bound to that row's index.
     *
     * @return array<array-key, mixed>
     */
    public function getExtraTemplateData(): array
    {
        $deleteAction = $this->getAction($this->getDeleteActionName());

        $rows = [];

        foreach ($this->getRawRows() as $index => $row) {
            $rows[] = [
                'index' => $index,
                'key' => $row['key'],
                'value' => $row['value'],
                'deleteAction' => $deleteAction?->__invoke(['row' => $index]),
            ];
        }

        return [
            'rows' => $rows,
            'addAction' => $this->getAction($this->getAddActionName()),
            'reorderAction' => $this->getAction($this->getReorderActionName()),
            'animationDuration' => $this->getReorderAnimationDuration(),
        ];
    }

    /**
     * A row index a client named, as an array key — a numeric string is the
     * integer key it names, since that is what a JSON round trip makes of one.
     */
    private function normalizeRowIndex(mixed $index): ?int
    {
        if (is_int($index)) {
            return $index;
        }

        if (is_string($index) && ctype_digit($index)) {
            return (int) $index;
        }

        return null;
    }

    /**
     * @param list<array{key: mixed, value: mixed}> $rows
     *
     * @return non-empty-list<array{key: mixed, value: mixed}>
     */
    private function padRows(array $rows): array
    {
        return ($rows === []) ? [self::BLANK_ROW] : $rows;
    }

    /**
     * The row a client's delete trigger named, validated against the rows the
     * server rendered.
     *
     * @param array<mixed> $arguments
     *
     * @throws LogicException when the trigger named no existing row
     */
    private function getRowArgument(array $arguments): int
    {
        $row = $this->normalizeRowIndex($arguments['row'] ?? null);

        if (($row === null) || ! array_key_exists($row, $this->getRawRows())) {
            throw new LogicException('The key-value delete action was mounted without a "row" argument naming the row.');
        }

        return $row;
    }

    /**
     * The icon a user's alias override names, or the shipped default.
     */
    private function resolveActionIcon(string $alias, string $fallback): string|BackedEnum|Markup
    {
        return ($this->iconManager ??= new IconManager())->resolve($alias) ?? $fallback;
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
