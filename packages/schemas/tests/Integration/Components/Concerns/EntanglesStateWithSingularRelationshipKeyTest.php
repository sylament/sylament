<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Components\Concerns;

use Doctrine\ORM\EntityManager;
use LogicException;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Binder;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\LintelBase;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\LintelHolder;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\LintelPair;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\TrestleOwner;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\TrestleTop;
use Sylament\Schemas\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Support\Tests\Doctrine\RecordingMiddleware;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/*
 * A singular relationship constructing its related record: one whose assigned
 * key is still incomplete once the group's fields are written could never be
 * keyed after the flush inserted it, so the save refuses it by name before
 * anything is written. A single scalar key stays the application's to guard.
 */

/**
 * A `data`-bag host validating through the container's validator, its schema
 * bound to `$record` and every relationship read through `$entityManager`.
 *
 * @return array{Schema, StateHostFixture}
 */
function singularKeySchema(object $record, EntityManager $entityManager, Component ...$components): array
{
    $reader = new AssociationReader($entityManager);
    $host = new StateHostFixture();
    $host->setSchemaStateValidator(test()->getContainer()->get(ValidatorInterface::class));

    foreach ($components as $component) {
        if ($component instanceof Group) {
            $component->setAssociationReader($reader);
        }
    }

    $schema = Schema::make($host)->statePath('data')->record($record)->components($components);
    $schema->setAssociationReader($reader);

    $host->cacheSchema('schema', $schema);
    $schema->fill($record);

    return [$schema, $host];
}

it('refuses by name a new related record whose composite key the group leaves blank, writing nothing', function (array $pair): void {
    $recorder = new RecordingMiddleware();
    $entityManager = EntityManagerFactory::create($recorder);
    $holder = new LintelHolder();

    [$schema, $host] = singularKeySchema($holder, $entityManager, StateField::make('name'), Group::make([StateField::make('a'), StateField::make('b'), StateField::make('label')])->relationship('pair'));
    $host->data = ['name' => 'Holder', 'pair' => $pair];
    $recorder->reset();

    expect(fn (): object => $schema->getData())->toThrow(
        LogicException::class,
        sprintf('The [pair] relationship is saving a new [%s] whose identifier is incomplete, so no record key can be minted for it. Give every identifier field a component — enabled, dehydrated and required — inside the relationship, or on the record it hangs off when its identifier is that record, or drop relationship() and create the related record by hand after getData().', LintelPair::class),
    );

    expect($recorder->statements())->toBe([]);
})->with([
    'a whitespace half' => [['a' => 'left', 'b' => '   ', 'label' => 'Spaced']],
    'an empty half' => [['a' => 'left', 'b' => '', 'label' => 'Empty']],
]);

it('refuses by name a new related record whose key half no field inside the group maps', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = EntityManagerFactory::create($recorder);

    [$schema, $host] = singularKeySchema(new LintelHolder(), $entityManager, StateField::make('name'), Group::make([StateField::make('a'), StateField::make('label')])->relationship('pair'));
    $host->data = ['name' => 'Holder', 'pair' => ['a' => 'left', 'label' => 'Half']];
    $recorder->reset();

    expect(fn (): object => $schema->getData())->toThrow(LogicException::class, sprintf('The [pair] relationship is saving a new [%s] whose identifier is incomplete', LintelPair::class));
    expect($recorder->statements())->toBe([]);
});

it("refuses by name a new shared-key record whose identifier is the owner while the owner's optional key is blank, naming the owner in its advice", function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = EntityManagerFactory::create($recorder);
    $owner = new TrestleOwner();

    [$schema, $host] = singularKeySchema($owner, $entityManager, StateField::make('code'), Group::make([StateField::make('label')])->relationship('top'));
    $host->data = ['code' => '   ', 'top' => ['label' => 'Topped']];
    $recorder->reset();

    expect(fn (): object => $schema->getData())->toThrow(
        LogicException::class,
        sprintf('The [top] relationship is saving a new [%s] whose identifier is incomplete, so no record key can be minted for it. Give every identifier field a component — enabled, dehydrated and required — inside the relationship, or on the record it hangs off when its identifier is that record, or drop relationship() and create the related record by hand after getData().', TrestleTop::class),
    );

    expect($recorder->statements())->toBe([]);
});

it('saves a new related record whose composite key the group fills', function (): void {
    $entityManager = EntityManagerFactory::create();
    $holder = new LintelHolder();

    [$schema, $host] = singularKeySchema($holder, $entityManager, StateField::make('name'), Group::make([StateField::make('a'), StateField::make('b'), StateField::make('label')])->relationship('pair'));
    $host->data = ['name' => 'Holder', 'pair' => ['a' => 'left', 'b' => 'right', 'label' => 'Filled']];

    expect($schema->getData())->toBe($holder);

    $entityManager->persist($holder);
    $entityManager->flush();

    expect($entityManager->getConnection()->fetchAllAssociative('SELECT a, b, label FROM LintelPair'))->toBe([['a' => 'left', 'b' => 'right', 'label' => 'Filled']]);
});

it('leaves a new related record whose single scalar key is blank to the application', function (): void {
    $entityManager = EntityManagerFactory::create();
    $binder = new Binder();

    [$schema, $host] = singularKeySchema($binder, $entityManager, StateField::make('title'), Group::make([StateField::make('code'), StateField::make('label')])->relationship('spine'));
    $host->data = ['title' => 'Binder', 'spine' => ['code' => '   ', 'label' => 'Spaced']];

    expect($schema->getData())->toBe($binder)
        ->and($binder->getSpine()?->getCode())->toBe('   ');
});

it('refuses nothing when the related record already exists', function (): void {
    $entityManager = EntityManagerFactory::create();
    $pair = new LintelPair();
    $pair->setA('left');
    $pair->setB('right');

    $holder = new LintelHolder();
    $holder->setName('Holder');
    $holder->setPair($pair);

    $entityManager->persist($holder);
    $entityManager->flush();

    [$schema, $host] = singularKeySchema($holder, $entityManager, StateField::make('name'), Group::make([StateField::make('label')])->relationship('pair'));
    $host->data = ['name' => 'Holder', 'pair' => ['label' => 'Relabelled']];

    expect($schema->getData())->toBe($holder)
        ->and($pair->getLabel())->toBe('Relabelled');
});

it("saves a new related record whose key the owner's syncing setter fills as the relationship is written", function (): void {
    $entityManager = EntityManagerFactory::create();
    $base = new LintelBase();

    [$schema, $host] = singularKeySchema($base, $entityManager, StateField::make('name'), Group::make([StateField::make('label')])->relationship('cap'));
    $host->data = ['name' => 'Base', 'cap' => ['label' => 'Capped']];

    expect($schema->getData())->toBe($base)
        ->and($base->getCap()?->getBase())->toBe($base);

    $entityManager->persist($base);
    $entityManager->flush();

    expect($entityManager->getConnection()->fetchAllAssociative('SELECT base_id, label FROM LintelCap'))->toBe([['base_id' => $base->getId(), 'label' => 'Capped']]);
});
