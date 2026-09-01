<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Pages\Page;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Project;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditMrrProject;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListMrrProjects;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ManageMrrProjectChecklist;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ManageMrrProjectTasks;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

/**
 * The owner side of the manage-related-records rig: the `tasks` page is keyed
 * by the relationship name, which is what {@see MrrTaskResource} is listed
 * through.
 */
final class MrrProjectResource extends Resource
{
    protected static ?string $recordClass = Project::class;

    protected static ?string $slug = 'projects';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListMrrProjects::route('/'),
            'edit' => EditMrrProject::route('/{record}/edit'),
            'tasks' => ManageMrrProjectTasks::route('/{record}/tasks'),
            'checklist' => ManageMrrProjectChecklist::route('/{record}/checklist'),
        ];
    }

    public function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            EditMrrProject::class,
            ManageMrrProjectTasks::class,
        ]);
    }
}
