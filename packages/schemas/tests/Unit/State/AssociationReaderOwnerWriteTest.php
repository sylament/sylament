<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\State;

use Doctrine\ORM\EntityManager;
use LogicException;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\State\Relationship;
use Sylament\Schemas\Tests\Fixtures\Doctrine\CompositeOwnerRig;
use Sylament\Schemas\Tests\Fixtures\Doctrine\CompositeTargetRig;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Marker;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\MixedFirstMarker;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\MixedLastMarker;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostSeal;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Redirect;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Tag;

/**
 * The owner of each flavour, freshly read from the given manager.
 */
function ownerWriteRecord(EntityManager $entityManager, string $flavour): object
{
    return match ($flavour) {
        'scalar pair' => CompositeOwnerRig::findRedirect($entityManager, 'example.com', '/a'),
        'derived identity' => CompositeOwnerRig::findTranslation($entityManager, 1, 'en'),
        default => CompositeOwnerRig::findEnrolment($entityManager, 1, 2),
    };
}

it('reads every join column of a composite foreign key when it answers whether the association is nullable', function (string $class, bool $isNullable): void {
    $entityManager = CompositeOwnerRig::create();
    $reader = new AssociationReader($entityManager);

    expect($reader->describe($class, 'redirect')?->isNullable)->toBe($isNullable);
})->with([
    'every join column nullable' => [Marker::class, true],
    'the first join column nullable, the second not' => [MixedFirstMarker::class, false],
    'the first join column not nullable, the second is' => [MixedLastMarker::class, false],
]);

it('refuses to dissociate a record whose composite foreign key is nullable in only one of its columns', function (string $relationship, ?string $message): void {
    $entityManager = CompositeOwnerRig::create();
    $reader = new AssociationReader($entityManager);
    $owner = CompositeOwnerRig::findRedirect($entityManager, 'example.com', '/a');

    try {
        $reader->assertRelationshipAcceptsDetach(new Relationship($owner, $relationship));
        $refusal = null;
    } catch (LogicException $logicException) {
        $refusal = $logicException->getMessage();
    }

    expect($refusal)->toBe($message);
})->with([
    'every join column nullable' => ['markers', null],
    'the first join column nullable, the second not' => [
        'firstMixedMarkers',
        '[' . MixedFirstMarker::class . '::$redirect] is mapped with a `nullable: false` join column, so a [' . MixedFirstMarker::class . '] cannot be dissociated from its [' . Redirect::class . ']; make the column nullable or use DeleteAction.',
    ],
    'the first join column not nullable, the second is' => [
        'lastMixedMarkers',
        '[' . MixedLastMarker::class . '::$redirect] is mapped with a `nullable: false` join column, so a [' . MixedLastMarker::class . '] cannot be dissociated from its [' . Redirect::class . ']; make the column nullable or use DeleteAction.',
    ],
]);

it('points a one-to-many child at a composite owner and back to nothing, writing every join column', function (string $flavour, string $firstColumn, string $secondColumn, int|string $first, int|string $second): void {
    $entityManager = CompositeOwnerRig::create();
    $reader = new AssociationReader($entityManager);

    $loose = $entityManager->find(Marker::class, 3);
    assert($loose instanceof Marker);

    $reader->attach(new Relationship(ownerWriteRecord($entityManager, $flavour), 'markers'), $loose);
    $entityManager->flush();
    $entityManager->clear();

    $stored = $entityManager->getConnection()->fetchAssociative(sprintf('SELECT %s, %s FROM Marker WHERE id = 3', $firstColumn, $secondColumn));

    expect($stored)->toBe([$firstColumn => $first, $secondColumn => $second]);

    $detached = $entityManager->find(Marker::class, 3);
    assert($detached instanceof Marker);

    $reader->detach(new Relationship(ownerWriteRecord($entityManager, $flavour), 'markers'), $detached);
    $entityManager->flush();
    $entityManager->clear();

    expect($entityManager->getConnection()->fetchAssociative(sprintf('SELECT %s, %s FROM Marker WHERE id = 3', $firstColumn, $secondColumn)))
        ->toBe([$firstColumn => null, $secondColumn => null]);
})->with([
    'scalar pair' => ['scalar pair', 'redirect_host', 'redirect_path', 'example.com', '/a'],
    'derived identity' => ['derived identity', 'translation_post_id', 'translation_locale', 1, 'en'],
    'join entity' => ['join entity', 'enrolment_post_id', 'enrolment_tag_id', 1, 2],
]);

it('writes and removes a join-table row carrying every owner column, from the owning and from the inverse side', function (string $flavour, string $relationship, string $table, string $where): void {
    $entityManager = CompositeOwnerRig::create();
    $reader = new AssociationReader($entityManager);

    $related = match ($relationship) {
        'labels' => $entityManager->find(Tag::class, 2),
        'topics' => $entityManager->find(Tag::class, 2),
        default => $entityManager->find(Post::class, 3),
    };
    assert($related !== null);

    $count = static fn (): int => (int) $entityManager->getConnection()->fetchOne(sprintf('SELECT COUNT(*) FROM %s WHERE %s', $table, $where));

    expect($count())->toBe(0);

    $reader->attach(new Relationship(ownerWriteRecord($entityManager, $flavour), $relationship), $related);
    $entityManager->flush();
    $entityManager->clear();

    expect($count())->toBe(1);

    $owner = ownerWriteRecord($entityManager, $flavour);
    $reloaded = $reader->createRelationshipQuery(new Relationship($owner, $relationship))->getQuery()->getResult();

    $reader->detach(new Relationship($owner, $relationship), $reloaded[count($reloaded) - 1]);
    $entityManager->flush();
    $entityManager->clear();

    expect($count())->toBe(0);
})->with([
    'scalar pair, owning side' => ['scalar pair', 'posts', 'redirect_post_link', "redirect_host = 'example.com' AND redirect_path = '/a' AND post_id = 3"],
    'scalar pair, inverse side' => ['scalar pair', 'labels', 'tag_redirect_link', "redirect_host = 'example.com' AND redirect_path = '/a' AND tag_id = 2"],
    'derived identity, owning side' => ['derived identity', 'posts', 'translation_post_link', "translation_post_id = 1 AND translation_locale = 'en' AND post_id = 3"],
    'join entity, owning side' => ['join entity', 'topics', 'enrolment_topic_link', 'enrolment_post_id = 1 AND enrolment_tag_id = 2 AND topic_id = 2'],
]);

it('describes a relationship of a composite owner without reading its identifier at all', function (string $flavour, string $relationship, bool $isManyToMany): void {
    $entityManager = CompositeOwnerRig::create();
    $reader = new AssociationReader($entityManager);

    $target = $reader->describeRelationship(new Relationship(ownerWriteRecord($entityManager, $flavour), $relationship));

    expect($target->isToMany)->toBeTrue()
        ->and($target->isManyToMany)->toBe($isManyToMany);
})->with([
    'scalar pair, one-to-many' => ['scalar pair', 'markers', false],
    'scalar pair, many-to-many owning' => ['scalar pair', 'posts', true],
    'scalar pair, many-to-many inverse' => ['scalar pair', 'labels', true],
    'derived identity, one-to-many' => ['derived identity', 'markers', false],
    'join entity, many-to-many' => ['join entity', 'topics', true],
]);

it('keeps the single-identifier rig it grew out of answering exactly as before', function (): void {
    $entityManager = CompositeOwnerRig::create();
    $reader = new AssociationReader($entityManager);
    $post = CompositeTargetRig::post($entityManager, 1);

    expect($reader->createRelationshipQuery(new Relationship($post, 'seals'))->getDQL())
        ->toBe('SELECT record FROM ' . PostSeal::class . ' record WHERE EXISTS (SELECT 1 FROM ' . Post::class . ' sy_owner JOIN sy_owner.seals sy_related WHERE sy_owner.id = :sylament_relationship_owner AND sy_related.post = record.post)');
});
