<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Actions\DeleteAction;
use Sylament\Actions\EditAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Imprint;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditBindery;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListBinderies;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ManageBinderyBindings;
use Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\ImprintBindingsRelationManager;
use Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\ImprintMotifsRelationManager;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

/**
 * The scalar pair as a relation owner: a second resource over the same records
 * as {@see ImprintResource}, carrying the two managers its edit page embeds,
 * the routed host of the same one-to-many, and the two nested resources under
 * it. It is a resource of its own rather than pages on the flat one because a
 * manager mounted on the flat edit page would fail the characterization that
 * page still renders after a key field is edited away.
 */
final class BinderyResource extends Resource
{
    protected static ?string $recordClass = Imprint::class;

    protected static ?string $recordTitleAttribute = 'label';

    protected static ?string $pluralRecordLabel = 'binderies';

    protected static ?string $slug = 'binderies';

    protected static bool $isGloballySearchable = false;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('house')->required(),
            TextInput::make('year')->required(),
            TextInput::make('label'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([TextColumn::make('label')])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public function getRelations(): array
    {
        return [
            'bindings' => ImprintBindingsRelationManager::class,
            'motifs' => ImprintMotifsRelationManager::class,
        ];
    }

    public function getPages(): array
    {
        return [
            'index' => ListBinderies::route('/'),
            'edit' => EditBindery::route('/{record}/edit'),
            'bindings' => ManageBinderyBindings::route('/{record}/bindings'),
        ];
    }
}
