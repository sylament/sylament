<?php

declare(strict_types=1);

namespace Sylament\Actions;

use Sylament\Actions\Concerns\CanMutateRecordData;
use Sylament\Schemas\Schema;

class ViewAction extends Action
{
    use CanMutateRecordData;

    public static function getDefaultName(): ?string
    {
        return 'view';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('View');

        $this->modalHeading(fn (): string => ($title = $this->getRecordTitle()) === null ? 'View' : "View {$title}");

        $this->modalSubmitAction(false);
        $this->modalCancelAction(fn (Action $action): Action => $action->label('Close'));

        $this->defaultColor('gray');

        $this->icon('heroicons:eye-20-solid');
        $this->groupedIcon('heroicons:eye-20-solid');

        $this->disabledSchema();

        $this->fillForm(fn (Schema $schema): array => $this->getRecordDataForFill($schema, $this->getRecordForFill()));
    }
}
