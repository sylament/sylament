<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostSection;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Tag;
use Sylament\Schemas\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

function seededPost(EntityManagerInterface $entityManager): Post
{
    $post = new Post();
    $post->setTitle('t');

    $a = new Tag();
    $a->setName('a');

    $b = new Tag();
    $b->setName('b');

    $post->addTag($a);
    $post->addTag($b);

    EntityManagerFactory::seed($entityManager, $a, $b, $post);

    $reloaded = $entityManager->find(Post::class, $post->getId());
    assert($reloaded instanceof Post);

    return $reloaded;
}

it('diffs a desired list into adder and remover calls without replacing the collection', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $accessor = $this::getContainer()->get(PropertyAccessorInterface::class);

    $post = seededPost($entityManager);
    $before = $post->getTags();
    $kept = $before->first();
    assert($kept instanceof Tag);

    $c = new Tag();
    $c->setName('c');

    $entityManager->persist($c);

    $accessor->setValue($post, 'tags', [$kept, $c]);

    expect($post->getTags())->toBe($before)
        ->toBeInstanceOf(PersistentCollection::class)
        ->and($post->getTags()->contains($kept))->toBeTrue()
        ->and($post->getTags()->contains($c))->toBeTrue()
        ->and($post->getTags()->count())->toBe(2);

    $postId = $post->getId();

    $entityManager->flush();
    $entityManager->clear();

    $reloaded = $entityManager->find(Post::class, $postId);
    assert($reloaded instanceof Post);

    expect($reloaded->getTags()->count())->toBe(2);
});

it('orphan-removes a section dropped from the desired list', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $accessor = $this::getContainer()->get(PropertyAccessorInterface::class);

    $post = new Post();
    $post->setTitle('t');

    $one = new PostSection();
    $one->setHeading('one');
    $one->setPosition(0);

    $two = new PostSection();
    $two->setHeading('two');
    $two->setPosition(1);

    $post->addSection($one);
    $post->addSection($two);

    $entityManager->persist($post);
    $entityManager->flush();

    $oneId = $one->getId();
    $twoId = $two->getId();

    $accessor->setValue($post, 'sections', [$one]);
    $entityManager->flush();

    expect($entityManager->find(PostSection::class, $twoId))->toBeNull()
        ->and($entityManager->find(PostSection::class, $oneId))->not->toBeNull();
});

it('maintains the owning side through the adder so a new child inserts on flush', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $accessor = $this::getContainer()->get(PropertyAccessorInterface::class);

    $post = new Post();
    $post->setTitle('t');

    $entityManager->persist($post);
    $entityManager->flush();

    $section = new PostSection();
    $section->setHeading('new');
    $section->setPosition(0);

    $accessor->setValue($post, 'sections', [$section]);

    expect($section->getPost())->toBe($post);

    $entityManager->flush();

    expect($section->getId())->not->toBeNull();
});
