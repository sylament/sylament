<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Concerns;

use LogicException;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/**
 * @param array<string, mixed> $arguments
 *
 * @return ?array{event: non-empty-string, payload: array<string, mixed>}
 */
function callLiveStateHostSchemaComponentMethod(array $arguments): ?array
{
    $component = test()->createLiveComponent('LiveStateHost');
    $component->call('callSchemaComponentMethod', $arguments);

    return $component->getDispatchedBrowserEvent($component->render(), 'sylament:schema-component-method-result');
}

it('calls the exposed method with named arguments and dispatches the result', function (): void {
    $event = callLiveStateHostSchemaComponentMethod([
        'componentKey' => 'schema.title',
        'method' => 'echoForJs',
        // Deliberately out of declaration order: proves the spread is a
        // named-argument call, not a positional one.
        'arguments' => ['times' => 2, 'value' => 'x'],
    ]);

    expect($event)->not->toBeNull()
        ->and($event['payload'])->toBe([
            'componentKey' => 'schema.title',
            'method' => 'echoForJs',
            'fileKey' => null,
            'result' => ['x', 'x'],
        ]);
});

it('throws naming the method and the component key for a result that is not JSON-serialisable', function (): void {
    expect(fn (): ?array => callLiveStateHostSchemaComponentMethod([
        'componentKey' => 'schema.title',
        'method' => 'unencodableForJs',
        'arguments' => [],
    ]))
        ->toThrow(LogicException::class, 'unencodableForJs')
        ->toThrow(LogicException::class, 'schema.title')
        ->toThrow(LogicException::class, 'JSON-serialisable');
});

it('answers null for a method missing the ExposedLiveComponentMethod attribute', function (): void {
    $event = callLiveStateHostSchemaComponentMethod([
        'componentKey' => 'schema.title',
        'method' => 'notExposed',
        'arguments' => [],
    ]);

    expect($event['payload']['result'])->toBeNull();
});

it('answers null for a non-public method carrying the attribute, and still re-renders', function (): void {
    $component = test()->createLiveComponent('LiveStateHost');
    $component->call('callSchemaComponentMethod', [
        'componentKey' => 'schema.title',
        'method' => 'exposedButProtected',
        'arguments' => [],
    ]);

    $render = $component->render();
    $event = $component->getDispatchedBrowserEvent($render, 'sylament:schema-component-method-result');

    expect($event)->not->toBeNull()
        ->and($event['payload']['result'])->toBeNull()
        ->and((string) $render)->toContain('data-model="data.title"');
});

it('answers null for an unknown component key', function (): void {
    $event = callLiveStateHostSchemaComponentMethod([
        'componentKey' => 'schema.doesNotExist',
        'method' => 'echoForJs',
        'arguments' => ['value' => 'x'],
    ]);

    expect($event['payload']['result'])->toBeNull();
});

it('answers null for a key naming a member of the host contract, and still re-renders', function (): void {
    $component = test()->createLiveComponent('LiveStateHost');
    $component->call('callSchemaComponentMethod', [
        'componentKey' => 'validateSchemaRecord.x',
        'method' => 'echoForJs',
        'arguments' => [],
    ]);

    $render = $component->render();
    $event = $component->getDispatchedBrowserEvent($render, 'sylament:schema-component-method-result');

    expect($event)->not->toBeNull()
        ->and($event['payload']['result'])->toBeNull()
        ->and((string) $render)->toContain('data-model="data.title"');
});

it('answers null for a key naming an unknown schema', function (): void {
    $event = callLiveStateHostSchemaComponentMethod([
        'componentKey' => 'unknown.title',
        'method' => 'echoForJs',
        'arguments' => ['value' => 'x'],
    ]);

    expect($event['payload']['result'])->toBeNull();
});

it('answers null for a key carrying no schema name at all', function (): void {
    $event = callLiveStateHostSchemaComponentMethod([
        'componentKey' => 'title',
        'method' => 'echoForJs',
        'arguments' => ['value' => 'x'],
    ]);

    expect($event['payload']['result'])->toBeNull();
});

it('answers null for a hidden component', function (): void {
    $event = callLiveStateHostSchemaComponentMethod([
        'componentKey' => 'schema.secret',
        'method' => 'echoForJs',
        'arguments' => ['value' => 'x'],
    ]);

    expect($event['payload']['result'])->toBeNull();
});

it('answers null for a method name that does not exist on the component', function (): void {
    $event = callLiveStateHostSchemaComponentMethod([
        'componentKey' => 'schema.title',
        'method' => 'thisMethodDoesNotExist',
        'arguments' => [],
    ]);

    expect($event['payload']['result'])->toBeNull();
});

it('answers null for an unknown named argument', function (): void {
    $event = callLiveStateHostSchemaComponentMethod([
        'componentKey' => 'schema.title',
        'method' => 'echoForJs',
        'arguments' => ['value' => 'x', 'notAParameter' => 1],
    ]);

    expect($event['payload']['result'])->toBeNull();
});

it('answers null for a missing required argument', function (): void {
    $event = callLiveStateHostSchemaComponentMethod([
        'componentKey' => 'schema.title',
        'method' => 'echoForJs',
        'arguments' => ['times' => 2],
    ]);

    expect($event['payload']['result'])->toBeNull();
});

it('answers null instead of a 500 for a mistyped argument', function (): void {
    $event = callLiveStateHostSchemaComponentMethod([
        'componentKey' => 'schema.title',
        'method' => 'echoForJs',
        'arguments' => ['value' => 'x', 'times' => 'not-an-int'],
    ]);

    expect($event['payload']['result'])->toBeNull();
});

it('still returns a normal live re-render alongside a null result', function (): void {
    $component = test()->createLiveComponent('LiveStateHost');
    $component->call('callSchemaComponentMethod', [
        'componentKey' => 'schema.doesNotExist',
        'method' => 'echoForJs',
        'arguments' => [],
    ]);

    $render = $component->render();

    expect((string) $render)->toContain('data-model="data.title"')
        ->and($component->getDispatchedBrowserEvent($render, 'sylament:schema-component-method-result'))->not->toBeNull();
});
