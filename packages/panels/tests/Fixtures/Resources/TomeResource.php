<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Tome;

/**
 * The ambiguity rig's parent: on no panel and with no pages, since only the
 * derivation from its two {@see Tome} collections is exercised.
 */
final class TomeResource extends Resource
{
    protected static ?string $recordClass = Tome::class;

    protected static ?string $recordTitleAttribute = 'title';
}
