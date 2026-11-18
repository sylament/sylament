<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Actions\Selectors as ActionsSelectors;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\ConfiguredSelectionProductTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Symfony\Component\DomCrawler\Crawler;

describe('selection chrome', function (): void {
    it("renders a checkbox named by each record's key, ticked once selected, and one named for the page, all in selection cells that are no column header", function (): void {
        $this->seed($lamp = new Product('Lamp'), $vase = new Product('Vase'));

        $crawler = $this->createLiveComponent(ConfiguredSelectionProductTable::class)->selectTableRecords([$lamp])->render()->crawler();

        expect($crawler->filter(Selectors::recordCheckbox((string) $lamp->id) . '[checked]'))->toHaveCount(1)
            ->and($crawler->filter(Selectors::recordCheckbox((string) $vase->id)))->toHaveCount(1)
            ->and($crawler->filter(Selectors::recordCheckbox((string) $vase->id) . '[checked]'))->toHaveCount(0)
            ->and($crawler->filter('th' . Selectors::SELECTION_CELL . ' ' . Selectors::PAGE_CHECKBOX . SupportSelectors::CHECKBOX))->toHaveCount(1)
            ->and($crawler->filter('td' . Selectors::SELECTION_CELL . ' ' . Selectors::RECORD_CHECKBOX . SupportSelectors::CHECKBOX))->toHaveCount(2)
            ->and($crawler->filter('th' . Selectors::HEADER_CELL)->each(static fn (Crawler $cell): bool => $cell->matches(Selectors::headerCell('name'))))->toBe([true]);
    });

    it('offers a primary "Select all" with the ceiling, hidden until the controller reveals it, beside a danger "Deselect all", on a page past the end too', function (int $page, int $rows): void {
        $this->seed(new Product('Lamp'), new Product('Vase'));

        $crawler = $this->createLiveComponent(ConfiguredSelectionProductTable::class, ['tablePage' => $page])->render()->crawler();
        $banner = $crawler->filter(Selectors::SELECTION_BANNER);

        expect($crawler->filter(Selectors::ROW))->toHaveCount($rows)
            ->and($banner)->toContainElement(SupportSelectors::LINK . SupportSelectors::color('primary') . '[hidden]', 'Select all 2')
            ->and($banner)->toContainElement(SupportSelectors::LINK . SupportSelectors::color('danger') . ':not([hidden])', 'Deselect all');
    })->with([
        'the first page' => [1, 2],
        'a page past the end' => [3, 0],
    ]);

    it("renders no banner while a deferred table's window is open", function (): void {
        $tester = $this->createLiveComponent(ConfiguredSelectionProductTable::class, ['defersLoading' => true]);

        expect($tester->render()->crawler()->filter(Selectors::SELECTION_BANNER))->toHaveCount(0)
            ->and($tester->loadTable()->render()->crawler()->filter(Selectors::SELECTION_BANNER))->toHaveCount(1);
    });
});

describe('selection settings', function (): void {
    it('turns the checkboxes on by itself for a visible bulk action, which the selectable() switch overrides either way', function (?bool $selectable, string $bulk, int $checkboxes): void {
        $this->seed(new Product('Lamp'));

        $crawler = $this->createLiveComponent(ConfiguredSelectionProductTable::class, ['selectable' => $selectable, 'bulk' => $bulk])->render()->crawler();

        expect($crawler->filter(Selectors::RECORD_CHECKBOX))->toHaveCount($checkboxes)
            ->and($crawler->filter(Selectors::PAGE_CHECKBOX))->toHaveCount($checkboxes);
    })->with([
        'a visible bulk action' => [null, 'visible', 1],
        'hidden bulk actions' => [null, 'hidden', 0],
        'no bulk action' => [null, 'none', 0],
        'switched off beside a visible bulk action' => [false, 'visible', 0],
        'switched on with no bulk action' => [true, 'none', 1],
    ]);

    it('renders an empty selection cell for a record the table refuses, handing the callback the record by name and by its own class, and nothing else', function (): void {
        $this->seed($lamp = new Product('Lamp'), $vase = new Product('Vase'));

        $crawler = $this->createLiveComponent(ConfiguredSelectionProductTable::class, ['refuses' => true])->render()->crawler();

        expect($crawler->filter(Selectors::row((string) $lamp->id) . ' ' . Selectors::RECORD_CHECKBOX))->toHaveCount(1)
            ->and($crawler->filter(Selectors::row((string) $vase->id) . ' td' . Selectors::SELECTION_CELL))->toHaveCount(1)
            ->and($crawler->filter(Selectors::row((string) $vase->id) . ' td' . Selectors::SELECTION_CELL . ' input'))->toHaveCount(0)
            ->and(array_values(array_unique($this::getContainer()->get(HookLog::class)->hooks)))->toBe(['asked about Lamp, the same record', 'asked about Vase, the same record']);
    });

    it('places the checkbox cell before the cells by default, and after them on request', function (?string $position, array $order): void {
        $this->seed($lamp = new Product('Lamp'));

        $crawler = $this->createLiveComponent(ConfiguredSelectionProductTable::class, ['checkboxPosition' => $position])->render()->crawler();
        $kinds = static fn (Crawler $cell): string => $cell->matches(Selectors::SELECTION_CELL) ? 'checkbox' : 'name';

        expect($crawler->filter(Selectors::HEADER_CELLS)->each($kinds))->toBe($order)
            ->and($crawler->filter(Selectors::row((string) $lamp->id) . ' td')->each($kinds))->toBe($order);
    })->with([
        'by default' => [null, ['checkbox', 'name']],
        'after the cells' => ['AfterCells', ['name', 'checkbox']],
    ]);

    it("hands the controller whether selection is page-only and can track deselections, page-only on a records() table whatever it sets and untracked where filtering keeps the selection", function (array $props, string $pageOnly, string $canTrack): void {
        $controller = $this->createLiveComponent(ConfiguredSelectionProductTable::class, $props)->render()->crawler()->filter(Selectors::TABLE_CONTROLLER);

        expect($controller->attr('data-sylament--tables--table-page-only-value'))->toBe($pageOnly)
            ->and($controller->attr('data-sylament--tables--table-can-track-value'))->toBe($canTrack);
    })->with([
        'by default' => [[], 'false', 'true'],
        'page-only' => [['pageOnly' => true], 'true', 'true'],
        'not tracking' => [['tracks' => false], 'false', 'false'],
        'keeping the selection when filtered' => [['keepsSelectionWhenFiltered' => true], 'false', 'false'],
        'a records() table' => [['records' => true], 'true', 'true'],
    ]);

    it("keeps the checkbox column but drops the page checkbox and the banner's select-all once only groups select", function (bool $groupsOnly, int $pageWide): void {
        $this->seed(new Product('Lamp'));

        $crawler = $this->createLiveComponent(ConfiguredSelectionProductTable::class, ['groupsOnly' => $groupsOnly])->render()->crawler();

        expect($crawler->filter('th' . Selectors::SELECTION_CELL))->toHaveCount(1)
            ->and($crawler->filter(Selectors::RECORD_CHECKBOX))->toHaveCount(1)
            ->and($crawler->filter(Selectors::PAGE_CHECKBOX))->toHaveCount($pageWide)
            ->and($crawler->filter(Selectors::SELECTION_BANNER . ' ' . Selectors::tableTarget('selectAll')))->toHaveCount($pageWide)
            ->and($crawler)->toContainElement(Selectors::SELECTION_BANNER . ' ' . SupportSelectors::LINK, 'Deselect all');
    })->with([
        'page-wide selection' => [false, 1],
        'groups only' => [true, 0],
    ]);

    it('groups its bulk actions in one bulk-only "Bulk actions" dropdown', function (): void {
        $this->seed(new Product('Lamp'));

        $crawler = $this->createLiveComponent(ConfiguredSelectionProductTable::class, ['grouped' => true])->render()->crawler();

        expect($crawler)->toContainButtons(Selectors::TOOLBAR_ACTIONS . ' ' . ActionsSelectors::BULK_ONLY . ' button', ['ellipsis-vertical-20-solidBulk actions', 'Archive', 'Archive by key', 'Recount', 'Purge'])
            ->and($crawler->filter(Selectors::TOOLBAR_ACTIONS . ' ' . SupportSelectors::DROPDOWN))->toHaveCount(1)
            ->and($crawler->filter(Selectors::RECORD_CHECKBOX))->toHaveCount(1);
    });

    it('takes its toolbar through the deprecated bulkActions() and pushBulkActions()', function (): void {
        $this->seed(new Product('Lamp'));

        $crawler = $this->createLiveComponent(ConfiguredSelectionProductTable::class, ['deprecated' => true])->render()->crawler();

        expect($crawler)->toContainButtons(Selectors::TOOLBAR_ACTIONS . ' button', ['Archive', 'Archive by key', 'Recount', 'Purge'])
            ->and($crawler->filter(Selectors::RECORD_CHECKBOX))->toHaveCount(1);
    });
});
