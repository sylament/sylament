<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Components\Concerns;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Casts\Contracts\StateCast;
use Sylament\Schemas\State\Casts\DateTimeStateCast;
use Sylament\Schemas\State\Casts\EnumStateCast;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Author;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Logbook;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Voyage;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Zone;
use Symfony\Component\Uid\Uuid;

/*
 * A singular relationship reads an object-valued leaf of its related record by
 * the root fill's own rule: the leaf enters the bag exactly when the reading
 * component's state casts flatten it to a non-object, so a date, an enum and a
 * uuid fill, and survive a save that did not touch them, while an association
 * and an object no cast flattens still skip and null-fill.
 */

/**
 * A voyage whose logbook carries a date, an enum, a uuid and a keeper, read back
 * after the identity map is cleared — the shape an edit page binds.
 */
function objectLeafSeedVoyage(EntityManagerInterface $entityManager): Voyage
{
    $keeper = new Author();
    $keeper->setName('Ishmael');

    $logbook = new Logbook();
    $logbook->setCaptain('Ahab');
    $logbook->setOpenedOn(new DateTimeImmutable('2026-03-14'));
    $logbook->setZone(Zone::Footer);
    $logbook->setSeal(Uuid::fromString('0190a6f2-7c1e-7a3b-9c4d-5e6f7a8b9c0d'));
    $logbook->setKeeper($keeper);

    $voyage = new Voyage();
    $voyage->setName('Pequod');
    $voyage->setLogbook($logbook);

    $entityManager->persist($keeper);
    $entityManager->persist($voyage);
    $entityManager->flush();
    $entityManager->clear();

    $reloaded = $entityManager->find(Voyage::class, $voyage->getId());
    assert($reloaded instanceof Voyage);

    return $reloaded;
}

/**
 * @return array{Schema, StateHostFixture}
 */
function objectLeafWire(object $record, Component $component): array
{
    $host = new StateHostFixture();
    $schema = Schema::make($host)->statePath('data')->record($record)->components([$component]);

    $host->cacheSchema('schema', $schema);
    $schema->getComponents();

    return [$schema, $host];
}

function objectLeafSealCast(): StateCast
{
    return new class implements StateCast {
        public function get(mixed $state): ?Uuid
        {
            return is_string($state) && $state !== '' ? Uuid::fromString($state) : null;
        }

        public function set(mixed $state): mixed
        {
            return $state instanceof Uuid ? $state->toRfc4122() : $state;
        }
    };
}

function objectLeafCastLogbook(): Group
{
    return Group::make([
        StateField::make('captain'),
        StateField::make('openedOn')->stateCast(new DateTimeStateCast(
            format: 'Y-m-d',
            internalFormat: 'Y-m-d',
            timezone: date_default_timezone_get(),
            objectClass: DateTimeImmutable::class,
        )),
        StateField::make('zone')->stateCast(new EnumStateCast(Zone::class)),
        StateField::make('seal')->stateCast(objectLeafSealCast()),
    ])->relationship('logbook');
}

/**
 * @return array<string, mixed>
 */
function objectLeafLogbookRow(EntityManagerInterface $entityManager): array
{
    $row = $entityManager->getConnection()->fetchAssociative('SELECT captain, openedOn AS opened_on, zone, seal FROM Logbook');
    assert(is_array($row));

    $seal = is_resource($row['seal']) ? stream_get_contents($row['seal']) : $row['seal'];

    return [...$row, 'seal' => is_string($seal) ? bin2hex($seal) : $seal];
}

it('fills a date, an enum and a uuid off the related record where the field state casts flatten them', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $voyage = objectLeafSeedVoyage($entityManager);

    [$schema, $host] = objectLeafWire($voyage, objectLeafCastLogbook());

    $schema->fill($voyage);

    expect($host->data['logbook'])->toBe([
        'captain' => 'Ahab',
        'openedOn' => '2026-03-14',
        'zone' => 'footer',
        'seal' => '0190a6f2-7c1e-7a3b-9c4d-5e6f7a8b9c0d',
    ]);
});

it('hands a fill hook the related date, enum and uuid as the objects the record holds, and the array fill casts them', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $voyage = objectLeafSeedVoyage($entityManager);

    [$schema, $host] = objectLeafWire($voyage, objectLeafCastLogbook());

    $data = $schema->getStateFromRecord($voyage);

    expect($data['logbook']['openedOn'] ?? null)->toEqual(new DateTimeImmutable('2026-03-14'))
        ->and($data['logbook']['zone'] ?? null)->toBe(Zone::Footer)
        ->and($data['logbook']['seal'] ?? null)->toEqual(Uuid::fromString('0190a6f2-7c1e-7a3b-9c4d-5e6f7a8b9c0d'));

    $schema->fill($data);

    expect($host->data['logbook'])->toBe([
        'captain' => 'Ahab',
        'openedOn' => '2026-03-14',
        'zone' => 'footer',
        'seal' => '0190a6f2-7c1e-7a3b-9c4d-5e6f7a8b9c0d',
    ]);
});

it('saves the untouched date, enum and uuid of the related record back as they were stored', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $voyage = objectLeafSeedVoyage($entityManager);

    [$schema, $host] = objectLeafWire($voyage, objectLeafCastLogbook());

    $schema->fill($voyage);
    $host->data['logbook']['captain'] = 'Starbuck';

    $schema->getData();
    $entityManager->flush();

    expect(objectLeafLogbookRow($entityManager))->toBe([
        'captain' => 'Starbuck',
        'opened_on' => '2026-03-14',
        'zone' => 'footer',
        'seal' => '0190a6f27c1e7a3b9c4d5e6f7a8b9c0d',
    ]);
});

it('reloads the date, enum and uuid through the same casts after a save', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $voyage = objectLeafSeedVoyage($entityManager);

    [$schema, $host] = objectLeafWire($voyage, objectLeafCastLogbook());

    $schema->fill($voyage);
    $host->data['logbook']['captain'] = 'Starbuck';

    $schema->getData();
    $entityManager->flush();
    $schema->loadStateFromRelationships();

    expect($host->data['logbook'])->toBe([
        'captain' => 'Starbuck',
        'openedOn' => '2026-03-14',
        'zone' => 'footer',
        'seal' => '0190a6f2-7c1e-7a3b-9c4d-5e6f7a8b9c0d',
    ]);
});

it('still skips an association and an object leaf its state casts leave an object', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $voyage = objectLeafSeedVoyage($entityManager);

    [$schema, $host] = objectLeafWire($voyage, Group::make([
        StateField::make('captain'),
        StateField::make('openedOn'),
        StateField::make('seal')->stateCast(new EnumStateCast(Zone::class)),
        StateField::make('keeper'),
    ])->relationship('logbook'));

    $schema->fill($voyage);

    expect($host->data['logbook'])->toBe([
        'captain' => 'Ahab',
        'openedOn' => null,
        'seal' => null,
        'keeper' => null,
    ]);
});
