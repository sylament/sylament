<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\GroupableLarderJarTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Symfony\Component\DomCrawler\Crawler;

describe('the groups', function (): void {
    it('offers each group it declares in the selector, after "-", in the order declared', function (): void {
        $options = $this->createLiveComponent(GroupableLarderJarTable::class)->render()->crawler()->filter(Selectors::GROUP_BY_SELECT)->first()->filter('option');

        expect($options->each(static fn (Crawler $option): ?string => $option->attr('value')))->toBe(['', 'seal', 'sealedOn', 'supplier.name', 'larder.name', 'label'])
            ->and($options->filter('[selected]')->attr('value'))->toBe('');
    });

    it('seeds a default group the user can select, shown selected, and lets the user pick "-" to ungroup', function (): void {
        $this->seed(new LarderJar('Apple', seal: JarSeal::Wax), new LarderJar('Fig', seal: JarSeal::Screw));

        $tester = $this->createLiveComponent(GroupableLarderJarTable::class, ['variants' => ['defaultSeal']]);
        $grouped = $tester->render()->crawler();

        expect($grouped->filter(Selectors::GROUP_HEADER_ROW))->toHaveCount(2)
            ->and($grouped->filter(Selectors::GROUP_BY_SELECT . ' option[selected]')->each(static fn (Crawler $option): ?string => $option->attr('value')))->toBe(['seal', 'seal']);

        $ungrouped = $tester->call('groupTable', ['group' => ''])->render()->crawler();

        expect($ungrouped->filter(Selectors::GROUP_HEADER_ROW))->toHaveCount(0)
            ->and($ungrouped->filter(Selectors::ROW))->toHaveCount(2)
            ->and($ungrouped->filter(Selectors::GROUP_BY_SELECT . ' option[selected]')->each(static fn (Crawler $option): ?string => $option->attr('value')))->toBe(['', '']);
    });

    it('groups by a default the user cannot select, ascending and never offered, until the user picks a group it offers', function (string $variant, string $heading): void {
        $this->seed($apple = new LarderJar('Apple', position: 2), $fig = new LarderJar('Fig', position: 1));

        $tester = $this->createLiveComponent(GroupableLarderJarTable::class, ['variants' => [$variant]]);
        $crawler = $tester->render()->crawler();

        expect($crawler->filter(Selectors::GROUP_HEADING)->each(static fn (Crawler $heading): string => $heading->text()))->toBe(["{$heading}: 1", "{$heading}: 2"])
            ->and($crawler->filter(Selectors::GROUP_BY_SELECT)->first()->filter('option')->each(static fn (Crawler $option): ?string => $option->attr('value')))->toBe(['', 'seal', 'sealedOn', 'supplier.name', 'larder.name', 'label']);

        $tester->assertCanSeeTableRecords([$fig, $apple], inOrder: true)->call('groupTable', ['group' => 'label']);

        expect($tester->render()->crawler()->filter(Selectors::GROUP_HEADING)->each(static fn (Crawler $heading): string => $heading->text()))->toBe(['Label: Apple', 'Label: Fig']);
    })->with([
        'by an id it does not offer' => ['defaultPosition', 'Position'],
        'by a group it declares' => ['defaultInstance', 'Shelf position'],
    ]);
});

describe('groupsOnly()', function (): void {
    it('renders one summary row per group and no record row, the whole table on one page', function (): void {
        $this->seed(
            new LarderJar('Apple', seal: JarSeal::Wax),
            new LarderJar('Cherry', seal: JarSeal::Wax),
            new LarderJar('Damson', seal: JarSeal::Screw),
            new LarderJar('Fig', seal: JarSeal::Screw),
            new LarderJar('Grape'),
        );

        $crawler = $this->createLiveComponent(GroupableLarderJarTable::class, ['variants' => ['groupsOnly', 'summarized', 'paged', 'defaultSeal']])->render()->crawler();

        expect($crawler->filter(Selectors::GROUP_SUMMARY_ROW)->each(static fn (Crawler $row): string => mb_strstr((string) $row->attr('id'), '.')))->toEqualCanonicalizing(['.table.group-summary.screw', '.table.group-summary.wax', '.table.group-summary.'])
            ->and($crawler->filter(Selectors::ROW))->toHaveCount(0)
            ->and($crawler->filter(Selectors::GROUP_HEADER_ROW))->toHaveCount(0)
            ->and($crawler->filter(SupportSelectors::PAGINATION))->toHaveCount(0);
    });

    it('keeps its record rows while nothing is grouped but loses its pagination, and renders no row for a group nothing summarizes', function (): void {
        $this->seed(new LarderJar('Apple', seal: JarSeal::Wax), new LarderJar('Cherry', seal: JarSeal::Wax), new LarderJar('Fig', seal: JarSeal::Screw));

        $ungrouped = $this->createLiveComponent(GroupableLarderJarTable::class, ['variants' => ['groupsOnly', 'summarized', 'paged']])->render()->crawler();
        $unsummarized = $this->createLiveComponent(GroupableLarderJarTable::class, ['variants' => ['groupsOnly', 'defaultSeal']])->render()->crawler();

        expect($ungrouped->filter(Selectors::ROW))->toHaveCount(3)
            ->and($ungrouped->filter(SupportSelectors::PAGINATION))->toHaveCount(0)
            ->and($unsummarized->filter(Selectors::TABLE))->toHaveCount(1)
            ->and($unsummarized->filter('tbody tr'))->toHaveCount(0);
    });
});

describe('the grouping settings', function (): void {
    it('hides the settings, the direction field or the inline copy of them as the table says', function (array $variants, int $copies, int $directions, bool $dropdownOnDesktop): void {
        $crawler = $this->createLiveComponent(GroupableLarderJarTable::class, ['variants' => $variants])->render()->crawler();

        expect($crawler->filter(Selectors::GROUP_BY_SELECT))->toHaveCount($copies)
            ->and($crawler->filter(Selectors::GROUP_DIRECTION_SELECT))->toHaveCount($directions)
            ->and($crawler->filter('button[aria-label="Group"]'))->toHaveCount($copies === 0 ? 0 : 1)
            ->and($crawler->filter('.sy-ta-grouping-settings.sy-ta-grouping-settings-dropdown-on-desktop'))->toHaveCount($dropdownOnDesktop ? 1 : 0);
    })->with([
        'all shown' => [[], 2, 2, false],
        'the settings hidden' => [['settingsHidden'], 0, 0, false],
        'the direction hidden' => [['directionHidden'], 2, 0, false],
        'in the dropdown on desktop' => [['dropdownOnDesktop'], 1, 1, true],
    ]);

    it('opens the settings from an icon button named "Group" showing the grouping icon, which the table can modify', function (array $variants, string $label): void {
        $settings = $this->createLiveComponent(GroupableLarderJarTable::class, ['variants' => $variants])->render()->crawler()->filter('.sy-ta-grouping-settings [data-controller="sylament--support--dropdown"]');

        expect($settings->filter('[data-sylament--support--dropdown-target="trigger"]')->filter("button[aria-label=\"{$label}\"]")->text())->toBe('rectangle-stack-20-solid')
            ->and($settings->filter('[data-sylament--support--dropdown-target="panel"]')->filter(Selectors::GROUP_BY_SELECT))->toHaveCount(1);
    })->with([
        'as shipped' => [[], 'Group'],
        'modified' => [['relabelledTrigger'], 'Regroup'],
    ]);

    it('selects the group and the direction in force in both copies, hiding the direction while no group is chosen', function (?string $grouping, string $group, string $direction): void {
        $crawler = $this->createLiveComponent(GroupableLarderJarTable::class, ['tableGrouping' => $grouping])->render()->crawler();
        $selected = static fn (string $select): array => $crawler->filter("{$select} option[selected]")->each(static fn (Crawler $option): ?string => $option->attr('value'));

        expect($selected(Selectors::GROUP_BY_SELECT))->toBe([$group, $group])
            ->and($selected(Selectors::GROUP_DIRECTION_SELECT))->toBe([$direction, $direction])
            ->and($crawler->filter('label[data-sylament--tables--table-target="groupDirectionField"]'))->toHaveCount(2)
            ->and($crawler->filter('label[data-sylament--tables--table-target="groupDirectionField"][hidden]'))->toHaveCount($group === '' ? 2 : 0);
    })->with([
        'descending' => ['label:desc', 'label', 'desc'],
        'a bare group' => ['label', 'label', 'asc'],
        'no group' => [null, '', 'asc'],
    ]);

    it('paints the groups of a collapsible grouping collapsed by default, every row but the header row', function (string $grouping, int $groups, bool $collapsed): void {
        $this->seed(new LarderJar('Apple', seal: JarSeal::Wax), new LarderJar('Cherry', seal: JarSeal::Wax), new LarderJar('Fig', seal: JarSeal::Screw));

        $crawler = $this->createLiveComponent(GroupableLarderJarTable::class, ['variants' => ['collapsed'], 'tableGrouping' => $grouping])->render()->crawler();

        expect($crawler->filter(Selectors::GROUP_HEADER_ROW))->toHaveCount($groups)
            ->and($crawler->filter(Selectors::GROUP_HEADER_ROW . ' ' . Selectors::GROUP_HEADER . '.sy-collapsed'))->toHaveCount($collapsed ? $groups : 0)
            ->and($crawler->filter(Selectors::GROUP_HEADER_ROW . ' button[aria-expanded="false"]'))->toHaveCount($collapsed ? $groups : 0)
            ->and($crawler->filter(Selectors::ROW . '.sy-collapsed'))->toHaveCount($collapsed ? 3 : 0)
            ->and($crawler->filter(Selectors::GROUP_HEADER_ROW . '.sy-collapsed'))->toHaveCount(0)
            ->and($crawler->filter('[data-sylament--tables--table-groups-collapsed-by-default-value="true"]'))->toHaveCount(1);
    })->with([
        'a collapsible group' => ['seal', 2, true],
        'a group that does not collapse' => ['label', 3, false],
    ]);
});
