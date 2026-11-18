<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Pages;

use BackedEnum;
use Sylament\Panels\Pages\Page;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A page routed at a slug with a `/` in it, titled but with no heading of its own, whose
 * navigation entry carries its own label, sort and an icon with no active variant.
 */
#[AsLiveComponent]
final class MonthlyReportPage extends Page
{
    protected static ?string $title = 'Monthly report';

    protected static ?string $slug = 'reports/monthly';

    protected static ?string $navigationLabel = 'Reports';

    protected static string|BackedEnum|null $navigationIcon = 'heroicons:home';

    protected static ?int $navigationSort = 1;
}
