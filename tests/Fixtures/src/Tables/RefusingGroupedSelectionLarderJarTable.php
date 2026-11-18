<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * The {@see GroupedSelectionLarderJarTable} refusing the `Elder` jar to the selection.
 */
#[AsLiveComponent(name: 'Tables:RefusingGroupedSelectionLarderJarTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class RefusingGroupedSelectionLarderJarTable extends GroupedSelectionLarderJarTable
{
    public function table(Table $table): Table
    {
        return parent::table($table)->checkIfRecordIsSelectableUsing(static fn (LarderJar $record): bool => $record->label !== 'Elder');
    }
}
