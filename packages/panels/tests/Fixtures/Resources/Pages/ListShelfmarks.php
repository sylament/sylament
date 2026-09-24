<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Panels\Resources\Pages\ListRecords;
use Sylament\Panels\Tests\Fixtures\Resources\ShelfmarkResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent]
final class ListShelfmarks extends ListRecords
{
    protected static string $resource = ShelfmarkResource::class;
}
