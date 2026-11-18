<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Pages\CreateRecord;
use Sylament\Tests\Fixtures\Panels\Resources\PottedPlantResource;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A create page whose button row declares, beside its submit button, an inline `repot` action that asks for confirmation
 * in a modal whose optional `name` field must be unique over a record class it does not name, and a group of `water` and `prune`;
 * each records its name in the request's {@see HookLog}.
 */
#[AsLiveComponent]
final class CreateGroupedRowPottedPlant extends CreateRecord
{
    protected static string $resource = PottedPlantResource::class;

    private HookLog $hookLog;

    #[Required]
    public function setHookLog(HookLog $hookLog): void
    {
        $this->hookLog = $hookLog;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            Action::make('repot')
                ->requiresConfirmation()
                ->schema([TextInput::make('name')->required(false)->unique()])
                ->action(fn () => $this->hookLog->record('repot')),
            ActionGroup::make([
                Action::make('water')->action(fn () => $this->hookLog->record('water')),
                Action::make('prune')->action(fn () => $this->hookLog->record('prune')),
            ]),
        ];
    }
}
