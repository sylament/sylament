<?php

declare(strict_types=1);

use Sylament\Tables\Testing\Testable;
use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Entity\Seedling;
use Sylament\Tests\Fixtures\Tables\SearchableLarderTable;
use Sylament\Tests\Fixtures\Tables\SeedlingGardenTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\SessionSearchedLarderTable;
use Sylament\Tests\Fixtures\Tables\TablePageController;
use Symfony\Component\DomCrawler\Crawler;

describe('the global term', function (): void {
    it('searches by the typed term trimmed, a blank one by nothing, while the wire keeps the term as typed', function (): void {
        $this->seed($redLamp = new Larder('Red Lamp'), $redChair = new Larder('Red Chair'), $blueLamp = new Larder('Blue Lamp'));
        $wire = fn (Testable $tester): mixed => $this::liveProps($tester->render()->crawler())['tableSearch'];

        $tester = $this->createLiveComponent(SearchableLarderTable::class, ['variants' => ['wholeTerm']])
            ->searchTable(' Red ')
            ->assertCanSeeTableRecords([$redChair, $redLamp])
            ->assertCanNotSeeTableRecords([$blueLamp]);

        expect($wire($tester))->toBe(' Red ');

        $tester->searchTable('  ')->assertCanSeeTableRecords([$blueLamp, $redChair, $redLamp]);

        expect($wire($tester))->toBe('  ');
    });

    it('returns to the first page on a new term, clearing the selection unless the table keeps it, and does neither for the term sent unchanged', function (array $variants, int $selected): void {
        $this->seed($blueLamp = new Larder('Blue Lamp'), $redChair = new Larder('Red Chair'), $redLamp = new Larder('Red Lamp'));

        $tester = $this->createLiveComponent(SearchableLarderTable::class, ['variants' => ['paged', ...$variants]])
            ->call('gotoTablePage', ['page' => 2])
            ->selectTableRecords([$redLamp])
            ->searchTable('')
            ->assertCanSeeTableRecords([$redLamp])
            ->assertCanNotSeeTableRecords([$blueLamp]);

        expect($tester->render()->crawler()->filter(Selectors::SELECTED_RECORD))->toHaveCount(1);

        $tester->searchTable('Lamp')->assertCanSeeTableRecords([$blueLamp, $redLamp], inOrder: true);

        expect($tester->render()->crawler()->filter(Selectors::SELECTED_RECORD))->toHaveCount($selected);
    })->with([
        'clearing the selection' => [[], 0],
        'keeping it' => [['keepsSelection'], 1],
    ]);
});

describe('the query pass', function (): void {
    it('matches every word somewhere, each word in any globally searchable column, and a quoted phrase as one word', function (): void {
        $acme = new Product('Acme');
        $redLamp = new Larder('Red Lamp');
        $redLamp->supplier = $acme;

        $blueLamp = new Larder('Blue Lamp');
        $blueLamp->supplier = $acme;
        $this->seed($acme, $redLamp, $blueLamp, $lampRed = new Larder('Lamp Red'), $redChair = new Larder('Red Chair'));

        $this->createLiveComponent(SearchableLarderTable::class, ['variants' => ['globalSupplier']])
            ->searchTable('Red Lamp')
            ->assertCanSeeTableRecords([$lampRed, $redLamp])
            ->assertCanNotSeeTableRecords([$blueLamp, $redChair])
            ->searchTable('"Red Lamp"')
            ->assertCanSeeTableRecords([$redLamp])
            ->assertCanNotSeeTableRecords([$lampRed])
            ->searchTable('Acme Lamp')
            ->assertCanSeeTableRecords([$blueLamp, $redLamp])
            ->assertCanNotSeeTableRecords([$lampRed, $redChair]);
    });

    it('skips a column the developer hides, and constrains nothing when no column is searchable', function (): void {
        $acme = new Product('Acme');
        $redLamp = new Larder('Red Lamp');
        $redLamp->supplier = $acme;
        $this->seed($acme, $redLamp, $redChair = new Larder('Red Chair'));

        $this->createLiveComponent(SearchableLarderTable::class, ['variants' => ['globalSupplier']])
            ->searchTable('Acme')
            ->assertCanSeeTableRecords([$redLamp])
            ->assertCanNotSeeTableRecords([$redChair]);

        $this->createLiveComponent(SearchableLarderTable::class, ['variants' => ['globalSupplier', 'supplierHidden']])
            ->searchTable('Acme')
            ->assertCanNotSeeTableRecords([$redLamp, $redChair]);

        $this->createLiveComponent(SearchableLarderTable::class, ['variants' => ['nameUnsearchable']])
            ->searchTable('Acme')
            ->assertCanSeeTableRecords([$redChair, $redLamp]);
    });
});

describe('resetTableSearch()', function (): void {
    it("shows the term, trimmed, on a removable chip that clears it, back on the first page with the selection cleared, even when no term is set", function (): void {
        $this->seed($blueLamp = new Larder('Blue Lamp'), $redChair = new Larder('Red Chair'), $redLamp = new Larder('Red Lamp'));
        $chips = static fn (Testable $tester): array => $tester->render()->crawler()->filter(Selectors::FILTER_INDICATOR_LABEL)->each(static fn (Crawler $label): string => $label->text(normalizeWhitespace: false));

        $tester = $this->createLiveComponent(SearchableLarderTable::class, ['variants' => ['paged']])->searchTable('   ');

        expect($chips($tester))->toBe([])
            ->and($chips($tester->searchTable("\u{a0}")))->toBe([]);

        $tester->searchTable(' a ')->call('gotoTablePage', ['page' => 2])->selectTableRecords([$blueLamp]);

        expect($chips($tester))->toBe(['Search: a'])
            ->and($tester->render()->crawler()->filter(Selectors::FILTER_INDICATOR . ' ' . Selectors::REMOVE_FILTER_BUTTON . '[data-live-action-param="resetTableSearch"]'))->toHaveCount(1);

        $tester->call('resetTableSearch')->assertCanSeeTableRecords([$blueLamp, $redChair])->assertCanNotSeeTableRecords([$redLamp]);

        expect($chips($tester))->toBe([])
            ->and($tester->render()->crawler()->filter(Selectors::SELECTED_RECORD))->toHaveCount(0);

        $tester->call('gotoTablePage', ['page' => 2])->call('resetTableSearch')->assertCanSeeTableRecords([$blueLamp, $redChair]);
    });

    it('forgets the term a table remembers', function (): void {
        $this->seed($redLamp = new Larder('Red Lamp'), $redChair = new Larder('Red Chair'));
        $client = $this::getContainer()->get('test.client');
        $this->createLiveComponent(SessionSearchedLarderTable::class, client: $client)->searchTable('Lamp')->call('resetTableSearch');

        $page = $this->visit(TablePageController::url('Tables:SessionSearchedLarderTable'), client: $client);

        expect($page->filter(Selectors::row((string) $redLamp->id)))->toHaveCount(1)
            ->and($page->filter(Selectors::row((string) $redChair->id)))->toHaveCount(1);
    });
});

describe('the per-column terms', function (): void {
    it('seeds a term for every column searchable on its own input, a dotted name nested, keeping a blank and dropping a key that names no column', function (): void {
        $this->seed($oak = new Seedling('Oak'), $elm = new Seedling('Elm'));
        $bag = fn (Testable $tester): mixed => $this::liveProps($tester->render()->crawler())['tableColumnSearches'];

        $tester = $this->createLiveComponent(SeedlingGardenTable::class);

        expect($bag($tester))->toBe(['name' => '', 'garden' => ['name' => '']]);

        $tester->searchTableColumns(['name' => 'Oak', 0 => 'Elm'])->assertCanSeeTableRecords([$oak])->assertCanNotSeeTableRecords([$elm]);

        expect($bag($tester))->toBe(['name' => 'Oak', 'garden' => ['name' => '']]);

        $tester->set('tableColumnSearches.name', '')->assertCanSeeTableRecords([$oak, $elm]);

        expect($bag($tester))->toBe(['name' => '', 'garden' => ['name' => '']]);
    });

    it('narrows by every word of each term, on every column holding one, beside the global term', function (): void {
        $acme = new Product('Acme');
        $redLamp = new Larder('Red Lamp');
        $redLamp->supplier = $acme;

        $redChair = new Larder('Red Chair');
        $redChair->supplier = $acme;
        $this->seed($acme, $redLamp, $redChair, $lampRed = new Larder('Lamp Red'), $blueLamp = new Larder('Blue Lamp'));

        $this->createLiveComponent(SearchableLarderTable::class, ['nameOwnInput' => true])
            ->searchTableColumns(['name' => 'Red Lamp', 'supplier' => ['name' => '']])
            ->assertCanSeeTableRecords([$lampRed, $redLamp])
            ->assertCanNotSeeTableRecords([$blueLamp, $redChair])
            ->searchTableColumns(['name' => 'Red Lamp', 'supplier' => ['name' => 'Acme']])
            ->assertCanSeeTableRecords([$redLamp])
            ->assertCanNotSeeTableRecords([$lampRed])
            ->searchTableColumns(['name' => '', 'supplier' => ['name' => 'Acme']])
            ->searchTable('Chair')
            ->assertCanSeeTableRecords([$redChair])
            ->assertCanNotSeeTableRecords([$redLamp, $blueLamp]);
    });

    it('ignores a term on a column the developer hides or one searchable only globally, and still narrows by one the user toggled off, its chip kept', function (): void {
        $acme = new Product('Acme');
        $redLamp = new Larder('Red Lamp');
        $redLamp->supplier = $acme;
        $this->seed($acme, $redLamp, $redChair = new Larder('Red Chair'));

        $toggledOff = $this->createLiveComponent(SearchableLarderTable::class, ['variants' => ['supplierToggleable']])
            ->toggleAllTableColumns(false)
            ->searchTableColumns(['name' => 'Chair', 'supplier' => ['name' => 'Acme']])
            ->assertCanSeeTableRecords([$redLamp])
            ->assertCanNotSeeTableRecords([$redChair]);

        expect($toggledOff->render()->crawler())->toContainElement(Selectors::FILTER_INDICATOR_LABEL, 'Supplier: Acme');

        $this->createLiveComponent(SearchableLarderTable::class, ['variants' => ['supplierHidden']])
            ->searchTableColumns(['supplier' => ['name' => 'Acme']])
            ->assertCanSeeTableRecords([$redChair, $redLamp]);
    });

    it('shows each term, trimmed, on a chip naming its column that clears that term alone, and none for a column the developer hides', function (): void {
        $acme = new Product('Acme');
        $redLamp = new Larder('Red Lamp');
        $redLamp->supplier = $acme;
        $this->seed($acme, $redLamp, $blueLamp = new Larder('Blue Lamp'), $redChair = new Larder('Red Chair'));
        $chips = static fn (Testable $tester): array => $tester->render()->crawler()->filter(Selectors::FILTER_INDICATOR_LABEL)->each(static fn (Crawler $label): string => $label->text(normalizeWhitespace: false));

        $tester = $this->createLiveComponent(SearchableLarderTable::class, ['nameOwnInput' => true])
            ->searchTableColumns(['name' => ' Lamp ', 'supplier' => ['name' => 'Acme']]);

        expect($chips($tester))->toBe(['Name: Lamp', 'Supplier: Acme'])
            ->and($tester->render()->crawler()->filter(Selectors::REMOVE_FILTER_BUTTON . '[data-live-action-param="resetTableColumnSearch"][data-live-column-param="supplier.name"]'))->toHaveCount(1);

        $tester->call('resetTableColumnSearch', ['column' => 'supplier.name'])
            ->assertCanSeeTableRecords([$blueLamp, $redLamp])
            ->assertCanNotSeeTableRecords([$redChair]);

        expect($chips($tester))->toBe(['Name: Lamp'])
            ->and($tester->render()->crawler()->filter(Selectors::columnSearch('name'))->attr('value'))->toBe(' Lamp ');

        $tester->searchTableColumns(['name' => 'Lamp', 'supplier' => ['name' => 'Acme']])->call('resetTableColumnSearches')->assertCanSeeTableRecords([$blueLamp, $redChair, $redLamp]);

        expect($chips($tester))->toBe([]);

        $hidden = $this->createLiveComponent(SearchableLarderTable::class, ['variants' => ['supplierHidden'], 'nameOwnInput' => true])
            ->searchTableColumns(['name' => 'Lamp', 'supplier' => ['name' => 'Acme']]);

        expect($chips($hidden))->toBe(['Name: Lamp']);
    });

    it('ignores a reset naming no column searchable on its own input, leaving every term where it was', function (string $column): void {
        $this->seed($oak = new Seedling('Oak'), $elm = new Seedling('Elm'));
        $bag = fn (Testable $tester): mixed => $this::liveProps($tester->render()->crawler())['tableColumnSearches'];

        $tester = $this->createLiveComponent(SeedlingGardenTable::class)
            ->searchTableColumns(['name' => 'Oak'])
            ->call('resetTableColumnSearch', ['column' => $column])
            ->assertCanSeeTableRecords([$oak])
            ->assertCanNotSeeTableRecords([$elm]);

        expect($bag($tester))->toBe(['name' => 'Oak', 'garden' => ['name' => '']]);

        $tester->call('resetTableColumnSearch', ['column' => 'name'])->assertCanSeeTableRecords([$oak, $elm]);
    })->with([
        'a path below a column' => ['name.x'],
        'a column searchable on no input' => ['id'],
        'no column' => ['shelf'],
    ]);

    it('returns to the first page and clears the selection on a term typed into a column, but not on a term re-sent as a number or a column becoming searchable', function (): void {
        $supplier = new Product('Larder 100');
        $larders = [];

        foreach (['Blue Lamp', 'Red Chair', 'Red Lamp'] as $name) {
            $larders[] = $larder = new Larder($name);
            $larder->supplier = $supplier;
        }

        $this->seed($supplier, ...$larders);
        [$blueLamp, $redChair, $redLamp] = $larders;

        $tester = $this->createLiveComponent(SearchableLarderTable::class, ['variants' => ['paged']])
            ->call('gotoTablePage', ['page' => 2])
            ->selectTableRecords([$blueLamp])
            ->set('tableColumnSearches.supplier.name', '100')
            ->assertCanSeeTableRecords([$blueLamp, $redChair])
            ->assertCanNotSeeTableRecords([$redLamp]);

        expect($tester->render()->crawler()->filter(Selectors::SELECTED_RECORD))->toHaveCount(0);

        $tester->call('gotoTablePage', ['page' => 2])
            ->selectTableRecords([$redLamp])
            ->set('tableColumnSearches.supplier.name', 100)
            ->set('nameOwnInput', true)
            ->assertCanSeeTableRecords([$redLamp])
            ->assertCanNotSeeTableRecords([$blueLamp]);

        expect($tester->render()->crawler()->filter(Selectors::SELECTED_RECORD))->toHaveCount(1)
            ->and($tester->render()->crawler()->filter(Selectors::columnSearch('name')))->toHaveCount(1);
    });
});
