<?php

declare(strict_types=1);

use Sylament\Schemas\Query\SearchConstraint;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Schemas\Tests\Fixtures\Doctrine\EntityManagerFactory;

it('assembles a case-sensitive LIKE on a non-postgres platform', function (): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()->select('post')->from(Post::class, 'post');

    $comparison = SearchConstraint::like($query, 'post.title', 'Tita', forceCaseInsensitive: null);

    expect((string) $comparison)->toBe('post.title LIKE :sylament_search_0')
        ->and($query->getParameter('sylament_search_0')?->getValue())->toBe('%Tita%');
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

    expect((string) $first)->toBe('post.title LIKE :sylament_search_0')
        ->and((string) $second)->toBe('post.title LIKE :sylament_search_1')
        ->and($query->getParameter('sylament_search_0')?->getValue())->toBe('%Space%')
        ->and($query->getParameter('sylament_search_1')?->getValue())->toBe('%Oddity%');
});
