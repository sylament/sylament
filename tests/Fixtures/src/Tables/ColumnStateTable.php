<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use stdClass;
use Sylament\Tables\Columns\Column;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A table of array rows, one keyed by a numeric string, whose cells read their state from the
 * row, from a literal or from a closure: blank, zero and false values meet a default, a string
 * meets a separator (an empty one splitting nothing), a closure takes the column's injections and the row loop, and the closures
 * that count record each evaluation in the `HookLog`.
 */
#[AsLiveComponent(name: 'Tables:ColumnStateTable', template: '@Fixtures/tables/table.html.twig')]
class ColumnStateTable implements InteractsWithTable
{
    use DefaultActionTrait;
    use HasTable;

    public function __construct(
        private readonly HookLog $hookLog,
    ) {
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => [
                ['__key' => '7', 'name' => 'Lamp', 'shelf' => ['row' => 'Top'], 'note' => null, 'tags' => 'oak,ash', 'unsplit' => 'oak,ash'],
                ['__key' => 'vase', 'name' => 'Vase', 'shelf' => [], 'note' => '', 'tags' => ''],
                ['__key' => 'jug', 'name' => 'Jug', 'note' => '   ', 'tags' => '   '],
                ['__key' => 'cup', 'name' => 'Cup', 'note' => 0],
                ['__key' => 'mug', 'name' => 'Mug', 'note' => '0'],
                ['__key' => 'pot', 'name' => 'Pot', 'note' => false],
            ])
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('shelf.row'),
                TextColumn::make('counted')->state($this->countState(...)),
                TextColumn::make('overridden')->state('first')->getStateUsing('second'),
                TextColumn::make('absentField')->state('—'),
                TextColumn::make('note')->default('—'),
                TextColumn::make('tags')
                    ->separator()
                    ->listWithLineBreaks()
                    ->default($this->countDefault(...)),
                TextColumn::make('unsplit')
                    ->separator('')
                    ->listWithLineBreaks(),
                TextColumn::make('injected')
                    ->getStateUsing($this->describeInjections(...))
                    ->extraCellAttributes(fn (string $state): array => ['data-state' => $state]),
                TextColumn::make('loop')->getStateUsing($this->describeRowLoop(...)),
            ]);
    }

    /**
     * @param array{name: string} $record
     */
    private function countState(array $record): string
    {
        $this->hookLog->record("state {$record['name']}");

        return $record['name'];
    }

    /**
     * @param array{name: string} $record
     *
     * @return list<string>
     */
    private function countDefault(array $record): array
    {
        $this->hookLog->record("default {$record['name']}");

        return ['none'];
    }

    /**
     * @param array{name: string} $record
     */
    private function describeInjections(Column $column, array $record, stdClass $rowLoop, Table $table, InteractsWithTable $liveComponent, Column $boundColumn): string
    {
        return implode(', ', [
            $column->getName(),
            $record['name'],
            'row ' . var_export($rowLoop->iteration, true),
            $table->getLiveComponent() === $liveComponent ? 'its table' : 'another table',
            $liveComponent === $this ? 'this host' : 'another host',
            $boundColumn === $column ? 'the same column' : 'another column',
        ]);
    }

    private function describeRowLoop(stdClass $rowLoop): string
    {
        $read = static fn (string $property): string => var_export($rowLoop->{$property}, true);

        return implode(' ', array_filter([
            sprintf('%s of %s', $read('iteration'), $read('count')),
            sprintf('index %s', $read('index')),
            sprintf('%s left', $read('remaining')),
            $rowLoop->odd === true ? 'odd' : 'even',
            $rowLoop->first === true ? 'first' : null,
            $rowLoop->last === true ? 'last' : null,
            sprintf('depth %s', $read('depth')),
            $rowLoop->parent === null ? 'no parent' : 'a parent',
        ]));
    }
}
