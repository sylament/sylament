<?php

declare(strict_types=1);

use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use Sylament\Support\Tests\Doctrine\TestPlatform;
use Sylament\Tables\Query\SearchConstraint;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Shortcut;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Song;
use Sylament\Tables\Tests\Fixtures\Doctrine\EntityManagerFactory;

it('assembles a case-sensitive LIKE by default, and a lowered one on PostgreSQL', function (): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()->select('song')->from(Song::class, 'song');

    $comparison = SearchConstraint::like($query, 'song.title', 'Tita', forceCaseInsensitive: null);

    expect((string) $comparison)->toBe(match (TestPlatform::current()) {
        TestPlatform::PostgreSql => 'LOWER(song.title) LIKE :sylament_search_0',
        TestPlatform::Sqlite, TestPlatform::MySql => 'song.title LIKE :sylament_search_0',
    })
        ->and($query->getParameter('sylament_search_0')?->getValue())->toBe(match (TestPlatform::current()) {
            TestPlatform::PostgreSql => '%tita%',
            TestPlatform::Sqlite, TestPlatform::MySql => '%Tita%',
        });
});

it('lowers both sides when forced case-insensitive', function (): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()->select('song')->from(Song::class, 'song');

    $comparison = SearchConstraint::like($query, 'song.title', 'Tita', forceCaseInsensitive: true);

    expect((string) $comparison)->toBe('LOWER(song.title) LIKE :sylament_search_0')
        ->and($query->getParameter('sylament_search_0')?->getValue())->toBe('%tita%');
});

it('names parameters uniquely across calls', function (): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()->select('song')->from(Song::class, 'song');

    $first = SearchConstraint::like($query, 'song.title', 'Space', forceCaseInsensitive: null);
    $second = SearchConstraint::like($query, 'song.title', 'Oddity', forceCaseInsensitive: null);

    expect([(string) $first, (string) $second])->toBe(match (TestPlatform::current()) {
        TestPlatform::PostgreSql => ['LOWER(song.title) LIKE :sylament_search_0', 'LOWER(song.title) LIKE :sylament_search_1'],
        TestPlatform::Sqlite, TestPlatform::MySql => ['song.title LIKE :sylament_search_0', 'song.title LIKE :sylament_search_1'],
    })
        ->and([$query->getParameter('sylament_search_0')?->getValue(), $query->getParameter('sylament_search_1')?->getValue()])->toBe(match (TestPlatform::current()) {
            TestPlatform::PostgreSql => ['%space%', '%oddity%'],
            TestPlatform::Sqlite, TestPlatform::MySql => ['%Space%', '%Oddity%'],
        });
});

function fetchJoinAliasQuery(string $join, string $alias, bool $selects): QueryBuilder
{
    $query = EntityManagerFactory::create()->createQueryBuilder()->select('record')->from(Shortcut::class, 'record')->leftJoin($join, $alias);

    return $selects ? $query->addSelect($alias) : $query;
}

it('refuses a searchable path whose head is an alias the query fetch-joins as a collection', function (): void {
    expect(fn (): string|Comparison => SearchConstraint::forColumn(fetchJoinAliasQuery('record.hits', 'hits', selects: true), 'hits.agent', 'curl', null))
        ->toThrow(LogicException::class, "The searchable path [hits.agent] begins with [hits], an alias the query fetch-joins as a collection, so a search would narrow each row's [hits] rather than choose rows. Alias that join something the path does not begin with; a table column can also take its search over with searchable(query:).");
});

it('keeps searching a declared alias the query joins without fetching, or fetches as a single record', function (string $join, string $alias, bool $selects, string $column): void {
    expect(SearchConstraint::forColumn(fetchJoinAliasQuery($join, $alias, $selects), $column, 'curl', null))->toBeInstanceOf(Comparison::class);
})->with([
    'a collection joined, not selected' => ['record.hits', 'hits', false, 'hits.agent'],
    'a single record fetched' => ['record.curator', 'curator', true, 'curator.name'],
]);

it('routes the path into the correlated EXISTS once the fetch join takes another alias', function (): void {
    expect(SearchConstraint::forColumn(fetchJoinAliasQuery('record.hits', 'shortcutHit', selects: true), 'hits.agent', 'curl', null))
        ->toBeString()
        ->toStartWith('EXISTS (');
});
