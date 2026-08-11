<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Author;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostSection;
use Sylament\Schemas\Tests\Fixtures\Doctrine\EntityManagerFactory;

it('boots an entity manager service with the fixture schema', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    $author = new Author();
    $author->setName('Ada');
    EntityManagerFactory::seed($entityManager, $author);

    $reloaded = $entityManager->find(Author::class, $author->getId());

    expect($reloaded)->toBeInstanceOf(Author::class)
        ->and($reloaded->getName())->toBe('Ada');
});

it('cascades a section insert and orphan-removes on collection removal', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    $post = new Post();
    $post->setTitle('t');

    $section = new PostSection();
    $section->setHeading('h');
    $section->setPosition(0);

    $post->addSection($section);

    $entityManager->persist($post);
    $entityManager->flush();

    $sectionId = $section->getId();

    expect($sectionId)->not->toBeNull();

    $post->removeSection($section);
    $entityManager->flush();

    expect($entityManager->find(PostSection::class, $sectionId))->toBeNull();
});
