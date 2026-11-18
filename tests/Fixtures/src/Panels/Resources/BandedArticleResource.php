<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Pages\Page;
use Sylament\Panels\Resources\Resource;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditBandedArticle;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditTopBandedArticle;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListArticles;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\UnroutedBandedArticlePage;

/**
 * An article resource naming no sub-navigation position, whose record band lists its own edit
 * page, another resource's edit page and a page of its own it registers nowhere; the index is
 * the article list's own, since only its URL is read.
 */
final class BandedArticleResource extends Resource
{
    protected static ?string $recordClass = Article::class;

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
            'edit' => EditBandedArticle::route('/{record}/edit'),
        ];
    }

    public function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([EditBandedArticle::class, EditTopBandedArticle::class, UnroutedBandedArticlePage::class]);
    }
}
