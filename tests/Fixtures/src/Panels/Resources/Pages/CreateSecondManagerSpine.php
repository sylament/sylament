<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Panels\Resources\Pages\CreateRecord;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Panels\KeyReadsRecordingAssociationReader;
use Sylament\Tests\Fixtures\Panels\Resources\SecondManagerSpineResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * Hands its form an application's reader recording both key reads, and writes through the second manager its resource answers.
 */
#[AsLiveComponent]
final class CreateSecondManagerSpine extends CreateRecord
{
    protected static string $resource = SecondManagerSpineResource::class;

    public function __construct(
        private readonly KeyReadsRecordingAssociationReader $reader,
    ) {
    }

    public function form(Schema $schema): Schema
    {
        $schema->setAssociationReader($this->reader);

        return parent::form($schema);
    }
}
