<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Panels\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('opens a dropdown at the bottom end from a user icon button, holding the theme switcher', function (): void {
    $menu = $this->renderTwigComponent('SylamentPanels:UserMenu')->crawler()->filter(SupportSelectors::DROPDOWN . SupportSelectors::dropdownValue('placement', 'bottom-end'));

    expect($menu->filter(SupportSelectors::DROPDOWN_TRIGGER . ' ' . Selectors::USER_MENU_TRIGGER . ' svg')->text())->toBe('user-circle')
        ->and($menu->filter(SupportSelectors::DROPDOWN_PANEL . ' ' . Selectors::THEME_SWITCHER)->count())->toBe(1);
});
