<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Verse;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateVerse;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditVerse;
use Sylament\Schemas\Schema;

/**
 * A nested resource in the index-less shape: its records are listed by the
 * parent, so it registers a create and an edit page and nothing else.
 */
final class VerseResource extends Resource
{
    protected static ?string $recordClass = Verse::class;

    protected static ?string $recordTitleAttribute = 'heading';

    protected static ?string $parentResource = ChapterResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('heading')->required(),
        ]);
    }

    public function getPages(): array
    {
        return [
            'create' => CreateVerse::route('/create'),
            'edit' => EditVerse::route('/{record}/edit'),
        ];
    }
}
