<?php

declare(strict_types=1);

use Sylament\Schemas\Identity\IdentifierConstraint;
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Triplet;
use Sylament\Schemas\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Schemas\Tests\Fixtures\Doctrine\KeyShapeRig;
use Sylament\Testing\Doctrine\RecordingMiddleware;

dataset('key shapes', [
    'three scalar fields' => ['three scalar fields', 'eu~1~c', 'zz~9~zz'],
    'an association in the middle' => ['an association in the middle', 'L1~1~3', 'L1~99~3'],
    'an int-backed enum half' => ['an int-backed enum half', '7~2', '1~99'],
    'ids on a mapped superclass' => ['ids on a mapped superclass', 'acme~x-1', 'acme~zz'],
    'a joined hierarchy under a composite root' => ['a joined hierarchy under a composite root', 'north~7', 'north~99'],
    'a single identifier association' => ['a single identifier association', '1', '99'],
    'smallint and ascii_string' => ['smallint and ascii_string', '5~ab', '9~zz'],
    "symfony's uuid beside an int" => ["symfony's uuid beside an int", KeyShapeRig::UUID . '~1', KeyShapeRig::UUID . '~99'],
    "symfony's ulid alone" => ["symfony's ulid alone", KeyShapeRig::ULID, KeyShapeRig::OTHER_ULID],
]);

it('mints and decodes the key of every supported shape, loaded or rebuilt from its values', function (string $shape, string $key): void {
    $entityManager = EntityManagerFactory::create();
    $class = KeyShapeRig::seed($entityManager, $shape);
    $identifier = RecordKey::toIdentifier($entityManager, $class, $key);

    assert($identifier !== null);

    $record = $entityManager->find($class, $identifier);

    assert($record !== null);

    expect(RecordKey::of($entityManager, $record))->toBe($key)
        ->and(RecordKey::find($entityManager, $record))->toBe($key)
        ->and(RecordKey::fromValues($entityManager, $class, array_values($identifier)))->toBe($key);
})->with('key shapes');

it('matches exactly the record a key names, alone and in a list beside a key naming none', function (string $shape, string $key, string $absent): void {
    $entityManager = EntityManagerFactory::create();
    $class = KeyShapeRig::seed($entityManager, $shape);

    $equals = $entityManager->createQueryBuilder()->select('r')->from($class, 'r');
    $equals->where((string) IdentifierConstraint::equals($equals, 'r', $class, $key, 'k'));

    $in = $entityManager->createQueryBuilder()->select('r')->from($class, 'r');
    $in->where(IdentifierConstraint::in($in, 'r', $class, [$key, $absent], 'ks'));

    expect($equals->getQuery()->getResult())->toHaveCount(1)
        ->and($in->getQuery()->getResult())->toHaveCount(1);
})->with('key shapes');

it('correlates a second alias to the first on every field of every supported shape', function (string $shape, string $key, string $absent): void {
    $entityManager = EntityManagerFactory::create();
    $class = KeyShapeRig::seed($entityManager, $shape);

    $correlated = static function (string $named) use ($entityManager, $class): array {
        $query = $entityManager->createQueryBuilder()->select('o')->from($class, 'o');
        $matching = IdentifierConstraint::equals($query, 'i', $class, $named, 'k');

        assert($matching !== null);

        $query->where(sprintf('EXISTS (SELECT 1 FROM %s i WHERE %s AND %s)', $class, IdentifierConstraint::correlate($query, 'i', $class, 'o'), $matching));

        return array_map(static fn (object $record): string => RecordKey::of($entityManager, $record), $query->getQuery()->getResult());
    };

    expect($correlated($key))->toBe([$key])
        ->and($correlated($absent))->toBe([]);
})->with('key shapes');

it('selects, groups, orders and re-keys the identifier of every supported shape', function (string $shape, string $key): void {
    $entityManager = EntityManagerFactory::create();
    $class = KeyShapeRig::seed($entityManager, $shape);

    $query = $entityManager->createQueryBuilder()->from($class, 'r');
    $selection = IdentifierConstraint::select($query, 'r', $class);
    $query->select($selection->expressions);

    foreach (IdentifierConstraint::groupBy($query, 'r', $class) as $expression) {
        $query->addGroupBy($expression);
    }

    IdentifierConstraint::orderBy($query, 'r', $class, 'ASC');

    expect(array_map($selection->keyOf(...), $query->getQuery()->getScalarResult()))->toBe([$key]);
})->with('key shapes');

it('hands back a reference for the key of every supported shape, loading the record only behind an identifier association', function (string $shape, string $key): void {
    $recorder = new RecordingMiddleware();
    $entityManager = EntityManagerFactory::create($recorder);
    $class = KeyShapeRig::seed($entityManager, $shape);
    $recorder->reset();

    $reference = new AssociationReader($entityManager)->getReference($class, $key);

    $tablesRead = array_map(
        static fn (string $statement): string => preg_match('/^SELECT .+ FROM (\w+) t0 WHERE /', $statement, $matches) === 1 ? $matches[1] : $statement,
        $recorder->statements(),
    );

    expect(RecordKey::of($entityManager, $reference))->toBe($key)
        ->and($tablesRead)->toBe(match ($shape) {
            'an association in the middle' => ['Mooring'],
            'a single identifier association' => ['Pier'],
            default => [],
        });
})->with('key shapes');

it('chunks a three-field key list at 500 keys a statement', function (): void {
    $keys = array_map(static fn (int $index): string => "r{$index}~{$index}~c", range(1, 501));

    expect(array_map(count(...), IdentifierConstraint::chunk(EntityManagerFactory::create(), Triplet::class, $keys)))->toBe([500, 1]);
});
