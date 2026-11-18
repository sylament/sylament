<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Panels\Resources\Pages\CreateRecord;
use Sylament\Tests\Fixtures\Panels\Resources\NoIndexResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent]
final class CreateNoIndexArticle extends CreateRecord
{
    protected static string $resource = NoIndexResource::class;
}
