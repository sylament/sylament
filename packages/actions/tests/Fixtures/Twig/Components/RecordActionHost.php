<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Twig\Components;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Twig\Markup;

#[AsLiveComponent(name: 'RecordActionHost', template: '@ActionsFixtures/components/RecordActionHost.html.twig')]
class RecordActionHost implements InteractsWithActions
{
    use DefaultActionTrait;
    use HasActions;

    /**
     * @var array<int, string>
     */
    protected array $rows = [1 => 'Ada', 2 => 'Grace'];

    /**
     * @var array<int, mixed>
     */
    #[LiveProp]
    public array $deleted = [];

    public function __construct()
    {
        $this->resolveRecordUsing(fn (mixed $key): mixed => $this->rows[$key] ?? null);
    }

    public function deleteAction(): Action
    {
        return Action::make('delete')
            ->requiresConfirmation()
            ->modalHeading('Delete?')
            ->action(fn (mixed $record): mixed => $this->deleted[] = $record);
    }

    /**
     * Carries custom modal content and a content footer — a literal Markup body
     * plus an action-injected closure — for the ActionModal content-slot tests.
     */
    public function briefedAction(): Action
    {
        return Action::make('briefed')
            ->modalContent(new Markup('<p class="test-modal-content">Body</p>', 'UTF-8'))
            ->modalContentFooter(fn (Action $action): Markup => new Markup(
                "<p class=\"test-modal-content-footer\">{$action->getName()}</p>",
                'UTF-8',
            ))
            ->extraModalFooterActions([Action::make('extra')->label('Extra')])
            ->action(fn (): null => null);
    }

    public function haltAction(): Action
    {
        return Action::make('halt')
            ->requiresConfirmation()
            ->action(fn (Action $action) => $action->halt());
    }

    public function cancelAction(): Action
    {
        return Action::make('cancel')
            ->requiresConfirmation()
            ->action(fn (Action $action) => $action->cancel());
    }

    public function sideSheetAction(): Action
    {
        return Action::make('sideSheet')
            ->modalHeading('Sheet')
            ->slideOver();
    }
}
