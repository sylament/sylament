<?php

declare(strict_types=1);

use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\AlignedColumnsTable;
use Sylament\Tests\Fixtures\Tables\BrowsableProductTable;
use Sylament\Tests\Fixtures\Tables\ClickableCellsLarderJarTable;
use Sylament\Tests\Fixtures\Tables\FilterableLarderJarTable;
use Sylament\Tests\Fixtures\Tables\GroupedLarderJarTable;
use Sylament\Tests\Fixtures\Tables\HeadedLarderJarTable;
use Sylament\Tests\Fixtures\Tables\LinkedLarderJarTable;
use Sylament\Tests\Fixtures\Tables\ProductTable;
use Sylament\Tests\Fixtures\Tables\ReorderableLarderJarTable;
use Sylament\Tests\Fixtures\Tables\SelectionReadingProductTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Symfony\Component\DomCrawler\Crawler;

describe('partial', function (): void {
    it('renders its records inside the scoped root, with no header or toolbar band while nothing goes in them', function (): void {
        $this->seed(new Product('Lamp'));

        $crawler = $this->createLiveComponent(ProductTable::class)->render()->crawler();

        expect($crawler->filter('.sy.sy-ta ' . Selectors::TABLE))->toHaveCount(1)
            ->and($crawler->filter(Selectors::TABLE)->attr('aria-label'))->toBeNull()
            ->and($crawler->filter(Selectors::HEADER))->toHaveCount(0)
            ->and($crawler->filter(Selectors::TOOLBAR))->toHaveCount(0);
    });

    it('renders a header band for a heading and a toolbar band for its search, the search at the end of it', function (): void {
        $crawler = $this->createLiveComponent(HeadedLarderJarTable::class)->render()->crawler();

        expect($crawler->filter(Selectors::HEADER))->toHaveCount(1)
            ->and($crawler->filter(Selectors::TOOLBAR . ' .sy-ta-header-toolbar-end ' . Selectors::SEARCH))->toHaveCount(1);
    });
});

it('hands a closure on the table the table and its host', function (): void {
    $host = new AlignedColumnsTable();
    $table = $host->getTable();

    $table->heading(static fn (Table $table, object $liveComponent): string => sprintf('table %s, host %s', $table === $host->getTable() ? 'bound' : 'not bound', $liveComponent === $host ? 'bound' : 'not bound'));

    expect($table->getHeading())->toBe('table bound, host bound');
});

describe('morph keys', function (): void {
    it('keys every row and cell as a document id by host, record and column, the same on the next request', function (): void {
        $this->seed($lamp = new Product('Lamp'), $desk = new Product('Desk'));

        $tester = $this->createLiveComponent(ProductTable::class);
        $hostId = $tester->component()->getTableHostId();
        $ids = static fn (Crawler $crawler): array => [
            $crawler->filter(Selectors::ROW)->each(static fn (Crawler $row): ?string => $row->attr('id')),
            $crawler->filter(Selectors::ROW . ' td[id]')->each(static fn (Crawler $cell): ?string => $cell->attr('id')),
        ];
        $first = $ids($tester->render()->crawler());

        expect($first)->toBe([
            ["{$hostId}.table.records.{$desk->id}", "{$hostId}.table.records.{$lamp->id}"],
            ["{$hostId}.table.record.{$desk->id}.column.name", "{$hostId}.table.record.{$lamp->id}.column.name"],
        ])
            ->and($ids($tester->refresh()->render()->crawler()))->toBe($first)
            ->and($tester->render()->crawler()->filterXPath('//*[@*[name()="wire:key"]]'))->toHaveCount(0);
    });
});

describe('row chrome', function (): void {
    it('marks a row clickable when its record links or clicks somewhere, and no other row', function (array $props, bool $clickable): void {
        $this->seed($apricot = new LarderJar('Apricot'), $hazard = new LarderJar('Hazard'));

        $crawler = $this->createLiveComponent(LinkedLarderJarTable::class, $props)->render()->crawler();

        expect($crawler->filter(Selectors::row((string) $apricot->id) . '.sy-clickable'))->toHaveCount(1)
            ->and($crawler->filter(Selectors::row((string) $hazard->id) . '.sy-clickable'))->toHaveCount($clickable ? 1 : 0);
    })->with([
        'a record URL, unsafe for one record' => [[], false],
        'a record action' => [['clickAction' => 'view'], true],
    ]);

    it('tints the row of a selected record', function (): void {
        $this->seed($lamp = new Product('Lamp'), $desk = new Product('Desk'));

        $crawler = $this->createLiveComponent(SelectionReadingProductTable::class)->selectTableRecords([$lamp])->render()->crawler();

        expect($crawler->filter(Selectors::row((string) $lamp->id) . SupportSelectors::SELECTED))->toHaveCount(1)
            ->and($crawler->filter(Selectors::row((string) $desk->id)))->toHaveCount(1)
            ->and($crawler->filter(Selectors::row((string) $desk->id) . SupportSelectors::SELECTED))->toHaveCount(0);
    });
});

describe('cell wrapper', function (): void {
    it('wraps a cell by what clicking it does, the column before the record', function (string $column, string $tag, array $attributes): void {
        $this->seed($jar = new LarderJar('Apricot'));
        $key = (string) $jar->id;

        $wrapper = $this->createLiveComponent(ClickableCellsLarderJarTable::class)
            ->render()
            ->crawler()
            ->filter(Selectors::cell($key, $column) . ' ' . Selectors::CELL_WRAPPER);

        expect($wrapper->nodeName())->toBe($tag);

        foreach ($attributes as $attribute => $value) {
            expect($wrapper->attr($attribute))->toBe($value === null ? null : str_replace('{key}', $key, $value));
        }
    })->with([
        "a column URL over the record's, in the column's new tab" => ['label', 'a', ['href' => '/labels/{key}', 'target' => '_blank']],
        "a column action over the record's URL" => ['position', 'button', ['href' => null, 'data-live-action-param' => 'mountAction', 'data-live-name-param' => 'restack', 'data-live-context-param' => '{"recordKey":"{key}","table":true}']],
        "a column closure over the record's URL" => ['shelf', 'button', ['href' => null, 'data-live-action-param' => 'callTableColumnAction', 'data-live-name-param' => 'shelf', 'data-live-record-key-param' => '{key}']],
        'URLs read from the state' => ['maker', 'div', ['href' => null]],
        'a click disabled' => ['sealedOn', 'div', ['href' => null]],
        "the record's URL, in no new tab" => ['seal', 'a', ['href' => '/jars/{key}', 'target' => null]],
    ]);
});

describe('header cells', function (): void {
    it('names the sort state of each sortable column and none of the others', function (?string $direction, string $label): void {
        $this->seed(new LarderJar('Apricot'));

        $tester = $this->createLiveComponent(ClickableCellsLarderJarTable::class);

        if ($direction !== null) {
            $tester->sortTable('label', $direction);
        }

        $crawler = $tester->render()->crawler();

        expect($crawler->filter(Selectors::sortState('label', $label)))->toHaveCount(1)
            ->and($crawler->filter(Selectors::sortState('seal', 'none')))->toHaveCount(1)
            ->and($crawler->filter(Selectors::headerCell('position')))->toHaveCount(1)
            ->and($crawler->filter(Selectors::headerCell('position'))->attr('aria-sort'))->toBeNull();
    })->with([
        'unsorted' => [null, 'none'],
        'ascending' => ['asc', 'ascending'],
        'descending' => ['desc', 'descending'],
    ]);

    it('draws the sort chevron pointing the way the column sorts', function (string $direction, string $sorted): void {
        $this->seed(new LarderJar('Apricot'));

        $crawler = $this->createLiveComponent(ClickableCellsLarderJarTable::class)->sortTable('label', $direction)->render()->crawler();

        expect($crawler->filter(Selectors::sortTrigger('label') . ' svg title')->text())->toBe($sorted)
            ->and($crawler->filter(Selectors::sortTrigger('label') . ' svg')->attr('viewBox'))->toBe('0 0 20 20')
            ->and($crawler->filter(Selectors::sortTrigger('seal') . ' svg title')->text())->toBe('chevron-down-20-solid');
    })->with([
        'ascending' => ['asc', 'chevron-up-20-solid'],
        'descending' => ['desc', 'chevron-down-20-solid'],
    ]);

    it('sorts by a markup label from a span naming its three events, and by a text label from a button', function (): void {
        $this->seed(new LarderJar('Apricot'));

        $crawler = $this->createLiveComponent(ClickableCellsLarderJarTable::class)->render()->crawler();
        $span = $crawler->filter(Selectors::sortTrigger('seal'));

        expect($span->nodeName())->toBe('span')
            ->and($span->attr('role'))->toBe('button')
            ->and($span->attr('tabindex'))->toBe('0')
            ->and($span->attr('data-action'))->toBe('click->live#action keydown.enter->live#action keydown.space->live#action')
            ->and($span->filter('em')->text())->toBe('Seal')
            ->and($crawler->filter(Selectors::sortTrigger('label'))->nodeName())->toBe('button');
    });
});

describe('selection controller', function (): void {
    it('renders the selection count map for the controller', function (): void {
        $this->seed(new Product('Lamp'));

        $controller = $this->createLiveComponent(SelectionReadingProductTable::class)->render()->crawler()->filter(Selectors::TABLE_CONTROLLER);

        expect(json_decode((string) $controller->attr('data-sylament--tables--table-count-messages-value'), associative: true))
            ->toBe(['one' => '__COUNT__ record selected', 'other' => '__COUNT__ records selected']);
    });

    it("mounts the table controller on a selectable table with its values, its banner hidden until a record is selected and each record's checkbox ticking on change", function (): void {
        $this->seed($lamp = new Product('Lamp'));

        $tester = $this->createLiveComponent(SelectionReadingProductTable::class);
        $unselected = $tester->render()->crawler();
        $selected = $tester->selectTableRecords([$lamp])->render()->crawler();
        $checkbox = $unselected->filter(Selectors::recordCheckbox((string) $lamp->id));

        expect($unselected->filter(Selectors::TABLE_CONTROLLER . Selectors::tableValue('can-track', 'true') . Selectors::tableValue('page-only', 'false') . Selectors::tableValue('all-selectable-count', '1')))->toHaveCount(1)
            ->and($unselected->filter(Selectors::SELECTION_BANNER . '[hidden]'))->toHaveCount(1)
            ->and($unselected->filter(Selectors::tableTarget('count')))->toHaveCount(1)
            ->and($unselected->filter(Selectors::tableTarget('selectAll')))->toHaveCount(1)
            ->and($unselected->filter(Selectors::tableTarget('deselectAll')))->toHaveCount(1)
            ->and($checkbox->attr('value'))->toBe((string) $lamp->id)
            ->and($checkbox->attr('data-action'))->toBe('change->sylament--tables--table#toggleRecord')
            ->and($selected->filter(Selectors::SELECTION_BANNER))->toHaveCount(1)
            ->and($selected->filter(Selectors::SELECTION_BANNER . '[hidden]'))->toHaveCount(0);
    });

    it('mounts the table controller only on a table that selects, reorders, defers its loading or groups', function (string $host, array $props, int $controllers): void {
        $this->seed(new Product('Lamp'), new LarderJar('Apricot'));

        expect($this->createLiveComponent($host, $props)->render()->crawler()->filter(Selectors::TABLE_CONTROLLER))->toHaveCount($controllers);
    })->with([
        'a plain table' => [ProductTable::class, [], 0],
        'a selectable table' => [SelectionReadingProductTable::class, [], 1],
        'a reorderable table' => [ReorderableLarderJarTable::class, [], 1],
        'a deferred table' => [BrowsableProductTable::class, ['defersLoading' => true], 1],
        'a grouped table selecting nothing' => [GroupedLarderJarTable::class, ['tableGrouping' => 'label', 'groupsOnly' => true], 1],
    ]);
});

describe('pagination footer', function (): void {
    it('renders the footer under a table with records, on an empty page past the end too', function (bool $simplePagination, bool $hasRecords, int $page, bool $footer): void {
        if ($hasRecords) {
            $this->seed(new LarderJar('Apricot'));
        }

        $tester = $this->createLiveComponent(GroupedLarderJarTable::class, ['simplePagination' => $simplePagination, 'tablePage' => $page]);

        $crawler = $tester->render()->crawler();

        expect($crawler->filter(Selectors::ROW))->toHaveCount(0)
            ->and($crawler->filter(SupportSelectors::PAGINATION))->toHaveCount($footer ? 1 : 0);
    })->with([
        'no records' => [false, false, 1, false],
        'a page past the end' => [false, true, 3, true],
        'no records, simply paginated' => [true, false, 1, false],
        'a page past the end, simply paginated' => [true, true, 3, true],
    ]);
});

describe('grouping', function (): void {
    it('labels each select above it in the dropdown copy of the grouping settings, and in the inline copy prefixes the group select and names the direction for screen readers only', function (): void {
        $settings = $this->createLiveComponent(GroupedLarderJarTable::class, ['tableGrouping' => 'label'])->render()->crawler()->filter('.sy-ta-grouping-settings-fields');
        $dropdown = $settings->first();
        $inline = $settings->last();

        expect($settings)->toHaveCount(2)
            ->and($inline->filter('.sy-ta-grouping-settings-field-label'))->toHaveCount(0)
            ->and($inline->filter(SupportSelectors::INPUT_WRAPPER_LABEL)->text())->toBe('Group by')
            ->and($inline)->toContainElement(SupportSelectors::SCREEN_READER_ONLY, 'Group direction')
            ->and($inline->filter(Selectors::GROUP_DIRECTION_SELECT))->toHaveCount(1)
            ->and($dropdown->filter('.sy-ta-grouping-settings-field-label')->each(static fn (Crawler $label): string => $label->text()))->toBe(['Group by', 'Group direction'])
            ->and($dropdown->filter(SupportSelectors::INPUT_WRAPPER_LABEL))->toHaveCount(0);
    });

    it("lands a group's checkbox under the selection header, the record actions beside it on the same side", function (string $position): void {
        $this->seed(new LarderJar('Apricot'));

        $crawler = $this->createLiveComponent(GroupedLarderJarTable::class, ['tableGrouping' => 'label', 'actionsPosition' => $position, 'checkboxPosition' => $position])->render()->crawler();
        $headerCells = $crawler->filter(Selectors::HEADER_CELLS)->each(static fn (Crawler $cell): bool => $cell->matches(Selectors::SELECTION_CELL));
        $column = 0;

        foreach ($crawler->filter(Selectors::groupHeaderRow('Apricot') . ' td')->each(static fn (Crawler $cell): array => [$cell->filter(Selectors::GROUP_CHECKBOX)->count() > 0, (int) ($cell->attr('colspan') ?? 1)]) as [$holdsCheckbox, $span]) {
            if ($holdsCheckbox) {
                break;
            }

            $column += $span;
        }

        expect($column)->toBe(array_search(true, $headerCells, true));
    })->with([
        'before the cells' => ['BeforeCells'],
        'after the cells' => ['AfterCells'],
    ]);

    it("names a group titled by markup by the title's text in its checkbox and its collapse control", function (): void {
        $this->seed(new LarderJar('Apricot'));

        $header = $this->createLiveComponent(GroupedLarderJarTable::class, ['tableGrouping' => 'marked'])->render()->crawler()->filter(Selectors::groupHeaderRow('<b>Apricot</b>'));

        expect($header->filter('input[type="checkbox"]')->attr('aria-label'))->toBe('Select/deselect every record in group Apricot for bulk actions.')
            ->and($header->filter('button[aria-expanded]')->attr('aria-label'))->toBe('Marked: Apricot')
            ->and($header->filter('button[aria-expanded]')->attr('title'))->toBe('Marked: Apricot');
    });

    it('closes a group with no title with no summary row of its own, except where the group is the row', function (bool $groupsOnly, int $blankSummaryRows): void {
        $this->seed(new LarderJar('', position: 1), new LarderJar('Apricot', position: 2));

        $summaryRows = $this->createLiveComponent(GroupedLarderJarTable::class, ['tableGrouping' => 'label', 'groupsOnly' => $groupsOnly])
            ->render()
            ->crawler()
            ->filter(Selectors::GROUP_SUMMARY_ROW);

        expect($summaryRows->each(static fn (Crawler $row): ?string => $row->attr('id')))->toHaveCount(1 + $blankSummaryRows)
            ->and($summaryRows->last()->attr('id'))->toEndWith('.table.group-summary.Apricot');
    })->with([
        'grouped records' => [false, 0],
        'groups only' => [true, 1],
    ]);
});

describe('summary', function (): void {
    it('renders the page summary only on a page holding records, past the last page of a column-searchable table included', function (int $page, int $pageRows): void {
        $this->seed(...array_map(static fn (int $position): LarderJar => new LarderJar("Jar {$position}", position: $position), range(1, 6)));

        $crawler = $this->createLiveComponent(GroupedLarderJarTable::class, ['tablePage' => $page])->render()->crawler();

        expect($crawler->filter(Selectors::summaryRow('all')))->toHaveCount(1)
            ->and($crawler->filter(Selectors::summaryRow('page')))->toHaveCount($pageRows);
    })->with([
        'the first page' => [1, 1],
        'past the last page' => [3, 0],
    ]);
});

describe('column search row', function (): void {
    it("keys each column's search cell and input by the column's name, and spans the row across every cell the head emits", function (bool $hasRecords): void {
        if ($hasRecords) {
            $this->seed(new LarderJar('Apricot'));
        }

        $tester = $this->createLiveComponent(GroupedLarderJarTable::class);
        $hostId = $tester->component()->getTableHostId();
        $crawler = $tester->render()->crawler();
        $searchCells = $crawler->filter(Selectors::COLUMN_SEARCH_ROW . ' td');

        expect($crawler->filter(Selectors::columnSearch('label'))->attr('id'))->toBe("{$hostId}.table.column-search.label.input")
            ->and($searchCells->each(static fn (Crawler $cell): ?string => $cell->attr('id')))->toContain("{$hostId}.table.column-search.label", "{$hostId}.table.column-search.position")
            ->and($searchCells)->toHaveCount($crawler->filter(Selectors::HEADER_CELLS)->count());
    })->with([
        'records' => [true],
        'no records' => [false],
    ]);
});

describe('filters chrome', function (): void {
    it('teleports the filters dropdown and counts the active filters on an extra-small badge beside its trigger', function (): void {
        $crawler = $this->createLiveComponent(FilterableLarderJarTable::class)->filterTable('seal', JarSeal::Wax->value)->render()->crawler();

        expect($crawler->filter('.sy-ta-filters-dropdown' . SupportSelectors::TELEPORTED_DROPDOWN . ' ' . Selectors::FILTERS_TRIGGER))->toHaveCount(1)
            ->and($crawler)->toContainElement('.sy-ta-filters-trigger-badge-ctn ' . SupportSelectors::BADGE . SupportSelectors::size('xs'), '1');
    });
});
