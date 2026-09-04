<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Article;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListGroupingArticles;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

/**
 * A listing that offers a grouping, so the routed page's `?grouping=` arm has a
 * group to seed. It sits on the probe panel rather than beside the admin
 * articles, whose sidebar labels are pinned exactly, and names no record title
 * attribute, which is what keeps that panel unsearchable.
 */
final class GroupingArticleResource extends Resource
{
    protected static ?string $recordClass = Article::class;

    public function table(Table $table): Table
    {
        return $table
            ->columns([TextColumn::make('title'), TextColumn::make('status')])
            ->groups(['status']);
    }

    public function getPages(): array
    {
        return [
            'index' => ListGroupingArticles::route('/'),
        ];
    }
}
