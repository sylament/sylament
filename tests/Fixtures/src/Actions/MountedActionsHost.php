<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Two modal actions whose mounted stack a test reads: `rename` validates its form, and
 * `hold` halts, so it stays mounted after its call.
 */
#[AsLiveComponent(name: 'Actions:MountedActionsHost', template: '@Fixtures/actions/MountedActionsHost.html.twig')]
class MountedActionsHost implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    public function schemas(): array
    {
        return [];
    }

    public function renameAction(): Action
    {
        return Action::make('rename')
            ->schema([
                TextInput::make('name')->required(),
            ]);
    }

    public function holdAction(): Action
    {
        return Action::make('hold')
            ->requiresConfirmation()
            ->action(function (Action $action): void {
                $action->halt();
            });
    }
}
