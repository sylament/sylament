<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Panels\Selectors;
use Symfony\Component\DomCrawler\Crawler;

it("hands the logo the panel's brand, its light and dark logos at the panel's height, above the page's heading and subheading", function (): void {
    $header = $this->visit('/simple-knobs/card-simple-page')->filter(Selectors::SIMPLE_HEADER);

    expect($header->filter(Selectors::LOGO)->each(fn (Crawler $logo): array => [$logo->attr('src'), $logo->attr('alt'), $logo->attr('style')]))->toBe([
        ['/images/acme.svg', 'Acme logo', 'height: 2rem'],
        ['/images/acme-dark.svg', 'Acme logo', 'height: 2rem'],
    ])
        ->and($header->filter(Selectors::PAGE_HEADING)->text())->toBe('Welcome back')
        ->and($header->filter('p.sy-pa-simple-header-subheading')->text())->toBe('Sign in to continue.');
});

it('renders the logo, the heading and the subheading each only when it has one', function (): void {
    $headingOnly = $this->renderTwigComponent('SylamentPanels:SimpleHeader', ['logo' => false, 'heading' => 'Reset your password', 'brandName' => 'Acme'])->crawler();
    $logoOnly = $this->renderTwigComponent('SylamentPanels:SimpleHeader', ['brandName' => 'Acme'])->crawler();

    expect($headingOnly->filter(Selectors::PAGE_HEADING)->text())->toBe('Reset your password')
        ->and($headingOnly->filter(Selectors::LOGO))->toHaveCount(0)
        ->and($logoOnly->filter(Selectors::LOGO)->text())->toBe('Acme')
        ->and($logoOnly->filter(Selectors::PAGE_HEADING))->toHaveCount(0)
        ->and($logoOnly->filter('p.sy-pa-simple-header-subheading'))->toHaveCount(0);
});
