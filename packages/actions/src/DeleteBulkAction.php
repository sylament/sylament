<?php

declare(strict_types=1);

namespace Sylament\Actions;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use LogicException;
use Sylament\Actions\Concerns\CanCustomizeProcess;
use Sylament\Schemas\Identity\IdentifierConstraint;
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
            $this->process($this->deleteRecords(...));
        });

        $this->deselectRecordsAfterCompletion();
    }

    /**
     * The fetching arm removes each record through its own manager and flushes
     * once; the by-key arm is one DQL `DELETE` per chunk of the plucked keys,
     * and evicts from the second-level cache the entity region of the class it
     * writes alone, since a deleted record is never cached again and a cached
     * collection listing it therefore misses and reloads.
     * Everything a developer can get wrong — the table, its entity manager, its
     * record class, the shape of the keys — is resolved before either arm's
     * `try`, so it propagates instead of becoming the danger toast;
     * {@see DissociateBulkAction} places its mapping refusals the same way.
     *
     * @param array<mixed> $records
     */
    private function deleteRecords(self $action, array $records): void
    {
        if ($action->shouldFetchSelectedRecords()) {
            $action->deleteFetchedRecords($action, $records);

            return;
        }

        if ($records === []) {
            $action->reportBulkProcessingSuccessfulRecordsCount(0);

            return;
        }

        $table = $action->getTable()
            ?? throw new LogicException(sprintf('The action [%s] deletes selected records by key, which only a table can supply.', $action->getName()));

        $entityManager = $table->getQueryEntityManager();
        $recordClass = $table->getRecordClass();

        if (!$entityManager instanceof EntityManagerInterface || $recordClass === null) {
            throw new LogicException(sprintf('The action [%s] deletes selected records by key, which needs a [query()] table.', $action->getName()));
        }

        $keys = [];

        foreach ($records as $record) {
            if (!is_string($record)) {
                throw new LogicException(sprintf('DeleteBulkAction deletes by key from record keys, got %s.', get_debug_type($record)));
            }

            $keys[] = $record;
        }

        $chunks = IdentifierConstraint::chunk($entityManager, $recordClass, $keys);

        try {
            $action->reportBulkProcessingSuccessfulRecordsCount($action->deleteSelectedRecordsByKey($entityManager, $recordClass, $chunks));
        } catch (Throwable) {
            // The fetching arm's swallow, for the fetching arm's reason: the
            // chunks are one transaction, so a throw means the whole batch failed
            // and the danger toast is what says so instead of a 500.
            $action->reportCompleteBulkProcessingFailure();
        }
    }

    /**
     * @param array<mixed> $records
     */
    private function deleteFetchedRecords(self $action, array $records): void
    {
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
    }

    /**
     * One DQL `DELETE` per chunk, and one transaction from the second chunk on,
     * so a mid-batch failure means the whole batch failed.
     *
     * @param class-string       $recordClass
     * @param list<list<string>> $chunks
     */
    protected function deleteSelectedRecordsByKey(EntityManagerInterface $entityManager, string $recordClass, array $chunks): int
    {
        $delete = function () use ($entityManager, $recordClass, $chunks): int {
            $affectedRows = 0;

            foreach ($chunks as $chunk) {
                $affectedRows += $this->deleteRecordKeyChunk($entityManager, $recordClass, $chunk);
            }

            return $affectedRows;
        };

        return count($chunks) > 1
            ? $entityManager->getConnection()->transactional($delete)
            : $delete();
    }

    /**
     * @param class-string $recordClass
     * @param list<string> $keys
     */
    private function deleteRecordKeyChunk(EntityManagerInterface $entityManager, string $recordClass, array $keys): int
    {
        $query = $entityManager->createQueryBuilder()->delete($recordClass, 'sylament_deleted_record');

        $affectedRows = $query
            ->where(IdentifierConstraint::in($query, 'sylament_deleted_record', $recordClass, $keys, 'sylament_selected_record_keys'))
            ->getQuery()
            ->setHint(Query::HINT_CACHE_EVICT, true)
            ->execute();

        if (!is_int($affectedRows)) {
            throw new LogicException(sprintf('A DQL delete of [%s] answered with %s instead of a row count.', $recordClass, get_debug_type($affectedRows)));
        }

        return $affectedRows;
    }
}
