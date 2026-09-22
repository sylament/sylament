<?php

declare(strict_types=1);

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Sylament\Schemas\Identity\IdentifierConstraint;
use Sylament\Schemas\Tests\Fixtures\Doctrine\CompositeOwnerRig;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Consignment;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Crate;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Ledger;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\LedgerDraft;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Listing;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Pallet;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostSeal;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostTranslation;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Redirect;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Sigil;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\SigilChild;
use Sylament\Schemas\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Schemas\Tests\Fixtures\Doctrine\IdentityRig;

/*
 * `inColumns()` is `in()` spelled in SQL for a statement with no
 * identification variable: the class's identifier columns — not its fields —
 * bound on a DBAL builder. So every case asserts the text it emits, as a
 * literal, and the rows that text selects when it is the WHERE of a real
 * statement.
 */

/**
 * The rows a DBAL `SELECT` of one column answers under the predicate, sorted.
 *
 * @return list<mixed>
 */
function sqlTwinRows(QueryBuilder $query, string $column, string $table, string $predicate): array
{
    $rows = $query->select($column)->from($table)->where($predicate)->executeQuery()->fetchFirstColumn();

    sort($rows);

    return $rows;
}

function sqlTwinQuery(EntityManager $entityManager): QueryBuilder
{
    return $entityManager->getConnection()->createQueryBuilder();
}

it('matches a single int identifier as one typed IN list', function (): void {
    $entityManager = IdentityRig::create();
    $query = sqlTwinQuery($entityManager);

    $predicate = IdentifierConstraint::inColumns($entityManager, $query, Post::class, ['1', '3', 'abc', '3'], 'sylament_selected_record_keys');

    expect($predicate)->toBe('id IN (:sylament_selected_record_keys)')
        ->and($query->getParameters())->toBe(['sylament_selected_record_keys' => [1, 3]])
        ->and($query->getParameterTypes())->toBe(['sylament_selected_record_keys' => ArrayParameterType::INTEGER])
        ->and(sqlTwinRows($query, 'title', 'Post', $predicate))->toBe(['Seven'])
        ->and($query->getSQL())->toBe('SELECT title FROM Post WHERE id IN (:sylament_selected_record_keys)');
});

it('matches a scalar pair as a disjunction of its two columns', function (): void {
    $entityManager = IdentityRig::create();
    $query = sqlTwinQuery($entityManager);

    $predicate = IdentifierConstraint::inColumns($entityManager, $query, Redirect::class, ['plain~about', 'www=2Eexample=2Ecom~=2Fblog=2F2026=2Fbundles=20all=20the=20way=20down'], 'keys');

    expect($predicate)->toBe('((host = :keys_0_0 AND path = :keys_0_1) OR (host = :keys_1_0 AND path = :keys_1_1))')
        ->and($query->getParameters())->toBe(['keys_0_0' => 'plain', 'keys_0_1' => 'about', 'keys_1_0' => 'www.example.com', 'keys_1_1' => '/blog/2026/bundles all the way down'])
        ->and($query->getParameterTypes())->toBe(['keys_0_0' => ParameterType::STRING, 'keys_0_1' => ParameterType::STRING, 'keys_1_0' => ParameterType::STRING, 'keys_1_1' => ParameterType::STRING])
        ->and(sqlTwinRows($query, 'label', 'Redirect', $predicate))->toBe(['hostile', 'plain']);
});

it('names a derived identity by its join column, not its field', function (): void {
    $entityManager = IdentityRig::create();
    $query = sqlTwinQuery($entityManager);

    $predicate = IdentifierConstraint::inColumns($entityManager, $query, PostTranslation::class, ['1~en', '1~en=2FGB'], 'keys');

    expect($predicate)->toBe('((post_id = :keys_0_0 AND locale = :keys_0_1) OR (post_id = :keys_1_0 AND locale = :keys_1_1))')
        ->and($query->getParameterTypes())->toBe(['keys_0_0' => ParameterType::INTEGER, 'keys_0_1' => ParameterType::STRING, 'keys_1_0' => ParameterType::INTEGER, 'keys_1_1' => ParameterType::STRING])
        ->and(sqlTwinRows($query, 'title', 'PostTranslation', $predicate))->toBe(['en', 'en/GB']);
});

it('names a single derived identity by its one join column', function (): void {
    $entityManager = IdentityRig::create();
    $query = sqlTwinQuery($entityManager);

    $predicate = IdentifierConstraint::inColumns($entityManager, $query, PostSeal::class, ['1', '2'], 'keys');

    expect($predicate)->toBe('post_id IN (:keys)')
        ->and($query->getParameters())->toBe(['keys' => [1, 2]])
        ->and($query->getParameterTypes())->toBe(['keys' => ArrayParameterType::INTEGER])
        ->and(sqlTwinRows($query, 'note', 'PostSeal', $predicate))->toBe(['sealed']);
});

it('binds a converting identifier as the value its column holds, dropping a spelling that does not print back', function (): void {
    $entityManager = IdentityRig::create();
    $query = sqlTwinQuery($entityManager);

    $predicate = IdentifierConstraint::inColumns($entityManager, $query, Listing::class, ['AB-123', 'ab-123', 'not-a-sku'], 'keys');

    expect($predicate)->toBe('sku IN (:keys)')
        ->and($query->getParameters())->toBe(['keys' => ['ab-123']])
        ->and($query->getParameterTypes())->toBe(['keys' => ArrayParameterType::STRING])
        ->and(sqlTwinRows($query, 'title', 'Listing', $predicate))->toBe(['Listed']);
});

it('names a single-table subclass by the shared columns and carries no discriminator', function (): void {
    $entityManager = EntityManagerFactory::create();
    $sigils = [];

    foreach ([[new SigilChild(), 'north', 'one'], [new SigilChild(), 'north', 'two'], [new Sigil(), 'south', 'one']] as [$sigil, $realm, $mark]) {
        $sigil->setRealm($realm);
        $sigil->setMark($mark);
        $sigil->setLabel("{$realm}/{$mark}");
        $sigils[] = $sigil;
    }

    EntityManagerFactory::seed($entityManager, ...$sigils);

    $query = sqlTwinQuery($entityManager);

    // `south~one` is a plain sigil, not a child: the SQL names no `kind`, so
    // it is selected too — a caller narrows by keys a discriminated query
    // already produced.
    $predicate = IdentifierConstraint::inColumns($entityManager, $query, SigilChild::class, ['north~two', 'south~one'], 'keys');

    expect($predicate)->toBe('((realm = :keys_0_0 AND mark = :keys_0_1) OR (realm = :keys_1_0 AND mark = :keys_1_1))')
        ->and(sqlTwinRows($query, 'label', 'Sigil', $predicate))->toBe(['north/two', 'south/one']);
});

it('answers 1 = 0 and binds nothing for an empty or an all-malformed list, which a write then leaves alone', function (): void {
    $entityManager = IdentityRig::create();
    $empty = sqlTwinQuery($entityManager);
    $malformed = sqlTwinQuery($entityManager);

    $predicate = IdentifierConstraint::inColumns($entityManager, $malformed, PostTranslation::class, ['junk', '1', '1~en~x'], 'keys');

    expect(IdentifierConstraint::inColumns($entityManager, $empty, PostTranslation::class, [], 'keys'))->toBe('1 = 0')
        ->and($empty->getParameters())->toBe([])
        ->and($predicate)->toBe('1 = 0')
        ->and($malformed->getParameters())->toBe([])
        ->and($malformed->update('PostTranslation')->set('title', "'overwritten'")->where($predicate)->executeStatement())->toBe(0)
        ->and(sqlTwinRows(sqlTwinQuery($entityManager), 'title', 'PostTranslation', '1 = 1'))->toBe(['en', 'en/GB', 'pt-BR']);
});

it('binds a repeated key once', function (): void {
    $entityManager = IdentityRig::create();
    $query = sqlTwinQuery($entityManager);

    $predicate = IdentifierConstraint::inColumns($entityManager, $query, PostTranslation::class, ['1~en', '1~en'], 'keys');

    expect($predicate)->toBe('(post_id = :keys_0_0 AND locale = :keys_0_1)')
        ->and($query->getParameters())->toBe(['keys_0_0' => 1, 'keys_0_1' => 'en']);
});

it('refuses a parameter base that already ends in an index', function (): void {
    $entityManager = IdentityRig::create();

    expect(fn (): string => IdentifierConstraint::inColumns($entityManager, sqlTwinQuery($entityManager), PostTranslation::class, ['1~en'], 'keys_1'))
        ->toThrow(LogicException::class, 'ends in an index of its own');
});

it('balances many composite keys, so SQLite takes more than a flat disjunction could carry', function (): void {
    $entityManager = IdentityRig::create();
    $three = sqlTwinQuery($entityManager);
    $many = sqlTwinQuery($entityManager);
    $keys = ['1~pt-BR'];

    for ($index = 0; $index < 1500; $index++) {
        $keys[] = "{$index}~absent";
    }

    expect(IdentifierConstraint::inColumns($entityManager, $three, PostTranslation::class, ['1~en', '1~pt-BR', '1~en=2FGB'], 'keys'))
        ->toBe('((post_id = :keys_0_0 AND locale = :keys_0_1) OR ((post_id = :keys_1_0 AND locale = :keys_1_1) OR (post_id = :keys_2_0 AND locale = :keys_2_1)))')
        ->and(sqlTwinRows($many, 'title', 'PostTranslation', IdentifierConstraint::inColumns($entityManager, $many, PostTranslation::class, $keys, 'keys')))->toBe(['pt-BR']);
});

it('names a joined subclass by the column its root declares, which its own table keys by too', function (): void {
    $entityManager = EntityManagerFactory::create();

    $plain = new Ledger();
    $plain->setLabel('plain');

    $first = new LedgerDraft();
    $first->setLabel('first');
    $first->setRevision('r1');

    $second = new LedgerDraft();
    $second->setLabel('second');
    $second->setRevision('r2');

    EntityManagerFactory::seed($entityManager, $plain, $first, $second);

    $draft = sqlTwinQuery($entityManager);
    $root = sqlTwinQuery($entityManager);

    $predicate = IdentifierConstraint::inColumns($entityManager, $draft, LedgerDraft::class, ['1', '3', 'x'], 'keys');

    expect($predicate)->toBe('ledger_id IN (:keys)')
        ->and($draft->getParameters())->toBe(['keys' => [1, 3]])
        ->and($draft->getParameterTypes())->toBe(['keys' => ArrayParameterType::INTEGER])
        ->and(sqlTwinRows($draft, 'revision', 'LedgerDraft', $predicate))->toBe(['r2'])
        ->and(sqlTwinRows($root, 'label', 'Ledger', IdentifierConstraint::inColumns($entityManager, $root, LedgerDraft::class, ['1', '3'], 'keys')))->toBe(['plain', 'second']);
});

it('quotes every identifier column the mapping quotes', function (): void {
    $entityManager = EntityManagerFactory::create();
    $consignments = [];

    foreach ([['north', 'acme', 'mine'], ['north', 'zenith', 'shares where'], ['south', 'acme', 'shares having']] as [$origin, $holder, $label]) {
        $consignment = new Consignment();
        $consignment->setOrigin($origin);
        $consignment->setHolder($holder);
        $consignment->setLabel($label);
        $consignments[] = $consignment;
    }

    EntityManagerFactory::seed($entityManager, ...$consignments);

    $query = sqlTwinQuery($entityManager);

    $predicate = IdentifierConstraint::inColumns($entityManager, $query, Consignment::class, ['north~acme', 'south~zenith'], 'keys');

    expect($predicate)->toBe('(("where" = :keys_0_0 AND "having" = :keys_0_1) OR ("where" = :keys_1_0 AND "having" = :keys_1_1))')
        ->and($query->getParameters())->toBe(['keys_0_0' => 'north', 'keys_0_1' => 'acme', 'keys_1_0' => 'south', 'keys_1_1' => 'zenith'])
        ->and(sqlTwinRows($query, 'label', '"order"', $predicate))->toBe(['mine']);
});

it('binds a raw-bytes uuid as the sixteen bytes its column holds, dropping a spelling that does not print back', function (): void {
    $entityManager = CompositeOwnerRig::create();
    $query = sqlTwinQuery($entityManager);
    $asText = sqlTwinQuery($entityManager);

    $predicate = IdentifierConstraint::inColumns($entityManager, $query, Crate::class, ['a1b2c3d4-0000-4000-8000-000000000001', 'a1b2c3d4-0000-4000-8000-000000000002', 'a1b2c3d4-0000-4000-8000-000000000003', 'A1B2C3D4-0000-4000-8000-000000000001', 'not-a-uuid'], 'keys');

    // The same bytes bound as text: SQLite never finds a BLOB equal to TEXT.
    $asText->setParameter('keys', $query->getParameter('keys'), ArrayParameterType::STRING);

    expect($predicate)->toBe('id IN (:keys)')
        ->and($query->getParameters())->toBe(['keys' => [hex2bin('a1b2c3d4000040008000000000000001'), hex2bin('a1b2c3d4000040008000000000000002'), hex2bin('a1b2c3d4000040008000000000000003')]])
        ->and($query->getParameterTypes())->toBe(['keys' => ArrayParameterType::BINARY])
        ->and(sqlTwinRows($query, 'label', 'Crate', $predicate))->toBe(['apart', 'mine'])
        ->and(sqlTwinRows($asText, 'label', 'Crate', $predicate))->toBe([]);
});

it('binds a raw-bytes half and a string half each under its own type', function (): void {
    $entityManager = CompositeOwnerRig::create();
    $query = sqlTwinQuery($entityManager);

    $predicate = IdentifierConstraint::inColumns($entityManager, $query, Pallet::class, ['b1b2c3d4-0000-4000-8000-000000000001~north', 'b1b2c3d4-0000-4000-8000-000000000002~north', 'B1B2C3D4-0000-4000-8000-000000000001~south'], 'keys');

    expect($predicate)->toBe('((batch = :keys_0_0 AND lane = :keys_0_1) OR (batch = :keys_1_0 AND lane = :keys_1_1))')
        ->and($query->getParameters())->toBe(['keys_0_0' => hex2bin('b1b2c3d4000040008000000000000001'), 'keys_0_1' => 'north', 'keys_1_0' => hex2bin('b1b2c3d4000040008000000000000002'), 'keys_1_1' => 'north'])
        ->and($query->getParameterTypes())->toBe(['keys_0_0' => ParameterType::BINARY, 'keys_0_1' => ParameterType::STRING, 'keys_1_0' => ParameterType::BINARY, 'keys_1_1' => ParameterType::STRING])
        ->and(sqlTwinRows($query, 'label', 'Pallet', $predicate))->toBe(['b1b2c3d4-0000-4000-8000-000000000001/north', 'b1b2c3d4-0000-4000-8000-000000000002/north']);
});
