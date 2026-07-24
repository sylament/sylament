<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Twig\Components;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

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
}
