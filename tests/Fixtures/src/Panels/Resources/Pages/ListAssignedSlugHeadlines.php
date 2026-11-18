<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Panels\Resources\Pages\ListRecords;
use Sylament\Tests\Fixtures\Panels\Resources\AssignedSlugHeadlineResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent]
final class ListAssignedSlugHeadlines extends ListRecords
{
    protected static string $resource = AssignedSlugHeadlineResource::class;
}
