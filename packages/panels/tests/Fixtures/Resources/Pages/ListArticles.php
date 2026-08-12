<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Actions\Action;
use Sylament\Notifications\Notification;
use Sylament\Panels\Resources\Pages\ListRecords;
use Sylament\Panels\Tests\Fixtures\Resources\ArticleResource;

final class ListArticles extends ListRecords
{
    protected static string $resource = ArticleResource::class;

    /**
     * Declared as `<name>Action()` so a mounted header action resolves through
     * actions' method discovery on the way back in — the contract a header
     * action with a modal depends on.
     */
    public function purgeAction(): Action
    {
        return Action::make('purge')
            ->requiresConfirmation()
            ->action(function (): void {
                Notification::make()
                    ->title('Purged')
                    ->success()
                    ->send();
            });
    }

    public function getHeaderActions(): array
    {
        return [
            $this->purgeAction(),
        ];
    }
}
