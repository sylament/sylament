<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Tests\Fixtures\Panels\Record\Placard;

/**
 * Titles its records by a caption that may hold any value.
 */
final class PlacardResource extends Resource
{
    protected static ?string $recordClass = Placard::class;

    protected static ?string $recordTitleAttribute = 'caption';
}
