<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Panels\Tests\Fixtures\Resources\AccessionResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent]
final class EditAccession extends EditRecord
{
    protected static string $resource = AccessionResource::class;
}
