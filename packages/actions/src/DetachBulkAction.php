<?php

declare(strict_types=1);

namespace Sylament\Actions;

use LogicException;
use Sylament\Actions\Concerns\CanCustomizeProcess;
use Throwable;

class DetachBulkAction extends BulkAction
{
    use CanCustomizeProcess;

    public static function getDefaultName(): ?string
    {
        return 'detach';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Detach selected');

        $this->modalHeading(fn (): string => ($label = $this->getTitleCasePluralRecordLabel()) === null ? 'Detach selected' : "Detach selected {$label}");

        $this->modalSubmitActionLabel('Detach');

        $this->successNotificationTitle('Detached');

        $this->failureNotificationTitle(
            fn (int $successCount, int $totalCount): string => $successCount > 0
                ? sprintf('Detached %d of %d', $successCount, $totalCount)
                : sprintf('Failed to detach %d records', $totalCount),
        );

        $this->defaultColor('danger');

        $this->icon('heroicons:x-mark-20-solid');

        $this->requiresConfirmation();

        $this->modalIcon('heroicons:x-mark');

        $this->action(function (): void {
            $this->process($this->detachRecords(...));
        });

        $this->deselectRecordsAfterCompletion();
    }

    /**
     * Both arms end in one flush, so the batch detaches whole or not at all: the
     * hydrated records, or — under `fetchSelectedRecords(false)` — references
     * built from the selected keys, which never SELECT the rows themselves.
     *
     * @param array<mixed> $records
     */
    private function detachRecords(self $action, array $records): void
    {
        $relationship = $this->getTable()?->getRelationship()
            ?? throw new LogicException(sprintf('The action [%s] detaches selected records from a relationship, which only a table declaring relationship() can supply.', $this->getName()));
        $reader = $this->getAssociationReader();
        $targetClass = $reader->describeRelationship($relationship)->targetClass;

        try {
            $count = 0;

            foreach ($records as $record) {
                if (! is_object($record)) {
                    if (! is_int($record) && ! is_string($record)) {
                        throw new LogicException(sprintf('DetachBulkAction can only detach entities or their keys, got %s.', get_debug_type($record)));
                    }

                    $record = $reader->getReference($targetClass, $record);
                }

                $reader->detach($relationship, $record);
                $count++;
            }

            $this->getEntityManagerForRecord($relationship->owner)->flush();
            $action->reportBulkProcessingSuccessfulRecordsCount($count);
        } catch (Throwable) {
            // One flush means the whole batch failed, and the danger toast is
            // what says so instead of a 500 — the DeleteBulkAction shape.
            $action->reportCompleteBulkProcessingFailure();
        }
    }
}
