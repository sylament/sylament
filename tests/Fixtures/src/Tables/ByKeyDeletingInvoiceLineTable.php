<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * Deletes the selected rows by key over a composite key of an association plus a scalar.
 */
#[AsLiveComponent(name: 'Tables:ByKeyDeletingInvoiceLineTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class ByKeyDeletingInvoiceLineTable extends ByKeyDeletingTable
{
    protected function recordClass(): string
    {
        return InvoiceLine::class;
    }

    protected function column(): string
    {
        return 'note';
    }
}
