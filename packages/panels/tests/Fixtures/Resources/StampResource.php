<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Actions\DeleteAction;
use Sylament\Actions\EditAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Stamp;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateStamp;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditStamp;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ManageStampMarks;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

/**
 * The nested flagship: a derived identity whose identifier association is also
 * the parent link, in the index-less shape — its records are listed by the
 * parent, so it registers a create and an edit page and no listing. Its third
 * page lists the marks of one stamp, which is a composite owner.
 * {@see FlatStampResource} is the searchable resource over these records, so
 * this one opts out: two searchable resources over one entity collapse into a
 * single global-search group.
 */
final class StampResource extends Resource
{
    protected static ?string $recordClass = Stamp::class;

    protected static ?string $recordTitleAttribute = 'caption';

    protected static ?string $parentResource = CatalogueResource::class;

    protected static bool $isGloballySearchable = false;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->required(),
            TextInput::make('caption'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([TextColumn::make('code'), TextColumn::make('caption')])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public function getPages(): array
    {
        return [
            'create' => CreateStamp::route('/create'),
            'edit' => EditStamp::route('/{record}/edit'),
            'marks' => ManageStampMarks::route('/{record}/marks'),
        ];
    }
}
