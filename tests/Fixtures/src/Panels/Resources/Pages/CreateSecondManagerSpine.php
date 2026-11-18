<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\Resources\Pages\CreateRecord;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Actions\SecondManager;
use Sylament\Tests\Fixtures\Panels\KeyReadsRecordingAssociationReader;
use Sylament\Tests\Fixtures\Panels\Resources\SecondManagerSpineResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * Writes through the request's {@see SecondManager} in place of the manager the container injects, and hands its form an
 * application's reader recording both key reads.
 */
#[AsLiveComponent]
final class CreateSecondManagerSpine extends CreateRecord
{
    protected static string $resource = SecondManagerSpineResource::class;

    public function __construct(
        private readonly SecondManager $secondManager,
        private readonly KeyReadsRecordingAssociationReader $reader,
    ) {
    }

    public function setRecordPersistence(EntityManagerInterface $entityManager): void
    {
        parent::setRecordPersistence($this->secondManager->get());
    }

    public function form(Schema $schema): Schema
    {
        $schema->setAssociationReader($this->reader);

        return parent::form($schema);
    }
}
