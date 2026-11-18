<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels;

use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Tests\Fixtures\Panels\Resources\BandedOrderLineResource;
use Sylament\Tests\Fixtures\Panels\Resources\SheetedBinderResource;
use Sylament\Tests\Fixtures\Panels\Resources\VariantHerbResource;

/**
 * The edit page's shapes: resources whose edit pages hook, retitle, act, band and save relationships differently.
 */
final class EditShapesPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('edit-shapes')
            ->path('edit-shapes')
            ->resources([
                VariantHerbResource::class,
                BandedOrderLineResource::class,
                SheetedBinderResource::class,
            ]);
    }
}
