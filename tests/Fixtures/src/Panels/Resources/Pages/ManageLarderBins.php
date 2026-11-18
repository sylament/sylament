<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Actions\CreateAction;
use Sylament\Panels\Resources\Pages\ManageRelatedRecords;
use Sylament\Tests\Fixtures\Panels\Resources\StockedLarderResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A larder's bins with no resource speaking for them and no form or table of the page's own, so
 * everything but its breadcrumb falls back to the association's name; its header creates a bin.
 */
#[AsLiveComponent]
final class ManageLarderBins extends ManageRelatedRecords
{
    protected static string $resource = StockedLarderResource::class;

    protected static string $relationship = 'bins';

    protected static ?string $breadcrumb = 'Every bin';

    public function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
