<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Tables\SearchableLarderTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\TablePageController;
use Sylament\Tests\Fixtures\Tables\TranslatedLarderJarTable;
use Symfony\Component\DomCrawler\Crawler;

describe('searchable()', function (): void {
    it('shows the toolbar search field exactly when the table is searchable', function (array $variants, int $fields): void {
        $crawler = $this->createLiveComponent(SearchableLarderTable::class, ['variants' => $variants])->render()->crawler();

        expect($crawler->filter(Selectors::SEARCH))->toHaveCount($fields);
    })->with([
        'a globally searchable column' => [[], 1],
        'searchable(false) beside one' => [['notSearchable'], 0],
        'only a column searchable on its own input' => [['nameUnsearchable'], 0],
        'extra searchable columns alone' => [['nameUnsearchable', 'extras'], 1],
        'searchable() over no searchable column' => [['nameUnsearchable', 'searchable'], 1],
        'searchable(false) after extra searchable columns' => [['nameUnsearchable', 'extras', 'notSearchable'], 0],
    ]);
});

describe('the search fields', function (): void {
    it('hands the toolbar field its placeholder, and every field the debounce in milliseconds, or a commit on change when searching on blur', function (array $variants, ?string $placeholder, ?string $debounce, string $shownPlaceholder, string $binding): void {
        $crawler = $this->createLiveComponent(SearchableLarderTable::class, ['variants' => $variants, 'nameOwnInput' => true, 'placeholder' => $placeholder, 'debounce' => $debounce])->render()->crawler();
        $field = $crawler->filter(Selectors::SEARCH);

        expect($field->attr('placeholder'))->toBe($shownPlaceholder)
            ->and($field->attr('data-model'))->toBe("{$binding}|tableSearch")
            ->and($crawler->filter(Selectors::columnSearch('name'))->attr('data-model'))->toBe("{$binding}|tableColumnSearches.name");
    })->with([
        'the defaults' => [[], null, null, 'Search', 'debounce(500)'],
        'a placeholder' => [[], 'Find a larder', null, 'Find a larder', 'debounce(500)'],
        'milliseconds' => [[], null, '750ms', 'Search', 'debounce(750)'],
        'seconds' => [[], null, '1s', 'Search', 'debounce(1000)'],
        'a bare number, read as milliseconds' => [[], null, '250', 'Search', 'debounce(250)'],
        'a duration it cannot read' => [[], null, 'soon', 'Search', 'debounce(500)'],
        'searchOnBlur()' => [['onBlur'], null, '1s', 'Search', 'on(change)'],
    ]);

    it("adds a row of inputs, each named for its column and holding its term as typed, while a column the developer shows is searchable on its own input", function (): void {
        $this->seed(new Larder('Red Lamp'));

        $shown = $this->createLiveComponent(SearchableLarderTable::class)->searchTableColumns(['supplier' => ['name' => ' Acme']])->render()->crawler();
        $hidden = $this->createLiveComponent(SearchableLarderTable::class, ['variants' => ['supplierHidden']])->render()->crawler();
        $toggledOff = $this->createLiveComponent(SearchableLarderTable::class, ['variants' => ['supplierToggleable']])->toggleAllTableColumns(false)->render()->crawler();

        expect($shown->filter(Selectors::COLUMN_SEARCH_ROW))->toHaveCount(1)
            ->and($shown->filter(Selectors::columnSearch('supplier.name'))->attr('aria-label'))->toBe('Search Supplier')
            ->and($shown->filter(Selectors::columnSearch('supplier.name'))->attr('value'))->toBe(' Acme')
            ->and($shown->filter(Selectors::columnSearch('name')))->toHaveCount(0)
            ->and($hidden->filter(Selectors::COLUMN_SEARCH_ROW))->toHaveCount(0)
            ->and($toggledOff->filter(Selectors::COLUMN_SEARCH_ROW))->toHaveCount(1)
            ->and($toggledOff->filter(Selectors::columnSearch('supplier.name')))->toHaveCount(0);
    });
});

describe('the global search', function (): void {
    it('matches the whole term as one phrase when told not to split it into words', function (): void {
        $this->seed($redLamp = new Larder('Red Lamp'), $lampRed = new Larder('Lamp Red'));

        $this->createLiveComponent(SearchableLarderTable::class)
            ->searchTable('Red Lamp')
            ->assertCanSeeTableRecords([$redLamp, $lampRed]);

        $this->createLiveComponent(SearchableLarderTable::class, ['variants' => ['wholeTerm']])
            ->searchTable('Red Lamp')
            ->assertCanSeeTableRecords([$redLamp])
            ->assertCanNotSeeTableRecords([$lampRed]);
    });

    it('leaves the whole search to a searchUsing() closure, reading neither the columns nor the extra columns', function (): void {
        $acme = new Product('Acme');
        $redLamp = new Larder('Red Lamp');
        $redLamp->supplier = $acme;

        $pantry = new Larder('Pantry');
        $pantry->supplier = $acme;
        $this->seed($acme, $redLamp, $pantry, $blueLamp = new Larder('Blue Lamp'));

        $this->createLiveComponent(SearchableLarderTable::class, ['variants' => ['searchUsing', 'extras']])
            ->searchTable('Acme')
            ->assertCanSeeTableRecords([$pantry, $redLamp])
            ->assertCanNotSeeTableRecords([$blueLamp])
            ->searchTable('Lamp')
            ->assertCanNotSeeTableRecords([$redLamp, $blueLamp]);
    });

    it("searches the extra columns beside the columns: a name across an association, a closure's expression among a word's matches and a closure's own constraint beside them, skipping a blank member, and none once searchable(false) follows", function (): void {
        $acme = new Product('Acme');
        $redLamp = new Larder('Red Lamp');
        $redLamp->supplier = $acme;

        $pantry = new Larder('Pantry');
        $pantry->supplier = $acme;
        $this->seed($acme, $redLamp, $pantry, $blueChair = new Larder('Blue Chair'), $greenJug = new Larder('Green Jug'));

        $this->createLiveComponent(SearchableLarderTable::class, ['variants' => ['extras']])
            ->searchTable('Acme')
            ->assertCanSeeTableRecords([$redLamp])
            ->assertCanNotSeeTableRecords([$pantry, $blueChair, $greenJug])
            ->searchTable((string) $blueChair->id)
            ->assertCanSeeTableRecords([$blueChair])
            ->assertCanNotSeeTableRecords([$redLamp, $greenJug])
            ->searchTable('Jug')
            ->assertCanSeeTableRecords([$greenJug])
            ->assertCanNotSeeTableRecords([$blueChair]);

        $this->createLiveComponent(SearchableLarderTable::class, ['variants' => ['extras', 'notSearchable']])
            ->searchTable('Acme')
            ->assertCanNotSeeTableRecords([$redLamp]);
    });
});

describe('session persistence', function (): void {
    it('remembers the global term across page loads only when the table says so', function (string $host, bool $remembers): void {
        $this->seed($redLamp = new Larder('Red Lamp'), $redChair = new Larder('Red Chair'));
        $client = $this::getContainer()->get('test.client');
        $this->createLiveComponent($host, client: $client)->searchTable('Lamp');

        $page = $this->visit(TablePageController::url($host), client: $client);

        expect($page->filter(Selectors::row((string) $redLamp->id)))->toHaveCount(1)
            ->and($page->filter(Selectors::row((string) $redChair->id)))->toHaveCount($remembers ? 0 : 1)
            ->and($page->filter(Selectors::FILTER_INDICATOR_LABEL)->each(static fn (Crawler $chip): string => $chip->text(normalizeWhitespace: false)))->toBe($remembers ? ['Search: Lamp'] : []);
    })->with([
        'remembering' => ['Tables:SessionSearchedLarderTable', true],
        'forgetting' => ['Tables:SearchableLarderTable', false],
    ]);

    it('remembers the per-column terms across page loads only when the table says so', function (string $host, bool $remembers): void {
        $acme = new Product('Acme');
        $redLamp = new Larder('Red Lamp');
        $redLamp->supplier = $acme;
        $this->seed($acme, $redLamp, $redChair = new Larder('Red Chair'));
        $client = $this::getContainer()->get('test.client');
        $this->createLiveComponent($host, client: $client)->searchTableColumns(['supplier' => ['name' => 'Acme']]);

        $page = $this->visit(TablePageController::url($host), client: $client);

        expect($page->filter(Selectors::row((string) $redLamp->id)))->toHaveCount(1)
            ->and($page->filter(Selectors::row((string) $redChair->id)))->toHaveCount($remembers ? 0 : 1)
            ->and($page->filter(Selectors::columnSearch('supplier.name'))->attr('value'))->toBe($remembers ? 'Acme' : '');
    })->with([
        'remembering' => ['Tables:SessionSearchedLarderTable', true],
        'forgetting' => ['Tables:SearchableLarderTable', false],
    ]);
});

describe('the search placeholder', function (): void {
    it('shows a placeholder given as a message in its translation', function (): void {
        expect($this->createLiveComponent(TranslatedLarderJarTable::class)->render()->crawler()->filter(Selectors::SEARCH)->attr('placeholder'))->toBe('Find a jar (translated)');
    });
});
