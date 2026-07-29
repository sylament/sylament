<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration\Concerns;

use Sylament\Schemas\Exceptions\ValidationException;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

it('keeps the modal open on invalid submit — stack untouched, bag filled, action not run', function (): void {
    $host = $this->createLiveComponent('ActionSchemaHost')->component();
    $host->mountAction('publish');

    expect(fn () => $host->callMountedAction())->toThrow(ValidationException::class)
        ->and($host->mountedActions)->toHaveCount(1)
        ->and($host->getErrorsForSchemaStatePath('mountedActionsData.0.title'))->toBe(['This value should not be blank.'])
        ->and($host->getMountedAction()?->getData())->toBe([])
        ->and($host->savedData)->toBe([]);
});

it('runs the hook pair around validation on the valid path and unmounts', function (): void {
    $host = $this->createLiveComponent('ActionSchemaHost')->component();
    $host->mountAction('publish');
    $host->mountedActionsData[0]['title'] = 'Hello';

    $host->callMountedAction();

    expect($host->savedData)->toBe(['title' => 'Hello'])
        ->and($host->hookLog)->toBe(['beforeFormValidated', 'afterFormValidated'])
        ->and($host->mountedActions)->toBe([])
        ->and($host->validatedSchemas)->toBe([]);
});

it('calls beforeFormValidated but not afterFormValidated on the invalid path', function (): void {
    $host = $this->createLiveComponent('ActionSchemaHost')->component();
    $host->hookLog = [];
    $host->mountAction('publish');

    try {
        $host->callMountedAction();
    } catch (ValidationException) {
    }

    expect($host->hookLog)->toBe(['beforeFormValidated']);
});

it('clears a stale error bag section when the same index remounts', function (): void {
    $host = $this->createLiveComponent('ActionSchemaHost')->component();
    $host->mountAction('publish');

    try {
        $host->callMountedAction();
    } catch (ValidationException) {
    }

    $host->unmountAction();
    $host->mountAction('publish');

    expect($host->getErrorsForSchemaStatePath('mountedActionsData.0.title'))->toBe([])
        ->and($host->validatedSchemas)->toBe([]);
});

/*
 * The scoped-clear divergence: Filament resets the whole error bag when a modal
 * opens, so a nested mount wipes the parent's errors. Clearing only the index
 * being mounted is what keeps the parent modal's messages on screen.
 */
it('leaves an outer frame errors alone when a further frame mounts', function (): void {
    $host = $this->createLiveComponent('ActionSchemaHost')->component();
    $host->mountAction('publish');

    try {
        $host->callMountedAction();
    } catch (ValidationException) {
    }

    $host->mountAction('note');

    expect($host->getErrorsForSchemaStatePath('mountedActionsData.0.title'))->toBe(['This value should not be blank.'])
        ->and($host->validatedSchemas)->toBe(['mountedActionSchema0']);
});

it('unmounts and rethrows when the invalid action has no modal to keep open', function (): void {
    $host = $this->createLiveComponent('ActionSchemaHost')->component();
    $action = $host->getAction('publishInline');

    expect(fn () => $host->mountAction('publishInline', ['origin' => 'row-3']))->toThrow(ValidationException::class)
        ->and($host->mountedActions)->toBe([])
        ->and($host->savedData)->toBe([])
        ->and($action?->getData())->toBe([])
        ->and($action?->getArguments())->toBe(['origin' => 'row-3']);
});

it('unmounts and rethrows when a mount-time hook raises validation', function (): void {
    $host = $this->createLiveComponent('ActionSchemaHost')->component();

    expect(fn () => $host->mountAction('guarded'))->toThrow(ValidationException::class)
        ->and($host->mountedActions)->toBe([]);
});
