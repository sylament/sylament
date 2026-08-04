<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Components\Concerns;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Schema;

final class KeyedChildSchemasFixture extends Component
{
    /**
     * @var array<int, string>
     */
    public array $itemKeys = [];

    public bool $isCacheFresh = true;

    public int $defaultChildSchemaBuilds = 0;

    /**
     * @return array<array-key, Schema>
     */
    public function getDefaultChildSchemas(): array
    {
        $this->defaultChildSchemaBuilds++;

        $schemas = [];

        foreach ($this->itemKeys as $itemKey) {
            $schemas[$itemKey] = $this->makeChildSchema($itemKey);
        }

        return $schemas;
    }

    protected function areCachedDefaultChildSchemasFresh(): bool
    {
        return $this->isCacheFresh;
    }
}
