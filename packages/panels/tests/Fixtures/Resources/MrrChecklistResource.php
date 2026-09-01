<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Task;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditMrrChecklistTask;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

/**
 * The same records as {@see MrrTaskResource}, reached through a resource that
 * registers an edit page and **no create page** — so a manage page relating to
 * this one has nowhere to link a create action at and mounts a modal instead,
 * which is the only path the page's action-record, label, schema-resolver and
 * relationship hooks are reachable through.
 */
final class MrrChecklistResource extends Resource
{
    protected static ?string $recordClass = Task::class;

    protected static ?string $slug = 'checklist';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('title')]);
    }

    public function getPages(): array
    {
        return [
            'edit' => EditMrrChecklistTask::route('/{record}/edit'),
        ];
    }
}
