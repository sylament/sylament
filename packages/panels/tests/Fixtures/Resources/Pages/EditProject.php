<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Panels\Tests\Fixtures\Resources\ProjectResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Twig\Markup;

/**
 * The page a relation manager is mounted against: the class-string a manager
 * resolves its owner through, and the resource that registers no relations at
 * all.
 */
#[AsLiveComponent]
final class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    /**
     * The rig's resource is mounted in no panel and declares no index page, so
     * there is no route for the default trail to link back to.
     *
     * @return array<int|string, string|Markup>
     */
    public function getBreadcrumbs(): array
    {
        return [];
    }
}
