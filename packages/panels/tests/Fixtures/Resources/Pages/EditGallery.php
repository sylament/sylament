<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Panels\Tests\Fixtures\Resources\GalleryResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * The page the many-to-many relation manager is embedded on: its owner record
 * is keyed by one scalar, and every record the manager lists by a pair.
 */
#[AsLiveComponent]
final class EditGallery extends EditRecord
{
    protected static string $resource = GalleryResource::class;
}
