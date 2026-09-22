<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\State;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\State\Relationship;
use Sylament\Schemas\Tests\Fixtures\Doctrine\CompositeOwnerRig;
use Sylament\Schemas\Tests\Fixtures\Doctrine\CompositeTargetRig;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Listing;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Placard;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Tag;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Zone;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Sku;

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
    'one-to-many' => ['slips', ['1'], ['2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13'], ['2']],
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

it('leaves the single-derived-identity owner bound as the record itself', function (string $relationship): void {
    $entityManager = CompositeOwnerRig::create();
    $reader = new AssociationReader($entityManager);
    $seal = CompositeTargetRig::findSeal($entityManager, 1);

    expect($reader->createRelationshipQuery(new Relationship($seal, $relationship))->getParameter('sylament_relationship_owner')?->getValue())->toBe($seal);
})->with(['topics', 'markers']);

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
