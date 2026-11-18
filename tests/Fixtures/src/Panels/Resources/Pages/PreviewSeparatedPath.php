<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Panels\Resources\Pages\Page;
use Sylament\Tests\Fixtures\Panels\Resources\SeparatedPathResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * The page every separated path mounts; its placeholders reach it as public properties.
 */
#[AsLiveComponent]
final class PreviewSeparatedPath extends Page
{
    public ?string $record = null;

    public ?string $locale = null;

    protected static string $resource = SeparatedPathResource::class;
}
