<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Tests\Fixtures\Entity\Dock;

/**
 * A dock, with no page of its own.
 */
final class DockResource extends Resource
{
    protected static ?string $recordClass = Dock::class;
}
