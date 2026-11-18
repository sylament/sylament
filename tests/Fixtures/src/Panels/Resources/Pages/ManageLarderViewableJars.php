<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Panels\Resources\Pages\ManageRelatedRecords;
use Sylament\Tests\Fixtures\Panels\Resources\StockedLarderResource;
use Sylament\Tests\Fixtures\Panels\Resources\ViewableJarResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A larder's jars as a resource with a view page and an edit page lists them.
 */
#[AsLiveComponent]
final class ManageLarderViewableJars extends ManageRelatedRecords
{
    protected static string $resource = StockedLarderResource::class;

    protected static string $relationship = 'jars';

    protected static ?string $relatedResource = ViewableJarResource::class;
}
