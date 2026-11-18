<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Tests\Fixtures\Entity\Seedling;

/**
 * A searched seedling matched on its own name or its garden's, with case folding forced on, linking to a URL of its own.
 */
final class SeedlingGardenSearchResource extends Resource
{
    protected static ?string $recordClass = Seedling::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?bool $isGlobalSearchForcedCaseInsensitive = true;

    public function getGloballySearchableAttributes(): array
    {
        return ['name', 'garden.name'];
    }

    public function getGlobalSearchResultUrl(object $record): string
    {
        /** @var Seedling $record */
        return "/seedlings/{$record->id}";
    }
}
