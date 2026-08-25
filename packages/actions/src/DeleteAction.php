<?php

declare(strict_types=1);

namespace Sylament\Actions;

use LogicException;
use Sylament\Actions\Concerns\CanCustomizeProcess;

class DeleteAction extends Action
{
    use CanCustomizeProcess;

    public static function getDefaultName(): ?string
    {
        return 'delete';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Delete');

        $this->modalHeading(fn (): string => ($title = $this->getRecordTitle()) === null ? 'Delete' : "Delete {$title}");

        $this->successNotificationTitle('Deleted');

        $this->defaultColor('danger');

        $this->icon('heroicons:trash-20-solid');

        $this->requiresConfirmation();

        $this->action(function (): void {
            $result = $this->process(function (object $record): bool {
                $registry = $this->managerRegistry
                    ?? throw new LogicException('DeleteAction needs the container-built instance — register it as a service so the manager registry is injected.');

                $manager = $registry->getManagerForClass($record::class)
                    ?? throw new LogicException(sprintf('No Doctrine manager handles %s.', $record::class));

                $manager->remove($record);
                $manager->flush();

                return true;
            });

            if (!$result) {
                $this->failure();

                return;
            }

            $this->success();
        });
    }
}
