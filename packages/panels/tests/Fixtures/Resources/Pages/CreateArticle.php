<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Panels\Resources\Pages\CreateRecord;
use Sylament\Panels\Tests\Fixtures\Resources\ArticleResource;

final class CreateArticle extends CreateRecord
{
    protected static string $resource = ArticleResource::class;
}
