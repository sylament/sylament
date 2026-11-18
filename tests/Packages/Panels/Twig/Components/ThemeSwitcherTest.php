<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Panels\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;

it('offers a button per theme, each picking its theme and closing the menu it sits in, none pressed until the theme controller says so', function (): void {
    $switcher = $this->renderTwigComponent('SylamentPanels:ThemeSwitcher')->crawler()->filter(Selectors::THEME_SWITCHER . '[data-controller="sylament--panels--theme"]');

    expect($switcher->filter('button')->each(fn (Crawler $button): array => [
        $button->attr('data-theme'),
        $button->attr('aria-label'),
        $button->attr('aria-pressed'),
        $button->matches(SupportSelectors::ACTIVE),
        $button->filter('svg')->text(),
        $button->attr('data-action'),
        $button->attr('data-sylament--panels--theme-target'),
    ]))->toBe([
        ['light', 'Enable light theme', 'false', false, 'sun-20-solid', 'sylament--panels--theme#select sylament--support--dropdown#close', 'button'],
        ['dark', 'Enable dark theme', 'false', false, 'moon-20-solid', 'sylament--panels--theme#select sylament--support--dropdown#close', 'button'],
        ['system', 'Enable system theme', 'false', false, 'computer-desktop-20-solid', 'sylament--panels--theme#select sylament--support--dropdown#close', 'button'],
    ]);
});
