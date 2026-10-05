<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Doctrine\ORM\QueryBuilder;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Verse;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditHushVerse;
use Sylament\Schemas\Schema;

/**
 * The middle of the `hush` nesting: a resource whose own record query hides
 * every verse headed `Hidden …`, under its parent as at the root — and roots
 * that query at `verse` rather than `record`, so the parent scope narrows
 * under whichever alias the resource chose.
 */
final class HushVerseResource extends Resource
{
    protected static ?string $recordClass = Verse::class;

    protected static ?string $recordTitleAttribute = 'heading';

    protected static ?string $parentResource = HushChapterResource::class;

    public function getRecordQuery(): QueryBuilder
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('verse')
            ->from(Verse::class, 'verse')
            ->andWhere("verse.heading NOT LIKE 'Hidden%'");
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('heading')->required(),
        ]);
    }

    public function getPages(): array
    {
        return [
            'edit' => EditHushVerse::route('/{record}/edit'),
        ];
    }
}
