<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Project;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditSingleRelationProject;
use Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\TasksRelationManager;
use Sylament\Schemas\Schema;

/**
 * One registered manager: the arm that mounts a bare embed with no tab bar
 * around it.
 */
final class SingleRelationProjectResource extends Resource
{
    protected static ?string $recordClass = Project::class;

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
        ]);
    }

    public function getRelations(): array
    {
        return [
            'tasks' => TasksRelationManager::class,
        ];
    }

    public function getPages(): array
    {
        return [
            'edit' => EditSingleRelationProject::route('/{record}/edit'),
        ];
    }
}
