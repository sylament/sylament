<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditTitleKeyedArticle;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListTitleKeyedArticles;

/**
 * Keys its URLs by the article's title, and registers its page paths without a leading slash.
 */
final class TitleKeyedArticleResource extends Resource
{
    protected static ?string $recordClass = Article::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $recordRouteKeyName = 'title';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required(),
        ]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListTitleKeyedArticles::route(''),
            'edit' => EditTitleKeyedArticle::route('{record}/edit'),
        ];
    }
}
