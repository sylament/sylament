<?php

declare(strict_types=1);

use Sylament\Actions\Testing\TestAction;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Tables\AlignedColumnsTable;
use Sylament\Tests\Fixtures\Tables\EmptyStateLarderJarTable;
use Sylament\Tests\Fixtures\Tables\HeadedLarderJarTable;
use Sylament\Tests\Fixtures\Tables\ProductTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Symfony\Component\DomCrawler\Crawler;

it('shows an empty table an empty state in place of the table, and a table with records none', function (): void {
    $empty = $this->createLiveComponent(ProductTable::class)->render()->crawler();

    $this->seed(new Product('Lamp'));

    $filled = $this->createLiveComponent(ProductTable::class)->render()->crawler();

    expect($empty->filter(Selectors::EMPTY_STATE . '[role="status"]'))->toHaveCount(1)
        ->and($empty->filter(Selectors::TABLE))->toHaveCount(0)
        ->and($filled->filter(Selectors::TABLE))->toHaveCount(1)
        ->and($filled->filter(Selectors::EMPTY_STATE))->toHaveCount(0);
});

it("says there are no records by the table's plural label, until a heading is given", function (string $host, string $heading): void {
    expect($this->createLiveComponent($host)->render()->crawler())->toContainElement(Selectors::EMPTY_STATE . ' h2, ' . Selectors::EMPTY_STATE . ' h3', $heading);
})->with([
    'a product table' => [ProductTable::class, 'No products'],
    'a larder jar table' => [HeadedLarderJarTable::class, 'No larder jars'],
    'a given heading' => [EmptyStateLarderJarTable::class, 'The larder is bare'],
]);

it('invites the user to create a record only on a table with a create action, until a description is given or cleared', function (string $host, array $props, ?string $description): void {
    $paragraphs = $this->createLiveComponent($host, $props)->render()->crawler()->filter(Selectors::EMPTY_STATE . ' p');

    expect($paragraphs->each(static fn (Crawler $paragraph): string => $paragraph->text()))->toBe($description === null ? [] : [$description]);
})->with([
    'a create action' => [HeadedLarderJarTable::class, [], 'Create a larder jar to get started.'],
    'no create action' => [HeadedLarderJarTable::class, ['creates' => false], null],
    'a given description' => [EmptyStateLarderJarTable::class, [], 'Seal a jar to fill the shelf.'],
    'a cleared description beside a create action' => [EmptyStateLarderJarTable::class, ['emptyStateDescription' => null], null],
]);

it('shows the icon given, and an x mark until one is', function (string $host, string $icon): void {
    expect($this->createLiveComponent($host)->render()->crawler()->filter(Selectors::EMPTY_STATE . ' svg title')->text())->toBe($icon);
})->with([
    'an icon given' => [EmptyStateLarderJarTable::class, 'Star'],
    'none' => [ProductTable::class, 'x-mark'],
]);

it('demotes its heading under a headed table, and moves with the root heading level', function (array $props, string $tag): void {
    expect($this->createLiveComponent(HeadedLarderJarTable::class, $props)->render()->crawler())->toContainElement(Selectors::EMPTY_STATE . " {$tag}", 'No larder jars');
})->with([
    'under a heading' => [[], 'h3'],
    'without one' => [['heading' => null], 'h2'],
    'under a heading a level down' => [['rootHeadingLevel' => 3], 'h4'],
]);

it('replaces the whole empty state with the block given', function (): void {
    $crawler = $this->createLiveComponent(EmptyStateLarderJarTable::class, ['replacesEmptyState' => true])->render()->crawler();

    expect($crawler)->toContainElement('p', 'Nothing on this shelf yet.')
        ->and($crawler->filter(Selectors::EMPTY_STATE))->toHaveCount(0)
        ->and($crawler->filter('button'))->toHaveCount(0);
});

describe('actions', function (): void {
    it('renders the visible ones of the last set, then those pushed after it, and mounts them on the table', function (): void {
        $tester = $this->createLiveComponent(EmptyStateLarderJarTable::class);

        expect($tester->render()->crawler())->toContainButtons(Selectors::EMPTY_STATE . ' button', ['Create', 'Actions', 'Import', 'Borrow', 'Donate']);

        $tester->callAction(TestAction::make('create')->table());

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['create']);
    });

    it('refuses a member that is neither an action nor a group', function (): void {
        expect(fn (): Table => new AlignedColumnsTable()->getTable()->emptyStateActions(['create']))
            ->toThrow(InvalidArgumentException::class, 'Table empty state actions must be an instance of [Sylament\Actions\Action] or [Sylament\Actions\ActionGroup], got [string].');
    });
});
