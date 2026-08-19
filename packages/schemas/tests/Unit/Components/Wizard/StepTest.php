<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\Wizard;

use LogicException;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Contracts\CanConcealComponents;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Components\Wizard;
use Sylament\Schemas\Components\Wizard\Step;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;
use Sylament\Support\Exceptions\Halt;

/**
 * Wire the step into a real Wizard inside a real Schema, the only arrangement it
 * renders in — its key, its columns fallback and its template data all read the chain.
 */
function wireStep(Step $step, ?string $statePath = null): Step
{
    $wizard = Wizard::make([$step]);

    Schema::make(new HasSchemasFixture())
        ->key('schema')
        ->statePath($statePath)
        ->components([$wizard])
        ->getComponents();

    $wizard->getSteps();

    return $step;
}

it('keys a step by its slugged label, non-inheritably', function (): void {
    $step = wireStep(Step::make('Order Basics'));

    expect($step->getKey(isAbsolute: false))->toBe('order-basics::wizard-step')
        ->and($step->getKey())->toBe('schema.order-basics::wizard-step')
        ->and($step->isKeyInheritable())->toBeFalse();
});

it('folds the container state path into the key', function (): void {
    $step = wireStep(Step::make('Basics'), statePath: 'data');

    expect($step->getKey())->toBe('schema.basics::data::wizard-step');
});

it('conceals its components', function (): void {
    expect(Step::make('Basics'))->toBeInstanceOf(CanConcealComponents::class)
        ->and(Step::make('Basics')->canConcealComponents())->toBeTrue();
});

it('round-trips the description and both icons through their closures', function (): void {
    $step = Step::make('Basics')
        ->description(fn (): string => 'The first bits')
        ->icon('heroicons:check')
        ->completedIcon(fn (): string => 'heroicons:x-mark');

    expect($step->getDescription())->toBe('The first bits')
        ->and($step->getIcon())->toBe('heroicons:check')
        ->and($step->getCompletedIcon())->toBe('heroicons:x-mark')
        ->and(Step::make('Basics')->getDescription())->toBeNull()
        ->and(Step::make('Basics')->getIcon())->toBeNull()
        ->and(Step::make('Basics')->getCompletedIcon())->toBeNull();
});

it('runs the validation callbacks with the step injected', function (): void {
    $seen = [];
    $step = Step::make('Basics')
        ->beforeValidation(function (Step $component) use (&$seen): void {
            $seen[] = ['before', $component];
        })
        ->afterValidation(function (Step $component) use (&$seen): void {
            $seen[] = ['after', $component];
        });

    $step->callBeforeValidation();
    $step->callAfterValidation();

    expect($seen)->toBe([['before', $step], ['after', $step]]);
});

it('lets a validation callback halt', function (): void {
    $step = Step::make('Basics')->beforeValidation(function (): void {
        throw new Halt();
    });

    expect(fn () => $step->callBeforeValidation())->toThrow(Halt::class);
});

it('does nothing when no validation callback is registered', function (): void {
    $step = Step::make('Basics');

    $step->callBeforeValidation();
    $step->callAfterValidation();

    expect($step->getLabel())->toBe('Basics');
});

it('falls back to the container columns until its own are set', function (): void {
    $step = wireStep(Step::make('Basics'));

    expect($step->getAllColumns())->toBe($step->getContainer()->getAllColumns())
        ->and(wireStep(Step::make('Basics')->columns(3))->getAllColumns())->toBe(['lg' => 3]);
});

it('reaches the wizard it renders inside', function (): void {
    $step = wireStep(Step::make('Basics'));

    expect($step->getWizard())->toBeInstanceOf(Wizard::class)
        ->and($step->getWizard()->getSteps())->toBe([$step]);
});

it('refuses to render outside a wizard', function (): void {
    $step = Step::make('Basics');
    $group = Group::make([$step]);
    Schema::make(new HasSchemasFixture())->components([$group])->getComponents();
    $group->getChildSchema()?->getComponents();

    expect(fn (): Wizard => $step->getWizard())->toThrow(LogicException::class);
});

it('names its own panel state for the template', function (): void {
    $step = wireStep(Step::make('Basics')->components([StateField::make('title')]));

    $data = $step->getExtraTemplateData();

    expect($data['stepKey'])->toBe('schema.basics::wizard-step')
        ->and($data['stepId'])->toBe('schema.basics::wizard-step')
        ->and($data['isActive'])->toBeTrue()
        ->and($data['hasHeaderReference'])->toBeTrue()
        ->and($data['labelText'])->toBe('Basics')
        ->and($data['contentSchema'])->toBeInstanceOf(Schema::class);
});

it('drops the header reference when the wizard hides its header', function (): void {
    $step = Step::make('Basics');
    $wizard = Wizard::make([$step])->hiddenHeader();
    Schema::make(new HasSchemasFixture())->key('schema')->components([$wizard])->getComponents();
    $wizard->getSteps();

    expect($step->getExtraTemplateData()['hasHeaderReference'])->toBeFalse();
});

it('extends the schemas component base', function (): void {
    expect(Step::make('Basics'))->toBeInstanceOf(Component::class);
});
