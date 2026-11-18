<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Tests\Fixtures\Entity\Seedling;

/**
 * Seedlings nested under the dock's resource, although a dock holds none, registered on no
 * panel.
 */
final class DockedSeedlingResource extends Resource
{
    protected static ?string $recordClass = Seedling::class;

    protected static ?string $parentResource = DockResource::class;
}
