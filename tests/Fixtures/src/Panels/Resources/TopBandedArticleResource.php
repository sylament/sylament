<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Pages\Enums\SubNavigationPosition;
use Sylament\Panels\Resources\Pages\Page;
use Sylament\Panels\Resources\Resource;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditTopBandedArticle;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditTopBandedArticleNotes;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListArticles;

/**
 * An article resource that draws its record band on top, over two record pages, one of which
 * names a position of its own; the index is the article list's own, since only its URL is read.
 */
final class TopBandedArticleResource extends Resource
{
    protected static ?string $recordClass = Article::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title'),
        ]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListArticles::route('/'),
            'edit' => EditTopBandedArticle::route('/{record}/edit'),
            'notes' => EditTopBandedArticleNotes::route('/{record}/notes'),
        ];
    }

    public function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([EditTopBandedArticle::class, EditTopBandedArticleNotes::class]);
    }
}
