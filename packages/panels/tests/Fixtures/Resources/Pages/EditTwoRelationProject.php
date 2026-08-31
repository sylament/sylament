<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Panels\Tests\Fixtures\Resources\TwoRelationProjectResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Twig\Markup;

/**
 * The relation-manager page rig — two managers, so the content schema is a tab bar.
 */
#[AsLiveComponent]
final class EditTwoRelationProject extends EditRecord
{
    protected static string $resource = TwoRelationProjectResource::class;

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
