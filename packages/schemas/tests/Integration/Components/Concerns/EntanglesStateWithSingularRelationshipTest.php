<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Components\Concerns;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use RuntimeException;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Byline;
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

it('writes a nested containers leaves rather than the container path itself', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    [$post, $profile] = seedProfiledPost($entityManager);

    $section = Section::make()
        ->relationship('profile')
        ->components([
            StateField::make('bio'),
            Section::make()->statePath('post')->components([StateField::make('title')]),
        ]);

    [$schema, $host] = wireEntangledComponents($post, $section);

    $schema->fill($post);

    expect($host->data['profile']['post']['title'])->toBe('t');

    $host->data['profile']['bio'] = 'edited';
    $host->data['profile']['post']['title'] = 'renamed';

    expect($schema->getData())->toBe($post)
        ->and($profile->getBio())->toBe('edited')
        ->and($post->getTitle())->toBe('renamed');
});

it('deletes a related record whose association is not mapped orphanRemoval', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    $post = new Post();
    $post->setTitle('t');

    $byline = new Byline();
    $byline->setCredit('seeded');

    $post->setByline($byline);

    $entityManager->persist($post);
    $entityManager->flush();

    $bylineId = $byline->getId();

    $section = Section::make()
        ->relationship('byline', condition: false)
        ->components([StateField::make('credit')]);

    [$schema, $host] = wireEntangledComponents($post, $section);

    $schema->fill($post);
    $host->data['byline']['credit'] = 'edited';

    $schema->getData();

    expect($post->getByline())->toBeNull();

    $entityManager->flush();

    expect($entityManager->find(Byline::class, $bylineId))->toBeNull();
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

it('re-roots the child schema onto the related record', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    [$post, $profile] = seedProfiledPost($entityManager);

    $field = StateField::make('bio');

    $section = Section::make()
        ->relationship('profile')
        ->components([$field]);

    [$schema] = wireEntangledComponents($post, $section);

    $schema->fill($post);

    expect($field->getRecord())->toBe($profile)
        ->and($field->getRecordClass())->toBe(Profile::class);
});

it('floors the re-rooted child schema at the target class when the record holds none', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    $post = seedProfilelessPost($entityManager);

    $field = StateField::make('bio');

    $section = Section::make()
        ->relationship('profile')
        ->components([$field]);

    [$schema] = wireEntangledComponents($post, $section);

    $schema->fill($post);

    expect($field->getRecord())->toBeNull()
        ->and($field->getRecordClass())->toBe(Profile::class);
});

it('stamps every child-schema key, decoration schemas included', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    [$post, $profile] = seedProfiledPost($entityManager);

    $section = Section::make()
        ->relationship('profile')
        ->components([StateField::make('bio')])
        ->belowContent([Text::make('note')]);

    wireEntangledComponents($post, $section);

    expect($section->getChildSchema(Section::BELOW_CONTENT_SCHEMA_KEY)?->getRecord())->toBe($profile);
});

it("keeps the section's own child-key configuration beneath the override", function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    [$post] = seedProfiledPost($entityManager);

    $section = Section::make()
        ->relationship('profile')
        ->components([StateField::make('bio')])
        ->belowContent([Text::make('note')]);

    wireEntangledComponents($post, $section);

    $belowContent = $section->getChildSchema(Section::BELOW_CONTENT_SCHEMA_KEY);

    expect($belowContent?->isInline())->toBeTrue()
        ->and($belowContent?->isEmbeddedInParentComponent())->toBeTrue();
});

it('fills a nested hooked path from the related record', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    [$post] = seedProfiledPost($entityManager);

    $section = Section::make()
        ->relationship('profile')
        ->components([
            StateField::make('bio')
                ->loadStateFromRelationshipsUsing(static fn (Profile $record): string => 'hooked:' . $record->getBio()),
            Section::make()->statePath('post')->components([StateField::make('title')]),
        ]);

    [$schema, $host] = wireEntangledComponents($post, $section);

    $schema->fill($post);

    expect($host->data['profile']['bio'])->toBe('hooked:seeded')
        ->and($host->data['profile']['post']['title'])->toBe('t');
});

it('routes a hooked path declared in any child-schema key', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    [$post] = seedProfiledPost($entityManager);

    $section = Section::make()
        ->relationship('profile')
        ->components([
            StateField::make('bio')->loadStateFromRelationshipsUsing(static fn (): string => 'from-default'),
        ])
        ->belowContent([
            StateField::make('post')
                ->loadStateFromRelationshipsUsing(static fn (Profile $record): string => 'post:' . $record->getPost()?->getTitle()),
        ]);

    [$schema, $host] = wireEntangledComponents($post, $section);

    $schema->fill($post);

    expect($host->data['profile']['bio'])->toBe('from-default')
        ->and($host->data['profile']['post'])->toBe('post:t');
});

it('saves a nested hooked path onto the related record', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    [$post, $profile] = seedProfiledPost($entityManager);

    $section = Section::make()
        ->relationship('profile')
        ->components([
            StateField::make('bio'),
            StateField::make('post')->saveRelationshipsUsing(static function (Profile $record, mixed $state): void {
                $record->setBio($record->getBio() . '+' . $state);
            }),
        ]);

    [$schema, $host] = wireEntangledComponents($post, $section);

    $schema->fill($post);

    $host->data['profile']['bio'] = 'edited';
    $host->data['profile']['post'] = 'from-client';

    expect($schema->getData())->toBe($post)
        ->and($profile->getBio())->toBe('edited+from-client');
});

it('runs no nested hooks when the relationship condition is off', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    [$post, $profile] = seedProfiledPost($entityManager);
    $profileId = $profile->getId();

    $section = Section::make()
        ->relationship('profile', condition: false)
        ->components([
            StateField::make('bio')->saveRelationshipsUsing(static function (): void {
                throw new RuntimeException('the nested save hook ran');
            }),
        ]);

    [$schema, $host] = wireEntangledComponents($post, $section);

    $schema->fill($post);
    $host->data['profile']['bio'] = 'edited';

    $schema->getData();

    expect($post->getProfile())->toBeNull();

    $entityManager->flush();

    expect($entityManager->find(Profile::class, $profileId))->toBeNull();
});
