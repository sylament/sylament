<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Sylament\Tests\Fixtures\Entity\OrderLine;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * Deletes the selected rows by key over a composite key of two scalar fields.
 */
#[AsLiveComponent(name: 'Tables:ByKeyDeletingOrderLineTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class ByKeyDeletingOrderLineTable extends ByKeyDeletingTable
{
    protected function recordClass(): string
    {
        return OrderLine::class;
    }

    protected function column(): string
    {
        return 'note';
    }
}
