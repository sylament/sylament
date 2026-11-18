<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Tables\Query\RelationConstraint;
use Sylament\Tables\Query\RelationPathResolver;
use Sylament\Tests\Fixtures\Entity\Allotment;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Entity\Greenhouse;
use Sylament\Tests\Fixtures\Entity\GreenhouseVent;
use Sylament\Tests\Fixtures\Entity\Plant;
use Sylament\Tests\Fixtures\Entity\Seedling;
use Sylament\Tests\Fixtures\Entity\Terrace;
use Sylament\Tests\Infrastructure\Doctrine\TestPlatform;
use Symfony\Component\Uid\Uuid;

describe('existsLike()', function (): void {
    it('chooses each root once through a relation of every cardinality, binding on the outer builder and joining nothing to it', function (): void {
        $kew = new Garden('Kew');
        $wisley = new Garden('Wisley');
        $oak = new Seedling('Oak');
        $oak->garden = $kew;

        $sapling = new Seedling('Oak sapling');
        $sapling->garden = $kew;

        $elm = new Seedling('Elm');
        $elm->garden = $wisley;

        $kew->addPlant($fern = new Plant('Fern'));
        $kew->addPlant($tree = new Plant('Tree fern'));

        $wisley->addPlant($moss = new Plant('Moss'));
        $this->seed($kew, $wisley, $oak, $sapling, $elm, $fern, $tree, $moss);

        $search = function (string $class, string $alias, string $path, string $term): array {
            $query = $this->entityManager()->createQueryBuilder()->select($alias)->from($class, $alias)->orderBy("{$alias}.name");
            $resolved = RelationPathResolver::analyseQueryPath($query, $path) ?? throw new LogicException("[{$path}] resolves nothing.");
            $query->where(RelationConstraint::existsLike($query, $resolved, $term, forceCaseInsensitive: false));

            return [
                array_map(static fn (Garden|Seedling $record): string => $record->name, $query->getQuery()->getResult()),
                $query->getAllAliases(),
                $query->getParameter('sylament_search_0')?->getValue(),
            ];
        };

        expect($search(Seedling::class, 'seedling', 'garden.name', 'Kew'))->toBe([['Oak', 'Oak sapling'], ['seedling'], '%Kew%'])
            ->and($search(Garden::class, 'garden', 'seedlings.name', 'Oak'))->toBe([['Kew'], ['garden'], '%Oak%'])
            ->and($search(Garden::class, 'garden', 'plants.name', 'Fern'))->toBe([['Kew'], ['garden'], '%Fern%']);
    });

    it('keeps two relation terms of one OR group apart', function (): void {
        $kew = new Garden('Kew');
        $wisley = new Garden('Wisley');
        $oak = new Seedling('Oak');
        $oak->garden = $kew;

        $wisley->addPlant($moss = new Plant('Moss'));
        $this->seed($kew, $wisley, new Garden('Eden'), $oak, $moss);

        $query = $this->entityManager()->createQueryBuilder()->select('garden')->from(Garden::class, 'garden')->orderBy('garden.name');
        $query->where($query->expr()->orX(
            RelationConstraint::existsLike($query, RelationPathResolver::analyseQueryPath($query, 'seedlings.name') ?? throw new LogicException(), 'Oak', forceCaseInsensitive: false),
            RelationConstraint::existsLike($query, RelationPathResolver::analyseQueryPath($query, 'plants.name') ?? throw new LogicException(), 'Moss', forceCaseInsensitive: false),
        ));

        expect(array_map(static fn (Garden $garden): string => $garden->name, $query->getQuery()->getResult()))->toBe(['Kew', 'Wisley'])
            ->and($query->getDQL())->toContain('FROM ' . Garden::class . ' sy_x0 JOIN sy_x0.seedlings sy_x0_0')
            ->and($query->getDQL())->toContain('FROM ' . Garden::class . ' sy_x1 JOIN sy_x1.plants sy_x1_0');
    });

    it('correlates a root of any identifier width on every key field, projecting one column', function (Closure $owners, string $subselect): void {
        [$owner, $other, $seeded] = $owners();
        $leek = new Seedling('Leek');
        $kale = new Seedling('Kale');
        $property = match ($owner::class) {
            Terrace::class => 'terrace',
            Allotment::class => 'allotment',
            default => 'vent',
        };
        $leek->{$property} = $owner;
        $kale->{$property} = $other;
        $this->seed(...[...$seeded, $owner, $other, $leek, $kale]);

        $query = $this->entityManager()->createQueryBuilder()->select('owner')->from($owner::class, 'owner');
        $resolved = RelationPathResolver::analyseQueryPath($query, 'seedlings.name') ?? throw new LogicException();
        $exists = RelationConstraint::existsLike($query, $resolved, 'Leek', forceCaseInsensitive: false);
        $query->where($exists);

        $found = $query->getQuery()->getResult();

        expect($exists)->toBe(sprintf($subselect, $owner::class))
            ->and($found)->toHaveCount(1)
            ->and($found[0]->seedlings->map(static fn (Seedling $seedling): string => $seedling->name)->toArray())->toBe(['Leek']);
    })->with([
        'two scalars' => [
            fn (): array => [new Terrace(1, 1), new Terrace(1, 2), []],
            'EXISTS (SELECT sy_x0.estate FROM %s sy_x0 JOIN sy_x0.seedlings sy_x0_0 WHERE sy_x0.estate = owner.estate AND sy_x0.number = owner.number AND sy_x0_0.name LIKE :sylament_search_0)',
        ],
        'an association and a scalar' => [
            function (): array {
                $garden = new Garden('Ash');

                return [new Allotment($garden, 1), new Allotment($garden, 2), [$garden]];
            },
            'EXISTS (SELECT IDENTITY(sy_x0.garden) FROM %s sy_x0 JOIN sy_x0.seedlings sy_x0_0 WHERE sy_x0.garden = owner.garden AND sy_x0.number = owner.number AND sy_x0_0.name LIKE :sylament_search_0)',
        ],
        'one association alone' => [
            function (): array {
                $greenhouse = new Greenhouse(Uuid::v7());
                $other = new Greenhouse(Uuid::v7());

                return [new GreenhouseVent($greenhouse), new GreenhouseVent($other), [$greenhouse, $other]];
            },
            'EXISTS (SELECT IDENTITY(sy_x0.greenhouse) FROM %s sy_x0 JOIN sy_x0.seedlings sy_x0_0 WHERE sy_x0.greenhouse = owner.greenhouse AND sy_x0_0.name LIKE :sylament_search_0)',
        ],
    ]);
});

describe('a related field holding no character data', function (): void {
    it('is cast to text on PostgreSQL alone', function (): void {
        $exists = function (EntityManagerInterface $entityManager): string {
            $query = $entityManager->createQueryBuilder()->select('seedling')->from(Seedling::class, 'seedling');

            return RelationConstraint::existsLike($query, RelationPathResolver::analyseQueryPath($query, 'garden.id') ?? throw new LogicException(), '1', forceCaseInsensitive: false);
        };

        $like = static fn (string $exists): string => mb_substr($exists, mb_strrpos($exists, ' AND ') + 5, -1);

        expect($like($exists($this->platformEntityManager(TestPlatform::PostgreSql))))->toBe("CONCAT(sy_x0_0.id, '') LIKE :sylament_search_0")
            ->and($like($exists($this->platformEntityManager(TestPlatform::MySql))))->toBe('sy_x0_0.id LIKE :sylament_search_0')
            ->and($like($exists($this->platformEntityManager(TestPlatform::Sqlite))))->toBe('sy_x0_0.id LIKE :sylament_search_0');
    });

    it('matches a root by a related integer on every platform', function (): void {
        $oak = new Seedling('Oak');
        $oak->garden = new Garden('Kew');
        $this->seed($oak->garden, $oak, new Seedling('Elm'));

        $query = $this->entityManager()->createQueryBuilder()->select('seedling')->from(Seedling::class, 'seedling');
        $query->where(RelationConstraint::existsLike($query, RelationPathResolver::analyseQueryPath($query, 'garden.id') ?? throw new LogicException(), (string) $oak->garden->id, forceCaseInsensitive: null));

        expect(array_map(static fn (Seedling $seedling): string => $seedling->name, $query->getQuery()->getResult()))->toBe(['Oak']);
    });
});
