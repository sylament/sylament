<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures;

use Sylament\Panels\Pages\Dashboard;
use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Panels\Tests\Fixtures\Resources\ChapterResource;
use Sylament\Panels\Tests\Fixtures\Resources\LineResource;
use Sylament\Panels\Tests\Fixtures\Resources\VerseResource;

/**
 * The nested-resource panel: a parent resource and the two levels beneath it,
 * deliberately not the default one, so the panel the rest of the suite resolves
 * without naming a panel stays the same.
 */
final class NestedPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('nest')
            ->path('nest')
            ->pages([Dashboard::class])
            ->resources([ChapterResource::class, VerseResource::class, LineResource::class]);
    }
}
