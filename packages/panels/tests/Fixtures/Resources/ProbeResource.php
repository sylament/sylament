<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\Pages\Page;
use Sylament\Panels\Resources\Pages\PageRegistration;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Article;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\PreviewProbePage;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\RecordProbePage;

final class ProbeResource extends Resource
{
    protected static ?string $recordClass = Article::class;

    /**
     * The derived slug would be `probes` — slugs come from the resource basename,
     * not the record class — and every route expectation pins `articles`, so this
     * also exercises the explicit-slug arm.
     */
    protected static ?string $slug = 'articles';

    public function getPages(): array
    {
        return [
            'index' => new PageRegistration(RecordProbePage::class, '/'),
            'probe' => new PageRegistration(RecordProbePage::class, '/{record}/probe'),
            'preview' => PreviewProbePage::route('/{record}/preview'),
        ];
    }

    /**
     * `RecordProbePage` is a plain page rather than a resource page, so it cannot
     * join a band; the preview page is the resource's only member.
     *
     * @return array<NavigationItem>
     */
    public function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([PreviewProbePage::class]);
    }
}
