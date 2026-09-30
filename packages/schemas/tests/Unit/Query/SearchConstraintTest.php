<?php

declare(strict_types=1);

use Sylament\Schemas\Query\SearchConstraint;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Author;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Flagged;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Listing;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Ticket;
use Sylament\Schemas\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Schemas\Tests\Fixtures\Doctrine\IdentityRig;
use Sylament\Support\Tests\Doctrine\TestPlatform;

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

it('casts a field that holds no character data on PostgreSQL alone, whatever the case folding', function (string $path, array $postgreSql, array $elsewhere): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()
        ->select('post')
        ->from(Post::class, 'post')
        ->leftJoin('post.author', 'author')
        ->leftJoin(Ticket::class, 'ticket', 'WITH', '1 = 1')
        ->leftJoin(Flagged::class, 'flagged', 'WITH', '1 = 1')
        ->leftJoin(Listing::class, 'listing', 'WITH', '1 = 1');

    $folded = SearchConstraint::like($query, $path, '1', forceCaseInsensitive: true);
    $exact = SearchConstraint::like($query, $path, '1', forceCaseInsensitive: false);

    expect([(string) $folded, (string) $exact])->toBe(match (TestPlatform::current()) {
        TestPlatform::PostgreSql => $postgreSql,
        TestPlatform::Sqlite, TestPlatform::MySql => $elsewhere,
    });
})->with([
    'an integer on the root' => [
        'post.id',
        ["LOWER(CONCAT(post.id, '')) LIKE :sylament_search_0", "CONCAT(post.id, '') LIKE :sylament_search_1"],
        ['LOWER(post.id) LIKE :sylament_search_0', 'post.id LIKE :sylament_search_1'],
    ],
    'an integer across a join' => [
        'author.id',
        ["LOWER(CONCAT(author.id, '')) LIKE :sylament_search_0", "CONCAT(author.id, '') LIKE :sylament_search_1"],
        ['LOWER(author.id) LIKE :sylament_search_0', 'author.id LIKE :sylament_search_1'],
    ],
    'a guid' => [
        'ticket.id',
        ["LOWER(CONCAT(ticket.id, '')) LIKE :sylament_search_0", "CONCAT(ticket.id, '') LIKE :sylament_search_1"],
        ['LOWER(ticket.id) LIKE :sylament_search_0', 'ticket.id LIKE :sylament_search_1'],
    ],
    'a bigint' => [
        'ticket.serial',
        ["LOWER(CONCAT(ticket.serial, '')) LIKE :sylament_search_0", "CONCAT(ticket.serial, '') LIKE :sylament_search_1"],
        ['LOWER(ticket.serial) LIKE :sylament_search_0', 'ticket.serial LIKE :sylament_search_1'],
    ],
    'a boolean' => [
        'flagged.flag',
        ["LOWER(CONCAT(flagged.flag, '')) LIKE :sylament_search_0", "CONCAT(flagged.flag, '') LIKE :sylament_search_1"],
        ['LOWER(flagged.flag) LIKE :sylament_search_0', 'flagged.flag LIKE :sylament_search_1'],
    ],
    'a string' => [
        'post.title',
        ['LOWER(post.title) LIKE :sylament_search_0', 'post.title LIKE :sylament_search_1'],
        ['LOWER(post.title) LIKE :sylament_search_0', 'post.title LIKE :sylament_search_1'],
    ],
    'a string under a type of its own' => [
        'listing.sku',
        ['LOWER(listing.sku) LIKE :sylament_search_0', 'listing.sku LIKE :sylament_search_1'],
        ['LOWER(listing.sku) LIKE :sylament_search_0', 'listing.sku LIKE :sylament_search_1'],
    ],
    'an alias the builder does not declare' => [
        'sy_x0_0.id',
        ['LOWER(sy_x0_0.id) LIKE :sylament_search_0', 'sy_x0_0.id LIKE :sylament_search_1'],
        ['LOWER(sy_x0_0.id) LIKE :sylament_search_0', 'sy_x0_0.id LIKE :sylament_search_1'],
    ],
    'an expression' => [
        'LOWER(post.title)',
        ['LOWER(LOWER(post.title)) LIKE :sylament_search_0', 'LOWER(post.title) LIKE :sylament_search_1'],
        ['LOWER(LOWER(post.title)) LIKE :sylament_search_0', 'LOWER(post.title) LIKE :sylament_search_1'],
    ],
]);

it('reads the class a subselect alias stands for from the caller', function (): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()->select('post')->from(Post::class, 'post');

    $comparison = SearchConstraint::like($query, 'sy_x0_0.id', '1', forceCaseInsensitive: false, class: Author::class);

    expect((string) $comparison)->toBe(match (TestPlatform::current()) {
        TestPlatform::PostgreSql => "CONCAT(sy_x0_0.id, '') LIKE :sylament_search_0",
        TestPlatform::Sqlite, TestPlatform::MySql => 'sy_x0_0.id LIKE :sylament_search_0',
    });
});

it('matches an integer and a guid field by their text on every platform', function (): void {
    $entityManager = IdentityRig::create();

    $byId = $entityManager->createQueryBuilder()->select('post')->from(Post::class, 'post');
    $byId->andWhere(SearchConstraint::like($byId, 'post.id', '1', forceCaseInsensitive: null));

    $byIdExactly = $entityManager->createQueryBuilder()->select('post')->from(Post::class, 'post');
    $byIdExactly->andWhere(SearchConstraint::like($byIdExactly, 'post.id', '1', forceCaseInsensitive: false));

    $byGuid = $entityManager->createQueryBuilder()->select('ticket')->from(Ticket::class, 'ticket');
    $byGuid->andWhere(SearchConstraint::like($byGuid, 'ticket.id', 'a0eebc99', forceCaseInsensitive: null));

    expect($byId->getQuery()->getResult())->not->toBe([])
        ->and($byIdExactly->getQuery()->getResult())->not->toBe([])
        ->and($byGuid->getQuery()->getResult())->not->toBe([]);
});

it('matches a boolean by the text its platform prints', function (): void {
    $entityManager = EntityManagerFactory::create();

    $on = new Flagged();
    $on->setFlag(true);
    $on->setTitle('on');

    EntityManagerFactory::seed($entityManager, $on);

    $query = $entityManager->createQueryBuilder()->select('flagged')->from(Flagged::class, 'flagged');
    $query->andWhere(SearchConstraint::like($query, 'flagged.flag', match (TestPlatform::current()) {
        TestPlatform::PostgreSql => 'true',
        TestPlatform::Sqlite, TestPlatform::MySql => '1',
    }, forceCaseInsensitive: null));

    expect($query->getQuery()->getResult())->toHaveCount(1);
});
