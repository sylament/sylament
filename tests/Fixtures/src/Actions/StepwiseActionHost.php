<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Wizard;
use Sylament\Schemas\Components\Wizard\Step;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A host whose actions open wizard modals over an Account and a Review step, recording their
 * data in the request's {@see HookLog} when they run: `onboard` declares the steps,
 * `resume` opens on the second and lets every step be skipped to, `sealed` disables its
 * schema, `retouched` declares its
 * steps through `schema()` and only marks the wizard it is handed with a `data-tour`
 * attribute, and `replaced` hands back a wizard of its own.
 */
#[AsLiveComponent(name: 'Actions:StepwiseActionHost', template: '@Fixtures/actions/StepwiseActionHost.html.twig')]
class StepwiseActionHost implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    public function __construct(
        private readonly HookLog $hookLog,
    ) {
    }

    public function schemas(): array
    {
        return [];
    }

    public function onboardAction(): Action
    {
        return $this->recording('onboard')->steps($this->steps());
    }

    public function resumeAction(): Action
    {
        return $this->recording('resume')
            ->steps($this->steps())
            ->startOnStep(2)
            ->skippableSteps();
    }

    public function sealedAction(): Action
    {
        return $this->recording('sealed')
            ->steps($this->steps())
            ->disabledSchema();
    }

    public function retouchedAction(): Action
    {
        return $this->recording('retouched')
            ->schema($this->steps())
            ->modifyWizardUsing(function (Wizard $wizard): void {
                $wizard->extraAttributes(['data-tour' => 'retouched']);
            });
    }

    public function replacedAction(): Action
    {
        return $this->recording('replaced')
            ->steps($this->steps())
            ->modifyWizardUsing(fn (): Wizard => Wizard::make([
                Step::make('Replacement'),
            ]));
    }

    /**
     * @return array<Step>
     */
    private function steps(): array
    {
        return [
            Step::make('Account')->components([
                TextInput::make('name'),
            ]),
            Step::make('Review')->components([
                TextInput::make('note'),
            ]),
        ];
    }

    private function recording(string $name): Action
    {
        return Action::make($name)->action(function (array $data) use ($name): void {
            $this->hookLog->record("{$name}: " . json_encode($data, JSON_THROW_ON_ERROR));
        });
    }
}
