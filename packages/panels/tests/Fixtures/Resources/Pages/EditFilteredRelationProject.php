<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Panels\Tests\Fixtures\Resources\FilteredRelationProjectResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Twig\Markup;

/**
 * The filtering page rig — three registrations, one of which survives, so the
 * content schema is a bare embed rather than a three-tab bar.
 */
#[AsLiveComponent]
final class EditFilteredRelationProject extends EditRecord
{
    protected static string $resource = FilteredRelationProjectResource::class;

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
