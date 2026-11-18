<?php

declare(strict_types=1);

use Sylament\Tables\Query\ColumnPathResolver;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Entity\PropagatedCutting;
use Sylament\Tests\Fixtures\Entity\Seedling;
use Sylament\Tests\Infrastructure\Doctrine\TestPlatform;

describe('resolve()', function (): void {
    it('reads a dotless name off the root alias, a to-one association included, and a head the builder declares verbatim', function (): void {
        $query = $this->entityManager()->createQueryBuilder()
            ->select('seedling')
            ->from(Seedling::class, 'seedling')
            ->innerJoin('seedling.garden', 'plot');

        expect(ColumnPathResolver::resolve($query, 'name'))->toBe('seedling.name')
            ->and(ColumnPathResolver::resolve($query, 'terrace'))->toBe('seedling.terrace')
            ->and(ColumnPathResolver::resolve($query, 'plot.name'))->toBe('plot.name');
    });

    it('refuses a dotted name the mapping does not resolve, naming the path, its head, the root it tried and the JSON case', function (): void {
        $query = $this->entityManager()->createQueryBuilder()->select('product')->from(Product::class, 'product');

        expect(fn (): string => ColumnPathResolver::resolve($query, 'data.theme'))->toThrow(
            LogicException::class,
            sprintf('Cannot resolve [data.theme] on the table query: [data] is not an alias on the builder, a mapped field path, or an association path on [%s]. Join and alias the relation in query(), or take over with sortable(query:)/searchable(query:); dotted paths into JSON columns are not supported in the query lane.', Product::class),
        );
    });
});

describe('resolveOrderable()', function (): void {
    it('orders by a field of the root, an embeddable one included, without joining', function (): void {
        $query = $this->entityManager()->createQueryBuilder()->select('cutting')->from(PropagatedCutting::class, 'cutting');

        expect(ColumnPathResolver::resolveOrderable($query, 'name'))->toBe('cutting.name')
            ->and(ColumnPathResolver::resolveOrderable($query, 'origin.nursery'))->toBe('cutting.origin.nursery')
            ->and($query->getAllAliases())->toBe(['cutting']);
    });

    it('left-joins a to-one hop, so a row whose relation is null stays, ordered where its platform puts a null', function (): void {
        $ash = new Seedling('Ash');
        $ash->garden = new Garden('Beech');

        $elm = new Seedling('Elm');
        $elm->garden = new Garden('Alder');
        $this->seed($ash->garden, $elm->garden, $ash, $elm, new Seedling('Oak'));

        $query = $this->entityManager()->createQueryBuilder()->select('seedling')->from(Seedling::class, 'seedling');
        $query->orderBy(ColumnPathResolver::resolveOrderable($query, 'garden.name'), SortDirection::Ascending);

        expect($query->getDQL())->toBe(sprintf('SELECT seedling FROM %s seedling LEFT JOIN seedling.garden sy_garden ORDER BY sy_garden.name ASC', Seedling::class))
            ->and(array_map(static fn (Seedling $seedling): string => $seedling->name, $query->getQuery()->getResult()))->toBe(match (TestPlatform::current()) {
                TestPlatform::Sqlite, TestPlatform::MySql => ['Oak', 'Elm', 'Ash'],
                TestPlatform::PostgreSql => ['Elm', 'Ash', 'Oak'],
            });
    });

    it("reuses the query's own join on the association, whatever its type", function (): void {
        $query = $this->entityManager()->createQueryBuilder()
            ->select('seedling')
            ->from(Seedling::class, 'seedling')
            ->innerJoin('seedling.garden', 'plot', 'WITH', "plot.name <> ''");

        expect(ColumnPathResolver::resolveOrderable($query, 'garden.supplier.name'))->toBe('sy_supplier.name')
            ->and($query->getDQL())->toBe(sprintf("SELECT seedling FROM %s seedling INNER JOIN seedling.garden plot WITH plot.name <> '' LEFT JOIN plot.supplier sy_supplier", Seedling::class));
    });

    it('refuses a path through a collection before joining anything', function (): void {
        $query = $this->entityManager()->createQueryBuilder()->select('seedling')->from(Seedling::class, 'seedling');

        expect(fn (): string => ColumnPathResolver::resolveOrderable($query, 'garden.seedlings.name'))
            ->toThrow(LogicException::class, 'Cannot sort by [garden.seedlings.name]: sorting through a relation supports to-one associations only. Take over with sortable(query:), or declare an aggregate column.')
            ->and($query->getAllAliases())->toBe(['seedling']);
    });
});
