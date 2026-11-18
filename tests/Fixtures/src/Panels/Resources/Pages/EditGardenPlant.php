<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Actions\CreateAction;
use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Tests\Fixtures\Panels\Resources\GardenPlantResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A garden plant's edit page, whose header creates another plant, in a modal, since the resource
 * registers no create page.
 */
#[AsLiveComponent]
final class EditGardenPlant extends EditRecord
{
    protected static string $resource = GardenPlantResource::class;

    public function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
