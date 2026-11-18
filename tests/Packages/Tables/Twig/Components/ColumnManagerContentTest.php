<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Symfony\Component\DomCrawler\Crawler;

it('lists each item by its label with a checkbox named for its column, ticked as the item is toggled and disabled where the column cannot be toggled', function (): void {
    $crawler = $this->renderTwigComponent('SylamentTables:ColumnManagerContent', [
        'hostId' => 'shelf',
        'items' => [
            ['name' => 'id', 'label' => 'ID', 'isToggleable' => false, 'isToggled' => true],
            ['name' => 'code', 'label' => 'Code', 'isToggleable' => true, 'isToggled' => false],
        ],
        'toggleable' => true,
    ])->crawler();
    $code = $crawler->filter(Selectors::columnManagerCheckbox('code'));

    expect($crawler->filter(Selectors::COLUMN_MANAGER_ITEM_LABEL)->each(static fn (Crawler $label): string => $label->text()))->toBe(['ID', 'Code'])
        ->and($crawler)->toContainElement(Selectors::columnManagerCheckbox('id') . '[checked][disabled]')
        ->and($code->attr('id'))->toBe('shelf.table.column-manager.code')
        ->and($code->attr('checked'))->toBeNull()
        ->and($code->attr('disabled'))->toBeNull()
        ->and($code->attr('data-action'))->toBe('change->sylament--tables--column-manager#toggle');
});

it('lists the items without a checkbox while no column can be toggled', function (): void {
    $crawler = $this->renderTwigComponent('SylamentTables:ColumnManagerContent', [
        'items' => [['name' => 'id', 'label' => 'ID', 'isToggleable' => false, 'isToggled' => true]],
    ])->crawler();

    expect($crawler)->toContainElement(Selectors::COLUMN_MANAGER_ITEM_LABEL, 'ID')
        ->and($crawler->filter(Selectors::COLUMN_MANAGER_CHECKBOX))->toHaveCount(0);
});

it('gives each item a drag handle named for its column, its end handed to the reorder, only while the columns are reorderable', function (): void {
    $items = [['name' => 'code', 'label' => 'Code', 'isToggleable' => true, 'isToggled' => true]];
    $reorderable = $this->renderTwigComponent('SylamentTables:ColumnManagerContent', ['items' => $items, 'reorderable' => true, 'reorderAnimationDuration' => 150])->crawler();
    $fixed = $this->renderTwigComponent('SylamentTables:ColumnManagerContent', ['items' => $items])->crawler();
    $list = $reorderable->filter(SupportSelectors::SORTABLE_CONTROLLER . SupportSelectors::sortableAnimationDuration('150'));

    expect($list->attr('data-action'))->toBe('sylament--support--sortable:end->sylament--tables--column-manager#reorder')
        ->and($list)->toContainElement(SupportSelectors::SORTABLE_ITEM . '[data-sortable-item="column::code"] ' . Selectors::columnManagerReorderHandle('code') . ' title', 'bars-2')
        ->and($fixed)->toContainElement(Selectors::COLUMN_MANAGER_ITEM_LABEL, 'Code')
        ->and($fixed->filter(SupportSelectors::SORTABLE_CONTROLLER . ', ' . SupportSelectors::SORTABLE_ITEM . ', ' . SupportSelectors::SORTABLE_HANDLE))->toHaveCount(0);
});

it('tells its controller whether to write each change to the server at once', function (bool $live): void {
    $crawler = $this->renderTwigComponent('SylamentTables:ColumnManagerContent', ['live' => $live])->crawler();

    expect($crawler->filter(Selectors::COLUMN_MANAGER)->attr('data-sylament--tables--column-manager-live-value'))->toBe(json_encode($live));
})->with([
    'deferring' => [false],
    'live' => [true],
]);
