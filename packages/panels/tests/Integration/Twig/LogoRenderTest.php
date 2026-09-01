<?php

declare(strict_types=1);

use Symfony\UX\TwigComponent\Test\InteractsWithTwigComponents;
use Twig\Markup;

uses(InteractsWithTwigComponents::class);

it('renders a string brand logo as an image with the brand alt text', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:Logo', [
        'brandName' => 'Demo Admin',
        'brandLogo' => '/images/logo.svg',
    ]);

    expect($html)
        ->toContain('<img')
        ->toContain('src="/images/logo.svg"')
        ->toContain('alt="Demo Admin logo"')
        ->toContain('class="sy sy-pa-logo"')
        ->toContain('style="height: 1.5rem"')
        ->not->toContain('Demo Admin</span>');
});

it('applies a custom brand logo height', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:Logo', [
        'brandLogo' => '/images/logo.svg',
        'brandLogoHeight' => '2rem',
    ]);

    expect($html)->toContain('style="height: 2rem"');
});

it('renders a Markup brand logo raw inside a wrapper', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:Logo', [
        'brandLogo' => new Markup('<svg id="brand-svg"></svg>', 'UTF-8'),
    ]);

    expect($html)
        ->toContain('<svg id="brand-svg"></svg>')
        ->toMatch('/<div class="sy sy-pa-logo" style="height: 1\.5rem">\s*<svg id="brand-svg"><\/svg>\s*<\/div>/')
        ->not->toContain('<img');
});

it('renders both logos with visibility classes when a dark mode logo is set', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:Logo', [
        'brandName' => 'Demo Admin',
        'brandLogo' => '/images/logo.svg',
        'darkModeBrandLogo' => '/images/logo-dark.svg',
    ]);

    expect($html)
        ->toContain('src="/images/logo.svg"')
        ->toContain('src="/images/logo-dark.svg"')
        ->toContain('class="sy sy-pa-logo sy-pa-logo-light"')
        ->toContain('class="sy sy-pa-logo sy-pa-logo-dark"')
        ->toMatch('/sy-pa-logo-light.+sy-pa-logo-dark/s');
});

it('leaves the visibility classes off without a dark mode logo', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:Logo', [
        'brandLogo' => '/images/logo.svg',
    ]);

    expect($html)
        ->not->toContain('sy-pa-logo-light')
        ->not->toContain('sy-pa-logo-dark');
});

it('falls back to the brand name text without a height style', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:Logo', [
        'brandName' => 'Demo Admin',
    ]);

    expect($html)
        ->toContain('<span class="sy sy-pa-logo">Demo Admin</span>')
        ->not->toContain('style=');
});

it('pairs a dark mode logo with the brand name text when no light logo is set', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:Logo', [
        'brandName' => 'Demo Admin',
        'darkModeBrandLogo' => '/images/logo-dark.svg',
    ]);

    expect($html)
        ->toContain('<span class="sy sy-pa-logo sy-pa-logo-light">Demo Admin</span>')
        ->toContain('src="/images/logo-dark.svg"');
});

it('renders nothing with no logo and no brand name', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:Logo');

    expect(mb_trim($html))->toBe('');
});

it('threads the brand logo through the layout into the topbar and sidebar', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:Layout', [
        'navigation' => [],
        'brandName' => 'Demo Admin',
        'brandLogo' => '/images/logo.svg',
        'darkModeBrandLogo' => '/images/logo-dark.svg',
        'brandLogoHeight' => '2rem',
    ], content: '<p>Body</p>');

    expect(mb_substr_count($html, 'src="/images/logo.svg"'))->toBe(2)
        ->and(mb_substr_count($html, 'src="/images/logo-dark.svg"'))->toBe(2)
        ->and(mb_substr_count($html, 'style="height: 2rem"'))->toBe(4);
});
