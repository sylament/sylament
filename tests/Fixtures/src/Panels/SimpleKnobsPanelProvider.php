<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels;

use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Support\Enums\Width;
use Sylament\Tests\Fixtures\Panels\Pages\BareSimplePage;
use Sylament\Tests\Fixtures\Panels\Pages\CardSimplePage;
use Sylament\Tests\Fixtures\Panels\Pages\NotesPage;
use Sylament\Tests\Fixtures\Panels\Pages\TokenWidthSimplePage;
use Sylament\Tests\Fixtures\Panels\Resources\ArticleResource;

/**
 * A panel that sets every knob a simple page reads: a brand with a light and a dark logo at
 * a height of its own, a content width for each shell, the simple one from a closure, and the
 * unsaved-changes alert, over simple pages, an ordinary page and the article resource.
 */
final class SimpleKnobsPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('simple-knobs')
            ->path('simple-knobs')
            ->brandName('Acme')
            ->brandLogo('/images/acme.svg')
            ->darkModeBrandLogo('/images/acme-dark.svg')
            ->brandLogoHeight('2rem')
            ->maxContentWidth(Width::SixExtraLarge)
            ->simplePageMaxContentWidth(static fn (): Width => Width::TwoExtraLarge)
            ->unsavedChangesAlerts()
            ->pages([CardSimplePage::class, BareSimplePage::class, TokenWidthSimplePage::class, NotesPage::class])
            ->resources([ArticleResource::class]);
    }
}
