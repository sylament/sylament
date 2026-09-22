<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Actions\DeleteAction;
use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Panels\Tests\Fixtures\Resources\EditionVariantResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent]
final class EditEditionVariant extends EditRecord
{
    protected static string $resource = EditionVariantResource::class;

    public function deleteAction(): DeleteAction
    {
        return DeleteAction::make();
    }

    public function getHeaderActions(): array
    {
        return [$this->deleteAction()];
    }
}
