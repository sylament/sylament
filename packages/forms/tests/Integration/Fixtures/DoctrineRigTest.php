<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Author;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Tag;
use Sylament\Forms\Tests\Fixtures\Doctrine\EntityManagerFactory;

it('boots an entity manager service with the fixture schema', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    $author = new Author();
    $author->setName('Ada');
    EntityManagerFactory::seed($entityManager, $author);

    $reloaded = $entityManager->find(Author::class, $author->getId());

    expect($reloaded)->toBeInstanceOf(Author::class)
        ->and($reloaded->getName())->toBe('Ada');
});

it('round-trips a many-to-many tag selection', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    $tag = new Tag();
    $tag->setName('php');

    $post = new Post();
    $post->setTitle('t');
    $post->addTag($tag);

    EntityManagerFactory::seed($entityManager, $tag, $post);

    $reloaded = $entityManager->find(Post::class, $post->getId());

    expect($reloaded->getTags()->count())->toBe(1)
        ->and($reloaded->getTags()->first()->getName())->toBe('php');
});
