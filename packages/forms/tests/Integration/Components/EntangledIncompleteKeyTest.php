<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\LintelCrest;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\LintelLodge;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Schema;
use Sylament\Support\Tests\Doctrine\RecordingMiddleware;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/*
 * Real form fields inside a singular relationship constructing a record keyed
 * by two assigned halves: a half over a non-nullable property is required on
 * its own, so a blank one is a field error, while one over a nullable property
 * is not, and a blank it submits leaves the key incomplete — which the save
 * refuses by name before anything is written, rather than inserting a row no
 * key can be minted for.
 */

/**
 * @return array{Schema, FieldRenderHost}
 */
function entangledIncompleteKeyWire(LintelLodge $lodge): array
{
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator(test()->getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->record($lodge)->components([
        TextInput::make('name'),
        Group::make()->relationship('crest')->components([TextInput::make('a'), TextInput::make('b'), TextInput::make('label')]),
    ]);

    $host->cacheSchema('schema', $schema);
    $schema->fill($lodge);

    return [$schema, $host];
}

it('refuses by name a constructed record whose nullable key half the user left blank, writing nothing', function (string $typed): void {
    $lodge = new LintelLodge();

    [$schema, $host] = entangledIncompleteKeyWire($lodge);
    $host->data = ['name' => 'Lodge', 'crest' => ['a' => 'north', 'b' => $typed, 'label' => 'Crest']];

    $recorder = $this::getContainer()->get(RecordingMiddleware::class);
    $recorder->reset();

    expect(fn (): object => $schema->getData())->toThrow(
        LogicException::class,
        sprintf('The [crest] relationship is saving a new [%s] whose identifier is incomplete', LintelCrest::class),
    );

    expect($recorder->statements())->toBe([])
        ->and($this::getContainer()->get(EntityManagerInterface::class)->getConnection()->fetchAllAssociative('SELECT a, b FROM LintelCrest'))->toBe([]);
})->with(['whitespace' => '   ', 'empty' => '']);

it('refuses a blank key half over a non-nullable property under its field, since the field is required on its own', function (): void {
    [$schema, $host] = entangledIncompleteKeyWire(new LintelLodge());
    $host->data = ['name' => 'Lodge', 'crest' => ['a' => '', 'b' => 'south', 'label' => 'Crest']];

    try {
        $schema->getData();
        $errors = null;
    } catch (ValidationException $validationException) {
        $errors = $validationException->getErrors();
    }

    expect($errors)->toBe(['data.crest.a' => ['This value should not be blank.']]);
});

it('saves a constructed record whose two key halves the user filled', function (): void {
    $lodge = new LintelLodge();

    [$schema, $host] = entangledIncompleteKeyWire($lodge);
    $host->data = ['name' => 'Lodge', 'crest' => ['a' => 'north', 'b' => 'south', 'label' => 'Crest']];

    expect($schema->getData())->toBe($lodge);

    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $entityManager->persist($lodge);
    $entityManager->flush();

    expect($entityManager->getConnection()->fetchAllAssociative('SELECT a, b, label FROM LintelCrest'))->toBe([['a' => 'north', 'b' => 'south', 'label' => 'Crest']]);
});
