<?php

declare(strict_types=1);

use Sylament\Support\Icons\IconManager;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('draws the named icon at the medium size', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Icon', ['icon' => 'support:star'])->crawler())
        ->toContainElement(SupportSelectors::ICON . SupportSelectors::MEDIUM, 'Star');
});

it('draws the icon registered for its alias, and its own icon for an alias none registered', function (): void {
    $this::getContainer()->get(IconManager::class)->register(['support.favourite' => 'support:heart']);

    expect($this->renderTwigComponent('SylamentSupport:Icon', ['icon' => 'support:star', 'alias' => 'support.favourite'])->crawler())
        ->toContainElement('svg', 'Heart')
        ->and($this->renderTwigComponent('SylamentSupport:Icon', ['icon' => 'support:star', 'alias' => 'support.missing'])->crawler())
        ->toContainElement('svg', 'Star');
});

it('draws the icon at the size it is given', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Icon', ['icon' => 'support:star', 'size' => 'lg'])->crawler())
        ->toContainElement(SupportSelectors::ICON . SupportSelectors::size('lg'), 'Star');
});

it("keeps the caller's attributes on the icon beside its own classes", function (): void {
    $icon = $this->renderTwigComponent('SylamentSupport:Icon', [
        'icon' => 'support:star',
        'class' => 'favourite',
        'aria-label' => 'Favourite',
        'data-rating' => '5',
    ])->crawler();

    expect($icon)->toContainElement('.sy' . SupportSelectors::ICON . SupportSelectors::MEDIUM . '.favourite[aria-label="Favourite"][data-rating="5"]', 'Star');
});
