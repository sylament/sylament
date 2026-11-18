<?php

declare(strict_types=1);

use Doctrine\ORM\Query\Expr\Comparison;
use Sylament\Tables\Query\SearchConstraint;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Entity\PropagatedCutting;
use Sylament\Tests\Fixtures\Entity\Seedling;

describe('forColumn()', function (): void {
    it('becomes a correlated EXISTS for a relation path and a LIKE for a field of the root, an embeddable one included', function (): void {
        $cuttings = $this->entityManager()->createQueryBuilder()->select('cutting')->from(PropagatedCutting::class, 'cutting');
        $gardens = $this->entityManager()->createQueryBuilder()->select('garden')->from(Garden::class, 'garden');

        $name = SearchConstraint::forColumn($cuttings, 'name', 'oak', forceCaseInsensitive: false);
        $nursery = SearchConstraint::forColumn($cuttings, 'origin.nursery', 'kew', forceCaseInsensitive: false);
        $seedlings = SearchConstraint::forColumn($gardens, 'seedlings.name', 'oak', forceCaseInsensitive: false);

        expect($name)->toBeInstanceOf(Comparison::class)
            ->and((string) $name)->toBe('cutting.name LIKE :sylament_search_0')
            ->and((string) $nursery)->toBe('cutting.origin.nursery LIKE :sylament_search_1')
            ->and($seedlings)->toBe(sprintf('EXISTS (SELECT sy_x0.id FROM %s sy_x0 JOIN sy_x0.seedlings sy_x0_0 WHERE sy_x0.id = garden.id AND sy_x0_0.name LIKE :sylament_search_0)', Garden::class));
    });
});

describe('a head shared with a join alias', function (): void {
    it('refuses a path whose head the query fetch-joins as a collection, naming the path and the alias', function (): void {
        $query = $this->entityManager()->createQueryBuilder()
            ->select('garden', 'seedlings')
            ->from(Garden::class, 'garden')
            ->leftJoin('garden.seedlings', 'seedlings');

        expect(fn (): string|Comparison => SearchConstraint::forColumn($query, 'seedlings.name', 'oak', forceCaseInsensitive: false))->toThrow(
            LogicException::class,
            "The searchable path [seedlings.name] begins with [seedlings], an alias the query fetch-joins as a collection, so a search would narrow each row's [seedlings] rather than choose rows. Alias that join something the path does not begin with; a table column can also take its search over with searchable(query:).",
        );
    });

    it("keeps the caller's own alias for a to-one fetch join, a join it does not select and an entity join", function (): void {
        $query = $this->entityManager()->createQueryBuilder()
            ->select('seedling', 'garden')
            ->from(Seedling::class, 'seedling')
            ->leftJoin('seedling.garden', 'garden')
            ->leftJoin('garden.seedlings', 'siblings')
            ->leftJoin(Product::class, 'product', 'WITH', 'product.id = garden.id');

        expect([
            (string) SearchConstraint::forColumn($query, 'garden.name', 'kew', forceCaseInsensitive: false),
            (string) SearchConstraint::forColumn($query, 'siblings.name', 'oak', forceCaseInsensitive: false),
            (string) SearchConstraint::forColumn($query, 'product.name', 'lamp', forceCaseInsensitive: false),
        ])->toBe([
            'garden.name LIKE :sylament_search_0',
            'siblings.name LIKE :sylament_search_1',
            'product.name LIKE :sylament_search_2',
        ]);
    });

    it('chooses rows and keeps their whole collection once the fetch join is aliased otherwise', function (): void {
        $kew = new Garden('Kew');
        $wisley = new Garden('Wisley');
        $oak = new Seedling('Oak');
        $oak->garden = $kew;

        $ash = new Seedling('Ash');
        $ash->garden = $kew;

        $elm = new Seedling('Elm');
        $elm->garden = $wisley;
        $this->seed($kew, $wisley, $oak, $ash, $elm);

        $query = $this->entityManager()->createQueryBuilder()
            ->select('garden', 'sprout')
            ->from(Garden::class, 'garden')
            ->leftJoin('garden.seedlings', 'sprout');
        $query->where(SearchConstraint::forColumn($query, 'seedlings.name', 'Oak', forceCaseInsensitive: false));

        $gardens = $query->getQuery()->getResult();
        $names = array_map(static fn (Seedling $seedling): string => $seedling->name, $gardens[0]->seedlings->toArray());
        sort($names);

        expect(array_map(static fn (Garden $garden): string => $garden->name, $gardens))->toBe(['Kew'])
            ->and($names)->toBe(['Ash', 'Oak']);
    });
});
