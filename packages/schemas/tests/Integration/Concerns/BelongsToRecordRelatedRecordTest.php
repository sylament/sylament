<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Concerns;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Binder;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\ConstrainedAuthor;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\ConstrainedPost;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Spine;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/*
 * The record a singular relationship holds, reached through the component that
 * owns it: a violation raised inside it lands on the field that maps it rather
 * than on the relationship as a whole, its key is guarded like the record's
 * own, and once the host has flushed, the relationship's state is read back off
 * the record it now holds.
 */

/**
 * A `data`-bag host with the container's validator, holding one schema over a
 * record the container's entity manager keeps.
 *
 * @return array{Schema, StateHostFixture}
 */
function relatedRecordSchema(object $record, Component ...$components): array
{
    $host = new StateHostFixture();
    $host->setSchemaStateValidator(test()->getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->record($record)->components($components);

    $host->cacheSchema('schema', $schema);
    $schema->fill($record);

    return [$schema, $host];
}

/**
 * @return array<string, list<string>>
 */
function relatedRecordErrors(Schema $schema): array
{
    try {
        $schema->getData();
    } catch (ValidationException $validationException) {
        return $validationException->getErrors();
    }

    return [];
}

function relatedRecordBinder(EntityManagerInterface $entityManager, ?string $spineCode): Binder
{
    $binder = new Binder();
    $binder->setTitle('Bound');

    if ($spineCode !== null) {
        $spine = new Spine();
        $spine->setCode($spineCode);
        $spine->setLabel('stored spine');
        $binder->setSpine($spine);
    }

    $entityManager->persist($binder);
    $entityManager->flush();

    return $binder;
}

function relatedRecordSpineSection(): Section
{
    return Section::make()->relationship('spine')->components([
        StateField::make('code'),
        StateField::make('label'),
    ]);
}

it('files a violation raised inside a singular relationship at the field that maps it', function (): void {
    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);
    $author = new ConstrainedAuthor();
    $author->setName('Ada');

    $post = new ConstrainedPost();
    $post->setTitle('Original');
    $post->setAuthor($author);

    $entityManager->persist($post);
    $entityManager->flush();

    [$schema, $host] = relatedRecordSchema(
        $post,
        StateField::make('title'),
        Section::make()->relationship('author')->components([StateField::make('name')]),
    );

    $host->data['author']['name'] = 'Al';

    expect(relatedRecordErrors($schema))->toBe(['data.author.name' => ['The author name is too short.']])
        ->and($host->getErrorsForSchemaStatePath('data.author.name'))->toBe(['The author name is too short.']);
});

it('still files at the relationship a violation raised at a path no component inside it maps', function (): void {
    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);
    $author = new ConstrainedAuthor();
    $author->setName('Ada');

    $post = new ConstrainedPost();
    $post->setTitle('Original');
    $post->setAuthor($author);

    $entityManager->persist($post);
    $entityManager->flush();

    [$schema] = relatedRecordSchema(
        $post,
        StateField::make('title'),
        Section::make()->relationship('author')->components([]),
    );

    $author->setName('Al');

    expect(relatedRecordErrors($schema))->toBe(['data.author' => ['The author name is too short.']]);
});

it('refuses a moved key on the record a singular relationship holds, at its field', function (): void {
    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);
    $binder = relatedRecordBinder($entityManager, 'SP-1');

    [$schema, $host] = relatedRecordSchema($binder, StateField::make('title'), relatedRecordSpineSection());

    $host->data['spine']['code'] = 'SP-9';

    expect(relatedRecordErrors($schema))->toBe(['data.spine.code' => ['This value cannot be changed.']])
        ->and($binder->getSpine()?->getCode())->toBe('SP-1');
});

it('refuses a taken key on the record a singular relationship constructs, at its field', function (): void {
    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    relatedRecordBinder($entityManager, 'SP-1');
    $binder = relatedRecordBinder($entityManager, null);

    [$schema, $host] = relatedRecordSchema($binder, StateField::make('title'), relatedRecordSpineSection());

    $host->data['spine'] = ['code' => 'SP-1', 'label' => 'constructed'];

    expect(relatedRecordErrors($schema))->toBe(['data.spine.code' => ['This value is already used.']]);
});

it('reads a singular relationship back off the record once the host has flushed, and nothing else', function (): void {
    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);
    $binder = relatedRecordBinder($entityManager, null);
    $hydrated = 0;

    [$schema, $host] = relatedRecordSchema(
        $binder,
        StateField::make('title'),
        Section::make()->relationship('spine')->components([
            StateField::make('code'),
            StateField::make('label')->afterStateHydrated(static function () use (&$hydrated): void {
                $hydrated++;
            }),
        ]),
    );

    $host->data['spine'] = ['code' => 'SP-7', 'label' => 'constructed'];

    expect($schema->getData())->toBe($binder);

    $entityManager->flush();

    $spine = $binder->getSpine() ?? throw new LogicException('getData() constructs the spine.');
    $spine->setLabel('relabelled since');
    $host->data['title'] = 'Edited since';
    $hydrated = 0;

    $schema->loadStateFromRelationships();

    expect($host->data)->toBe(['title' => 'Edited since', 'spine' => ['code' => 'SP-7', 'label' => 'relabelled since']])
        ->and($hydrated)->toBe(1);
});
