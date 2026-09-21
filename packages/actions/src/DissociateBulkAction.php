<?php

declare(strict_types=1);

namespace Sylament\Actions;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Actions\Concerns\CanCustomizeProcess;
use Sylament\Schemas\Identity\IdentifierConstraint;
use Sylament\Schemas\State\AssociationTarget;
use Throwable;

class DissociateBulkAction extends BulkAction
{
    use CanCustomizeProcess;

    public static function getDefaultName(): ?string
    {
        return 'dissociate';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Dissociate selected');

        $this->modalHeading(fn (): string => ($label = $this->getTitleCasePluralRecordLabel()) === null ? 'Dissociate selected' : "Dissociate selected {$label}");

        $this->modalSubmitActionLabel('Dissociate');

        $this->successNotificationTitle('Dissociated');

        $this->failureNotificationTitle(
            fn (int $successCount, int $totalCount): string => $successCount > 0
                ? sprintf('Dissociated %d of %d', $successCount, $totalCount)
                : sprintf('Failed to dissociate %d records', $totalCount),
        );

        $this->defaultColor('danger');

        $this->icon('heroicons:x-mark-20-solid');

        $this->requiresConfirmation();

        $this->modalIcon('heroicons:x-mark');

        $this->action(function (): void {
            $this->process($this->dissociateRecords(...));
        });

        $this->deselectRecordsAfterCompletion();
    }

    /**
     * The fetching arm nulls each record's owner through the reader and flushes
     * once; the by-key arm is one DQL UPDATE per chunk of the plucked keys,
     * bypassing the UnitOfWork the way DeleteBulkAction's delete does —
     * already-managed entities keep their stale owner until reloaded. Every
     * mapping refusal runs before either arm — the four detach refusals and the
     * many-to-many one — as does the shape of the keys, so all six propagate as
     * developer errors rather than a toast.
     *
     * @param array<mixed> $records
     */
    private function dissociateRecords(self $action, array $records): void
    {
        $relationship = $this->getTable()?->getRelationship()
            ?? throw new LogicException(sprintf('The action [%s] dissociates selected records through a relationship, which only a table declaring relationship() can supply.', $this->getName()));
        $reader = $this->getAssociationReader();
        $reader->assertRelationshipAcceptsDetach($relationship);

        $target = $reader->describeRelationship($relationship);

        if ($target->isManyToMany) {
            throw new LogicException(sprintf('The action [%s] dissociates a one-to-many; [%s] is a many-to-many — use DetachBulkAction.', $this->getName(), $relationship->name));
        }

        $mappedBy = $target->mappedBy
            ?? throw new LogicException(sprintf('The one-to-many [%s] names no mapped-by field, which Doctrine requires of every one-to-many.', $relationship->name));

        if (! $action->shouldFetchSelectedRecords()) {
            $keys = [];

            foreach ($records as $record) {
                if (! is_string($record)) {
                    throw new LogicException(sprintf('DissociateBulkAction dissociates by key from record keys, got %s.', get_debug_type($record)));
                }

                $keys[] = $record;
            }

            $entityManager = $this->getEntityManagerForRecord($target->targetClass);
            $chunks = IdentifierConstraint::chunk($entityManager, $target->targetClass, $keys);

            try {
                $action->reportBulkProcessingSuccessfulRecordsCount($action->dissociateSelectedRecordsByKey($entityManager, $target, $mappedBy, $chunks));
            } catch (Throwable) {
                // The chunks are one transaction, so a throw means the whole
                // batch failed; the danger toast says so instead of a 500.
                $action->reportCompleteBulkProcessingFailure();
            }

            return;
        }

        try {
            $count = 0;

            foreach ($records as $record) {
                if (! is_object($record)) {
                    throw new LogicException(sprintf('DissociateBulkAction can only dissociate entities, got %s.', get_debug_type($record)));
                }

                $reader->detach($relationship, $record);
                $count++;
            }

            $this->getEntityManagerForRecord($relationship->owner)->flush();
            $action->reportBulkProcessingSuccessfulRecordsCount($count);
        } catch (Throwable) {
            $action->reportCompleteBulkProcessingFailure();
        }
    }

    /**
     * One DQL `UPDATE` per chunk, and one transaction from the second chunk on,
     * so a mid-batch failure means the whole batch failed —
     * {@see DeleteBulkAction::deleteSelectedRecordsByKey()}'s shape.
     *
     * @param list<list<string>> $chunks
     */
    private function dissociateSelectedRecordsByKey(EntityManagerInterface $entityManager, AssociationTarget $target, string $mappedBy, array $chunks): int
    {
        $dissociate = function () use ($entityManager, $target, $mappedBy, $chunks): int {
            $affectedRows = 0;

            foreach ($chunks as $chunk) {
                $affectedRows += $this->dissociateRecordKeyChunk($entityManager, $target, $mappedBy, $chunk);
            }

            return $affectedRows;
        };

        return count($chunks) > 1
            ? $entityManager->getConnection()->transactional($dissociate)
            : $dissociate();
    }

    /**
     * @param list<string> $keys
     */
    private function dissociateRecordKeyChunk(EntityManagerInterface $entityManager, AssociationTarget $target, string $mappedBy, array $keys): int
    {
        $query = $entityManager->createQueryBuilder()
            ->update($target->targetClass, 'sylament_dissociated_record')
            ->set(sprintf('sylament_dissociated_record.%s', $mappedBy), 'NULL');

        $affectedRows = $query
            ->where(IdentifierConstraint::in($query, 'sylament_dissociated_record', $target->targetClass, $keys, 'sylament_selected_record_keys'))
            ->getQuery()
            ->execute();

        if (! is_int($affectedRows)) {
            throw new LogicException(sprintf('A DQL update of [%s] answered with %s instead of a row count.', $target->targetClass, get_debug_type($affectedRows)));
        }

        return $affectedRows;
    }
}
