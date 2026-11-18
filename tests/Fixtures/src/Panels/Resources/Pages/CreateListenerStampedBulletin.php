<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Panels\Resources\Pages\CreateRecord;
use Sylament\Tests\Fixtures\Panels\Resources\ListenerStampedBulletinResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent]
final class CreateListenerStampedBulletin extends CreateRecord
{
    protected static string $resource = ListenerStampedBulletinResource::class;
}
