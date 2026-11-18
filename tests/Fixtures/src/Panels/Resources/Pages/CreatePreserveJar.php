<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Panels\Resources\Pages\CreateRecord;
use Sylament\Tests\Fixtures\Panels\Resources\PreserveJarResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent]
final class CreatePreserveJar extends CreateRecord
{
    protected static string $resource = PreserveJarResource::class;
}
