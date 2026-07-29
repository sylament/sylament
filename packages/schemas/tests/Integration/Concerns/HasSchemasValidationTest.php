<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Concerns;

use Sylament\Schemas\Exceptions\ValidationException;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

it('fills the bag, records stickiness, and throws on a failing submit-path validation', function (): void {
    $host = $this->createLiveComponent('ValidationHost')->component();
    $schema = $host->getSchema();

    expect(fn () => $host->validateSchema($schema))
        ->toThrow(ValidationException::class)
        ->and($host->validatedSchemas)->toBe(['schema'])
        ->and($host->getErrorsForSchemaStatePath('data.title'))->toBe(['This value should not be blank.'])
        ->and($host->getErrorsForSchemaStatePath('data.slug'))->toBe([]);
});

it('carries the errors on the exception keyed by absolute path', function (): void {
    $host = $this->createLiveComponent('ValidationHost')->component();

    try {
        $host->validateSchema($host->getSchema());
        $this->fail('Expected ValidationException');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->getErrors()))->toBe(['data.title']);
    }
});

it('clears stickiness and the bag section on a passing submit-path validation', function (): void {
    $host = $this->createLiveComponent('ValidationHost')->component();

    try {
        $host->validateSchema($host->getSchema());
    } catch (ValidationException) {
    }

    $host->data['title'] = 'Hello';
    $host->validateSchema($host->getSchema());

    expect($host->validatedSchemas)->toBe([])
        ->and($host->getErrorsForSchemaStatePath('data.title'))->toBe([]);
});

it('fills the bag without throwing on the silent path', function (): void {
    $host = $this->createLiveComponent('ValidationHost')->component();

    $host->validateSchema($host->getSchema(), shouldThrow: false);

    expect($host->getErrorsForSchemaStatePath('data.title'))->toBe(['This value should not be blank.'])
        ->and($host->validatedSchemas)->toBe([]);
});

it('scoped-clears one schema and full-clears all', function (): void {
    $host = $this->createLiveComponent('ValidationHost')->component();

    try {
        $host->validateSchema($host->getSchema());
    } catch (ValidationException) {
    }

    $host->clearSchemaValidation('schema');

    expect($host->validatedSchemas)->toBe([])
        ->and($host->getErrorsForSchemaStatePath('data.title'))->toBe([]);
});
