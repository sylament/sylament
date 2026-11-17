<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use Sylament\Actions\DeleteBulkAction;

/**
 * A by-key bulk delete whose override deletes every key it is handed but the first, and
 * answers the rows it deleted.
 */
class ShortCountingDeleteBulkAction extends DeleteBulkAction
{
    protected function deleteSelectedRecordsByKey(array $keys): int
    {
        return parent::deleteSelectedRecordsByKey(array_slice($keys, 1));
    }
}
