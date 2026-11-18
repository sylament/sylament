<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Panels\Resources\Pages\Page;
use Sylament\Tests\Fixtures\Panels\Resources\ArticleArchiveResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A resource page that names its own crumb and leaves the resource's trail out.
 */
#[AsLiveComponent]
final class ArchiveDigest extends Page
{
    protected static string $resource = ArticleArchiveResource::class;

    protected static ?string $title = 'Monthly digest';

    protected static ?string $breadcrumb = 'Digest';

    public function hasResourceBreadcrumbs(): bool
    {
        return false;
    }
}
