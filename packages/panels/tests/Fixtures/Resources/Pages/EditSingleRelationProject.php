<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Panels\Tests\Fixtures\Resources\SingleRelationProjectResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Twig\Markup;

/**
 * The relation-manager page rig — one manager, so the content schema is a bare embed.
 */
#[AsLiveComponent]
final class EditSingleRelationProject extends EditRecord
{
    protected static string $resource = SingleRelationProjectResource::class;

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

    /**
     * The same missing route the trail dodges: with no index page there is
     * nowhere for the default cancel link to point, so the rig submits only.
     *
     * @return array<Action|ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [$this->getSaveFormAction()];
    }
}
