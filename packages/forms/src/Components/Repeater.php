<?php

declare(strict_types=1);

namespace Sylament\Forms\Components;

use BackedEnum;
use Closure;
use LogicException;
use Sylament\Actions\Action;
use Sylament\Forms\Components\Concerns\BelongsToRelationship;
use Sylament\Forms\Components\Concerns\CanBeCloned;
use Sylament\Forms\Components\Concerns\CanGenerateUuids;
use Sylament\Forms\Components\Concerns\CanLimitItemsLength;
use Sylament\Forms\Components\Concerns\HasExtraItemActions;
use Sylament\Forms\Components\Repeater\TableColumn;
use Sylament\Forms\Twig\FormsIconAlias;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Concerns\CanBeCollapsed;
use Sylament\Schemas\Components\Concerns\CanBeCompact;
use Sylament\Schemas\Components\Concerns\HasContainerGridLayout;
use Sylament\Schemas\Components\Contracts\CanConcealComponents;
use Sylament\Schemas\Components\Contracts\HasExtraItemActions as HasExtraItemActionsContract;
use Sylament\Schemas\Components\Contracts\HasKeyedItems;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\AssociationTarget;
use Sylament\Schemas\State\Casts\ObjectStateCast;
use Sylament\Schemas\State\RecordTarget;
use Sylament\Support\Components\Concerns\HasReorderAnimationDuration;
use Sylament\Support\Enums\Alignment;
use Sylament\Support\Enums\Size;
use Sylament\Support\Enums\VerticalAlignment;
use Sylament\Support\Icons\IconManager;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Contracts\Service\Attribute\Required;
use Twig\Markup;

use function Sylament\Forms\array_move_after;
use function Sylament\Forms\array_move_before;
use function Sylament\Support\filled;
use function Symfony\Component\String\u;

class Repeater extends Field implements CanConcealComponents, HasExtraItemActionsContract, HasKeyedItems
{
    use BelongsToRelationship;
    use CanBeCloned;
    use CanBeCollapsed;
    use CanBeCompact;
    use CanGenerateUuids;
    use CanLimitItemsLength;
    use HasContainerGridLayout;
    use HasExtraItemActions;
    use HasReorderAnimationDuration;

    public const string TEMPLATE = '@SylamentForms/partials/Repeater/index.html.twig';

    public const string SIMPLE_TEMPLATE = '@SylamentForms/partials/Repeater/Simple.html.twig';

    public const string TABLE_TEMPLATE = '@SylamentForms/partials/Repeater/Table.html.twig';

    /**
     * The item-key prefix that carries a loaded child's record key, so a save
     * can tell an item that was filled off the collection from one the client
     * added.
     */
    private const string RECORD_ITEM_KEY_PREFIX = 'record-';

    protected string|Closure|null $defaultTemplate = self::TEMPLATE;

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

    /**
     * The item key each element of the record's collection was written from,
     * keyed by the key that collection holds the element under — recorded by the
     * last relationship save, since only the write knows the pairing.
     *
     * @var array<int|string, string>|null
     */
    protected ?array $itemKeysByCollectionKey = null;

    protected bool $shouldMergeHydratedDefaultStateWithItemsStateAfterStateHydrated = true;

    protected Field|Closure|null $simpleField = null;

    protected ?Field $cachedSimpleField = null;

    protected ObjectStateCast|string|Closure|null $itemsAs = null;

    protected ?Closure $denormalizeItemsUsing = null;

    protected ?Closure $normalizeItemsUsing = null;

    protected ?ObjectStateCast $cachedItemCast = null;

    protected string|Closure|null $orderColumn = null;

    protected ?Closure $mutateRelationshipDataBeforeCreateUsing = null;

    protected ?Closure $mutateRelationshipDataBeforeFillUsing = null;

    protected ?Closure $mutateRelationshipDataBeforeSaveUsing = null;

    protected string|Markup|Closure|null $itemLabel = null;

    protected bool|Closure $hasItemNumbers = false;

    protected bool|Closure $hasItemHeaders = true;

    protected bool|Closure $isItemLabelTruncated = true;

    protected bool|Closure $isAddable = true;

    protected bool|Closure $isDeletable = true;

    protected bool|Closure $isReorderable = true;

    protected bool|Closure $isReorderableWithButtons = false;

    protected bool|Closure $isReorderableWithDragAndDrop = true;

    protected string|Closure|null $addActionLabel = null;

    protected string|Closure|null $addBetweenActionLabel = null;

    protected string|Closure|null $labelBetweenItems = null;

    /**
     * @var array<TableColumn>|Closure|null
     */
    protected array|Closure|null $tableColumns = null;

    protected Alignment|string|Closure|null $addActionAlignment = null;

    protected Action|Closure|null $modifyAddActionUsing = null;

    protected Action|Closure|null $modifyAddBetweenActionUsing = null;

    protected Action|Closure|null $modifyCloneActionUsing = null;

    protected Action|Closure|null $modifyCollapseActionUsing = null;

    protected Action|Closure|null $modifyCollapseAllActionUsing = null;

    protected Action|Closure|null $modifyDeleteActionUsing = null;

    protected Action|Closure|null $modifyExpandActionUsing = null;

    protected Action|Closure|null $modifyExpandAllActionUsing = null;

    protected Action|Closure|null $modifyMoveDownActionUsing = null;

    protected Action|Closure|null $modifyMoveUpActionUsing = null;

    protected Action|Closure|null $modifyReorderActionUsing = null;

    private ?IconManager $iconManager = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultItems(1);

        $this->afterStateHydrated(static function (Repeater $component): void {
            $component->hydrateItems();
        });

        $this->registerActions([
            fn (Repeater $component): Action => $component->getAddAction(),
            fn (Repeater $component): Action => $component->getAddBetweenAction(),
            fn (Repeater $component): Action => $component->getCloneAction(),
            fn (Repeater $component): Action => $component->getCollapseAction(),
            fn (Repeater $component): Action => $component->getCollapseAllAction(),
            fn (Repeater $component): Action => $component->getDeleteAction(),
            fn (Repeater $component): Action => $component->getExpandAction(),
            fn (Repeater $component): Action => $component->getExpandAllAction(),
            fn (Repeater $component): Action => $component->getMoveDownAction(),
            fn (Repeater $component): Action => $component->getMoveUpAction(),
            fn (Repeater $component): Action => $component->getReorderAction(),
        ]);

        $this->mutateDehydratedStateUsing(static fn (Repeater $component, mixed $state): array => $component->dehydrateItems(is_array($state) ? $state : []));
    }

    #[Required]
    public function setIconManager(IconManager $iconManager): void
    {
        $this->iconManager = $iconManager;
    }

    public function ownsRecordSubtree(): bool
    {
        return true;
    }

    /**
     * A relationship repeater routes the hooks its *items* declare: the path has
     * to name one of the items it currently holds and then something the item
     * schema declares a hook at, read from the very discovery the two edges route
     * from. Anything else is a decoration schema's — `belowContent()` and its nine
     * siblings hang off the repeater itself, and neither edge walks them, so a
     * dotted path of theirs must not be vouched for by a remainder that happens to
     * name an item hook — and it stays refused rather than silently never firing.
     */
    public function routesNestedRelationshipHookAt(string $path): bool
    {
        if (! $this->hasRelationship()) {
            return false;
        }

        $separator = mb_strpos($path, '.');

        if (($separator === false) || ($separator === 0)) {
            return false;
        }

        if (! array_key_exists(mb_substr($path, 0, $separator), $this->getCachedDefaultChildSchemas())) {
            return false;
        }

        $itemPath = mb_substr($path, $separator + 1);

        return ($itemPath !== '') && array_key_exists($itemPath, $this->getTemplateNestedRelationshipHooks());
    }

    /**
     * The set-ward firing point of a declared item cast: record-read objects are
     * deconstructed into value-shaped arrays here, before any hook or descendant
     * reads the items.
     *
     * @param array<string, true> $appliedStateCastPaths
     * @param array<string, mixed> $hydratedDefaultState
     */
    public function hydrateState(bool $applyDefaults = true, array &$appliedStateCastPaths = [], array &$hydratedDefaultState = []): void
    {
        parent::hydrateState($applyDefaults, $appliedStateCastPaths, $hydratedDefaultState);

        $cast = $this->getItemCast();

        if (! $cast instanceof ObjectStateCast) {
            return;
        }

        $rawState = $this->getRawState();

        if (! is_array($rawState)) {
            return;
        }

        $items = array_map($cast->set(...), $rawState);

        if ($items !== $rawState) {
            $this->rawState($items);
        }
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

        if ($this->hasRelationship()) {
            return;
        }

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
     * The get-ward firing point of a declared item cast: each item is
     * constructed after the map has been positionalised. Relationship items are
     * keyed by identity rather than position, so they pass through untouched —
     * the save hook reads those keys.
     *
     * @param array<array-key, mixed> $state
     *
     * @return array<array-key, mixed>
     */
    public function dehydrateItems(array $state): array
    {
        if ($this->hasRelationship()) {
            return $state;
        }

        $simpleField = $this->getSimpleField();

        if ($simpleField instanceof Field) {
            $name = (string) $simpleField->getName();

            return array_map(
                static fn (mixed $itemData): mixed => is_array($itemData) ? ($itemData[$name] ?? null) : null,
                array_values($state),
            );
        }

        $items = array_values($state);

        $cast = $this->getItemCast();

        if ($cast instanceof ObjectStateCast) {
            return array_map($cast->get(...), $items);
        }

        return $items;
    }

    /**
     * One child schema per item, each a clone of the `components()` template
     * re-pathed onto the item's key, so two items never share a field instance.
     * A relationship item is stamped with the child it maps, so everything inside
     * it — a nested relation-backed field above all — resolves that record rather
     * than the owner's; an item the client added has no child yet and keeps the
     * template's class stamp.
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
            $itemSchema = $this->makeItemSchema((string) $itemKey);

            if (! $itemSchema instanceof Schema) {
                continue;
            }

            $items[(string) $itemKey] = $itemSchema;
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

    /**
     * The item keys keyed the way the record holds the items: a relationship
     * save records the pairing element by element, because the accessor's
     * adder/remover diffing leaves the collection's own keys alone rather than
     * re-keying it into state order. Without such a save — a plain-array
     * repeater, or a read before any `getData()` — the items are positional and
     * state order is the record's order.
     */
    public function getItemKeys(): array
    {
        if ($this->itemKeysByCollectionKey !== null) {
            return $this->itemKeysByCollectionKey;
        }

        $rawState = $this->getRawState();

        return array_map(strval(...), array_keys(is_array($rawState) ? $rawState : []));
    }

    /**
     * The dotted state paths the item schema declares, relative to one item —
     * what a fill reads off each related record and a save writes back onto it.
     * Read off a clone, so the shared field instances the item schemas are cloned
     * from keep their own cached paths.
     *
     * @return list<string>
     */
    public function getItemStatePaths(): array
    {
        $itemSchema = $this->getChildSchema()?->getClone();

        if (! $itemSchema instanceof Schema) {
            return [];
        }

        $prefix = $itemSchema->getStatePath() . '.';
        $itemStatePaths = [];

        foreach ($itemSchema->getFlatComponents(withActions: false) as $component) {
            $statePath = $component->getStatePath();

            if (($statePath !== null) && str_starts_with($statePath, $prefix)) {
                $itemStatePaths[] = mb_substr($statePath, mb_strlen($prefix));
            }
        }

        return $itemStatePaths;
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
        if ($field !== null && $this->itemsAs !== null) {
            throw new LogicException('simple() cannot compose with itemsAs(): the simple pluck would null every constructed object. Put a leaf stateCast() on the simple field instead.');
        }

        if ($field !== null && $this->relationship !== null) {
            throw new LogicException("simple() cannot compose with relationship(): a related record is mapped by the item schema's own fields, which the simple pluck would reduce to one scalar. Declare that one field with components() instead.");
        }

        $this->simpleField = $field;

        $this->defaultTemplate(($field === null) ? self::TEMPLATE : self::SIMPLE_TEMPLATE);

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
     * Declare the value-object item mode: items dehydrate as fresh instances of
     * the declared class and fill deconstructs them back into value-shaped
     * arrays, while the live bag, validation and the structural actions keep
     * seeing arrays. The closure pair overrides either conversion half; each is
     * invoked directly with the item value, never through the Invoker.
     */
    public function itemsAs(string|ObjectStateCast|Closure|null $items, ?Closure $denormalizeUsing = null, ?Closure $normalizeUsing = null): static
    {
        if ($items !== null && $this->isSimple()) {
            throw new LogicException('itemsAs() cannot compose with simple(): the simple pluck would null every constructed object. Put a leaf stateCast() on the simple field instead.');
        }

        if ($items !== null && $this->relationship !== null) {
            throw new LogicException("itemsAs() cannot compose with relationship(): the relationship already constructs each item as the association's target entity, and a freshly built value object would lose its identity. Drop itemsAs().");
        }

        if ($items instanceof ObjectStateCast && ($denormalizeUsing instanceof Closure || $normalizeUsing instanceof Closure)) {
            throw new LogicException('itemsAs() received a prebuilt ObjectStateCast beside conversion closures; the instance already owns both conversion halves.');
        }

        $this->itemsAs = $items;
        $this->denormalizeItemsUsing = $denormalizeUsing;
        $this->normalizeItemsUsing = $normalizeUsing;
        $this->cachedItemCast = null;

        return $this;
    }

    public function getItemCast(): ?ObjectStateCast
    {
        if ($this->cachedItemCast instanceof ObjectStateCast) {
            return $this->cachedItemCast;
        }

        $items = $this->evaluate($this->itemsAs);

        if ($items === null) {
            return null;
        }

        if ($items instanceof ObjectStateCast) {
            if ($this->denormalizeItemsUsing instanceof Closure || $this->normalizeItemsUsing instanceof Closure) {
                throw new LogicException('itemsAs() resolved a prebuilt ObjectStateCast beside conversion closures; the instance already owns both conversion halves.');
            }

            return $this->cachedItemCast = $items;
        }

        return $this->cachedItemCast = new ObjectStateCast($items, $this->denormalizeItemsUsing, $this->normalizeItemsUsing);
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

    /**
     * Back this repeater with a Doctrine collection association: `fill()` reads
     * the children the record holds as identity-keyed items, and `getData()`
     * diffs the submitted items back onto that collection — an item still keyed
     * to a loaded child updates that very instance, an item the client added
     * constructs the association's target, and a child no item names is left out
     * of the desired list for `orphanRemoval` to delete. `$name` names the
     * association property and defaults to the field's own name.
     *
     * @throws LogicException when the repeater already declares another item
     *                        mode, or when a query scope is passed
     */
    public function relationship(string|Closure|null $name = null, ?Closure $modifyQueryUsing = null): static
    {
        if ($this->isSimple()) {
            throw new LogicException("relationship() cannot compose with simple(): a related record is mapped by the item schema's own fields, which the simple pluck would reduce to one scalar. Declare that one field with components() instead.");
        }

        if ($this->itemsAs !== null) {
            throw new LogicException("relationship() cannot compose with itemsAs(): the relationship already constructs each item as the association's target entity, and a freshly built value object would lose its identity. Drop itemsAs().");
        }

        if ($modifyQueryUsing instanceof Closure) {
            throw new LogicException('relationship() was passed a modifyQueryUsing scope, but the items come from the collection the record already holds rather than from a query of their own, so the scope would silently never run. Scope the association itself with Doctrine\'s #[ORM\OrderBy] or a filtered collection.');
        }

        $this->relationship = $name ?? $this->getName();

        $this->loadStateFromRelationshipsUsing(static fn (Repeater $component, object $record): array => $component->getItemsFromRelationship($record));

        $this->saveRelationshipsUsing(static function (Repeater $component, object $record, mixed $state): void {
            $component->saveItemsToRelationship($record, $state);
        });

        $this->reorderable(false);

        return $this;
    }

    /**
     * The property each item's position in the list is stamped onto before the
     * collection is written, so the order the user arranged survives a reload.
     * Doctrine orders the read side: the association needs an `#[ORM\OrderBy]`
     * on this same property for the stamped order to come back.
     */
    public function orderColumn(string|Closure|null $column = 'sort'): static
    {
        $this->orderColumn = $column;

        $this->reorderable(fn (Repeater $component): bool => $component->getOrderColumn() !== null);

        return $this;
    }

    public function getOrderColumn(): ?string
    {
        return $this->evaluate($this->orderColumn);
    }

    /**
     * Rewrite an item's array after it was read off a related record and before
     * the item schema fills from it.
     */
    public function mutateRelationshipDataBeforeFillUsing(?Closure $callback): static
    {
        $this->mutateRelationshipDataBeforeFillUsing = $callback;

        return $this;
    }

    /**
     * Rewrite an added item's array before it is mapped onto a freshly
     * constructed related record.
     */
    public function mutateRelationshipDataBeforeCreateUsing(?Closure $callback): static
    {
        $this->mutateRelationshipDataBeforeCreateUsing = $callback;

        return $this;
    }

    /**
     * Rewrite an item's array before it is mapped onto the related record it was
     * filled from, which the callback receives as `$record` in its pre-write
     * state.
     */
    public function mutateRelationshipDataBeforeSaveUsing(?Closure $callback): static
    {
        $this->mutateRelationshipDataBeforeSaveUsing = $callback;

        return $this;
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

    public function reorderableWithDragAndDrop(bool|Closure $condition = true): static
    {
        $this->isReorderableWithDragAndDrop = $condition;

        return $this;
    }

    public function isReorderableWithDragAndDrop(): bool
    {
        return $this->evaluate($this->isReorderableWithDragAndDrop) && $this->isReorderable();
    }

    public function labelBetweenItems(string|Closure|null $label): static
    {
        $this->labelBetweenItems = $label;

        return $this;
    }

    public function getLabelBetweenItems(): ?string
    {
        return $this->evaluate($this->labelBetweenItems);
    }

    /**
     * Lay the items out as one table row each, pairing the item's components with
     * these columns by position.
     *
     * @param array<TableColumn>|Closure|null $columns
     */
    public function table(array|Closure|null $columns): static
    {
        $this->tableColumns = $columns;

        return $this;
    }

    /**
     * @return array<TableColumn>|null
     */
    public function getTableColumns(): ?array
    {
        return $this->evaluate($this->tableColumns);
    }

    public function isTable(): bool
    {
        return filled($this->getTableColumns());
    }

    public function canConcealComponents(): bool
    {
        return $this->isCollapsible();
    }

    public function getTemplate(): ?string
    {
        return $this->evaluate($this->template) ?? ($this->isTable() ? self::TABLE_TEMPLATE : $this->evaluate($this->defaultTemplate));
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

                $component->collapsed(false, shouldMakeComponentCollapsible: false);

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

    public function getAddActionAlignmentClass(): ?string
    {
        $alignment = $this->getAddActionAlignment();

        if ($alignment instanceof Alignment) {
            return 'sy-align-' . $alignment->value;
        }

        return $alignment;
    }

    public function getDeleteAction(): Action
    {
        $action = Action::make($this->getDeleteActionName())
            ->label('Delete')
            ->icon($this->resolveActionIcon(FormsIconAlias::COMPONENTS_REPEATER_ACTIONS_DELETE, 'heroicons:trash-20-solid'))
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
            ->icon($this->resolveActionIcon(FormsIconAlias::COMPONENTS_REPEATER_ACTIONS_CLONE, 'heroicons:square-2-stack-20-solid'))
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

                $component->collapsed(false, shouldMakeComponentCollapsible: false);

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
            ->icon($this->resolveActionIcon(FormsIconAlias::COMPONENTS_REPEATER_ACTIONS_MOVE_UP, 'heroicons:arrow-up-20-solid'))
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
            ->icon($this->resolveActionIcon(FormsIconAlias::COMPONENTS_REPEATER_ACTIONS_MOVE_DOWN, 'heroicons:arrow-down-20-solid'))
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

    public function getAddBetweenAction(): Action
    {
        $action = Action::make($this->getAddBetweenActionName())
            ->label(fn (Repeater $component): string => $component->getAddBetweenActionLabel())
            ->color('gray')
            ->action(function (array $arguments, Repeater $component): void {
                $afterItem = $arguments['afterItem'] ?? null;

                if (! is_string($afterItem) && ! is_int($afterItem)) {
                    throw new LogicException('The repeater add-between action was mounted without an "afterItem" argument naming the item to insert after.');
                }

                $newKey = $component->generateUuid();
                $rawState = $component->getRawState();
                $items = [];

                foreach ((is_array($rawState) ? $rawState : []) as $key => $item) {
                    $items[$key] = $item;

                    if ((string) $key === (string) $afterItem) {
                        if ($newKey !== null) {
                            $items[$newKey] = [];
                        } else {
                            $items[] = [];
                            $newKey = (string) array_key_last($items);
                        }
                    }
                }

                $component->rawState($items);

                if ($newKey !== null) {
                    $component->getChildSchema($newKey)?->fill();
                }

                $component->collapsed(false, shouldMakeComponentCollapsible: false);

                $component->callAfterStateUpdatedHooks();
            })
            ->button()
            ->size(Size::Small)
            ->visible(false);

        return $this->applyActionModifier($this->modifyAddBetweenActionUsing, $action);
    }

    public function addBetweenAction(?Closure $callback): static
    {
        $this->modifyAddBetweenActionUsing = $callback;

        return $this;
    }

    public function getAddBetweenActionName(): string
    {
        return 'addBetween';
    }

    public function addBetweenActionLabel(string|Closure|null $label): static
    {
        $this->addBetweenActionLabel = $label;

        return $this;
    }

    public function getAddBetweenActionLabel(): string
    {
        return $this->evaluate($this->addBetweenActionLabel) ?? 'Insert between';
    }

    public function getReorderAction(): Action
    {
        $action = Action::make($this->getReorderActionName())
            ->label('Move')
            ->icon($this->resolveActionIcon(FormsIconAlias::COMPONENTS_REPEATER_ACTIONS_REORDER, 'heroicons:arrows-up-down-20-solid'))
            ->color('gray')
            ->action(function (array $arguments, Repeater $component): void {
                $order = $arguments['items'] ?? null;

                if (! is_array($order) || ! array_is_list($order)) {
                    throw new LogicException('The repeater reorder action was mounted without an "items" argument listing the item keys in their new order.');
                }

                $rawState = $component->getRawState();
                $items = is_array($rawState) ? $rawState : [];
                $reordered = [];

                foreach ($order as $itemKey) {
                    if ((is_string($itemKey) || is_int($itemKey)) && array_key_exists($itemKey, $items)) {
                        $reordered[$itemKey] = $items[$itemKey];
                    }
                }

                $component->rawState($reordered + $items);

                $component->callAfterStateUpdatedHooks();
            })
            ->liveComponentClickHandlerEnabled(false)
            ->iconButton()
            ->size(Size::Small)
            ->visible(fn (Repeater $component): bool => $component->isReorderableWithDragAndDrop());

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

    public function getCollapseAction(): Action
    {
        $action = Action::make($this->getCollapseActionName())
            ->label('Collapse')
            ->icon($this->resolveActionIcon(FormsIconAlias::COMPONENTS_REPEATER_ACTIONS_COLLAPSE, 'heroicons:chevron-up-20-solid'))
            ->color('gray')
            ->liveComponentClickHandlerEnabled(false)
            ->iconButton()
            ->size(Size::Small);

        return $this->applyActionModifier($this->modifyCollapseActionUsing, $action);
    }

    public function collapseAction(?Closure $callback): static
    {
        $this->modifyCollapseActionUsing = $callback;

        return $this;
    }

    public function getCollapseActionName(): string
    {
        return 'collapse';
    }

    public function getExpandAction(): Action
    {
        $action = Action::make($this->getExpandActionName())
            ->label('Expand')
            ->icon($this->resolveActionIcon(FormsIconAlias::COMPONENTS_REPEATER_ACTIONS_EXPAND, 'heroicons:chevron-down-20-solid'))
            ->color('gray')
            ->liveComponentClickHandlerEnabled(false)
            ->iconButton()
            ->size(Size::Small);

        return $this->applyActionModifier($this->modifyExpandActionUsing, $action);
    }

    public function expandAction(?Closure $callback): static
    {
        $this->modifyExpandActionUsing = $callback;

        return $this;
    }

    public function getExpandActionName(): string
    {
        return 'expand';
    }

    public function getCollapseAllAction(): Action
    {
        $action = Action::make($this->getCollapseAllActionName())
            ->label('Collapse all')
            ->color('gray')
            ->liveComponentClickHandlerEnabled(false)
            ->link()
            ->size(Size::Small);

        return $this->applyActionModifier($this->modifyCollapseAllActionUsing, $action);
    }

    public function collapseAllAction(?Closure $callback): static
    {
        $this->modifyCollapseAllActionUsing = $callback;

        return $this;
    }

    public function getCollapseAllActionName(): string
    {
        return 'collapseAll';
    }

    public function getExpandAllAction(): Action
    {
        $action = Action::make($this->getExpandAllActionName())
            ->label('Expand all')
            ->color('gray')
            ->liveComponentClickHandlerEnabled(false)
            ->link()
            ->size(Size::Small);

        return $this->applyActionModifier($this->modifyExpandAllActionUsing, $action);
    }

    public function expandAllAction(?Closure $callback): static
    {
        $this->modifyExpandAllActionUsing = $callback;

        return $this;
    }

    public function getExpandAllActionName(): string
    {
        return 'expandAll';
    }

    /**
     * The per-item preamble a template cannot express in a single getter call:
     * each item's triggers are the shared structural action bound to that item's
     * key, and a surface that is off drops out of the row rather than rendering
     * an invisible trigger.
     *
     * @return array<array-key, mixed>
     */
    public function getExtraTemplateData(): array
    {
        $items = $this->getItems();
        $itemCount = count($items);

        $isTable = $this->isTable();
        $isDefault = ! $isTable && ! $this->isSimple();

        $addAction = $this->getAction($this->getAddActionName());
        $addBetweenAction = $isDefault ? $this->getAction($this->getAddBetweenActionName()) : null;
        $cloneAction = $this->getAction($this->getCloneActionName());
        $deleteAction = $this->getAction($this->getDeleteActionName());
        $moveDownAction = $this->getAction($this->getMoveDownActionName());
        $moveUpAction = $this->getAction($this->getMoveUpActionName());
        $reorderAction = $this->getAction($this->getReorderActionName());
        $collapseAllAction = $isDefault ? $this->getAction($this->getCollapseAllActionName()) : null;
        $expandAllAction = $isDefault ? $this->getAction($this->getExpandAllActionName()) : null;
        $extraItemActions = $this->getExtraItemActions();

        $isAddable = $this->isAddable();
        $isCloneable = $this->isCloneable();
        $isCollapsible = $isDefault && $this->isCollapsible();
        $isDeletable = $this->isDeletable();
        $isReorderableWithButtons = $this->isReorderableWithButtons();
        $isDragReorderable = $this->isReorderableWithDragAndDrop() && ($reorderAction?->isVisible() ?? false);
        $hasItemHeaders = $this->hasItemHeaders();
        $labelBetweenItems = $isDefault ? $this->getLabelBetweenItems() : null;
        $tableColumns = $isTable ? array_values($this->getTableColumns() ?? []) : [];

        if ($isDragReorderable) {
            $reorderAction->extraAttributes(['data-sortable-handle' => true], merge: true);
        }

        $itemRows = [];
        $index = 0;

        foreach ($items as $itemKey => $itemSchema) {
            $index++;
            $itemKey = (string) $itemKey;
            $isFirst = $index === 1;
            $isLast = $index === $itemCount;

            $visibleExtraItemActions = array_values(array_filter(
                array_map(static fn (Action $action): Action => $action(['item' => $itemKey]), $extraItemActions),
                static fn (Action $action): bool => $action->isVisible(),
            ));
            $itemCloneAction = $cloneAction?->__invoke(['item' => $itemKey]);
            $itemDeleteAction = $deleteAction?->__invoke(['item' => $itemKey]);
            $itemMoveUpAction = $moveUpAction?->__invoke(['item' => $itemKey])->disabled($isFirst);
            $itemMoveDownAction = $moveDownAction?->__invoke(['item' => $itemKey])->disabled($isLast);
            $itemAddBetweenAction = ($isAddable && ! $isLast) ? $addBetweenAction?->__invoke(['afterItem' => $itemKey]) : null;

            $rowReorderAction = $isDragReorderable ? $reorderAction : null;
            $rowMoveUpAction = ($isReorderableWithButtons && $itemMoveUpAction?->isVisible()) ? $itemMoveUpAction : null;
            $rowMoveDownAction = ($isReorderableWithButtons && $itemMoveDownAction?->isVisible()) ? $itemMoveDownAction : null;
            $rowCloneAction = ($isCloneable && $itemCloneAction?->isVisible()) ? $itemCloneAction : null;
            $rowDeleteAction = ($isDeletable && $itemDeleteAction?->isVisible()) ? $itemDeleteAction : null;
            $rowAddBetweenAction = ($itemAddBetweenAction?->isVisible() ?? false) ? $itemAddBetweenAction : null;
            $label = $this->getItemLabel($itemKey, $index - 1);

            $tableRow = $isTable ? $this->buildTableRow($itemSchema, $tableColumns) : ['cells' => [], 'hiddenComponents' => []];

            $itemRows[] = [
                'key' => $itemKey,
                'schema' => $itemSchema,
                'label' => $label,
                'isFirst' => $isFirst,
                'isLast' => $isLast,
                'isCollapsed' => $isCollapsible && $this->isCollapsed($itemSchema),
                'hasHeader' => $hasItemHeaders && (
                    $rowReorderAction instanceof Action || $rowMoveUpAction instanceof Action || $rowMoveDownAction instanceof Action
                    || filled($label) || $rowCloneAction instanceof Action || $rowDeleteAction instanceof Action
                    || $isCollapsible || $visibleExtraItemActions !== []
                ),
                'reorderAction' => $rowReorderAction,
                'moveUpAction' => $rowMoveUpAction,
                'moveDownAction' => $rowMoveDownAction,
                'cloneAction' => $rowCloneAction,
                'deleteAction' => $rowDeleteAction,
                'extraItemActions' => $visibleExtraItemActions,
                'addBetweenAction' => $rowAddBetweenAction,
                'cells' => $tableRow['cells'],
                'hiddenComponents' => $tableRow['hiddenComponents'],
            ];
        }

        return [
            'itemRows' => $itemRows,
            'addAction' => ($isAddable && $addAction?->isVisible()) ? $addAction : null,
            'hasController' => $isCollapsible || $isDragReorderable,
            'isCollapsible' => $isCollapsible,
            'persistCollapsed' => $isCollapsible && $this->shouldPersistCollapsed(),
            'isDragReorderable' => $isDragReorderable,
            'animationDuration' => $this->getReorderAnimationDuration(),
            'gridColumns' => $this->getGridColumns(),
            'collapseAllAction' => ($isCollapsible && $collapseAllAction?->isVisible()) ? $collapseAllAction : null,
            'expandAllAction' => ($isCollapsible && $expandAllAction?->isVisible()) ? $expandAllAction : null,
            'collapseAction' => $isCollapsible ? $this->getAction($this->getCollapseActionName()) : null,
            'expandAction' => $isCollapsible ? $this->getAction($this->getExpandActionName()) : null,
            'labelBetweenItems' => $labelBetweenItems,
            'tableColumns' => $tableColumns,
            'hasReorderColumn' => $isTable && $itemCount > 1 && ($isReorderableWithButtons || $this->isReorderableWithDragAndDrop()),
            'hasActionsColumn' => $isTable && ($extraItemActions !== [] || $isCloneable || $isDeletable),
            'isCompact' => $isTable && $this->isCompact(),
        ];
    }

    /**
     * Pair an item's components with the table columns by position — Filament's
     * counter: a Hidden field consumes no column and renders in the trailing hidden
     * cell, an invisible component still consumes one, components past the last
     * column are dropped.
     *
     * @param list<TableColumn> $tableColumns
     *
     * @return array{cells: list<array{component: Component, isVisible: bool, verticalAlignmentClass: ?string}>, hiddenComponents: list<Component>}
     */
    private function buildTableRow(Schema $itemSchema, array $tableColumns): array
    {
        $cells = [];
        $hiddenComponents = [];
        $counter = 0;

        foreach ($itemSchema->getComponents(withActions: true, withHidden: true) as $schemaComponent) {
            if (! $schemaComponent instanceof Component) {
                throw new LogicException(sprintf('Table repeaters must only contain schema components, but [%s] was used.', $schemaComponent::class));
            }

            if (count($tableColumns) <= $counter) {
                continue;
            }

            if ($schemaComponent instanceof Hidden) {
                $hiddenComponents[] = $schemaComponent;

                continue;
            }

            $counter++;
            $verticalAlignment = $tableColumns[$counter - 1]->getVerticalAlignment();

            $cells[] = [
                'component' => $schemaComponent,
                'isVisible' => $schemaComponent->isVisible(),
                'verticalAlignmentClass' => $verticalAlignment instanceof VerticalAlignment
                    ? "sy-vertical-align-{$verticalAlignment->value}"
                    : $verticalAlignment,
            ];
        }

        return ['cells' => $cells, 'hiddenComponents' => $hiddenComponents];
    }

    /**
     * The icon a user's alias override names, or the shipped default.
     */
    private function resolveActionIcon(string $alias, string $fallback): string|BackedEnum|Markup
    {
        return ($this->iconManager ??= new IconManager())->resolve($alias) ?? $fallback;
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

    /**
     * The item **template** of a relationship repeater is stamped with the
     * association's target class, so a component inside an item resolves the
     * child's class before any child exists to resolve — and only the template,
     * which is the `default` key every item schema is cloned from. A decoration
     * schema is the repeater's own, not an item's: neither edge walks one, so
     * framing it on the child would hand its closures a record its own component
     * never maps. The resolution is soft on purpose: this seam runs from every
     * structural read, and the fill and save edges own the refusals a mapping
     * failure earns.
     */
    protected function configureChildSchema(Schema $schema, string $key): Schema
    {
        $schema = parent::configureChildSchema($schema, $key);

        if (($key !== 'default') || ! $this->hasRelationship()) {
            return $schema;
        }

        return $schema->record(fn (): ?string => $this->getSoftRelationshipTargetClass());
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

    protected function getRelationshipFallbackAdvice(): string
    {
        return 'or drop relationship() and map the items onto the record by hand after getData().';
    }

    /**
     * The children the record holds, each as an item array keyed by the child's
     * record key — the identity a save diffs against. A relation-backed field
     * inside the item declares a load hook of its own that no walk of the
     * record's own graph could answer, so this runs it against the child and
     * leaves its path out of the leaf read.
     *
     * @return array<string, array<string, mixed>>
     */
    private function getItemsFromRelationship(object $record): array
    {
        $this->requireRelationshipCollectionTarget();

        $reader = $this->getAssociationReader();
        $accessor = $this->getPropertyAccessor();
        $itemStatePaths = $this->getItemStatePaths();

        $loadHooks = array_filter(
            $this->getTemplateNestedRelationshipHooks(),
            static fn (Component $component): bool => $component->hasLoadStateFromRelationships(),
        );

        $related = $accessor->getFromRecord($record, $this->getRelationshipNameOrFail());

        $items = [];

        foreach (is_iterable($related) ? $related : [] as $child) {
            if (! is_object($child)) {
                continue;
            }

            $itemData = [];

            foreach ($loadHooks as $hookPath => $component) {
                $accessor->setInArray($itemData, (string) $hookPath, $component->loadStateFromRelationships($child));
            }

            foreach ($itemStatePaths as $itemStatePath) {
                if ($this->isRoutedItemStatePath($itemStatePath, $loadHooks)) {
                    continue;
                }

                try {
                    $value = $accessor->getFromRecord($child, $itemStatePath);
                } catch (AccessException) {
                    continue;
                }

                if (is_object($value)) {
                    continue;
                }

                $accessor->setInArray($itemData, $itemStatePath, $value);
            }

            $items[self::RECORD_ITEM_KEY_PREFIX . $reader->getRecordKey($child)] = $this->requireFilledItemData($itemData);
        }

        return $items;
    }

    /**
     * Map the submitted items back onto the association: each item is written
     * onto the child it names or onto a freshly constructed one, and the whole
     * desired list is handed to the accessor in state order, whose adder/remover
     * diffing leaves the kept children untouched and drops the rest. A
     * relation-backed field inside the item is handed the child instead of a leaf
     * write, after the item's own leaves have settled and before the collection
     * write, so the identity pairing that follows sees finished children.
     */
    private function saveItemsToRelationship(object $record, mixed $state): void
    {
        $target = $this->requireRelationshipCollectionTarget();
        $name = $this->getRelationshipNameOrFail();
        $reader = $this->getAssociationReader();
        $accessor = $this->getPropertyAccessor();
        $itemStatePaths = $this->getItemStatePaths();
        $orderColumn = $this->getOrderColumn();
        $routesSaveHooks = array_any(
            $this->getTemplateNestedRelationshipHooks(),
            static fn (Component $component): bool => $component->hasSaveRelationships(),
        );

        $related = $accessor->getFromRecord($record, $name);

        $existingRecords = [];

        foreach (is_iterable($related) ? $related : [] as $child) {
            if (is_object($child)) {
                $existingRecords[$reader->getRecordKey($child)] = $child;
            }
        }

        $desiredRecords = [];
        $desiredItemKeys = [];

        foreach (is_array($state) ? $state : [] as $itemKey => $itemData) {
            /** @var array<string, mixed> $itemData */
            $itemData = is_array($itemData) ? $itemData : [];

            $child = $this->findRelationshipItemRecord((string) $itemKey, $existingRecords);

            $mutated = ($child === null)
                ? $this->mutateRelationshipItemData($this->mutateRelationshipDataBeforeCreateUsing, $itemData)
                : $this->mutateRelationshipItemData($this->mutateRelationshipDataBeforeSaveUsing, $itemData, $child);

            if ($mutated === null) {
                if ($child !== null) {
                    $desiredRecords[] = $child;
                    $desiredItemKeys[] = (string) $itemKey;
                }

                continue;
            }

            $itemData = $mutated;

            $child ??= $accessor->constructRecord(
                $target->targetClass,
                sprintf('an added item of the [%s] repeater onto a new record', (string) $this->getName()),
                'drop relationship() and add the records by hand after getData().',
            );

            $saveHooks = $routesSaveHooks
                ? array_filter(
                    $this->getItemSchemaOrFail((string) $itemKey)->getNestedRelationshipHooks(),
                    static fn (Component $component): bool => $component->hasSaveRelationships(),
                )
                : [];

            foreach ($itemStatePaths as $itemStatePath) {
                if ($this->isRoutedItemStatePath($itemStatePath, $saveHooks)) {
                    continue;
                }

                if ($accessor->hasInArray($itemData, $itemStatePath)) {
                    $accessor->setOnRecord($child, $itemStatePath, $accessor->getFromArray($itemData, $itemStatePath));
                }
            }

            foreach ($saveHooks as $hookPath => $component) {
                $hookPath = (string) $hookPath;

                if ($accessor->hasInArray($itemData, $hookPath)) {
                    $component->saveRelationships($child, $accessor->getFromArray($itemData, $hookPath));
                }
            }

            if ($orderColumn !== null) {
                $accessor->setOnRecord($child, $orderColumn, count($desiredRecords));
            }

            $desiredRecords[] = $child;
            $desiredItemKeys[] = (string) $itemKey;
        }

        $accessor->setAssociationOnRecord($record, $name, $desiredRecords);

        $this->itemKeysByCollectionKey = $this->readItemKeysByCollectionKey($record, $name, $desiredRecords, $desiredItemKeys);
    }

    /**
     * The relationship hooks one item declares, keyed by their item-relative
     * paths — read off the template, because a fill runs before a single item
     * exists to read from. The same walk answers
     * {@see routesNestedRelationshipHookAt()}, so what the mapper is told is
     * routed and what the fill routes are one set.
     *
     * @return array<string, Component>
     */
    private function getTemplateNestedRelationshipHooks(): array
    {
        return $this->getChildSchema()?->getClone()->getNestedRelationshipHooks() ?? [];
    }

    /**
     * The schema of a submitted item, whose components are the very ones the
     * record path map dropped in this repeater's favour. An item key the built
     * ones do not cover — one a caller added to the state it handed `getData()`
     * rather than to the repeater's own — is built the same way they are, so a
     * hand-supplied item routes the hooks its template declares exactly as a
     * rendered one does.
     *
     * @throws LogicException when the repeater declares no item schema at all,
     *                        leaving the hooks the template carries nothing to
     *                        run from
     */
    private function getItemSchemaOrFail(string $itemKey): Schema
    {
        return $this->getChildSchema($itemKey)
            ?? $this->makeItemSchema($itemKey)
            ?? throw new LogicException(sprintf(
                'The [%s] repeater is saving item "%s" and has no item schema to route the relationship hooks beneath it from. Give the repeater components(), or drop the hook.',
                (string) $this->getName(),
                $itemKey,
            ));
    }

    /**
     * One item's schema, re-pathed onto its key and — under relationship(), for
     * an item naming a child — stamped with the child it maps, falling back to
     * the target class the template already carries.
     */
    private function makeItemSchema(string $itemKey): ?Schema
    {
        $itemSchema = $this->getChildSchema()?->statePath($itemKey)->key($itemKey)->getClone();

        if (! $itemSchema instanceof Schema) {
            return null;
        }

        if ($this->hasRelationship() && str_starts_with($itemKey, self::RECORD_ITEM_KEY_PREFIX)) {
            $itemSchema->record(fn (): object|string|null => $this->findItemRecordByKey($itemKey) ?? $this->getSoftRelationshipTargetClass());
        }

        return $itemSchema;
    }

    /**
     * Whether an item's leaf path is one a nested hook already speaks for — the
     * hook's own path or anything beneath it — so the leaf walk leaves it alone.
     * Subtraction is per edge: a save-only hook keeps its ordinary leaf read on
     * the fill side, and a load-only one its ordinary write on the save side.
     *
     * @param array<string, Component> $routedHooks
     */
    private function isRoutedItemStatePath(string $itemStatePath, array $routedHooks): bool
    {
        foreach (array_keys($routedHooks) as $hookPath) {
            $hookPath = (string) $hookPath;

            if (($itemStatePath === $hookPath) || str_starts_with($itemStatePath, $hookPath . '.')) {
                return true;
            }
        }

        return false;
    }

    /**
     * The child an item key names, read off the collection the owner record
     * holds — null where the item names none, the owner is unbound, or the
     * association cannot be read at all, since the stamps this answers are
     * evaluated from structural walks rather than from the mapping edges.
     */
    private function findItemRecordByKey(string $itemKey): ?object
    {
        if (! str_starts_with($itemKey, self::RECORD_ITEM_KEY_PREFIX)) {
            return null;
        }

        $record = $this->getRecord();
        $name = $this->getRelationshipName();

        if (! is_object($record) || ! filled($name)) {
            return null;
        }

        try {
            $related = $this->getPropertyAccessor()->getFromRecord($record, $name);
        } catch (AccessException) {
            return null;
        }

        $recordKey = mb_substr($itemKey, mb_strlen(self::RECORD_ITEM_KEY_PREFIX));
        $reader = $this->getAssociationReader();

        foreach (is_iterable($related) ? $related : [] as $child) {
            if (is_object($child) && ($reader->findRecordKey($child) === $recordKey)) {
                return $child;
            }
        }

        return null;
    }

    /**
     * The class the association targets, or null where the mapping cannot say —
     * the never-throwing twin of {@see requireRelationshipCollectionTarget()},
     * because the record stamps are read from rendering and from every state
     * walk, and an eager refusal there would move the fill and save edges'
     * refusals into all of them.
     *
     * @return class-string|null
     */
    private function getSoftRelationshipTargetClass(): ?string
    {
        $recordClass = $this->getRecordClass();
        $name = $this->getRelationshipName();

        if (($recordClass === null) || ! filled($name)) {
            return null;
        }

        return $this->getAssociationReader()->describe($recordClass, $name)?->targetClass;
    }

    /**
     * Pair each element of the collection the record now holds with the item key
     * it was written from, read back **after** the write: the accessor diffs the
     * desired list through the record's adder and remover, which leaves an
     * unchanged membership in its old order and a thinned one keyed sparsely, so
     * the keys a violation path will carry are only knowable from the collection
     * itself. Identity is the pairing — a key of its own would miss an item the
     * client added, which has none until a flush.
     *
     * @param list<object> $desiredRecords
     * @param list<string> $desiredItemKeys
     *
     * @return array<int|string, string>
     */
    private function readItemKeysByCollectionKey(object $record, string $name, array $desiredRecords, array $desiredItemKeys): array
    {
        $related = $this->getPropertyAccessor()->getFromRecord($record, $name);

        $itemKeys = [];

        foreach (is_iterable($related) ? $related : [] as $collectionKey => $child) {
            if (! is_int($collectionKey) && ! is_string($collectionKey)) {
                continue;
            }

            $index = array_search($child, $desiredRecords, true);

            if ($index === false) {
                continue;
            }

            $itemKeys[$collectionKey] = $desiredItemKeys[$index];
        }

        return $itemKeys;
    }

    /**
     * @throws LogicException when the association holds a single record rather
     *                        than a collection, or when removing an item from it
     *                        would leave the record behind instead of deleting it
     */
    private function requireRelationshipCollectionTarget(): AssociationTarget
    {
        $target = $this->getRelationshipTarget();

        if (! $target->isToMany) {
            throw new LogicException(sprintf(
                'The [%s] repeater declares relationship("%s"), but that association holds a single %s rather than a collection, so there is nothing to repeat. Use a Select for a to-one association.',
                (string) $this->getName(),
                $this->getRelationshipNameOrFail(),
                $target->targetClass,
            ));
        }

        if (! $target->isOrphanRemoval) {
            throw new LogicException(sprintf(
                'The [%s] repeater declares relationship("%s"), but that association is not mapped orphanRemoval, so an item the user deletes would only be detached and would survive the flush. Map the association orphanRemoval: true — a repeater edits records the parent owns — or drop relationship() and diff the collection by hand after getData().',
                (string) $this->getName(),
                $this->getRelationshipNameOrFail(),
            ));
        }

        return $target;
    }

    /**
     * The loaded child an item names, or null when the item is one the client
     * added.
     *
     * @param array<string, object> $existingRecords
     *
     * @throws LogicException when the item names a child the association no
     *                        longer holds
     */
    private function findRelationshipItemRecord(string $itemKey, array $existingRecords): ?object
    {
        if (! str_starts_with($itemKey, self::RECORD_ITEM_KEY_PREFIX)) {
            return null;
        }

        $recordKey = mb_substr($itemKey, mb_strlen(self::RECORD_ITEM_KEY_PREFIX));

        return $existingRecords[$recordKey] ?? throw new LogicException(sprintf(
            'The [%s] repeater carries item "%s", but the [%s] association no longer holds a record with that identifier — it was deleted or detached since the schema was filled. Fill the schema from the record again before saving.',
            (string) $this->getName(),
            $itemKey,
            $this->getRelationshipNameOrFail(),
        ));
    }

    /**
     * One loaded child as an item array, after the fill callback has had it.
     * Only the create and save callbacks may decline an item; a fill has an item
     * to show either way, so a null here is a mistake rather than a skip.
     *
     * @param array<string, mixed> $itemData
     *
     * @return array<string, mixed>
     *
     * @throws LogicException when the fill callback returns null
     */
    private function requireFilledItemData(array $itemData): array
    {
        return $this->mutateRelationshipItemData($this->mutateRelationshipDataBeforeFillUsing, $itemData)
            ?? throw new LogicException(sprintf(
                'The [%s] repeater\'s mutateRelationshipDataBeforeFillUsing callback returned null, but a fill has no item to skip — only the create and save callbacks may decline one. Return the array the callback was handed.',
                (string) $this->getName(),
            ));
    }

    /**
     * The item array a mutation callback hands back, or null where it declined
     * the item — `$record` is the child the item names, injected by name and by
     * its own class so a hook may type-hint the entity.
     *
     * @param array<string, mixed> $itemData
     *
     * @return array<string, mixed>|null
     *
     * @throws LogicException when the callback returns something other than the
     *                        item array or null
     */
    private function mutateRelationshipItemData(?Closure $callback, array $itemData, ?object $record = null): ?array
    {
        if (! $callback instanceof Closure) {
            return $itemData;
        }

        return $this->requireMutatedItemData($this->evaluate(
            $callback,
            ($record === null) ? ['state' => $itemData] : ['state' => $itemData, 'record' => $record],
            ($record === null) ? [] : [$record::class => $record],
        ));
    }

    /**
     * @return array<string, mixed>|null
     *
     * @throws LogicException when a mutation callback returned something other
     *                        than the item array or null
     */
    private function requireMutatedItemData(mixed $mutated): ?array
    {
        if ($mutated === null) {
            return null;
        }

        if (! is_array($mutated)) {
            throw new LogicException(sprintf(
                'A relationship mutation callback on the [%s] repeater returned %s where the mutated item array was expected. Return the array the callback was handed, or null to skip the item.',
                (string) $this->getName(),
                get_debug_type($mutated),
            ));
        }

        /** @var array<string, mixed> $mutated */
        return $mutated;
    }

    /**
     * A repeater bound to an object collection would render empty and meet the
     * mapper's refusal only on first save, so it refuses at hydration instead.
     *
     * @throws LogicException when the record declares an object collection at this
     *                        component's record path
     */
    private function refuseObjectCollectionRecord(): void
    {
        if ($this->hasRelationship()) {
            return;
        }

        if (! isset($this->container)) {
            return;
        }

        $recordClass = $this->getNearestRecordClass();
        $recordPath = $this->getNearestRecordStatePath();

        if (($recordClass === null) || ($recordPath === null)) {
            return;
        }

        $target = $this->getRecordTypeReader()->describe($recordClass, $recordPath);

        if (! $target instanceof RecordTarget || ! $target->isObjectCollection) {
            return;
        }

        $segments = explode('.', $recordPath);

        throw new LogicException(sprintf(
            'Cannot map state path "%s" onto the %s framing it: "%s" is an object collection. Collection items need identity mapping (which state item is which entity), which only relationship() supplies — the Repeater maps plain-array items otherwise. Declare relationship() on this repeater, or map this property by hand after getData().',
            $recordPath,
            $recordClass,
            $segments[count($segments) - 1],
        ));
    }
}
