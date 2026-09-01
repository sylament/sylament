<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Actions\Action;
use Sylament\Actions\CreateAction;
use Sylament\Notifications\Notification;
use Sylament\Panels\Resources\Pages\ListRecords;
use Sylament\Panels\Tests\Fixtures\Resources\ArticleResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

#[AsLiveComponent]
final class ListArticles extends ListRecords
{
    use ProbesSubNavigation;

    protected static string $resource = ArticleResource::class;

    /**
     * Flipped by the header action no `{name}Action()` method declares, so a
     * case can tell that a second request re-resolved it from the list.
     */
    #[LiveProp]
    public bool $inlineRan = false;

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

    /**
     * Three declaration forms in one list: a prebuilt action built inline, one
     * reached through a `<name>Action()` method, and an inline action no method
     * declares — the page binds and registers all three itself.
     */
    public function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            $this->purgeAction(),
            Action::make('inline')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->inlineRan = true;
                }),
        ];
    }
}
