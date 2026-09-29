<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\State;

use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\State\Relationship;
use Sylament\Schemas\Tests\Fixtures\Doctrine\CompositeOwnerRig;
use Sylament\Schemas\Tests\Fixtures\Doctrine\CompositeTargetRig;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Enrolment;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Marker;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostSeal;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostTranslation;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Redirect;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Tag;

it('correlates the many-to-many subselect against every identifier field of the target', function (string $relationship, string $targetClass, string $correlation): void {
    $entityManager = CompositeTargetRig::create();
    $reader = new AssociationReader($entityManager);
    $post = CompositeTargetRig::post($entityManager, 1);

    $related = $reader->createRelationshipQuery(new Relationship($post, $relationship));
    $candidates = $reader->createRelationshipCandidateQuery(new Relationship($post, $relationship));

    $exists = 'EXISTS (SELECT 1 FROM ' . Post::class . ' sy_owner JOIN sy_owner.' . $relationship . ' sy_related WHERE sy_owner.id = :sylament_relationship_owner AND ' . $correlation . ')';

    expect($related->getDQL())->toBe('SELECT record FROM ' . $targetClass . ' record WHERE ' . $exists)
        ->and($candidates->getDQL())->toBe('SELECT record FROM ' . $targetClass . ' record WHERE NOT ' . $exists)
        ->and($related->getParameters())->toHaveCount(1)
        ->and($candidates->getParameters())->toHaveCount(1);
})->with([
    'single identifier' => ['tags', Tag::class, 'sy_related.id = record.id'],
    'scalar pair, owning side' => ['redirects', Redirect::class, 'sy_related.host = record.host AND sy_related.path = record.path'],
    'scalar pair, inverse side' => ['listedRedirects', Redirect::class, 'sy_related.host = record.host AND sy_related.path = record.path'],
    'derived identity, owning side' => ['linkedTranslations', PostTranslation::class, 'sy_related.post = record.post AND sy_related.locale = record.locale'],
    'derived identity, inverse side' => ['taggedTranslations', PostTranslation::class, 'sy_related.post = record.post AND sy_related.locale = record.locale'],
    'single derived identity' => ['seals', PostSeal::class, 'sy_related.post = record.post'],
    'join entity' => ['enrolments', Enrolment::class, 'sy_related.post = record.post AND sy_related.tag = record.tag'],
]);

it('correlates against the alias the caller chose', function (): void {
    $entityManager = CompositeTargetRig::create();
    $reader = new AssociationReader($entityManager);
    $post = CompositeTargetRig::post($entityManager, 1);

    expect($reader->createRelationshipQuery(new Relationship($post, 'redirects'), 'thing')->getDQL())
        ->toBe('SELECT thing FROM ' . Redirect::class . ' thing WHERE EXISTS (SELECT 1 FROM ' . Post::class . ' sy_owner JOIN sy_owner.redirects sy_related WHERE sy_owner.id = :sylament_relationship_owner AND sy_related.host = thing.host AND sy_related.path = thing.path)');
});

it('leaves the two subselect aliases reserved on both queries', function (string $alias, string $method): void {
    $entityManager = CompositeTargetRig::create();
    $reader = new AssociationReader($entityManager);
    $post = CompositeTargetRig::post($entityManager, 1);

    expect(fn (): mixed => $reader->{$method}(new Relationship($post, 'redirects'), $alias)->getQuery()->getResult())
        ->toThrow(QueryException::class, $alias);
})->with(['sy_owner', 'sy_related'])->with(['createRelationshipQuery', 'createRelationshipCandidateQuery']);

it('lists the records a single-identifier owner is related to, and the complement, for every identifier flavour', function (string $relationship, array $related, array $candidates): void {
    $entityManager = CompositeTargetRig::create();
    $reader = new AssociationReader($entityManager);
    $post = CompositeTargetRig::post($entityManager, 1);

    $key = static fn (object $record): string => RecordKey::of($entityManager, $record);

    expect(array_map($key, $reader->createRelationshipQuery(new Relationship($post, $relationship))->getQuery()->getResult()))->toEqualCanonicalizing($related)
        ->and(array_map($key, $reader->createRelationshipCandidateQuery(new Relationship($post, $relationship))->getQuery()->getResult()))->toEqualCanonicalizing($candidates);
})->with([
    'scalar pair, owning side' => ['redirects', [CompositeTargetRig::REDIRECT_ONE], [CompositeTargetRig::REDIRECT_TWO, CompositeTargetRig::REDIRECT_THREE]],
    'scalar pair, inverse side' => ['listedRedirects', [CompositeTargetRig::REDIRECT_TWO], [CompositeTargetRig::REDIRECT_ONE, CompositeTargetRig::REDIRECT_THREE]],
    'derived identity, owning side' => ['linkedTranslations', [CompositeTargetRig::TRANSLATION_EN], [CompositeTargetRig::TRANSLATION_DE, CompositeTargetRig::TRANSLATION_FR]],
    'derived identity, inverse side' => ['taggedTranslations', [CompositeTargetRig::TRANSLATION_DE], [CompositeTargetRig::TRANSLATION_EN, CompositeTargetRig::TRANSLATION_FR]],
    'single derived identity' => ['seals', [CompositeTargetRig::SEAL_ONE], [CompositeTargetRig::SEAL_TWO]],
    'join entity' => ['enrolments', [CompositeTargetRig::ENROLMENT_ONE], [CompositeTargetRig::ENROLMENT_TWO, CompositeTargetRig::ENROLMENT_THREE]],
]);

it('offers every record of a composite class to an owner related to none of them', function (string $relationship, int $total): void {
    $entityManager = CompositeTargetRig::create();
    $reader = new AssociationReader($entityManager);
    $post = CompositeTargetRig::post($entityManager, 3);

    expect($reader->createRelationshipQuery(new Relationship($post, $relationship))->getQuery()->getResult())->toBe([])
        ->and($reader->createRelationshipCandidateQuery(new Relationship($post, $relationship))->getQuery()->getResult())->toHaveCount($total);
})->with([
    'scalar pair' => ['redirects', 3],
    'derived identity' => ['linkedTranslations', 3],
    'single derived identity' => ['seals', 2],
    'join entity' => ['enrolments', 3],
]);

it('scopes a relationship query to an owner Doctrine cannot bind, on both arms and both queries', function (string $owner, string $relationship, string $targetClass, string $scope, array $related, array $candidates, string $method): void {
    $entityManager = CompositeOwnerRig::create();
    $reader = new AssociationReader($entityManager);

    $record = match ($owner) {
        'scalar pair' => CompositeOwnerRig::findRedirect($entityManager, 'example.com', '/a'),
        'derived identity' => CompositeOwnerRig::findTranslation($entityManager, 1, 'en'),
        default => CompositeOwnerRig::findEnrolment($entityManager, 1, 1),
    };

    $exists = 'EXISTS (SELECT 1 FROM ' . $record::class . ' sy_owner JOIN sy_owner.' . $relationship . ' sy_related WHERE ' . $scope . ' AND sy_related.id = record.id)';
    $isRelated = $method === 'createRelationshipQuery';

    $query = $reader->{$method}(new Relationship($record, $relationship));

    expect($query->getDQL())->toBe('SELECT record FROM ' . $targetClass . ' record WHERE ' . ($isRelated ? '' : 'NOT ') . $exists)
        ->and($query->getParameters()->map(static fn (Parameter $parameter): string => $parameter->getName())->toArray())
        ->toBe(['sylament_relationship_owner_0', 'sylament_relationship_owner_1'])
        ->and(array_map(static fn (object $record): string => RecordKey::of($entityManager, $record), $query->getQuery()->getResult()))
        ->toEqualCanonicalizing($isRelated ? $related : $candidates);
})->with([
    'scalar pair, many-to-many' => ['scalar pair', 'posts', Post::class, 'sy_owner.host = :sylament_relationship_owner_0 AND sy_owner.path = :sylament_relationship_owner_1', ['1', '2'], ['3']],
    'scalar pair, one-to-many' => ['scalar pair', 'markers', Marker::class, 'sy_owner.host = :sylament_relationship_owner_0 AND sy_owner.path = :sylament_relationship_owner_1', ['4'], ['1', '2', '3', '5', '6', '7', '8', '9', '10', '11', '12', '13']],
    'derived identity, many-to-many' => ['derived identity', 'posts', Post::class, 'sy_owner.post = :sylament_relationship_owner_0 AND sy_owner.locale = :sylament_relationship_owner_1', ['1', '2'], ['3']],
    'derived identity, one-to-many' => ['derived identity', 'markers', Marker::class, 'sy_owner.post = :sylament_relationship_owner_0 AND sy_owner.locale = :sylament_relationship_owner_1', ['7'], ['1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13']],
    'join entity, many-to-many' => ['join entity', 'topics', Tag::class, 'sy_owner.post = :sylament_relationship_owner_0 AND sy_owner.tag = :sylament_relationship_owner_1', ['1', '2'], []],
    'join entity, one-to-many' => ['join entity', 'markers', Marker::class, 'sy_owner.post = :sylament_relationship_owner_0 AND sy_owner.tag = :sylament_relationship_owner_1', ['10'], ['1', '2', '3', '4', '5', '6', '7', '8', '9', '11', '12', '13']],
])->with(['createRelationshipQuery', 'createRelationshipCandidateQuery']);

it('hands three owners sharing one identifier half each only its own records, on both arms', function (string $flavour, string $relationship, array $mine, array $sharesFirst, array $sharesSecond): void {
    $entityManager = CompositeOwnerRig::create();
    $reader = new AssociationReader($entityManager);

    $owners = match ($flavour) {
        'scalar pair' => [
            CompositeOwnerRig::findRedirect($entityManager, 'example.com', '/a'),
            CompositeOwnerRig::findRedirect($entityManager, 'example.com', '/b'),
            CompositeOwnerRig::findRedirect($entityManager, 'other.com', '/a'),
        ],
        'derived identity' => [
            CompositeOwnerRig::findTranslation($entityManager, 1, 'en'),
            CompositeOwnerRig::findTranslation($entityManager, 1, 'de'),
            CompositeOwnerRig::findTranslation($entityManager, 2, 'en'),
        ],
        default => [
            CompositeOwnerRig::findEnrolment($entityManager, 1, 1),
            CompositeOwnerRig::findEnrolment($entityManager, 1, 2),
            CompositeOwnerRig::findEnrolment($entityManager, 2, 1),
        ],
    };

    $keysOf = static fn (object $owner): array => array_map(
        static fn (object $record): string => RecordKey::of($entityManager, $record),
        $reader->createRelationshipQuery(new Relationship($owner, $relationship))->getQuery()->getResult(),
    );

    expect($keysOf($owners[0]))->toEqualCanonicalizing($mine)
        ->and($keysOf($owners[1]))->toBe($sharesFirst)
        ->and($keysOf($owners[2]))->toBe($sharesSecond);
})->with([
    'scalar pair, one-to-many' => ['scalar pair', 'markers', ['4'], ['5'], ['6']],
    'scalar pair, many-to-many' => ['scalar pair', 'posts', ['1', '2'], ['1'], ['3']],
    'derived identity, one-to-many' => ['derived identity', 'markers', ['7'], ['8'], ['9']],
    'derived identity, many-to-many' => ['derived identity', 'posts', ['1', '2'], ['1'], ['3']],
    'join entity, one-to-many' => ['join entity', 'markers', ['10'], ['11'], ['12']],
    'join entity, many-to-many' => ['join entity', 'topics', ['1', '2'], ['1'], ['2']],
]);

it('scopes a relationship query from a single-derived-identity owner, whose identifier field is not named id', function (): void {
    $entityManager = CompositeTargetRig::create();
    $reader = new AssociationReader($entityManager);
    $seal = CompositeTargetRig::findSeal($entityManager, 1);

    expect($reader->createRelationshipQuery(new Relationship($seal, 'topics'))->getDQL())
        ->toBe('SELECT record FROM ' . Tag::class . ' record WHERE EXISTS (SELECT 1 FROM ' . PostSeal::class . ' sy_owner JOIN sy_owner.topics sy_related WHERE sy_owner.post = :sylament_relationship_owner AND sy_related.id = record.id)')
        ->and($reader->createRelationshipQuery(new Relationship($seal, 'markers'))->getDQL())
        ->toBe('SELECT record FROM ' . Marker::class . ' record WHERE record.seal = :sylament_relationship_owner');
});

it('answers two single-derived-identity owners disjointly, on both arms', function (string $relationship, array $first, array $second): void {
    $entityManager = CompositeTargetRig::create();
    $reader = new AssociationReader($entityManager);

    $labels = static fn (array $records): array => array_map(static fn (object $record): string => RecordKey::of($entityManager, $record), $records);

    expect($labels($reader->createRelationshipQuery(new Relationship(CompositeTargetRig::findSeal($entityManager, 1), $relationship))->getQuery()->getResult()))->toBe($first)
        ->and($labels($reader->createRelationshipQuery(new Relationship(CompositeTargetRig::findSeal($entityManager, 2), $relationship))->getQuery()->getResult()))->toBe($second);
})->with([
    'many-to-many' => ['topics', ['1'], ['2']],
    'one-to-many' => ['markers', ['1'], ['2']],
]);

it('refuses a relationship query for an owner without an identifier, on either route', function (string $flavour, string $relationship): void {
    $entityManager = CompositeTargetRig::create();
    $reader = new AssociationReader($entityManager);

    // A scalar pair cannot reach this refusal: both halves are non-nullable
    // strings, so a half-filled one is refused as unprintable instead. The
    // derived identity is the composite owner that can hold half an identifier.
    $half = new PostTranslation();
    $half->setLocale('en');

    $owner = $flavour === 'single identifier' ? new Post() : $half;

    expect(fn (): QueryBuilder => $reader->createRelationshipQuery(new Relationship($owner, $relationship)))
        ->toThrow(LogicException::class, 'has none set');
})->with([
    'single identifier' => ['single identifier', 'redirects'],
    'composite owner' => ['composite owner', 'markers'],
]);
