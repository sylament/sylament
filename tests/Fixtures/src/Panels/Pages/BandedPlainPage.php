<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Pages;

use BackedEnum;
use Sylament\Panels\Navigation\NavigationGroup;
use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\Pages\Page;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A plain page whose band holds every shape the band normalises: itself and a grouped sibling
 * page, loose items sorted, tied, hidden and opening in a new tab, a declared group with no
 * label, a declared labelled group, and a loose item joining the sibling's group.
 */
#[AsLiveComponent]
final class BandedPlainPage extends Page
{
    protected static ?string $slug = 'banded';

    protected static ?string $navigationLabel = 'Overview';

    protected static string|BackedEnum|null $navigationIcon = 'support:star';

    protected static ?int $navigationSort = 0;

    public static function getNavigationBadge(): string
    {
        return '5';
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'success';
    }

    public function getSubNavigation(): array
    {
        return [
            ...$this->generateNavigationItems([TopBandedPlainPage::class, self::class]),
            NavigationItem::make('Drafts')->sort(1)->url('/drafts')->openUrlInNewTab(),
            NavigationItem::make('Trash')->hidden()->url('/trash'),
            NavigationGroup::make()->items([NavigationItem::make('Pinned')->sort(1)->url('/pinned')]),
            NavigationGroup::make('Archive')->items([
                NavigationItem::make('Old')->sort(2)->url('/old'),
                NavigationItem::make('Older')->sort(1)->url('/older'),
            ]),
            NavigationItem::make('Monthly')->group('Reports')->sort(0)->url('/monthly'),
        ];
    }
}
