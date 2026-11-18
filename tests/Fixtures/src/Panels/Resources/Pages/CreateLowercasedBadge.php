<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Panels\Resources\Pages\CreateRecord;
use Sylament\Tests\Fixtures\Entity\CodeKeyedSlate;
use Sylament\Tests\Fixtures\Entity\ConstructorCodedBadge;
use Sylament\Tests\Fixtures\Panels\Resources\LowercasedBadgeResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * Builds the badge from its typed code, lower-cased, which its constructor alone can set; for {@see self::STRAY_CODE} it
 * answers a slate instead, no record of its resource's class.
 */
#[AsLiveComponent]
final class CreateLowercasedBadge extends CreateRecord
{
    public const string STRAY_CODE = 'stray';

    protected static string $resource = LowercasedBadgeResource::class;

    protected function makeRecord(array $data): object
    {
        $code = is_string($data['code'] ?? null) ? $data['code'] : '';

        if ($code === self::STRAY_CODE) {
            return new CodeKeyedSlate($code);
        }

        return new ConstructorCodedBadge(mb_strtolower($code));
    }
}
