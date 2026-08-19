<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Twig\Components;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Schemas\Components\Wizard;
use Sylament\Schemas\Components\Wizard\Step;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The modal-wizard host: `onboard` declares its modal schema as wizard steps,
 * `modified` adds a `modifyWizardUsing()` hook, and `edit` is the plain
 * schema-bearing control whose footer actions must survive.
 */
#[AsLiveComponent(name: 'WizardActionHost', template: '@ActionsFixtures/components/WizardActionHost.html.twig')]
class WizardActionHost implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp]
    public array $savedData = [];

    /**
     * @return array<string>
     */
    public function schemas(): array
    {
        return [];
    }

    public function onboardAction(): Action
    {
        return Action::make('onboard')
            ->steps([
                Step::make('Details')->components([
                    ActionSchemaField::make('title'),
                ]),
                Step::make('Review'),
            ])
            ->action(function (array $data): void {
                $this->savedData = $data;
            });
    }

    public function modifiedAction(): Action
    {
        return Action::make('modified')
            ->steps([
                Step::make('Details')->components([
                    ActionSchemaField::make('title'),
                ]),
                Step::make('Review'),
            ])
            ->modifyWizardUsing(fn (Wizard $wizard): Wizard => $wizard->skippable())
            ->action(fn (): null => null);
    }

    public function editAction(): Action
    {
        return Action::make('edit')
            ->schema([
                ActionSchemaField::make('title'),
            ])
            ->action(fn (): null => null);
    }
}
