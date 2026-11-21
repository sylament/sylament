<?php

declare(strict_types=1);

namespace Sylament\Actions;

use Sylament\Actions\Concerns\CanMutateRecordData;
use Sylament\Schemas\Schema;
use Symfony\Component\Translation\TranslatableMessage;

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

        $this->label(new TranslatableMessage('view.single.label', [], 'sylament-actions'));

        $this->modalHeading(fn (): TranslatableMessage => ($title = $this->getRecordTitle()) === null
            ? new TranslatableMessage('view.single.modal.heading_bare', [], 'sylament-actions')
            : new TranslatableMessage('view.single.modal.heading', ['label' => $title], 'sylament-actions'));

        $this->modalSubmitAction(false);
        $this->modalCancelAction(fn (Action $action): Action => $action->label(new TranslatableMessage('view.single.modal.actions.close.label', [], 'sylament-actions')));

        $this->defaultColor('gray');

        $this->icon('heroicons:eye-20-solid');
        $this->groupedIcon('heroicons:eye-20-solid');

        $this->disabledSchema();

        $this->fillForm(fn (Schema $schema): array => $this->getRecordDataForFill($schema, $this->getRecordForFill()));
    }
}
