<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Concerns;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Concerns\BelongsToGroup;
use Sylament\Actions\Concerns\BelongsToTable;
use Sylament\Actions\Concerns\CanUseDatabaseTransactions;
use Sylament\Actions\Concerns\HasName;
use Sylament\Support\Concerns\EvaluatesClosures;

/**
 * Exposes the trait's protected manager resolver, which the shipped write
 * bodies reach through `$this` and no public surface reveals.
 */
class CanUseDatabaseTransactionsFixture
{
    use BelongsToGroup;
    use BelongsToTable;
    use CanUseDatabaseTransactions;
    use EvaluatesClosures;
    use HasName;

    public function __construct()
    {
        $this->name('persist');
    }

    /**
     * @param object|class-string $recordOrClass
     */
    public function exposeEntityManagerForRecord(object|string $recordOrClass): EntityManagerInterface
    {
        return $this->getEntityManagerForRecord($recordOrClass);
    }
}
