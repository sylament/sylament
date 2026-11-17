<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Sylament\Tests\Fixtures\Entity\Invoice;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * Deletes the selected rows by key over a key an invoice line references.
 */
#[AsLiveComponent(name: 'Tables:ByKeyDeletingInvoiceTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class ByKeyDeletingInvoiceTable extends ByKeyDeletingTable
{
    protected function recordClass(): string
    {
        return Invoice::class;
    }

    protected function column(): string
    {
        return 'id';
    }
}
