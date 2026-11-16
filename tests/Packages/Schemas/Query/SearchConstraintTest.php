<?php

declare(strict_types=1);

use Sylament\Schemas\Query\SearchConstraint;
use Sylament\Tests\Fixtures\Entity\ColumnKindSample;
use Sylament\Tests\Fixtures\Entity\ConvoyWagon;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Entity\Tally;
use Sylament\Tests\Infrastructure\Doctrine\TestPlatform;

describe('like()', function (): void {
    it('matches the rows whose field contains the term, bound as a parameter', function (): void {
        $this->seed(new Product('abc'), new Product('xyz'));

        $query = $this->entityManager()->createQueryBuilder()->select('product.name')->from(Product::class, 'product');
        $query->where(SearchConstraint::like($query, 'product.name', 'b', forceCaseInsensitive: null));

        expect($query->getQuery()->getSingleColumnResult())->toBe(['abc'])
            ->and($query->getParameter('sylament_search_0')?->getValue())->toBe('%b%');
    });

    it('mints a parameter of its own on every call, so two searches on one builder both bind', function (): void {
        $this->seed(new Product('abc'), new Product('abd'), new Product('xbc'));

        $query = $this->entityManager()->createQueryBuilder()->select('product.name')->from(Product::class, 'product');
        $query->where(SearchConstraint::like($query, 'product.name', 'ab', forceCaseInsensitive: false))
            ->andWhere(SearchConstraint::like($query, 'product.name', 'c', forceCaseInsensitive: false));

        expect($query->getQuery()->getSingleColumnResult())->toBe(['abc'])
            ->and($query->getParameter('sylament_search_0')?->getValue())->toBe('%ab%')
            ->and($query->getParameter('sylament_search_1')?->getValue())->toBe('%c%');
    });

    it('keeps a % and an _ the user types as wildcards, and a quote as a character like any other', function (string $search, array $expected): void {
        $this->seed(new Product('abc'), new Product('xyz'));

        $query = $this->entityManager()->createQueryBuilder()->select('product.name')->from(Product::class, 'product')->orderBy('product.name');
        $query->where(SearchConstraint::like($query, 'product.name', $search, forceCaseInsensitive: null));

        expect($query->getQuery()->getSingleColumnResult())->toBe($expected);
    })->with([
        'an underscore' => ['a_c', ['abc']],
        'a percent sign' => ['%', ['abc', 'xyz']],
        'a quote' => ["a'c", []],
    ]);
});

describe('case folding', function (): void {
    it('folds case by default on PostgreSQL alone, and as the caller forces it on every platform', function (): void {
        $like = function (TestPlatform $platform, ?bool $forceCaseInsensitive): array {
            $query = $this->platformEntityManager($platform)->createQueryBuilder()->select('product')->from(Product::class, 'product');
            $comparison = SearchConstraint::like($query, 'product.name', 'ABC', $forceCaseInsensitive);

            return [(string) $comparison, $query->getParameter('sylament_search_0')?->getValue()];
        };

        expect($like(TestPlatform::PostgreSql, null))->toBe(['LOWER(product.name) LIKE :sylament_search_0', '%abc%'])
            ->and($like(TestPlatform::MySql, null))->toBe(['product.name LIKE :sylament_search_0', '%ABC%'])
            ->and($like(TestPlatform::Sqlite, null))->toBe(['product.name LIKE :sylament_search_0', '%ABC%'])
            ->and($like(TestPlatform::MySql, true))->toBe(['LOWER(product.name) LIKE :sylament_search_0', '%abc%'])
            ->and($like(TestPlatform::PostgreSql, false))->toBe(['product.name LIKE :sylament_search_0', '%ABC%']);
    });

    it('matches a term typed in another case as each platform compares it', function (): void {
        $this->seed(new Product('Abc'));

        $matches = function (?bool $forceCaseInsensitive): int {
            $query = $this->entityManager()->createQueryBuilder()->select('product.name')->from(Product::class, 'product');
            $query->where(SearchConstraint::like($query, 'product.name', 'ABC', $forceCaseInsensitive));

            return count($query->getQuery()->getSingleColumnResult());
        };

        expect(['default' => $matches(null), 'forced' => $matches(true), 'off' => $matches(false)])->toBe(match (TestPlatform::current()) {
            TestPlatform::PostgreSql => ['default' => 1, 'forced' => 1, 'off' => 0],
            TestPlatform::Sqlite, TestPlatform::MySql => ['default' => 1, 'forced' => 1, 'off' => 1],
        });
    });
});

describe('non-character fields', function (): void {
    it('casts a field that holds no character data to text on PostgreSQL, folded or not, and leaves a character field as it is', function (string $class, string $field, array $expected): void {
        $query = $this->platformEntityManager(TestPlatform::PostgreSql)->createQueryBuilder()->select('sample')->from($class, 'sample');

        $folded = SearchConstraint::like($query, "sample.{$field}", '1', forceCaseInsensitive: true);
        $exact = SearchConstraint::like($query, "sample.{$field}", '1', forceCaseInsensitive: false);

        expect([(string) $folded, (string) $exact])->toBe($expected);
    })->with([
        'an integer' => [Tally::class, 'count', ["LOWER(CONCAT(sample.count, '')) LIKE :sylament_search_0", "CONCAT(sample.count, '') LIKE :sylament_search_1"]],
        'a boolean' => [ColumnKindSample::class, 'flagged', ["LOWER(CONCAT(sample.flagged, '')) LIKE :sylament_search_0", "CONCAT(sample.flagged, '') LIKE :sylament_search_1"]],
        'a guid' => [ColumnKindSample::class, 'token', ["LOWER(CONCAT(sample.token, '')) LIKE :sylament_search_0", "CONCAT(sample.token, '') LIKE :sylament_search_1"]],
        'a date and time' => [ColumnKindSample::class, 'loggedAt', ["LOWER(CONCAT(sample.loggedAt, '')) LIKE :sylament_search_0", "CONCAT(sample.loggedAt, '') LIKE :sylament_search_1"]],
        'text' => [ColumnKindSample::class, 'body', ['LOWER(sample.body) LIKE :sylament_search_0', 'sample.body LIKE :sylament_search_1']],
        'a string' => [Product::class, 'name', ['LOWER(sample.name) LIKE :sylament_search_0', 'sample.name LIKE :sylament_search_1']],
    ]);

    it('leaves a field that holds no character data uncast on MySQL and SQLite', function (TestPlatform $platform): void {
        $query = $this->platformEntityManager($platform)->createQueryBuilder()->select('sample')->from(ColumnKindSample::class, 'sample');

        expect((string) SearchConstraint::like($query, 'sample.flagged', '1', forceCaseInsensitive: true))->toBe('LOWER(sample.flagged) LIKE :sylament_search_0')
            ->and((string) SearchConstraint::like($query, 'sample.loggedAt', '1', forceCaseInsensitive: false))->toBe('sample.loggedAt LIKE :sylament_search_1');
    })->with([
        'MySQL' => [TestPlatform::MySql],
        'SQLite' => [TestPlatform::Sqlite],
    ]);

    it('resolves a join alias, an entity join alias and a subselect alias the caller names, and leaves an expression or an undeclared alias as it was', function (): void {
        $query = $this->platformEntityManager(TestPlatform::PostgreSql)->createQueryBuilder()
            ->select('wagon')
            ->from(ConvoyWagon::class, 'wagon')
            ->leftJoin('wagon.convoy', 'convoy')
            ->leftJoin(Tally::class, 'tally', 'WITH', '1 = 1');

        expect([
            (string) SearchConstraint::like($query, 'convoy.id', '1', forceCaseInsensitive: false),
            (string) SearchConstraint::like($query, 'tally.count', '1', forceCaseInsensitive: false),
            (string) SearchConstraint::like($query, 'subselected.count', '1', forceCaseInsensitive: false, class: Tally::class),
            (string) SearchConstraint::like($query, 'subselected.count', '1', forceCaseInsensitive: false),
            (string) SearchConstraint::like($query, 'LENGTH(wagon.cargo)', '1', forceCaseInsensitive: false),
        ])->toBe([
            "CONCAT(convoy.id, '') LIKE :sylament_search_0",
            "CONCAT(tally.count, '') LIKE :sylament_search_1",
            "CONCAT(subselected.count, '') LIKE :sylament_search_2",
            'subselected.count LIKE :sylament_search_3',
            'LENGTH(wagon.cargo) LIKE :sylament_search_4',
        ]);
    });

    it('matches a field that holds no character data by its text on every platform', function (string $class, string $field, string $search): void {
        $tally = new Tally();
        $tally->count = 12;
        $this->seed($tally, new ColumnKindSample(body: 'lorem ipsum', token: 'a1b2c3d4-0000-4000-8000-000000000000', loggedAt: new DateTimeImmutable('2024-05-06 07:08:09')));

        $query = $this->entityManager()->createQueryBuilder()->select('sample')->from($class, 'sample');
        $query->where(SearchConstraint::like($query, "sample.{$field}", $search, forceCaseInsensitive: null));

        expect($query->getQuery()->getResult())->toHaveCount(1);
    })->with([
        'an integer' => [Tally::class, 'count', '1'],
        'a guid' => [ColumnKindSample::class, 'token', 'c3d4'],
        'a date and time' => [ColumnKindSample::class, 'loggedAt', '2024-05-06'],
        'text' => [ColumnKindSample::class, 'body', 'ipsum'],
    ]);

    it('reads a boolean as true or false on PostgreSQL and as 1 or 0 elsewhere', function (): void {
        $this->seed(new ColumnKindSample(flagged: true));

        $matches = function (string $search): int {
            $query = $this->entityManager()->createQueryBuilder()->select('sample')->from(ColumnKindSample::class, 'sample');
            $query->where(SearchConstraint::like($query, 'sample.flagged', $search, forceCaseInsensitive: null));

            return count($query->getQuery()->getResult());
        };

        expect(['true' => $matches('true'), '1' => $matches('1')])->toBe(match (TestPlatform::current()) {
            TestPlatform::PostgreSql => ['true' => 1, '1' => 0],
            TestPlatform::Sqlite, TestPlatform::MySql => ['true' => 0, '1' => 1],
        });
    });
});
