<?php

declare(strict_types=1);

use Doctrine\ORM\QueryBuilder;
use Sylament\Schemas\State\Relationship;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Allotment;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Entity\Plant;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Entity\Seedling;
use Sylament\Tests\Fixtures\Entity\Terrace;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Tables\AssociableRelationTable;
use Sylament\Tests\Fixtures\Tables\AttachableRelationTable;
use Sylament\Tests\Fixtures\Tables\ProductTable;
use Sylament\Tests\Fixtures\Tables\SeedlingGardenTable;
use Twig\Error\RuntimeError;

describe('the query lane', function (): void {
    it('hands out a scoped clone of one base query, a closure declaring it run once, so a caller narrowing one read narrows no other', function (): void {
        $this->seed(new Product('Lamp'), new Product('Desk'));
        $runs = 0;

        $table = Table::make(new ProductTable($this->entityManager()))->query(function () use (&$runs): QueryBuilder {
            $runs++;

            return $this->entityManager()->createQueryBuilder()->select('product')->from(Product::class, 'product')->orderBy('product.name');
        });

        $names = static fn (QueryBuilder $query): array => array_map(static fn (Product $product): string => $product->name, $query->getQuery()->getResult());

        $narrowed = $table->getQuery()?->andWhere("product.name = 'Lamp'") ?? throw new LogicException('The table has no query.');

        expect($names($narrowed))->toBe(['Lamp'])
            ->and($names($table->getQuery() ?? throw new LogicException('The table has no query.')))->toBe(['Desk', 'Lamp'])
            ->and($table->getQueryEntityManager())->toBe($this->entityManager())
            ->and($runs)->toBe(1);
    });

    it("runs its scopes in registration order on that clone, a scope returning nothing keeping the query it was handed, and scopes a caller's own builder", function (): void {
        $this->seed(new Product('Lamp'), new Product('Desk'), new Product('Lantern'));
        $seen = [];

        $table = Table::make(new ProductTable($this->entityManager()))
            ->query($this->entityManager()->createQueryBuilder()->select('product')->from(Product::class, 'product')->orderBy('product.name'))
            ->modifyQueryUsing(function (QueryBuilder $query) use (&$seen): QueryBuilder {
                $seen[] = 'narrow';

                return $query->andWhere("product.name LIKE 'L%'");
            })
            ->modifyQueryUsing(function (QueryBuilder $query, bool $isResolvingRecord) use (&$seen): void {
                $seen[] = $isResolvingRecord ? 'resolving' : 'listing';
                $query->andWhere("product.name <> 'Lantern'");
            });

        $names = static fn (QueryBuilder $query): array => array_map(static fn (Product $product): string => $product->name, $query->getQuery()->getResult());

        $listed = $names($table->getQuery() ?? throw new LogicException('The table has no query.'));
        $resolved = $names($table->getQuery(isResolvingRecord: true) ?? throw new LogicException('The table has no query.'));
        $own = $names($table->applyQueryScopes($this->entityManager()->createQueryBuilder()->select('product')->from(Product::class, 'product')));

        expect([$listed, $resolved, $own])->toBe([['Lamp'], ['Lamp'], ['Lamp']])
            ->and($seen)->toBe(['narrow', 'listing', 'narrow', 'resolving', 'narrow', 'listing']);
    });

    it('evaluates a re-declared query afresh, and a declaration answering null on every read', function (): void {
        $runs = 0;
        $table = Table::make(new ProductTable($this->entityManager()))->query(function () use (&$runs): ?QueryBuilder {
            $runs++;

            return null;
        });

        expect([$table->getQuery(), $table->getQuery(), $runs])->toBe([null, null, 2]);

        $table->query($this->entityManager()->createQueryBuilder()->select('product')->from(Product::class, 'product'));
        $before = [$table->getQuery()?->getRootEntities(), $table->getRecordClass()];

        $table->query($this->entityManager()->createQueryBuilder()->select('seedling')->from(Seedling::class, 'seedling'));

        expect($before)->toBe([[Product::class], Product::class])
            ->and([$table->getQuery()?->getRootEntities(), $table->getRecordClass()])->toBe([[Seedling::class], Seedling::class]);
    });
});

describe('the relation-scoped table', function (): void {
    it("lists exactly the owner's related rows", function (Closure $scene): void {
        [$host, $owner, $relation, $seeded, $owned, $foreign] = $scene();
        $this->seed(...$seeded);

        $this->createLiveComponent($host, $host::props($this->entityManager(), $owner, $relation))
            ->assertCanSeeTableRecords($owned)
            ->assertCanNotSeeTableRecords($foreign);
    })->with([
        'a one-to-many' => [static function (): array {
            $kew = new Garden('Kew');
            [$oak, $ash, $elm] = [new Seedling('Oak'), new Seedling('Ash'), new Seedling('Elm')];
            [$oak->garden, $ash->garden] = [$kew, $kew];

            return [AssociableRelationTable::class, $kew, 'seedlings', [$kew, $oak, $ash, $elm], [$oak, $ash], [$elm]];
        }],
        'a many-to-many' => [static function (): array {
            $kew = new Garden('Kew');
            $kew->addPlant($fern = new Plant('Fern'));

            $moss = new Plant('Moss');

            return [AttachableRelationTable::class, $kew, 'plants', [$kew, $fern, $moss], [$fern], [$moss]];
        }],
        'a many-to-many of an owner keyed by two scalars' => [static function (): array {
            $terrace = new Terrace(1, 1);
            $terrace->addPlant($fern = new Plant('Fern'));

            $other = new Terrace(1, 2);
            $other->addPlant($moss = new Plant('Moss'));

            return [AttachableRelationTable::class, $terrace, 'plants', [$terrace, $other, $fern, $moss], [$fern], [$moss]];
        }],
        'a many-to-many of an owner keyed by an association and a scalar' => [static function (): array {
            $garden = new Garden('Ash');
            $allotment = new Allotment($garden, 1);
            $allotment->addPlant($fern = new Plant('Fern'));

            $other = new Allotment($garden, 2);
            $other->addPlant($moss = new Plant('Moss'));

            return [AttachableRelationTable::class, $allotment, 'plants', [$garden, $allotment, $other, $fern, $moss], [$fern], [$moss]];
        }],
    ]);

    it("searches, sorts, counts and selects all within the owner's rows, and resolves no key outside them", function (): void {
        $kew = new Garden('Kew');
        [$oak, $olive, $ash, $orange] = [new Seedling('Oak'), new Seedling('Olive'), new Seedling('Ash'), new Seedling('Orange')];
        [$oak->garden, $olive->garden, $ash->garden] = [$kew, $kew, $kew];
        $this->seed($kew, $oak, $olive, $ash, $orange);

        $tester = $this->createLiveComponent(SeedlingGardenTable::class, ['gardenId' => $kew->id])
            ->assertCountTableRecords(3);

        $selectable = $tester->component()->getAllSelectableTableRecordKeys();
        sort($selectable);

        expect($selectable)->toBe([(string) $oak->id, (string) $olive->id, (string) $ash->id]);

        $tester
            ->searchTable('O')
            ->sortTable('name', 'desc')
            ->assertCanSeeTableRecords([$olive, $oak], inOrder: true)
            ->assertCanNotSeeTableRecords([$ash, $orange]);

        $host = $tester->component();

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['relationship evaluated'])
            ->and($host->getTableRecord((string) $oak->id))->toBeInstanceOf(Seedling::class)
            ->and($host->getTableRecord((string) $orange->id))->toBeNull();
    });

    it('evaluates its relationship once, and again once it is re-declared', function (): void {
        $this->seed($kew = new Garden('Kew'));
        $kew = $this->entityManager()->find(Garden::class, $kew->id);
        $runs = 0;
        $relationship = function () use (&$runs, $kew): Relationship {
            $runs++;

            return new Relationship($kew, 'seedlings');
        };

        $table = Table::make(new ProductTable($this->entityManager()))->relationship($relationship);
        $table->getQuery();
        $table->getQuery();
        $table->getRelationship();

        expect([$runs, $table->isRelationshipBaseQuery(), $table->getQuery()?->getRootEntities()])->toBe([1, true, [Seedling::class]]);

        $table->relationship($relationship)->getQuery();

        expect($runs)->toBe(2);
    });

    it('runs a table declaring a query beside its relationship on the query, never evaluating the relationship', function (): void {
        $kew = new Garden('Kew');
        $oak = new Seedling('Oak');
        $oak->garden = $kew;
        $this->seed($kew, $oak, $elm = new Seedling('Elm'));

        $this->createLiveComponent(SeedlingGardenTable::class, ['gardenId' => $kew->id, 'variants' => ['queryBeside']])
            ->assertCanSeeTableRecords([$oak, $elm]);

        $table = Table::make(new ProductTable($this->entityManager()))
            ->query($this->entityManager()->createQueryBuilder()->select('product')->from(Product::class, 'product'))
            ->relationship(fn (): Relationship => throw new LogicException('The relationship was evaluated.'));

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe([])
            ->and([$table->isRelationshipBaseQuery(), $table->getQuery()?->getRootEntities()])->toBe([false, [Product::class]]);
    });

    it('refuses to render a table whose relationship answers no owner', function (): void {
        expect(fn () => $this->createLiveComponent(SeedlingGardenTable::class, ['variants' => ['ownerless']])->render())
            ->toThrow(RuntimeError::class, 'Table [' . SeedlingGardenTable::class . '] must have [records()], [query()] or [relationship()].');
    });
});
