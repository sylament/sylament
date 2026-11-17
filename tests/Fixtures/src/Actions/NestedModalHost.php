<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * One confirmation modal, `outer`, whose modal actions open modals of their own over it,
 * each recording its run in the request's {@see HookLog}: `inner` as it ships,
 * `cancelling` cancelling every parent when it runs, `overlaying` keeping its parent's
 * modal open beneath it, `closing` cancelling its parents when it closes, and `middle`,
 * whose own modal action `deep` cancels back to it.
 */
#[AsLiveComponent(name: 'Actions:NestedModalHost', template: '@Fixtures/actions/NestedModalHost.html.twig')]
class NestedModalHost implements InteractsWithActions
{
    use DefaultActionTrait;
    use HasActions;

    private HookLog $hookLog;

    #[Required]
    public function setHookLog(HookLog $hookLog): void
    {
        $this->hookLog = $hookLog;
    }

    public function outerAction(): Action
    {
        return $this->recording('outer')->registerModalActions([
            $this->recording('inner'),
            $this->recording('cancelling')->cancelParentActions(),
            $this->recording('overlaying')->overlayParentActions(),
            $this->recording('closing')->cancelParentActionsOnClose(),
            $this->recording('middle')->registerModalActions([
                $this->recording('deep')->cancelParentActions('middle'),
            ]),
        ]);
    }

    private function recording(string $name): Action
    {
        return Action::make($name)
            ->requiresConfirmation()
            ->action(fn () => $this->hookLog->record($name));
    }
}
