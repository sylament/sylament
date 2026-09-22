<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\RelationManagers;

use Sylament\Actions\CreateAction;
use Sylament\Actions\DeleteAction;
use Sylament\Actions\EditAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\RelationManagers\RelationManager;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A manager whose owner is composite: registered on no page, so only a test
 * that mounts it by hand lists through it — which keeps the stamp's own edit
 * page free of a manager the nested suite renders it without.
 */
#[AsLiveComponent]
final class StampMarksRelationManager extends RelationManager
{
    protected static string $relationship = 'marks';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('label')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([TextColumn::make('label')])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
