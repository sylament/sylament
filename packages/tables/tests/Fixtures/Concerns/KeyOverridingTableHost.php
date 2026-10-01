<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Concerns;

use Closure;
use LogicException;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;

/**
 * A host overriding getTableRecordKey(): the closure is handed the row and the
 * key the trait would answer for it, and answers the row's key.
 */
final class KeyOverridingTableHost implements InteractsWithTable
{
    use HasTable {
        getTableRecordKey as private getTraitTableRecordKey;
    }

    public function __construct(
        private readonly ?Closure $configureTable = null,
        private readonly ?Closure $recordKey = null,
    ) {
    }

    public function table(Table $table): Table
    {
        if (!$this->configureTable instanceof Closure) {
            return $table;
        }

        $table = ($this->configureTable)($table);

        if (!$table instanceof Table) {
            throw new LogicException('The configureTable fixture closure must return a Table.');
        }

        return $table;
    }

    /**
     * @param object|array<string, mixed> $record
     */
    public function getTableRecordKey(object|array $record): string
    {
        $key = $this->getTraitTableRecordKey($record);

        if (!$this->recordKey instanceof Closure) {
            return $key;
        }

        $key = ($this->recordKey)($record, $key);

        if (!is_string($key)) {
            throw new LogicException('The recordKey fixture closure must return a string.');
        }

        return $key;
    }
}
