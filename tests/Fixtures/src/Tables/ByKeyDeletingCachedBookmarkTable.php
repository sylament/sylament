<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Sylament\Tests\Fixtures\Entity\CachedBookmark;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * Deletes the selected rows by key over a record the second-level cache holds.
 */
#[AsLiveComponent(name: 'Tables:ByKeyDeletingCachedBookmarkTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class ByKeyDeletingCachedBookmarkTable extends ByKeyDeletingTable
{
    protected function recordClass(): string
    {
        return CachedBookmark::class;
    }

    protected function column(): string
    {
        return 'name';
    }
}
