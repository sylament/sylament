<?php

declare(strict_types=1);

use Sylament\Panels\Pages\Page;
use Sylament\Panels\Pages\SimplePage;
use Sylament\Support\Enums\Width;

final class PanelWidthSimplePage extends SimplePage
{
    protected Width|string|null $maxContentWidth = Width::TwoExtraLarge;
}

final class CardWidthSimplePage extends SimplePage
{
    protected Width|string|null $maxContentWidth = Width::TwoExtraLarge;

    protected Width|string|null $maxWidth = Width::Small;
}

final class TopbarlessLayoutSimplePage extends SimplePage
{
    protected bool $hasTopbar = false;
}

it('renders in the centred-card shell rather than the panel one', function (): void {
    $page = new class extends SimplePage {};

    expect($page)->toBeInstanceOf(Page::class)
        ->and($page->getLayout())->toBe('SylamentPanels:SimpleLayout')
        ->and(SimplePage::LAYOUT)->toBe('SylamentPanels:SimpleLayout')
        ->and($page->getTemplate())->toBe('@SylamentPanels/partials/SimplePage/index.html.twig');
});

it('stays out of the navigation and keeps its logo', function (): void {
    expect(SimplePage::shouldRegisterNavigation())->toBeFalse()
        ->and(new class extends SimplePage {}->hasLogo())->toBeTrue();
});

it('hands the layout the topbar flag and the card width', function (): void {
    expect(new class extends SimplePage {}->getLayoutData())->toBe(['hasTopbar' => true, 'maxContentWidth' => null])
        ->and(new TopbarlessLayoutSimplePage()->getLayoutData())->toBe(['hasTopbar' => false, 'maxContentWidth' => null]);
});

it('narrows the layout width to the card width when the page names one', function (): void {
    // The card width is the narrowing: a simple page naming only the page-wide
    // one still hands that through, so the fallback is observable.
    expect(new PanelWidthSimplePage()->getLayoutData()['maxContentWidth'])->toBe(Width::TwoExtraLarge)
        ->and(new CardWidthSimplePage()->getLayoutData()['maxContentWidth'])->toBe(Width::Small);
});
