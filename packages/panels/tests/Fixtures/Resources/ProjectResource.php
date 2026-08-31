<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Project;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditProject;
use Sylament\Schemas\Schema;

/**
 * The owner side of the relation-manager rig: the resource a manager resolves
 * its owner record through. Deliberately outside the test panel, so the
 * navigation and routing suites keep the shape they were written against.
 */
final class ProjectResource extends Resource
{
    protected static ?string $recordClass = Project::class;

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
        ]);
    }

    public function getPages(): array
    {
        return [
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }
}
