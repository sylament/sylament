<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Forms\PlainSuit;
use Sylament\Tests\Fixtures\Forms\Selectors as FormsSelectors;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\EditableCellLooksTable;
use Sylament\Tests\Fixtures\Tables\EditableLarderJarTable;
use Sylament\Tests\Fixtures\Tables\RecordsLaneCellsTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Symfony\Component\DomCrawler\Crawler;
use Twig\Error\RuntimeError;

describe('the write', function (): void {
    it("writes the enum case of the option chosen, another choice overwriting it, and selects the case's option by its value", function (): void {
        $this->seed($jar = new LarderJar('Plum', position: 1));
        $seal = Selectors::cell((string) $jar->id, 'seal');

        $tester = $this->createLiveComponent(EditableLarderJarTable::class)
            ->call('updateTableColumnState', ['column' => 'seal', 'record' => (string) $jar->id, 'input' => 'wax']);

        expect($this->reload($jar)->seal)->toBe(JarSeal::Wax)
            ->and($tester->render()->crawler())->toContainElement($seal . ' ' . Selectors::editableCellState('wax') . ' option[value="wax"][selected]', 'Wax');

        $tester->call('updateTableColumnState', ['column' => 'seal', 'record' => (string) $jar->id, 'input' => 'screw']);

        expect($this->reload($jar)->seal)->toBe(JarSeal::Screw);
    });

    it('clears a nullable column when its blank option is posted', function (): void {
        $this->seed($jar = new LarderJar('Plum', seal: JarSeal::Wax, position: 1));

        $this->createLiveComponent(EditableLarderJarTable::class)
            ->call('updateTableColumnState', ['column' => 'seal', 'record' => (string) $jar->id, 'input' => '']);

        expect($this->reload($jar)->seal)->toBeNull();
    });

    it('refuses a pure enum at the first render, naming the column and the enum', function (): void {
        expect(fn () => $this->createLiveComponent(EditableCellLooksTable::class, ['pureEnumSeal' => true])->render())
            ->toThrow(RuntimeError::class, 'The [seal] column was given the enum [' . PlainSuit::class . '], which is not a backed enum, so its cases cannot round-trip through a select. Back the enum with string or int values.');
    });
});

describe('the Choice seed', function (): void {
    it('accepts an integer key posted as the string a select sends', function (): void {
        $this->createLiveComponent(RecordsLaneCellsTable::class)
            ->call('updateTableColumnState', ['column' => 'grade', 'record' => 'north', 'input' => '1']);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(["write grade of north: '1'"]);
    });

    it("refuses a key no enabled option has, painting the refusal under the select and keeping the refused option selected over the record's own", function (string $refused, array $selected): void {
        $crawler = $this->createLiveComponent(RecordsLaneCellsTable::class)
            ->call('updateTableColumnState', ['column' => 'grade', 'record' => 'north', 'input' => $refused])
            ->render()
            ->crawler();
        $grade = Selectors::cell('north', 'grade');

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe([])
            ->and($crawler)->toContainElement($grade . ' ' . Selectors::CELL_ERROR, 'The value you selected is not a valid choice.')
            ->and($crawler)->toContainElement($grade . ' ' . FormsSelectors::SELECT . '[aria-invalid="true"]')
            ->and($crawler->filter($grade . ' option[selected]')->each(static fn (Crawler $option): ?string => $option->attr('value')))->toBe($selected);
    })->with([
        'a disabled option' => ['3', ['3']],
        'a key the map lacks' => ['9', []],
    ]);

    it('judges a post against the options its closure gives the row at the moment of the post', function (): void {
        $tester = $this->createLiveComponent(RecordsLaneCellsTable::class)
            ->call('updateTableColumnState', ['column' => 'tint', 'record' => 'north', 'input' => 'blue']);

        expect($tester->render()->crawler())->toContainElement(Selectors::cell('north', 'tint') . ' ' . Selectors::CELL_ERROR, 'The value you selected is not a valid choice.');

        $tester->call('updateTableColumnState', ['column' => 'tint', 'record' => 'south', 'input' => 'blue']);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(["write tint of south: 'blue'"]);
    });
});

describe('the options', function (): void {
    it("renders one option per key of its map, the state's selected, an integer key compared as the string it posts", function (): void {
        $options = static fn (Crawler $crawler, string $key, string $column): array => $crawler->filter(Selectors::cell($key, $column) . ' ' . FormsSelectors::OPTION)
            ->each(static fn (Crawler $option): string => $option->attr('value') . ($option->attr('selected') === null ? '' : ' selected') . ($option->attr('disabled') === null ? '' : ' disabled') . ': ' . $option->text());

        expect($options($this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler(), 'north', 'shape'))->toBe(['round: Round', 'square selected: Square'])
            ->and($options($this->createLiveComponent(RecordsLaneCellsTable::class)->render()->crawler(), 'north', 'grade'))->toBe(['1: One', '2 selected: Two', '3 disabled: Three']);
    });

    it('selects only the option whose key spells the state, never another key that reads as the same number', function (): void {
        $crawler = $this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler();

        expect($crawler->filter(Selectors::cell('north', 'code') . ' ' . FormsSelectors::OPTION)->each(static fn (Crawler $option): string => $option->attr('value') . ($option->attr('selected') === null ? '' : ' selected')))
            ->toBe(['01', '1 selected']);
    });

    it('renders a grouped map as an optgroup per group', function (): void {
        $crawler = $this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler();

        expect($crawler->filter(Selectors::cell('north', 'size') . ' optgroup')->each(static fn (Crawler $group): string => $group->attr('label') . ': ' . implode(', ', $group->filter('option')->each(static fn (Crawler $option): string => $option->text() . ($option->attr('selected') === null ? '' : ' selected')))))
            ->toBe(['Small: S, XS selected', 'Large: L']);
    });

    it('offers a blank "Select an option" first, which a disabled column leaves blank and selectablePlaceholder(false) drops', function (): void {
        $crawler = $this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler();

        expect($crawler)->toContainElement(Selectors::cell('north', 'shape') . ' option[value=""]', 'Select an option')
            ->and($crawler)->toContainElement(Selectors::cell('north', 'lid') . ' option[value=""]', '')
            ->and($crawler->filter(Selectors::cell('north', 'fill') . ' option[value=""]'))->toHaveCount(0)
            ->and($crawler)->toContainElement(Selectors::cell('north', 'fill') . ' option[value="jam"][selected]', 'Jam');
    });

    it('remembers a closure option map for the whole page, so a per-row map it remembers shows the first row its own on every row', function (): void {
        $crawler = $this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler();
        $labels = static fn (string $key, string $column): array => $crawler->filter(Selectors::cell($key, $column) . ' ' . FormsSelectors::OPTION)->each(static fn (Crawler $option): string => $option->text());

        expect(array_values(array_filter($this::getContainer()->get(HookLog::class)->hooks, static fn (string $hook): bool => str_starts_with($hook, 'bin '))))->toBe(['bin options for north'])
            ->and($labels('south', 'bin'))->toBe(['Oak'])
            ->and(array_values(array_filter($this::getContainer()->get(HookLog::class)->hooks, static fn (string $hook): bool => str_starts_with($hook, 'crate '))))->toBe(['crate options for north', 'crate options for south'])
            ->and($labels('south', 'crate'))->toBe(['Elm']);
    });
});

describe('the cell', function (): void {
    it("renders support's input wrapper around forms' select, named by its label and titled by its tooltip, with none of a field's id, name or model", function (): void {
        $crawler = $this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler();
        $shape = Selectors::cell('north', 'shape');

        expect($crawler)->toContainElement($shape . ' ' . Selectors::SELECT_COLUMN . '[title="Pick a shape"] ' . SupportSelectors::INPUT_WRAPPER . ':not(' . SupportSelectors::DISABLED . ') ' . FormsSelectors::SELECT . '[aria-label="Shape"]:not([disabled])')
            ->and($crawler)->toContainElement(Selectors::cell('north', 'lid') . ' ' . SupportSelectors::INPUT_WRAPPER . SupportSelectors::DISABLED . ' ' . FormsSelectors::SELECT . '[disabled]')
            ->and($crawler->filter($shape . ' ' . FormsSelectors::SELECT . '[id], ' . $shape . ' ' . FormsSelectors::SELECT . '[name], ' . $shape . ' ' . FormsSelectors::SELECT . '[data-model]'))->toHaveCount(0);
    });

    it('publishes its state on its root for the controller, a backed enum by its value, and commits its select on change', function (): void {
        $crawler = $this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler();

        expect($crawler)->toContainElement(Selectors::cell('north', 'shape') . ' ' . Selectors::SELECT_COLUMN . Selectors::editableCellState('square') . Selectors::editableCell('shape', 'north') . ' ' . FormsSelectors::SELECT . Selectors::EDITABLE_CONTROL)
            ->and($crawler)->toContainElement(Selectors::cell('north', 'seal') . ' ' . Selectors::editableCellState('screw') . ' option[value="screw"][selected]', 'Screw');
    });

    it('carries inline and its alignment on its root', function (): void {
        $crawler = $this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler();

        expect($crawler)->toContainElement(Selectors::cell('north', 'shape') . ' ' . Selectors::SELECT_COLUMN . Selectors::INLINE . SupportSelectors::align('end'))
            ->and($crawler)->toContainElement(Selectors::cell('north', 'size') . ' ' . Selectors::SELECT_COLUMN . ':not(' . Selectors::INLINE . ')' . SupportSelectors::NO_ALIGN);
    });

    it('puts its extra input attributes on its select, not its root or its wrapper', function (): void {
        $crawler = $this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler();
        $shape = Selectors::cell('north', 'shape');

        expect($crawler)->toContainElement($shape . ' ' . FormsSelectors::SELECT . '[data-probe="shape"]')
            ->and($crawler->filter($shape . ' ' . Selectors::SELECT_COLUMN . '[data-probe], ' . $shape . ' ' . SupportSelectors::INPUT_WRAPPER . '[data-probe]'))->toHaveCount(0);
    });

    it('keeps its cell out of the record link a clickable row wraps its other cells in', function (): void {
        $crawler = $this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler();

        expect($crawler)->toContainElement(Selectors::cell('north', 'label') . ' a' . Selectors::CELL_WRAPPER . '[href="/larder/north"]')
            ->and($crawler)->toContainElement(Selectors::cell('north', 'shape') . ' div' . Selectors::CELL_WRAPPER)
            ->and($crawler->filter(Selectors::cell('north', 'shape') . ' a' . Selectors::CELL_WRAPPER))->toHaveCount(0);
    });
});
