<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\State;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\State\Relationship;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Cipher;
use Sylament\Schemas\Tests\Fixtures\Doctrine\CompositeOwnerRig;
use Sylament\Schemas\Tests\Fixtures\Doctrine\CompositeTargetRig;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Listing;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\ListingSeal;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Placard;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Strongbox;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\StrongboxSeal;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Tag;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Zone;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Sku;
use Symfony\Component\Uid\Uuid;

/**
 * @param list<object> $records
 *
 * @return list<string>
 */
function ownerBindKeys(EntityManagerInterface $entityManager, array $records): array
{
    return array_map(static fn (object $record): string => RecordKey::of($entityManager, $record), $records);
}

it('binds a single scalar identifier as its own value under its own DBAL type', function (string $flavour, string $relationship, mixed $expected, string $type): void {
    $entityManager = CompositeOwnerRig::create();
    $reader = new AssociationReader($entityManager);

    $owner = match ($flavour) {
        'converting value object' => CompositeOwnerRig::findListing($entityManager, CompositeOwnerRig::LISTING_MINE),
        'backed enum' => CompositeOwnerRig::findPlacard($entityManager, Zone::Header),
        'raw-bytes uuid' => CompositeOwnerRig::findCrate($entityManager, CompositeOwnerRig::CRATE_MINE),
        default => CompositeTargetRig::post($entityManager, 1),
    };

    $parameter = $reader->createRelationshipQuery(new Relationship($owner, $relationship))->getParameter('sylament_relationship_owner');

    expect($parameter?->getValue())->toEqual($expected)
        ->and($parameter?->getType())->toBe($type);
})->with([
    'int id, many-to-many' => ['int id', 'tags', 1, 'integer'],
    'int id, one-to-many' => ['int id', 'sections', 1, 'integer'],
    'converting value object, many-to-many' => ['converting value object', 'topics', new Sku('AB-123'), 'sku'],
    'converting value object, one-to-many' => ['converting value object', 'slips', new Sku('AB-123'), 'sku'],
    'backed enum, many-to-many' => ['backed enum', 'topics', 'header', 'string'],
    'backed enum, one-to-many' => ['backed enum', 'slips', 'header', 'string'],
    'raw-bytes uuid, many-to-many' => ['raw-bytes uuid', 'topics', Uuid::fromString('a1b2c3d4-0000-4000-8000-000000000001'), 'binary_uuid'],
    'raw-bytes uuid, one-to-many' => ['raw-bytes uuid', 'slips', Uuid::fromString('a1b2c3d4-0000-4000-8000-000000000001'), 'binary_uuid'],
]);

it('scopes an owner whose one identifier field stores a form it does not print, on both arms', function (string $relationship, array $mine, array $mineCandidates, array $apart): void {
    $entityManager = CompositeOwnerRig::create();
    $reader = new AssociationReader($entityManager);

    $owner = CompositeOwnerRig::findListing($entityManager, CompositeOwnerRig::LISTING_MINE);
    $other = CompositeOwnerRig::findListing($entityManager, CompositeOwnerRig::LISTING_APART);

    expect(ownerBindKeys($entityManager, $reader->createRelationshipQuery(new Relationship($owner, $relationship))->getQuery()->getResult()))->toBe($mine)
        ->and(ownerBindKeys($entityManager, $reader->createRelationshipCandidateQuery(new Relationship($owner, $relationship))->getQuery()->getResult()))->toBe($mineCandidates)
        ->and(ownerBindKeys($entityManager, $reader->createRelationshipQuery(new Relationship($other, $relationship))->getQuery()->getResult()))->toBe($apart);
})->with([
    'many-to-many' => ['topics', ['1'], ['2'], ['2']],
    'one-to-many' => ['slips', ['1'], ['2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24'], ['2']],
]);

it('binds an enum identifier by its backing value, which is what its column holds', function (string $relationship, array $header, array $footer): void {
    $entityManager = CompositeOwnerRig::create();
    $reader = new AssociationReader($entityManager);

    expect(ownerBindKeys($entityManager, $reader->createRelationshipQuery(new Relationship(CompositeOwnerRig::findPlacard($entityManager, Zone::Header), $relationship))->getQuery()->getResult()))->toBe($header)
        ->and(ownerBindKeys($entityManager, $reader->createRelationshipQuery(new Relationship(CompositeOwnerRig::findPlacard($entityManager, Zone::Footer), $relationship))->getQuery()->getResult()))->toBe($footer);
})->with([
    'many-to-many' => ['topics', ['1'], ['2']],
    'one-to-many' => ['slips', ['9'], ['10']],
]);

it("binds a single derived identity as its target's identifier value under that value's own DBAL type", function (string $relationship): void {
    $entityManager = CompositeOwnerRig::create();
    $reader = new AssociationReader($entityManager);
    $seal = CompositeTargetRig::findSeal($entityManager, 1);

    $parameter = $reader->createRelationshipQuery(new Relationship($seal, $relationship))->getParameter('sylament_relationship_owner');

    expect($parameter?->getValue())->toBe(1)
        ->and($parameter?->getType())->toBe('integer');
})->with(['topics', 'markers']);

it("binds a single derived identity onto a converting target as the terminal value under the terminal field's type", function (string $flavour, string $relationship, mixed $expected, string $type): void {
    $entityManager = CompositeOwnerRig::create();
    $reader = new AssociationReader($entityManager);

    $owner = match ($flavour) {
        'converting' => CompositeOwnerRig::findListingSeal($entityManager, CompositeOwnerRig::LISTING_SEAL_MINE),
        'raw-bytes' => CompositeOwnerRig::findCrateSeal($entityManager, CompositeOwnerRig::CRATE_SEAL_MINE),
        default => CompositeOwnerRig::findStrongboxSeal($entityManager, CompositeOwnerRig::STRONGBOX_SEAL_MINE),
    };

    $parameter = $reader->createRelationshipQuery(new Relationship($owner, $relationship))->getParameter('sylament_relationship_owner');

    expect($parameter?->getValue())->toEqual($expected)
        ->and($parameter?->getType())->toBe($type);
})->with([
    'onto a converting value object, many-to-many' => ['converting', 'topics', new Sku('AB-123'), 'sku'],
    'onto a converting value object, one-to-many' => ['converting', 'slips', new Sku('AB-123'), 'sku'],
    'onto a raw-bytes uuid, many-to-many' => ['raw-bytes', 'topics', Uuid::fromString('a1b2c3d4-0000-4000-8000-000000000001'), 'binary_uuid'],
    'onto a raw-bytes uuid, one-to-many' => ['raw-bytes', 'slips', Uuid::fromString('a1b2c3d4-0000-4000-8000-000000000001'), 'binary_uuid'],
    'onto a doubly-strict value object, many-to-many' => ['doubly strict', 'topics', new Cipher('omega'), 'cipher'],
    'onto a doubly-strict value object, one-to-many' => ['doubly strict', 'slips', new Cipher('omega'), 'cipher'],
]);

it('scopes a single derived identity onto a converting, a raw-bytes and a doubly-strict target, on both arms and both queries', function (string $flavour, string $relationship, array $mine, array $mineCandidates, array $apart): void {
    $entityManager = CompositeOwnerRig::create();
    $reader = new AssociationReader($entityManager);

    [$owner, $other] = match ($flavour) {
        'converting' => [CompositeOwnerRig::findListingSeal($entityManager, CompositeOwnerRig::LISTING_SEAL_MINE), CompositeOwnerRig::findListingSeal($entityManager, CompositeOwnerRig::LISTING_SEAL_APART)],
        'raw-bytes' => [CompositeOwnerRig::findCrateSeal($entityManager, CompositeOwnerRig::CRATE_SEAL_MINE), CompositeOwnerRig::findCrateSeal($entityManager, CompositeOwnerRig::CRATE_SEAL_APART)],
        default => [CompositeOwnerRig::findStrongboxSeal($entityManager, CompositeOwnerRig::STRONGBOX_SEAL_MINE), CompositeOwnerRig::findStrongboxSeal($entityManager, CompositeOwnerRig::STRONGBOX_SEAL_APART)],
    };

    expect(ownerBindKeys($entityManager, $reader->createRelationshipQuery(new Relationship($owner, $relationship))->getQuery()->getResult()))->toBe($mine)
        ->and(ownerBindKeys($entityManager, $reader->createRelationshipCandidateQuery(new Relationship($owner, $relationship))->getQuery()->getResult()))->toBe($mineCandidates)
        ->and(ownerBindKeys($entityManager, $reader->createRelationshipQuery(new Relationship($other, $relationship))->getQuery()->getResult()))->toBe($apart);
})->with([
    'onto a converting value object, many-to-many' => ['converting', 'topics', ['1'], ['2'], ['2']],
    'onto a converting value object, one-to-many' => ['converting', 'slips', ['19'], ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '20', '21', '22', '23', '24'], ['20']],
    'onto a raw-bytes uuid, many-to-many' => ['raw-bytes', 'topics', ['1'], ['2'], ['2']],
    'onto a raw-bytes uuid, one-to-many' => ['raw-bytes', 'slips', ['21'], ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '22', '23', '24'], ['22']],
    'onto a doubly-strict value object, many-to-many' => ['doubly strict', 'topics', ['1'], ['2'], ['2']],
    'onto a doubly-strict value object, one-to-many' => ['doubly strict', 'slips', ['23'], ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '24'], ['24']],
]);

it("scopes an unflushed single derived identity whose target's identifier is assigned, answering nothing related", function (string $relationship, array $candidates): void {
    $entityManager = CompositeOwnerRig::create();
    $reader = new AssociationReader($entityManager);

    $listing = new Listing();
    $listing->setSku(new Sku('ZZ-999'));

    $seal = new ListingSeal();
    $seal->setListing($listing);

    $related = $reader->createRelationshipQuery(new Relationship($seal, $relationship));

    expect($related->getQuery()->getResult())->toBe([])
        ->and($related->getParameter('sylament_relationship_owner')?->getValue())->toEqual(new Sku('ZZ-999'))
        ->and($related->getParameter('sylament_relationship_owner')?->getType())->toBe('sku')
        ->and(ownerBindKeys($entityManager, $reader->createRelationshipCandidateQuery(new Relationship($seal, $relationship))->getQuery()->getResult()))->toBe($candidates);
})->with([
    'many-to-many' => ['topics', ['1', '2']],
    'one-to-many' => ['slips', ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24']],
]);

it("refuses a single derived identity whose target's identifier is blank or unprintable, before binding anything", function (string $cipher): void {
    $entityManager = CompositeOwnerRig::create();
    $reader = new AssociationReader($entityManager);

    $strongbox = new Strongbox();
    $strongbox->setCipher(new Cipher($cipher));

    $seal = new StrongboxSeal();
    $seal->setStrongbox($strongbox);

    expect(fn (): array => $reader->createRelationshipQuery(new Relationship($seal, 'topics'))->getQuery()->getResult())
        ->toThrow(LogicException::class, 'is not a printable value');
})->with([
    'blank' => [''],
    'a NUL byte' => ["om\0ega"],
]);

it('reads back the very records a converting and an enum owner were seeded with', function (): void {
    $entityManager = CompositeOwnerRig::create();

    $listing = CompositeOwnerRig::findListing($entityManager, CompositeOwnerRig::LISTING_MINE);
    $placard = CompositeOwnerRig::findPlacard($entityManager, Zone::Header);

    expect($listing)->toBeInstanceOf(Listing::class)
        ->and((string) $listing->getSku())->toBe(CompositeOwnerRig::LISTING_MINE)
        ->and($placard)->toBeInstanceOf(Placard::class)
        ->and($placard->getZone())->toBe(Zone::Header)
        ->and($listing->getTopics()->map(static fn (Tag $tag): ?int => $tag->getId())->toArray())->toBe([1]);
});
