<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;
use LogicException;
use Sylament\Schemas\Schema;

use function Sylament\Support\except;

trait CanMutateRecordData
{
    protected ?Closure $mutateRecordDataUsing = null;

    public function mutateRecordDataUsing(?Closure $callback): static
    {
        $this->mutateRecordDataUsing = $callback;

        return $this;
    }

    /**
     * The record as the form's array — the schema's declared paths, minus
     * `$except` — run through `mutateRecordDataUsing()`; an array record is
     * handed through as-is.
     *
     * @param object|array<string, mixed> $record
     * @param array<string> $except
     *
     * @return array<string, mixed>
     */
    protected function getRecordDataForFill(Schema $schema, object|array $record, array $except = []): array
    {
        return $this->mutateRecordData(except(is_array($record) ? $record : $schema->getStateFromRecord($record), $except));
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function mutateRecordData(array $data): array
    {
        if ($this->mutateRecordDataUsing === null) {
            return $data;
        }

        $data = $this->evaluate($this->mutateRecordDataUsing, ['data' => $data]);

        self::assertFieldKeyedData($data, 'mutateRecordDataUsing');

        return $data;
    }

    /**
     * @return object|array<string, mixed>
     */
    protected function getRecordForFill(): object|array
    {
        $record = $this->getRecord();

        if (is_object($record)) {
            return $record;
        }

        if (is_array($record)) {
            return array_filter($record, is_string(...), ARRAY_FILTER_USE_KEY);
        }

        throw new LogicException(sprintf('The action [%s] fills its form from a record but none is bound — mount it from a table row, a record page, or call record().', $this->getName()));
    }

    protected function getRecordOrFail(): object
    {
        $record = $this->getRecord();

        if (! is_object($record)) {
            throw new LogicException(sprintf('The action [%s] writes a record but no entity is bound — mount it from a query() table row or a record page, call record(), or take the write over with using().', $this->getName()));
        }

        return $record;
    }
}
