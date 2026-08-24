<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Sylament\Actions\Action;
use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\TextInput;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

use function Sylament\Forms\Tests\renderRepeater;

uses(InteractsWithLiveComponents::class);

/*
 * The three repeater partials through the real Twig stack. Items are seeded on the
 * host bag rather than hydrated, so they stay positionally keyed and the ids the
 * template composes are readable in an assertion; a real mount keys them by uuid,
 * which is why nothing here asserts a literal key.
 */

it('renders one item block per item, each carrying a stable id', function (): void {
    $repeater = Repeater::make('tags')->components([TextInput::make('name')]);

    $crawler = renderRepeater($repeater, ['tags' => [['name' => 'a'], ['name' => 'b']]]);

    $items = $crawler->filter('.sy-fo-repeater-item');

    expect($items->count())->toBe(2)
        ->and($items->extract(['id']))->toBe([
            $repeater->getKey() . '.0',
            $repeater->getKey() . '.1',
        ])
        ->and($crawler->filter('.sy-fo-repeater-item-content input')->extract(['id', 'data-model']))->toBe([
            [$repeater->getKey() . '.0.name', 'norender|data.tags.0.name'],
            [$repeater->getKey() . '.1.name', 'norender|data.tags.1.name'],
        ]);
});

it('labels the repeater group with a heading instead of a label element', function (): void {
    $repeater = Repeater::make('tags')->components([TextInput::make('name')]);

    $crawler = renderRepeater($repeater, ['tags' => [['name' => 'a']]]);

    $group = $crawler->filter('.sy-fo-repeater');
    $labelId = $repeater->getId() . '-label';

    expect($group->attr('role'))->toBe('group')
        ->and($group->attr('aria-labelledby'))->toBe($labelId)
        ->and($crawler->filter('div#' . $labelId . '.sy-fo-field-label')->count())->toBe(1)
        ->and($crawler->filter('label[for="' . $repeater->getId() . '"]')->count())->toBe(0);
});

it('renders the delete trigger and no move triggers by default', function (): void {
    $crawler = renderRepeater(
        Repeater::make('tags')->components([TextInput::make('name')]),
        ['tags' => [['name' => 'a'], ['name' => 'b']]],
    );

    expect($crawler->filter('.sy-fo-repeater-item-header [aria-label="Delete"]')->count())->toBe(2)
        ->and($crawler->filter('[aria-label="Move up"]')->count())->toBe(0)
        ->and($crawler->filter('[aria-label="Move down"]')->count())->toBe(0)
        ->and($crawler->filter('[aria-label="Clone"]')->count())->toBe(0);
});

it('renders the move and clone triggers once their surfaces are on', function (): void {
    $crawler = renderRepeater(
        Repeater::make('tags')->components([TextInput::make('name')])->reorderableWithButtons()->cloneable(),
        ['tags' => [['name' => 'a'], ['name' => 'b']]],
    );

    expect($crawler->filter('[aria-label="Move up"]')->count())->toBe(2)
        ->and($crawler->filter('[aria-label="Move down"]')->count())->toBe(2)
        ->and($crawler->filter('[aria-label="Clone"]')->count())->toBe(2)
        ->and($crawler->filter('[aria-label="Move up"]')->first()->attr('disabled'))->not->toBeNull()
        ->and($crawler->filter('[aria-label="Move down"]')->last()->attr('disabled'))->not->toBeNull();
});

it('renders no item header when every header surface is off', function (): void {
    $crawler = renderRepeater(
        Repeater::make('tags')->components([TextInput::make('name')])->deletable(false)->reorderable(false),
        ['tags' => [['name' => 'a']]],
    );

    expect($crawler->filter('.sy-fo-repeater-item-header')->count())->toBe(0)
        ->and($crawler->filter('.sy-fo-repeater-item-has-header')->count())->toBe(0)
        ->and($crawler->filter('.sy-fo-repeater-item')->count())->toBe(1);
});

it('renders the item label, numbered when item numbers are on', function (): void {
    $crawler = renderRepeater(
        Repeater::make('tags')->components([TextInput::make('name')])->itemLabel('Row')->itemNumbers(),
        ['tags' => [['name' => 'a'], ['name' => 'b']]],
    );

    $labels = $crawler->filter('.sy-fo-repeater-item-header-label');

    expect($labels->count())->toBe(2)
        ->and($labels->first()->text())->toBe('Row 1')
        ->and($labels->last()->text())->toBe('Row 2')
        ->and($labels->first()->attr('class'))->toContain('sy-truncated');
});

it('renders the add trigger and drops it once maxItems is reached', function (): void {
    $addable = renderRepeater(
        Repeater::make('tags')->components([TextInput::make('name')])->maxItems(2),
        ['tags' => [['name' => 'a']]],
    );

    expect($addable->filter('.sy-fo-repeater-add')->count())->toBe(1)
        ->and($addable->filter('.sy-fo-repeater-add button')->text())->toContain('Add to tags');

    $full = renderRepeater(
        Repeater::make('tags')->components([TextInput::make('name')])->maxItems(2),
        ['tags' => [['name' => 'a'], ['name' => 'b']]],
    );

    expect($full->filter('.sy-fo-repeater-add')->count())->toBe(0);
});

it('aligns the add trigger container when an alignment is declared', function (): void {
    $crawler = renderRepeater(
        Repeater::make('tags')->components([TextInput::make('name')])->addActionAlignment('end'),
        ['tags' => []],
    );

    expect($crawler->filter('.sy-fo-repeater-add')->attr('class'))->toContain('sy-align-end');
});

it('renders no item list but keeps the add trigger while empty', function (): void {
    $crawler = renderRepeater(
        Repeater::make('tags')->components([TextInput::make('name')]),
        ['tags' => []],
    );

    expect($crawler->filter('.sy-fo-repeater-items')->count())->toBe(0)
        ->and($crawler->filter('.sy-fo-repeater-item')->count())->toBe(0)
        ->and($crawler->filter('.sy-fo-repeater-add')->count())->toBe(1);
});

it('renders the simple variant without a label on the inner field', function (): void {
    $crawler = renderRepeater(
        Repeater::make('tags')->simple(TextInput::make('value')),
        ['tags' => ['a', 'b']],
    );

    expect($crawler->filter('.sy-fo-simple-repeater')->count())->toBe(1)
        ->and($crawler->filter('.sy-fo-repeater')->count())->toBe(0)
        ->and($crawler->filter('.sy-fo-simple-repeater-item')->count())->toBe(2)
        ->and($crawler->filter('.sy-fo-simple-repeater-item input')->count())->toBe(2)
        ->and($crawler->filter('.sy-fo-simple-repeater-item .sy-fo-field-label:not(.sy-fo-sr-only)')->count())->toBe(0)
        ->and($crawler->filter('.sy-fo-simple-repeater-item-actions [aria-label="Delete"]')->count())->toBe(2);
});

it('renders an item field error inside that item block', function (): void {
    $component = $this->createLiveComponent('ValidatedRepeaterForm', ['validatedSchemas' => ['schema']]);
    $component->set('data', ['tags' => [['name' => 'a'], ['name' => null]]]);

    $crawler = $component->render()->crawler();

    $items = $crawler->filter('.sy-fo-repeater-item');

    expect($items->count())->toBe(2)
        ->and($items->first()->filter('.sy-fo-field-error-message')->count())->toBe(0)
        ->and($items->last()->filter('.sy-fo-field-error-message')->text())->toBe('This value should not be blank.');
});

it('attaches the repeater controller, the sortable control and the handle only when drag reorder is on', function (): void {
    $repeater = Repeater::make('tags')->components([TextInput::make('name')]);
    $crawler = renderRepeater($repeater, ['tags' => [['name' => 'a'], ['name' => 'b']]]);

    expect($crawler->filter('.sy-fo-repeater')->attr('data-controller'))->toBe('sylament--forms--repeater')
        ->and($crawler->filter('.sy-fo-repeater')->attr('data-sylament--forms--repeater-key-value'))->toBe($repeater->getKey())
        ->and($crawler->filter('.sy-fo-repeater-items')->attr('data-controller'))->toBe('sylament--support--sortable')
        ->and($crawler->filter('.sy-fo-repeater-items')->attr('data-sylament--support--sortable-animation-duration-value'))->toBe('300')
        ->and($crawler->filter('.sy-fo-repeater-items')->attr('data-action'))->toBe('sylament--support--sortable:end->sylament--forms--repeater#reorder')
        ->and($crawler->filter('.sy-fo-repeater-item')->extract(['data-sortable-item']))->toBe(['0', '1'])
        ->and($crawler->filter('.sy-fo-repeater-item')->extract(['data-item-key']))->toBe(['0', '1'])
        ->and($crawler->filter('.sy-fo-repeater-item-header-start-actions button[data-sortable-handle]'))->toHaveCount(2)
        ->and($crawler->filter('.sy-fo-repeater-item-header-start-actions button[data-sortable-handle]')->first()->attr('aria-label'))->toBe('Move')
        ->and($crawler->filter('.sy-fo-repeater-item-header-start-actions button[data-sortable-handle]')->first()->attr('data-action'))->toBeNull();

    $off = renderRepeater(Repeater::make('tags')->reorderable(false)->components([TextInput::make('name')]), ['tags' => [['name' => 'a']]]);

    expect($off->filter('.sy-fo-repeater')->attr('data-controller'))->toBeNull()
        ->and($off->filter('[data-sortable-item], [data-sortable-handle], [data-controller~="sylament--support--sortable"]'))->toHaveCount(0);
});

it('renders the collapse chrome from the server state and wires it to the controller', function (): void {
    $crawler = renderRepeater(Repeater::make('tags')->collapsed()->persistCollapsed()->components([TextInput::make('name')]), ['tags' => [['name' => 'a'], ['name' => 'b']]]);

    $root = $crawler->filter('.sy-fo-repeater');
    $items = $crawler->filter('.sy-fo-repeater-item');

    expect($root->attr('class'))->toContain('sy-collapsible')->toContain('sy-cloak')
        ->and($root->attr('data-sylament--forms--repeater-persist-value'))->toBe('true')
        ->and($items->first()->attr('class'))->toContain('sy-collapsed')
        ->and($items->first()->attr('data-sylament--forms--repeater-target'))->toBe('item')
        ->and($items->first()->attr('data-action'))->toBe('expand->sylament--forms--repeater#expandItem')
        ->and($crawler->filter('.sy-fo-repeater-item-header')->first()->attr('data-action'))->toBe('click->sylament--forms--repeater#toggleItemFromHeader')
        ->and($crawler->filter('.sy-fo-repeater-item-header-collapsible-actions')->first()->attr('data-action'))->toBe('click->sylament--forms--repeater#toggleItemFromButton')
        ->and($crawler->filter('.sy-fo-repeater-item-header-collapse-action button')->first()->attr('aria-label'))->toBe('Collapse')
        ->and($crawler->filter('.sy-fo-repeater-item-header-expand-action button')->first()->attr('aria-label'))->toBe('Expand')
        ->and($crawler->filter('.sy-fo-repeater-actions')->attr('class'))->not->toContain('sy-hidden')
        ->and($crawler->filter('.sy-fo-repeater-actions > span')->first()->attr('data-action'))->toBe('click->sylament--forms--repeater#collapseAll')
        ->and($crawler->filter('.sy-fo-repeater-actions > span')->last()->attr('data-action'))->toBe('click->sylament--forms--repeater#expandAll');

    $one = renderRepeater(Repeater::make('tags')->collapsible()->components([TextInput::make('name')]), ['tags' => [['name' => 'a']]]);

    expect($one->filter('.sy-fo-repeater-actions')->attr('class'))->toContain('sy-hidden')
        ->and($one->filter('.sy-fo-repeater')->attr('class'))->not->toContain('sy-cloak')
        ->and($one->filter('.sy-fo-repeater-item')->attr('class'))->not->toContain('sy-collapsed');
});

it('renders between-items chrome only where asked, and never as a draggable sibling', function (): void {
    $withAdd = renderRepeater(
        Repeater::make('tags')->addBetweenAction(static fn (Action $action): Action => $action->visible())->labelBetweenItems('and')->components([TextInput::make('name')]),
        ['tags' => [['name' => 'a'], ['name' => 'b'], ['name' => 'c']]],
    );

    expect($withAdd->filter('.sy-fo-repeater-add-between-items-ctn'))->toHaveCount(2)
        ->and($withAdd->filter('.sy-fo-repeater-label-between-items-ctn'))->toHaveCount(0)
        ->and($withAdd->filter('.sy-fo-repeater-add-between-items-ctn')->first()->attr('data-sortable-item'))->toBeNull()
        ->and($withAdd->filter('.sy-fo-repeater-add-between-items button')->first()->attr('data-live-arguments-param'))->toContain('"afterItem":"0"');

    $withLabel = renderRepeater(Repeater::make('tags')->labelBetweenItems('and')->components([TextInput::make('name')]), ['tags' => [['name' => 'a'], ['name' => 'b']]]);

    expect($withLabel->filter('.sy-fo-repeater-label-between-items')->text())->toBe('and')
        ->and($withLabel->filter('.sy-fo-repeater-add-between-items-ctn'))->toHaveCount(0);
});

it('renders visible extra item actions first in the header, bound to their item', function (): void {
    $crawler = renderRepeater(
        Repeater::make('tags')->cloneable()->extraItemActions([
            Action::make('inspect')->icon('heroicons:eye-20-solid'),
            Action::make('never')->visible(false),
        ])->components([TextInput::make('name')]),
        ['tags' => [['name' => 'a']]],
    );

    $buttons = $crawler->filter('.sy-fo-repeater-item-header-end-actions button');

    expect($buttons->first()->attr('data-live-name-param'))->toBe('inspect')
        ->and($buttons->first()->attr('data-live-arguments-param'))->toContain('"item":"0"')
        ->and($buttons->eq(1)->attr('data-live-name-param'))->toBe('clone')
        ->and($crawler->filter('[data-live-name-param="never"]'))->toHaveCount(0);
});

it("lays the items out on support's grid", function (): void {
    $crawler = renderRepeater(Repeater::make('tags')->grid(2)->components([TextInput::make('name')]), ['tags' => [['name' => 'a']]]);

    $list = $crawler->filter('.sy-fo-repeater-items');

    expect($list->attr('class'))->toContain('sy-grid')->toContain('lg:sy-grid-cols')
        ->and($list->attr('style'))->toContain('--cols-lg: repeat(2, minmax(0, 1fr))');
});

it('keeps an item header for a repeater whose only header surface is collapsibility', function (): void {
    $crawler = renderRepeater(
        Repeater::make('tags')->collapsible()->reorderable(false)->deletable(false)->components([TextInput::make('name')]),
        ['tags' => [['name' => 'a']]],
    );

    expect($crawler->filter('.sy-fo-repeater-item-has-header .sy-fo-repeater-item-header'))->toHaveCount(1)
        ->and($crawler->filter('.sy-fo-repeater-item-header-collapsible-actions'))->toHaveCount(1);
});

it('orders the simple-mode item actions reorder, move, extras, clone, delete', function (): void {
    $crawler = renderRepeater(
        Repeater::make('tags')
            ->simple(TextInput::make('value'))
            ->reorderableWithButtons()
            ->cloneable()
            ->extraItemActions([Action::make('inspect')->icon('heroicons:eye-20-solid')]),
        ['tags' => ['a', 'b']],
    );

    $labels = $crawler->filter('.sy-fo-simple-repeater-item')->first()->filter('.sy-fo-simple-repeater-item-actions button')->extract(['aria-label']);

    expect($labels)->toBe(['Move', 'Move up', 'Move down', 'Inspect', 'Clone', 'Delete'])
        ->and($crawler->filter('.sy-fo-simple-repeater-items')->attr('data-controller'))->toBe('sylament--support--sortable')
        ->and($crawler->filter('.sy-fo-simple-repeater-item')->first()->attr('data-sortable-item'))->toBe('0');
});
