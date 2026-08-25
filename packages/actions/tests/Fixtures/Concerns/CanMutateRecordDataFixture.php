<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Concerns;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\CanMutateRecordData;
use Sylament\Schemas\Schema;

/**
 * The trait is only ever composed onto an `Action` subclass — `getRecord()`,
 * `getName()` and `evaluate()` are the base class's — so the fixture is one,
 * with the four protected members exposed for the Unit cases.
 */
class CanMutateRecordDataFixture extends Action
{
    use CanMutateRecordData;

    /**
     * @param object|array<string, mixed> $record
     * @param array<string> $except
     *
     * @return array<string, mixed>
     */
    public function readRecordDataForFill(Schema $schema, object|array $record, array $except = []): array
    {
        return $this->getRecordDataForFill($schema, $record, $except);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function runMutateRecordData(array $data): array
    {
        return $this->mutateRecordData($data);
    }

    /**
     * @return object|array<string, mixed>
     */
    public function readRecordForFill(): object|array
    {
        return $this->getRecordForFill();
    }

    public function readRecordOrFail(): object
    {
        return $this->getRecordOrFail();
    }
}
