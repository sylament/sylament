<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Components\Wizard;
use Sylament\Schemas\Components\Wizard\Step;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Schemas\Selectors;
use Sylament\Tests\Fixtures\Schemas\WizardForm;
use Symfony\Component\DomCrawler\Crawler;
use Twig\Error\RuntimeError;

it('shows its label on its header button and what it holds in its panel', function (): void {
    expect($this->renderSchemaComponent(Wizard::make([Step::make('Account')->components([TextInput::make('name')])])))
        ->toContainElement(Selectors::WIZARD_STEP_BUTTON, 'check 01 Account')
        ->toContainElement(Selectors::WIZARD_STEP . ' input[name="name"]');
});

it('is keyed by its label under the state path on its header item, button and panel, and leaves the key of what it holds as it was', function (): void {
    $inner = Text::make('Who you are')->key('inner');
    $step = Step::make('Account Details')->components([$inner]);
    $schema = Schema::make()->key('schema')->components([Wizard::make([$step])]);
    $rendered = $this->renderSchemaComponent(Wizard::make([Step::make('Account Details')]));

    expect($rendered->filter('[data-step-key]')->each(static fn (Crawler $element): string => $element->nodeName() . ' ' . $element->attr('data-step-key')))
        ->toBe(['li schema.account-details::data::wizard-step', 'button schema.account-details::data::wizard-step', 'div schema.account-details::data::wizard-step'])
        ->and($schema->getComponent('account-details::wizard-step'))->toBe($step)
        ->and($schema->getComponent('inner'))->toBe($inner);
});

it('runs its before-validation hook ahead of the validation, with the state injected, even when the step then fails', function (): void {
    $this->createLiveComponent(WizardForm::class, ['data' => ['name' => 'Ada Lovelace']])
        ->call('callSchemaComponentMethod', ['componentKey' => 'form.data::wizard', 'method' => 'nextStep', 'arguments' => ['currentStepIndex' => 0]])
        ->assertHasFormErrors(['name']);

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['beforeValidation: Ada Lovelace']);
});

it('runs its after-validation hook once the step passes, and not when it fails', function (string $name, array $hooks): void {
    $this->createLiveComponent(WizardForm::class, ['data' => ['name' => $name]])
        ->call('callSchemaComponentMethod', ['componentKey' => 'form.data::wizard', 'method' => 'nextStep', 'arguments' => ['currentStepIndex' => 0]]);

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe($hooks);
})->with([
    'a passing step' => ['Ada', ['beforeValidation: Ada', 'afterValidation: Ada']],
    'a failing step' => ['', ['beforeValidation: ']],
]);

it('shows its description under its label', function (): void {
    expect($this->renderSchemaComponent(Wizard::make([Step::make('Account')->description('Who you are')])))
        ->toContainElement(Selectors::WIZARD_STEP_BUTTON, 'check 01 Account Who you are');
});

it('shows its icon in place of its number', function (): void {
    expect($this->renderSchemaComponent(Wizard::make([Step::make('Account')->icon('support:star'), Step::make('Review')])))
        ->toContainButtons(Selectors::WIZARD_STEP_BUTTON, ['check Star Account', 'check 02 Review Not completed']);
});

it('carries the completed tick it is given, or a check, in its header item whether or not it is completed', function (): void {
    $wizard = $this->renderSchemaComponent(Wizard::make([Step::make('Account')->completedIcon('support:heart'), Step::make('Review')])->startOnStep(2));

    expect($wizard)->toContainButtons(Selectors::WIZARD_STEP_BUTTON, ['Heart 01 Account Completed', 'check 02 Review']);
});

it('lays out what it holds in the columns of its wizard, unless it names its own', function (): void {
    $wizard = $this->renderSchemaComponent(Wizard::make([
        Step::make('Account')->components([TextInput::make('name')]),
        Step::make('Review')->columns(3)->components([TextInput::make('notes')]),
    ])->columns(2));

    expect($wizard)->toContainElement(Selectors::WIZARD_STEP . '[data-step-key="schema.account::data::wizard-step"] [style*="--cols-lg: repeat(2, minmax(0, 1fr))"] input[name="name"]')
        ->toContainElement(Selectors::WIZARD_STEP . '[data-step-key="schema.review::data::wizard-step"] [style*="--cols-lg: repeat(3, minmax(0, 1fr))"] input[name="notes"]');
});

it('keeps a hidden label as screen-reader text, so its header button and its panel keep their name', function (): void {
    expect($this->renderSchemaComponent(Wizard::make([Step::make('Account')->hiddenLabel()])))
        ->toContainElement(Selectors::WIZARD_STEP_BUTTON . '[id="schema.account::data::wizard-step-tab"] .sy-sc-wizard-header-step-label-hidden', 'Account')
        ->toContainElement(Selectors::WIZARD_STEP . '[aria-labelledby="schema.account::data::wizard-step-tab"]');
});

it('renders as a focusable group its header button names, active on the start step only, and named by its own label when the header is hidden', function (): void {
    $wizard = $this->renderSchemaComponent(Wizard::make([Step::make('Account'), Step::make('Review')])->startOnStep(2));
    $panel = 'div' . Selectors::WIZARD_STEP . '[tabindex="0"][data-sylament--schemas--wizard-target="step"][data-action="expand->sylament--schemas--wizard#expand"]';

    expect($wizard)->toContainElement(Selectors::WIZARD_STEP_BUTTON . '[id="schema.review::data::wizard-step-tab"]', 'check 02 Review')
        ->toContainElement($panel . '.sy-active[id="schema.review::data::wizard-step"][aria-labelledby="schema.review::data::wizard-step-tab"]:not([aria-label])')
        ->toContainElement($panel . ':not(.sy-active)[id="schema.account::data::wizard-step"][aria-labelledby="schema.account::data::wizard-step-tab"]')
        ->and($this->renderSchemaComponent(Wizard::make([Step::make('Account')])->hiddenHeader()))
        ->toContainElement($panel . '.sy-active[aria-label="Account"]:not([aria-labelledby])');
});

it('refuses to render outside a wizard, naming both classes', function (): void {
    expect(fn () => $this->renderSchemaComponent(Step::make('Account')))
        ->toThrow(function (RuntimeError $error): void {
            expect($error->getPrevious())->toBeInstanceOf(LogicException::class)
                ->and($error->getPrevious()?->getMessage())->toStartWith('A ' . Step::class . ' renders only inside a ' . Wizard::class . ' ');
        });
});
