<?php

declare(strict_types=1);

use Sylament\Actions\Testing\TestAction;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\AlignedColumnsTable;
use Sylament\Tests\Fixtures\Tables\PositionedRecordActionsProductTable;
use Sylament\Tests\Fixtures\Tables\SelectionReadingProductTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Symfony\Component\DomCrawler\Crawler;

it("renders each record's visible actions in its row, bound to that record, an action hidden for one record dropped from its row alone, with no query per record", function (): void {
    $this->seed($lamp = new Product('Lamp'), $vase = new Product('Vase'), new Product('Rug'));

    $tester = $this->createLiveComponent(PositionedRecordActionsProductTable::class);
    $crawler = $tester->render()->crawler();

    expect($this->statementsRun())->toHaveCount(2)
        ->and($crawler)->toContainButtons(Selectors::row((string) $lamp->id) . ' ' . Selectors::RECORD_ACTIONS . ' button', ['Open', 'Ship', 'Weigh', 'Actions', 'Rename'])
        ->and($crawler)->toContainButtons(Selectors::row((string) $vase->id) . ' ' . Selectors::RECORD_ACTIONS . ' button', ['Open', 'Restock', 'Ship', 'Weigh', 'Actions', 'Rename']);

    $tester->callAction(TestAction::make('rename')->table($vase));

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['rename Vase']);

    $tester->callAction(TestAction::make('open')->table($lamp));

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['open Lamp']);
});

it("keeps the actions column for the other records while one record's actions are all hidden, and renders none once every record's are or none are registered", function (): void {
    $this->seed($lamp = new Product('Lamp'), $sealed = new Product('Sealed'));

    $crawler = $this->createLiveComponent(PositionedRecordActionsProductTable::class)->render()->crawler();

    expect($crawler->filter('th[aria-label="Actions"]'))->toHaveCount(1)
        ->and($crawler->filter(Selectors::row((string) $lamp->id) . ' ' . Selectors::RECORD_ACTIONS . ' button'))->not->toHaveCount(0)
        ->and($crawler->filter(Selectors::row((string) $sealed->id) . ' ' . Selectors::RECORD_ACTIONS))->toHaveCount(1)
        ->and($crawler->filter(Selectors::row((string) $sealed->id) . ' ' . Selectors::RECORD_ACTIONS . ' button'))->toHaveCount(0);

    $this->entityManager()->remove($this->reload($lamp));
    $this->entityManager()->flush();

    foreach ([PositionedRecordActionsProductTable::class, SelectionReadingProductTable::class] as $host) {
        $crawler = $this->createLiveComponent($host)->render()->crawler();

        expect($crawler->filter(Selectors::row((string) $sealed->id)))->toHaveCount(1)
            ->and($crawler->filter('th[aria-label="Actions"]'))->toHaveCount(0)
            ->and($crawler->filter(Selectors::RECORD_ACTIONS))->toHaveCount(0);
    }
});

it('renders a record action as a small link unless it is given a button or a size of its own', function (): void {
    $this->seed($lamp = new Product('Lamp'));

    $row = Selectors::row((string) $lamp->id) . ' ';

    expect($this->createLiveComponent(PositionedRecordActionsProductTable::class)->render()->crawler())
        ->toContainElement($row . SupportSelectors::SMALL_LINK, 'Open')
        ->toContainElement($row . SupportSelectors::BUTTON . SupportSelectors::size('sm'), 'Ship')
        ->toContainElement($row . SupportSelectors::LINK . SupportSelectors::size('lg'), 'Weigh');
});

it("opens a group's dropdown towards the start beside actions placed before the columns, and towards the end otherwise", function (?string $position, string $placement): void {
    $this->seed($lamp = new Product('Lamp'));

    $crawler = $this->createLiveComponent(PositionedRecordActionsProductTable::class, ['position' => $position])->render()->crawler();

    expect($crawler->filter(Selectors::row((string) $lamp->id) . ' ' . Selectors::RECORD_ACTIONS . ' ' . SupportSelectors::dropdownValue('placement', $placement)))->toHaveCount(1);
})->with([
    'before the columns' => ['BeforeColumns', 'bottom-start'],
    'by default, after the columns' => [null, 'bottom-end'],
]);

it('places the actions cell where its position says, the two positions on each side of the cells telling apart only once the checkbox column is on, and renders none after the content', function (?string $position, bool $selectable, ?string $checkboxPosition, array $order): void {
    $this->seed($lamp = new Product('Lamp'));

    $crawler = $this->createLiveComponent(PositionedRecordActionsProductTable::class, ['position' => $position, 'selectable' => $selectable, 'checkboxPosition' => $checkboxPosition])->render()->crawler();
    $kinds = static fn (Crawler $cell): string => match (true) {
        $cell->matches(Selectors::SELECTION_CELL) => 'checkbox',
        $cell->matches('th[aria-label="Actions"]') || $cell->filter(Selectors::RECORD_ACTIONS)->count() > 0 => 'actions',
        default => 'name',
    };

    expect($crawler->filter(Selectors::HEADER_CELLS)->each($kinds))->toBe($order)
        ->and($crawler->filter(Selectors::row((string) $lamp->id) . ' td')->each($kinds))->toBe($order);
})->with([
    'before the cells, no checkboxes' => ['BeforeCells', false, null, ['actions', 'name']],
    'before the columns, no checkboxes' => ['BeforeColumns', false, null, ['actions', 'name']],
    'by default, no checkboxes' => [null, false, null, ['name', 'actions']],
    'after the cells, no checkboxes' => ['AfterCells', false, null, ['name', 'actions']],
    'before the cells, the checkboxes before them' => ['BeforeCells', true, 'BeforeCells', ['actions', 'checkbox', 'name']],
    'before the columns, the checkboxes before them' => ['BeforeColumns', true, 'BeforeCells', ['checkbox', 'actions', 'name']],
    'after the columns, the checkboxes after them' => ['AfterColumns', true, 'AfterCells', ['name', 'actions', 'checkbox']],
    'after the cells, the checkboxes after them' => ['AfterCells', true, 'AfterCells', ['name', 'checkbox', 'actions']],
    'after the content' => ['AfterContent', true, null, ['checkbox', 'name']],
]);

it('refuses a member that is neither an action nor a group', function (): void {
    expect(fn (): Table => new AlignedColumnsTable()->getTable()->pushRecordActions(['open']))
        ->toThrow(InvalidArgumentException::class, 'Table record actions must be an instance of [Sylament\Actions\Action] or [Sylament\Actions\ActionGroup], got [string].');
});
