<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Panels\Resources\Pages\ListRecords;
use Sylament\Panels\Tests\Fixtures\Resources\GroupingArticleResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent]
final class ListGroupingArticles extends ListRecords
{
    protected static string $resource = GroupingArticleResource::class;
}
