<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Actions\Action;
use Sylament\Panels\Resources\Pages\CreateRecord;
use Sylament\Tests\Fixtures\Panels\Resources\PottedPlantResource;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;

/**
 * A create page whose button row spans the page, whose submit button reads {@see self::CREATE_LABEL}, and whose form
 * submits to a `plant` live action of its own that creates as `create` does.
 */
#[AsLiveComponent]
final class CreateCustomRowPottedPlant extends CreateRecord
{
    public const string CREATE_LABEL = 'Plant it';

    protected static string $resource = PottedPlantResource::class;

    #[LiveAction]
    public function plant(): ?RedirectResponse
    {
        return $this->create();
    }

    public function getSubmitFormLiveComponentMethodName(): string
    {
        return 'plant';
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label(self::CREATE_LABEL);
    }
}
