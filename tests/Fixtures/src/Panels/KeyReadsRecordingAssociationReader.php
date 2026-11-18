<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\State\PropertyAccessor;
use Sylament\Tests\Fixtures\Actions\SecondManager;
use Sylament\Tests\Fixtures\Schemas\HookLog;

/**
 * An application's own association reader, which a create page hands its form, recording both key reads a create asks
 * it, the record check's changed-key question and the blank-key read after `persist()`, each with the manager it was
 * rebound to: the registry's, the request's {@see SecondManager}, or none.
 */
final class KeyReadsRecordingAssociationReader extends AssociationReader
{
    private string $binding = 'unbound';

    public function __construct(
        private readonly HookLog $log,
        private readonly SecondManager $secondManager,
        private readonly EntityManagerInterface $registryManager,
        PropertyAccessor $propertyAccessor,
    ) {
        parent::__construct($registryManager, $propertyAccessor);
    }

    public function withEntityManager(EntityManagerInterface $entityManager): static
    {
        $reader = parent::withEntityManager($entityManager);
        $reader->binding = match ($entityManager) {
            $this->registryManager => "rebound to the registry's manager",
            $this->secondManager->get() => 'rebound to the second manager',
            default => 'rebound to another manager',
        };

        return $reader;
    }

    public function revertIdentifierChanges(object $record): array
    {
        $this->log->record(sprintf('key check on a %s by the reader %s', $record::class, $this->binding));

        return parent::revertIdentifierChanges($record);
    }

    public function hasBlankAssignedIdentifier(object $record): bool
    {
        $this->log->record(sprintf('blank-key read on a %s by the reader %s', $record::class, $this->binding));

        return parent::hasBlankAssignedIdentifier($record);
    }
}
