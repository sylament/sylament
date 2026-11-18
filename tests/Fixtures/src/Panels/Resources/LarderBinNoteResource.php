<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Entity\LarderBinNote;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditLarderBinNote;

/**
 * A bin's notes as a resource nested under the larder bin's, two levels below the larder,
 * titled by their text and crumbed by a breadcrumb of their own: a text field and an edit page.
 */
final class LarderBinNoteResource extends Resource
{
    protected static ?string $recordClass = LarderBinNote::class;

    protected static ?string $parentResource = LarderBinResource::class;

    protected static ?string $recordTitleAttribute = 'text';

    protected static ?string $breadcrumb = 'Notes';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('text'),
        ]);
    }

    public function getPages(): array
    {
        return [
            'edit' => EditLarderBinNote::route('/{record}/edit'),
        ];
    }
}
