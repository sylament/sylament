<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Panels\Tests\Fixtures\Resources\CatalogueResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * The page a relation manager over composite records is mounted from, which is
 * also how its related-resource URLs resolve the panel they belong to.
 */
#[AsLiveComponent]
final class EditCatalogue extends EditRecord
{
    protected static string $resource = CatalogueResource::class;
}
