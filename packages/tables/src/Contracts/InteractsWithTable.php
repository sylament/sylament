<?php

declare(strict_types=1);

namespace Sylament\Tables\Contracts;

use Sylament\Tables\Table;

interface InteractsWithTable
{
    public function getTable(): Table;

    /**
     * @return array<array-key, object|array<string, mixed>> keyed by record key
     */
    public function getTableRecords(): array;
}
