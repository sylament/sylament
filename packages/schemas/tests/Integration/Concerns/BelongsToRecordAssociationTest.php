<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Concerns;

use LogicException;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\Tests\Fixtures\Components\RoutingSubtreeOwner;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Components\SubtreeOwner;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Author;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Post;

/*
 * The relationship hook pair, exercised through the real fill()/getData() walks
 * against a Doctrine entity. Nothing here persists — a hook mutates in memory,
 * which is the whole point of the single-flush shape: the walks route a path
 * through the component instead of through the property accessor.
 */

/**
 * Wire one component onto a `data`-bag host bound to a record, the way a live
 * host does, so both walks see a component tree rather than a detached leaf.
 *
 * @return array{Schema, StateHostFixture}
 */
function wireHookedComponent(Component $component, object $record): array
{
    $host = new StateHostFixture();
    $schema = Schema::make($host)->statePath('data')->record($record)->components([$component]);

    $host->cacheSchema('schema', $schema);
    $schema->getComponents();

    return [$schema, $host];
}

function seedPost(): Post
{
    $author = new Author();
    $author->setName('Ada');

    $post = new Post();
    $post->setAuthor($author);

    return $post;
}

it('fills a hooked path from the load hook instead of the record read', function (): void {
    $post = seedPost();

    $field = StateField::make('author')
        ->loadStateFromRelationshipsUsing(fn (Post $record): string => 'k-' . $record->getAuthor()?->getName());

    [$schema, $host] = wireHookedComponent($field, $post);

    $schema->fill($post);

    expect($host->data['author'])->toBe('k-Ada');
});

it('routes a hooked path through the save hook instead of setOnRecord', function (): void {
    $post = seedPost();
    $author = $post->getAuthor();

    $field = StateField::make('author')
        ->saveRelationshipsUsing(function (Post $record, mixed $state): void {
            $record->setTitle('hook:' . (is_string($state) ? $state : ''));
        });

    [$schema, $host] = wireHookedComponent($field, $post);

    $schema->fill($post);
    $host->data['author'] = 'Ada';

    expect($schema->getData())->toBe($post)
        ->and($post->getTitle())->toBe('hook:Ada')
        ->and($post->getAuthor())->toBe($author);
});

it('still refuses an unhooked object collection', function (): void {
    $post = seedPost();

    [$schema, $host] = wireHookedComponent(StateField::make('sections'), $post);

    $schema->fill($post);
    $host->data['sections'] = [['title' => 'Intro']];

    expect(fn (): object => $schema->getData())
        ->toThrow(LogicException::class, 'is an object collection');
});

it('refuses nullWhenBlank on a component with a save hook', function (): void {
    $post = seedPost();

    $owner = SubtreeOwner::make('author')
        ->nullWhenBlank()
        ->saveRelationshipsUsing(fn (): null => null)
        ->components([StateField::make('name')]);

    [$schema, $host] = wireHookedComponent($owner, $post);

    $schema->fill($post);
    $host->data['author'] = ['name' => ''];

    expect(fn (): object => $schema->getData())
        ->toThrow(LogicException::class, 'nullWhenBlank() alongside a relationship save hook');
});

it('refuses a hook on a container whose children carry the mapped paths', function (): void {
    $post = seedPost();

    $section = Section::make()
        ->statePath('author')
        ->saveRelationshipsUsing(fn (): null => null)
        ->components([StateField::make('name')]);

    [$schema] = wireHookedComponent($section, $post);

    expect(fn (): Schema => $schema->fill($post))
        ->toThrow(LogicException::class, 'ownsRecordSubtree()');
});

it('refuses a hook on a component with no state path', function (): void {
    $post = seedPost();

    $section = Section::make()
        ->loadStateFromRelationshipsUsing(fn (): string => 'never');

    [$schema] = wireHookedComponent($section, $post);

    expect(fn (): Schema => $schema->fill($post))
        ->toThrow(LogicException::class, 'names no record path');
});

it('still refuses a hook beneath an owner that does not route', function (): void {
    $post = seedPost();

    $owner = SubtreeOwner::make('sections')->components([
        StateField::make('title')->saveRelationshipsUsing(fn (): null => null),
    ]);

    [$schema] = wireHookedComponent($owner, $post);

    expect(fn (): Schema => $schema->fill($post))
        ->toThrow(LogicException::class, 'Declare relationship() on');
});

it('does not refuse a hook beneath a routing owner', function (): void {
    $post = seedPost();

    $owner = RoutingSubtreeOwner::make('sections')
        ->loadStateFromRelationshipsUsing(fn (): array => ['title' => 'Intro'])
        ->saveRelationshipsUsing(function (Post $record): void {
            $record->setTitle('routed');
        })
        ->components([
            StateField::make('title')->saveRelationshipsUsing(fn (): null => null),
        ]);

    [$schema, $host] = wireHookedComponent($owner, $post);

    $schema->fill($post);

    expect($host->data['sections'])->toBe(['title' => 'Intro'])
        ->and($schema->getData())->toBe($post)
        ->and($post->getTitle())->toBe('routed');
});

it('injects the container association reader into a container-built component', function (): void {
    $component = $this::getContainer()->get(Section::class);

    expect($component)->toBeInstanceOf(Section::class)
        ->and($component->getAssociationReader())->toBe($this::getContainer()->get(AssociationReader::class));
});
