<?php

declare(strict_types=1);

use Sylament\Tables\Columns\TextColumn;
use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Entity\LarderBin;
use Sylament\Tests\Fixtures\Entity\LarderBinNote;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Entity\Trellis;
use Sylament\Tests\Fixtures\Entity\TrellisVine;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Tables\ColumnStateTable;
use Sylament\Tests\Fixtures\Tables\LarderStateTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\TrellisVineTable;
use Symfony\Component\DomCrawler\Crawler;

describe('on array rows', function (): void {
    it('reads its state from the row, a literal or a closure, the last of `state()` and `getStateUsing()` winning, and runs the closure once per row', function (): void {
        $crawler = $this->createLiveComponent(ColumnStateTable::class)->render()->crawler();
        $hooks = $this::getContainer()->get(HookLog::class)->hooks;

        expect($crawler->filter(Selectors::cell('7', 'name'))->text())->toBe('Lamp')
            ->and($crawler->filter(Selectors::cell('vase', 'counted'))->text())->toBe('Vase')
            ->and($crawler->filter(Selectors::cell('7', 'overridden'))->text())->toBe('second')
            ->and($crawler->filter(Selectors::cell('7', 'absentField'))->text())->toBe('—')
            ->and($crawler->filter(Selectors::headerCell('absent-field'))->text())->toBe('Absent field')
            ->and(array_values(array_filter($hooks, static fn (string $hook): bool => str_starts_with($hook, 'state '))))
            ->toBe(['state Lamp', 'state Vase', 'state Jug', 'state Cup', 'state Mug', 'state Pot']);
    });

    it('reads an array row by key and by nested key, an absent key reading as an empty cell', function (): void {
        $crawler = $this->createLiveComponent(ColumnStateTable::class)->render()->crawler();

        expect($crawler->filter(Selectors::cell('7', 'shelf.row'))->text())->toBe('Top')
            ->and($crawler->filter(Selectors::cell('vase', 'shelf.row'))->text())->toBe('')
            ->and($crawler->filter(Selectors::cell('jug', 'shelf.row'))->text())->toBe('');
    });

    it('fills a blank cell with its default and leaves zero and false alone', function (): void {
        $crawler = $this->createLiveComponent(ColumnStateTable::class)->render()->crawler();
        $note = static fn (string $key): string => $crawler->filter(Selectors::cell($key, 'note'))->text();

        expect($note('7'))->toBe('—')
            ->and($note('vase'))->toBe('—')
            ->and($note('jug'))->toBe('—')
            ->and($note('cup'))->toBe('0')
            ->and($note('mug'))->toBe('0')
            ->and($note('pot'))->toBe('');
    });

    it('splits a string state on its separator before the default, so a blank string reaches a default that runs once per row, and an empty separator splits nothing', function (): void {
        $crawler = $this->createLiveComponent(ColumnStateTable::class)->render()->crawler();
        $hooks = $this::getContainer()->get(HookLog::class)->hooks;
        $tags = static fn (string $key): Crawler => $crawler->filter(Selectors::cell($key, 'tags'));

        expect($tags('7')->filter('li')->each(static fn (Crawler $item): string => $item->text()))->toBe(['oak', 'ash'])
            ->and($tags('vase')->text())->toBe('none')
            ->and($tags('jug')->text())->toBe('none')
            ->and($crawler->filter(Selectors::cell('7', 'unsplit'))->text())->toBe('oak,ash')
            ->and(array_values(array_filter($hooks, static fn (string $hook): bool => str_starts_with($hook, 'default '))))
            ->toBe(['default Vase', 'default Jug', 'default Cup', 'default Mug', 'default Pot']);
    });
});

describe('its cache', function (): void {
    it('reads a column nobody keyed afresh for each record it is handed, and a keyed one once per key', function (): void {
        $column = TextColumn::make('name');

        expect($column->record(['name' => 'Lamp'])->getState())->toBe('Lamp')
            ->and($column->record(['name' => 'Vase'])->getState())->toBe('Vase')
            ->and($column->recordKey('a')->getState())->toBe('Vase')
            ->and($column->record(['name' => 'Jug'])->getState())->toBe('Vase')
            ->and($column->recordKey('b')->getState())->toBe('Jug')
            ->and(TextColumn::make('note')->state('—')->getState())->toBe('—');
    });

    it('reads its record afresh once its cached state is cleared', function (): void {
        $column = TextColumn::make('name')->record(['name' => 'Lamp'])->recordKey('a');
        $column->getState();
        $column->record(['name' => 'Vase']);

        expect($column->getState())->toBe('Lamp');

        $column->clearCachedState();

        expect($column->getState())->toBe('Vase');
    });
});

describe('on entities', function (): void {
    beforeEach(function (): void {
        $this->bare = new Larder('Bare');
        $bare = $this->bare;

        $this->pantry = new Larder('Pantry');
        $pantry = $this->pantry;
        $pantry->supplier = new Product('');

        foreach (['a', '', 'b', 'a'] as $position => $label) {
            $pantry->addJar(new LarderJar($label, position: $position));
        }

        $pantry->addBin($pantryBin = new LarderBin('P1'));
        $pantryBin->addNote(new LarderBinNote('dry'));
        $this->cellar = new Larder('Cellar');
        $cellar = $this->cellar;
        $cellar->supplier = new Product('Acme');
        $cellar->addJar(new LarderJar('cider'));
        $cellar->addBin($cellarBin = new LarderBin('C1'));

        $cellarBin->addNote(new LarderBinNote('cold'));
        $cellarBin->addNote(new LarderBinNote('dark'));

        $this->seed($pantry->supplier, $cellar->supplier, $bare, $pantry, $cellar);
    });

    it("reads along an entity's associations, a to-one chain as one value, a blank one meeting the default, and a to-many path as its items without blanks", function (): void {
        [$bare, $pantry, $cellar] = [$this->bare, $this->pantry, $this->cellar];
        $crawler = $this->createLiveComponent(LarderStateTable::class)->render()->crawler();
        $jars = static fn (Larder $larder): array => $crawler->filter(Selectors::cell((string) $larder->id, 'jars.label') . ' li')->each(static fn (Crawler $item): string => $item->text());

        expect($crawler->filter(Selectors::cell((string) $cellar->id, 'supplier.name'))->text())->toBe('Acme')
            ->and($crawler->filter(Selectors::cell((string) $pantry->id, 'supplier.name'))->text())->toBe('No supplier')
            ->and($crawler->filter(Selectors::cell((string) $bare->id, 'supplier.name'))->text())->toBe('No supplier')
            ->and($jars($pantry))->toBe(['a', 'b', 'a'])
            ->and($crawler->filter(Selectors::cell((string) $cellar->id, 'jars.label'))->text())->toBe('cider')
            ->and($crawler->filter(Selectors::cell((string) $pantry->id, 'bins.notes.text'))->text())->toBe('dry');
    });

    it("reads a to-many whose getter answers a plain array as that array's items, not as a list holding it", function (): void {
        $trellis = new Trellis('Arch');
        $trellis->addVine($ivy = new TrellisVine('ivy'));
        $trellis->addVine(new TrellisVine('hop'));
        $this->seed($trellis);

        $crawler = $this->createLiveComponent(TrellisVineTable::class)->render()->crawler();

        expect($crawler->filter(Selectors::cell((string) $ivy->id, 'trellis.vines') . ' li')->each(static fn (Crawler $item): string => $item->text()))->toBe(['hop', 'ivy']);
    });

    it('reads a name the entity cannot answer, at any hop, as an empty cell', function (): void {
        $pantry = $this->pantry;
        $crawler = $this->createLiveComponent(LarderStateTable::class)->render()->crawler();

        expect($crawler->filter(Selectors::cell((string) $pantry->id, 'name'))->text())->toBe('Pantry')
            ->and($crawler->filter(Selectors::cell((string) $pantry->id, 'nonexistent'))->text())->toBe('')
            ->and($crawler->filter(Selectors::cell((string) $pantry->id, 'jars.nonexistent'))->text())->toBe('');
    });

    it('hands a to-many path to its closures as a list on every row that reaches an item, one item included', function (): void {
        [$bare, $pantry, $cellar] = [$this->bare, $this->pantry, $this->cellar];
        $crawler = $this->createLiveComponent(LarderStateTable::class)->render()->crawler();
        $shape = static fn (Larder $larder): ?string => $crawler->filter(Selectors::cell((string) $larder->id, 'bins.notes.text'))->attr('data-shape');

        expect($shape($cellar))->toBe('array')
            ->and($shape($pantry))->toBe('array')
            ->and($shape($bare))->toBe('null');
    });

    it('drops a repeated value from a to-many list when told to', function (): void {
        $pantry = $this->pantry;
        $jars = fn (bool $distinct): array => $this->createLiveComponent(LarderStateTable::class, ['distinct' => $distinct])->render()->crawler()
            ->filter(Selectors::cell((string) $pantry->id, 'jars.label') . ' li')
            ->each(static fn (Crawler $item): string => $item->text());

        expect($jars(false))->toBe(['a', 'b', 'a'])
            ->and($jars(true))->toBe(['a', 'b']);
    });
});
