<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Tests\Fixtures\Panels\Resources\BandedArticleResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A record page of a resource that registers it under no key, so no route reaches it.
 */
#[AsLiveComponent]
final class UnroutedBandedArticlePage extends EditRecord
{
    protected static string $resource = BandedArticleResource::class;
}
