<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\RelationManagers\RelationGroup;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Project;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditConfiguredRelationProject;
use Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\TaggedTasksRelationManager;
use Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\TasksRelationManager;
use Sylament\Schemas\Schema;

/**
 * A configuration beside a group, registered as a bare list: the tabs arm over
 * the two entry kinds the plain-class arm does not cover, and the int keys that
 * pin the active manager's string cast.
 */
final class ConfiguredRelationProjectResource extends Resource
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
            TaggedTasksRelationManager::make(['tag' => 'configured']),
            RelationGroup::make('Work', [
                'primary' => TasksRelationManager::class,
            ]),
        ];
    }

    public function getPages(): array
    {
        return [
            'edit' => EditConfiguredRelationProject::route('/{record}/edit'),
        ];
    }
}
