<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Plate;

/**
 * The child of a unidirectional to-many: the mapping names no field on either
 * side of it, so the route parameter falls back to the parent record's own
 * name.
 */
final class PlateResource extends Resource
{
    protected static ?string $recordClass = Plate::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $parentResource = AnthologyResource::class;
}
