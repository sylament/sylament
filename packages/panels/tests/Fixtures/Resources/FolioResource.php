<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Folio;

/**
 * The child hanging off the owning side of a many-to-many: nothing names the
 * field completing the association from the parent's side, so the route
 * parameter comes off the mapping's inverse field instead.
 */
final class FolioResource extends Resource
{
    protected static ?string $recordClass = Folio::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $parentResource = AnthologyResource::class;
}
