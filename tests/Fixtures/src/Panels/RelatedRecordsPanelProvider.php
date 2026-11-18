<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels;

use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Tests\Fixtures\Panels\Resources\FlatJarResource;
use Sylament\Tests\Fixtures\Panels\Resources\PreserveJarResource;
use Sylament\Tests\Fixtures\Panels\Resources\StockedLarderResource;

/**
 * The pages that manage one larder's records: a larder whose jars, as a resource nested under it,
 * as a resource nesting under nothing and as no resource at all, and its bins each have a page of
 * their own.
 */
final class RelatedRecordsPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('related')
            ->path('related')
            ->resources([StockedLarderResource::class, PreserveJarResource::class, FlatJarResource::class]);
    }
}
