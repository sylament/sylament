<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('paints an outlined button in its text shades alone and a filled one with its backgrounds', function (): void {
    $outlined = $this->renderTwigComponent('SylamentSupport:Button', ['color' => 'primary', 'outlined' => true], content: 'Save')->crawler();
    $filled = $this->renderTwigComponent('SylamentSupport:Button', ['color' => 'primary'], content: 'Save')->crawler();

    expect($outlined)
        ->toContainElement(SupportSelectors::BUTTON . '.sy-outlined' . SupportSelectors::color('primary') . '[class~="dark:sy-text-color-500"]', 'Save')
        ->not->toContainElement('[class*="sy-bg-color-"]')
        ->and($filled)
        ->toContainElement(SupportSelectors::BUTTON . SupportSelectors::color('primary') . ':not(.sy-outlined)[class~="hover:sy-bg-color-300"]', 'Save');
});
