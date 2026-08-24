<?php

declare(strict_types=1);

namespace Sylament\Support\Exceptions;

use Exception;

class Halt extends Exception
{
    protected bool $shouldRollBackDatabaseTransaction = false;

    public function rollBackDatabaseTransaction(bool $condition = true): static
    {
        $this->shouldRollBackDatabaseTransaction = $condition;

        return $this;
    }

    public function shouldRollbackDatabaseTransaction(): bool
    {
        return $this->shouldRollBackDatabaseTransaction;
    }
}
