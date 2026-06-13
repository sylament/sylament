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
}
