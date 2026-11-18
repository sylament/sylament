<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Tests\Fixtures\Panels\Resources\BandedArticleResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent]
final class EditBandedArticle extends EditRecord
{
    protected static string $resource = BandedArticleResource::class;
}
