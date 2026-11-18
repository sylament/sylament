<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * The {@see ColumnManagerProductTable} that does not remember its columns in the session.
 */
#[AsLiveComponent(name: 'Tables:UnpersistedColumnManagerProductTable', template: '@Fixtures/tables/table.html.twig')]
class UnpersistedColumnManagerProductTable extends ColumnManagerProductTable
{
    public function table(Table $table): Table
    {
        return parent::table($table)->persistColumnsInSession(false);
    }
}
