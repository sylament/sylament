<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Panels\Resources\Pages\ListRecords;
use Sylament\Tests\Fixtures\Panels\Resources\SheetedBinderResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent]
final class ListSheetedBinders extends ListRecords
{
    protected static string $resource = SheetedBinderResource::class;
}
