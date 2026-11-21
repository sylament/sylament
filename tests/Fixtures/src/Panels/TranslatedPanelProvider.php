<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels;

use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Tests\Fixtures\Panels\Pages\TranslatedDeskPage;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * A panel whose brand name and one registered navigation group are catalogue messages, over a page
 * whose navigation entry and breadcrumbs are too, and a later item in an unregistered group sorted
 * ahead of it, so the registered group's place in the order shows it was matched.
 */
final class TranslatedPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('translated')
            ->path('translated')
            ->brandName(new TranslatableMessage('brand', [], 'fixtures-panels'))
            ->pages([TranslatedDeskPage::class])
            ->navigationGroups([new TranslatableMessage('page.group', [], 'fixtures-panels')])
            ->navigationItems([NavigationItem::make('Glossary')->group('Misc')->sort(-5)->url('/glossary')]);
    }
}
