<?php

declare(strict_types=1);

use Sylament\Support\Icons\IconManager;

it('resolves an alias to the icon registered for it last, keeping the aliases a later registration leaves out', function (): void {
    $icons = $this::getContainer()->get(IconManager::class);
    $icons->register(['support.favourite' => 'support:heart', 'support.rated' => 'support:heart']);
    $icons->register(['support.rated' => 'support:star-lg']);

    expect($this->renderTwigComponent('SylamentSupport:Icon', ['icon' => 'support:star', 'alias' => 'support.rated'])->crawler())
        ->toContainElement('svg', 'Star, large')
        ->and($this->renderTwigComponent('SylamentSupport:Icon', ['icon' => 'support:star', 'alias' => 'support.favourite'])->crawler())
        ->toContainElement('svg', 'Heart');
});

it('refuses to render an icon without a renderer, naming the UX Icons bundle', function (): void {
    expect(fn (): string => new IconManager()->renderIcon('support:star'))
        ->toThrow(LogicException::class, 'Icon rendering requires symfony/ux-icons; register "Symfony\UX\Icons\UXIconsBundle" in your kernel.');
});
