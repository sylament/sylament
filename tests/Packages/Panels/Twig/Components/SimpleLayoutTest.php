<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Panels\Selectors;

it("holds the skip link and the user menu with its theme switcher in its strip, and the page in a main at the page's width", function (): void {
    $page = $this->visit('/simple-knobs/card-simple-page');
    $strip = $page->filter(Selectors::SIMPLE_LAYOUT . ' .sy-pa-simple-layout-header');

    expect($page->filter(Selectors::SIMPLE_LAYOUT . ' ' . Selectors::SKIP_LINK)->text())->toBe('Skip to content')
        ->and($strip->filter(Selectors::USER_MENU_TRIGGER))->toHaveCount(1)
        ->and($strip->filter(Selectors::THEME_SWITCHER))->toHaveCount(1)
        ->and($page->filter(Selectors::contentWidth('2xl') . '#sy-pa-main ' . Selectors::SIMPLE_PAGE))->toHaveCount(1);
});

it('renders its content at the large width when handed no width class', function (): void {
    $layout = $this->renderTwigComponent('SylamentPanels:SimpleLayout', content: 'Signed out.')->crawler();

    expect($layout->filter(Selectors::contentWidth('lg'))->text())->toBe('Signed out.');
});
