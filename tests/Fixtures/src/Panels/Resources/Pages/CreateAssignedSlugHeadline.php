<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Panels\Resources\Pages\CreateRecord;
use Sylament\Tests\Fixtures\Entity\CappedHeadline;
use Sylament\Tests\Fixtures\Panels\Resources\AssignedSlugHeadlineResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * Builds every headline with the slug {@see self::SLUG}.
 */
#[AsLiveComponent]
final class CreateAssignedSlugHeadline extends CreateRecord
{
    public const string SLUG = 'front-page';

    protected static string $resource = AssignedSlugHeadlineResource::class;

    protected function makeRecord(array $data): CappedHeadline
    {
        return new CappedHeadline(slug: self::SLUG);
    }
}
