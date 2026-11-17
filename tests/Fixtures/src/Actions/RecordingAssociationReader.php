<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\State\PropertyAccessor;
use Sylament\Tests\Fixtures\Schemas\HookLog;

/**
 * An application's own association reader, which a host hands its schema, recording each
 * changed-key question the record check asks it and whether it was rebound to a manager
 * first.
 */
final class RecordingAssociationReader extends AssociationReader
{
    private bool $rebound = false;

    public function __construct(
        private readonly HookLog $log,
        EntityManagerInterface $entityManager,
        PropertyAccessor $propertyAccessor,
    ) {
        parent::__construct($entityManager, $propertyAccessor);
    }

    public function withEntityManager(EntityManagerInterface $entityManager): static
    {
        $reader = parent::withEntityManager($entityManager);
        $reader->rebound = true;

        return $reader;
    }

    public function revertIdentifierChanges(object $record): array
    {
        $this->log->record(sprintf('key check on a %s by the %s reader', $record::class, $this->rebound ? 'rebound' : 'unbound'));

        return parent::revertIdentifierChanges($record);
    }
}
