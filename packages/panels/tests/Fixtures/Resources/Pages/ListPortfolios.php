<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Panels\Resources\Pages\ListRecords;
use Sylament\Panels\Tests\Fixtures\Resources\PortfolioResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent]
final class ListPortfolios extends ListRecords
{
    protected static string $resource = PortfolioResource::class;
}
