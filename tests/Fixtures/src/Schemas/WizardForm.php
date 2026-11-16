<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

use LogicException;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Components\Wizard;
use Sylament\Schemas\Components\Wizard\Step;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Get;
use Sylament\Support\Exceptions\Halt;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A form holding one wizard, keyed `form.data::wizard`, whose visible steps are Account
 * (`name`, at most ten characters), Address (`email`, and `city` inside a group) and Review
 * (`notes`), each field required; a hidden Survey step and a text sit between the first
 * two. Account's before- and after-validation hooks record in the request's {@see HookLog},
 * and the one `haltIn` names halts after recording. `skippable` and `startStep` configure
 * the wizard; the live actions reach `goToStep()`, alone or after a `previousStep()`, a
 * `nextStep()` or another `goToStep()` in the same request.
 */
#[AsLiveComponent(name: 'Schemas:WizardForm', template: '@Fixtures/schemas/WizardForm.html.twig')]
class WizardForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    #[LiveProp]
    public bool $skippable = false;

    #[LiveProp]
    public int $startStep = 1;

    #[LiveProp]
    public ?string $haltIn = null;

    private HookLog $hookLog;

    #[Required]
    public function setHookLog(HookLog $hookLog): void
    {
        $this->hookLog = $hookLog;
    }

    public function schemas(): array
    {
        return ['form'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Wizard::make()
                    ->skippable($this->skippable)
                    ->startOnStep($this->startStep)
                    ->components([
                        Step::make('Account')
                            ->beforeValidation(fn (Get $get) => $this->recordHook('beforeValidation', $get))
                            ->afterValidation(fn (Get $get) => $this->recordHook('afterValidation', $get))
                            ->components([TextInput::make('name')->required()->maxLength(10)]),
                        Step::make('Survey')
                            ->hidden()
                            ->components([TextInput::make('referrer')->required()]),
                        Text::make('Three short steps'),
                        Step::make('Address')
                            ->components([
                                TextInput::make('email')->required(),
                                Group::make([TextInput::make('city')->required()]),
                            ]),
                        Step::make('Review')
                            ->components([TextInput::make('notes')->required()]),
                    ]),
            ]);
    }

    #[LiveAction]
    public function goToWizardStep(#[LiveArg] string $step): void
    {
        $this->wizard()->goToStep($step);
    }

    #[LiveAction]
    public function stepBackThenGoTo(#[LiveArg] int $from, #[LiveArg] string $step): void
    {
        $wizard = $this->wizard();

        $wizard->previousStep($from);
        $wizard->goToStep($step);
    }

    #[LiveAction]
    public function advanceThenGoTo(#[LiveArg] int $from, #[LiveArg] string $step): void
    {
        $wizard = $this->wizard();

        $wizard->nextStep($from);
        $wizard->goToStep($step);
    }

    #[LiveAction]
    public function goToThenGoTo(#[LiveArg] string $first, #[LiveArg] string $step): void
    {
        $wizard = $this->wizard();

        $wizard->goToStep($first);
        $wizard->goToStep($step);
    }

    private function recordHook(string $hook, Get $get): void
    {
        $name = $get('name');

        $this->hookLog->record(sprintf('%s: %s', $hook, is_string($name) ? $name : ''));

        if ($this->haltIn === $hook) {
            throw new Halt();
        }
    }

    private function wizard(): Wizard
    {
        $wizard = $this->getSchemaComponent('form.data::wizard');

        if (!$wizard instanceof Wizard) {
            throw new LogicException('The form has no wizard.');
        }

        return $wizard;
    }
}
