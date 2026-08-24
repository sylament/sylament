<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Components;

use LogicException;
use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\Repeater\TableColumn;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Records\Article;
use Sylament\Forms\Tests\Fixtures\Records\TaggedPost;
use Sylament\Schemas\Schema;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Type;

/**
 * Wire a repeater under a `data`-bag host, optionally binding the root schema to
 * a record class, so the repeater reads and writes a real raw-state slice. The
 * host is returned alongside so a test can read the bag back after a mutation.
 *
 * @param array<string, mixed> $data
 * @param class-string|null    $recordClass
 *
 * @return array{Repeater, FieldRenderHost}
 */
function wireRepeater(Repeater $repeater, array $data = [], ?string $recordClass = null): array
{
    $host = new FieldRenderHost();
    $host->data = $data;

    $schema = Schema::make($host)->statePath('data');

    if ($recordClass !== null) {
        $schema->record($recordClass);
    }

    $schema->components([$repeater])->getComponents();

    return [$repeater, $host];
}

it('names its own state path and owns its record subtree', function (): void {
    Repeater::fake();

    [$repeater] = wireRepeater(Repeater::make('tags'));

    expect($repeater->getStatePath())->toBe('data.tags')
        ->and($repeater->ownsRecordSubtree())->toBeTrue();
});

it('re-keys the raw state into items on hydration', function (): void {
    Repeater::fake();

    [$repeater, $host] = wireRepeater(
        Repeater::make('tags')->components([TextInput::make('name')]),
        ['tags' => [['name' => 'a'], ['name' => 'b']]],
    );

    $repeater->hydrateItems();

    expect($host->data['tags'])->toBe([['name' => 'a'], ['name' => 'b']]);
});

it('builds one item schema per raw-state key', function (): void {
    Repeater::fake();

    $template = TextInput::make('name');

    [$repeater] = wireRepeater(
        Repeater::make('tags')->components([$template]),
        ['tags' => [['name' => 'a'], ['name' => 'b']]],
    );

    $items = $repeater->getItems();

    expect(array_keys($items))->toBe([0, 1]);

    $first = $items[0]->getComponents()[0];
    $second = $items[1]->getComponents()[0];

    expect($first)->not->toBe($second)
        ->and($first->getStatePath())->toBe('data.tags.0.name')
        ->and($second->getStatePath())->toBe('data.tags.1.name')
        ->and($first)->not->toBe($template)
        ->and($template->getName())->toBe('name');
});

it('resolves an item schema by its key and answers null for an unknown one', function (): void {
    Repeater::fake();

    [$repeater] = wireRepeater(
        Repeater::make('tags')->components([TextInput::make('name')]),
        ['tags' => [['name' => 'a']]],
    );

    expect($repeater->getChildSchema('0'))->toBe($repeater->getItems()[0])
        ->and($repeater->getChildSchema('nope'))->toBeNull();
});

it('resolves each item field against the root schema', function (): void {
    Repeater::fake();

    $host = new FieldRenderHost();
    $host->data = ['tags' => [['name' => 'a']]];

    $root = Schema::make($host)->statePath('data');
    $repeater = Repeater::make('tags')->components([TextInput::make('name')]);
    $root->components([$repeater])->getComponents();

    expect($repeater->getItems()[0]->getComponents()[0]->getRootContainer())->toBe($root);
});

it('keeps item schemas until the raw-state structure changes', function (): void {
    Repeater::fake();

    [$repeater, $host] = wireRepeater(
        Repeater::make('tags')->components([TextInput::make('name')]),
        ['tags' => [['name' => 'a']]],
    );

    $first = $repeater->getItems();

    expect($repeater->getItems())->toBe($first);

    $host->data['tags'][0]['name'] = 'edited';

    expect($repeater->getItems())->toBe($first);

    $host->data['tags'][] = ['name' => 'b'];

    $rebuilt = $repeater->getItems();

    expect($rebuilt)->not->toBe($first)
        ->and(array_keys($rebuilt))->toBe([0, 1]);
});

it('dehydrates the items back into a positional list', function (): void {
    Repeater::fake();

    $host = new FieldRenderHost();
    $host->data = ['tags' => ['a' => ['name' => 'first'], 'b' => ['name' => 'second']]];

    $schema = Schema::make($host)->statePath('data');
    $schema->components([Repeater::make('tags')->components([TextInput::make('name')])])->getComponents();

    expect($schema->getStateSnapshot())->toBe(['tags' => [['name' => 'first'], ['name' => 'second']]]);
});

it('wraps and plucks a scalar item list in simple mode', function (): void {
    Repeater::fake();

    $host = new FieldRenderHost();
    $host->data = ['tags' => ['x', 'y']];

    $schema = Schema::make($host)->statePath('data');
    $repeater = Repeater::make('tags')->simple(TextInput::make('value'));
    $schema->components([$repeater])->getComponents();

    $repeater->hydrateItems();

    expect($repeater->isSimple())->toBeTrue()
        ->and($host->data['tags'])->toBe([['value' => 'x'], ['value' => 'y']])
        ->and($schema->getStateSnapshot())->toBe(['tags' => ['x', 'y']]);
});

it('hides the label of the simple field', function (): void {
    Repeater::fake();

    [$repeater] = wireRepeater(Repeater::make('tags')->simple(TextInput::make('value')));

    expect($repeater->getSimpleField()?->isLabelHidden())->toBeTrue();
});

it('fills empty items from defaultItems on a defaults hydration', function (): void {
    Repeater::fake();

    $host = new FieldRenderHost();

    $schema = Schema::make($host)->statePath('data');
    $schema->components([Repeater::make('tags')->defaultItems(2)->components([TextInput::make('name')])])->getComponents();

    $schema->fill();

    expect($host->data['tags'])->toBe([['name' => null], ['name' => null]]);
});

it('merges a declared default with the per-field hydration pass', function (): void {
    Repeater::fake();

    $host = new FieldRenderHost();

    $schema = Schema::make($host)->statePath('data');
    $schema->components([
        Repeater::make('tags')
            ->default([['name' => 'seeded']])
            ->components([TextInput::make('name')]),
    ])->getComponents();

    $schema->fill();

    expect($host->data['tags'])->toBe([['name' => 'seeded']]);
});

/**
 * Characterization of today's behaviour, not a shape anyone wants: a no-arg
 * `fill()` over an *already populated* repeater leaves the pre-fill items
 * standing as all-null ghosts beside the default one. Recorded in
 * `docs/progress/forms/repeater.md` ("A defaults fill over a populated repeater
 * resurrects its pre-fill items"), unreachable from every no-arg `fill()` caller
 * that ships — so changing this assertion means the fix landed, not that the
 * test drifted.
 */
it('characterizes the ghost items a defaults fill leaves over a populated repeater', function (): void {
    $host = new FieldRenderHost();
    $host->data = ['tags' => [['name' => 'a'], ['name' => 'b']]];

    $schema = Schema::make($host)->statePath('data');
    $schema->components([
        Repeater::make('tags')
            ->generateUuidUsing(static fn (): string => 'fresh')
            ->defaultItems(1)
            ->components([TextInput::make('name')]),
    ])->getComponents();

    $schema->fill();

    expect($host->data['tags'])->toBe([
        'fresh' => ['name' => null],
        0 => ['name' => null],
        1 => ['name' => null],
    ]);
});

it('reads a single item state slice', function (): void {
    Repeater::fake();

    [$repeater] = wireRepeater(
        Repeater::make('tags')->components([TextInput::make('name')]),
        ['tags' => [['name' => 'a'], ['name' => 'b']]],
    );

    expect($repeater->getItemState('1'))->toBe(['name' => 'b'])
        ->and($repeater->getRawItemState('1'))->toBe(['name' => 'b'])
        ->and($repeater->getItemState('nope'))->toBe([]);
});

it('refuses an object collection at hydration', function (): void {
    Repeater::fake();

    [$repeater] = wireRepeater(
        Repeater::make('tags')->components([TextInput::make('name')]),
        [],
        TaggedPost::class,
    );

    expect(fn (): mixed => $repeater->hydrateItems())
        ->toThrow(LogicException::class, 'is an object collection');
});

it('hydrates against a plain array record property', function (): void {
    Repeater::fake();

    [$repeater, $host] = wireRepeater(
        Repeater::make('tags')->components([TextInput::make('name')]),
        ['tags' => [['name' => 'a']]],
        Article::class,
    );

    $repeater->hydrateItems();

    expect($host->data['tags'])->toBe([['name' => 'a']]);
});

it('gates adding on the item limit and the disabled flag', function (): void {
    Repeater::fake();

    [$full] = wireRepeater(Repeater::make('tags')->maxItems(2), ['tags' => [[], []]]);
    [$room] = wireRepeater(Repeater::make('tags')->maxItems(3), ['tags' => [[], []]]);
    [$disabled] = wireRepeater(Repeater::make('tags')->disabled(), ['tags' => [[]]]);

    expect($full->isAddable())->toBeFalse()
        ->and($room->isAddable())->toBeTrue()
        ->and($disabled->isAddable())->toBeFalse()
        ->and($disabled->isDeletable())->toBeFalse()
        ->and($disabled->isReorderable())->toBeFalse();
});

it('keeps button reordering opt-in behind reorderability', function (): void {
    Repeater::fake();

    [$default] = wireRepeater(Repeater::make('tags'));
    [$buttons] = wireRepeater(Repeater::make('tags')->reorderableWithButtons());
    [$off] = wireRepeater(Repeater::make('tags')->reorderable(false)->reorderableWithButtons());

    expect($default->isReorderableWithButtons())->toBeFalse()
        ->and($buttons->isReorderableWithButtons())->toBeTrue()
        ->and($off->isReorderableWithButtons())->toBeFalse();
});

it('appends the length constraints only when a limit is declared', function (): void {
    Repeater::fake();

    [$unlimited] = wireRepeater(Repeater::make('tags'));
    [$limited] = wireRepeater(Repeater::make('tags')->minItems(1)->maxItems(3));

    $constraints = $limited->getValidationConstraints();

    expect($unlimited->getValidationConstraints())->toBe([])
        ->and($constraints)->toHaveCount(2)
        ->and($constraints[0])->toBeInstanceOf(Type::class)
        ->and($constraints[0]->type)->toBe('array')
        ->and($constraints[1])->toBeInstanceOf(Count::class)
        ->and($constraints[1]->min)->toBe(1)
        ->and($constraints[1]->max)->toBe(3);
});

it('carries the item chrome flags', function (): void {
    Repeater::fake();

    [$plain] = wireRepeater(Repeater::make('tags'));
    [$labelled] = wireRepeater(
        Repeater::make('tags')
            ->components([TextInput::make('name')])
            ->itemLabel(fn (array $state): string => (string) $state['name'])
            ->itemNumbers()
            ->itemHeaders(false)
            ->truncateItemLabel(false),
        ['tags' => [['name' => 'a']]],
    );

    expect($plain->hasItemLabels())->toBeFalse()
        ->and($plain->getHeadingsCount())->toBe(0)
        ->and($plain->hasItemNumbers())->toBeFalse()
        ->and($plain->hasItemHeaders())->toBeTrue()
        ->and($plain->isItemLabelTruncated())->toBeTrue()
        ->and($labelled->hasItemLabels())->toBeTrue()
        ->and($labelled->getHeadingsCount())->toBe(1)
        ->and($labelled->hasItemNumbers())->toBeTrue()
        ->and($labelled->hasItemHeaders())->toBeFalse()
        ->and($labelled->isItemLabelTruncated())->toBeFalse()
        ->and($labelled->getItemLabel('0', 0))->toBe('a');
});

it('registers the eleven structural actions under their Filament names', function (): void {
    [$repeater] = wireRepeater(Repeater::make('tags')->components([TextInput::make('name')]));

    expect(array_keys($repeater->getActions()))->toBe(['add', 'addBetween', 'clone', 'collapse', 'collapseAll', 'delete', 'expand', 'expandAll', 'moveDown', 'moveUp', 'reorder'])
        ->and($repeater->getAddBetweenActionLabel())->toBe('Insert between')
        ->and($repeater->addBetweenActionLabel('Squeeze one in')->getAddBetweenActionLabel())->toBe('Squeeze one in')
        ->and($repeater->getAction('addBetween')?->isVisible())->toBeFalse()
        ->and($repeater->getAction('reorder')?->isLiveComponentClickHandlerEnabled())->toBeFalse()
        ->and($repeater->getAction('collapse')?->isLiveComponentClickHandlerEnabled())->toBeFalse()
        ->and($repeater->getAction('collapseAll')?->getLabel())->toBe('Collapse all')
        ->and($repeater->getAction('expandAll')?->getLabel())->toBe('Expand all');
});

it('drag-reorders by default and only when reorderable at all', function (): void {
    // isReorderable() reaches CanBeDisabled::isDisabled() → getContainer(), so every repeater is wired.
    Repeater::fake();
    $wired = static fn (Repeater $repeater): Repeater => wireRepeater($repeater)[0];

    expect($wired(Repeater::make('a'))->isReorderableWithDragAndDrop())->toBeTrue()
        ->and($wired(Repeater::make('a'))->isReorderableWithButtons())->toBeFalse()
        ->and($wired(Repeater::make('a')->reorderable(false))->isReorderableWithDragAndDrop())->toBeFalse()
        ->and($wired(Repeater::make('a')->reorderableWithDragAndDrop(false))->isReorderableWithDragAndDrop())->toBeFalse()
        ->and($wired(Repeater::make('a')->disabled())->isReorderableWithDragAndDrop())->toBeFalse();
});

it("ties a relationship repeater's reorderability to its order column, in Filament's order of calls", function (): void {
    Repeater::fake();
    $wired = static fn (Repeater $repeater): Repeater => wireRepeater($repeater, [], TaggedPost::class)[0];

    expect($wired(Repeater::make('tags')->relationship())->isReorderable())->toBeFalse()
        ->and($wired(Repeater::make('tags')->relationship()->orderColumn('position'))->isReorderable())->toBeTrue()
        ->and($wired(Repeater::make('tags')->orderColumn('position')->relationship())->isReorderable())->toBeFalse()
        ->and($wired(Repeater::make('tags')->orderColumn(null))->isReorderable())->toBeFalse();
});

it('is a table only with columns, and table wins over simple', function (): void {
    expect(Repeater::make('a')->isTable())->toBeFalse()
        ->and(Repeater::make('a')->table([])->isTable())->toBeFalse()
        ->and(Repeater::make('a')->table([TableColumn::make('Name')])->isTable())->toBeTrue()
        ->and(Repeater::make('a')->table(static fn (): array => [TableColumn::make('Name')])->getTemplate())->toBe(Repeater::TABLE_TEMPLATE)
        ->and(Repeater::make('a')->simple(TextInput::make('v'))->table([TableColumn::make('Name')])->getTemplate())->toBe(Repeater::TABLE_TEMPLATE)
        ->and(Repeater::make('a')->simple(TextInput::make('v'))->getTemplate())->toBe(Repeater::SIMPLE_TEMPLATE);
});

it('conceals its children exactly when collapsible', function (): void {
    expect(Repeater::make('a')->canConcealComponents())->toBeFalse()
        ->and(Repeater::make('a')->collapsible()->canConcealComponents())->toBeTrue()
        ->and(Repeater::make('a')->collapsed()->canConcealComponents())->toBeTrue()
        ->and(Repeater::make('a')->collapsed(shouldMakeComponentCollapsible: false)->canConcealComponents())->toBeFalse();
});

it('carries the items grid and the label between items', function (): void {
    expect(Repeater::make('a')->grid(2)->getGridColumns())->toBe(['lg' => 2])
        ->and(Repeater::make('a')->labelBetweenItems('and')->getLabelBetweenItems())->toBe('and')
        ->and(Repeater::make('a')->getLabelBetweenItems())->toBeNull()
        ->and(Repeater::make('a')->getReorderAnimationDuration())->toBe(300);
});
