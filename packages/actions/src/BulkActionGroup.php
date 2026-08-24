<?php

declare(strict_types=1);

namespace Sylament\Actions;

class BulkActionGroup extends ActionGroup
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Bulk actions');
        $this->icon(static::DEFAULT_ICON);
        $this->defaultColor('gray');
        $this->defaultTriggerTemplate(static::BUTTON_TEMPLATE);
        $this->dropdownPlacement('bottom-start');
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtraDropdownAttributes(): array
    {
        return [
            'data-sy-table-bulk-only' => true,
            ...parent::getExtraDropdownAttributes(),
        ];
    }
}
