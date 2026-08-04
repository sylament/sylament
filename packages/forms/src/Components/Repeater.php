<?php

declare(strict_types=1);

namespace Sylament\Forms\Components;

use Closure;
use LogicException;
use Sylament\Actions\Action;
use Sylament\Forms\Components\Concerns\CanBeCloned;
use Sylament\Forms\Components\Concerns\CanGenerateUuids;
use Sylament\Forms\Components\Concerns\CanLimitItemsLength;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\RecordTarget;
use Sylament\Support\Enums\Alignment;
use Sylament\Support\Enums\Size;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Type;
use Twig\Markup;

use function Sylament\Forms\array_move_after;
use function Sylament\Forms\array_move_before;
use function Symfony\Component\String\u;

class Repeater extends Field
{
    use CanBeCloned;
    use CanGenerateUuids;
    use CanLimitItemsLength;

    /**
     * The keys, their order, and whether each item holds an array — everything an
     * item schema is built from — snapshotted when the item schemas were cached.
     *
     * @var array<bool>|null
     */
    protected ?array $cachedItemsRawStateStructure = null;

    /**
     * The uuid-keyed items a declared {@see default()} produced, kept so the
     * per-field hydration pass that runs after it can be merged back in rather
     * than overwriting it.
     *
     * @var array<array-key, mixed>|null
     */
    protected ?array $hydratedDefaultState = null;

    protected bool $shouldMergeHydratedDefaultStateWithItemsStateAfterStateHydrated = true;

    protected Field|Closure|null $simpleField = null;

    protected ?Field $cachedSimpleField = null;

    protected string|Markup|Closure|null $itemLabel = null;

    protected bool|Closure $hasItemNumbers = false;

    protected bool|Closure $hasItemHeaders = true;

    protected bool|Closure $isItemLabelTruncated = true;

    protected bool|Closure $isAddable = true;

    protected bool|Closure $isDeletable = true;

    protected bool|Closure $isReorderable = true;

    protected bool|Closure $isReorderableWithButtons = false;

    protected string|Closure|null $addActionLabel = null;

    protected Alignment|string|Closure|null $addActionAlignment = null;

    protected Action|Closure|null $modifyAddActionUsing = null;

    protected Action|Closure|null $modifyCloneActionUsing = null;

    protected Action|Closure|null $modifyDeleteActionUsing = null;

    protected Action|Closure|null $modifyMoveDownActionUsing = null;

    protected Action|Closure|null $modifyMoveUpActionUsing = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultItems(1);

        $this->afterStateHydrated(static function (Repeater $component): void {
            $component->hydrateItems();
        });

        $this->registerActions([
            fn (Repeater $component): Action => $component->getAddAction(),
            fn (Repeater $component): Action => $component->getCloneAction(),
            fn (Repeater $component): Action => $component->getDeleteAction(),
            fn (Repeater $component): Action => $component->getMoveDownAction(),
            fn (Repeater $component): Action => $component->getMoveUpAction(),
        ]);

        $this->mutateDehydratedStateUsing(static fn (Repeater $component, mixed $state): array => $component->dehydrateItems(is_array($state) ? $state : []));
    }

    public function ownsRecordSubtree(): bool
    {
        return true;
    }

    /**
     * Re-key whatever positional list the record read into per-request uuids, so a
     * structural mutation never renumbers a sibling's state path mid-edit. A
     * declared default has already produced its own keyed items, so it only takes
     * the merge and returns.
     */
    public function hydrateItems(): void
    {
        $this->refuseObjectCollectionRecord();

        if (
            is_array($this->hydratedDefaultState)
            && $this->shouldMergeHydratedDefaultStateWithItemsStateAfterStateHydrated
        ) {
            $this->mergeHydratedDefaultStateWithItemsState();
        }

        if (is_array($this->hydratedDefaultState)) {
            return;
        }

        $rawState = $this->getRawState();
        $items = [];

        foreach (is_array($rawState) ? $rawState : [] as $itemData) {
            $itemData = $this->wrapSimpleItemData($itemData);

            $uuid = $this->generateUuid();

            if ($uuid !== null) {
                $items[$uuid] = $itemData;

                continue;
            }

            $items[] = $itemData;
        }

        $this->rawState($items);
    }

    /**
     * @param array<array-key, mixed> $state
     *
     * @return array<int, mixed>
     */
    public function dehydrateItems(array $state): array
    {
        $simpleField = $this->getSimpleField();

        if (! $simpleField instanceof Field) {
            return array_values($state);
        }

        $name = (string) $simpleField->getName();

        return array_map(
            static fn (mixed $itemData): mixed => is_array($itemData) ? ($itemData[$name] ?? null) : null,
            array_values($state),
        );
    }

    /**
     * One child schema per item, each a clone of the `components()` template
     * re-pathed onto the item's key, so two items never share a field instance.
     *
     * @return array<array-key, Schema>
     */
    public function getDefaultChildSchemas(): array
    {
        $rawState = $this->getRawState();
        $rawState = is_array($rawState) ? $rawState : [];

        $this->cachedItemsRawStateStructure = array_map(is_array(...), $rawState);

        $items = [];

        foreach (array_keys($rawState) as $itemKey) {
            $itemSchema = $this->getChildSchema()?->statePath((string) $itemKey)->getClone();

            if ($itemSchema instanceof Schema) {
                $items[$itemKey] = $itemSchema;
            }
        }

        return $items;
    }

    /**
     * @return array<array-key, Schema>
     */
    public function getItems(): array
    {
        return $this->getCachedDefaultChildSchemas();
    }

    public function defaultItems(int|Closure $count): static
    {
        $this->default(static function (Repeater $component) use ($count): array {
            $count = $component->evaluate($count);

            if ($count < 1) {
                return [];
            }

            return array_fill(0, $count, $component->isSimple() ? null : []);
        });

        $this->shouldMergeHydratedDefaultStateWithItemsStateAfterStateHydrated = false;

        return $this;
    }

    public function default(mixed $state): static
    {
        parent::default(function (Repeater $component) use ($state): array {
            $state = $component->evaluate($state);

            $items = [];

            foreach (is_array($state) ? $state : [] as $itemData) {
                $itemData = $component->wrapSimpleItemData($itemData);

                $uuid = $component->generateUuid();

                if ($uuid !== null) {
                    $items[$uuid] = $itemData;

                    continue;
                }

                $items[] = $itemData;
            }

            $component->hydratedDefaultState = $items;

            return $items;
        });

        $this->shouldMergeHydratedDefaultStateWithItemsStateAfterStateHydrated = true;

        return $this;
    }

    public function simple(Field|Closure|null $field): static
    {
        $this->simpleField = $field;

        $this->components(static function (Repeater $component): array {
            $simpleField = $component->getSimpleField();

            return $simpleField instanceof Field ? [$simpleField] : [];
        });

        return $this;
    }

    public function isSimple(): bool
    {
        return $this->simpleField !== null;
    }

    public function getSimpleField(): ?Field
    {
        return ($this->cachedSimpleField ??= $this->evaluate($this->simpleField))?->hiddenLabel();
    }

    /**
     * @return array<string, mixed>
     */
    public function getItemState(string $key): array
    {
        return $this->getChildSchema($key)?->getState(shouldCallHooksBefore: false) ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getRawItemState(string $key): array
    {
        return $this->getChildSchema($key)?->getStateSnapshot() ?? [];
    }

    public function itemLabel(string|Markup|Closure|null $label): static
    {
        $this->itemLabel = $label;

        return $this;
    }

    public function getItemLabel(string $key, ?int $index = null): string|Markup|null
    {
        $container = $this->getChildSchema($key);

        return $this->evaluate($this->itemLabel, [
            'container' => $container,
            'index' => $index,
            'item' => $container,
            'key' => $key,
            'schema' => $container,
            'state' => $container?->getStateSnapshot() ?? [],
            'uuid' => $key,
        ]);
    }

    public function hasItemLabels(): bool
    {
        return $this->itemLabel !== null;
    }

    public function itemNumbers(bool|Closure $condition = true): static
    {
        $this->hasItemNumbers = $condition;

        return $this;
    }

    public function hasItemNumbers(): bool
    {
        return (bool) $this->evaluate($this->hasItemNumbers);
    }

    public function itemHeaders(bool|Closure $condition = true): static
    {
        $this->hasItemHeaders = $condition;

        return $this;
    }

    public function hasItemHeaders(): bool
    {
        return (bool) $this->evaluate($this->hasItemHeaders);
    }

    public function truncateItemLabel(bool|Closure $condition = true): static
    {
        $this->isItemLabelTruncated = $condition;

        return $this;
    }

    public function isItemLabelTruncated(): bool
    {
        return (bool) $this->evaluate($this->isItemLabelTruncated);
    }

    public function getHeadingsCount(): int
    {
        return $this->hasItemLabels() ? 1 : 0;
    }

    public function addable(bool|Closure $condition = true): static
    {
        $this->isAddable = $condition;

        return $this;
    }

    public function isAddable(): bool
    {
        if ($this->isDisabled()) {
            return false;
        }

        $maxItems = $this->getMaxItems();

        if (($maxItems !== null) && ($maxItems <= $this->getItemsCount())) {
            return false;
        }

        return (bool) $this->evaluate($this->isAddable);
    }

    public function deletable(bool|Closure $condition = true): static
    {
        $this->isDeletable = $condition;

        return $this;
    }

    public function isDeletable(): bool
    {
        if ($this->isDisabled()) {
            return false;
        }

        return (bool) $this->evaluate($this->isDeletable);
    }

    public function reorderable(bool|Closure $condition = true): static
    {
        $this->isReorderable = $condition;

        return $this;
    }

    public function isReorderable(): bool
    {
        if ($this->isDisabled()) {
            return false;
        }

        return (bool) $this->evaluate($this->isReorderable);
    }

    public function reorderableWithButtons(bool|Closure $condition = true): static
    {
        $this->isReorderableWithButtons = $condition;

        return $this;
    }

    public function isReorderableWithButtons(): bool
    {
        return $this->evaluate($this->isReorderableWithButtons) && $this->isReorderable();
    }

    public function getAddAction(): Action
    {
        $action = Action::make($this->getAddActionName())
            ->label(fn (Repeater $component): string => $component->getAddActionLabel())
            ->color('gray')
            ->action(function (Repeater $component): void {
                $newUuid = $component->generateUuid();

                $items = $component->getRawState();
                $items = is_array($items) ? $items : [];

                if ($newUuid !== null) {
                    $items[$newUuid] = [];
                } else {
                    $items[] = [];
                }

                $component->rawState($items);

                $component->getChildSchema((string) ($newUuid ?? array_key_last($items)))?->fill();

                $component->callAfterStateUpdatedHooks();
            })
            ->button()
            ->size(Size::Small)
            ->visible(fn (Repeater $component): bool => $component->isAddable());

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

    public function addActionLabel(string|Closure|null $label): static
    {
        $this->addActionLabel = $label;

        return $this;
    }

    public function getAddActionLabel(): string
    {
        $label = $this->evaluate($this->addActionLabel);

        if ($label !== null) {
            return $label;
        }

        $componentLabel = u((string) $this->getLabel());

        return 'Add to ' . $componentLabel->slice(0, 1)->lower()->toString() . $componentLabel->slice(1)->toString();
    }

    public function addActionAlignment(Alignment|string|Closure|null $alignment): static
    {
        $this->addActionAlignment = $alignment;

        return $this;
    }

    public function getAddActionAlignment(): Alignment|string|null
    {
        $alignment = $this->evaluate($this->addActionAlignment);

        if (is_string($alignment)) {
            return Alignment::tryFrom($alignment) ?? $alignment;
        }

        return $alignment;
    }

    public function getDeleteAction(): Action
    {
        $action = Action::make($this->getDeleteActionName())
            ->label('Delete')
            ->icon('heroicons:trash')
            ->color('danger')
            ->action(function (array $arguments, Repeater $component): void {
                $items = $component->getRawState();
                $items = is_array($items) ? $items : [];

                unset($items[$component->getItemArgument($arguments)]);

                $component->rawState($items);

                $component->callAfterStateUpdatedHooks();
            })
            ->iconButton()
            ->size(Size::Small)
            ->visible(fn (Repeater $component): bool => $component->isDeletable());

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

    public function getCloneAction(): Action
    {
        $action = Action::make($this->getCloneActionName())
            ->label('Clone')
            ->icon('heroicons:square-2-stack')
            ->color('gray')
            ->action(function (array $arguments, Repeater $component): void {
                $items = $component->getRawState();
                $items = is_array($items) ? $items : [];

                $itemKey = $component->getItemArgument($arguments);

                if (! array_key_exists($itemKey, $items)) {
                    return;
                }

                $newUuid = $component->generateUuid();

                if ($newUuid !== null) {
                    $items[$newUuid] = $items[$itemKey];
                } else {
                    $items[] = $items[$itemKey];
                }

                $component->rawState($items);

                $component->callAfterStateUpdatedHooks();
            })
            ->iconButton()
            ->size(Size::Small)
            ->visible(fn (Repeater $component): bool => $component->isCloneable());

        return $this->applyActionModifier($this->modifyCloneActionUsing, $action);
    }

    public function cloneAction(?Closure $callback): static
    {
        $this->modifyCloneActionUsing = $callback;

        return $this;
    }

    public function getCloneActionName(): string
    {
        return 'clone';
    }

    public function getMoveUpAction(): Action
    {
        $action = Action::make($this->getMoveUpActionName())
            ->label('Move up')
            ->icon('heroicons:arrow-up')
            ->color('gray')
            ->action(function (array $arguments, Repeater $component): void {
                $items = $component->getRawState();

                $component->rawState(array_move_before(is_array($items) ? $items : [], $component->getItemArgument($arguments)));

                $component->callAfterStateUpdatedHooks();
            })
            ->iconButton()
            ->size(Size::Small)
            ->visible(fn (Repeater $component): bool => $component->isReorderable());

        return $this->applyActionModifier($this->modifyMoveUpActionUsing, $action);
    }

    public function moveUpAction(?Closure $callback): static
    {
        $this->modifyMoveUpActionUsing = $callback;

        return $this;
    }

    public function getMoveUpActionName(): string
    {
        return 'moveUp';
    }

    public function getMoveDownAction(): Action
    {
        $action = Action::make($this->getMoveDownActionName())
            ->label('Move down')
            ->icon('heroicons:arrow-down')
            ->color('gray')
            ->action(function (array $arguments, Repeater $component): void {
                $items = $component->getRawState();

                $component->rawState(array_move_after(is_array($items) ? $items : [], $component->getItemArgument($arguments)));

                $component->callAfterStateUpdatedHooks();
            })
            ->iconButton()
            ->size(Size::Small)
            ->visible(fn (Repeater $component): bool => $component->isReorderable());

        return $this->applyActionModifier($this->modifyMoveDownActionUsing, $action);
    }

    public function moveDownAction(?Closure $callback): static
    {
        $this->modifyMoveDownActionUsing = $callback;

        return $this;
    }

    public function getMoveDownActionName(): string
    {
        return 'moveDown';
    }

    /**
     * Item schemas only depend on the raw state's structure — the item keys, their
     * order, and whether each holds an array — since fields inside the items read
     * their values from the live raw state. Comparing a structural fingerprint
     * keeps a value edit inside an item from rebuilding every item schema.
     */
    protected function areCachedDefaultChildSchemasFresh(): bool
    {
        $rawState = $this->getRawState();

        return $this->cachedItemsRawStateStructure === array_map(is_array(...), is_array($rawState) ? $rawState : []);
    }

    protected function mergeHydratedDefaultStateWithItemsState(): void
    {
        $state = $this->getRawState();
        $state = is_array($state) ? $state : [];

        $items = $this->hydratedDefaultState ?? [];

        foreach ($items as $itemKey => $itemData) {
            $hydrated = $state[$itemKey] ?? [];

            $items[$itemKey] = [
                ...(is_array($hydrated) ? $hydrated : []),
                ...(is_array($itemData) ? $itemData : []),
            ];
        }

        $this->rawState($items);
    }

    /**
     * @return list<Constraint>
     */
    protected function buildValidationConstraints(): array
    {
        $constraints = parent::buildValidationConstraints();

        $minItems = $this->getMinItems();
        $maxItems = $this->getMaxItems();

        if (($minItems !== null) || ($maxItems !== null)) {
            $constraints[] = new Type('array');
            $constraints[] = new Count(
                min: ($minItems === null) ? null : max(0, $minItems),
                max: ($maxItems === null) ? null : max(0, $maxItems),
            );
        }

        return $constraints;
    }

    /**
     * Simple mode carries one scalar per item where the item pipeline works in
     * item arrays, so a scalar is wrapped under the simple field's name on the way
     * in ({@see dehydrateItems()} plucks it back out on the way out).
     */
    protected function wrapSimpleItemData(mixed $itemData): mixed
    {
        $simpleField = $this->getSimpleField();

        if (! $simpleField instanceof Field) {
            return $itemData;
        }

        return [(string) $simpleField->getName() => $itemData];
    }

    /**
     * The item a per-item trigger bound through `HasMountableArguments`, as an
     * array key. A numeric string finds the integer key a positionally keyed map
     * carries, since PHP normalises it on lookup.
     *
     * @param array<mixed> $arguments
     *
     * @throws LogicException when the trigger mounted the action without naming an item
     */
    private function getItemArgument(array $arguments): string
    {
        $item = $arguments['item'] ?? null;

        if (! is_string($item) && ! is_int($item)) {
            throw new LogicException('A repeater item action was mounted without an "item" argument naming the item it acts on.');
        }

        return (string) $item;
    }

    /**
     * Run a declared `…Action()` modifier over a freshly built action, keeping the
     * built one when the modifier declares no replacement.
     */
    private function applyActionModifier(Action|Closure|null $callback, Action $action): Action
    {
        return $this->evaluate($callback, ['action' => $action]) ?? $action;
    }

    /**
     * A repeater bound to an object collection would render empty and meet the
     * mapper's refusal only on first save, so it refuses at hydration instead:
     * mapping collection items needs identity, which this epic does not build.
     *
     * @throws LogicException when the record declares an object collection at this
     *                        component's record path
     */
    private function refuseObjectCollectionRecord(): void
    {
        if (! isset($this->container)) {
            return;
        }

        $recordClass = $this->getRootContainer()->getRecordClass();
        $recordPath = $this->getRecordStatePath();

        if (($recordClass === null) || ($recordPath === null)) {
            return;
        }

        $target = $this->getRecordTypeReader()->describe($recordClass, $recordPath);

        if (! $target instanceof RecordTarget || ! $target->isObjectCollection) {
            return;
        }

        $segments = explode('.', $recordPath);

        throw new LogicException(sprintf(
            'Cannot map state path "%s" onto %s: "%s" is an object collection. Collection items need identity mapping (which state item is which entity), which arrives with the Doctrine relationship layer — the Repeater maps plain-array items only. Until then, map this property by hand after getData().',
            $recordPath,
            $recordClass,
            $segments[count($segments) - 1],
        ));
    }
}
