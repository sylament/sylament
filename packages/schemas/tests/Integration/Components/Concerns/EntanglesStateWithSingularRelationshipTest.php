<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Components\Concerns;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Profile;

/*
 * Singular-relationship entanglement on a layout component, against a real
 * Doctrine entity manager: the component maps its whole subtree at the
 * association's property, so fill() reads the related record's leaves through the
 * load hook and getData() writes them back — mutating the record that is already
 * there, constructing one when it is not, and deleting it when the condition
 * turns the relationship off.
 */

/**
 * Wire one entangled component onto a `data`-bag host bound to the record, the
 * way a live host does.
 *
 * @return array{Schema, StateHostFixture}
 */
function wireEntangledComponents(object $record, Component ...$components): array
{
    $host = new StateHostFixture();
    $schema = Schema::make($host)->statePath('data')->record($record)->components($components);

    $host->cacheSchema('schema', $schema);
    $schema->getComponents();

    return [$schema, $host];
}

/**
 * A flushed post carrying a profile, both managed.
 *
 * @return array{Post, Profile}
 */
function seedProfiledPost(EntityManagerInterface $entityManager): array
{
    $post = new Post();
    $post->setTitle('t');

    $profile = new Profile();
    $profile->setBio('seeded');

    $post->setProfile($profile);

    $entityManager->persist($post);
    $entityManager->flush();

    return [$post, $profile];
}

function seedProfilelessPost(EntityManagerInterface $entityManager): Post
{
    $post = new Post();
    $post->setTitle('t');

    $entityManager->persist($post);
    $entityManager->flush();

    return $post;
}

it('fills the entangled subtree from the related record', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    [$post] = seedProfiledPost($entityManager);

    $section = Section::make()
        ->relationship('profile')
        ->components([StateField::make('bio')]);

    [$schema, $host] = wireEntangledComponents($post, $section);

    $schema->fill($post);

    expect($host->data['profile']['bio'])->toBe('seeded');
});

it('fills the entangled subtree with nulls when there is no related record', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    $post = seedProfilelessPost($entityManager);

    $section = Section::make()
        ->relationship('profile')
        ->components([StateField::make('bio')]);

    [$schema, $host] = wireEntangledComponents($post, $section);

    $schema->fill($post);

    expect($host->data['profile']['bio'])->toBeNull();
});

it('mutates the related record in place rather than replacing it', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    [$post, $profile] = seedProfiledPost($entityManager);

    $section = Section::make()
        ->relationship('profile')
        ->components([StateField::make('bio')]);

    [$schema, $host] = wireEntangledComponents($post, $section);

    $schema->fill($post);
    $host->data['profile']['bio'] = 'edited';

    expect($schema->getData())->toBe($post)
        ->and($post->getProfile())->toBe($profile)
        ->and($profile->getBio())->toBe('edited');

    $profileId = $profile->getId();

    $entityManager->flush();
    $entityManager->clear();

    $reloaded = $entityManager->find(Profile::class, $profileId);

    expect($reloaded)->toBeInstanceOf(Profile::class)
        ->and($reloaded->getBio())->toBe('edited');
});

it('constructs the related record when the record carries none', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    $post = seedProfilelessPost($entityManager);

    $section = Section::make()
        ->relationship('profile')
        ->components([StateField::make('bio')]);

    [$schema, $host] = wireEntangledComponents($post, $section);

    $schema->fill($post);
    $host->data['profile']['bio'] = 'fresh';

    $schema->getData();

    $profile = $post->getProfile();

    expect($profile)->toBeInstanceOf(Profile::class)
        ->and($profile->getBio())->toBe('fresh')
        ->and($profile->getPost())->toBe($post);

    $entityManager->flush();

    expect($profile->getId())->not->toBeNull();
});

it('deletes the related record when the relationship condition is false', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    [$post, $profile] = seedProfiledPost($entityManager);
    $profileId = $profile->getId();

    $section = Section::make()
        ->relationship('profile', condition: false)
        ->components([StateField::make('bio')]);

    [$schema, $host] = wireEntangledComponents($post, $section);

    $schema->fill($post);
    $host->data['profile']['bio'] = 'edited';

    $schema->getData();

    expect($post->getProfile())->toBeNull();

    $entityManager->flush();

    expect($entityManager->find(Profile::class, $profileId))->toBeNull();
});

it('refuses two components entangling the same relationship', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    [$post] = seedProfiledPost($entityManager);

    $one = Section::make()
        ->relationship('profile')
        ->components([StateField::make('bio')]);

    $two = Section::make()
        ->relationship('profile')
        ->components([StateField::make('bio')]);

    [$schema] = wireEntangledComponents($post, $one, $two);

    expect(fn (): Schema => $schema->fill($post))
        ->toThrow(LogicException::class, 'both map record path "profile"');
});

it('refuses a relationship the mapping does not declare an association at', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    [$post] = seedProfiledPost($entityManager);

    $section = Section::make()
        ->relationship('title')
        ->components([StateField::make('bio')]);

    [$schema] = wireEntangledComponents($post, $section);

    expect(fn (): Schema => $schema->fill($post))
        ->toThrow(LogicException::class, 'maps no association at "title"');
});

it('refuses a relationship that holds a collection', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    [$post] = seedProfiledPost($entityManager);

    $section = Section::make()
        ->relationship('sections')
        ->components([StateField::make('heading')]);

    [$schema] = wireEntangledComponents($post, $section);

    expect(fn (): Schema => $schema->fill($post))
        ->toThrow(LogicException::class, 'holds a collection');
});

it('refuses a relationship declared without a name', function (): void {
    expect(fn (): Section => Section::make()->relationship())
        ->toThrow(LogicException::class, 'names no association property');
});
