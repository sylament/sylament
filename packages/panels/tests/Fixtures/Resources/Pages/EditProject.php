<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
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
