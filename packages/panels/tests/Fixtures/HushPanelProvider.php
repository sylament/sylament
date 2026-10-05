<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures;

use Sylament\Panels\Pages\Dashboard;
use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Panels\Tests\Fixtures\Resources\HushChapterResource;
use Sylament\Panels\Tests\Fixtures\Resources\HushLineResource;
use Sylament\Panels\Tests\Fixtures\Resources\HushVerseResource;

/**
 * The chapter → verse → line nesting again, over resources whose own record
 * query hides every row titled `Hidden …` — so a nested page that dropped
 * that query for the parent scope would serve the row. Not the default panel,
 * for the reason {@see NestedPanelProvider} gives.
 */
final class HushPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('hush')
            ->path('hush')
            ->pages([Dashboard::class])
            ->resources([HushChapterResource::class, HushVerseResource::class, HushLineResource::class]);
    }
}
