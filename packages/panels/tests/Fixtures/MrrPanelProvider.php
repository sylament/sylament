<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures;

use Sylament\Panels\Pages\Dashboard;
use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Panels\Tests\Fixtures\Resources\MrrProjectResource;
use Sylament\Panels\Tests\Fixtures\Resources\MrrTaskResource;

/**
 * The manage-related-records panel: a project resource whose tasks are managed
 * on a page of their own, and the task resource nested under it. Deliberately
 * not the default one — a second `default()` would silently reorder the panel
 * every other suite resolves without naming one.
 */
final class MrrPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('mrr')
            ->path('mrr')
            ->pages([Dashboard::class])
            ->resources([MrrProjectResource::class, MrrTaskResource::class]);
    }
}
