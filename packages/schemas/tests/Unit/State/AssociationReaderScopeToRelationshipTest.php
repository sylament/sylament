<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\State;

use Doctrine\ORM\EntityManager;
use LogicException;
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\State\Relationship;
use Sylament\Schemas\Tests\Fixtures\Doctrine\CompositeOwnerRig;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Pallet;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostSection;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Slip;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Tag;
use Sylament\Schemas\Tests\Fixtures\Doctrine\EntityManagerFactory;

/**
 * A post owning a visible and a hidden section and two tags, beside a second
 * post owning a section and a tag of its own — so a scope that drops the
 * caller's condition, or the owner's, answers more than it should.
 *
 * @return array{EntityManager, Post, Tag, Tag}
 */
function scopedPost(): array
{
    $entityManager = EntityManagerFactory::create();
    $post = new Post();
    $post->setTitle('Scoped');

    $other = new Post();
    $other->setTitle('Other');

    $kept = new Tag();
    $kept->setName('kept');

    $dropped = new Tag();
    $dropped->setName('dropped');

    $foreign = new Tag();
    $foreign->setName('foreign');

    $post->addTag($kept);
    $post->addTag($dropped);

    $other->addTag($foreign);

    foreach ([[$post, 'Visible'], [$post, 'Hidden'], [$other, 'Elsewhere']] as [$owner, $heading]) {
        $section = new PostSection();
        $section->setHeading($heading);
        $owner->addSection($section);
        $entityManager->persist($section);
    }

    foreach ([$post, $other, $kept, $dropped, $foreign] as $record) {
        $entityManager->persist($record);
    }

    $entityManager->flush();

    return [$entityManager, $post, $kept, $dropped];
}

/**
 * @param list<object> $records
 *
 * @return list<string>
 */
function scopedKeys(EntityManager $entityManager, array $records): array
{
    return array_map(static fn (object $record): string => RecordKey::of($entityManager, $record), $records);
}

it('narrows a caller builder through the mapped-by field and keeps its own condition', function (): void {
    [$entityManager, $post] = scopedPost();

    $query = $entityManager->createQueryBuilder()->select('section')->from(PostSection::class, 'section')->andWhere("section.heading <> 'Hidden'");
    $scoped = new AssociationReader($entityManager)->scopeToRelationship($query, new Relationship($post, 'sections'), 'section');

    /** @var list<PostSection> $sections */
    $sections = $scoped->getQuery()->getResult();

    expect($scoped)->toBe($query)
        ->and($scoped->getDQL())->toBe('SELECT section FROM ' . PostSection::class . " section WHERE section.heading <> 'Hidden' AND section.post = :sylament_relationship_owner")
        ->and($scoped->getParameter('sylament_relationship_owner')?->getValue())->toBe($post->getId())
        ->and(array_map(static fn (PostSection $section): string => $section->getHeading(), $sections))->toBe(['Visible']);
});

it('narrows a caller builder through the correlated subselect of a many-to-many and keeps its own condition', function (): void {
    [$entityManager, $post, $kept, $dropped] = scopedPost();

    $query = $entityManager->createQueryBuilder()->select('record')->from(Tag::class, 'record')->andWhere('record.id <> :dropped')->setParameter('dropped', $dropped->getId());
    $scoped = new AssociationReader($entityManager)->scopeToRelationship($query, new Relationship($post, 'tags'));

    expect($scoped->getDQL())->toBe('SELECT record FROM ' . Tag::class . ' record WHERE record.id <> :dropped AND (EXISTS (SELECT 1 FROM ' . Post::class . ' sy_owner JOIN sy_owner.tags sy_related WHERE sy_owner.id = :sylament_relationship_owner AND sy_related.id = record.id))')
        ->and(scopedKeys($entityManager, $scoped->getQuery()->getResult()))->toBe([RecordKey::of($entityManager, $kept)]);
});

it('narrows a caller builder from a composite owner on both arms and keeps its own condition', function (string $relationship, string $targetClass, string $condition, array $unconditioned, array $conditioned): void {
    $entityManager = CompositeOwnerRig::create();
    $reader = new AssociationReader($entityManager);
    $owner = CompositeOwnerRig::findPallet($entityManager, 'b1b2c3d4-0000-4000-8000-000000000002', 'north');

    $scoped = $reader->scopeToRelationship($entityManager->createQueryBuilder()->select('record')->from($targetClass, 'record')->andWhere($condition), new Relationship($owner, $relationship));

    expect($scoped->getDQL())->toBe('SELECT record FROM ' . $targetClass . ' record WHERE ' . $condition . ' AND (EXISTS (SELECT 1 FROM ' . Pallet::class . ' sy_owner JOIN sy_owner.' . $relationship . ' sy_related WHERE sy_owner.batch = :sylament_relationship_owner_0 AND sy_owner.lane = :sylament_relationship_owner_1 AND sy_related.id = record.id))')
        ->and(scopedKeys($entityManager, $reader->createRelationshipQuery(new Relationship($owner, $relationship))->getQuery()->getResult()))->toEqualCanonicalizing($unconditioned)
        ->and(scopedKeys($entityManager, $scoped->getQuery()->getResult()))->toBe($conditioned);
})->with([
    'many-to-many' => ['topics', Tag::class, 'record.id <> 1', ['1', '2'], ['2']],
    'one-to-many' => ['slips', Slip::class, "record.label <> 'pallet shares lane'", ['18'], []],
]);

it('answers createRelationshipQuery() as the scope over a bare select of the target', function (): void {
    [$entityManager, $post] = scopedPost();
    $reader = new AssociationReader($entityManager);

    foreach (['sections' => PostSection::class, 'tags' => Tag::class] as $relationship => $targetClass) {
        $bare = $entityManager->createQueryBuilder()->select('record')->from($targetClass, 'record');

        expect($reader->createRelationshipQuery(new Relationship($post, $relationship))->getDQL())
            ->toBe($reader->scopeToRelationship($bare, new Relationship($post, $relationship))->getDQL());
    }
});

it('refuses a builder with no root under the alias the scope narrows', function (): void {
    [$entityManager, $post] = scopedPost();

    $query = $entityManager->createQueryBuilder()->select('section')->from(PostSection::class, 'section');

    expect(fn (): mixed => new AssociationReader($entityManager)->scopeToRelationship($query, new Relationship($post, 'sections')))
        ->toThrow(LogicException::class, sprintf('Cannot scope a query to the relationship [sections] of [%s]: it has no root under the alias [record], which the scope narrows.', Post::class))
        ->and($query->getDQLPart('where'))->toBeNull();
});
