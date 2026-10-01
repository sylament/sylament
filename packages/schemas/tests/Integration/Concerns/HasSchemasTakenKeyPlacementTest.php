<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Concerns;

use Doctrine\ORM\EntityManager;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;
use Sylament\Schemas\Tests\Fixtures\Doctrine\CompositeTargetRig;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostTranslation;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Redirect;
use Sylament\Schemas\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Schemas\Tests\Fixtures\Doctrine\IdentifierGuardRig;
use Sylament\Schemas\Tests\Fixtures\Doctrine\KeyShapeRig;
use Sylament\Support\Tests\Doctrine\RecordingMiddleware;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/*
 * Where a taken key's message lands: under every identifier field that is no
 * association and that a component of the schema maps, in identifier order —
 * the reader answers them all, the host keeps the mapped ones — and, when the
 * schema maps none, where it always did. The rigs store a redirect
 * `example.com` + `/a`, the translation of post 1 into `en`, and a triplet
 * `eu` + 1 + `c`. Each lookup is one primary-key SELECT on every platform.
 */

/**
 * @return array{Schema, StateHostFixture}
 */
function takenKeyPlacementSchema(object $record, EntityManager $entityManager, Component ...$components): array
{
    $host = new StateHostFixture();
    $host->setSchemaStateValidator(test()->getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->record($record)->components($components);
    $schema->setAssociationReader(new AssociationReader($entityManager));

    $host->cacheSchema('schema', $schema);
    $schema->fill($record);

    return [$schema, $host];
}

/**
 * @return array<string, list<string>>
 */
function takenKeyPlacementErrors(Schema $schema): array
{
    try {
        $schema->getData();
    } catch (ValidationException $validationException) {
        return $validationException->getErrors();
    }

    return [];
}

it('files a taken scalar pair under both halves the schema maps, in identifier order, after one lookup', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);

    [$schema, $host] = takenKeyPlacementSchema(new Redirect(), $entityManager, StateField::make('path'), StateField::make('host'), StateField::make('label'));

    $host->data['host'] = 'example.com';
    $host->data['path'] = '/a';
    $host->data['label'] = 'Again';
    $recorder->reset();

    expect(takenKeyPlacementErrors($schema))->toBe([
        'data.host' => ['This value is already used.'],
        'data.path' => ['This value is already used.'],
    ])
        ->and($host->getErrorsForSchemaStatePath('data.path'))->toBe(['This value is already used.'])
        ->and($recorder->normalizedStatements())->toHaveCount(1)
        ->and($recorder->normalizedStatements()[0])->toContain('FROM Redirect p0 WHERE p0.host = ? AND p0.path = ?');
});

it('files a taken scalar pair under the one half the schema maps', function (): void {
    $entityManager = IdentifierGuardRig::create();
    $redirect = new Redirect();
    $redirect->setPath('/a');

    [$schema, $host] = takenKeyPlacementSchema($redirect, $entityManager, StateField::make('host'), StateField::make('label'));

    $host->data['host'] = 'example.com';
    $host->data['label'] = 'Again';

    expect(takenKeyPlacementErrors($schema))->toBe(['data.host' => ['This value is already used.']]);
});

it('files a taken scalar pair at the schema path when the schema maps neither half', function (): void {
    $entityManager = IdentifierGuardRig::create();
    $redirect = new Redirect();
    $redirect->setHost('example.com');
    $redirect->setPath('/a');

    [$schema, $host] = takenKeyPlacementSchema($redirect, $entityManager, StateField::make('label'));

    $host->data['label'] = 'Again';

    expect(takenKeyPlacementErrors($schema))->toBe(['data' => ['This value is already used.']]);
});

it('files a taken derived identity under its scalar half alone', function (): void {
    $entityManager = IdentifierGuardRig::create();
    $translation = new PostTranslation();
    $translation->setPost(CompositeTargetRig::post($entityManager, 1));

    [$schema, $host] = takenKeyPlacementSchema($translation, $entityManager, StateField::make('locale'), StateField::make('title'));

    $host->data['locale'] = 'en';
    $host->data['title'] = 'English again';

    expect(takenKeyPlacementErrors($schema))->toBe(['data.locale' => ['This value is already used.']]);
});

it('files a taken triple under the two of its fields the schema maps and not the third', function (): void {
    $entityManager = EntityManagerFactory::create();
    KeyShapeRig::seed($entityManager, 'three scalar fields');

    [$schema] = takenKeyPlacementSchema(KeyShapeRig::build($entityManager, 'three scalar fields'), $entityManager, StateField::make('region'), StateField::make('code'), StateField::make('label'));

    expect(takenKeyPlacementErrors($schema))->toBe([
        'data.region' => ['This value is already used.'],
        'data.code' => ['This value is already used.'],
    ]);
});
