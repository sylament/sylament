<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Action;
use Sylament\Schemas\Components\Wizard;
use Sylament\Schemas\Components\Wizard\Step;
use Sylament\Schemas\Schema;

it('is not a wizard by default', function (): void {
    expect(Action::make('onboard')->isWizard())->toBeFalse();
});

it('becomes a wizard when steps are declared, and declares them as its schema', function (): void {
    $step = Step::make('One');
    $action = Action::make('onboard')->steps([$step]);

    expect($action->isWizard())->toBeTrue()
        ->and($action->getSchema(Schema::make())?->getComponents())->toHaveCount(1);
});

it('accepts a closure returning the steps', function (): void {
    $action = Action::make('onboard')->steps(fn (): array => [Step::make('One'), Step::make('Two')]);

    expect($action->isWizard())->toBeTrue();
});

it('starts on step one by default', function (): void {
    expect(Action::make('onboard')->getWizardStartStep())->toBe(1);
});

it('starts on the step it is given, literal or closure', function (): void {
    expect(Action::make('onboard')->startOnStep(2)->getWizardStartStep())->toBe(2)
        ->and(Action::make('onboard')->startOnStep(fn (): int => 3)->getWizardStartStep())->toBe(3);
});

it('has non-skippable steps by default', function (): void {
    expect(Action::make('onboard')->isWizardSkippable())->toBeFalse();
});

it('makes its steps skippable, literal or closure', function (): void {
    expect(Action::make('onboard')->skippableSteps()->isWizardSkippable())->toBeTrue()
        ->and(Action::make('onboard')->skippableSteps(false)->isWizardSkippable())->toBeFalse()
        ->and(Action::make('onboard')->skippableSteps(fn (): bool => true)->isWizardSkippable())->toBeTrue();
});

it('turns the action into a wizard through modifyWizardUsing() alone', function (): void {
    expect(Action::make('onboard')->modifyWizardUsing(null)->isWizard())->toBeTrue();
});

it('wraps an array schema in a wizard carrying the action configuration', function (): void {
    $action = Action::make('onboard')
        ->steps([Step::make('One'), Step::make('Two')])
        ->startOnStep(2)
        ->skippableSteps()
        ->action(fn (): null => null);

    $components = $action->getSchema(Schema::make())?->getComponents();
    $wizard = $components[0] ?? null;

    expect($components)->toHaveCount(1)
        ->and($wizard)->toBeInstanceOf(Wizard::class)
        ->and($wizard->isContained())->toBeFalse()
        ->and($wizard->getStartStep())->toBe(2)
        ->and($wizard->isSkippable())->toBeTrue()
        ->and($wizard->getSteps())->toHaveCount(2)
        ->and($wizard->getCancelAction()?->getName())->toBe('cancel')
        ->and($wizard->getSubmitAction()?->getName())->toBe('submit');
});

it('hands the built wizard to the modifyWizardUsing() callback', function (): void {
    $action = Action::make('onboard')
        ->steps([Step::make('One')])
        ->modifyWizardUsing(fn (Wizard $wizard): Wizard => $wizard->hiddenHeader());

    $wizard = $action->getSchema(Schema::make())?->getComponents()[0] ?? null;

    expect($wizard)->toBeInstanceOf(Wizard::class)
        ->and($wizard->isHeaderHidden())->toBeTrue();
});

it('keeps the built wizard when the modify callback returns nothing', function (): void {
    $action = Action::make('onboard')
        ->steps([Step::make('One')])
        ->modifyWizardUsing(function (Wizard $wizard): void {
            $wizard->hiddenHeader();
        });

    $wizard = $action->getSchema(Schema::make())?->getComponents()[0] ?? null;

    expect($wizard)->toBeInstanceOf(Wizard::class)
        ->and($wizard->isHeaderHidden())->toBeTrue();
});

it('propagates disabledSchema() into the wizard', function (): void {
    $action = Action::make('onboard')
        ->steps([Step::make('One')])
        ->disabledSchema();

    $wizard = $action->getSchema(Schema::make())?->getComponents()[0] ?? null;

    expect($wizard)->toBeInstanceOf(Wizard::class)
        ->and($wizard->isDisabled())->toBeTrue();
});

it('leaves a non-array schema declaration alone', function (): void {
    $schema = Schema::make()->components([Step::make('One')]);
    $action = Action::make('onboard')->steps([Step::make('Ignored')])->schema(fn (): Schema => $schema);

    expect($action->getSchema(Schema::make()))->toBe($schema);
});

it('suppresses the modal footer actions for a wizard', function (): void {
    $action = Action::make('onboard')->steps([Step::make('One')])->action(fn (): null => null);

    expect($action->getModalFooterActions())->toBe([])
        ->and($action->getVisibleModalFooterActions())->toBe([]);
});

it('keeps the modal footer actions for a non-wizard action', function (): void {
    $action = Action::make('save')->action(fn (): null => null);

    expect(array_keys($action->getModalFooterActions()))->toBe(['submit', 'cancel']);
});
