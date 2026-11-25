<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Panels\Selectors;
use Symfony\Component\DomCrawler\Crawler;
use Twig\Markup;

it('renders a markup logo as it is, at the default height', function (): void {
    $logo = $this->renderTwigComponent('SylamentPanels:Logo', ['brandName' => 'Acme', 'brandLogo' => new Markup('<svg><title>Acme mark</title></svg>', 'UTF-8')])->crawler()->filter(Selectors::LOGO);

    expect([$logo->nodeName(), $logo->filter('svg')->text(), $logo->attr('style')])->toBe(['div', 'Acme mark', 'height: 1.5rem']);
});

it('renders a logo path as an image named after the brand', function (): void {
    $logo = $this->renderTwigComponent('SylamentPanels:Logo', ['brandName' => 'Acme', 'brandLogo' => '/brand/acme.svg'])->crawler()->filter(Selectors::LOGO);

    expect([$logo->nodeName(), $logo->attr('src'), $logo->attr('alt'), $logo->attr('style')])->toBe(['img', '/brand/acme.svg', 'Acme logo', 'height: 1.5rem']);
});

it('names a logo image after the text of a markup brand name', function (): void {
    $logo = $this->renderTwigComponent('SylamentPanels:Logo', ['brandName' => new Markup('<b>Tom</b> &amp; Jerry', 'UTF-8'), 'brandLogo' => '/brand/acme.svg'])->crawler()->filter(Selectors::LOGO);

    expect($logo->attr('alt'))->toBe('Tom & Jerry logo');
});

it('falls back to the brand name without a logo, and to nothing without a name either', function (): void {
    $named = $this->renderTwigComponent('SylamentPanels:Logo', ['brandName' => 'Acme'])->crawler()->filter(Selectors::LOGO);

    expect([$named->nodeName(), $named->text()])->toBe(['span', 'Acme'])
        ->and($this->renderTwigComponent('SylamentPanels:Logo')->crawler()->filter(Selectors::LOGO)->count())->toBe(0);
});

it('renders a light and a dark rendition at the height set when a dark-mode logo is set', function (): void {
    $logos = $this->renderTwigComponent('SylamentPanels:Logo', ['brandName' => 'Acme', 'brandLogo' => '/brand/acme.svg', 'darkModeBrandLogo' => '/brand/acme-dark.svg', 'brandLogoHeight' => '2rem'])->crawler();

    expect($logos->filter(Selectors::LOGO)->each(fn (Crawler $logo): array => [$logo->attr('src'), $logo->matches(Selectors::LOGO_LIGHT), $logo->matches(Selectors::LOGO_DARK), $logo->attr('style')]))
        ->toBe([['/brand/acme.svg', true, false, 'height: 2rem'], ['/brand/acme-dark.svg', false, true, 'height: 2rem']]);
});
