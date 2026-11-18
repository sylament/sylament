<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Three array rows, listed in the order the `records()` closure returns them, which records
 * the sort it is handed in the {@see HookLog} as `sort <column> <direction>`. Sortable by
 * label, by the year 2024's tally and, with `offersShelf`, by shelf; never by note, which is
 * not sortable, nor by secret, which is sortable but hidden. It remembers its sort in the
 * session.
 */
#[AsLiveComponent(name: 'Tables:SortInjectingRecordsTable', template: '@Fixtures/tables/table.html.twig')]
class SortInjectingRecordsTable implements InteractsWithTable
{
    use DefaultActionTrait;
    use HasTable;

    #[LiveProp]
    public bool $offersShelf = false;

    public function __construct(
        private readonly HookLog $hookLog,
    ) {
    }

    public function table(Table $table): Table
    {
        $columns = [
            TextColumn::make('label')->sortable(),
            TextColumn::make('note'),
            TextColumn::make('secret')->sortable()->hidden(),
            TextColumn::make('2024')->state(static fn (array $record): mixed => $record['tally'] ?? null)->sortable(),
        ];

        if ($this->offersShelf) {
            $columns[] = TextColumn::make('shelf')->sortable();
        }

        return $table
            ->records(function (?string $sortColumn, ?string $sortDirection): array {
                $this->hookLog->record("sort {$sortColumn} {$sortDirection}");

                return [
                    ['__key' => 'plum', 'label' => 'Plum', 'note' => 'tart', 'secret' => 'b', 'tally' => 3, 'shelf' => 'top'],
                    ['__key' => 'fig', 'label' => 'Fig', 'note' => 'sweet', 'secret' => 'a', 'tally' => 1, 'shelf' => 'low'],
                    ['__key' => 'quince', 'label' => 'Quince', 'note' => 'hard', 'secret' => 'c', 'tally' => 2, 'shelf' => 'mid'],
                ];
            })
            ->columns($columns)
            ->persistSortInSession();
    }
}
