<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use BackedEnum;
use Sylament\Actions\DeleteAction;
use Sylament\Actions\EditAction;
use Sylament\Actions\ViewAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Grouping\Group;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreatePreserveJar;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditPreserveJar;

/**
 * A larder's jars as a resource nested under the larder's, labelled apart from the association's
 * name, with a navigation icon of its own: a sortable, searchable label, a table groupable by
 * seal, create and edit pages, and row actions to view, edit (never for the Quince jar) and
 * delete.
 */
final class PreserveJarResource extends Resource
{
    public const string UNEDITABLE_LABEL = 'Quince';

    protected static ?string $recordClass = LarderJar::class;

    protected static ?string $parentResource = StockedLarderResource::class;

    protected static ?string $recordLabel = 'jar';

    protected static ?string $pluralRecordLabel = 'preserve jars';

    protected static ?string $recordTitleAttribute = 'label';

    protected static string|BackedEnum|null $navigationIcon = 'heroicons:user-circle';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('label')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')->sortable()->searchable(),
            ])
            ->groups([Group::make('seal')])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->hidden(static fn (?LarderJar $record): bool => $record?->label === self::UNEDITABLE_LABEL),
                DeleteAction::make(),
            ]);
    }

    public function getPages(): array
    {
        return [
            'create' => CreatePreserveJar::route('/create'),
            'edit' => EditPreserveJar::route('/{record}/edit'),
        ];
    }
}
