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

it('casts a field that holds no character data on PostgreSQL alone, whatever the case folding', function (string $path, bool $casts): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()
        ->select('post')
        ->from(Post::class, 'post')
        ->leftJoin('post.author', 'author')
        ->leftJoin(Ticket::class, 'ticket', 'WITH', '1 = 1')
        ->leftJoin(Flagged::class, 'flagged', 'WITH', '1 = 1')
        ->leftJoin(Listing::class, 'listing', 'WITH', '1 = 1');

    $folded = SearchConstraint::like($query, $path, '1', forceCaseInsensitive: true);
    $exact = SearchConstraint::like($query, $path, '1', forceCaseInsensitive: false);

    $operand = $casts && TestPlatform::current() === TestPlatform::PostgreSql ? "CONCAT({$path}, '')" : $path;

    expect((string) $folded)->toBe("LOWER({$operand}) LIKE :sylament_search_0")
        ->and((string) $exact)->toBe("{$operand} LIKE :sylament_search_1");
})->with([
    'an integer on the root' => ['post.id', true],
    'an integer across a join' => ['author.id', true],
    'a guid' => ['ticket.id', true],
    'a bigint' => ['ticket.serial', true],
    'a boolean' => ['flagged.flag', true],
    'a string' => ['post.title', false],
    'a string under a type of its own' => ['listing.sku', false],
    'an alias the builder does not declare' => ['sy_x0_0.id', false],
    'an expression' => ['LOWER(post.title)', false],
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
