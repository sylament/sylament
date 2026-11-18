<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Tests\Fixtures\Panels\Resources\TopBandedArticleResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent]
final class EditTopBandedArticle extends EditRecord
{
    protected static string $resource = TopBandedArticleResource::class;
}
