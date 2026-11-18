<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Tests\Fixtures\Entity\LarderBinNote;

/**
 * A bin's notes whose bin travels under the parameter its larder already travels under,
 * registered on no kernel panel.
 */
final class RepeatedParameterNoteResource extends Resource
{
    protected static ?string $recordClass = LarderBinNote::class;

    protected static ?string $parentResource = LarderBinResource::class;

    public function getParentRouteParameterName(): string
    {
        return 'larder';
    }
}
