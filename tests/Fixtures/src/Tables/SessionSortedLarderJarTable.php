<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * The {@see SortableLarderJarTable} remembering its sort in the session.
 */
#[AsLiveComponent(name: 'Tables:SessionSortedLarderJarTable', template: '@Fixtures/tables/table.html.twig')]
class SessionSortedLarderJarTable extends SortableLarderJarTable
{
    public function table(Table $table): Table
    {
        return parent::table($table)->persistSortInSession();
    }
}
