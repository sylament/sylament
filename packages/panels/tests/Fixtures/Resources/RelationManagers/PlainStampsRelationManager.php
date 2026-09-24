<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\RelationManagers;

use Sylament\Actions\CreateAction;
use Sylament\Actions\DeleteAction;
use Sylament\Actions\EditAction;
use Sylament\Actions\ReplicateAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\RelationManagers\RelationManager;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * {@see StampsRelationManager} without a related resource, registered on no
 * page: it mints no URL, so a test can mount it by hand on a panel that is not
 * the default one and drive its own actions.
 */
#[AsLiveComponent]
final class PlainStampsRelationManager extends RelationManager
{
    protected static string $relationship = 'stamps';

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
            ->headerActions([CreateAction::make()])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                ReplicateAction::make()->schema(fn (Schema $schema): Schema => $this->form($schema)),
            ]);
    }
}
