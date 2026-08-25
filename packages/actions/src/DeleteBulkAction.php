<?php

declare(strict_types=1);

namespace Sylament\Actions;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Actions\Concerns\CanCustomizeProcess;
use Throwable;

class DeleteBulkAction extends BulkAction
{
    use CanCustomizeProcess;

    public static function getDefaultName(): ?string
    {
        return 'delete';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Delete selected');

        $this->modalHeading(fn (): string => ($label = $this->getTitleCasePluralRecordLabel()) === null ? 'Delete selected' : "Delete selected {$label}");

        $this->modalSubmitActionLabel('Delete');

        $this->successNotificationTitle('Deleted');

        $this->failureNotificationTitle(
            fn (int $successCount, int $totalCount): string => $successCount > 0
                ? sprintf('Deleted %d of %d', $successCount, $totalCount)
                : sprintf('Failed to delete %d records', $totalCount),
        );

        $this->defaultColor('danger');

        $this->icon('heroicons:trash-20-solid');

        $this->requiresConfirmation();

        $this->action(function (): void {
            $this->process(function (DeleteBulkAction $action, array $records): void {
                if (!$action->shouldFetchSelectedRecords()) {
                    try {
                        $action->reportBulkProcessingSuccessfulRecordsCount($action->deleteSelectedRecordsByKey($records));
                    } catch (Throwable) {
                        // The same swallow as the fetching arm below, for the same
                        // reason: one statement means the whole batch failed, and the
                        // danger toast is what says so instead of a 500.
                        $action->reportCompleteBulkProcessingFailure();
                    }

                    return;
                }

                $registry = $action->managerRegistry
                    ?? throw new LogicException('DeleteBulkAction needs the container-built instance — register it as a service so the manager registry is injected.');

                $manager = null;

                try {
                    foreach ($records as $record) {
                        if (!is_object($record)) {
                            throw new LogicException(sprintf('DeleteBulkAction can only delete entities, got %s.', get_debug_type($record)));
                        }

                        $manager ??= $registry->getManagerForClass($record::class)
                            ?? throw new LogicException(sprintf('No Doctrine manager handles %s.', $record::class));

                        $manager->remove($record);
                    }

                    $manager?->flush();
                } catch (Throwable) {
                    // A bulk delete answers with the counting failure notification, not
                    // with a 500: the flush is atomic, so a throw means the whole batch
                    // failed and the danger toast is what says so.
                    $action->reportCompleteBulkProcessingFailure();
                }
            });
        });

        $this->deselectRecordsAfterCompletion();
    }

    /**
     * One DQL `DELETE` over the already-plucked keys, bypassing ORM cascades
     * and lifecycle callbacks.
     *
     * @param array<mixed> $keys
     */
    protected function deleteSelectedRecordsByKey(array $keys): int
    {
        if ($keys === []) {
            return 0;
        }

        $table = $this->getTable()
            ?? throw new LogicException(sprintf('The action [%s] deletes selected records by key, which only a table can supply.', $this->getName()));

        $entityManager = $table->getQueryEntityManager();
        $recordClass = $table->getRecordClass();

        if (!$entityManager instanceof EntityManagerInterface || $recordClass === null) {
            throw new LogicException(sprintf('The action [%s] deletes selected records by key, which needs a [query()] table.', $this->getName()));
        }

        $identifier = $entityManager->getClassMetadata($recordClass)->getSingleIdentifierFieldName();

        $affectedRows = $entityManager->createQueryBuilder()
            ->delete($recordClass, 'sylament_deleted_record')
            ->where(sprintf('sylament_deleted_record.%s IN (:sylament_selected_record_keys)', $identifier))
            ->setParameter('sylament_selected_record_keys', $keys)
            ->getQuery()
            ->execute();

        if (!is_int($affectedRows)) {
            throw new LogicException(sprintf('A DQL delete of [%s] answered with %s instead of a row count.', $recordClass, get_debug_type($affectedRows)));
        }

        return $affectedRows;
    }
}
