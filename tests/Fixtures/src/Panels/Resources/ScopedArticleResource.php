<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Doctrine\ORM\QueryBuilder;
use Sylament\Actions\DeleteAction;
use Sylament\Actions\EditAction;
use Sylament\Actions\ViewAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateScopedArticle;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditScopedArticle;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListScopedArticles;

/**
 * Speaks only for the articles whose title starts with an `A`, through one record query it builds once,
 * under an alias of its own.
 */
final class ScopedArticleResource extends Resource
{
    protected static ?string $recordClass = Article::class;

    protected static ?string $recordLabel = 'shortlisted article';

    private ?QueryBuilder $recordQuery = null;

    public function getRecordQuery(): QueryBuilder
    {
        return $this->recordQuery ??= $this->getEntityManager()
            ->createQueryBuilder()
            ->select('article')
            ->from(Article::class, 'article')
            ->where('article.title LIKE :initial')
            ->setParameter('initial', 'A%');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title'),
            ])
            ->recordActions([
                EditAction::make(),
                ViewAction::make(),
                DeleteAction::make(),
            ]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListScopedArticles::route('/'),
            'create' => CreateScopedArticle::route('/create'),
            'edit' => EditScopedArticle::route('/{record}/edit'),
        ];
    }
}
