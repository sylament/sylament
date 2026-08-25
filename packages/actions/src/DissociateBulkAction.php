<?php

declare(strict_types=1);

namespace Sylament\Actions;

use LogicException;
use Sylament\Actions\Concerns\CanCustomizeProcess;
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
     * once; the by-key arm is one DQL UPDATE over the plucked keys, bypassing the
     * UnitOfWork the way DeleteBulkAction's delete does — already-managed entities
     * keep their stale owner until reloaded. Every mapping refusal runs before
     * either arm — the three detach refusals and the many-to-many one — so all
     * four propagate as developer errors rather than a toast.
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
            try {
                $action->reportBulkProcessingSuccessfulRecordsCount($this->dissociateSelectedRecordsByKey($target, $mappedBy, $records));
            } catch (Throwable) {
                // One statement means the whole batch failed; the danger toast says so instead of a 500.
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
     * @param array<mixed> $keys
     */
    private function dissociateSelectedRecordsByKey(AssociationTarget $target, string $mappedBy, array $keys): int
    {
        if ($keys === []) {
            return 0;
        }

        $identifier = $this->getAssociationReader()->getIdentifierFieldName($target->targetClass);
        $entityManager = $this->getEntityManagerForRecord($target->targetClass);

        $affectedRows = $entityManager->createQueryBuilder()
            ->update($target->targetClass, 'sylament_dissociated_record')
            ->set(sprintf('sylament_dissociated_record.%s', $mappedBy), 'NULL')
            ->where(sprintf('sylament_dissociated_record.%s IN (:sylament_selected_record_keys)', $identifier))
            ->setParameter('sylament_selected_record_keys', $keys)
            ->getQuery()
            ->execute();

        if (! is_int($affectedRows)) {
            throw new LogicException(sprintf('A DQL update of [%s] answered with %s instead of a row count.', $target->targetClass, get_debug_type($affectedRows)));
        }

        return $affectedRows;
    }
}
