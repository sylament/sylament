<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Actions\CreateAction;
use Sylament\Actions\DeleteAction;
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

    /**
     * The delete whose success redirect the page answers: the record it removes
     * is the one this page shows, so staying here would leave a page whose
     * record no longer exists.
     */
    public function deleteAction(): DeleteAction
    {
        return DeleteAction::make();
    }

    /**
     * Never a header action — it exists so a create modal can be mounted on a
     * record page, where the page's own record must not reach it.
     */
    public function createAction(): CreateAction
    {
        return CreateAction::make();
    }

    public function getHeaderActions(): array
    {
        return [
            $this->editAction(),
            $this->deleteAction(),
        ];
    }
}
