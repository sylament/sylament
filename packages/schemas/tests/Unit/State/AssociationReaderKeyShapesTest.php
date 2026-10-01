<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Dock;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Tier;
use Sylament\Schemas\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Schemas\Tests\Fixtures\Doctrine\KeyShapeRig;
use Symfony\Component\Uid\Ulid;

it('refuses a new record whose key of every supported shape is taken, under every field the user types', function (string $shape, array $fields): void {
    $entityManager = EntityManagerFactory::create();
    KeyShapeRig::seed($entityManager, $shape);

    $record = KeyShapeRig::build($entityManager, $shape);

    expect(new AssociationReader($entityManager)->findTakenIdentifiers($record))->toBe(array_fill_keys($fields, 'This value is already used.'));
})->with([
    'three scalar fields' => ['three scalar fields', ['region', 'slot', 'code']],
    'an association in the middle' => ['an association in the middle', ['lane', 'seq']],
    'an int-backed enum half' => ['an int-backed enum half', ['tier', 'slot']],
    'ids on a mapped superclass' => ['ids on a mapped superclass', ['tenant', 'code']],
    'a joined hierarchy under a composite root' => ['a joined hierarchy under a composite root', ['fleet', 'number']],
    'a single identifier association' => ['a single identifier association', ['dock']],
    'smallint and ascii_string' => ['smallint and ascii_string', ['a', 'b']],
    "symfony's uuid beside an int" => ["symfony's uuid beside an int", ['batch', 'seq']],
    "symfony's ulid alone" => ["symfony's ulid alone", ['id']],
]);

it('puts back a changed key of every supported shape and names the field', function (string $shape, string $key, string $field, Closure $change, Closure $read, mixed $stored): void {
    $entityManager = EntityManagerFactory::create();
    $class = KeyShapeRig::seed($entityManager, $shape);
    $identifier = RecordKey::toIdentifier($entityManager, $class, $key);

    assert($identifier !== null);

    $record = $entityManager->find($class, $identifier);

    assert($record !== null);

    $change($record, $entityManager);

    expect(new AssociationReader($entityManager)->revertIdentifierChanges($record))->toBe([$field => 'This value cannot be changed.'])
        ->and($read($record))->toEqual($stored);
})->with([
    'three scalar fields' => ['three scalar fields', 'eu~1~c', 'slot', fn ($r) => $r->setSlot(2), fn ($r) => $r->getSlot(), 1],
    'an association in the middle' => ['an association in the middle', 'L1~1~3', 'dock', fn ($r, EntityManager $em) => $r->setDock(keyShapeSecondDock($em)), fn ($r) => $r->getDock()->getId(), 1],
    'an int-backed enum half' => ['an int-backed enum half', '7~2', 'tier', fn ($r) => $r->setTier(Tier::Low), fn ($r) => $r->getTier(), Tier::High],
    'ids on a mapped superclass' => ['ids on a mapped superclass', 'acme~x-1', 'code', fn ($r) => $r->setCode('x-2'), fn ($r) => $r->getCode(), 'x-1'],
    'a joined hierarchy under a composite root' => ['a joined hierarchy under a composite root', 'north~7', 'number', fn ($r) => $r->setNumber(8), fn ($r) => $r->getNumber(), 7],
    'a single identifier association' => ['a single identifier association', '1', 'dock', fn ($r, EntityManager $em) => $r->setDock(keyShapeSecondDock($em)), fn ($r) => $r->getDock()->getId(), 1],
    'smallint and ascii_string' => ['smallint and ascii_string', '5~ab', 'b', fn ($r) => $r->setB('cd'), fn ($r) => $r->getB(), 'ab'],
    "symfony's uuid beside an int" => ["symfony's uuid beside an int", KeyShapeRig::UUID . '~1', 'seq', fn ($r) => $r->setSeq(2), fn ($r) => $r->getSeq(), 1],
    "symfony's ulid alone" => ["symfony's ulid alone", KeyShapeRig::ULID, 'id', fn ($r) => $r->setId(Ulid::fromString(KeyShapeRig::OTHER_ULID)), fn ($r): string => (string) $r->getId(), KeyShapeRig::ULID],
]);

function keyShapeSecondDock(EntityManager $entityManager): Dock
{
    $dock = new Dock();
    $dock->setName('South');

    $entityManager->persist($dock);
    $entityManager->flush();

    return $dock;
}
