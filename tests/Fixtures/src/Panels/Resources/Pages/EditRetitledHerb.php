<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Tests\Fixtures\Panels\Resources\VariantHerbResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * An edit page with a title and a crumb of its own, a blank saved notification title, and a save that leaves for the
 * listing.
 */
#[AsLiveComponent]
final class EditRetitledHerb extends EditRecord
{
    protected static ?string $title = 'Tend the herb';

    protected static ?string $breadcrumb = 'Tend';

    protected static string $resource = VariantHerbResource::class;

    protected function getSavedNotificationTitle(): ?string
    {
        return null;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResourceInstance()->getIndexUrl();
    }
}
