<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Pages;

use Sylament\Panels\Pages\Dashboard;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * The shipped dashboard under a title of the application's own.
 */
#[AsLiveComponent]
final class OverviewDashboard extends Dashboard
{
    protected static ?string $title = 'Overview';
}
