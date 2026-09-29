<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\State\AssociationTarget;
use Sylament\Schemas\State\PropertyAccessor;
use Sylament\Schemas\State\RecordTypeReader;
use Sylament\Schemas\State\Relationship;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Author;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Category;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\CompositePair;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostSection;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Profile;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Tag;
use Sylament\Schemas\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * One manager shared, within a test, by the relationship cases below, so a case
 * that clears the identity map clears the one the reader holds. The cases above
 * build their own.
 */
function entityManager(): EntityManager
{
    /** @var WeakMap<TestCase, EntityManager> $entityManagers */
    static $entityManagers = new WeakMap();

    return $entityManagers[test()->target] ??= EntityManagerFactory::create();
}

function reader(): AssociationReader
{
    return new AssociationReader(entityManager(), new PropertyAccessor(PropertyAccess::createPropertyAccessor(), new RecordTypeReader()));
}

function seededRelationshipPost(): Post
{
    $post = new Post();
    $post->setTitle('Alpha');

    entityManager()->persist($post);
    entityManager()->flush();

    return $post;
}

function seededRelationshipCategory(): Category
{
    $category = new Category();
    $category->setName('news');

    entityManager()->persist($category);
    entityManager()->flush();

    return $category;
}

it('describes a to-one association', function (): void {
    $reader = new AssociationReader(EntityManagerFactory::create());

    $target = $reader->describe(Post::class, 'author');

    expect($target)->toBeInstanceOf(AssociationTarget::class)
        ->and($target?->targetClass)->toBe(Author::class)
        ->and($target?->isToMany)->toBeFalse()
        ->and($target?->isOwningSide)->toBeTrue()
        ->and($target?->isOrphanRemoval)->toBeFalse();
});

it('describes a to-many association with orphan removal', function (): void {
    $reader = new AssociationReader(EntityManagerFactory::create());

    $target = $reader->describe(Post::class, 'sections');

    expect($target?->targetClass)->toBe(PostSection::class)
        ->and($target?->isToMany)->toBeTrue()
        ->and($target?->isOwningSide)->toBeFalse()
        ->and($target?->isOrphanRemoval)->toBeTrue();
});

it('describes the many-to-many owning side', function (): void {
    $reader = new AssociationReader(EntityManagerFactory::create());

    $target = $reader->describe(Post::class, 'tags');

    expect($target?->targetClass)->toBe(Tag::class)
        ->and($target?->isToMany)->toBeTrue()
        ->and($target?->isOwningSide)->toBeTrue()
        ->and($target?->isOrphanRemoval)->toBeFalse();
});

it('reports the inverse side of a one-to-one', function (): void {
    $reader = new AssociationReader(EntityManagerFactory::create());

    $target = $reader->describe(Post::class, 'profile');

    expect($target?->targetClass)->toBe(Profile::class)
        ->and($target?->isToMany)->toBeFalse()
        ->and($target?->isOwningSide)->toBeFalse()
        ->and($target?->isOrphanRemoval)->toBeTrue();
});

it('answers null for a scalar property, a non-entity class and an anonymous class', function (): void {
    $reader = new AssociationReader(EntityManagerFactory::create());

    $anonymous = new class {
        public ?Author $author = null;
    };

    expect($reader->describe(Post::class, 'title'))->toBeNull()
        ->and($reader->describe(stdClass::class, 'anything'))->toBeNull()
        ->and($reader->describe($anonymous::class, 'author'))->toBeNull();
});

it('answers null without an entity manager and throws on the operations that need one', function (): void {
    $reader = new AssociationReader();

    expect($reader->hasEntityManager())->toBeFalse()
        ->and($reader->describe(Post::class, 'author'))->toBeNull()
        ->and(fn (): object => $reader->getReference(Author::class, 1))
        ->toThrow(LogicException::class, 'no Doctrine EntityManager')
        ->and(fn (): mixed => $reader->createOptionQuery(Author::class))
        ->toThrow(LogicException::class, 'no Doctrine EntityManager')
        ->and(fn () => $reader->remove(new Author()))
        ->toThrow(LogicException::class, 'no Doctrine EntityManager')
        ->and(fn () => $reader->persist(new Author()))
        ->toThrow(LogicException::class, 'no Doctrine EntityManager')
        ->and(fn () => $reader->flush())
        ->toThrow(LogicException::class, 'no Doctrine EntityManager')
        ->and(fn (): string => $reader->getRecordKey(new Author()))
        ->toThrow(LogicException::class, 'no Doctrine EntityManager');
});

it('reports whether it has an entity manager', function (): void {
    expect(new AssociationReader(EntityManagerFactory::create())->hasEntityManager())->toBeTrue();
});

it('resolves a record key', function (): void {
    $entityManager = EntityManagerFactory::create();
    $author = new Author();
    $author->setName('Ada');
    EntityManagerFactory::seed($entityManager, $author);

    $reader = new AssociationReader($entityManager);
    $reloaded = $entityManager->find(Author::class, $author->getId());
    assert($reloaded instanceof Author);

    expect($reader->getRecordKey($reloaded))->toBe((string) $author->getId());
});

it('refuses a record with no identifier set', function (): void {
    $reader = new AssociationReader(EntityManagerFactory::create());

    expect(fn (): string => $reader->getRecordKey(new Author()))
        ->toThrow(LogicException::class, 'has none set');
});

it('mints a key of every identifier component', function (): void {
    $reader = new AssociationReader(EntityManagerFactory::create());

    expect($reader->getRecordKey(new CompositePair(1, 2, 'pair')))->toBe('1~2');
});

it('finds a record key', function (): void {
    $entityManager = EntityManagerFactory::create();
    $author = new Author();
    $author->setName('Ada');
    EntityManagerFactory::seed($entityManager, $author);

    $reader = new AssociationReader($entityManager);
    $reloaded = $entityManager->find(Author::class, $author->getId());
    assert($reloaded instanceof Author);

    expect($reader->findRecordKey($reloaded))->toBe((string) $author->getId());
});

it('finds no record key for a record with no identifier set', function (): void {
    $reader = new AssociationReader(EntityManagerFactory::create());

    expect($reader->findRecordKey(new Author()))->toBeNull();
});

it('finds the same key of every identifier component', function (): void {
    $reader = new AssociationReader(EntityManagerFactory::create());

    expect($reader->findRecordKey(new CompositePair(1, 2, 'pair')))->toBe('1~2');
});

it('references an entity by identifier', function (): void {
    $entityManager = EntityManagerFactory::create();
    $author = new Author();
    $author->setName('Ada');
    EntityManagerFactory::seed($entityManager, $author);

    $reader = new AssociationReader($entityManager);
    $id = $author->getId();
    assert($id !== null);

    expect($reader->getReference(Author::class, $id))->toBeInstanceOf(Author::class);
});

it('builds an option query selecting the target entity', function (): void {
    $entityManager = EntityManagerFactory::create();
    $author = new Author();
    $author->setName('Ada');
    EntityManagerFactory::seed($entityManager, $author);

    $reader = new AssociationReader($entityManager);
    $query = $reader->createOptionQuery(Author::class);

    expect($query->getDQL())->toBe('SELECT record FROM ' . Author::class . ' record')
        ->and($query->getQuery()->getResult())->toHaveCount(1);
});

it('removes an entity through the entity manager', function (): void {
    $entityManager = EntityManagerFactory::create();
    $author = new Author();
    $author->setName('Ada');
    EntityManagerFactory::seed($entityManager, $author);

    $reader = new AssociationReader($entityManager);
    $reloaded = $entityManager->find(Author::class, $author->getId());
    assert($reloaded instanceof Author);

    $reader->remove($reloaded);
    $entityManager->flush();
    $entityManager->clear();

    expect($entityManager->find(Author::class, $author->getId()))->toBeNull();
});

it('persists an entity through the entity manager and flushes it', function (): void {
    $entityManager = EntityManagerFactory::create();
    $reader = new AssociationReader($entityManager);

    $author = new Author();
    $author->setName('Ada');

    $reader->persist($author);

    expect($entityManager->contains($author))->toBeTrue()
        ->and($author->getId())->toBeNull();

    $reader->flush();
    $id = $author->getId();
    $entityManager->clear();

    expect($entityManager->find(Author::class, $id))->toBeInstanceOf(Author::class);
});

it('describes the mapping shape of every association kind', function (string $class, string $property, array $expected): void {
    $target = reader()->describe($class, $property);

    expect($target)->toBeInstanceOf(AssociationTarget::class)
        ->and([
            'isManyToMany' => $target?->isManyToMany,
            'mappedBy' => $target?->mappedBy,
            'inversedBy' => $target?->inversedBy,
            'isNullable' => $target?->isNullable,
            'orderBy' => $target?->orderBy,
        ])->toBe($expected);
})->with([
    'owning unidirectional many-to-many' => [Post::class, 'tags', ['isManyToMany' => true, 'mappedBy' => null, 'inversedBy' => null, 'isNullable' => true, 'orderBy' => []]],
    'owning bidirectional many-to-many' => [Post::class, 'categories', ['isManyToMany' => true, 'mappedBy' => null, 'inversedBy' => 'posts', 'isNullable' => true, 'orderBy' => []]],
    'inverse many-to-many' => [Category::class, 'posts', ['isManyToMany' => true, 'mappedBy' => 'categories', 'inversedBy' => null, 'isNullable' => true, 'orderBy' => []]],
    'ordered one-to-many' => [Post::class, 'sections', ['isManyToMany' => false, 'mappedBy' => 'post', 'inversedBy' => null, 'isNullable' => true, 'orderBy' => ['position' => 'ASC']]],
    'descending one-to-many' => [Post::class, 'replies', ['isManyToMany' => false, 'mappedBy' => 'post', 'inversedBy' => null, 'isNullable' => true, 'orderBy' => ['body' => 'DESC']]],
    'nullable to-one' => [Post::class, 'author', ['isManyToMany' => false, 'mappedBy' => null, 'inversedBy' => 'posts', 'isNullable' => true, 'orderBy' => []]],
    'not-null to-one' => [PostSection::class, 'post', ['isManyToMany' => false, 'mappedBy' => null, 'inversedBy' => 'sections', 'isNullable' => false, 'orderBy' => []]],
]);

it('describes a relationship and refuses what a relation-scoped table cannot list', function (): void {
    $reader = reader();
    $post = seededRelationshipPost();

    expect($reader->describeRelationship(new Relationship($post, 'tags'))->isManyToMany)->toBeTrue()
        ->and($reader->describeRelationship(new Relationship($post, 'sections'))->mappedBy)->toBe('post')
        ->and(fn (): AssociationTarget => $reader->describeRelationship(new Relationship($post, 'author')))->toThrow(LogicException::class, 'single-valued')
        ->and(fn (): AssociationTarget => $reader->describeRelationship(new Relationship($post, 'nope')))->toThrow(LogicException::class, 'maps no association')
        ->and(fn (): AssociationTarget => $reader->describeRelationship(new Relationship(new stdClass(), 'tags')))->toThrow(LogicException::class, 'not a managed entity')
        ->and(fn (): AssociationTarget => new AssociationReader()->describeRelationship(new Relationship($post, 'tags')))->toThrow(LogicException::class, 'EntityManager');
});

it('describes a relationship whose owner is an uninitialised proxy', function (): void {
    $reader = reader();
    $entityManager = entityManager();
    $id = seededRelationshipPost()->getId();
    assert($id !== null);
    $entityManager->clear();

    $owner = $entityManager->getReference(Post::class, $id);
    assert($owner !== null);

    expect($reader->describeRelationship(new Relationship($owner, 'tags'))->targetClass)->toBe(Tag::class);
});

it('builds the relationship query rooted at the target', function (): void {
    $reader = reader();
    $post = seededRelationshipPost();

    $tags = $reader->createRelationshipQuery(new Relationship($post, 'tags'));
    $sections = $reader->createRelationshipQuery(new Relationship($post, 'sections'), 'section');
    $categoryPosts = $reader->createRelationshipQuery(new Relationship(seededRelationshipCategory(), 'posts'));

    expect($tags->getRootEntities())->toBe([Tag::class])
        ->and($tags->getRootAliases())->toBe(['record'])
        ->and($tags->getDQL())->toBe('SELECT record FROM ' . Tag::class . ' record WHERE EXISTS (SELECT 1 FROM ' . Post::class . ' sy_owner JOIN sy_owner.tags sy_related WHERE sy_owner.id = :sylament_relationship_owner AND sy_related.id = record.id)')
        ->and($tags->getParameter('sylament_relationship_owner')?->getValue())->toBe($post->getId())
        ->and($tags->getParameter('sylament_relationship_owner')?->getType())->toBe('integer')
        ->and($sections->getDQL())->toBe('SELECT section FROM ' . PostSection::class . ' section WHERE section.post = :sylament_relationship_owner')
        ->and($sections->getDQLPart('orderBy'))->toBe([])
        ->and($categoryPosts->getDQL())->toContain('JOIN sy_owner.posts sy_related');
});

it('builds the candidate query as the relationship query negated', function (): void {
    $reader = reader();
    $post = seededRelationshipPost();

    expect($reader->createRelationshipCandidateQuery(new Relationship($post, 'tags'))->getDQL())->toContain('WHERE NOT EXISTS (SELECT 1 FROM ' . Post::class . ' sy_owner JOIN sy_owner.tags sy_related WHERE sy_owner.id = :sylament_relationship_owner AND sy_related.id = record.id)')
        ->and($reader->createRelationshipCandidateQuery(new Relationship($post, 'sections'))->getDQL())->toEndWith('WHERE record.post IS NULL OR record.post <> :sylament_relationship_owner');

    $composed = $reader->createRelationshipCandidateQuery(new Relationship($post, 'sections'));
    $composed->andWhere('record.id IN (:keys)');

    expect($composed->getDQL())->toContain('WHERE (record.post IS NULL OR record.post <> :sylament_relationship_owner) AND record.id IN (:keys)');
});

it('refuses a relationship query for an owner without an identifier', function (): void {
    expect(fn (): QueryBuilder => reader()->createRelationshipQuery(new Relationship(new Post(), 'tags')))->toThrow(LogicException::class, 'identifier');
});

it('renders the relationship shapes as SQL the driver runs', function (): void {
    $reader = reader();
    $post = seededRelationshipPost();

    expect($reader->createRelationshipQuery(new Relationship($post, 'tags'))->getQuery()->getSQL())
        ->toContain('INNER JOIN post_tag')
        ->toContain('WHERE EXISTS (SELECT 1 AS sclr_2 FROM Post p1_ INNER JOIN post_tag p3_ ON p1_.id = p3_.post_id INNER JOIN Tag t2_ ON t2_.id = p3_.tag_id WHERE p1_.id = ? AND t2_.id = t0_.id)')
        ->and($reader->createRelationshipQuery(new Relationship($post, 'sections'))->getQuery()->getSQL())
        ->toEndWith('WHERE p0_.post_id = ?')
        ->and($reader->createRelationshipCandidateQuery(new Relationship($post, 'tags'))->getQuery()->getSQL())
        ->toContain('WHERE NOT EXISTS (SELECT 1 AS sclr_2 FROM Post p1_ INNER JOIN post_tag p3_ ON p1_.id = p3_.post_id INNER JOIN Tag t2_ ON t2_.id = p3_.tag_id WHERE p1_.id = ? AND t2_.id = t0_.id)')
        ->and($reader->createRelationshipCandidateQuery(new Relationship($post, 'sections'))->getQuery()->getSQL())
        ->toEndWith('WHERE p0_.post_id IS NULL OR p0_.post_id <> ?');
});

it('runs the relationship query against the database', function (): void {
    $reader = reader();
    $entityManager = entityManager();

    $post = seededRelationshipPost();
    $tag = new Tag();
    $tag->setName('php');

    $post->addTag($tag);
    $entityManager->persist($tag);
    $entityManager->flush();

    $other = new Tag();
    $other->setName('symfony');

    $entityManager->persist($other);
    $entityManager->flush();

    /** @var list<Tag> $related */
    $related = $reader->createRelationshipQuery(new Relationship($post, 'tags'))->getQuery()->getResult();

    /** @var list<Tag> $candidates */
    $candidates = $reader->createRelationshipCandidateQuery(new Relationship($post, 'tags'))->getQuery()->getResult();

    expect(array_map(static fn (Tag $found): ?int => $found->getId(), $related))->toBe([$tag->getId()])
        ->and(array_map(static fn (Tag $found): ?int => $found->getId(), $candidates))->toContain($other->getId())
        ->and(array_map(static fn (Tag $found): ?int => $found->getId(), $candidates))->not->toContain($tag->getId());
});
