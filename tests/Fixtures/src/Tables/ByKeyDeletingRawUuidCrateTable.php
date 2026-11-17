<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Sylament\Tests\Fixtures\Entity\RawUuidCrate;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * Deletes the selected rows by key over a uuid its column holds as raw bytes.
 */
#[AsLiveComponent(name: 'Tables:ByKeyDeletingRawUuidCrateTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class ByKeyDeletingRawUuidCrateTable extends ByKeyDeletingTable
{
    protected function recordClass(): string
    {
        return RawUuidCrate::class;
    }

    protected function column(): string
    {
        return 'note';
    }
}
