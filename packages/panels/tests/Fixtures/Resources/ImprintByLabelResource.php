<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Imprint;

/**
 * The custom route-key arm over a composite record: a named field keys and
 * resolves the record, so the codec never runs. On no panel and with no pages,
 * since only the seam's two methods are exercised — and not searchable, since
 * {@see ImprintResource} is the searchable resource over these records.
 */
final class ImprintByLabelResource extends Resource
{
    protected static ?string $recordClass = Imprint::class;

    protected static ?string $recordTitleAttribute = 'label';

    protected static ?string $pluralRecordLabel = 'imprints by label';

    protected static ?string $recordRouteKeyName = 'label';

    protected static ?string $slug = 'imprints-by-label';

    protected static bool $isGloballySearchable = false;
}
