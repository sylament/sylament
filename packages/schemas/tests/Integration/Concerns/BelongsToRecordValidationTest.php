<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Concerns;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\ConstrainedAuthor;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\ConstrainedPost;
use Sylament\Schemas\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/*
 * The record-sourced constraint tail of getData(): the mapped object is
 * validated after it is written, and each violation is spoken back in state
 * terms. Nothing here flushes — the tail throws over an already-mutated record,
 * which is the recorded cost of validating what the mapping produced.
 */

/**
 * Wire a host holding the container's validator — the injection a container-built
 * host gets through its `#[Required]` setter — so a schema on it can run the
 * record tail at all.
 *
 * @return array{Schema, StateHostFixture}
 */
function wireValidatedRecordSchema(object $record, Component ...$components): array
{
    $host = new StateHostFixture();
    $host->setSchemaStateValidator(test()->getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->record($record)->components($components);

    $host->cacheSchema('schema', $schema);
    $schema->fill($record);

    return [$schema, $host];
}

function constrainedPost(string $title = 'Original'): ConstrainedPost
{
    $author = new ConstrainedAuthor();
    $author->setName('Ada');

    $post = new ConstrainedPost();
    $post->setTitle($title);
    $post->setAuthor($author);

    return $post;
}

it('folds a record violation onto the state path of the component that maps it', function (): void {
    $post = constrainedPost();

    [$schema, $host] = wireValidatedRecordSchema($post, StateField::make('title'));

    $host->data['title'] = ConstrainedPost::REJECTED_TITLE;

    expect(fn (): object => $schema->getData())->toThrow(ValidationException::class)
        ->and($host->getErrorsForSchemaStatePath('data.title'))->toBe(['That title is taken.'])
        ->and($host->validatedSchemas)->toBe(['schema']);
});

it('carries the folded errors on the exception', function (): void {
    $post = constrainedPost();

    [$schema, $host] = wireValidatedRecordSchema($post, StateField::make('title'));

    $host->data['title'] = ConstrainedPost::REJECTED_TITLE;

    try {
        $schema->getData();
        $this->fail('Expected ValidationException');
    } catch (ValidationException $validationException) {
        expect($validationException->getErrors())->toBe(['data.title' => ['That title is taken.']]);
    }
});

it('folds a class-level violation at the schemas own state path', function (): void {
    $post = constrainedPost();

    [$schema, $host] = wireValidatedRecordSchema($post, StateField::make('title'));

    $host->data['title'] = ConstrainedPost::REJECTED_POST;

    expect(fn (): object => $schema->getData())->toThrow(ValidationException::class)
        ->and($host->getErrorsForSchemaStatePath('data'))->toBe(['That post is not publishable.'])
        ->and($host->getErrorsForSchemaStatePath('data.title'))->toBe([]);
});

it('folds a violation on an unmapped property at the schemas own state path', function (): void {
    $post = constrainedPost();

    [$schema, $host] = wireValidatedRecordSchema($post, StateField::make('title'));

    $host->data['title'] = ConstrainedPost::REJECTED_SLUG;

    expect(fn (): object => $schema->getData())->toThrow(ValidationException::class)
        ->and($host->getErrorsForSchemaStatePath('data'))->toBe(['That slug is taken.']);
});

it('folds a cascaded violation onto the nested components state path', function (): void {
    $post = constrainedPost();

    [$schema, $host] = wireValidatedRecordSchema(
        $post,
        StateField::make('title'),
        Section::make()->statePath('author')->components([StateField::make('name')]),
    );

    $host->data['author']['name'] = 'Al';

    expect(fn (): object => $schema->getData())->toThrow(ValidationException::class)
        ->and($host->getErrorsForSchemaStatePath('data.author.name'))->toBe(['The author name is too short.']);
});

it('returns the mapped record when the record validates', function (): void {
    $post = constrainedPost();

    [$schema, $host] = wireValidatedRecordSchema($post, StateField::make('title'));

    $host->data['title'] = 'Accepted';

    expect($schema->getData())->toBe($post)
        ->and($post->getTitle())->toBe('Accepted')
        ->and($host->getErrorsForSchemaStatePath('data'))->toBe([]);
});

it('leaves the record mutated by the mapping that preceded the failure', function (): void {
    $post = constrainedPost();

    [$schema, $host] = wireValidatedRecordSchema($post, StateField::make('title'));

    $host->data['title'] = ConstrainedPost::REJECTED_TITLE;

    try {
        $schema->getData();
    } catch (ValidationException) {
    }

    expect($post->getTitle())->toBe(ConstrainedPost::REJECTED_TITLE);
});

it('never flushes the record it refused', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    $post = constrainedPost();
    EntityManagerFactory::seed($entityManager, $post);

    $managed = $entityManager->find(ConstrainedPost::class, $post->getId());

    [$schema, $host] = wireValidatedRecordSchema($managed, StateField::make('title'));

    $host->data['title'] = ConstrainedPost::REJECTED_TITLE;

    try {
        $schema->getData();
    } catch (ValidationException) {
    }

    expect($managed->getTitle())->toBe(ConstrainedPost::REJECTED_TITLE);

    $entityManager->clear();

    expect($entityManager->find(ConstrainedPost::class, $post->getId())->getTitle())->toBe('Original');
});

it('skips the record tail on a host built outside the container', function (): void {
    $post = constrainedPost();

    $host = new StateHostFixture();
    $schema = Schema::make($host)->statePath('data')->record($post)->components([StateField::make('title')]);
    $host->cacheSchema('schema', $schema);
    $schema->fill($post);

    $host->data['title'] = ConstrainedPost::REJECTED_TITLE;

    expect($schema->getData())->toBe($post)
        ->and($post->getTitle())->toBe(ConstrainedPost::REJECTED_TITLE);
});
