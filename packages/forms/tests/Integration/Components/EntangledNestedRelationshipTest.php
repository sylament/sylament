<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Author;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Handle;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Schema;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/*
 * A relation-backed field nested inside an entangled layout component, against a
 * real Doctrine entity manager: `Section::relationship('author')` maps the post's
 * author wholesale, and the `Select` inside it maps the *author's* own handle. The
 * record path map drops every path beneath the section, so the select's hooks only
 * fire because the section routes them — and they only resolve their target
 * because the section re-roots its child schema onto the author.
 */

/**
 * Bind a `data`-bag host to a post, validator-fed so `getData()` can run its
 * validation pass.
 *
 * @return array{Schema, FieldRenderHost}
 */
function wireEntangledAuthorForm(Section $section, Post $post): array
{
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator(test()->getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->record($post)->components([$section]);

    $host->cacheSchema('schema', $schema);
    $schema->getComponents();

    return [$schema, $host];
}

/**
 * The entangled section under test: the author's name beside a select over the
 * author's own handle association.
 */
function makeEntangledAuthorSection(): Section
{
    return Section::make()
        ->relationship('author')
        ->components([
            TextInput::make('name'),
            Select::make('handle')->relationship(titleAttribute: 'name'),
        ]);
}

/**
 * A flushed post authored by Ada, whose handle is `ada`, with `bob` available as
 * the other option. The identity map is left intact, so every entity below is
 * managed.
 *
 * @return array{Post, Author, Handle, Handle}
 */
function seedHandledPost(EntityManagerInterface $entityManager): array
{
    $adaHandle = new Handle('ada');
    $bobHandle = new Handle('bob');

    $ada = new Author();
    $ada->setName('Ada');
    $ada->setHandle($adaHandle);

    $post = new Post();
    $post->setTitle('t');
    $post->setAuthor($ada);

    $entityManager->persist($adaHandle);
    $entityManager->persist($bobHandle);
    $entityManager->persist($ada);
    $entityManager->persist($post);
    $entityManager->flush();

    return [$post, $ada, $adaHandle, $bobHandle];
}

it('fills a nested relation select off the entangled record', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, , $adaHandle, $bobHandle] = seedHandledPost($entityManager);

    $section = makeEntangledAuthorSection();
    [$schema, $host] = wireEntangledAuthorForm($section, $post);

    $schema->fill($post);

    $select = $section->getChildSchema()?->getComponent('handle');

    expect($host->data['author']['name'])->toBe('Ada')
        ->and($host->data['author']['handle'])->toBe((string) $adaHandle->getId())
        ->and($select)->toBeInstanceOf(Select::class)
        ->and($select instanceof Select ? $select->getOptions() : [])->toBe([
            $adaHandle->getId() => 'ada',
            $bobHandle->getId() => 'bob',
        ]);
});

it('lands a changed selection on the entangled record through getData', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $ada, , $bobHandle] = seedHandledPost($entityManager);

    $section = makeEntangledAuthorSection();
    [$schema, $host] = wireEntangledAuthorForm($section, $post);

    $schema->fill($post);

    $host->data['author']['name'] = 'Ada Lovelace';
    $host->data['author']['handle'] = (string) $bobHandle->getId();

    expect($schema->getData())->toBe($post)
        ->and($post->getAuthor())->toBe($ada)
        ->and($ada->getName())->toBe('Ada Lovelace')
        ->and($ada->getHandle())->toBe($entityManager->find(Handle::class, $bobHandle->getId()));

    $entityManager->flush();
    $entityManager->clear();

    $reloaded = $entityManager->find(Author::class, $ada->getId());

    expect($reloaded)->toBeInstanceOf(Author::class)
        ->and($reloaded?->getHandle()?->getName())->toBe('bob');
});

it('nulls the entangled record association on an empty selection', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $ada] = seedHandledPost($entityManager);

    $section = makeEntangledAuthorSection();
    [$schema, $host] = wireEntangledAuthorForm($section, $post);

    $schema->fill($post);

    $host->data['author']['handle'] = '';

    expect($schema->getData())->toBe($post)
        ->and($ada->getHandle())->toBeNull();
});
