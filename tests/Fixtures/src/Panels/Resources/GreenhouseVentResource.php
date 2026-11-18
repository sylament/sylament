<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Tests\Fixtures\Entity\GreenhouseVent;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditGreenhouseVent;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListGreenhouseVents;

/**
 * A greenhouse's vent, keyed by an identity derived from its greenhouse: a list page, and one
 * record page registered as both its view and its edit page.
 */
final class GreenhouseVentResource extends Resource
{
    protected static ?string $recordClass = GreenhouseVent::class;

    public function getPages(): array
    {
        return [
            'index' => ListGreenhouseVents::route('/'),
            'view' => EditGreenhouseVent::route('/{record}'),
            'edit' => EditGreenhouseVent::route('/{record}/edit'),
        ];
    }
}
