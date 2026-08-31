<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Project;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditTwoRelationProject;
use Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\PaginatedTasksRelationManager;
use Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\TasksRelationManager;
use Sylament\Schemas\Schema;

/**
 * Two registered managers under string keys: the tabs arm, and the pin that
 * those keys reach the tab bar unchanged.
 */
final class TwoRelationProjectResource extends Resource
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
            'paged' => PaginatedTasksRelationManager::class,
        ];
    }

    public function getPages(): array
    {
        return [
            'edit' => EditTwoRelationProject::route('/{record}/edit'),
        ];
    }
}
