<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Components\Wizard;
use Sylament\Schemas\Components\Wizard\Step;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Schemas\Selectors;
use Sylament\Tests\Fixtures\Schemas\WizardForm;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Twig\Markup;

describe('render', function (): void {
    it('shows each step it holds as a header button and a panel, in order, the last list it is given winning, and none when it holds none', function (): void {
        $wizard = $this->renderSchemaComponent(Wizard::make([Step::make('Draft')])->steps([Step::make('Account'), Step::make('Address'), Step::make('Review')]));

        expect($wizard)->toContainButtons(Selectors::WIZARD_STEP_BUTTON, ['check 01 Account', 'check 02 Address Not completed', 'check 03 Review Not completed'])
            ->and($wizard->filter(Selectors::WIZARD_STEP)->each(static fn (Crawler $panel): ?string => $panel->attr('data-step-key')))
            ->toBe(['schema.account::data::wizard-step', 'schema.address::data::wizard-step', 'schema.review::data::wizard-step'])
            ->and($empty = $this->renderSchemaComponent(Wizard::make()))
            ->toContainElement(Selectors::WIZARD . '[data-controller="sylament--schemas--wizard"]')
            ->and($empty->filter(Selectors::WIZARD_STEP_BUTTON . ', ' . Selectors::WIZARD_STEP)->count())->toBe(0);
    });

    it('numbers and opens only its visible steps, so a hidden step or another child never shifts which step is which', function (): void {
        $wizard = $this->renderSchemaComponent(Wizard::make()->startOnStep(2)->components([
            Step::make('Account'),
            Step::make('Survey')->hidden(),
            Text::make('Three short steps'),
            Step::make('Address'),
        ]));

        expect($wizard)->toContainButtons(Selectors::WIZARD_STEP_BUTTON, ['check 01 Account Completed', 'check 02 Address'])
            ->toContainElement(Selectors::WIZARD_STEP . SupportSelectors::ACTIVE . '[data-step-key="schema.address::data::wizard-step"]')
            ->and($wizard->filter(Selectors::WIZARD_STEP)->count())->toBe(2)
            ->and($wizard->filter(Selectors::WIZARD)->text())->not->toContain('Three short steps');
    });

    it('is keyed by its label under the state path, and leaves the key of what its steps hold as it was', function (): void {
        $inner = Text::make('Who you are')->key('inner');
        $wizard = Wizard::make([Step::make('Account')->components([$inner])]);
        $schema = Schema::make()->key('schema')->components([$wizard]);

        expect($this->renderSchemaComponent(Wizard::make([Step::make('Account')])))
            ->toContainElement(Selectors::WIZARD . Selectors::wizardValue('key', 'schema.data::wizard'))
            ->and($this->renderSchemaComponent(Wizard::make([Step::make('Account')])->label('Checkout Flow')))
            ->toContainElement(Selectors::WIZARD . Selectors::wizardValue('key', 'schema.checkout-flow::data::wizard'))
            ->and($schema->getComponent('wizard'))->toBe($wizard)
            ->and($schema->getComponent('inner'))->toBe($inner);
    });

    it('names its header after its label, and leaves an unlabelled header unnamed', function (): void {
        expect($this->renderSchemaComponent(Wizard::make([Step::make('Account')])->label('Checkout')))
            ->toContainElement('ol[role="list"][aria-label="Checkout"]', 'check 01 Account')
            ->and($this->renderSchemaComponent(Wizard::make([Step::make('Account')])))
            ->toContainElement('ol[role="list"]:not([aria-label])', 'check 01 Account');
    });

    it('wires its controller to its root, its header steps and its footer slots, and passes extra attributes to its root', function (): void {
        $wizard = $this->renderSchemaComponent(Wizard::make([Step::make('Account'), Step::make('Review')])->extraAttributes(['data-tour' => 'checkout']));
        $controller = Selectors::WIZARD . '[data-controller="sylament--schemas--wizard"][data-tour="checkout"]'
            . Selectors::wizardValue('schema-key', 'schema') . Selectors::wizardValue('start-step', '1')
            . ':not(' . Selectors::wizardValue('skippable') . '):not(' . Selectors::wizardValue('query-string-key') . ')'
            . '[data-action~="next-wizard-step@window->sylament--schemas--wizard#onNextStepEvent"]'
            . '[data-action~="go-to-wizard-step@window->sylament--schemas--wizard#onGoToStepEvent"]'
            . '[data-action~="reset-schema-component-state@window->sylament--schemas--wizard#resetState"]'
            . '[data-action~="keydown->sylament--schemas--wizard#onKeydown"]';

        expect($wizard)->toContainElement($controller)
            ->toContainElement($controller . ' ol[data-sylament--schemas--wizard-target="header"] li' . Selectors::WIZARD_HEADER_STEP . '[data-step-key="schema.review::data::wizard-step"] button[data-sylament--schemas--wizard-target="headerButton"][data-action="click->sylament--schemas--wizard#goToStepFromHeader"][data-step-key="schema.review::data::wizard-step"][aria-controls="schema.review::data::wizard-step"]', 'check 02 Review Not completed')
            ->toContainElement($controller . ' [data-sylament--schemas--wizard-target="previousSlot"]', 'Back')
            ->toContainElement($controller . ' ' . Selectors::WIZARD_CANCEL_SLOT)
            ->toContainElement($controller . ' [data-sylament--schemas--wizard-target="nextSlot"]', 'Next')
            ->toContainElement($controller . ' ' . Selectors::WIZARD_SUBMIT_SLOT);
    });

    it('opens on the step startOnStep() names, the earlier ones completed and reachable from the header, the later ones not', function (): void {
        $wizard = $this->renderSchemaComponent(Wizard::make([Step::make('Account'), Step::make('Address'), Step::make('Review')])->startOnStep(2));

        expect($wizard)->toContainElement(Selectors::WIZARD . Selectors::wizardValue('start-step', '2'))
            ->toContainElement('li.sy-completed:not(' . SupportSelectors::ACTIVE . ') ' . Selectors::WIZARD_STEP_BUTTON . ':not([disabled]):not([aria-current])', 'check 01 Account Completed')
            ->toContainElement('li' . SupportSelectors::ACTIVE . ':not(.sy-completed) ' . Selectors::WIZARD_STEP_BUTTON . '[disabled][aria-current="step"]', 'check 02 Address')
            ->toContainElement('li:not(' . SupportSelectors::ACTIVE . '):not(.sy-completed) ' . Selectors::WIZARD_STEP_BUTTON . '[disabled]:not([aria-current])', 'check 03 Review Not completed')
            ->and($wizard->filter(Selectors::WIZARD_STEP . SupportSelectors::ACTIVE)->each(static fn (Crawler $panel): ?string => $panel->attr('data-step-key')))
            ->toBe(['schema.address::data::wizard-step']);
    });

    it('shows Back or cancel and Next or submit by where its start step sits', function (?int $startStep, array $shown, array $hidden): void {
        $wizard = Wizard::make([Step::make('Account'), Step::make('Address'), Step::make('Review')]);
        $rendered = $this->renderSchemaComponent($startStep === null ? $wizard : $wizard->startOnStep($startStep));
        $slots = static fn (string $state): array => $rendered->filter("[data-sylament--schemas--wizard-target\$=\"Slot\"]{$state}")
            ->each(static fn (Crawler $slot): ?string => $slot->attr('data-sylament--schemas--wizard-target'));

        expect($slots(':not(' . SupportSelectors::HIDDEN . ')'))->toBe($shown)
            ->and($slots(SupportSelectors::HIDDEN))->toBe($hidden);
    })->with([
        'the first step, by default' => [null, ['cancelSlot', 'nextSlot'], ['previousSlot', 'submitSlot']],
        'a middle step' => [2, ['previousSlot', 'nextSlot'], ['cancelSlot', 'submitSlot']],
        'the last step' => [3, ['previousSlot', 'submitSlot'], ['cancelSlot', 'nextSlot']],
    ]);

    it('lets every header button be clicked when skippable', function (): void {
        $wizard = $this->renderSchemaComponent(Wizard::make([Step::make('Account'), Step::make('Address'), Step::make('Review')])->skippable());

        expect($wizard)->toContainElement(Selectors::WIZARD . Selectors::wizardValue('skippable', 'true'))
            ->toContainButtons(Selectors::WIZARD_STEP_BUTTON . ':not([disabled])', ['check 01 Account', 'check 02 Address Not completed', 'check 03 Review Not completed']);
    });

    it('leaves out its header when hidden, each panel taking its step label as its name', function (): void {
        $wizard = $this->renderSchemaComponent(Wizard::make([Step::make('Account'), Step::make('Review')])->hiddenHeader());

        expect($wizard)->toContainElement(Selectors::WIZARD_STEP . '[aria-label="Account"]:not([aria-labelledby])')
            ->toContainElement(Selectors::WIZARD_STEP . '[aria-label="Review"]:not([aria-labelledby])')
            ->and($wizard->filter(Selectors::WIZARD . ' ol, ' . Selectors::WIZARD_STEP_BUTTON)->count())->toBe(0);
    });
});

describe('footer', function (): void {
    it('advances through a Next button whose wrapper the controller clicks, its icon after its label, which a disabled Next leaves inert', function (): void {
        expect($this->renderSchemaComponent(Wizard::make([Step::make('Account'), Step::make('Review')])))
            ->toContainElement('[data-sylament--schemas--wizard-target="nextSlot"][data-action="click->sylament--schemas--wizard#requestNextStep"][data-loading="addClass(sy-disabled)"] ' . SupportSelectors::BUTTON . SupportSelectors::COLORED . ':not([disabled]):not([data-action])', 'Next')
            ->and($this->renderSchemaComponent(Wizard::make([Step::make('Account'), Step::make('Review')])->nextAction(fn (Action $action): Action => $action->label('Continue')->icon('support:star')->disabled())))
            ->toContainElement('[data-sylament--schemas--wizard-target="nextSlot"]:not([data-action])[data-loading="addClass(sy-disabled)"] ' . SupportSelectors::BUTTON . '[disabled]', 'ContinueStar');
    });

    it('steps back through a gray Back button whose wrapper the controller clicks, a disabled Back also disabling every header button', function (): void {
        $steps = static fn (): array => [Step::make('Account'), Step::make('Review')];

        expect($this->renderSchemaComponent(Wizard::make($steps())->startOnStep(2)))
            ->toContainElement('[data-sylament--schemas--wizard-target="previousSlot"][data-action="click->sylament--schemas--wizard#goToPreviousStep"] ' . SupportSelectors::BUTTON . SupportSelectors::GRAY . ':not([disabled]):not([data-action])', 'Back')
            ->toContainElement(Selectors::WIZARD_STEP_BUTTON . ':not([disabled])', 'check 01 Account Completed')
            ->and($disabled = $this->renderSchemaComponent(Wizard::make($steps())->startOnStep(2)->skippable()->previousAction(fn (Action $action): Action => $action->label('Previous')->disabled())))
            ->toContainElement('[data-sylament--schemas--wizard-target="previousSlot"]:not([data-action]) ' . SupportSelectors::BUTTON . '[disabled]', 'Previous')
            ->toContainButtons(Selectors::WIZARD_STEP_BUTTON . '[disabled]', ['check 01 Account Completed', 'check 02 Review'])
            ->and($disabled->filter(Selectors::WIZARD_STEP_BUTTON . ':not([disabled])')->count())->toBe(0);
    });

    it('renders what its cancel and submit slots are given, a string escaped, a Markup as it is and an action as its button, keeping only Next and Back as its own actions', function (Closure $slot, string $selector, string $text): void {
        $wizard = Wizard::make([Step::make('Account')])->cancelAction($slot())->submitAction($slot());
        $rendered = $this->renderSchemaComponent($wizard);

        expect($rendered)->toContainElement(Selectors::WIZARD_CANCEL_SLOT . $selector, $text)
            ->toContainElement(Selectors::WIZARD_SUBMIT_SLOT . $selector, $text)
            ->and($wizard->getAction('next'))->toBeInstanceOf(Action::class)
            ->and($wizard->getAction('previous'))->toBeInstanceOf(Action::class)
            ->and($wizard->getAction('leave'))->toBeNull();
    })->with([
        'a string' => [static fn (): string => '<b>Leave</b>', '', '<b>Leave</b>'],
        'a Markup' => [static fn (): Markup => new Markup('<a href="/orders">Leave</a>', 'UTF-8'), ' a[href="/orders"]', 'Leave'],
        'an action' => [static fn (): Action => Action::make('leave'), ' ' . SupportSelectors::BUTTON, 'Leave'],
    ]);
});

describe('query string', function (): void {
    it('opens on the step its query string names by absolute key, under the key it is told', function (): void {
        $this::getContainer()->get('request_stack')->push(Request::create('/checkout?step=schema.review::data::wizard-step&stage=schema.address::data::wizard-step'));
        $steps = static fn (): array => [Step::make('Account'), Step::make('Address'), Step::make('Review')];

        expect($this->renderSchemaComponent(Wizard::make($steps())->persistStepInQueryString()))
            ->toContainElement(Selectors::WIZARD . Selectors::wizardValue('query-string-key', 'step') . Selectors::wizardValue('start-step', '3'))
            ->toContainElement(Selectors::WIZARD_STEP_BUTTON . '[aria-current="step"]', 'check 03 Review')
            ->and($this->renderSchemaComponent(Wizard::make($steps())->persistStepInQueryString('stage')))
            ->toContainElement(Selectors::WIZARD . Selectors::wizardValue('query-string-key', 'stage'))
            ->toContainElement(Selectors::WIZARD_STEP_BUTTON . '[aria-current="step"]', 'check 02 Address')
            ->and($this->renderSchemaComponent(Wizard::make($steps())))
            ->toContainElement(Selectors::WIZARD_STEP_BUTTON . '[aria-current="step"]', 'check 01 Account');
    });

    it('keeps its start step when its query string names none of its steps', function (string $uri): void {
        $this::getContainer()->get('request_stack')->push(Request::create($uri));

        expect($this->renderSchemaComponent(Wizard::make([Step::make('Account'), Step::make('Address'), Step::make('Review')])->persistStepInQueryString()->startOnStep(2)))
            ->toContainElement(Selectors::WIZARD_STEP_BUTTON . '[aria-current="step"]', 'check 02 Address');
    })->with([
        'an unknown key' => '/checkout?step=schema.survey::data::wizard-step',
        'a list' => '/checkout?step[]=schema.review::data::wizard-step',
    ]);
});

describe('moving between steps', function (): void {
    it('validates the step it leaves, fills what only the next visible step holds with null and tells the browser to advance', function (): void {
        $tester = $this->createLiveComponent(WizardForm::class, ['data' => ['name' => 'Ada']])
            ->call('callSchemaComponentMethod', ['componentKey' => 'form.data::wizard', 'method' => 'nextStep', 'arguments' => ['currentStepIndex' => 0]])
            ->assertHasNoFormErrors();

        expect($this->dispatchedBrowserEvents($tester, 'next-wizard-step'))->toBe([['key' => 'form.data::wizard']])
            ->and($tester->component()->data)->toBe(['name' => 'Ada', 'email' => null, 'city' => null]);
    });

    it("stops on a failing step, filing its errors under that step's key and leaving the next step as it was", function (): void {
        $tester = $this->createLiveComponent(WizardForm::class, ['data' => ['name' => 'Ada']])
            ->call('callSchemaComponentMethod', ['componentKey' => 'form.data::wizard', 'method' => 'nextStep', 'arguments' => ['currentStepIndex' => 1]])
            ->assertHasFormErrors(['email', 'city'])
            ->assertHasNoFormErrors(['name', 'referrer']);

        expect($this->dispatchedBrowserEvents($tester, 'form-validation-error'))->toBe([['schemaKey' => 'form.address::data::wizard-step']])
            ->and($this->dispatchedBrowserEvents($tester, 'next-wizard-step'))->toBe([])
            ->and($tester->component()->data)->toBe(['name' => 'Ada']);
    });

    it('advances a skippable wizard without validating the step it leaves', function (): void {
        $tester = $this->createLiveComponent(WizardForm::class, ['skippable' => true])
            ->call('callSchemaComponentMethod', ['componentKey' => 'form.data::wizard', 'method' => 'nextStep', 'arguments' => ['currentStepIndex' => 0]])
            ->assertHasNoFormErrors();

        expect($this->dispatchedBrowserEvents($tester, 'next-wizard-step'))->toBe([['key' => 'form.data::wizard']])
            ->and($this::getContainer()->get(HookLog::class)->hooks)->toBe([])
            ->and($tester->component()->data)->toBe([]);
    });

    it('ignores an index that names none of its visible steps', function (): void {
        $tester = $this->createLiveComponent(WizardForm::class)
            ->call('callSchemaComponentMethod', ['componentKey' => 'form.data::wizard', 'method' => 'nextStep', 'arguments' => ['currentStepIndex' => 3]])
            ->assertHasNoFormErrors();

        expect($this->dispatchedBrowserEvents($tester, 'sylament:schema-component-method-result'))->toBe([
            ['componentKey' => 'form.data::wizard', 'method' => 'nextStep', 'fileKey' => null, 'result' => null],
        ])
            ->and($this->dispatchedBrowserEvents($tester, 'next-wizard-step'))->toBe([])
            ->and($this::getContainer()->get(HookLog::class)->hooks)->toBe([]);
    });

    it('stops the advance without dispatching the next-step event when a step hook halts', function (string $hook, array $hooks): void {
        $tester = $this->createLiveComponent(WizardForm::class, ['data' => ['name' => 'Ada'], 'haltIn' => $hook])
            ->call('callSchemaComponentMethod', ['componentKey' => 'form.data::wizard', 'method' => 'nextStep', 'arguments' => ['currentStepIndex' => 0]]);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe($hooks)
            ->and($this->dispatchedBrowserEvents($tester, 'next-wizard-step'))->toBe([])
            ->and($tester->component()->data)->toBe(['name' => 'Ada']);
    })->with([
        'before validation' => ['beforeValidation', ['beforeValidation: Ada']],
        'after validation' => ['afterValidation', ['beforeValidation: Ada', 'afterValidation: Ada']],
    ]);

    it('goes to a step it has reached and tells the browser, and ignores a later step unless skippable, or a step it does not show', function (array $props, string $step, bool $goes): void {
        $tester = $this->createLiveComponent(WizardForm::class, $props)->call('goToWizardStep', ['step' => $step]);

        expect($this->dispatchedBrowserEvents($tester, 'go-to-wizard-step'))->toBe($goes ? [['key' => 'form.data::wizard', 'step' => $step]] : []);
    })->with([
        'an earlier step' => [['startStep' => 3], 'form.address::data::wizard-step', true],
        'the current step' => [[], 'form.account::data::wizard-step', true],
        'a later step' => [[], 'form.address::data::wizard-step', false],
        'a later step of a skippable wizard' => [['skippable' => true], 'form.review::data::wizard-step', true],
        'a hidden step of a skippable wizard' => [['skippable' => true], 'form.survey::data::wizard-step', false],
    ]);

    it('moves the step it has reached back one, never before the first, and announces nothing', function (): void {
        $tester = $this->createLiveComponent(WizardForm::class, ['startStep' => 3])
            ->call('stepBackThenGoTo', ['from' => 2, 'step' => 'form.review::data::wizard-step']);

        expect($this->dispatchedBrowserEvents($tester, 'go-to-wizard-step'))->toBe([])
            ->and($this->dispatchedBrowserEvents($tester->call('stepBackThenGoTo', ['from' => 2, 'step' => 'form.address::data::wizard-step']), 'go-to-wizard-step'))
            ->toBe([['key' => 'form.data::wizard', 'step' => 'form.address::data::wizard-step']])
            ->and($this->dispatchedBrowserEvents($this->createLiveComponent(WizardForm::class)->call('stepBackThenGoTo', ['from' => 0, 'step' => 'form.account::data::wizard-step']), 'go-to-wizard-step'))
            ->toBe([['key' => 'form.data::wizard', 'step' => 'form.account::data::wizard-step']]);

        $previous = $this->createLiveComponent(WizardForm::class, ['startStep' => 2])
            ->call('callSchemaComponentMethod', ['componentKey' => 'form.data::wizard', 'method' => 'previousStep', 'arguments' => ['currentStepIndex' => 1]]);

        expect($this->dispatchedBrowserEvents($previous, 'sylament:schema-component-method-result'))->toBe([
            ['componentKey' => 'form.data::wizard', 'method' => 'previousStep', 'fileKey' => null, 'result' => null],
        ])
            ->and($this->dispatchedBrowserEvents($previous, 'next-wizard-step'))->toBe([])
            ->and($this->dispatchedBrowserEvents($previous, 'go-to-wizard-step'))->toBe([]);
    });

    it('moves the step it has reached to the step it goes to, so a later step is refused for the rest of the request', function (): void {
        $tester = $this->createLiveComponent(WizardForm::class, ['startStep' => 3])
            ->call('goToThenGoTo', ['first' => 'form.account::data::wizard-step', 'step' => 'form.address::data::wizard-step']);

        expect($this->dispatchedBrowserEvents($tester, 'go-to-wizard-step'))->toBe([['key' => 'form.data::wizard', 'step' => 'form.account::data::wizard-step']]);
    });

    it('moves the step it has reached on as it advances, and a failing step aborts the request before anything reads it', function (): void {
        $passing = $this->createLiveComponent(WizardForm::class, ['data' => ['name' => 'Ada']])
            ->call('advanceThenGoTo', ['from' => 0, 'step' => 'form.address::data::wizard-step']);

        expect($this->dispatchedBrowserEvents($passing, 'next-wizard-step'))->toBe([['key' => 'form.data::wizard']])
            ->and($this->dispatchedBrowserEvents($passing, 'go-to-wizard-step'))->toBe([['key' => 'form.data::wizard', 'step' => 'form.address::data::wizard-step']]);

        $failing = $this->createLiveComponent(WizardForm::class)
            ->call('advanceThenGoTo', ['from' => 0, 'step' => 'form.address::data::wizard-step'])
            ->assertHasFormErrors(['name']);

        expect($this->dispatchedBrowserEvents($failing, 'go-to-wizard-step'))->toBe([]);
    });
});
