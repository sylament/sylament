<?php

declare(strict_types=1);

use Sylament\Support\Icons\IconManager;
use Sylament\Tables\Twig\TablesIconAlias;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Tables\FilteredLarderJarTable;
use Sylament\Tests\Fixtures\Tables\GroupedLarderJarTable;
use Sylament\Tests\Fixtures\Tables\ProductTable;
use Sylament\Tests\Fixtures\Tables\ReorderableLarderJarTable;
use Sylament\Tests\Fixtures\Tables\SealFlagShelfTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Symfony\Component\DomCrawler\Crawler;

it('swaps the icon of its own control for the one an application registers under the alias', function (string $alias, string $host, array $data, Closure $icon): void {
    $this->seed($jar = new LarderJar('Plum'));

    $this::getContainer()->get(IconManager::class)->register([$alias => 'support:heart']);

    $rendered = $this->createLiveComponent($host, $data)->render()->crawler();

    expect($rendered)->toContainElement($icon($jar), 'Heart')
        ->and(array_count_values($rendered->filter('svg')->each(fn (Crawler $svg): string => $svg->text()))['Heart'] ?? 0)->toBe(1);
})->with([
    'the sort button of an unsorted column' => [TablesIconAlias::HEADER_CELL_SORT_BUTTON, ReorderableLarderJarTable::class, [], fn (): string => Selectors::sortTrigger('label') . ' svg'],
    'the sort button of an ascending column' => [TablesIconAlias::HEADER_CELL_SORT_ASC_BUTTON, ReorderableLarderJarTable::class, ['tableSort' => 'label:asc'], fn (): string => Selectors::sortTrigger('label') . ' svg'],
    'the sort button of a descending column' => [TablesIconAlias::HEADER_CELL_SORT_DESC_BUTTON, ReorderableLarderJarTable::class, ['tableSort' => 'label:desc'], fn (): string => Selectors::sortTrigger('label') . ' svg'],
    'the search field' => [TablesIconAlias::SEARCH_FIELD, FilteredLarderJarTable::class, [], fn (): string => Selectors::SEARCH_FIELD . ' svg'],
    'the grouping trigger' => [TablesIconAlias::ACTIONS_GROUP, FilteredLarderJarTable::class, [], fn (): string => Selectors::GROUP_TRIGGER . ' svg'],
    'the column manager trigger' => [TablesIconAlias::ACTIONS_COLUMN_MANAGER, FilteredLarderJarTable::class, ['maker' => 'toggledOff'], fn (): string => Selectors::COLUMN_MANAGER_TRIGGER . ' svg'],
    'the trigger that starts reordering' => [TablesIconAlias::ACTIONS_ENABLE_REORDERING, ReorderableLarderJarTable::class, [], fn (): string => Selectors::REORDER_TRIGGER . ' svg'],
    'the trigger that finishes reordering' => [TablesIconAlias::ACTIONS_DISABLE_REORDERING, ReorderableLarderJarTable::class, ['isTableReordering' => true], fn (): string => Selectors::FINISH_REORDERING_TRIGGER . ' svg'],
    "a record's reorder handle" => [TablesIconAlias::REORDER_HANDLE, ReorderableLarderJarTable::class, ['isTableReordering' => true], fn (LarderJar $jar): string => Selectors::reorderHandle((string) $jar->id) . ' svg'],
    "a group's collapse button" => [TablesIconAlias::GROUPING_COLLAPSE_BUTTON, GroupedLarderJarTable::class, ['defaultGroup' => 'label'], fn (): string => 'button[aria-label="Label: Plum"] svg'],
    'the default empty state' => [TablesIconAlias::EMPTY_STATE, ProductTable::class, [], fn (): string => Selectors::EMPTY_STATE . ' svg'],
    'the funnel of filters folded above the rows' => [TablesIconAlias::ACTIONS_FILTER, SealFlagShelfTable::class, [], fn (): string => Selectors::FILTERS_TRIGGER . ' svg'],
    "the column manager's reorder handle" => [TablesIconAlias::REORDER_HANDLE, SealFlagShelfTable::class, [], fn (): string => Selectors::columnManagerReorderHandle('sealed') . ' svg'],
    "a boolean icon column's true icon" => [TablesIconAlias::COLUMNS_ICON_COLUMN_TRUE, SealFlagShelfTable::class, [], fn (): string => Selectors::cell('plum', 'sealed') . ' svg'],
    "a boolean icon column's false icon" => [TablesIconAlias::COLUMNS_ICON_COLUMN_FALSE, SealFlagShelfTable::class, [], fn (): string => Selectors::cell('fig', 'sealed') . ' svg'],
]);
