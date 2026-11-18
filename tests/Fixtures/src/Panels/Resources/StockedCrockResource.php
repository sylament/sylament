<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Tests\Fixtures\Entity\LarderCrock;

/**
 * A larder's crocks as a resource nested under the stocked larder's, which registers no page for
 * them, registered on no panel.
 */
final class StockedCrockResource extends Resource
{
    protected static ?string $recordClass = LarderCrock::class;

    protected static ?string $parentResource = StockedLarderResource::class;
}
