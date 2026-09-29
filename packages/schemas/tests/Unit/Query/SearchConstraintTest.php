<?php

declare(strict_types=1);

use Sylament\Schemas\Query\SearchConstraint;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Schemas\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Testing\Doctrine\TestPlatform;

it('assembles a case-sensitive LIKE by default, and a lowered one on PostgreSQL', function (): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()->select('post')->from(Post::class, 'post');

    $comparison = SearchConstraint::like($query, 'post.title', 'Tita', forceCaseInsensitive: null);

    expect((string) $comparison)->toBe(match (TestPlatform::current()) {
        TestPlatform::PostgreSql => 'LOWER(post.title) LIKE :sylament_search_0',
        TestPlatform::Sqlite, TestPlatform::MySql => 'post.title LIKE :sylament_search_0',
    })
        ->and($query->getParameter('sylament_search_0')?->getValue())->toBe(match (TestPlatform::current()) {
            TestPlatform::PostgreSql => '%tita%',
            TestPlatform::Sqlite, TestPlatform::MySql => '%Tita%',
        });
});

it('lowers both sides when forced case-insensitive', function (): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()->select('post')->from(Post::class, 'post');

    $comparison = SearchConstraint::like($query, 'post.title', 'Tita', forceCaseInsensitive: true);

    expect((string) $comparison)->toBe('LOWER(post.title) LIKE :sylament_search_0')
        ->and($query->getParameter('sylament_search_0')?->getValue())->toBe('%tita%');
});

it('names parameters uniquely across calls', function (): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()->select('post')->from(Post::class, 'post');

    $first = SearchConstraint::like($query, 'post.title', 'Space', forceCaseInsensitive: null);
    $second = SearchConstraint::like($query, 'post.title', 'Oddity', forceCaseInsensitive: null);

    expect([(string) $first, (string) $second])->toBe(match (TestPlatform::current()) {
        TestPlatform::PostgreSql => ['LOWER(post.title) LIKE :sylament_search_0', 'LOWER(post.title) LIKE :sylament_search_1'],
        TestPlatform::Sqlite, TestPlatform::MySql => ['post.title LIKE :sylament_search_0', 'post.title LIKE :sylament_search_1'],
    })
        ->and([$query->getParameter('sylament_search_0')?->getValue(), $query->getParameter('sylament_search_1')?->getValue()])->toBe(match (TestPlatform::current()) {
            TestPlatform::PostgreSql => ['%space%', '%oddity%'],
            TestPlatform::Sqlite, TestPlatform::MySql => ['%Space%', '%Oddity%'],
        });
});
