<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\DeleteBulkAction;
use Sylament\Tests\Fixtures\Actions\RefusingDeleteBulkAction;
use Sylament\Tests\Fixtures\Actions\ShortCountingDeleteBulkAction;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * Deletes the selected products by key: `delete` as shipped, `deleteShort` and
 * `deleteRefused` through overrides that delete all but the first key or refuse them all,
 * and `deleteUsing` through a `using()` that records what it is handed; `loose` is a
 * by-key delete the host offers outside its table.
 */
#[AsLiveComponent(name: 'Tables:ByKeyDeletingProductTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class ByKeyDeletingProductTable extends ByKeyDeletingTable
{
    public function __construct(
        EntityManagerInterface $entityManager,
        private readonly HookLog $log,
    ) {
        parent::__construct($entityManager);
    }

    public function looseAction(): DeleteBulkAction
    {
        return DeleteBulkAction::make('loose')->fetchSelectedRecords(false);
    }

    protected function recordClass(): string
    {
        return Product::class;
    }

    protected function column(): string
    {
        return 'name';
    }

    protected function moreToolbarActions(): array
    {
        return [
            ShortCountingDeleteBulkAction::make('deleteShort')->fetchSelectedRecords(false),
            RefusingDeleteBulkAction::make('deleteRefused')->fetchSelectedRecords(false),
            DeleteBulkAction::make('deleteUsing')
                ->fetchSelectedRecords(false)
                ->using(function (array $records): void {
                    $this->log->record('using() was handed ' . implode(', ', array_map(get_debug_type(...), $records)));
                }),
        ];
    }
}
