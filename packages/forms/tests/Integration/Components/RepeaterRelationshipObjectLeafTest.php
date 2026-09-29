<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Sylament\Forms\Components\DateTimePicker;
use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Berth;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Crossing;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Deck;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Ferry;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Schema;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/*
 * A relationship repeater item and a singular relationship read an
 * object-valued leaf of their related record by the root fill's own rule: the
 * leaf enters the bag exactly when the field's state casts flatten it, so a
 * date picker and an enum select fill from the stored value and a save that did
 * not touch them writes it back, where both used to fill empty and write NULL.
 */

/**
 * A ferry with two crossings and a berth, read back after the identity map is
 * cleared — the shape an edit page binds.
 */
function repeaterObjectLeafSeedFerry(EntityManagerInterface $entityManager): Ferry
{
    $ferry = new Ferry();
    $ferry->setName('Pride of Kent');

    foreach ([['Dover', '2026-05-01', Deck::Upper], ['Calais', '2026-05-02', Deck::Lower]] as [$route, $departsOn, $deck]) {
        $crossing = new Crossing();
        $crossing->setRoute($route);
        $crossing->setDepartsOn(new DateTimeImmutable($departsOn));
        $crossing->setDeck($deck);

        $ferry->addCrossing($crossing);
    }

    $berth = new Berth();
    $berth->setPier('East');
    $berth->setAssignedOn(new DateTimeImmutable('2026-04-30'));

    $ferry->setBerth($berth);

    $entityManager->persist($ferry);
    $entityManager->flush();
    $entityManager->clear();

    $reloaded = $entityManager->find(Ferry::class, $ferry->getId());
    assert($reloaded instanceof Ferry);

    return $reloaded;
}

/**
 * @return array{Schema, FieldRenderHost}
 */
function repeaterObjectLeafWire(object $record, Component $component): array
{
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator(test()->getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->record($record)->components([$component]);

    $host->cacheSchema('schema', $schema);
    $schema->getComponents();

    return [$schema, $host];
}

function repeaterObjectLeafCrossings(): Repeater
{
    return Repeater::make('crossings')->relationship()->components([
        TextInput::make('route'),
        DateTimePicker::make('departsOn')->time(false),
        Select::make('deck')->options(Deck::class),
    ]);
}

/**
 * @return list<array<string, mixed>>
 */
function repeaterObjectLeafCrossingRows(EntityManagerInterface $entityManager): array
{
    return $entityManager->getConnection()->fetchAllAssociative('SELECT route, departsOn AS departs_on, deck FROM Crossing ORDER BY id');
}

it('fills a date picker and an enum select inside a relationship item from the stored child', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $ferry = repeaterObjectLeafSeedFerry($entityManager);

    [$schema, $host] = repeaterObjectLeafWire($ferry, repeaterObjectLeafCrossings());

    $schema->fill($ferry);

    expect($host->data['crossings'])->toBe([
        'record-1' => ['route' => 'Dover', 'departsOn' => '2026-05-01', 'deck' => 'upper'],
        'record-2' => ['route' => 'Calais', 'departsOn' => '2026-05-02', 'deck' => 'lower'],
    ]);
});

it('hands a fill hook the item date and enum as the objects the record holds, and the array fill casts them', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $ferry = repeaterObjectLeafSeedFerry($entityManager);

    [$schema, $host] = repeaterObjectLeafWire($ferry, repeaterObjectLeafCrossings());

    $data = $schema->getStateFromRecord($ferry);

    expect($data['crossings']['record-1']['departsOn'] ?? null)->toEqual(new DateTimeImmutable('2026-05-01'))
        ->and($data['crossings']['record-1']['deck'] ?? null)->toBe(Deck::Upper);

    $schema->fill($data);

    expect($host->data['crossings']['record-1'])->toBe(['route' => 'Dover', 'departsOn' => '2026-05-01', 'deck' => 'upper']);
});

it('saves the untouched date and enum of every relationship item back as they were stored', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $ferry = repeaterObjectLeafSeedFerry($entityManager);

    [$schema, $host] = repeaterObjectLeafWire($ferry, repeaterObjectLeafCrossings());

    $schema->fill($ferry);
    $host->data['crossings']['record-1']['route'] = 'Dover Eastern Docks';

    $schema->getData();
    $entityManager->flush();

    expect(repeaterObjectLeafCrossingRows($entityManager))->toBe([
        ['route' => 'Dover Eastern Docks', 'departs_on' => '2026-05-01', 'deck' => 'upper'],
        ['route' => 'Calais', 'departs_on' => '2026-05-02', 'deck' => 'lower'],
    ]);
});

it('reloads every item after a save, a new one included, with its date and enum as strings', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $ferry = repeaterObjectLeafSeedFerry($entityManager);

    [$schema, $host] = repeaterObjectLeafWire($ferry, repeaterObjectLeafCrossings());

    $schema->fill($ferry);
    $host->data['crossings']['new-a'] = ['route' => 'Dunkirk', 'departsOn' => '2026-05-03', 'deck' => 'upper'];

    $schema->getData();
    $entityManager->flush();
    $schema->loadStateFromRelationships();

    expect($host->data['crossings'])->toBe([
        'record-1' => ['route' => 'Dover', 'departsOn' => '2026-05-01', 'deck' => 'upper'],
        'record-2' => ['route' => 'Calais', 'departsOn' => '2026-05-02', 'deck' => 'lower'],
        'record-3' => ['route' => 'Dunkirk', 'departsOn' => '2026-05-03', 'deck' => 'upper'],
    ])->and(repeaterObjectLeafCrossingRows($entityManager))->toBe([
        ['route' => 'Dover', 'departs_on' => '2026-05-01', 'deck' => 'upper'],
        ['route' => 'Calais', 'departs_on' => '2026-05-02', 'deck' => 'lower'],
        ['route' => 'Dunkirk', 'departs_on' => '2026-05-03', 'deck' => 'upper'],
    ]);
});

it('still skips an object leaf inside an item that no state cast flattens, an association included', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $ferry = repeaterObjectLeafSeedFerry($entityManager);

    [$schema, $host] = repeaterObjectLeafWire($ferry, Repeater::make('crossings')->relationship()->components([
        TextInput::make('route'),
        TextInput::make('departsOn'),
        Select::make('ferry')->options(Deck::class),
    ]));

    $schema->fill($ferry);

    expect($host->data['crossings'])->toBe([
        'record-1' => ['route' => 'Dover', 'departsOn' => null, 'ferry' => null],
        'record-2' => ['route' => 'Calais', 'departsOn' => null, 'ferry' => null],
    ]);
});

it('fills a date picker inside a singular relationship and saves it back untouched', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $ferry = repeaterObjectLeafSeedFerry($entityManager);

    [$schema, $host] = repeaterObjectLeafWire($ferry, Group::make([
        TextInput::make('pier'),
        DateTimePicker::make('assignedOn')->time(false),
    ])->relationship('berth'));

    $schema->fill($ferry);

    expect($host->data['berth'])->toBe(['pier' => 'East', 'assignedOn' => '2026-04-30']);

    $host->data['berth']['pier'] = 'West';

    $schema->getData();
    $entityManager->flush();

    expect($entityManager->getConnection()->fetchAllAssociative('SELECT pier, assignedOn AS assigned_on FROM Berth'))
        ->toBe([['pier' => 'West', 'assigned_on' => '2026-04-30']]);
});
