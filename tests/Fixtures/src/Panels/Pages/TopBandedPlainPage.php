<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Pages;

use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\Pages\Enums\SubNavigationPosition;
use Sylament\Panels\Pages\Page;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A plain page in the `Reports` navigation group that draws its band on top: an ungrouped
 * sibling page and the `Reports` group holding itself and a loose item.
 */
#[AsLiveComponent]
final class TopBandedPlainPage extends Page
{
    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $slug = 'top-banded';

    protected static ?string $navigationLabel = 'Quarterly';

    protected static ?string $navigationGroup = 'Reports';

    public function getSubNavigation(): array
    {
        return [
            ...$this->generateNavigationItems([BandedPlainPage::class, self::class]),
            NavigationItem::make('Monthly')->group('Reports')->url('/monthly'),
        ];
    }
}
