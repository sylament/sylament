<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Twig\Markup;

/**
 * A host with actions and no schemas, each recording its name and arguments in the request's
 * {@see HookLog} when it runs: one per signal that opens a modal (a heading, a description,
 * content, a content footer, `modal()`, a confirmation), a heading behind `modalHidden()`, a
 * `modalHidden()` that `modal(true)` overrides, and `compose`, whose footer adds a
 * create-another action, a draft action and a group holding a share action.
 */
#[AsLiveComponent(name: 'Actions:ModalChromeHost', template: '@Fixtures/actions/ModalChromeHost.html.twig')]
class ModalChromeHost implements InteractsWithActions
{
    use DefaultActionTrait;
    use HasActions;

    public function __construct(
        private readonly HookLog $hookLog,
    ) {
    }

    public function headedAction(): Action
    {
        return $this->recording('headed')->modalHeading('Headed');
    }

    public function describedAction(): Action
    {
        return $this->recording('described')->modalDescription('Described');
    }

    public function contentedAction(): Action
    {
        return $this->recording('contented')->modalContent(new Markup('<p>Contented</p>', 'UTF-8'));
    }

    public function footedAction(): Action
    {
        return $this->recording('footed')->modalContentFooter(new Markup('<p>Footed</p>', 'UTF-8'));
    }

    public function forcedAction(): Action
    {
        return $this->recording('forced')->modal();
    }

    public function confirmedAction(): Action
    {
        return $this->recording('confirmed')->requiresConfirmation();
    }

    public function hushedAction(): Action
    {
        return $this->recording('hushed')->modalHeading('Hushed')->modalHidden();
    }

    public function insistedAction(): Action
    {
        return $this->recording('insisted')->modalHidden()->modal(true);
    }

    public function composeAction(): Action
    {
        return $this->recording('compose')
            ->modalHeading('Compose')
            ->extraModalFooterActions(fn (Action $action): array => [
                $action->makeModalSubmitAction('another', arguments: ['another' => true])->label('Compose another'),
                $this->recording('saveDraft'),
                ActionGroup::make([
                    $this->recording('share'),
                ]),
            ]);
    }

    private function recording(string $name): Action
    {
        return Action::make($name)->action(function (array $arguments) use ($name): void {
            $this->hookLog->record($name . ($arguments === [] ? '' : ': ' . json_encode($arguments, JSON_THROW_ON_ERROR)));
        });
    }
}
