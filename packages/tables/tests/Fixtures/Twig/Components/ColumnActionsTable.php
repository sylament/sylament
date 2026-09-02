<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Twig\Components;

use Sylament\Actions\Action;
use Sylament\Tables\Columns\Column;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Records\Song;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A live host whose columns carry all three `action()` arms — a closure the host's
 * own live action evaluates, a method name the string arm wraps, and an `Action`
 * object it refuses. Each call appends to a `LiveProp`, so what the action was
 * handed survives the round trip and the next render reads it.
 */
#[AsLiveComponent(name: 'ColumnActionsTable', template: '@TablesFixtures/components/ColumnActionsTable.html.twig')]
class ColumnActionsTable implements InteractsWithTable
{
    use DefaultActionTrait;
    use HasTable;

    /**
     * @var list<string>
     */
    #[LiveProp]
    public array $calls = [];

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => [
                new Song('Airbag', 'OK Computer', id: 1),
                new Song('Let Down', 'OK Computer', id: 2),
            ])
            ->columns([
                TextColumn::make('title')->action(function (Song $record, Column $column): void {
                    $this->calls[] = sprintf('closure:%s:%s', $record->title, $column->getRecordKey() ?? '');
                }),
                TextColumn::make('album')->action('appendAlbumCall'),
                TextColumn::make('actionObject')->state('view')->action(Action::make('view')),
                TextColumn::make('id'),
            ]);
    }

    public function appendAlbumCall(?object $record = null): string
    {
        $this->calls[] = 'string:' . ($record instanceof Song ? $record->title : 'none');

        return 'called';
    }
}
