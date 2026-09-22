<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\State;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Schemas\Identity\IdentifierConstraint;
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\State\Relationship;
use Sylament\Schemas\Tests\Fixtures\Doctrine\CompositeOwnerRig;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Marker;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Redirect;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\SigilChild;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Vault;
use Sylament\Schemas\Tests\Fixtures\Doctrine\RecordingMiddleware;

/**
 * @param list<object> $records
 *
 * @return list<string>
 */
function ownerScopeKeys(EntityManagerInterface $entityManager, array $records): array
{
    return array_map(static fn (object $record): string => RecordKey::of($entityManager, $record), $records);
}

it('keeps a record with no owner at all and one whose composite foreign key is half null among the candidates', function (): void {
    $entityManager = CompositeOwnerRig::create();
    $reader = new AssociationReader($entityManager);
    $owner = CompositeOwnerRig::findRedirect($entityManager, 'example.com', '/a');

    $candidates = $reader->createRelationshipCandidateQuery(new Relationship($owner, 'markers'))->getQuery()->getResult();
    $labels = array_map(static fn (Marker $marker): string => $marker->getLabel(), $candidates);

    $partial = $entityManager->getConnection()->fetchAssociative("SELECT redirect_host, redirect_path FROM Marker WHERE label = 'half an owner'");

    expect($labels)->toContain('loose')
        ->toContain('half an owner')
        ->and($partial)->toBe(['redirect_host' => 'example.com', 'redirect_path' => null]);
});

it('scopes an inverse-side many-to-many from a composite owner', function (string $path, array $related, array $candidates): void {
    $entityManager = CompositeOwnerRig::create();
    $reader = new AssociationReader($entityManager);
    $owner = CompositeOwnerRig::findRedirect($entityManager, 'example.com', $path);

    expect(ownerScopeKeys($entityManager, $reader->createRelationshipQuery(new Relationship($owner, 'labels'))->getQuery()->getResult()))->toBe($related)
        ->and(ownerScopeKeys($entityManager, $reader->createRelationshipCandidateQuery(new Relationship($owner, 'labels'))->getQuery()->getResult()))->toBe($candidates);
})->with([
    'mine' => ['/a', ['1'], ['2']],
    'sharing the host half' => ['/b', ['2'], ['1']],
]);

it('scopes a composite owner to a collection of records of its own class', function (): void {
    $entityManager = CompositeOwnerRig::create();
    $reader = new AssociationReader($entityManager);
    $owner = CompositeOwnerRig::findRedirect($entityManager, 'example.com', '/a');

    $related = $reader->createRelationshipQuery(new Relationship($owner, 'children'));

    expect($related->getDQL())->toBe('SELECT record FROM ' . Redirect::class . ' record WHERE EXISTS (SELECT 1 FROM ' . Redirect::class . ' sy_owner JOIN sy_owner.children sy_related WHERE sy_owner.host = :sylament_relationship_owner_0 AND sy_owner.path = :sylament_relationship_owner_1 AND sy_related.host = record.host AND sy_related.path = record.path)')
        ->and(ownerScopeKeys($entityManager, $related->getQuery()->getResult()))->toBe([CompositeOwnerRig::REDIRECT_SHARES_HOST, CompositeOwnerRig::REDIRECT_SHARES_PATH])
        ->and(ownerScopeKeys($entityManager, $reader->createRelationshipCandidateQuery(new Relationship($owner, 'children'))->getQuery()->getResult()))
        ->toBe([CompositeOwnerRig::REDIRECT_MINE, CompositeOwnerRig::REDIRECT_APART]);
});

it('scopes from a single-table subclass owner whose collections the root declares', function (string $relationship, array $related, array $candidates): void {
    $entityManager = CompositeOwnerRig::create();
    $reader = new AssociationReader($entityManager);
    $owner = CompositeOwnerRig::findSigil($entityManager, 'north', 'alpha');

    $query = $reader->createRelationshipQuery(new Relationship($owner, $relationship));

    expect($query->getDQL())->toContain('FROM ' . SigilChild::class . ' sy_owner JOIN sy_owner.' . $relationship . ' sy_related WHERE sy_owner.realm = :sylament_relationship_owner_0 AND sy_owner.mark = :sylament_relationship_owner_1')
        ->and(ownerScopeKeys($entityManager, $query->getQuery()->getResult()))->toBe($related)
        ->and(ownerScopeKeys($entityManager, $reader->createRelationshipCandidateQuery(new Relationship($owner, $relationship))->getQuery()->getResult()))->toBe($candidates);
})->with([
    'many-to-many' => ['topics', ['1', '2'], []],
    'one-to-many' => ['slips', ['11'], ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '12', '13']],
]);

it('scopes a many-to-many from a chained derived identity', function (int $postId, int $link, array $related, array $candidates): void {
    $entityManager = CompositeOwnerRig::create();
    $reader = new AssociationReader($entityManager);
    $owner = CompositeOwnerRig::findChain($entityManager, $postId, $link);

    expect($reader->createRelationshipQuery(new Relationship($owner, 'topics'))->getDQL())
        ->toContain('sy_owner.seal = :sylament_relationship_owner_0 AND sy_owner.link = :sylament_relationship_owner_1')
        ->and(ownerScopeKeys($entityManager, $reader->createRelationshipQuery(new Relationship($owner, 'topics'))->getQuery()->getResult()))->toBe($related)
        ->and(ownerScopeKeys($entityManager, $reader->createRelationshipCandidateQuery(new Relationship($owner, 'topics'))->getQuery()->getResult()))->toBe($candidates);
})->with([
    'mine' => [1, 1, ['1'], ['2']],
    'sharing the seal half' => [1, 2, ['2'], ['1']],
    'sharing the link half' => [2, 1, ['1', '2'], []],
]);

it('leaves the outer builder one root, no join and no alias of its own, for every owner flavour', function (string $flavour, string $relationship, string $method): void {
    $entityManager = CompositeOwnerRig::create();
    $reader = new AssociationReader($entityManager);

    $owner = match ($flavour) {
        'scalar pair' => CompositeOwnerRig::findRedirect($entityManager, 'example.com', '/a'),
        'derived identity' => CompositeOwnerRig::findTranslation($entityManager, 1, 'en'),
        'converting single identifier' => CompositeOwnerRig::findListing($entityManager, CompositeOwnerRig::LISTING_MINE),
        'subclass' => CompositeOwnerRig::findSigil($entityManager, 'north', 'alpha'),
        default => CompositeOwnerRig::findEnrolment($entityManager, 1, 1),
    };

    $query = $reader->{$method}(new Relationship($owner, $relationship));

    expect($query->getAllAliases())->toBe(['record'])
        ->and($query->getDQLPart('join'))->toBe([])
        ->and($query->getRootAliases())->toBe(['record']);
})->with([
    'scalar pair, one-to-many' => ['scalar pair', 'markers'],
    'scalar pair, many-to-many' => ['scalar pair', 'posts'],
    'derived identity, one-to-many' => ['derived identity', 'markers'],
    'join entity, many-to-many' => ['join entity', 'topics'],
    'converting single identifier' => ['converting single identifier', 'topics'],
    'subclass owner' => ['subclass', 'slips'],
])->with(['createRelationshipQuery', 'createRelationshipCandidateQuery']);

it('carries the owner scope beside a record key and two key selections on one builder', function (): void {
    $entityManager = CompositeOwnerRig::create();
    $reader = new AssociationReader($entityManager);
    $owner = CompositeOwnerRig::findRedirect($entityManager, 'example.com', '/a');

    $query = $reader->createRelationshipQuery(new Relationship($owner, 'children'));

    $query->andWhere(IdentifierConstraint::in($query, 'record', Redirect::class, [CompositeOwnerRig::REDIRECT_SHARES_HOST, CompositeOwnerRig::REDIRECT_SHARES_PATH], 'sylament_selected_record_keys'));
    $query->andWhere(IdentifierConstraint::in($query, 'record', Redirect::class, [CompositeOwnerRig::REDIRECT_SHARES_HOST, CompositeOwnerRig::REDIRECT_APART], 'sylament_selectable_record_keys'));

    $narrowed = IdentifierConstraint::equals($query, 'record', Redirect::class, CompositeOwnerRig::REDIRECT_SHARES_HOST, 'sylament_record_key');
    expect($narrowed)->toBeString();

    $query->andWhere((string) $narrowed);

    $names = $query->getParameters()->map(static fn (object $parameter): string => $parameter->getName())->toArray();

    expect($names)->toHaveCount(count(array_unique($names)))
        ->toContain('sylament_relationship_owner_0')
        ->toContain('sylament_relationship_owner_1')
        ->toContain('sylament_record_key_0')
        ->and(ownerScopeKeys($entityManager, $query->getQuery()->getResult()))->toBe([CompositeOwnerRig::REDIRECT_SHARES_HOST]);
});

it('scopes from an owner reference it never initialises, from a detached twin and from an unflushed record', function (): void {
    $entityManager = CompositeOwnerRig::create();
    $reader = new AssociationReader($entityManager);

    $reference = $entityManager->getReference(Redirect::class, ['host' => 'example.com', 'path' => '/a']);
    assert($reference instanceof Redirect);

    $related = ownerScopeKeys($entityManager, $reader->createRelationshipQuery(new Relationship($reference, 'markers'))->getQuery()->getResult());

    expect($entityManager->isUninitializedObject($reference))->toBeTrue()
        ->and($related)->toBe(['4']);

    $twin = new Redirect();
    $twin->setHost('example.com');
    $twin->setPath('/a');

    expect(ownerScopeKeys($entityManager, $reader->createRelationshipQuery(new Relationship($twin, 'markers'))->getQuery()->getResult()))->toBe(['4']);

    $unflushed = new Redirect();
    $unflushed->setHost('nowhere.test');
    $unflushed->setPath('/z');
    $unflushed->setLabel('unflushed');

    $entityManager->persist($unflushed);

    expect($reader->createRelationshipQuery(new Relationship($unflushed, 'markers'))->getQuery()->getResult())->toBe([])
        ->and($reader->createRelationshipCandidateQuery(new Relationship($unflushed, 'markers'))->getQuery()->getResult())->toHaveCount(13);
});

it('refuses an owner whose key does not decode against its own mapping, on both arms and both queries, before anything is executed', function (string $relationship, string $method): void {
    $recorder = new RecordingMiddleware();
    $entityManager = CompositeOwnerRig::create($recorder);
    $reader = new AssociationReader($entityManager);
    $owner = CompositeOwnerRig::findVault($entityManager, 'zeta', 'north');

    $recorder->reset();

    try {
        $reader->{$method}(new Relationship($owner, $relationship))->getQuery()->getResult();
        $message = null;
    } catch (LogicException $logicException) {
        $message = $logicException->getMessage();
    }

    expect($message)->toBe(sprintf(
        'The owner [%s] mints the key [%s], which does not decode against its own mapping, so the relationship [%s] cannot be scoped to it — an identifier type that cannot read its own printed form.',
        Vault::class,
        CompositeOwnerRig::VAULT_MINE,
        $relationship,
    ))->and($recorder->statements())->toBe([]);
})->with(['topics', 'slips'])->with(['createRelationshipQuery', 'createRelationshipCandidateQuery']);
