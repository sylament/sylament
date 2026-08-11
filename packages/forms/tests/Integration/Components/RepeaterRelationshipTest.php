<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\PostSection;
use Sylament\Schemas\Schema;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/*
 * Repeater::relationship() against a real Doctrine entity manager: items are
 * keyed by the identity of the children the collection holds, and getData()
 * diffs the submitted items back onto that collection — updating the kept
 * instances in place, constructing the added ones, and dropping the missing ones
 * for orphanRemoval to delete. Nothing here is faked: the item keys are real
 * record keys, so no test calls Repeater::fake().
 */

/**
 * Bind a repeater to a record instance through a `data`-bag host, keyed and
 * validator-fed so `getData()` can run its validation pass.
 *
 * @return array{Schema, FieldRenderHost}
 */
function wireRelationshipRepeater(Repeater $repeater, object $record): array
{
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator(test()->getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->record($record)->components([$repeater]);

    $host->cacheSchema('schema', $schema);
    $schema->getComponents();

    return [$schema, $host];
}

/**
 * A flushed post carrying two ordered sections, every entity still managed.
 *
 * @return array{Post, PostSection, PostSection}
 */
function seedSectionedPost(EntityManagerInterface $entityManager): array
{
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

    return [$post, $one, $two];
}

/**
 * The item key the add action minted — the one key the submitted map carries
 * that the filled map did not.
 *
 * @param array<array-key, mixed> $items
 * @param list<string>            $filledKeys
 */
function addedItemKey(array $items, array $filledKeys): string
{
    return (string) array_first(array_diff(array_map(strval(...), array_keys($items)), $filledKeys));
}

it('fills loaded sections as record-keyed items', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $one, $two] = seedSectionedPost($entityManager);

    $repeater = Repeater::make('sections')
        ->relationship()
        ->components([TextInput::make('heading'), TextInput::make('body')]);

    [$schema, $host] = wireRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    expect(array_keys($host->data['sections']))->toBe(['record-' . $one->getId(), 'record-' . $two->getId()])
        ->and($host->data['sections']['record-' . $one->getId()]['heading'])->toBe('one')
        ->and($host->data['sections']['record-' . $two->getId()]['heading'])->toBe('two')
        ->and($host->data['sections']['record-' . $one->getId()]['body'])->toBeNull();
});

it('updates an existing child in place through getData', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $one] = seedSectionedPost($entityManager);

    $repeater = Repeater::make('sections')
        ->relationship()
        ->components([TextInput::make('heading')]);

    [$schema, $host] = wireRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    $host->data['sections']['record-' . $one->getId()]['heading'] = 'edited';

    expect($schema->getData())->toBe($post)
        ->and($one->getHeading())->toBe('edited')
        ->and($post->getSections()->contains($one))->toBeTrue()
        ->and($post->getSections()->count())->toBe(2);
});

it('creates an added item and deletes a removed one on flush', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $one, $two] = seedSectionedPost($entityManager);

    $postId = $post->getId();
    $twoId = $two->getId();

    $repeater = Repeater::make('sections')
        ->relationship()
        ->orderColumn('position')
        ->components([TextInput::make('heading'), TextInput::make('body')]);

    [$schema, $host] = wireRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    $filledKeys = array_map(strval(...), array_keys($host->data['sections']));

    $repeater->getAction($repeater->getDeleteActionName())?->__invoke(['item' => 'record-' . $twoId])->call();
    $repeater->getAction($repeater->getAddActionName())?->call();

    $addedKey = addedItemKey($host->data['sections'], $filledKeys);

    expect($addedKey)->not->toStartWith('record-');

    $host->data['sections'][$addedKey]['heading'] = 'three';

    expect($schema->getData())->toBe($post);

    $entityManager->flush();
    $entityManager->clear();

    $reloaded = $entityManager->find(Post::class, $postId);
    assert($reloaded instanceof Post);

    $headings = array_map(static fn (PostSection $section): string => $section->getHeading(), $reloaded->getSections()->toArray());

    expect($entityManager->find(PostSection::class, $twoId))->toBeNull()
        ->and($headings)->toBe(['one', 'three'])
        ->and($reloaded->getSections()->last()->getPost())->toBe($reloaded)
        ->and($one->getId())->not->toBeNull();
});

it('stamps the order column from item order', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $one, $two] = seedSectionedPost($entityManager);

    $repeater = Repeater::make('sections')
        ->relationship()
        ->orderColumn('position')
        ->components([TextInput::make('heading')]);

    [$schema, $host] = wireRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    $repeater->getAction($repeater->getMoveUpActionName())?->__invoke(['item' => 'record-' . $two->getId()])->call();

    expect(array_keys($host->data['sections']))->toBe(['record-' . $two->getId(), 'record-' . $one->getId()]);

    $schema->getData();

    expect($repeater->getOrderColumn())->toBe('position')
        ->and($two->getPosition())->toBe(0)
        ->and($one->getPosition())->toBe(1);
});

it('runs the mutate hooks at their moments', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $one, $two] = seedSectionedPost($entityManager);

    $repeater = Repeater::make('sections')
        ->relationship()
        ->mutateRelationshipDataBeforeFillUsing(static fn (array $state): array => [
            ...$state,
            'heading' => mb_strtoupper((string) $state['heading']),
        ])
        ->mutateRelationshipDataBeforeCreateUsing(static fn (array $state): array => [
            ...$state,
            'body' => 'created',
        ])
        ->mutateRelationshipDataBeforeSaveUsing(static fn (array $state, PostSection $record): array => [
            ...$state,
            'body' => 'saved ' . $record->getHeading(),
        ])
        ->components([TextInput::make('heading'), TextInput::make('body')]);

    [$schema, $host] = wireRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    expect($host->data['sections']['record-' . $one->getId()]['heading'])->toBe('ONE');

    $filledKeys = array_map(strval(...), array_keys($host->data['sections']));

    $repeater->getAction($repeater->getAddActionName())?->call();

    $addedKey = addedItemKey($host->data['sections'], $filledKeys);
    $host->data['sections'][$addedKey]['heading'] = 'three';

    $schema->getData();

    expect($one->getHeading())->toBe('ONE')
        ->and($one->getBody())->toBe('saved one')
        ->and($two->getBody())->toBe('saved two')
        ->and($post->getSections()->last()->getBody())->toBe('created');
});

it('skips creating an item whose before-create hook returns null', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = seedSectionedPost($entityManager);

    $postId = $post->getId();

    $repeater = Repeater::make('sections')
        ->relationship()
        ->mutateRelationshipDataBeforeCreateUsing(static fn (): null => null)
        ->components([TextInput::make('heading')]);

    [$schema, $host] = wireRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    $filledKeys = array_map(strval(...), array_keys($host->data['sections']));

    $repeater->getAction($repeater->getAddActionName())?->call();

    $host->data['sections'][addedItemKey($host->data['sections'], $filledKeys)]['heading'] = 'three';

    $schema->getData();

    expect($post->getSections()->count())->toBe(2);

    $entityManager->flush();
    $entityManager->clear();

    $reloaded = $entityManager->find(Post::class, $postId);
    assert($reloaded instanceof Post);

    expect(array_map(static fn (PostSection $section): string => $section->getHeading(), $reloaded->getSections()->toArray()))
        ->toBe(['one', 'two']);
});

it('leaves a child whose before-save hook returns null untouched and still kept', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $one, $two] = seedSectionedPost($entityManager);

    $repeater = Repeater::make('sections')
        ->relationship()
        ->mutateRelationshipDataBeforeSaveUsing(
            static fn (array $state, PostSection $record): ?array => ($record->getHeading() === 'two') ? null : $state,
        )
        ->components([TextInput::make('heading')]);

    [$schema, $host] = wireRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    $host->data['sections']['record-' . $one->getId()]['heading'] = 'edited one';
    $host->data['sections']['record-' . $two->getId()]['heading'] = 'edited two';

    $schema->getData();

    expect($one->getHeading())->toBe('edited one')
        ->and($two->getHeading())->toBe('two')
        ->and($post->getSections()->contains($two))->toBeTrue()
        ->and($post->getSections()->count())->toBe(2);
});

it('injects the child record by type into the before-save hook', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $one] = seedSectionedPost($entityManager);

    $repeater = Repeater::make('sections')
        ->relationship()
        ->mutateRelationshipDataBeforeSaveUsing(static fn (array $state, PostSection $section): array => [
            ...$state,
            'body' => 'saved ' . $section->getHeading(),
        ])
        ->components([TextInput::make('heading'), TextInput::make('body')]);

    [$schema] = wireRelationshipRepeater($repeater, $post);

    $schema->fill($post);
    $schema->getData();

    expect($one->getBody())->toBe('saved one');
});

it('keeps refusing an object collection without relationship()', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = seedSectionedPost($entityManager);

    [$schema] = wireRelationshipRepeater(
        Repeater::make('sections')->components([TextInput::make('heading')]),
        $post,
    );

    expect(fn (): Schema => $schema->fill($post))
        ->toThrow(LogicException::class, 'is an object collection')
        ->and(fn (): Schema => $schema->fill($post))
        ->toThrow(LogicException::class, 'Declare relationship() on this repeater');
});

it('refuses a relation-backed field declared inside a relationship item', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = seedSectionedPost($entityManager);

    [$schema] = wireRelationshipRepeater(
        Repeater::make('sections')->relationship()->components([
            TextInput::make('heading'),
            Select::make('post')->relationship(titleAttribute: 'title'),
        ]),
        $post,
    );

    $schema->fill($post);

    expect(fn (): object => $schema->getData())
        ->toThrow(LogicException::class, 'owns its record subtree');
});

it('refuses relationship() beside another item mode', function (): void {
    expect(fn (): Repeater => Repeater::make('sections')->simple(TextInput::make('heading'))->relationship())
        ->toThrow(LogicException::class, 'relationship() cannot compose with simple()')
        ->and(fn (): Repeater => Repeater::make('sections')->itemsAs(PostSection::class)->relationship())
        ->toThrow(LogicException::class, 'relationship() cannot compose with itemsAs()')
        ->and(fn (): Repeater => Repeater::make('sections')->relationship()->simple(TextInput::make('heading')))
        ->toThrow(LogicException::class, 'simple() cannot compose with relationship()')
        ->and(fn (): Repeater => Repeater::make('sections')->relationship()->itemsAs(PostSection::class))
        ->toThrow(LogicException::class, 'itemsAs() cannot compose with relationship()');
});
