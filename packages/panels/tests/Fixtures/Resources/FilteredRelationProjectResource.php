<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\RelationManagers\RelationGroup;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Project;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditFilteredRelationProject;
use Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\HiddenTasksRelationManager;
use Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\TaggedTasksRelationManager;
use Sylament\Schemas\Schema;

/**
 * Three registrations of which the filter keeps one: a manager the record shows,
 * a manager it hides, and a group that names none. The survivor is registered
 * bare, so the embed it mounts is also where the manager's own default
 * properties are read.
 */
final class FilteredRelationProjectResource extends Resource
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
            'tagged' => TaggedTasksRelationManager::class,
            'hidden' => HiddenTasksRelationManager::class,
            'empty' => RelationGroup::make('Empty', []),
        ];
    }

    public function getPages(): array
    {
        return [
            'edit' => EditFilteredRelationProject::route('/{record}/edit'),
        ];
    }
}
