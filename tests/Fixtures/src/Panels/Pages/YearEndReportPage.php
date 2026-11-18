<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Pages;

use Sylament\Panels\Pages\Page;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * An ungrouped page sorted after a resource with no sort of its own.
 */
#[AsLiveComponent]
final class YearEndReportPage extends Page
{
    protected static ?int $navigationSort = 0;
}
