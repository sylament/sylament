<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Panels\Tests\Fixtures\Resources\GroupedRelationProjectResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Twig\Markup;

/**
 * The relation-manager page rig — one group, so the content schema is the group's embeds side by side.
 */
#[AsLiveComponent]
final class EditGroupedRelationProject extends EditRecord
{
    protected static string $resource = GroupedRelationProjectResource::class;

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
