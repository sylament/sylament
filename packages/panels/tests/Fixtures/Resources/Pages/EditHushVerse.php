<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Actions\EditAction;
use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Panels\Tests\Fixtures\Resources\HushVerseResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent]
final class EditHushVerse extends EditRecord
{
    protected static string $resource = HushVerseResource::class;

    /**
     * Record-bound, so a mounted frame naming another verse's key reaches the
     * page's own parent-scoped resolution.
     */
    public function editAction(): EditAction
    {
        return EditAction::make()->record($this->getRecord());
    }
}
