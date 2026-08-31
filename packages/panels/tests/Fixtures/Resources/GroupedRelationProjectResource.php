<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\RelationManagers\RelationGroup;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Project;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditGroupedRelationProject;
use Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\PaginatedTasksRelationManager;
use Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\TasksRelationManager;
use Sylament\Schemas\Schema;

/**
 * One registered group holding two managers: the arm that renders both of them
 * side by side, with no tab bar because the page registers a single entry.
 */
final class GroupedRelationProjectResource extends Resource
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
            'work' => RelationGroup::make('Work', [
                'primary' => TasksRelationManager::class,
                'paged' => PaginatedTasksRelationManager::class,
            ]),
        ];
    }

    public function getPages(): array
    {
        return [
            'edit' => EditGroupedRelationProject::route('/{record}/edit'),
        ];
    }
}
