<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A table of array rows whose cells act on their row when clicked: one calls a host method by
 * name, one a closure and one mounts an action; each records what it did in the `HookLog`.
 */
#[AsLiveComponent(name: 'Tables:ColumnActionTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class ColumnActionTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    public function __construct(
        private readonly HookLog $hookLog,
    ) {
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => [
                ['__key' => 'lamp', 'name' => 'Lamp'],
            ])
            ->columns([
                TextColumn::make('name')->action('archive'),
                TextColumn::make('note')->state('Fragile')->action($this->note(...)),
                TextColumn::make('sku')->state('L-1')->action(Action::make('open')->action($this->open(...))),
            ]);
    }

    /**
     * @param array{name: string} $record
     */
    public function archive(array $record): void
    {
        $this->hookLog->record("archive {$record['name']}");
    }

    /**
     * @param array{name: string} $record
     */
    private function note(array $record): void
    {
        $this->hookLog->record("note {$record['name']}");
    }

    /**
     * @param array{name: string} $record
     */
    private function open(array $record): void
    {
        $this->hookLog->record("open {$record['name']}");
    }
}
