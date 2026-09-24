<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Actions\CreateAction;
use Sylament\Panels\Resources\Pages\ListRecords;
use Sylament\Panels\Tests\Fixtures\Resources\EditionResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent]
final class ListEditions extends ListRecords
{
    protected static string $resource = EditionResource::class;

    /**
     * Never a header action, and the resource registers no create page, so
     * mounted by name it is the modal arm over the resource form.
     */
    public function createAction(): CreateAction
    {
        return CreateAction::make();
    }
}
