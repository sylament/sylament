<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Panels\Pages\Enums\SubNavigationPosition;
use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Tests\Fixtures\Panels\Resources\TopBandedArticleResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A second record page of a resource that draws its band on top, drawing its own at the start.
 */
#[AsLiveComponent]
final class EditTopBandedArticleNotes extends EditRecord
{
    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

    protected static string $resource = TopBandedArticleResource::class;

    protected static ?string $navigationLabel = 'Notes';
}
