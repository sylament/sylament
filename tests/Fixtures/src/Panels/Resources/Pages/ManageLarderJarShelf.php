<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use BackedEnum;
use Sylament\Actions\CreateAction;
use Sylament\Actions\EditAction;
use Sylament\Actions\ViewAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Pages\ManageRelatedRecords;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Panels\Resources\StockedLarderResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A larder's jars with no resource speaking for them: the page names its own title, relationship
 * title, navigation label and icon, its own form, and its own table, two jars a page under a
 * query-string identifier the table names, with row actions to view (never the Apricot jar) and
 * edit; its header creates a jar.
 */
#[AsLiveComponent]
final class ManageLarderJarShelf extends ManageRelatedRecords
{
    public const string UNVIEWABLE_LABEL = 'Apricot';

    protected static string $resource = StockedLarderResource::class;

    protected static string $relationship = 'jars';

    protected static ?string $relationshipTitle = 'Jar shelf';

    protected static ?string $title = 'Pantry shelf';

    protected static ?string $navigationLabel = 'Shelf';

    protected static string|BackedEnum|null $navigationIcon = 'heroicons:check-circle';

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
                TextColumn::make('label'),
            ])
            ->queryStringIdentifier('shelf')
            ->paginated([2])
            ->recordActions([
                ViewAction::make()->hidden(static fn (?LarderJar $record): bool => $record?->label === self::UNVIEWABLE_LABEL),
                EditAction::make(),
            ]);
    }

    public function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
