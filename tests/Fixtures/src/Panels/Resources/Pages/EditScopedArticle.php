<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Actions\EditAction;
use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Tests\Fixtures\Panels\Resources\ScopedArticleResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent]
final class EditScopedArticle extends EditRecord
{
    protected static string $resource = ScopedArticleResource::class;

    public function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
