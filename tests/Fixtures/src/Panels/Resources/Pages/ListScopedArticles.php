<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Actions\CreateAction;
use Sylament\Actions\DeleteAction;
use Sylament\Actions\EditAction;
use Sylament\Panels\Resources\Pages\ListRecords;
use Sylament\Tests\Fixtures\Entity\HookedArticle;
use Sylament\Tests\Fixtures\Panels\Resources\ScopedArticleResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent]
final class ListScopedArticles extends ListRecords
{
    protected static string $resource = ScopedArticleResource::class;

    public function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            CreateAction::make('createHooked')->recordClass(HookedArticle::class),
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
