<?php

declare(strict_types=1);

use Sylament\Tables\Query\Hop;
use Sylament\Tables\Query\RelationPathResolver;
use Sylament\Tables\Query\ResolvedRelationPath;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Entity\PropagatedCutting;
use Sylament\Tests\Fixtures\Entity\Seedling;

describe('a name with nothing to cross', function (): void {
    it('leaves a dotless name and a head the builder declares as an alias to the caller', function (): void {
        $query = $this->entityManager()->createQueryBuilder()
            ->select('seedling')
            ->from(Seedling::class, 'seedling')
            ->leftJoin('seedling.garden', 'garden');

        expect(RelationPathResolver::analyseQueryPath($query, 'name'))->toBeNull()
            ->and(RelationPathResolver::analyseQueryPath($query, 'garden.name'))->toBeNull()
            ->and(RelationPathResolver::analyseQueryPath($query, 'unknown.name'))->toBeNull();
    });
});

describe('the mapping walk', function (): void {
    it('splits a path into its association hops and the field at the end, a collection hop marked', function (): void {
        $query = $this->entityManager()->createQueryBuilder()->select('seedling')->from(Seedling::class, 'seedling');

        expect(RelationPathResolver::analyseQueryPath($query, 'garden.supplier.name'))->toEqual(new ResolvedRelationPath(
            [new Hop('garden', Garden::class, isToMany: false), new Hop('supplier', Product::class, isToMany: false)],
            'name',
        ))->and(RelationPathResolver::analyseQueryPath($query, 'garden.seedlings.name'))->toEqual(new ResolvedRelationPath(
            [new Hop('garden', Garden::class, isToMany: false), new Hop('seedlings', Seedling::class, isToMany: true)],
            'name',
        ));
    });

    it('reads an embeddable field as a field of the root, before its head is asked for an association', function (): void {
        $query = $this->entityManager()->createQueryBuilder()->select('cutting')->from(PropagatedCutting::class, 'cutting');

        expect(RelationPathResolver::analyseQueryPath($query, 'origin.nursery'))->toEqual(new ResolvedRelationPath([], 'origin.nursery'))
            ->and(RelationPathResolver::analyseQueryPath($query, 'parent.origin.nursery'))->toEqual(new ResolvedRelationPath(
                [new Hop('parent', PropagatedCutting::class, isToMany: false)],
                'origin.nursery',
            ));
    });
});

describe('a path ending on an association', function (): void {
    it('is refused on the query seam, naming the field it lacks, and read leniently on the display seam', function (): void {
        $query = $this->entityManager()->createQueryBuilder()->select('seedling')->from(Seedling::class, 'seedling');

        expect(fn (): ?ResolvedRelationPath => RelationPathResolver::analyseQueryPath($query, 'garden.supplier'))
            ->toThrow(LogicException::class, 'Cannot resolve [garden.supplier]: the path ends in an association rather than a field — name a field on it, e.g. [garden.supplier.<field>].')
            ->and(RelationPathResolver::tryAnalyseQueryPath($query, 'garden.supplier'))->toBeNull()
            ->and(RelationPathResolver::tryAnalyseQueryPath($query, 'garden.name'))->toEqual(new ResolvedRelationPath(
                [new Hop('garden', Garden::class, isToMany: false)],
                'name',
            ));
    });
});
