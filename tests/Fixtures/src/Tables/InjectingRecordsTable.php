<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Support\DependencyInjection\StaticInstanceRegistry;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Filters\Filter;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A table of array rows that records into the `HookLog` each time it builds its table, and
 * what its `records()` closure is handed each time it runs: the host's browsing state by
 * Filament's eight names, the table by name and by type, the host, a public container
 * service by type and a private one; its `inspect` row action records that it ran.
 */
#[AsLiveComponent(name: 'Tables:InjectingRecordsTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class InjectingRecordsTable implements InteractsWithActions, InteractsWithSchemas, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;
    use HasTable;

    public function __construct(
        private readonly HookLog $hookLog,
        private readonly StaticInstanceRegistry $staticInstanceRegistry,
    ) {
    }

    /**
     * @return array<string>
     */
    public function schemas(): array
    {
        return [];
    }

    public function table(Table $table): Table
    {
        $this->hookLog->record('table built');

        return $table
            ->records(function (array $columnSearches, array $filters, int $page, int|string|null $recordsPerPage, ?string $search, array $sort, ?string $sortColumn, ?string $sortDirection, Table $table, object $liveComponent, Table $grid, StaticInstanceRegistry $registry, ?HookLog $privateLog = null): array {
                $this->hookLog->record(json_encode([
                    'columnSearches' => $columnSearches,
                    'filters' => $filters,
                    'page' => $page,
                    'recordsPerPage' => $recordsPerPage,
                    'search' => $search,
                    'sort' => $sort,
                    'sortColumn' => $sortColumn,
                    'sortDirection' => $sortDirection,
                    'table' => $table === $this->getTable() && $grid === $table ? 'its table' : 'another table',
                    'liveComponent' => $liveComponent === $this ? 'its host' : 'another host',
                    'registry' => $registry === $this->staticInstanceRegistry ? 'the shared instance' : 'another instance',
                    'privateLog' => $privateLog instanceof HookLog ? 'a log' : 'none',
                ], JSON_THROW_ON_ERROR));

                return [
                    ['__key' => 'lamp', 'name' => 'Lamp', 'maker' => ['name' => 'Acme']],
                    ['__key' => 'vase', 'name' => 'Vase', 'maker' => ['name' => 'Brine']],
                ];
            })
            ->columns([
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('maker.name')->searchable(isIndividual: true),
            ])
            ->filters([
                Filter::make('short'),
            ])
            ->recordActions([
                Action::make('inspect')->action(function (): void {
                    $this->hookLog->record('inspected');
                }),
            ]);
    }
}
