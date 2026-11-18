<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Tests\Fixtures\Entity\Voyage;

/**
 * Voyages nested under the dock's resource without naming which of the dock's two voyage
 * associations they hang off, registered on no panel.
 */
final class AmbiguousVoyageResource extends Resource
{
    protected static ?string $recordClass = Voyage::class;

    protected static ?string $parentResource = DockResource::class;
}
