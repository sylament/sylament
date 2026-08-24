<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Concerns;

use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\ConstrainedField;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Components\StateGroup;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
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

it('validates a constrained leaf under a group path the host holds as null', function (): void {
    $host = $this->createLiveComponent('ValidationHost')->component();
    $host->data = ['author' => null];

    $schema = Schema::make($host)->key('nested')->statePath('data')->components([
        StateGroup::make()->statePath('author')->components([
            ConstrainedField::make('name')->required(),
        ]),
    ]);

    $host->validateSchema($schema, shouldThrow: false);

    expect($host->getErrorsForSchemaStatePath('data.author.name'))
        ->toBe(['This value should not be blank.']);
});

it('gates a blank string down to the implicit constraints before running the validator', function (): void {
    $host = $this->createLiveComponent('ValidationHost')->component();
    $host->data = ['title' => ''];

    $schema = Schema::make($host)->key('gated')->statePath('data')->components([
        ConstrainedField::make('title')->fixtureConstraints([new Length(min: 3), new GreaterThanOrEqual(1)]),
    ]);

    $host->validateSchema($schema, shouldThrow: false);

    expect($host->getErrorsForSchemaStatePath('data.title'))->toBe([]);
});

it('still runs the implicit constraints against a blank string', function (): void {
    $host = $this->createLiveComponent('ValidationHost')->component();
    $host->data = ['title' => ''];

    $schema = Schema::make($host)->key('gated')->statePath('data')->components([
        ConstrainedField::make('title')->fixtureConstraints([new NotBlank(), new Length(min: 3)]),
    ]);

    $host->validateSchema($schema, shouldThrow: false);

    expect($host->getErrorsForSchemaStatePath('data.title'))->toBe(['This value should not be blank.']);
});

it('does not gate a null value, which every ported constraint already skips', function (): void {
    $host = $this->createLiveComponent('ValidationHost')->component();
    $host->data = ['title' => null];

    $schema = Schema::make($host)->key('gated')->statePath('data')->components([
        ConstrainedField::make('title')->fixtureConstraints([new NotNull(), new Length(min: 3)]),
    ]);

    $host->validateSchema($schema, shouldThrow: false);

    expect($host->getErrorsForSchemaStatePath('data.title'))->toBe(['This value should not be null.']);
});

it('does not gate a value that is blank but not a string', function (): void {
    $host = $this->createLiveComponent('ValidationHost')->component();
    $host->data = ['tags' => []];

    $schema = Schema::make($host)->key('gated')->statePath('data')->components([
        ConstrainedField::make('tags')->fixtureConstraints([new Count(min: 1)]),
    ]);

    $host->validateSchema($schema, shouldThrow: false);

    expect($host->getErrorsForSchemaStatePath('data.tags'))
        ->toBe(['This collection should contain 1 element or more.']);
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

/*
 * The validation identity (M3 · 5): a root schema keeps its registered name, a
 * component's child schema is keyed by that component's absolute key, and the
 * sticky pass resolves the second form through `getSchemaComponent()`.
 */

it('keys a root schema validation by the name the host registered it under', function (): void {
    $host = $this->createLiveComponent('ValidationNestedHost')->component();

    expect(fn () => $host->validateSchema($host->getSchema()))
        ->toThrow(ValidationException::class)
        ->and($host->validatedSchemas)->toBe(['schema'])
        ->and($host->getErrorsForSchemaStatePath('data.title'))->toBe(['This value should not be blank.'])
        ->and($host->getErrorsForSchemaStatePath('data.other'))->toBe(['This value should not be blank.']);
});

it("keys a component's child schema by the owning component's absolute key", function (): void {
    $host = $this->createLiveComponent('ValidationNestedHost')->component();

    expect(fn () => $host->getBasicsSchema()->validate())
        ->toThrow(ValidationException::class)
        ->and($host->validatedSchemas)->toBe(['schema.basics::section'])
        ->and($host->getErrorsForSchemaStatePath('data.title'))->toBe(['This value should not be blank.'])
        ->and($host->getErrorsForSchemaStatePath('data.other'))->toBe([]);
});

it('fills a child schema bag without stickiness on the silent path', function (): void {
    $host = $this->createLiveComponent('ValidationNestedHost')->component();

    $host->validateSchema($host->getBasicsSchema(), shouldThrow: false);

    expect($host->validatedSchemas)->toBe([])
        ->and($host->getErrorsForSchemaStatePath('data.title'))->toBe(['This value should not be blank.'])
        ->and($host->getErrorsForSchemaStatePath('data.other'))->toBe([]);
});

it('re-validates a sticky child-schema identity on the next request, and only it', function (): void {
    $component = $this->createLiveComponent('ValidationNestedHost', ['validatedSchemas' => ['schema.basics::section']]);

    expect($component->component()->getErrorsForSchemaStatePath('data.title'))->toBe(['This value should not be blank.'])
        ->and($component->component()->getErrorsForSchemaStatePath('data.other'))->toBe([]);

    $component->call('noop');

    expect($component->component()->getErrorsForSchemaStatePath('data.title'))->toBe(['This value should not be blank.'])
        ->and($component->component()->getErrorsForSchemaStatePath('data.other'))->toBe([])
        ->and($component->component()->validatedSchemas)->toBe(['schema.basics::section']);
});

it('drops a child-schema identity whose component no longer resolves', function (): void {
    $host = $this->createLiveComponent('ValidationNestedHost')->component();
    $host->validatedSchemas = ['schema.gone::section'];

    $host->revalidateValidatedSchemas();

    expect($host->validatedSchemas)->toBe([])
        ->and($host->getErrorsForSchemaStatePath('data.title'))->toBe([]);
});

it('clears nested identities when the root schema is validated', function (): void {
    $host = $this->createLiveComponent('ValidationNestedHost')->component();

    try {
        $host->getBasicsSchema()->validate();
    } catch (ValidationException) {
    }

    expect($host->validatedSchemas)->toBe(['schema.basics::section']);

    try {
        $host->validateSchema($host->getSchema());
    } catch (ValidationException) {
    }

    expect($host->validatedSchemas)->toBe(['schema']);
});

it('clears nested identities when the root identity is scope-cleared', function (): void {
    $host = $this->createLiveComponent('ValidationNestedHost')->component();

    try {
        $host->getBasicsSchema()->validate();
    } catch (ValidationException) {
    }

    expect($host->validatedSchemas)->toBe(['schema.basics::section'])
        ->and($host->getErrorsForSchemaStatePath('data.title'))->toBe(['This value should not be blank.']);

    $host->clearSchemaValidation('schema');

    expect($host->validatedSchemas)->toBe([])
        ->and($host->getErrorsForSchemaStatePath('data.title'))->toBe([]);
});

it('returns a message once when a root and a child identity both hold it', function (): void {
    $host = $this->createLiveComponent('ValidationNestedHost')->component();

    try {
        $host->validateSchema($host->getSchema());
    } catch (ValidationException) {
    }

    try {
        $host->getBasicsSchema()->validate();
    } catch (ValidationException) {
    }

    expect($host->validatedSchemas)->toBe(['schema', 'schema.basics::section'])
        ->and($host->getErrorsForSchemaStatePath('data.title'))->toBe(['This value should not be blank.']);
});

it('dispatches form-validation-error naming the failing root identity', function (): void {
    $component = $this->createLiveComponent('ValidationNestedHost');
    $component->call('validateRoot');

    $event = $component->getDispatchedBrowserEvent($component->render(), 'form-validation-error');

    expect($event)->not->toBeNull()
        ->and($event['payload'])->toBe(['schemaKey' => 'schema']);
});

it("dispatches form-validation-error naming the failing component's child-schema identity", function (): void {
    $component = $this->createLiveComponent('ValidationNestedHost');
    $component->call('validateBasics');

    $event = $component->getDispatchedBrowserEvent($component->render(), 'form-validation-error');

    expect($event)->not->toBeNull()
        ->and($event['payload'])->toBe(['schemaKey' => 'schema.basics::section']);
});

it('dispatches nothing on the silent validation path', function (): void {
    $component = $this->createLiveComponent('ValidationNestedHost');
    $component->call('validateRootSilently');

    expect($component->getDispatchedBrowserEvent($component->render(), 'form-validation-error'))->toBeNull();
});

/*
 * `Schema::dispatchClientSideStateReset()` lives on the schema rather than the host,
 * but only a live host can be driven to observe the event it announces.
 */

it('announces a client-side state reset naming the schema', function (): void {
    $component = $this->createLiveComponent('ValidationNestedHost');
    $component->call('resetClientState');

    $event = $component->getDispatchedBrowserEvent($component->render(), 'reset-schema-component-state');

    expect($event)->not->toBeNull()
        ->and($event['payload'])->toBe(['schemaKey' => 'schema']);
});

it('validates the mutated value, so a mutation that blanks a value fails the required constraint', function (): void {
    $host = $this->createLiveComponent('ValidationHost')->component();
    $host->getSchema()->getComponentByStatePath('data.title')?->mutateStateForValidationUsing(fn (): string => '');
    $host->data = ['title' => 'filled'];

    expect(fn () => $host->validateSchema($host->getSchema()))->toThrow(ValidationException::class)
        ->and($host->getErrorsForSchemaStatePath('data.title'))->toBe(['This value should not be blank.']);
});

it('gates the mutated value, so a mutation that trims a value to nothing strips the non-implicit constraints', function (): void {
    $host = $this->createLiveComponent('ValidationHost')->component();
    $host->getSchema()->getComponentByStatePath('data.slug')?->fixtureConstraints([new Length(min: 3)])
        ->mutateStateForValidationUsing(fn (mixed $state): string => mb_trim((string) $state));
    $host->data = ['title' => 'Hello', 'slug' => '  '];

    $host->validateSchema($host->getSchema());

    expect($host->getErrorsForSchemaStatePath('data.slug'))->toBe([]);
});

it('leaves the host bag untouched when a value is mutated for validation', function (): void {
    $host = $this->createLiveComponent('ValidationHost')->component();
    $host->getSchema()->getComponentByStatePath('data.title')?->mutateStateForValidationUsing(fn (mixed $state): string => mb_trim((string) $state));
    $host->data = ['title' => '  Hello  '];

    $host->validateSchema($host->getSchema());

    expect($host->data['title'])->toBe('  Hello  ');
});

it('mutates on the sticky pass too', function (): void {
    $component = $this->createLiveComponent('ValidationHost', ['validatedSchemas' => ['schema']]);
    $host = $component->component();
    $host->getSchema()->getComponentByStatePath('data.title')?->mutateStateForValidationUsing(fn (): string => '');
    $host->data = ['title' => 'filled'];

    $host->revalidateValidatedSchemas();

    expect($host->getErrorsForSchemaStatePath('data.title'))->toBe(['This value should not be blank.']);
});
