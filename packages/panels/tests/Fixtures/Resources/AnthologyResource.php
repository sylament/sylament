<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Anthology;

/**
 * The many-to-many rig's parent: on no panel and with no pages, since only what
 * its three collections derive is exercised.
 */
final class AnthologyResource extends Resource
{
    protected static ?string $recordClass = Anthology::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $slug = 'anthologies';
}
