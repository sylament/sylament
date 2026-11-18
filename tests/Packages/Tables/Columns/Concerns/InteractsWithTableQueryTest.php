<?php

declare(strict_types=1);

use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Atelier;
use Sylament\Tests\Fixtures\Entity\AtelierEasel;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Entity\PropagatedCutting;
use Sylament\Tests\Fixtures\Entity\Seedling;
use Sylament\Tests\Fixtures\Tables\ProductTable;
use Sylament\Tests\Fixtures\Tables\SeedlingGardenTable;
use Sylament\Tests\Fixtures\Tables\Selectors;

describe('applyEagerLoading()', function (): void {
    it('fetch-joins and selects every to-one hop a name reads through, reusing a join the query or its sort carries', function (): void {
        $fresh = $this->entityManager()->createQueryBuilder()->select('seedling')->from(Seedling::class, 'seedling');
        $joined = $this->entityManager()->createQueryBuilder()->select('seedling', 'plot')->from(Seedling::class, 'seedling')->leftJoin('seedling.garden', 'plot');

        $sorted = $this->entityManager()->createQueryBuilder()->select('seedling')->from(Seedling::class, 'seedling');

        TextColumn::make('garden.supplier.name')->applyEagerLoading($fresh);
        TextColumn::make('garden.supplier.name')->applyEagerLoading($joined);
        $column = TextColumn::make('garden.name')->sortable();
        $column->applySort($sorted);
        $column->applyEagerLoading($sorted);

        expect($fresh->getDQL())->toBe(sprintf('SELECT seedling, sy_garden, sy_garden_supplier FROM %s seedling LEFT JOIN seedling.garden sy_garden LEFT JOIN sy_garden.supplier sy_garden_supplier', Seedling::class))
            ->and($joined->getDQL())->toBe(sprintf('SELECT seedling, plot, sy_supplier FROM %s seedling LEFT JOIN seedling.garden plot LEFT JOIN plot.supplier sy_supplier', Seedling::class))
            ->and($sorted->getDQL())->toBe(sprintf('SELECT seedling, sy_garden FROM %s seedling LEFT JOIN seedling.garden sy_garden ORDER BY sy_garden.name ASC', Seedling::class));
    });

    it('joins nothing for a name that crosses no to-one chain', function (string $class, string $name): void {
        $query = $this->entityManager()->createQueryBuilder()->select('record')->from($class, 'record');
        $dql = $query->getDQL();

        TextColumn::make($name)->applyEagerLoading($query);

        expect($query->getDQL())->toBe($dql);
    })->with([
        'a dotless name' => [Seedling::class, 'name'],
        'an embeddable field' => [PropagatedCutting::class, 'origin.nursery'],
        'a name ending on an association' => [Seedling::class, 'garden.supplier'],
        'a path through a collection' => [Seedling::class, 'garden.seedlings.name'],
    ]);

    it('reads a page through a to-one in the statements one row costs, and lists each root once beside a collection column', function (): void {
        $seedlings = [];

        foreach (['Kew', 'Wisley', 'Eden'] as $name) {
            $seedling = new Seedling("{$name} oak");
            $seedling->garden = new Garden($name);
            $sibling = new Seedling("{$name} ash");
            $sibling->garden = $seedling->garden;
            $seedlings[] = $seedling->garden;
            $seedlings[] = $seedling;
            $seedlings[] = $sibling;
        }

        $this->seed(...$seedlings);
        $this->createLiveComponent(SeedlingGardenTable::class)->render();
        $withGardens = count($this->statementsRun());

        $crawler = $this->createLiveComponent(SeedlingGardenTable::class, ['variants' => ['siblings']])->render()->crawler();

        expect($withGardens)->toBe(2)
            ->and($crawler->filter(Selectors::ROW))->toHaveCount(6);
    });
});

describe('applySort()', function (): void {
    it('orders by each declared sort column in turn and in the direction asked, joining a to-one path', function (): void {
        $kew = new Garden('Kew');
        $alder = new Garden('Alder');
        [$oak, $ash, $elm] = [new Seedling('Oak'), new Seedling('Ash'), new Seedling('Elm')];
        [$oak->garden, $ash->garden, $elm->garden] = [$kew, $kew, $alder];
        $this->seed($kew, $alder, $oak, $ash, $elm);

        $query = $this->entityManager()->createQueryBuilder()->select('seedling')->from(Seedling::class, 'seedling');
        TextColumn::make('garden.name')->sortable(['garden.name', 'name'])->applySort($query, 'desc');

        expect(array_map(static fn (Seedling $seedling): string => $seedling->name, $query->getQuery()->getResult()))->toBe(['Oak', 'Ash', 'Elm']);
    });

    it('hands a sortable(query:) closure the builder and the direction, ordering nothing itself', function (): void {
        $handed = [];
        $query = $this->entityManager()->createQueryBuilder()->select('product')->from(Product::class, 'product');

        TextColumn::make('name')
            ->sortable(query: function (QueryBuilder $query, string $direction) use (&$handed): void {
                $handed = [$query, $direction];
            })
            ->applySort($query, 'desc');

        expect($handed)->toBe([$query, 'desc'])
            ->and($query->getDQLPart('orderBy'))->toBe([]);
    });

    it('orders an aggregate column by its aggregate, an existence check by the raw count it tests, and by the columns it names once it names any', function (): void {
        $zulu = new Atelier('Zulu');
        $mike = new Atelier('Mike');
        $alpha = new Atelier('Alpha');
        $this->seed($zulu, $mike, $alpha, new AtelierEasel($mike, 1), new AtelierEasel($alpha, 1), new AtelierEasel($alpha, 2));

        $sorted = function (TextColumn $column): array {
            $query = $this->entityManager()->createQueryBuilder()->select('atelier')->from(Atelier::class, 'atelier');
            $column->table(Table::make(new ProductTable($this->entityManager()))->query($query))->applySort($query, 'desc');

            return array_map(static fn (Atelier $atelier): string => $atelier->name, $query->getQuery()->getResult());
        };

        expect($sorted(TextColumn::make('easels_count')->counts('easels')->sortable()))->toBe(['Alpha', 'Mike', 'Zulu'])
            ->and($sorted(TextColumn::make('easels_exists')->exists('easels')->sortable()))->toBe(['Alpha', 'Mike', 'Zulu'])
            ->and($sorted(TextColumn::make('easels_count')->counts('easels')->sortable(['name'])))->toBe(['Zulu', 'Mike', 'Alpha']);
    });
});

describe('applySearchConstraint()', function (): void {
    it('adds one LIKE per search column to the group, a relation path as its correlated EXISTS', function (): void {
        $kew = new Garden('Kew');
        $oak = new Seedling('Oak');
        $oak->garden = $kew;
        $this->seed($kew, $oak, new Seedling('Kewpie'), new Seedling('Elm'));

        $query = $this->entityManager()->createQueryBuilder()->select('seedling')->from(Seedling::class, 'seedling')->orderBy('seedling.name');
        $group = new Orx();
        TextColumn::make('label')->searchable(['name', 'garden.name'])->applySearchConstraint($query, $group, 'Kew');
        $query->where($group);

        expect($group->count())->toBe(2)
            ->and(array_map(static fn (Seedling $seedling): string => $seedling->name, $query->getQuery()->getResult()))->toBe(['Kewpie', 'Oak']);
    });

    it("joins what a search closure returns to the group, and nothing for a closure answering nothing or the builder it narrowed", function (): void {
        $query = $this->entityManager()->createQueryBuilder()->select('product')->from(Product::class, 'product');
        $group = new Orx();
        $handed = [];

        TextColumn::make('name')->searchable(query: function (QueryBuilder $query, string $search, string $searchQuery) use (&$handed): Comparison {
            $handed = [$query, $search, $searchQuery];

            return $query->expr()->eq('product.name', $query->expr()->literal($search));
        })->applySearchConstraint($query, $group, 'Lamp');
        TextColumn::make('id')->searchable(query: static fn (): string => 'product.id = 7')->applySearchConstraint($query, $group, 'Lamp');
        TextColumn::make('code')->searchable(query: static fn (): null => null)->applySearchConstraint($query, $group, 'Lamp');
        TextColumn::make('size')->searchable(query: static fn (QueryBuilder $query): QueryBuilder => $query->andWhere("product.name <> ''"))->applySearchConstraint($query, $group, 'Lamp');

        expect($handed)->toBe([$query, 'Lamp', 'Lamp'])
            ->and((string) $group)->toBe("product.name = 'Lamp' OR product.id = 7")
            ->and((string) $query->getDQLPart('where'))->toBe("product.name <> ''");
    });
});
