<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Panels\Resources\Pages\CreateRecord;
use Sylament\Tests\Fixtures\Panels\Resources\PottedPlantResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A create page stripped to its form under a title and a crumb of its own: it fills the form itself with
 * {@see self::FILLED_NAME}, declares no button row and announces nothing once it has created.
 */
#[AsLiveComponent]
final class CreateBarePottedPlant extends CreateRecord
{
    public const string FILLED_NAME = 'Cutting';

    protected static ?string $title = 'Pot a cutting';

    protected static ?string $breadcrumb = 'Pot';

    protected static string $resource = PottedPlantResource::class;

    protected function fillForm(): void
    {
        $this->getSchema('form')->fill(['name' => self::FILLED_NAME]);
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }
}
