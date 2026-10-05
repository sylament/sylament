<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Doctrine\ORM\QueryBuilder;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Line;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditHushLine;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListHushLines;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

/**
 * The depth-two arm of the `hush` nesting: its own record query hides every
 * line reading `Hidden …`, and its table narrows further, past every line
 * reading `Muted …`, so both layers can be told apart on the nested list.
 */
final class HushLineResource extends Resource
{
    protected static ?string $recordClass = Line::class;

    protected static ?string $parentResource = HushVerseResource::class;

    public function getRecordQuery(): QueryBuilder
    {
        return parent::getRecordQuery()->andWhere("record.content NOT LIKE 'Hidden%'");
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('content')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([TextColumn::make('content')])
            ->modifyQueryUsing(fn (QueryBuilder $query): QueryBuilder => $query->andWhere("record.content NOT LIKE 'Muted%'"));
    }

    public function getPages(): array
    {
        return [
            'index' => ListHushLines::route('/'),
            'edit' => EditHushLine::route('/{record}/edit'),
        ];
    }
}
