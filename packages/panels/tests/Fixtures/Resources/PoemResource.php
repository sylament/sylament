<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Poem;

/**
 * The child hanging off the inverse side of a many-to-many: its route parameter
 * is the field completing the association, and that field holds a collection,
 * so its parent can never be guessed off a record.
 */
final class PoemResource extends Resource
{
    protected static ?string $recordClass = Poem::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $parentResource = AnthologyResource::class;
}
