<?php

declare(strict_types=1);

namespace Sylament\Tables\Concerns;

use Closure;
use Sylament\Tables\Columns\Column;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;

trait HasColumns
{
    /**
     * Runs the closure a column's `action()` holds, for the record the client
     * names. Every client value is untrusted: the key resolves through the
     * query lane with the table's filters re-applied, so a filtered-out record
     * is simply absent, and the column resolves by name off the table. A
     * missing record, an unknown column or an action that is not a closure
     * returns without effect.
     */
    #[LiveAction]
    public function callTableColumnAction(#[LiveArg] string $name, #[LiveArg] string $recordKey): void
    {
        $record = $this->getTableRecord($recordKey);

        if ($record === null) {
            return;
        }

        $column = $this->getTable()->getColumn($name);

        if (!$column instanceof Column) {
            return;
        }

        $action = $column->getAction();

        if (!$action instanceof Closure) {
            return;
        }

        $column->record($record)->recordKey($recordKey)->evaluate($action);
    }
}
