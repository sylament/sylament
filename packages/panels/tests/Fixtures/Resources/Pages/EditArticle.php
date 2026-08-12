<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Actions\EditAction;
use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Panels\Tests\Fixtures\Resources\ArticleResource;

final class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    /**
     * A record-bound `EditAction` on the very page that edits the record — the
     * one arrangement in which the url hook must decline, since linking here
     * would point at the page already open.
     */
    public function editAction(): EditAction
    {
        return EditAction::make()->record($this->getRecord());
    }

    public function getHeaderActions(): array
    {
        return [
            $this->cacheAction($this->editAction()),
        ];
    }
}
