<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Panels\Resources\ParentResourceRegistration;
use Sylament\Panels\Resources\Resource;
use Sylament\Tests\Fixtures\Entity\Voyage;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListArrivingVoyages;

/**
 * A dock's arrivals as a resource nested under the dock's, its registration built off the
 * parent's `asParent()` and naming the association, since two of the dock's associations hold
 * voyages: a list page of its own.
 */
final class ArrivingVoyageResource extends Resource
{
    protected static ?string $recordClass = Voyage::class;

    public function getParentResourceRegistration(): ParentResourceRegistration
    {
        return $this->getResourceRegistry()
            ->get(DockResource::class)
            ->asParent(self::class)
            ->relationship('arrivals');
    }

    public function getPages(): array
    {
        return [
            'index' => ListArrivingVoyages::route('/'),
        ];
    }
}
