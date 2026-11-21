<?php

declare(strict_types=1);

namespace Sylament\Actions;

use LogicException;
use Sylament\Actions\Concerns\CanCustomizeProcess;
use Symfony\Component\Translation\TranslatableMessage;

class DissociateAction extends Action
{
    use CanCustomizeProcess;

    public static function getDefaultName(): ?string
    {
        return 'dissociate';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(new TranslatableMessage('dissociate.single.label', [], 'sylament-actions'));

        $this->modalHeading(fn (): TranslatableMessage => ($title = $this->getRecordTitle()) === null
            ? new TranslatableMessage('dissociate.single.modal.heading_bare', [], 'sylament-actions')
            : new TranslatableMessage('dissociate.single.modal.heading', ['label' => $title], 'sylament-actions'));

        $this->modalSubmitActionLabel(new TranslatableMessage('dissociate.single.modal.actions.dissociate.label', [], 'sylament-actions'));

        $this->successNotificationTitle(new TranslatableMessage('dissociate.single.notifications.dissociated.title', [], 'sylament-actions'));

        $this->defaultColor('danger');

        $this->icon('heroicons:x-mark-20-solid');

        $this->requiresConfirmation();

        $this->modalIcon('heroicons:x-mark');

        $this->action(function (): void {
            $this->process($this->dissociateRecord(...));

            $this->success();
        });
    }

    /**
     * The default step `using()` replaces: undo the record's membership of the
     * table's relationship and flush. The record arrives typed — the resolver
     * injects `getRecord()` by name, the `DeleteAction` shape. The reader's three
     * mapping refusals propagate as developer errors rather than a toast.
     */
    private function dissociateRecord(object $record): void
    {
        $relationship = $this->getTable()?->getRelationship()
            ?? throw new LogicException(sprintf('The action [%s] dissociates a record through a relationship, which only a table declaring relationship() can supply.', $this->getName()));

        $this->getAssociationReader()->detach($relationship, $record);
        $this->getEntityManagerForRecord($record)->flush();
    }
}
