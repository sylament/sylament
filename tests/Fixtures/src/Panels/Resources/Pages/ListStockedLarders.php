<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Panels\Resources\Pages\ListRecords;
use Sylament\Tests\Fixtures\Panels\Resources\StockedLarderResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent]
final class ListStockedLarders extends ListRecords
{
    protected static string $resource = StockedLarderResource::class;
}
