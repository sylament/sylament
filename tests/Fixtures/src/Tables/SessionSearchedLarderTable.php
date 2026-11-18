<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * The {@see SearchableLarderTable} remembering its global and per-column search terms in the session.
 */
#[AsLiveComponent(name: 'Tables:SessionSearchedLarderTable', template: '@Fixtures/tables/table.html.twig')]
class SessionSearchedLarderTable extends SearchableLarderTable
{
    public function table(Table $table): Table
    {
        return parent::table($table)->persistSearchInSession()->persistColumnSearchesInSession();
    }
}
