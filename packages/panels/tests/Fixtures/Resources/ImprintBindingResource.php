<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Actions\DeleteAction;
use Sylament\Actions\EditAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Binding;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateImprintBinding;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditImprintBinding;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListImprintBindings;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

/**
 * The nested vertical under a scalar-pair parent: every page a nested resource
 * can register, so the listing, the create and the delete redirect all resolve
 * through a parent record keyed by two fields.
 */
final class ImprintBindingResource extends Resource
{
    protected static ?string $recordClass = Binding::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $parentResource = BinderyResource::class;

    protected static bool $isGloballySearchable = false;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([TextColumn::make('title')])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListImprintBindings::route('/'),
            'create' => CreateImprintBinding::route('/create'),
            'edit' => EditImprintBinding::route('/{record}/edit'),
        ];
    }
}
