<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Actions\DeleteAction;
use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Tests\Fixtures\Panels\Resources\GardenSeedlingResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A garden seedling's edit page, whose header deletes it.
 */
#[AsLiveComponent]
final class EditGardenSeedling extends EditRecord
{
    protected static string $resource = GardenSeedlingResource::class;

    public function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
