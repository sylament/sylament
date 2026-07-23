<?php

declare(strict_types=1);

namespace Sylament\Schemas\Contracts;

use Sylament\Schemas\Schema;

interface InteractsWithSchemas
{
    /**
     * @return array<string>
     */
    public function schemas(): array;

    public function getSchema(string $name): Schema;

    public function makeSchema(): Schema;

    /**
     * The pre-change raw state snapshotted for the given absolute state path at
     * the end of the previous render, used by the `afterStateUpdated` diff.
     */
    public function getOldSchemaState(?string $path): mixed;
}
