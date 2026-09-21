<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Panels\Resources\Pages\CreateRecord;
use Sylament\Panels\Tests\Fixtures\Resources\GalleryImprintResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent]
final class CreateGalleryImprint extends CreateRecord
{
    protected static string $resource = GalleryImprintResource::class;
}
