<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Panels\Resources\Pages\CreateRecord;
use Sylament\Panels\Tests\Fixtures\Resources\WatermarkDraft;
use Sylament\Panels\Tests\Fixtures\Resources\WatermarkResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A create page whose makeRecord() answers an object that is no watermark,
 * kept where a test can read whether anything was written onto it.
 */
#[AsLiveComponent]
final class CreateWatermarkAsDraft extends CreateRecord
{
    public static ?WatermarkDraft $draft = null;

    protected static string $resource = WatermarkResource::class;

    /**
     * @param array<string, mixed> $data
     */
    protected function makeRecord(array $data): WatermarkDraft
    {
        return self::$draft = new WatermarkDraft();
    }
}
