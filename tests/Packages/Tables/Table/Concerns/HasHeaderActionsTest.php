<?php

declare(strict_types=1);

use Sylament\Actions\Testing\TestAction;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Tables\ActionSetsProductTable;
use Sylament\Tests\Fixtures\Tables\AlignedColumnsTable;
use Sylament\Tests\Fixtures\Tables\Selectors;

it('renders its visible actions in the header band of a table with no heading, replacing the set an earlier call named and the checkboxes its bulk action turned on', function (): void {
    $this->seed(new Product('Lamp'));

    $tester = $this->createLiveComponent(ActionSetsProductTable::class, ['setup' => 'headerReplaced']);
    $crawler = $tester->render()->crawler();

    expect($crawler)->toContainButtons(Selectors::HEADER . ' ' . Selectors::HEADER_ACTIONS . ' button', ['Restock'])
        ->and($crawler->filter(Selectors::HEADER . ' .sy-ta-header-heading'))->toHaveCount(0)
        ->and($crawler->filter(Selectors::RECORD_CHECKBOX))->toHaveCount(0);

    $tester->callAction(TestAction::make('restock')->table());

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['restock']);
});

it('appends the actions it is pushed after the set before them, which keeps its checkboxes', function (): void {
    $this->seed(new Product('Lamp'));

    $crawler = $this->createLiveComponent(ActionSetsProductTable::class, ['setup' => 'headerPushed'])->render()->crawler();

    expect($crawler)->toContainButtons(Selectors::HEADER_ACTIONS . ' button', ['Purge', 'Restock'])
        ->and($crawler->filter(Selectors::RECORD_CHECKBOX))->toHaveCount(1);
});

it('refuses a member that is neither an action nor a group', function (): void {
    expect(fn (): Table => new AlignedColumnsTable()->getTable()->pushHeaderActions(['restock']))
        ->toThrow(InvalidArgumentException::class, 'Table header actions must be an instance of [Sylament\Actions\Action] or [Sylament\Actions\ActionGroup], got [string].');
});

it('marks the header band for the adaptive actions position by default, and not once its actions sit at the bottom', function (string $setup, int $adaptive): void {
    $crawler = $this->createLiveComponent(ActionSetsProductTable::class, ['setup' => $setup])->render()->crawler();

    expect($crawler->filter(Selectors::HEADER))->toHaveCount(1)
        ->and($crawler->filter(Selectors::HEADER . '.sy-ta-header-adaptive-actions-position'))->toHaveCount($adaptive);
})->with([
    'by default' => ['headerReplaced', 1],
    'at the bottom' => ['headerBottom', 0],
]);
