<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components;

use Sylament\Actions\Action;
use Sylament\Schemas\Components\Wizard;
use Sylament\Schemas\Components\Wizard\Step;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;
use Sylament\Support\Enums\IconPosition;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Markup;

/**
 * Wire the wizard into a real parent Schema so its steps reach the container chain
 * (and the key closure a state path), as happens in a real render.
 */
function wireWizard(Wizard $wizard, ?string $statePath = null): Wizard
{
    Schema::make(new HasSchemasFixture())
        ->key('schema')
        ->statePath($statePath)
        ->components([$wizard])
        ->getComponents();

    return $wizard;
}

function requestStackWith(string $queryString): RequestStack
{
    $requestStack = new RequestStack();
    $requestStack->push(Request::create('/?' . $queryString));

    return $requestStack;
}

it('keys an unlabelled wizard by the bare suffix, non-inheritably', function (): void {
    $wizard = wireWizard(Wizard::make([Step::make('Basics')->components([StateField::make('title')])]));

    expect($wizard->getKey(isAbsolute: false))->toBe('wizard')
        ->and($wizard->getKey())->toBe('schema.wizard')
        ->and($wizard->isKeyInheritable())->toBeFalse();
});

it('prefixes the key with the slugged label', function (): void {
    $wizard = wireWizard(Wizard::make()->label('Order Flow'));

    expect($wizard->getKey())->toBe('schema.order-flow::wizard');
});

it('folds the container state path into the key', function (): void {
    $wizard = wireWizard(Wizard::make(), statePath: 'data');

    expect($wizard->getKey())->toBe('schema.data::wizard');
});

it('keeps a nested field addressed from the schema root, the key being non-inheritable', function (): void {
    $wizard = wireWizard(Wizard::make([
        Step::make('Basics')->components([StateField::make('title')]),
    ]));

    $field = $wizard->getSteps()[0]->getChildSchema()?->getComponents()[0] ?? null;

    expect($field?->getKey())->toBe('schema.title');
});

it('defaults to the first step and honours startOnStep()', function (): void {
    $wizard = wireWizard(Wizard::make([Step::make('One'), Step::make('Two'), Step::make('Three')]));

    expect($wizard->getStartStep())->toBe(1)
        ->and($wizard->getCurrentStepIndex())->toBe(0)
        ->and(wireWizard(Wizard::make([Step::make('One'), Step::make('Two')])->startOnStep(2))->getStartStep())->toBe(2);
});

it('derives the current step index from the start step', function (): void {
    $wizard = wireWizard(Wizard::make([Step::make('One'), Step::make('Two')])->startOnStep(2));

    expect($wizard->getCurrentStepIndex())->toBe(1);
});

it('reports the absolute key of the step it starts on', function (): void {
    $wizard = wireWizard(Wizard::make([Step::make('One'), Step::make('Two')])->startOnStep(2));

    expect($wizard->getStartStepKey())->toBe('schema.two::wizard-step')
        ->and(wireWizard(Wizard::make())->getStartStepKey())->toBeNull();
});

it('resolves the start step from the query string by absolute step key', function (): void {
    $wizard = Wizard::make([Step::make('One'), Step::make('Two')])->persistStepInQueryString();
    $wizard->setRequestStack(requestStackWith('step=' . rawurlencode('schema.two::wizard-step')));
    wireWizard($wizard);

    expect($wizard->getStartStep())->toBe(2);
});

it('falls back to the start step for an unknown query-string value', function (): void {
    $wizard = Wizard::make([Step::make('One'), Step::make('Two')])->persistStepInQueryString();
    $wizard->setRequestStack(requestStackWith('step=nope'));
    wireWizard($wizard);

    expect($wizard->getStartStep())->toBe(1);
});

it('skips the query-string read with no request stack', function (): void {
    $wizard = wireWizard(Wizard::make([Step::make('One'), Step::make('Two')])->persistStepInQueryString());

    expect($wizard->getStartStep())->toBe(1)
        ->and($wizard->isStepPersistedInQueryString())->toBeTrue()
        ->and($wizard->getStepQueryStringKey())->toBe('step');
});

it('is not persisted in the query string by default', function (): void {
    $wizard = wireWizard(Wizard::make());

    expect($wizard->isStepPersistedInQueryString())->toBeFalse()
        ->and($wizard->getStepQueryStringKey())->toBeNull();
});

it('lists only the visible steps, dropping foreign children', function (): void {
    $wizard = wireWizard(Wizard::make([
        Step::make('One'),
        Step::make('Hidden')->hidden(),
        StateField::make('stray'),
        Step::make('Two'),
    ]));

    expect(array_map(static fn (Step $step): string|Markup|null => $step->getLabel(), $wizard->getSteps()))
        ->toBe(['One', 'Two']);
});

it('builds the next action as an inert trailing-icon button', function (): void {
    $wizard = wireWizard(Wizard::make());
    $action = $wizard->getNextAction();

    expect($action->getName())->toBe('next')
        ->and($wizard->getNextActionName())->toBe('next')
        ->and((string) $action->getLabel())->toBe('Next')
        ->and($action->isLiveComponentClickHandlerEnabled())->toBeFalse()
        ->and($action->getIconPosition())->toBe(IconPosition::After)
        ->and($action->getTemplate())->toBe(Action::BUTTON_TEMPLATE);
});

it('builds the previous action as an inert gray button', function (): void {
    $wizard = wireWizard(Wizard::make());
    $action = $wizard->getPreviousAction();

    expect($action->getName())->toBe('previous')
        ->and($wizard->getPreviousActionName())->toBe('previous')
        ->and((string) $action->getLabel())->toBe('Back')
        ->and($action->getColor())->toBe('gray')
        ->and($action->isLiveComponentClickHandlerEnabled())->toBeFalse()
        ->and($action->getTemplate())->toBe(Action::BUTTON_TEMPLATE);
});

it('applies the nextAction() and previousAction() modifiers with the action injected', function (): void {
    $wizard = wireWizard(Wizard::make()
        ->nextAction(fn (Action $action): Action => $action->label('Continue'))
        ->previousAction(fn (Action $action): Action => $action->label('Go back')));

    expect((string) $wizard->getNextAction()->getLabel())->toBe('Continue')
        ->and((string) $wizard->getPreviousAction()->getLabel())->toBe('Go back');
});

it('keeps the action returned by a modifier that returns nothing', function (): void {
    $wizard = wireWizard(Wizard::make()->nextAction(function (Action $action): void {
        $action->label('Mutated');
    }));

    expect((string) $wizard->getNextAction()->getLabel())->toBe('Mutated');
});

it('registers both footer actions against the wizard', function (): void {
    $wizard = wireWizard(Wizard::make());

    expect($wizard->getAction('next')?->getName())->toBe('next')
        ->and($wizard->getAction('next')?->getSchemaComponent())->toBe($wizard)
        ->and($wizard->getAction('previous')?->getName())->toBe('previous')
        ->and($wizard->getAction('previous')?->getSchemaComponent())->toBe($wizard);
});

it('round-trips the boolean flags through their closures', function (): void {
    $wizard = wireWizard(Wizard::make());

    expect($wizard->isSkippable())->toBeFalse()
        ->and($wizard->isHeaderHidden())->toBeFalse()
        ->and($wizard->isContained())->toBeTrue()
        ->and($wizard->skippable()->isSkippable())->toBeTrue()
        ->and(wireWizard(Wizard::make())->hiddenHeader(fn (): bool => true)->isHeaderHidden())->toBeTrue()
        ->and(wireWizard(Wizard::make())->skippable(fn (): bool => false)->isSkippable())->toBeFalse();
});

it('round-trips all three footer slot shapes', function (): void {
    $markup = new Markup('<b>Cancel</b>', 'UTF-8');
    $action = Action::make('save');

    expect(wireWizard(Wizard::make())->cancelAction('Cancel')->getCancelAction())->toBe('Cancel')
        ->and(wireWizard(Wizard::make())->cancelAction($markup)->getCancelAction())->toBe($markup)
        ->and(wireWizard(Wizard::make())->submitAction($action)->getSubmitAction())->toBe($action)
        ->and(wireWizard(Wizard::make())->getCancelAction())->toBeNull()
        ->and(wireWizard(Wizard::make())->getSubmitAction())->toBeNull();
});

it('walks the current step index back without going below the first step', function (): void {
    $wizard = wireWizard(Wizard::make([Step::make('One'), Step::make('Two'), Step::make('Three')]));

    $wizard->previousStep(2);

    expect($wizard->getCurrentStepIndex())->toBe(1);

    $wizard->previousStep(0);
    expect($wizard->getCurrentStepIndex())->toBe(0);
});
