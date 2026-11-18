<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * The {@see FilterableLarderJarTable} remembering its applied filters in the session.
 */
#[AsLiveComponent(name: 'Tables:SessionFilteredLarderJarTable', template: '@Fixtures/tables/table.html.twig')]
class SessionFilteredLarderJarTable extends FilterableLarderJarTable
{
    public function table(Table $table): Table
    {
        return parent::table($table)->persistFiltersInSession();
    }
}
