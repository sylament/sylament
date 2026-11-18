<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Tests\Fixtures\Panels\Resources\ViewableJarResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * Registered as the resource's view page, which `ViewRecord` (not ported) would be, and as its edit page.
 */
#[AsLiveComponent]
final class EditViewableJar extends EditRecord
{
    protected static string $resource = ViewableJarResource::class;
}
