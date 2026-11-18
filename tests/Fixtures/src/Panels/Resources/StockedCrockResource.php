<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Tests\Fixtures\Entity\LarderCrock;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditStockedCrock;

/**
 * A larder's crocks, keyed by their larder plus a shelf and titled by label, as a resource nested
 * under the stocked larder's, which registers no page for them, so their listing falls back to the
 * larders' own: an edit page.
 */
final class StockedCrockResource extends Resource
{
    protected static ?string $recordClass = LarderCrock::class;

    protected static ?string $parentResource = StockedLarderResource::class;

    protected static ?string $recordTitleAttribute = 'label';

    public function getPages(): array
    {
        return [
            'edit' => EditStockedCrock::route('/{record}/edit'),
        ];
    }
}
