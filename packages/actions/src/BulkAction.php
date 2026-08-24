<?php

declare(strict_types=1);

namespace Sylament\Actions;

class BulkAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->bulk();
        $this->accessSelectedRecords();
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtraAttributes(): array
    {
        return [
            'data-sy-table-bulk-only' => true,
            ...parent::getExtraAttributes(),
        ];
    }
}
