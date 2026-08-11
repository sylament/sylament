<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\PostSection;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Schema;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/*
 * The record-validation tail meeting a relationship repeater: a violation the
 * child entity raises arrives keyed by the collection position it sat at, and
 * the repeater's own item keys are what turns that back into the state path of
 * the field inside the item.
 */

/**
 * @return array{Schema, FieldRenderHost}
 */
function wireValidatedRelationshipRepeater(Repeater $repeater, object $record): array
{
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator(test()->getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->record($record)->components([$repeater]);

    $host->cacheSchema('schema', $schema);
    $schema->getComponents();

    return [$schema, $host];
}

/**
 * @return array{Post, PostSection, PostSection}
 */
function seedValidatedSectionedPost(EntityManagerInterface $entityManager): array
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

it('reports its item keys in state order before any save', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $one, $two] = seedValidatedSectionedPost($entityManager);

    $repeater = Repeater::make('sections')
        ->relationship()
        ->components([TextInput::make('heading')]);

    [$schema] = wireValidatedRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    expect($repeater->getItemKeys())->toBe(['record-' . $one->getId(), 'record-' . $two->getId()]);
});

it('keys its item keys by the collection key a save left behind', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $one, $two] = seedValidatedSectionedPost($entityManager);

    $repeater = Repeater::make('sections')
        ->relationship()
        ->orderColumn('position')
        ->components([TextInput::make('heading')]);

    [$schema] = wireValidatedRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    $repeater->getAction($repeater->getMoveUpActionName())?->__invoke(['item' => 'record-' . $two->getId()])->call();

    $schema->getData();

    expect($repeater->getItemKeys())->toBe([
        'record-' . $one->getId(),
        'record-' . $two->getId(),
    ]);
});

it('folds a child violation onto the item field that maps it', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $one] = seedValidatedSectionedPost($entityManager);

    $repeater = Repeater::make('sections')
        ->relationship()
        ->components([TextInput::make('heading')]);

    [$schema, $host] = wireValidatedRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    $host->data['sections']['record-' . $one->getId()]['heading'] = PostSection::REJECTED_HEADING;

    expect(fn (): object => $schema->getData())->toThrow(ValidationException::class)
        ->and($host->getErrorsForSchemaStatePath('data.sections.record-' . $one->getId() . '.heading'))
        ->toBe(['That heading is taken.'])
        ->and($host->getErrorsForSchemaStatePath('data.sections'))->toBe([]);
});

it('folds the violation of a later child onto that childs item', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, , $two] = seedValidatedSectionedPost($entityManager);

    $repeater = Repeater::make('sections')
        ->relationship()
        ->components([TextInput::make('heading')]);

    [$schema, $host] = wireValidatedRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    $host->data['sections']['record-' . $two->getId()]['heading'] = PostSection::REJECTED_HEADING;

    expect(fn (): object => $schema->getData())->toThrow(ValidationException::class)
        ->and($host->getErrorsForSchemaStatePath('data.sections.record-' . $two->getId() . '.heading'))
        ->toBe(['That heading is taken.']);
});

it('holds the attribution when a deletion left the collection keyed sparsely', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $one, $two] = seedValidatedSectionedPost($entityManager);

    $repeater = Repeater::make('sections')
        ->relationship()
        ->components([TextInput::make('heading')]);

    [$schema, $host] = wireValidatedRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    $repeater->getAction($repeater->getDeleteActionName())?->__invoke(['item' => 'record-' . $one->getId()])->call();

    $host->data['sections']['record-' . $two->getId()]['heading'] = PostSection::REJECTED_HEADING;

    expect(fn (): object => $schema->getData())->toThrow(ValidationException::class)
        ->and($host->getErrorsForSchemaStatePath('data.sections.record-' . $two->getId() . '.heading'))
        ->toBe(['That heading is taken.'])
        ->and($host->getErrorsForSchemaStatePath('data.sections'))->toBe([]);
});

it('holds the attribution when a reorder left the collection in its old order', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $one, $two] = seedValidatedSectionedPost($entityManager);

    $repeater = Repeater::make('sections')
        ->relationship()
        ->orderColumn('position')
        ->components([TextInput::make('heading')]);

    [$schema, $host] = wireValidatedRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    $repeater->getAction($repeater->getMoveUpActionName())?->__invoke(['item' => 'record-' . $two->getId()])->call();

    $host->data['sections']['record-' . $two->getId()]['heading'] = PostSection::REJECTED_HEADING;

    expect(fn (): object => $schema->getData())->toThrow(ValidationException::class)
        ->and($host->getErrorsForSchemaStatePath('data.sections.record-' . $two->getId() . '.heading'))
        ->toBe(['That heading is taken.'])
        ->and($host->getErrorsForSchemaStatePath('data.sections.record-' . $one->getId() . '.heading'))
        ->toBe([]);
});

it('folds a violation on a property no item field maps onto the item itself', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $one] = seedValidatedSectionedPost($entityManager);

    $repeater = Repeater::make('sections')
        ->relationship()
        ->components([TextInput::make('heading')]);

    [$schema, $host] = wireValidatedRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    $host->data['sections']['record-' . $one->getId()]['heading'] = PostSection::REJECTED_BODY;

    expect(fn (): object => $schema->getData())->toThrow(ValidationException::class)
        ->and($host->getErrorsForSchemaStatePath('data.sections.record-' . $one->getId()))
        ->toBe(['That body is too short.'])
        ->and($host->getErrorsForSchemaStatePath('data.sections.record-' . $one->getId() . '.body'))->toBe([]);
});

it('folds a class-level violation on a child onto the item itself', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $one] = seedValidatedSectionedPost($entityManager);

    $repeater = Repeater::make('sections')
        ->relationship()
        ->components([TextInput::make('heading')]);

    [$schema, $host] = wireValidatedRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    $host->data['sections']['record-' . $one->getId()]['heading'] = PostSection::REJECTED_SECTION;

    expect(fn (): object => $schema->getData())->toThrow(ValidationException::class)
        ->and($host->getErrorsForSchemaStatePath('data.sections.record-' . $one->getId()))
        ->toBe(['That section is not publishable.'])
        ->and($host->getErrorsForSchemaStatePath('data.sections'))->toBe([]);
});

it('leaves the rejected child mutated and unflushed', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $one] = seedValidatedSectionedPost($entityManager);

    $sectionId = $one->getId();

    $repeater = Repeater::make('sections')
        ->relationship()
        ->components([TextInput::make('heading')]);

    [$schema, $host] = wireValidatedRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    $host->data['sections']['record-' . $sectionId]['heading'] = PostSection::REJECTED_HEADING;

    try {
        $schema->getData();
    } catch (ValidationException) {
    }

    expect($one->getHeading())->toBe(PostSection::REJECTED_HEADING);

    $entityManager->clear();

    expect($entityManager->find(PostSection::class, $sectionId)->getHeading())->toBe('one');
});
