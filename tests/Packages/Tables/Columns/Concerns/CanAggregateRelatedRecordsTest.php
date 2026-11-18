<?php

declare(strict_types=1);

use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Query\AggregateDeclaration;
use Sylament\Tests\Fixtures\Entity\Atelier;
use Sylament\Tests\Fixtures\Entity\AtelierEasel;
use Sylament\Tests\Fixtures\Entity\AtelierPigment;
use Sylament\Tests\Fixtures\Entity\AtelierStock;
use Sylament\Tests\Fixtures\Tables\AggregatingAtelierTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Infrastructure\Doctrine\DriverValues;

beforeEach(function (): void {
    $this->north = new Atelier('North');
    $this->east = new Atelier('East');
    $this->north->pigments->add($ochre = new AtelierPigment('ochre'));
    $this->north->pigments->add($umber = new AtelierPigment('umber'));
    $this->seed($this->north, $this->east, $ochre, $umber, new AtelierEasel($this->north, 1), new AtelierEasel($this->north, 2), new AtelierEasel($this->north, 3), new AtelierStock($this->north, $ochre, 4));
});

describe('the aggregate declarations', function (): void {
    it('shows the figure of the related rows in its cell, and what an empty set reads as', function (string $column, int|float $figure, string $empty): void {
        $crawler = $this->createLiveComponent(AggregatingAtelierTable::class, ['columns' => [$column]])->render()->crawler();

        expect(DriverValues::number($crawler->filter(Selectors::cell((string) $this->north->id, $column))->text()))->toEqual($figure)
            ->and($crawler->filter(Selectors::cell((string) $this->east->id, $column))->text())->toBe($empty);
    })->with([
        'a count' => ['easels_count', 3, '0'],
        'a count over a many-to-many' => ['pigments_count', 2, '0'],
        'an existence check' => ['easels_exists', 1, ''],
        'an average' => ['easels_avg_position', 2, ''],
        'a maximum' => ['easels_max_position', 3, ''],
        'a minimum' => ['easels_min_position', 1, ''],
        'a sum' => ['easels_sum_position', 6, ''],
    ]);

    it('narrows a declaration keyed to a scope closure, handing it the alias it aggregates', function (): void {
        $crawler = $this->createLiveComponent(AggregatingAtelierTable::class, ['columns' => ['raised']])->render()->crawler();

        expect($crawler->filter(Selectors::cell((string) $this->north->id, 'raised'))->text())->toBe('2');
    });

    it('fills the cells of a table whose own scope names its root alias', function (): void {
        $this->seed($west = new Atelier('West'));

        $crawler = $this->createLiveComponent(AggregatingAtelierTable::class, ['columns' => ['easels_count'], 'scoped' => true])->render()->crawler();

        expect($crawler->filter(Selectors::cell((string) $this->north->id, 'easels_count'))->text())->toBe('3')
            ->and($crawler->filter(Selectors::row((string) $this->north->id)))->toHaveCount(1)
            ->and($crawler->filter(Selectors::row((string) $west->id)))->toHaveCount(0);
    });
});

describe('naming the column', function (): void {
    it('binds a lone declaration to the column whatever it is called, and an array form by the alias it derives or is given', function (string $column, string $figure): void {
        $crawler = $this->createLiveComponent(AggregatingAtelierTable::class, ['columns' => [$column]])->render()->crawler();

        expect($crawler->filter(Selectors::cell((string) $this->north->id, $column))->text())->toBe($figure);
    })->with([
        'a lone declaration under a free name' => ['easel_total', '3'],
        'a lone declaration aliased otherwise' => ['numbered', '3'],
        'an array entry by the alias a dotted path derives' => ['stocks_pigment_count', '1'],
        'the array entry aliased as the column' => ['trophies', '2'],
    ]);

    it('reads no declaration its column does not claim, in one statement more per aggregate column', function (): void {
        $statements = function (array $columns): int {
            $this->seed();
            $this->createLiveComponent(AggregatingAtelierTable::class, ['columns' => $columns])->render();

            return count($this->statementsRun());
        };

        $plain = $statements([]);

        expect($statements(['trophies']))->toBe($plain + 1)
            ->and($statements(['trophies', 'easels_count']))->toBe($plain + 2);
    });

    it('resolves the declaration its cell reads by its name', function (Closure $column, ?array $declared): void {
        $declaration = $column()->getCellAggregateDeclaration();

        expect($declaration instanceof AggregateDeclaration ? [$declaration->function, $declaration->relationshipPath, $declaration->column] : null)->toBe($declared);
    })->with([
        'nothing declared' => [fn (): TextColumn => TextColumn::make('easels_count'), null],
        'an array form naming another cell' => [fn (): TextColumn => TextColumn::make('total')->counts(['easels']), null],
        'a string form beside an array form naming other cells' => [fn (): TextColumn => TextColumn::make('total')->counts('easels')->sum(['easels as weight'], 'position'), ['COUNT', 'easels', null]],
        'two array forms naming no cell' => [fn (): TextColumn => TextColumn::make('total')->counts(['easels'])->max(['easels'], 'position'), null],
        'the alias a camel-cased field derives' => [fn (): TextColumn => TextColumn::make('easels_avg_total_duration')->avg(['easels'], 'totalDuration'), ['AVG', 'easels', 'totalDuration']],
        'one of two string forms by its derived alias' => [fn (): TextColumn => TextColumn::make('easels_max_position')->counts('easels')->max('easels', 'position'), ['MAX', 'easels', 'position']],
    ]);

    it('refuses a declaration it cannot resolve, naming the column', function (Closure $column, string $message): void {
        expect(fn (): ?AggregateDeclaration => $column()->getCellAggregateDeclaration())->toThrow(LogicException::class, $message);
    })->with([
        'a malformed array entry' => [
            fn (): TextColumn => TextColumn::make('total')->counts(['easels' => 'pigments']),
            'Column [total] declares a malformed aggregate: name the relationship as an array value, or as an array key whose value is the closure scoping it.',
        ],
        'a string form beside an array entry aliased as the column' => [
            fn (): TextColumn => TextColumn::make('total')->counts('easels')->sum(['easels as total'], 'position'),
            'Column [total] declares multiple aggregates claiming its cell, and its name cannot say which one fills it. Alias the one it reads [relationship as total], or move the others onto columns of their own.',
        ],
    ]);
});
