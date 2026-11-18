<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\CappedHeadline;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateAssignedSlugHeadline;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListAssignedSlugHeadlines;

/**
 * Headlines whose unique slug the create page assigns and no field maps, so a taken slug is a refusal that names no field.
 */
final class AssignedSlugHeadlineResource extends Resource
{
    protected static ?string $recordClass = CappedHeadline::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code'),
            TextInput::make('title'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title'),
        ]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListAssignedSlugHeadlines::route('/'),
            'create' => CreateAssignedSlugHeadline::route('/create'),
        ];
    }
}
