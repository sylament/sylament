<?php

declare(strict_types=1);

use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Actions\Selectors as ActionsSelectors;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\ActionSetsProductTable;
use Sylament\Tests\Fixtures\Tables\AlignedColumnsTable;
use Sylament\Tests\Fixtures\Tables\BrowsableProductTable;
use Sylament\Tests\Fixtures\Tables\Selectors;

it("renders its visible actions in the toolbar's start group, a group as one dropdown, replacing the set an earlier call named and the checkboxes its bulk action turned on", function (): void {
    $this->seed(new Product('Lamp'));

    $crawler = $this->createLiveComponent(ActionSetsProductTable::class, ['setup' => 'toolbarReplaced'])->render()->crawler();

    expect($crawler)->toContainButtons(Selectors::TOOLBAR_ACTIONS . ' button', ['Export', 'Actions', 'Print', 'Mail'])
        ->and($crawler->filter(Selectors::TOOLBAR_ACTIONS . ' ' . SupportSelectors::DROPDOWN))->toHaveCount(1)
        ->and($crawler->filter(Selectors::RECORD_CHECKBOX))->toHaveCount(0);
});

it('appends the actions it is pushed after the set before them, which keeps its checkboxes', function (): void {
    $this->seed(new Product('Lamp'));

    $crawler = $this->createLiveComponent(ActionSetsProductTable::class, ['setup' => 'toolbarPushed'])->render()->crawler();

    expect($crawler)->toContainButtons(Selectors::TOOLBAR_ACTIONS . ' button', ['Purge', 'Export'])
        ->and($crawler->filter(Selectors::RECORD_CHECKBOX))->toHaveCount(1);
});

it('refuses a member that is neither an action nor a group', function (): void {
    expect(fn (): Table => new AlignedColumnsTable()->getTable()->pushToolbarActions(['export']))
        ->toThrow(InvalidArgumentException::class, 'Table toolbar actions must be an instance of [Sylament\Actions\Action] or [Sylament\Actions\ActionGroup], got [string].');
});

it('renders the toolbar band for a toolbar action or a search, and its start group only for the action', function (string $host, array $props, int $toolbar, int $startGroup): void {
    $crawler = $this->createLiveComponent($host, $props)->render()->crawler();

    expect($crawler->filter(Selectors::TOOLBAR))->toHaveCount($toolbar)
        ->and($crawler->filter(Selectors::TOOLBAR . ' ' . Selectors::TOOLBAR_ACTIONS))->toHaveCount($startGroup);
})->with([
    'a toolbar action' => [ActionSetsProductTable::class, ['setup' => 'toolbarPushed'], 1, 1],
    'a search alone' => [BrowsableProductTable::class, [], 1, 0],
    'header actions alone' => [ActionSetsProductTable::class, ['setup' => 'headerPushed'], 0, 0],
]);

it('hides each bulk-only trigger in a wrapper until a record is selected or the selection is tracked, in the toolbar and the header band alike, and wraps no plain action or plain group', function (): void {
    $this->seed($lamp = new Product('Lamp'));

    $unselected = $this->createLiveComponent(ActionSetsProductTable::class, ['setup' => 'bulkOnly'])->render()->crawler();
    $wrapped = static fn (string $band): array => $unselected->filter($band . ' ' . ActionsSelectors::BULK_ONLY . '[hidden] button')->each(
        static fn ($button): string => mb_trim($button->attr('aria-label') ?? '') ?: $button->text(),
    );

    expect($wrapped(Selectors::TOOLBAR_ACTIONS))->toBe(['Peek', 'ellipsis-vertical-20-solidBulk actions', 'Tidy'])
        ->and($wrapped(Selectors::HEADER_ACTIONS))->toBe(['Purge'])
        ->and($unselected->filter(ActionsSelectors::trigger('export'))->closest(ActionsSelectors::BULK_ONLY))->toBeNull()
        ->and($unselected->filter(ActionsSelectors::trigger('restock'))->closest(ActionsSelectors::BULK_ONLY))->toBeNull()
        ->and($unselected->filter(Selectors::TOOLBAR_ACTIONS . ' button[aria-label="Archiving"]')->closest(ActionsSelectors::BULK_ONLY))->toBeNull();

    foreach ([
        $this->createLiveComponent(ActionSetsProductTable::class, ['setup' => 'bulkOnly'])->selectTableRecords([$lamp]),
        $this->createLiveComponent(ActionSetsProductTable::class, ['setup' => 'bulkOnly'])->set('isTrackingDeselectedTableRecords', true),
    ] as $tester) {
        $crawler = $tester->render()->crawler();

        expect($crawler->filter('span' . ActionsSelectors::BULK_ONLY))->toHaveCount(3)
            ->and($crawler->filter('span' . ActionsSelectors::BULK_ONLY . '[hidden]'))->toHaveCount(0);
    }
});
