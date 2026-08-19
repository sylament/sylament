<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Concerns;

use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Tests\Fixtures\Twig\Components\WizardHost;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * Drives the Wizard through the browser-callable seam against a real live host.
 * `TestLiveComponent` forces `catchExceptions(false)`, so a `nextStep()` that
 * fails validation surfaces the `ValidationException` as a PHP exception rather
 * than a stored 422 and there is no response to read — the fixture's `advance`
 * action swallows it so the same call can be inspected through its render, and
 * the sticky state a failed advance leaves behind is reproduced by seeding
 * `validatedSchemas` (forms' `FieldErrorsTest` is the precedent for both).
 */

it('refuses to advance past a step whose fields are invalid', function (): void {
    $component = $this->createLiveComponent('WizardHost');

    expect(fn () => $component->call('callSchemaComponentMethod', [
        'componentKey' => WizardHost::WIZARD_KEY,
        'method' => 'nextStep',
        'arguments' => ['currentStepIndex' => 0],
    ]))->toThrow(ValidationException::class);
});

it('announces the failure against the step identity and stays put', function (): void {
    $component = $this->createLiveComponent('WizardHost');
    $component->call('advance', ['currentStepIndex' => 0]);

    $render = $component->render();

    expect($component->getDispatchedBrowserEvent($render, 'form-validation-error')['payload'] ?? null)
        ->toBe(['schemaKey' => WizardHost::FIRST_STEP_KEY])
        ->and($component->getDispatchedBrowserEvent($render, 'next-wizard-step'))->toBeNull();
});

it('keeps the failed step sticky and leaves the later step clean', function (): void {
    $component = $this->createLiveComponent('WizardHost', [
        'validatedSchemas' => [WizardHost::FIRST_STEP_KEY],
    ]);

    expect($component->component()->validatedSchemas)->toBe([WizardHost::FIRST_STEP_KEY])
        ->and($component->component()->getErrorsForSchemaStatePath('data.title'))
        ->toBe(['This value should not be blank.'])
        ->and($component->component()->getErrorsForSchemaStatePath('data.body'))->toBe([]);

    $component->refresh();

    expect($component->component()->getErrorsForSchemaStatePath('data.title'))
        ->toBe(['This value should not be blank.'])
        ->and($component->component()->getErrorsForSchemaStatePath('data.body'))->toBe([]);
});

it('clears the sticky step once its field is filled', function (): void {
    $component = $this->createLiveComponent('WizardHost', [
        'validatedSchemas' => [WizardHost::FIRST_STEP_KEY],
    ]);

    $component->set('data', ['title' => 'Hello']);

    expect($component->component()->getErrorsForSchemaStatePath('data.title'))->toBe([]);
});

it('advances and announces the step change once the step is valid', function (): void {
    $component = $this->createLiveComponent('WizardHost');
    $component->set('data', ['title' => 'Hello']);
    $component->call('callSchemaComponentMethod', [
        'componentKey' => WizardHost::WIZARD_KEY,
        'method' => 'nextStep',
        'arguments' => ['currentStepIndex' => 0],
    ]);

    $render = $component->render();

    expect($component->getDispatchedBrowserEvent($render, 'next-wizard-step')['payload'] ?? null)
        ->toBe(['key' => WizardHost::WIZARD_KEY])
        ->and($component->getDispatchedBrowserEvent($render, 'sylament:schema-component-method-result')['payload'] ?? null)
        ->toBe([
            'componentKey' => WizardHost::WIZARD_KEY,
            'method' => 'nextStep',
            'result' => null,
        ]);
});

it('validates the second step on its own paths', function (): void {
    $component = $this->createLiveComponent('WizardHost');
    $component->set('data', ['title' => 'Hello']);
    $component->call('advance', ['currentStepIndex' => 1]);

    $render = $component->render();

    expect($component->getDispatchedBrowserEvent($render, 'form-validation-error')['payload'] ?? null)
        ->toBe(['schemaKey' => WizardHost::SECOND_STEP_KEY])
        ->and($component->component()->getErrorsForSchemaStatePath('data.body'))
        ->toBe(['This value should not be blank.'])
        ->and($component->component()->getErrorsForSchemaStatePath('data.title'))->toBe([]);
});

it('advances a step with no constraints at all', function (): void {
    $component = $this->createLiveComponent('WizardHost');
    $component->call('advance', ['currentStepIndex' => 2]);

    $render = $component->render();

    expect($component->getDispatchedBrowserEvent($render, 'next-wizard-step')['payload'] ?? null)
        ->toBe(['key' => WizardHost::WIZARD_KEY])
        ->and($component->getDispatchedBrowserEvent($render, 'form-validation-error'))->toBeNull();
});

it('skips validation entirely when the wizard is skippable', function (): void {
    $component = $this->createLiveComponent('WizardHost', ['skippable' => true]);
    $component->call('callSchemaComponentMethod', [
        'componentKey' => WizardHost::WIZARD_KEY,
        'method' => 'nextStep',
        'arguments' => ['currentStepIndex' => 0],
    ]);

    $render = $component->render();

    expect($component->getDispatchedBrowserEvent($render, 'next-wizard-step')['payload'] ?? null)
        ->toBe(['key' => WizardHost::WIZARD_KEY])
        ->and($component->getDispatchedBrowserEvent($render, 'form-validation-error'))->toBeNull();
});

it('announces nothing when beforeValidation halts', function (): void {
    $component = $this->createLiveComponent('WizardHost', ['halt' => true]);
    $component->call('callSchemaComponentMethod', [
        'componentKey' => WizardHost::WIZARD_KEY,
        'method' => 'nextStep',
        'arguments' => ['currentStepIndex' => 0],
    ]);

    $render = $component->render();

    expect($component->response()->getStatusCode())->toBe(200)
        ->and($component->getDispatchedBrowserEvent($render, 'next-wizard-step'))->toBeNull()
        ->and($component->getDispatchedBrowserEvent($render, 'form-validation-error'))->toBeNull()
        ->and($component->component()->validatedSchemas)->toBe([]);
});

it('ignores a step index no step answers to', function (): void {
    $component = $this->createLiveComponent('WizardHost');
    $component->call('callSchemaComponentMethod', [
        'componentKey' => WizardHost::WIZARD_KEY,
        'method' => 'nextStep',
        'arguments' => ['currentStepIndex' => 9],
    ]);

    $render = $component->render();

    expect($component->response()->getStatusCode())->toBe(200)
        ->and($component->getDispatchedBrowserEvent($render, 'next-wizard-step'))->toBeNull();
});

it('answers a previousStep call with the result event alone', function (): void {
    $component = $this->createLiveComponent('WizardHost');
    $component->call('callSchemaComponentMethod', [
        'componentKey' => WizardHost::WIZARD_KEY,
        'method' => 'previousStep',
        'arguments' => ['currentStepIndex' => 1],
    ]);

    $render = $component->render();

    expect($component->getDispatchedBrowserEvent($render, 'sylament:schema-component-method-result')['payload'] ?? null)
        ->toBe([
            'componentKey' => WizardHost::WIZARD_KEY,
            'method' => 'previousStep',
            'result' => null,
        ])
        ->and($component->getDispatchedBrowserEvent($render, 'next-wizard-step'))->toBeNull()
        ->and($component->getDispatchedBrowserEvent($render, 'go-to-wizard-step'))->toBeNull();
});
