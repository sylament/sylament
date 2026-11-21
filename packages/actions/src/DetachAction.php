<?php

declare(strict_types=1);

namespace Sylament\Actions;

use LogicException;
use Sylament\Actions\Concerns\CanCustomizeProcess;
use Symfony\Component\Translation\TranslatableMessage;

class DetachAction extends Action
{
    use CanCustomizeProcess;

    public static function getDefaultName(): ?string
    {
        return 'detach';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(new TranslatableMessage('detach.single.label', [], 'sylament-actions'));

        $this->modalHeading(fn (): TranslatableMessage => ($title = $this->getRecordTitle()) === null
            ? new TranslatableMessage('detach.single.modal.heading_bare', [], 'sylament-actions')
            : new TranslatableMessage('detach.single.modal.heading', ['label' => $title], 'sylament-actions'));

        $this->modalSubmitActionLabel(new TranslatableMessage('detach.single.modal.actions.detach.label', [], 'sylament-actions'));

        $this->successNotificationTitle(new TranslatableMessage('detach.single.notifications.detached.title', [], 'sylament-actions'));

        $this->defaultColor('danger');

        $this->icon('heroicons:x-mark-20-solid');

        $this->requiresConfirmation();

        $this->modalIcon('heroicons:x-mark');

        $this->action(function (): void {
            $this->process($this->detachRecord(...));

            $this->success();
        });
    }

    /**
     * The default step `using()` replaces: undo the record's membership of the
     * table's relationship and flush. The record arrives typed — the resolver
     * injects `getRecord()` by name, the `DeleteAction` shape.
     */
    private function detachRecord(object $record): void
    {
        $relationship = $this->getTable()?->getRelationship()
            ?? throw new LogicException(sprintf('The action [%s] detaches a record from a relationship, which only a table declaring relationship() can supply.', $this->getName()));

        $this->getAssociationReader()->detach($relationship, $record);
        $this->getEntityManagerForRecord($record)->flush();
    }
}
