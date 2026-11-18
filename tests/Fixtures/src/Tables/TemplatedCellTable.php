<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Sylament\Tables\Columns\ViewColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A table of one array row whose cells are view columns: `orbit` a template that prints what
 * it is handed, aligned to the end, and `blank` a template that prints nothing.
 */
#[AsLiveComponent(name: 'Tables:TemplatedCellTable', template: '@Fixtures/tables/table.html.twig')]
class TemplatedCellTable implements InteractsWithTable
{
    use DefaultActionTrait;
    use HasTable;

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => [
                ['__key' => 'io', 'name' => 'Io', 'orbit' => '1.77 days'],
            ])
            ->columns([
                ViewColumn::make('orbit')->template('@Fixtures/tables/orbit-cell.html.twig', ['planet' => 'Jupiter'])->alignEnd(),
                ViewColumn::make('blank')->template('@Fixtures/tables/blank-cell.html.twig'),
            ]);
    }
}
