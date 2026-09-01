<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Actions\DeleteAction;
use Sylament\Actions\EditAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Task;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateMrrTask;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditMrrTask;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

/**
 * The related side: a nested resource with no index page of its own, so the
 * manage page on {@see MrrProjectResource} is the listing its records are
 * reached through. Its columns and record actions arrive on the manage page's
 * table through the related stamp.
 */
final class MrrTaskResource extends Resource
{
    protected static ?string $recordClass = Task::class;

    protected static ?string $slug = 'tasks';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $parentResource = MrrProjectResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title'),
                TextColumn::make('position'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public function getPages(): array
    {
        return [
            'create' => CreateMrrTask::route('/create'),
            'edit' => EditMrrTask::route('/{record}/edit'),
        ];
    }
}
