<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Actions\Action;
use Sylament\Panels\Resources\Resource;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditArticle;
use Twig\Markup;

/**
 * A searched article dressed by every result hook: a marked-up title, details labelled for a title starting `Moon` and
 * a bare list otherwise, an action bound to the hit, one bound to a record of its own and a hidden one; it has a view
 * page, so a hit links there, and sorts ahead of the panel's other resources.
 */
final class AnnotatedArticleSearchResource extends Resource
{
    protected static ?string $recordClass = Article::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $pluralRecordLabel = 'annotated articles';

    protected static ?int $globalSearchSort = -1;

    public function getPages(): array
    {
        return [
            'view' => EditArticle::route('/{record}'),
        ];
    }

    public function getGlobalSearchResultTitle(object $record): Markup
    {
        /** @var Article $record */
        return new Markup('<b>' . htmlspecialchars($record->title) . '</b>', 'UTF-8');
    }

    public function getGlobalSearchResultDetails(object $record): array
    {
        /** @var Article $record */
        return str_starts_with($record->title, 'Moon') ? ['Title' => $record->title, 'Words' => (string) str_word_count($record->title)] : [$record->title];
    }

    public function getGlobalSearchResultActions(object $record): array
    {
        return [
            Action::make('open')->url(fn (Article $record): string => "/open/{$record->id}"),
            Action::make('pinned')->record(new Article('Pinned'))->url(fn (Article $record): string => "/pinned/{$record->title}"),
            Action::make('archive')->url('/archive')->hidden(),
        ];
    }
}
