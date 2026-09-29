<?php

declare(strict_types=1);

use Sylament\Support\Tests\Doctrine\TestPlatform;
use Sylament\Tables\Query\SearchConstraint;
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
