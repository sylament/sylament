<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * The {@see HeadedLarderJarTable} pushing a `Stock` group of two confirmed header actions,
 * `restock` and `donate`, after its own.
 */
#[AsLiveComponent(name: 'Tables:GroupedHeaderLarderJarTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class GroupedHeaderLarderJarTable extends HeadedLarderJarTable
{
    public function table(Table $table): Table
    {
        return parent::table($table)->pushHeaderActions([
            ActionGroup::make([
                Action::make('restock')->requiresConfirmation(),
                Action::make('donate')->requiresConfirmation(),
            ])->label('Stock'),
        ]);
    }
}
