<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Actions\CreateAction;
use Sylament\Actions\DeleteAction;
use Sylament\Panels\Resources\Pages\ListRecords;
use Sylament\Tests\Fixtures\Panels\Resources\ModalArticleResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * Declares a create and a delete header action, and names its table, so its page number rides `?articlesPage=`.
 */
#[AsLiveComponent]
final class ListModalArticles extends ListRecords
{
    protected static string $resource = ModalArticleResource::class;

    public function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            DeleteAction::make(),
        ];
    }

    public function getTableQueryStringIdentifier(): string
    {
        return 'articles';
    }
}
