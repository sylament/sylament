<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Panels\Resources\Pages\CreateRecord;
use Sylament\Tests\Fixtures\Entity\ClaspedLocket;
use Sylament\Tests\Fixtures\Panels\Resources\UnkeyedLocketResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * Keys the locket's clasp with {@see self::CLASP_CODE} before the default write when the locket is labelled
 * {@see self::KEYED_LABEL}, and leaves it blank otherwise.
 */
#[AsLiveComponent]
final class CreateUnkeyedLocket extends CreateRecord
{
    public const string KEYED_LABEL = 'Keyed';

    public const string CLASP_CODE = 'gold';

    protected static string $resource = UnkeyedLocketResource::class;

    protected function handleRecordCreation(object $record): object
    {
        if ($record instanceof ClaspedLocket && $record->label === self::KEYED_LABEL) {
            $record->clasp->code = self::CLASP_CODE;
        }

        return parent::handleRecordCreation($record);
    }
}
