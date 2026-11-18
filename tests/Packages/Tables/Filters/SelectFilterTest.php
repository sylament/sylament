<?php

declare(strict_types=1);

use Doctrine\ORM\QueryBuilder;
use Sylament\Tables\Filters\SelectFilter;
use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Tables\DefaultedJarFilterTable;
use Sylament\Tests\Fixtures\Tables\FilterableLarderJarTable;
use Sylament\Tests\Fixtures\Tables\QueryingJarFilterTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\ShapedJarFilterTable;
use Symfony\Component\DomCrawler\Crawler;

describe('the field', function (): void {
    it('builds a select named by its label over its options, its default chosen', function (): void {
        $crawler = $this->createLiveComponent(DefaultedJarFilterTable::class)->render()->crawler();

        expect($crawler)
            ->toContainElement(Selectors::filterFieldLabel('seal', 'value'), 'Seal')
            ->toContainElement(Selectors::filterField('seal', 'value') . ' option[value="wax"][selected]', 'Wax')
            ->toContainElement(Selectors::filterField('seal', 'value') . ' option[value="screw"]:not([selected])', 'Screw');
    });

    it("holds a multiple filter's choices under values, and a single filter's choice under value", function (): void {
        $crawler = $this->createLiveComponent(FilterableLarderJarTable::class)->render()->crawler();

        expect($crawler)
            ->toContainElement(Selectors::filterField('seals', 'values') . '[multiple]')
            ->toContainElement(Selectors::filterField('seal', 'value') . ':not([multiple])');
    });

    it('offers no empty choice once its placeholder is not selectable', function (): void {
        $crawler = $this->createLiveComponent(ShapedJarFilterTable::class)->render()->crawler();

        expect($crawler->filter(Selectors::filterField('strict', 'value') . ' option')->each(static fn (Crawler $option): string => $option->text()))->toBe(['Wax', 'Screw'])
            ->and($crawler->filter(Selectors::filterField('pinnedSeal', 'value') . ' option')->each(static fn (Crawler $option): string => $option->text()))->toBe(['All', 'Wax', 'Screw']);
    });
});

describe('the query', function (): void {
    it('keeps the rows its state compares equal to, any of, or all of them for a blank one', function (string $filter, mixed $value, array $kept): void {
        $this->seed(new LarderJar('Plum', seal: JarSeal::Wax), new LarderJar('Fig', seal: JarSeal::Screw), new LarderJar('Open'));

        $tester = $this->createLiveComponent(FilterableLarderJarTable::class)->filterTable($filter, $value);

        expect($tester->render()->crawler()->filter(Selectors::ROW . ' ' . Selectors::TEXT_COLUMN)->each(static fn (Crawler $cell): string => $cell->text()))->toEqualCanonicalizing($kept);
    })->with([
        'one value' => ['seal', 'wax', ['Plum']],
        'a blank value' => ['seal', '', ['Plum', 'Fig', 'Open']],
        'several values' => ['seals', ['wax', 'screw'], ['Plum', 'Fig']],
        'only blank values' => ['seals', ['', null], ['Plum', 'Fig', 'Open']],
        'a blank among values' => ['seals', ['', 'wax'], ['Plum']],
        'a value carrying DQL' => ['seal', "wax' OR 1=1 OR jar.seal = 'wax", []],
        'values carrying DQL' => ['seals', ["wax') OR (1=1"], []],
    ]);

    it('binds each filter its own parameter, so two set on one query both narrow it', function (): void {
        $this->seed($plum = new LarderJar('Plum', seal: JarSeal::Wax), $fig = new LarderJar('Fig', seal: JarSeal::Screw));

        $this->createLiveComponent(FilterableLarderJarTable::class)
            ->filterTable('seal', 'wax')
            ->filterTable('seals', ['screw'])
            ->assertCountTableRecords(0)
            ->filterTable('seals', ['wax', 'screw'])
            ->assertCanSeeTableRecords([$plum])
            ->assertCanNotSeeTableRecords([$fig]);
    });

    it('compares the attribute it names on the root, or a column of a joined alias', function (): void {
        $pantry = new Larder('Pantry');
        $pantry->addJar($stored = new LarderJar('Plum', position: 1));
        $this->seed($pantry, $cellared = new LarderJar('Fig', seal: JarSeal::Wax, position: 1));

        $this->createLiveComponent(QueryingJarFilterTable::class)
            ->filterTable('larder', 'Pantry')
            ->assertCanSeeTableRecords([$stored])
            ->assertCanNotSeeTableRecords([$cellared]);

        $this->createLiveComponent(FilterableLarderJarTable::class)
            ->filterTable('seals', ['wax'])
            ->assertCanSeeTableRecords([$cellared])
            ->assertCanNotSeeTableRecords([$stored]);
    });

    it('refuses an attribute whose head the query aliases nothing for, naming the join it needs', function (): void {
        $query = $this->entityManager()->createQueryBuilder()->select('jar')->from(LarderJar::class, 'jar');

        expect(fn (): QueryBuilder => SelectFilter::make('larder')->attribute('larder.name')->apply($query, ['value' => 'Pantry']))
            ->toThrow(LogicException::class, 'Cannot resolve [larder.name] for this filter: [larder] is not an alias on the builder, and a filter joins nothing of its own');
    });

    it('keeps every row while static, yet lists its choices and shows its chip', function (): void {
        $this->seed($plum = new LarderJar('Plum', seal: JarSeal::Wax), $fig = new LarderJar('Fig', seal: JarSeal::Screw));

        $tester = $this->createLiveComponent(ShapedJarFilterTable::class)
            ->filterTable('pinnedSeal', 'screw')
            ->assertCanSeeTableRecords([$plum, $fig]);

        expect($tester->render()->crawler())
            ->toContainElement(Selectors::filterField('pinnedSeal', 'value') . ' option[value="screw"]', 'Screw')
            ->toContainElement(Selectors::FILTER_INDICATOR_LABEL, 'Pinned seal: Screw');
    });
});

describe('the state', function (): void {
    it('counts the filter active while it holds a value, whether its options list it or not', function (): void {
        $tester = $this->createLiveComponent(FilterableLarderJarTable::class)->filterTable('seals', ['wax']);

        expect($tester->render()->crawler())->toContainElement(Selectors::FILTERS_TRIGGER_BADGE, '1')
            ->and($tester->render()->crawler()->filter(Selectors::FILTER_INDICATOR)->count())->toBe(1);

        $tester->filterTable('seals', [])->filterTable('seal', 'cork');

        expect($tester->render()->crawler())->toContainElement(Selectors::FILTERS_TRIGGER_BADGE, '1')
            ->and($tester->render()->crawler()->filter(Selectors::FILTER_INDICATOR)->count())->toBe(0);

        $tester->filterTable('seal', null);

        expect($tester->render()->crawler()->filter(Selectors::FILTERS_TRIGGER_BADGE)->count())->toBe(0);
    });

    it("clears the key its state uses when removed, a multiple filter's values and a single filter's value", function (): void {
        $this->seed($plum = new LarderJar('Plum', seal: JarSeal::Wax), $fig = new LarderJar('Fig', seal: JarSeal::Screw));

        $tester = $this->createLiveComponent(FilterableLarderJarTable::class)
            ->filterTable('seal', 'wax')
            ->filterTable('seals', ['screw'])
            ->removeTableFilter('seals')
            ->assertCanSeeTableRecords([$plum])
            ->assertCanNotSeeTableRecords([$fig]);

        expect($tester->render()->crawler()->filter(Selectors::FILTER_INDICATOR_LABEL)->each(static fn (Crawler $chip): string => $chip->text()))->toBe(['Seal: Wax']);

        $tester->removeTableFilter('seal')->assertCanSeeTableRecords([$plum, $fig]);

        expect($tester->render()->crawler()->filter(Selectors::FILTER_INDICATOR)->count())->toBe(0);
    });
});

describe('the chip', function (): void {
    it('reads the label and the chosen option, or every chosen option in the order chosen', function (): void {
        $tester = $this->createLiveComponent(FilterableLarderJarTable::class)
            ->filterTable('seal', 'wax')
            ->filterTable('seals', ['screw', 'wax', 'screw']);

        expect($tester->render()->crawler()->filter(Selectors::FILTER_INDICATOR_LABEL)->each(static fn (Crawler $chip): string => $chip->text()))->toBe(['Seal: Wax', 'Seals: Screw, Wax & Screw']);
    });

    it('reads a string indicator as its prefix and shows a built indicator as it is', function (): void {
        $tester = $this->createLiveComponent(ShapedJarFilterTable::class)
            ->filterTable('kind', 'wax')
            ->filterTable('marked', 'screw');

        expect($tester->render()->crawler()->filter(Selectors::FILTER_INDICATOR_LABEL)->each(static fn (Crawler $chip): string => $chip->text()))->toBe(['Seal kind: Wax', 'Marked chip']);
    });

    it('shows a chip for an option whose key is blank while keeping every row', function (): void {
        $this->seed($plum = new LarderJar('Plum', seal: JarSeal::Wax), $open = new LarderJar('Open'));

        $tester = $this->createLiveComponent(ShapedJarFilterTable::class)
            ->filterTable('blankable', '')
            ->assertCanSeeTableRecords([$plum, $open]);

        expect($tester->render()->crawler()->filter(Selectors::FILTER_INDICATOR_LABEL)->each(static fn (Crawler $chip): string => $chip->text()))->toBe(['Blankable: None']);
    });
});
