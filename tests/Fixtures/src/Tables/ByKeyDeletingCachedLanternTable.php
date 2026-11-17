<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Sylament\Tests\Fixtures\Entity\CachedLantern;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * Deletes the selected rows by key over a record the second-level cache holds.
 */
#[AsLiveComponent(name: 'Tables:ByKeyDeletingCachedLanternTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class ByKeyDeletingCachedLanternTable extends ByKeyDeletingTable
{
    protected function recordClass(): string
    {
        return CachedLantern::class;
    }

    protected function column(): string
    {
        return 'name';
    }
}
