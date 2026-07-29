<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Concerns;

use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;
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

it('re-validates a sticky schema on the next request and clears fixed errors', function (): void {
    $component = $this->createLiveComponent('ValidationHost', ['validatedSchemas' => ['schema']]);

    expect($component->component()->getErrorsForSchemaStatePath('data.title'))->toBe(['This value should not be blank.']);

    $component->set('data', ['title' => 'Hello', 'slug' => null]);
    $component->refresh();

    expect($component->component()->getErrorsForSchemaStatePath('data.title'))->toBe([])
        ->and($component->component()->validatedSchemas)->toBe(['schema']);
});

it('keeps showing errors on subsequent requests while the field stays invalid', function (): void {
    $component = $this->createLiveComponent('ValidationHost', ['validatedSchemas' => ['schema']]);

    $component->refresh();
    $component->refresh();

    expect($component->component()->getErrorsForSchemaStatePath('data.title'))->toBe(['This value should not be blank.']);
});

it('re-validates only after afterStateUpdated mutations settle', function (): void {
    $component = $this->createLiveComponent('ValidationHost', ['validatedSchemas' => ['schema']]);

    expect($component->component()->getErrorsForSchemaStatePath('data.title'))->toBe(['This value should not be blank.']);

    $component->set('data', ['title' => null, 'slug' => 'hello']);
    $component->refresh();

    expect($component->component()->getErrorsForSchemaStatePath('data.title'))->toBe([]);
});

it('drops a stale sticky key whose schema no longer resolves', function (): void {
    $host = $this->createLiveComponent('ValidationHost')->component();
    $host->validatedSchemas = ['mountedActionSchema7'];

    $host->revalidateValidatedSchemas();

    expect($host->validatedSchemas)->toBe([]);
});

it('validates through getState() and skips validation in getStateSnapshot()', function (): void {
    $host = $this->createLiveComponent('ValidationHost')->component();

    expect(fn () => $host->getSchema()->getState())->toThrow(ValidationException::class)
        ->and($host->getSchema()->getStateSnapshot())->toBe(['title' => null, 'slug' => null]);
});

it('passes the final dehydrated state to afterValidate after hooks', function (): void {
    $host = $this->createLiveComponent('ValidationHost')->component();
    $host->data['title'] = 'Hello';

    $seen = null;
    $state = $host->getSchema()->getState(afterValidate: function (array $validatedState) use (&$seen): void {
        $seen = $validatedState;
    });

    expect($seen)->toBe($state)
        ->and($state['title'])->toBe('Hello');
});

it('skips callBeforeStateDehydrated when shouldCallHooksBefore is false', function (): void {
    $host = new StateHostFixture();
    $schema = Schema::make($host)->statePath('data')->components([
        StateField::make('title')->beforeStateDehydrated(function ($component): void {
            $component->state('rewritten');
        }, shouldUpdateValidatedStateAfter: true),
    ]);
    $schema->fill(['title' => 'original']);

    expect($schema->getState(shouldCallHooksBefore: false))->toBe(['title' => 'original']);
});
