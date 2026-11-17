<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use RuntimeException;
use Sylament\Actions\DeleteBulkAction;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * A by-key bulk delete whose override records the keys it is handed, sorted, and refuses to
 * delete any of them.
 */
class RefusingDeleteBulkAction extends DeleteBulkAction
{
    private HookLog $log;

    #[Required]
    public function setHookLog(HookLog $log): void
    {
        $this->log = $log;
    }

    protected function deleteSelectedRecordsByKey(array $keys): int
    {
        sort($keys);

        $this->log->record('refused ' . implode(', ', $keys));

        throw new RuntimeException('These records are protected.');
    }
}
