<?php

declare(strict_types=1);

use Sylament\Actions\Testing\Testable;
use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Tables\GroupableLarderJarTable;
use Sylament\Tests\Fixtures\Tables\GroupedArrayRowsTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Symfony\Component\DomCrawler\Crawler;

describe('the grouping state', function (): void {
    it('publishes the grouping as written, and a blank one as nothing', function (string $grouping, ?string $published): void {
        $props = $this::liveProps($this->createLiveComponent(GroupableLarderJarTable::class, ['tableGrouping' => $grouping])->render()->crawler());

        expect($props['tableGrouping'])->toBe($published);
    })->with([
        'a bare group' => ['seal', 'seal'],
        'a descending group' => ['seal:desc', 'seal:desc'],
        'a blank grouping' => ['', null],
    ]);

    it('groups the table by a group it offers in the direction asked, back on page one, and ungroups on a blank group', function (): void {
        $this->seed(
            $apple = new LarderJar('Apple', seal: JarSeal::Wax),
            $cherry = new LarderJar('Cherry', seal: JarSeal::Screw),
            $damson = new LarderJar('Damson', seal: JarSeal::Wax),
            $fig = new LarderJar('Fig', seal: JarSeal::Screw),
        );

        $grouping = fn (Testable $tester): mixed => $this::liveProps($tester->render()->crawler())['tableGrouping'];

        $tester = $this->createLiveComponent(GroupableLarderJarTable::class, ['variants' => ['paged']])
            ->call('gotoTablePage', ['page' => 2])
            ->assertCanSeeTableRecords([$damson, $fig], inOrder: true)
            ->call('groupTable', ['group' => 'seal', 'direction' => 'desc'])
            ->assertCanSeeTableRecords([$apple, $damson], inOrder: true);

        expect($grouping($tester))->toBe('seal:desc');

        $tester->call('groupTable', ['group' => 'seal', 'direction' => 'asc'])->assertCanSeeTableRecords([$cherry, $fig], inOrder: true);

        expect($grouping($tester))->toBe('seal');

        $tester->call('groupTable', ['group' => 'seal'])->assertCanSeeTableRecords([$cherry, $fig], inOrder: true);

        expect($grouping($tester))->toBe('seal');

        $tester->call('groupTable', ['group' => ''])->assertCanSeeTableRecords([$apple, $cherry], inOrder: true);

        expect($grouping($tester))->toBeNull();
    });

    it('ignores a group it does not offer and a direction it does not know, leaving the page where it was', function (array $arguments): void {
        $this->seed(new LarderJar('Apple', seal: JarSeal::Wax), new LarderJar('Cherry', seal: JarSeal::Wax), $fig = new LarderJar('Fig', seal: JarSeal::Screw));

        $tester = $this->createLiveComponent(GroupableLarderJarTable::class, ['variants' => ['paged']])
            ->call('gotoTablePage', ['page' => 2])
            ->call('groupTable', $arguments)
            ->assertCanSeeTableRecords([$fig]);
        $props = $this::liveProps($tester->render()->crawler());

        expect($props['tableGrouping'])->toBeNull()
            ->and($props['tablePage'])->toBe(2);
    })->with([
        'a group it does not offer' => [['group' => 'nope']],
        'a direction it does not know' => [['group' => 'seal', 'direction' => 'sideways']],
    ]);

    it('clears a mounted grouping the table does not offer, then seeds a default the user can select into a blank one', function (array $variants, ?string $mounted, ?string $seeded): void {
        $props = $this::liveProps($this->createLiveComponent(GroupableLarderJarTable::class, ['variants' => $variants, 'tableGrouping' => $mounted])->render()->crawler());

        expect($props['tableGrouping'])->toBe($seeded);
    })->with([
        'a group it does not offer' => [['defaultSeal'], 'nope', 'seal'],
        'a direction it does not know' => [['defaultSeal'], 'label:sideways', 'seal'],
        'a group it offers' => [['defaultSeal'], 'label:desc', 'label:desc'],
        'a blank grouping' => [['defaultSeal'], '', 'seal'],
        'no default' => [[], 'nope', null],
        'a default the user cannot select' => [['defaultPosition'], null, null],
    ]);

    it('renders ungrouped while the table is reordering, and grouped again once it stops', function (): void {
        $this->seed(new LarderJar('Apple', seal: JarSeal::Wax), new LarderJar('Fig', seal: JarSeal::Screw));

        $tester = $this->createLiveComponent(GroupableLarderJarTable::class, ['variants' => ['reorderable'], 'tableGrouping' => 'seal']);

        expect($tester->render()->crawler()->filter(Selectors::GROUP_HEADER_ROW))->toHaveCount(2)
            ->and($tester->call('toggleTableReordering')->render()->crawler()->filter(Selectors::GROUP_HEADER_ROW))->toHaveCount(0)
            ->and($tester->call('toggleTableReordering')->render()->crawler()->filter(Selectors::GROUP_HEADER_ROW))->toHaveCount(2);
    });
});

it("orders the rows by the group ahead of every sort, the user's sort and the default sort ordering the rows inside each group", function (): void {
    $this->seed(
        $apple = new LarderJar('Apple', seal: JarSeal::Screw),
        $damson = new LarderJar('Damson', seal: JarSeal::Wax),
        $cherry = new LarderJar('Cherry', seal: JarSeal::Wax),
        $fig = new LarderJar('Fig', seal: JarSeal::Screw),
    );

    $this->createLiveComponent(GroupableLarderJarTable::class, ['tableGrouping' => 'seal'])
        ->assertCanSeeTableRecords([$apple, $fig, $cherry, $damson], inOrder: true)
        ->sortTable('label', 'desc')
        ->assertCanSeeTableRecords([$fig, $apple, $damson, $cherry], inOrder: true)
        ->call('groupTable', ['group' => 'seal', 'direction' => 'desc'])
        ->assertCanSeeTableRecords([$damson, $cherry, $fig, $apple], inOrder: true)
        ->call('groupTable', ['group' => ''])
        ->assertCanSeeTableRecords([$fig, $damson, $cherry, $apple], inOrder: true);
});

describe('the group boundaries', function (): void {
    it('maps each record on the page to where it sits in its group, keyed as the records are, and nothing while ungrouped', function (): void {
        $this->seed($apple = new LarderJar('Apple', seal: JarSeal::Wax), $cherry = new LarderJar('Cherry', seal: JarSeal::Wax), $fig = new LarderJar('Fig', seal: JarSeal::Screw));

        $boundary = static fn (string $seal, bool $first, bool $last, int $index): array => ['key' => $seal, 'title' => $seal, 'description' => null, 'isFirstOfGroup' => $first, 'isLastOfGroup' => $last, 'indexInGroup' => $index];
        $boundaries = $this->createLiveComponent(GroupableLarderJarTable::class, ['tableGrouping' => 'seal'])->component()->getTableRecordGroupBoundaries();

        expect(array_keys($boundaries))->toBe([$fig->id, $apple->id, $cherry->id])
            ->and($boundaries)->toBe([
                $fig->id => $boundary('screw', true, true, 0),
                $apple->id => $boundary('wax', true, false, 0),
                $cherry->id => $boundary('wax', false, true, 1),
            ])
            ->and($this->createLiveComponent(GroupableLarderJarTable::class)->component()->getTableRecordGroupBoundaries())->toBe([]);
    });

    it("closes the page's last group where the next page carries it on only on a table that summarizes it", function (array $variants, bool $closes): void {
        $this->seed(new LarderJar('Apple', seal: JarSeal::Wax), $cherry = new LarderJar('Cherry', seal: JarSeal::Wax), new LarderJar('Damson', seal: JarSeal::Wax));

        $boundaries = $this->createLiveComponent(GroupableLarderJarTable::class, ['variants' => $variants, 'tableGrouping' => 'seal'])->component()->getTableRecordGroupBoundaries();

        expect($boundaries[$cherry->id]['isLastOfGroup'])->toBe($closes);
    })->with([
        'unsummarized' => [['paged'], true],
        'summarized' => [['paged', 'summarized'], false],
    ]);
});

describe('the group header rows', function (): void {
    it('heads each group with a row as wide as the table, again at each group down the page', function (array $variants): void {
        $this->seed(new LarderJar('Apple', seal: JarSeal::Wax), new LarderJar('Cherry', seal: JarSeal::Screw), new LarderJar('Fig', seal: JarSeal::Wax));

        $crawler = $this->createLiveComponent(GroupableLarderJarTable::class, ['variants' => $variants, 'tableGrouping' => 'seal'])->render()->crawler();
        $width = count($crawler->filter(Selectors::HEADER_CELLS));

        expect($crawler->filter(Selectors::BODY_ROW)->each(static fn (Crawler $row): string => $row->matches(Selectors::GROUP_HEADER_ROW) ? (string) $row->attr('data-sy-group') : 'record'))->toBe(['screw', 'record', 'wax', 'record', 'record'])
            ->and($crawler->filter(Selectors::GROUP_HEADER_ROW)->each(static fn (Crawler $row): int => array_sum($row->filter('td')->each(static fn (Crawler $cell): int => (int) ($cell->attr('colspan') ?? 1)))))->toBe([$width, $width]);
    })->with([
        'a plain table' => [[]],
        'a table that selects and acts on its records' => [['selectable']],
    ]);

    it("binds a group's collapse and tick to the table controller, the tick naming its group's keys and checked once all of them are selected", function (): void {
        $this->seed($apple = new LarderJar('Apple', seal: JarSeal::Wax), $cherry = new LarderJar('Cherry', seal: JarSeal::Wax), $fig = new LarderJar('Fig', seal: JarSeal::Screw));

        $crawler = $this->createLiveComponent(GroupableLarderJarTable::class, ['variants' => ['selectable'], 'tableGrouping' => 'seal'])
            ->selectTableRecords([$apple, $cherry])
            ->render()
            ->crawler();
        $wax = $crawler->filter(Selectors::groupHeaderRow('wax'));
        $tick = $wax->filter('input[type="checkbox"]' . Selectors::tableTarget('groupCheckbox'));

        expect($tick->attr('data-action'))->toBe('change->sylament--tables--table#toggleGroupRecords')
            ->and(json_decode((string) $tick->attr('data-sy-group-keys'), true))->toBe([(string) $apple->id, (string) $cherry->id])
            ->and($tick->attr('checked'))->not->toBeNull()
            ->and($crawler->filter(Selectors::groupHeaderRow('screw') . ' input' . Selectors::tableTarget('groupCheckbox'))->attr('checked'))->toBeNull()
            ->and($wax->filter(Selectors::GROUP_HEADER)->attr('data-action'))->toBe('click->sylament--tables--table#toggleGroup')
            ->and($wax->filter('button[aria-label="Seal: wax"][aria-expanded="true"]')->attr('data-action'))->toBe('click->sylament--tables--table#toggleGroup')
            ->and($crawler->filter(Selectors::GROUP_BY_SELECT . Selectors::tableTarget('groupColumn') . '[data-action="change->sylament--tables--table#group"]'))->toHaveCount(2)
            ->and($crawler->filter(Selectors::GROUP_DIRECTION_SELECT . Selectors::tableTarget('groupDirection') . '[data-action="change->sylament--tables--table#group"]'))->toHaveCount(2);
    });

    it('opens one heading for the records of two keys a title closure titles alike', function (): void {
        $crawler = $this->createLiveComponent(GroupedArrayRowsTable::class, ['tableGrouping' => 'code'])->render()->crawler();

        expect($crawler->filter(Selectors::GROUP_HEADER_ROW)->each(static fn (Crawler $row): array => [$row->attr('data-sy-group'), mb_strstr((string) $row->attr('id'), '.')]))->toBe([
            ['North', '.table.groups.N1'],
            ['South', '.table.groups.S1'],
        ])
            ->and($crawler->filter(Selectors::ROW)->each(static fn (Crawler $row): ?string => $row->attr('data-sy-group')))->toBe(['North', 'North', 'South']);
    });

    it("keeps the ids of two keys apart where they differ only by a space, encoding a slash and a colon too, in the header row, its checkbox and the group's summary row, where the data attributes keep the key as written", function (): void {
        $this->seed(new LarderJar('Zero 7'), new LarderJar('Zero  7'), new LarderJar('A/B:C'));

        $crawler = $this->createLiveComponent(GroupableLarderJarTable::class, ['variants' => ['selectable', 'summarized'], 'tableGrouping' => 'label'])->render()->crawler();
        $suffix = static fn (?string $id): string => (string) mb_strstr((string) $id, '.');

        expect($crawler->filter(Selectors::GROUP_HEADER_ROW)->each(static fn (Crawler $row): array => [$row->attr('data-sy-group'), $suffix($row->attr('id')), $suffix($row->filter('input[type="checkbox"]')->attr('id'))]))->toEqualCanonicalizing([
            ['Zero 7', '.table.groups.Zero%207', '.table.groups.Zero%207.checkbox'],
            ['Zero  7', '.table.groups.Zero%20%207', '.table.groups.Zero%20%207.checkbox'],
            ['A/B:C', '.table.groups.A%2FB%3AC', '.table.groups.A%2FB%3AC.checkbox'],
        ])
            ->and($crawler->filter(Selectors::GROUP_SUMMARY_ROW)->each(static fn (Crawler $row): string => $suffix($row->attr('id'))))->toEqualCanonicalizing(['.table.group-summary.Zero%207', '.table.group-summary.Zero%20%207', '.table.group-summary.A%2FB%3AC'])
            ->and($crawler->filter(Selectors::ROW)->each(static fn (Crawler $row): ?string => $row->attr('data-sy-group')))->toEqualCanonicalizing(['Zero 7', 'Zero  7', 'A/B:C']);
    });

    it('restarts the stripe at the first record of every group', function (?string $grouping, array $striped): void {
        $this->seed(
            new LarderJar('Apple', seal: JarSeal::Wax),
            new LarderJar('Cherry', seal: JarSeal::Wax),
            new LarderJar('Fig', seal: JarSeal::Screw),
            new LarderJar('Grape', seal: JarSeal::Screw),
            new LarderJar('Hazel', seal: JarSeal::Screw),
        );

        $rows = $this->createLiveComponent(GroupableLarderJarTable::class, ['variants' => ['striped'], 'tableGrouping' => $grouping])->render()->crawler()->filter(Selectors::ROW);

        expect($rows->each(static fn (Crawler $row): bool => $row->matches(Selectors::STRIPED)))->toBe($striped);
    })->with([
        'grouped' => ['seal', [false, true, false, false, true]],
        'ungrouped' => [null, [false, true, false, true, false]],
    ]);
});

it('groups the rows a records closure returns in the order it returns them, opening a heading again each time a value comes round', function (bool $outOfOrder, array $headings): void {
    $headers = $this->createLiveComponent(GroupedArrayRowsTable::class, ['outOfOrder' => $outOfOrder, 'tableGrouping' => 'zone'])->render()->crawler()->filter(Selectors::GROUP_HEADER_ROW);

    expect($headers->each(static fn (Crawler $row): ?string => $row->attr('data-sy-group')))->toBe($headings);
})->with([
    'in group order' => [false, ['North', 'South']],
    'out of group order' => [true, ['North', 'South', 'North']],
]);
