<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use stdClass;
use Sylament\Panels\Resources\Resource;

/**
 * A resource over a class no manager maps, registered on no panel.
 */
final class UnmappedRecordResource extends Resource
{
    protected static ?string $recordClass = stdClass::class;
}
