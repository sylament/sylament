<?php

declare(strict_types=1);

namespace Sylament\Actions;

use Symfony\Component\Translation\TranslatableMessage;

class BulkActionGroup extends ActionGroup
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(new TranslatableMessage('table.actions.open_bulk_actions.label', [], 'sylament-actions'));
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
