<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\RelationManagers;

use Sylament\Panels\Resources\RelationManagers\RelationManager;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A garden's rows behind an access answer no record passes.
 */
#[AsLiveComponent]
final class HiddenRowsRelationManager extends RelationManager
{
    protected static string $relationship = 'rows';

    public static function canViewForRecord(object $ownerRecord, string $pageClass): bool
    {
        return false;
    }
}
