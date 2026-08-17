<?php

declare(strict_types=1);

namespace Sylament\Actions;

use Doctrine\Persistence\ManagerRegistry;
use LogicException;
use Sylament\Actions\Concerns\CanCustomizeProcess;
use Symfony\Contracts\Service\Attribute\Required;

class DeleteAction extends Action
{
    use CanCustomizeProcess;

    protected ?ManagerRegistry $managerRegistry = null;

    public static function getDefaultName(): ?string
    {
        return 'delete';
    }

    #[Required]
    public function setManagerRegistry(?ManagerRegistry $managerRegistry = null): void
    {
        $this->managerRegistry = $managerRegistry;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Delete');

        $this->modalHeading('Delete');

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
