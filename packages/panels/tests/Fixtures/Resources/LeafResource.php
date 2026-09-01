<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Leaf;

/**
 * The ambiguity rig's child: it names a parent and no relationship, so the
 * derivation meets two candidate associations and refuses.
 */
final class LeafResource extends Resource
{
    protected static ?string $recordClass = Leaf::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $parentResource = TomeResource::class;
}
