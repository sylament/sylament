<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Panels\Resources\ParentResourceRegistration;
use Sylament\Panels\Resources\Resource;
use Sylament\Tests\Fixtures\Entity\Voyage;

/**
 * A dock's departures as a resource nested under the dock's, naming the association on the
 * registration its parent static builds, registered on no panel.
 */
final class DepartingVoyageResource extends Resource
{
    protected static ?string $recordClass = Voyage::class;

    protected static ?string $parentResource = DockResource::class;

    public function getParentResourceRegistration(): ?ParentResourceRegistration
    {
        return parent::getParentResourceRegistration()?->relationship('departures');
    }
}
