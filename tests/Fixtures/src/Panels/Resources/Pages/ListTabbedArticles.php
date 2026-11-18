<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Doctrine\ORM\QueryBuilder;
use Sylament\Panels\Resources\Pages\ListRecords;
use Sylament\Schemas\Components\Tabs\Tab;
use Sylament\Tests\Fixtures\Panels\Resources\TabbedArticleResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * Titled and crumbed by its own statics; its tabs are all, the drafts, the drafts again for a tab whose scope a
 * single record's lookup skips, and two unlabelled keys, one per separator. A page opened `?tabless=1` offers no
 * tabs until a later request writes the flag false.
 */
#[AsLiveComponent]
final class ListTabbedArticles extends ListRecords
{
    protected static string $resource = TabbedArticleResource::class;

    protected static ?string $title = 'Article shelf';

    protected static ?string $breadcrumb = 'Shelf';

    #[LiveProp(writable: true, url: true)]
    public bool $tabless = false;

    public function getTabs(): array
    {
        if ($this->tabless) {
            return [];
        }

        $drafts = static fn (QueryBuilder $query): QueryBuilder => $query->andWhere("record.title LIKE 'Draft%'");

        return [
            'all' => Tab::make(),
            'drafts' => Tab::make()->modifyQueryUsing($drafts),
            'loose' => Tab::make('Loose drafts')->modifyQueryUsing($drafts)->excludeQueryWhenResolvingRecord(),
            'needs_review' => Tab::make(),
            'to-check' => Tab::make(),
        ];
    }
}
