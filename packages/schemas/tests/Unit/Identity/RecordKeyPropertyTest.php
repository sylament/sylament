<?php

declare(strict_types=1);

use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Types\Type;
use Sylament\Schemas\Identity\IdentifierConstraint;
use Sylament\Schemas\Identity\IdentifierFields;
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Account;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Bay;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Beacon;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Chain;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\CompositePair;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Consignment;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Crate;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\CrateSeal;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Divider;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Enrolment;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Flagged;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Headband;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Listened;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Listing;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\ListingNote;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\ListingSeal;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Microcode;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Mooring;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Pallet;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Parcel;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Pennant;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Pier;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Placement;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PlinthCap;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostSeal;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostSealNote;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostTranslation;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Receipt;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Redirect;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Ribbon;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Sheet;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Shortlink;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Sigil;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Stamped;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Tariff;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Ticket;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Triplet;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Truck;
use Sylament\Schemas\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Schemas\Tests\Fixtures\Identity\KeyTuples;
use Sylament\Testing\Doctrine\TestPlatform;

/**
 * Every fixture class whose identifier the codec keys. The cipher classes are left
 * out: CipherType refuses its own printed form on write as well as on read, the
 * residual record-key.md records, so no generated tuple of theirs round-trips.
 *
 * @return list<class-string>
 */
function propertyClasses(): array
{
    return [
        CompositePair::class, Redirect::class, Consignment::class, Sigil::class,
        Listened::class, Stamped::class, Placement::class, Ticket::class,
        Pallet::class, ListingNote::class, PostTranslation::class, Chain::class,
        PostSealNote::class, Pennant::class, Enrolment::class, Ribbon::class,
        Sheet::class, Post::class, Shortlink::class, Tariff::class,
        Flagged::class, Beacon::class, Crate::class, Listing::class,
        Divider::class, PostSeal::class, Headband::class, CrateSeal::class,
        ListingSeal::class, PlinthCap::class,
        Triplet::class, Mooring::class, Bay::class, Account::class,
        Truck::class, Pier::class, Microcode::class, Parcel::class, Receipt::class,
    ];
}

function propertySeed(): int
{
    return (int) (getenv('SYLAMENT_PROPERTY_SEED') ?: 20260928);
}

function propertyIterations(): int
{
    return (int) (getenv('SYLAMENT_PROPERTY_ITERATIONS') ?: 50);
}

/**
 * Whether a decoded identifier holds an integer wider than its column on
 * PostgreSQL, which refuses to bind it with its range error (SQLSTATE 22003)
 * where SQLite and MySQL answer no row: the residual record-key.md records as
 * "An integer beyond the column's width still raises the platform's range error".
 *
 * @param array<string, mixed> $decoded
 */
function isWiderThanItsPostgreSqlColumn(IdentifierFields $fields, array $decoded): bool
{
    $ranges = ['smallint' => [-32768, 32767], 'integer' => [-2147483648, 2147483647]];

    foreach (array_values($decoded) as $index => $value) {
        $range = $ranges[$fields->types[$index]] ?? null;

        if ($range !== null && is_int($value) && ($value < $range[0] || $value > $range[1])) {
            return true;
        }
    }

    return false;
}

/**
 * Whether a tuple holds a guid component that is not a UUID, which a PostgreSQL
 * uuid column cannot hold, so no row or loaded record there mints its key: the
 * decode shape-checks guid only where the platform declares a native guid type,
 * as record-key.md records under "The grammar".
 *
 * @param list<mixed> $php
 */
function holdsANonUuidGuid(IdentifierFields $fields, array $php): bool
{
    return array_any($php, fn ($value, $index): bool => $fields->types[$index] === 'guid' && (!is_string($value) || preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD', $value) !== 1));
}

it('round-trips every generated identifier through its key, and mints from a record the key its row binds', function (): void {
    $entityManager = EntityManagerFactory::create();
    $platform = $entityManager->getConnection()->getDatabasePlatform();
    $tuples = new KeyTuples(propertySeed());
    $failures = [];

    foreach (propertyClasses() as $class) {
        $fields = IdentifierFields::of($entityManager, $class);

        for ($n = 0; $n < propertyIterations(); $n++) {
            [$raw, $php] = $tuples->identifier($entityManager, $class);

            if (TestPlatform::current() === TestPlatform::PostgreSql && holdsANonUuidGuid($fields, $php)) {
                continue;
            }

            try {
                $key = RecordKey::fromValues($entityManager, $class, $php);
            } catch (LogicException) {
                // An unprintable tuple is refused by name, which is the contract.
                continue;
            }

            $decoded = RecordKey::toIdentifier($entityManager, $class, $key);

            if ($decoded === null
                || array_map(IdentifierFields::printable(...), array_values($decoded)) !== array_map(IdentifierFields::printable(...), $php)
                || RecordKey::fromValues($entityManager, $class, array_values($decoded)) !== $key) {
                $failures[] = sprintf('%s: key %s decodes to %s', $class, KeyTuples::show($key), KeyTuples::show($decoded));

                continue;
            }

            $minted = RecordKey::of($entityManager, $tuples->record($entityManager, $class, $raw));
            $written = array_map(
                static fn (mixed $value, string $type): mixed => Type::getType($type)->convertToDatabaseValue($value instanceof BackedEnum ? $value->value : $value, $platform),
                $raw,
                $fields->types,
            );

            if ($fields->toDatabaseValues($entityManager, $minted) !== $written) {
                $failures[] = sprintf('%s: a record holding %s mints %s, which binds another row', $class, KeyTuples::show($raw), KeyTuples::show($minted));
            }
        }
    }

    expect($failures)->toBe([], 'seed ' . propertySeed());
});

it('decodes only canonical spellings: every misspelling is refused, or is the canonical key of another tuple', function (): void {
    $entityManager = EntityManagerFactory::create();
    $tuples = new KeyTuples(propertySeed());
    $failures = [];

    foreach (propertyClasses() as $class) {
        for ($n = 0; $n < max(1, intdiv(propertyIterations(), 5)); $n++) {
            try {
                $key = RecordKey::fromValues($entityManager, $class, $tuples->identifier($entityManager, $class)[1]);
            } catch (LogicException) {
                // An unprintable tuple is refused by name, which is the contract.
                continue;
            }

            foreach ($tuples->mutants($key) as $mutator => $mutant) {
                $decoded = RecordKey::toIdentifier($entityManager, $class, $mutant);
                $query = $entityManager->createQueryBuilder()->select('r')->from($class, 'r');

                if ((IdentifierConstraint::equals($query, 'r', $class, $mutant, 'k') === null) !== ($decoded === null)) {
                    $failures[] = sprintf('%s: %s — equals() and the decode disagree on %s', $class, $mutator, KeyTuples::show($mutant));
                }

                if ($decoded !== null && RecordKey::fromValues($entityManager, $class, array_values($decoded)) !== $mutant) {
                    $failures[] = sprintf('%s: %s — %s decodes but is not canonical', $class, $mutator, KeyTuples::show($mutant));
                }
            }
        }
    }

    expect($failures)->toBe([], 'seed ' . propertySeed());
});

it('keys a single identifier by its printed value, verbatim', function (): void {
    $entityManager = EntityManagerFactory::create();
    $tuples = new KeyTuples(propertySeed());
    $failures = [];

    foreach (propertyClasses() as $class) {
        if (count(IdentifierFields::of($entityManager, $class)->names) !== 1) {
            continue;
        }

        for ($n = 0; $n < propertyIterations(); $n++) {
            $php = $tuples->identifier($entityManager, $class)[1];

            try {
                $key = RecordKey::fromValues($entityManager, $class, $php);
            } catch (LogicException) {
                // An unprintable tuple is refused by name, which is the contract.
                continue;
            }

            if ($key !== IdentifierFields::printable($php[0])) {
                $failures[] = sprintf('%s: %s keys as %s', $class, KeyTuples::show($php[0]), KeyTuples::show($key));
            }
        }
    }

    expect($failures)->toBe([], 'seed ' . propertySeed());
});

it('answers any client string without throwing: a decode or null, a predicate or none, a row or none', function (): void {
    $entityManager = EntityManagerFactory::create();
    $tuples = new KeyTuples(propertySeed());
    $failures = [];

    foreach (propertyClasses() as $class) {
        $fields = IdentifierFields::of($entityManager, $class);

        for ($n = 0; $n < propertyIterations(); $n++) {
            $input = $tuples->garbage();

            try {
                $decoded = RecordKey::toIdentifier($entityManager, $class, $input);
                IdentifierConstraint::equals($entityManager->createQueryBuilder()->select('r')->from($class, 'r'), 'r', $class, $input, 'k');
                IdentifierConstraint::notEquals($entityManager->createQueryBuilder()->select('r')->from($class, 'r'), 'r', $class, $input, 'k');
                IdentifierConstraint::in($entityManager->createQueryBuilder()->select('r')->from($class, 'r'), 'r', $class, [$input, $input . '~'], 'k');
                IdentifierConstraint::notIn($entityManager->createQueryBuilder()->select('r')->from($class, 'r'), 'r', $class, [$input], 'k');

                if ($decoded !== null && (TestPlatform::current() !== TestPlatform::PostgreSql || !isWiderThanItsPostgreSqlColumn($fields, $decoded))) {
                    $entityManager->find($class, $decoded);
                    $entityManager->clear();

                    $query = $entityManager->createQueryBuilder()->select('r')->from($class, 'r');
                    $query->andWhere((string) IdentifierConstraint::equals($query, 'r', $class, $input, 'k'))->getQuery()->getResult();
                }
            } catch (Throwable $exception) {
                $failures[] = sprintf('%s: %s threw %s: %s', $class, KeyTuples::show($input), $exception::class, $exception->getMessage());
            }
        }
    }

    expect($failures)->toBe([], 'seed ' . propertySeed());
});

it('names no row by an integer wider than its column on SQLite and MySQL, and raises the range error on PostgreSQL', function (): void {
    $entityManager = EntityManagerFactory::create();
    $identifier = RecordKey::toIdentifier($entityManager, Post::class, '9999999999');

    expect($identifier)->toBe(['id' => 9999999999]);

    match (TestPlatform::current()) {
        TestPlatform::Sqlite, TestPlatform::MySql => expect($entityManager->find(Post::class, $identifier))->toBeNull(),
        TestPlatform::PostgreSql => expect(fn (): ?object => $entityManager->find(Post::class, $identifier))->toThrow(DriverException::class, 'SQLSTATE[22003]'),
    };
});

it('decodes a guid key that is not a UUID on SQLite and MySQL, and refuses it on PostgreSQL', function (): void {
    $identifier = RecordKey::toIdentifier(EntityManagerFactory::create(), Beacon::class, 'not-a-uuid');

    match (TestPlatform::current()) {
        TestPlatform::Sqlite, TestPlatform::MySql => expect($identifier)->toBe(['id' => 'not-a-uuid']),
        TestPlatform::PostgreSql => expect($identifier)->toBeNull(),
    };
});
