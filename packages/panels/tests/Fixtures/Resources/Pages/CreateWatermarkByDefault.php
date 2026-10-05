<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Panels\Resources\Pages\CreateRecord;
use Sylament\Panels\Tests\Fixtures\Resources\WatermarkResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * The watermark create page left on the default makeRecord(), which cannot
 * build a record whose constructor takes its key.
 */
#[AsLiveComponent]
final class CreateWatermarkByDefault extends CreateRecord
{
    protected static string $resource = WatermarkResource::class;
}
