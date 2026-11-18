<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Panels\Resources\Pages\Page;
use Sylament\Tests\Fixtures\Panels\Resources\ArticleArchiveResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Twig\Markup;

/**
 * A resource page titled with markup and naming no crumb of its own.
 */
#[AsLiveComponent]
final class ArchiveNotice extends Page
{
    protected static string $resource = ArticleArchiveResource::class;

    public function getTitle(): Markup
    {
        return new Markup('Archive <em>notice</em>', 'UTF-8');
    }
}
