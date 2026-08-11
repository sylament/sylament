<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Author;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostSection;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Profile;
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

it('maintains the owning side when a profile is set on the post', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    $post = new Post();
    $post->setTitle('t');

    $profile = new Profile();
    $profile->setBio('b');

    $post->setProfile($profile);

    $entityManager->persist($post);
    $entityManager->flush();

    expect($profile->getId())->not->toBeNull()
        ->and($profile->getPost())->toBe($post);

    $profileId = $profile->getId();
    $entityManager->clear();

    $reloaded = $entityManager->find(Profile::class, $profileId);

    expect($reloaded)->toBeInstanceOf(Profile::class)
        ->and($reloaded->getPost()->getTitle())->toBe('t')
        ->and($reloaded->getPost()->getProfile())->toBe($reloaded);
});

it('orphan-removes the profile when it is detached from the post', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    $post = new Post();
    $post->setTitle('t');

    $profile = new Profile();
    $post->setProfile($profile);

    $entityManager->persist($post);
    $entityManager->flush();

    $profileId = $profile->getId();

    $post->setProfile(null);
    $entityManager->flush();

    expect($entityManager->find(Profile::class, $profileId))->toBeNull();
});
