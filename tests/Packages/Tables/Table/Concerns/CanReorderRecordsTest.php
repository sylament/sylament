<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\ReorderableLarderJarTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Symfony\Component\DomCrawler\Crawler;

it('opens a reordering mode from its toolbar trigger, where rows drag by a handle and nothing else clicks, and closes it again', function (): void {
    $this->seed($apricot = new LarderJar('Apricot', position: 1), $bramble = new LarderJar('Bramble', position: 2));
    $keys = [(string) $apricot->id, (string) $bramble->id];

    $tester = $this->createLiveComponent(ReorderableLarderJarTable::class);
    $browsing = $tester->render()->crawler();
    $reordering = $tester->call('toggleTableReordering')->render()->crawler();
    $closed = $tester->call('toggleTableReordering')->render()->crawler();

    expect($browsing->filter(Selectors::REORDER_TRIGGER . '[data-opens="reordering"] title')->text())->toBe('arrows-up-down')
        ->and($browsing->filter(SupportSelectors::SORTABLE_CONTROLLER))->toHaveCount(0)
        ->and($browsing->filter(Selectors::reorderHandle($keys[0])))->toHaveCount(0)
        ->and($browsing)->toContainButtons('button.sy-ac', ['Restock', 'Reorder records', 'Rename', 'Rename'])
        ->and($browsing->filter(Selectors::sortTrigger('label')))->toHaveCount(1)
        ->and($browsing->filter(Selectors::LINKED_CELL_WRAPPER))->toHaveCount(2)
        ->and($reordering->filter(Selectors::FINISH_REORDERING_TRIGGER . '[data-opens="browsing"] title')->text())->toBe('check')
        ->and($reordering)->toContainElement('[role="status"]', 'Drag and drop the records into order.')
        ->and($reordering)->toContainElement(SupportSelectors::SORTABLE_CONTROLLER . SupportSelectors::sortableAnimationDuration('300') . '[data-action="sylament--support--sortable:end->sylament--tables--table#reorder"]')
        ->and($reordering->filter(Selectors::ROW . SupportSelectors::SORTABLE_ITEM)->each(static fn (Crawler $row): ?string => $row->attr('data-sortable-item')))->toBe($keys)
        ->and($reordering->filter(Selectors::reorderHandle($keys[0])))->toHaveCount(1)
        ->and($reordering->filter(Selectors::reorderHandle($keys[1])))->toHaveCount(1)
        ->and($reordering)->toContainButtons('button.sy-ac', ['Finish reordering records'])
        ->and($reordering->filter(Selectors::sortTrigger('label')))->toHaveCount(0)
        ->and($reordering->filter(Selectors::PLAIN_CELL_WRAPPER))->toHaveCount(2)
        ->and($closed->filter(SupportSelectors::SORTABLE_CONTROLLER))->toHaveCount(0)
        ->and($closed->filter(Selectors::REORDER_TRIGGER))->toHaveCount(1);
});

it('offers no reordering, and opens no mode on request, until a column is named, its condition holds and it is authorized', function (array $props): void {
    $this->seed($jar = new LarderJar('Apricot'));

    $crawler = $this->createLiveComponent(ReorderableLarderJarTable::class, $props)
        ->call('toggleTableReordering')
        ->render()
        ->crawler();

    expect($crawler->filter(Selectors::row((string) $jar->id)))->toHaveCount(1)
        ->and($crawler->filter(Selectors::REORDER_TRIGGER . ', ' . Selectors::FINISH_REORDERING_TRIGGER))->toHaveCount(0)
        ->and($crawler->filter(SupportSelectors::SORTABLE_CONTROLLER))->toHaveCount(0)
        ->and($crawler->filter(Selectors::reorderHandle((string) $jar->id)))->toHaveCount(0);
})->with([
    'no column' => [['reorderColumn' => null]],
    'a false condition' => [['reorderCondition' => false]],
    'unauthorized' => [['reorderAuthorized' => false]],
]);

it('calls its hooks before and after a drag with the new order of the keys', function (): void {
    $this->seed($apricot = new LarderJar('Apricot', position: 1), $bramble = new LarderJar('Bramble', position: 2));
    $order = "{$bramble->id},{$apricot->id}";

    $this->createLiveComponent(ReorderableLarderJarTable::class)
        ->call('reorderTable', ['order' => [(string) $bramble->id, (string) $apricot->id]]);

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(["before {$order}", "after {$order}"]);
});
