<?php

declare(strict_types=1);

use Doctrine\ORM\QueryBuilder;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\State\Relationship;
use Sylament\Support\Tests\Doctrine\SqlNormalizer;
use Sylament\Tables\Query\ColumnPathResolver;
use Sylament\Tables\Query\Hop;
use Sylament\Tables\Query\RelationJoiner;
use Sylament\Tables\Query\SummaryQuery;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Artist;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Circuit;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Label;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Song;
use Sylament\Tables\Tests\Fixtures\Doctrine\EntityManagerFactory;

/**
 * A relation-scoped query rooted at {@see Circuit}, narrowed to the
 * single-identifier owner that holds the join table — so the reader's
 * correlated subselect reaches the builder as a raw WHERE string declaring
 * `sy_owner` and `sy_related`, which are exactly the two names this builder's
 * own minter produces for a hop called `owner` or `related`. One of the two
 * seeded circuits is the owner's, and it points at the other through both
 * to-ones, so a sort or a summary through either hop has a row to answer with.
 */
function relationJoinerScopedCircuitsQuery(): QueryBuilder
{
    $entityManager = EntityManagerFactory::create();

    $northern = new Circuit();
    $northern->setRegion('north');
    $northern->setSlot('one');
    $northern->setName('Northern');

    $southern = new Circuit();
    $southern->setRegion('south');
    $southern->setSlot('two');
    $southern->setName('Southern');
    $southern->setOwner($northern);
    $southern->setRelated($northern);

    $ada = new Artist(1, 'Ada');
    $ada->addCircuit($southern);

    EntityManagerFactory::seed($entityManager, $northern, $southern, $ada);

    return new AssociationReader($entityManager)
        ->createRelationshipQuery(new Relationship($entityManager->find(Artist::class, 1), 'circuits'), 'record');
}

it('left-joins each hop with a deterministic sy_ alias', function (): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()->select('s')->from(Song::class, 's');
    $hops = [new Hop('artist', Artist::class, false), new Hop('label', Label::class, false)];

    $alias = RelationJoiner::ensureJoined($query, 's', $hops);

    expect($alias)->toBe('sy_artist_label')
        ->and($query->getDQL())->toContain('LEFT JOIN s.artist sy_artist LEFT JOIN sy_artist.label sy_artist_label');
});

it('returns the parent alias untouched for an empty hop list', function (): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()->select('s')->from(Song::class, 's');

    $alias = RelationJoiner::ensureJoined($query, 's', []);

    expect($alias)->toBe('s')
        ->and($query->getDQLPart('join'))->toBe([]);
});

it('reuses an existing join for the same parent alias and property', function (): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()
        ->select('s')->from(Song::class, 's')
        ->leftJoin('s.artist', 'writer');

    $alias = RelationJoiner::ensureJoined($query, 's', [new Hop('artist', Artist::class, false)]);

    expect($alias)->toBe('writer')
        ->and($query->getDQLPart('join')['s'])->toHaveCount(1);
});

it('is idempotent across calls', function (): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()->select('s')->from(Song::class, 's');
    $hops = [new Hop('artist', Artist::class, false), new Hop('label', Label::class, false)];

    $first = RelationJoiner::ensureJoined($query, 's', $hops);
    $second = RelationJoiner::ensureJoined($query, 's', $hops);

    expect($second)->toBe($first)
        ->and($query->getDQLPart('join')['s'])->toHaveCount(2);
});

it('suffixes a minted alias that collides', function (): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()
        ->select('s')->from(Song::class, 's')
        ->leftJoin('s.genres', 'sy_artist');

    $alias = RelationJoiner::ensureJoined($query, 's', [new Hop('artist', Artist::class, false)]);

    expect($alias)->toBe('sy_artist_2')
        ->and($query->getDQL())->toContain('LEFT JOIN s.artist sy_artist_2');
});

it('keeps suffixing until the minted alias is free', function (): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()
        ->select('s')->from(Song::class, 's')
        ->leftJoin('s.genres', 'sy_artist')
        ->leftJoin('s.genres', 'sy_artist_2');

    $alias = RelationJoiner::ensureJoined($query, 's', [new Hop('artist', Artist::class, false)]);

    expect($alias)->toBe('sy_artist_3');
});

it('suffixes a name a raw where string already declares as an identification variable', function (): void {
    $query = relationJoinerScopedCircuitsQuery();

    expect($query->getAllAliases())->toBe(['record'])
        ->and(RelationJoiner::ensureJoined($query, 'record', [new Hop('owner', Circuit::class, false)]))->toBe('sy_owner_2')
        ->and($query->getDQL())->toContain('LEFT JOIN record.owner sy_owner_2');
});

it('keeps suffixing past an alias the builder declares and one only the dql carries', function (): void {
    $query = relationJoinerScopedCircuitsQuery()->leftJoin('record.related', 'sy_owner_2');

    expect(RelationJoiner::ensureJoined($query, 'record', [new Hop('owner', Circuit::class, false)]))->toBe('sy_owner_3');
});

it('leaves a name free that stands in the dql only as a parameter', function (): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()
        ->select('record')->from(Circuit::class, 'record')
        ->where('record.name = :sy_owner')
        ->setParameter('sy_owner', 'Northern');

    expect(RelationJoiner::ensureJoined($query, 'record', [new Hop('owner', Circuit::class, false)]))->toBe('sy_owner');
});

it('leaves a name free that stands in the dql only as a field of a path', function (): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()
        ->select('record')->from(Circuit::class, 'record')
        ->where('record.sy_owner IS NOT NULL');

    expect(RelationJoiner::ensureJoined($query, 'record', [new Hop('owner', Circuit::class, false)]))->toBe('sy_owner');
});

it('leaves a name free that stands in the dql only inside a longer identifier', function (): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()
        ->select('record')->from(Circuit::class, 'record')
        ->leftJoin('record.related', 'sy_ownerly');

    expect(RelationJoiner::ensureJoined($query, 'record', [new Hop('owner', Circuit::class, false)]))->toBe('sy_owner');
});

it('leaves a name free that stands in the dql only at the tail of a longer identifier', function (): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()
        ->select('record')->from(Circuit::class, 'record')
        ->leftJoin('record.related', 'far_sy_owner');

    expect(RelationJoiner::ensureJoined($query, 'record', [new Hop('owner', Circuit::class, false)]))->toBe('sy_owner')
        ->and($query->getDQL())->toContain('LEFT JOIN record.related far_sy_owner LEFT JOIN record.owner sy_owner');
});

/*
 * DQL resolves its identification variables case-sensitively — `SY_OWNER`
 * declared beside `sy_owner` parses and compiles rather than raising
 * `'sy_owner' is already defined` (executed, ORM 3.6.8) — so the scan is
 * case-sensitive too, and the upper-case name blocks nothing.
 */
it('leaves a name free that the dql carries only in another case', function (): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()
        ->select('record')->from(Circuit::class, 'record')
        ->leftJoin('record.related', 'SY_OWNER');

    expect(RelationJoiner::ensureJoined($query, 'record', [new Hop('owner', Circuit::class, false)]))->toBe('sy_owner')
        ->and($query->getDQL())->toContain('LEFT JOIN record.related SY_OWNER LEFT JOIN record.owner sy_owner')
        ->and(SqlNormalizer::normalize($query->getQuery()->getSQL()))->toContain('LEFT JOIN Circuit a1_ ON a0_.related_region = a1_.region');
});

it('sorts a relation-scoped query through a hop named after the subselect owner alias', function (): void {
    $query = relationJoinerScopedCircuitsQuery();

    $expression = ColumnPathResolver::resolveOrderable($query, 'owner.name');
    $records = $query->orderBy($expression, 'ASC')->getQuery()->getResult();

    expect($expression)->toBe('sy_owner_2.name')
        ->and($query->getDQL())->toContain('LEFT JOIN record.owner sy_owner_2')
        ->and($records)->toHaveCount(1)
        ->and($records[0]->getRegion())->toBe('south');
});

it('sorts a relation-scoped query through a hop named after the subselect correlation alias', function (): void {
    $query = relationJoinerScopedCircuitsQuery();

    $expression = ColumnPathResolver::resolveOrderable($query, 'related.name');
    $records = $query->orderBy($expression, 'ASC')->getQuery()->getResult();

    expect($expression)->toBe('sy_related_2.name')
        ->and($query->getDQL())->toContain('LEFT JOIN record.related sy_related_2')
        ->and($records)->toHaveCount(1)
        ->and($records[0]->getRegion())->toBe('south');
});

it('summarizes a relation-scoped query through a hop named after the subselect owner alias', function (): void {
    $query = relationJoinerScopedCircuitsQuery();

    $attribute = SummaryQuery::qualifyAttribute($query, 'owner.name');

    expect($attribute)->toBe('sy_owner_2.name')
        ->and(SummaryQuery::readRow($query, ["COUNT({$attribute}) AS sy_summary_0"]))->toBe(['sy_summary_0' => 1]);
});
