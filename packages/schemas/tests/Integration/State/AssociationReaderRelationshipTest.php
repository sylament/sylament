<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\State\Relationship;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Author;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Category;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Comment;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostSection;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Reply;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Tag;

/**
 * The container's reader over the container's entity manager — the schemas test
 * kernel builds one from the same fixture factory, so the pair is a real rig.
 *
 * @return array{AssociationReader, EntityManagerInterface}
 */
function rig(): array
{
    return [
        test()->getContainer()->get(AssociationReader::class),
        test()->getContainer()->get(EntityManagerInterface::class),
    ];
}

/**
 * The keys the relationship query answers, read through the reader — identity
 * comparisons across a fresh query are meaningless.
 *
 * @return list<string>
 */
function relatedIds(AssociationReader $reader, object $owner, string $name): array
{
    /** @var list<object> $records */
    $records = $reader->createRelationshipQuery(new Relationship($owner, $name))->getQuery()->getResult();

    return array_map($reader->getRecordKey(...), $records);
}

it('attaches and detaches through the owning collection of a unidirectional many-to-many', function (): void {
    [$reader, $entityManager] = rig();

    $post = new Post();
    $post->setTitle('Alpha');

    $tag = new Tag();
    $tag->setName('php');

    $entityManager->persist($post);
    $entityManager->persist($tag);
    $entityManager->flush();

    $reader->attach(new Relationship($post, 'tags'), $tag);
    $entityManager->flush();

    expect(relatedIds($reader, $post, 'tags'))->toBe([(string) $tag->getId()]);

    $reader->detach(new Relationship($post, 'tags'), $tag);
    $entityManager->flush();

    expect(relatedIds($reader, $post, 'tags'))->toBe([]);
});

it('attaches and detaches through the owning side of a bidirectional many-to-many', function (): void {
    [$reader, $entityManager] = rig();

    $post = new Post();
    $post->setTitle('Alpha');

    $category = new Category();
    $category->setName('news');

    $entityManager->persist($post);
    $entityManager->persist($category);
    $entityManager->flush();

    $reader->attach(new Relationship($post, 'categories'), $category);
    $entityManager->flush();
    $entityManager->clear();

    $reloaded = $entityManager->find(Post::class, $post->getId());
    assert($reloaded instanceof Post);

    expect(relatedIds($reader, $reloaded, 'categories'))->toBe([(string) $category->getId()]);

    $related = $entityManager->find(Category::class, $category->getId());
    assert($related instanceof Category);

    $reader->detach(new Relationship($reloaded, 'categories'), $related);
    $entityManager->flush();
    $entityManager->clear();

    $again = $entityManager->find(Post::class, $post->getId());
    assert($again instanceof Post);

    expect(relatedIds($reader, $again, 'categories'))->toBe([]);
});

it('attaches from the inverse side of a bidirectional many-to-many by writing the owning collection', function (): void {
    [$reader, $entityManager] = rig();

    $post = new Post();
    $post->setTitle('Alpha');

    $category = new Category();
    $category->setName('news');

    $entityManager->persist($post);
    $entityManager->persist($category);
    $entityManager->flush();

    $reader->attach(new Relationship($category, 'posts'), $post);
    $entityManager->flush();
    $entityManager->clear();

    $reloaded = $entityManager->find(Category::class, $category->getId());
    assert($reloaded instanceof Category);

    expect(relatedIds($reader, $reloaded, 'posts'))->toBe([(string) $post->getId()]);
});

it('detaches from the inverse side of a bidirectional many-to-many', function (): void {
    [$reader, $entityManager] = rig();

    $post = new Post();
    $post->setTitle('Alpha');

    $category = new Category();
    $category->setName('news');

    $entityManager->persist($post);
    $entityManager->persist($category);
    $entityManager->flush();

    $reader->attach(new Relationship($category, 'posts'), $post);
    $entityManager->flush();

    $reader->detach(new Relationship($category, 'posts'), $post);
    $entityManager->flush();
    $entityManager->clear();

    $reloaded = $entityManager->find(Category::class, $category->getId());
    assert($reloaded instanceof Category);

    expect(relatedIds($reader, $reloaded, 'posts'))->toBe([]);
});

it('associates and dissociates a one-to-many by pointing the target at the owner', function (): void {
    [$reader, $entityManager] = rig();

    $author = new Author();
    $author->setName('Ada');

    $post = new Post();
    $post->setTitle('Alpha');

    $entityManager->persist($author);
    $entityManager->persist($post);
    $entityManager->flush();

    $reader->attach(new Relationship($author, 'posts'), $post);
    $entityManager->flush();

    expect($post->getAuthor())->toBe($author)
        ->and(relatedIds($reader, $author, 'posts'))->toBe([(string) $post->getId()]);

    $reader->detach(new Relationship($author, 'posts'), $post);
    $entityManager->flush();

    expect($post->getAuthor())->toBeNull()
        ->and(relatedIds($reader, $author, 'posts'))->toBe([]);
});

it('detaches a related record handed in as an uninitialised reference', function (): void {
    [$reader, $entityManager] = rig();

    $post = new Post();
    $post->setTitle('Alpha');

    $tag = new Tag();
    $tag->setName('php');

    $entityManager->persist($post);
    $entityManager->persist($tag);
    $entityManager->flush();

    $reader->attach(new Relationship($post, 'tags'), $tag);
    $entityManager->flush();

    $postId = $post->getId();
    $tagId = $tag->getId();
    assert($tagId !== null);

    $entityManager->clear();

    $reloaded = $entityManager->find(Post::class, $postId);
    assert($reloaded instanceof Post);

    $reference = $reader->getReference(Tag::class, $tagId);

    expect($entityManager->isUninitializedObject($reference))->toBeTrue();

    $reader->detach(new Relationship($reloaded, 'tags'), $reference);
    $entityManager->flush();

    expect(relatedIds($reader, $reloaded, 'tags'))->toBe([]);
});

it('refuses to dissociate where the mapping forbids it', function (): void {
    [$reader, $entityManager] = rig();

    $post = new Post();
    $post->setTitle('Alpha');

    $section = new PostSection();
    $section->setHeading('one');
    $section->setPost($post);
    $post->getSections()->add($section);

    $comment = new Comment();
    $comment->setPost($post);

    $reply = new Reply();
    $reply->setPost($post);

    foreach ([$post, $section, $comment, $reply] as $entity) {
        $entityManager->persist($entity);
    }

    $entityManager->flush();

    expect(fn () => $reader->detach(new Relationship($post, 'sections'), $section))->toThrow(LogicException::class, 'orphanRemoval')
        ->and(fn () => $reader->detach(new Relationship($post, 'comments'), $comment))->toThrow(LogicException::class, 'nullable: false')
        ->and(fn () => $reader->detach(new Relationship($post, 'replies'), $reply))->toThrow(LogicException::class, 'accept null')
        ->and(fn () => $reader->assertRelationshipAcceptsDetach(new Relationship($post, 'sections')))->toThrow(LogicException::class, 'DeleteAction')
        ->and(fn () => $reader->assertRelationshipAcceptsDetach(new Relationship($post, 'tags')))->not->toThrow(LogicException::class);
});

it('leaves an attach of an already related record alone', function (): void {
    [$reader, $entityManager] = rig();

    $post = new Post();
    $post->setTitle('Alpha');

    $tag = new Tag();
    $tag->setName('php');

    $entityManager->persist($post);
    $entityManager->persist($tag);
    $entityManager->flush();

    $reader->attach(new Relationship($post, 'tags'), $tag);
    $entityManager->flush();
    $reader->attach(new Relationship($post, 'tags'), $tag);
    $entityManager->flush();

    expect(relatedIds($reader, $post, 'tags'))->toBe([(string) $tag->getId()])
        ->and($post->getTags())->toHaveCount(1);
});
