<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Tests\Fixtures\Panels\Resources\TitleKeyedArticleResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent]
final class EditTitleKeyedArticle extends EditRecord
{
    protected static string $resource = TitleKeyedArticleResource::class;
}
