<?php

declare(strict_types=1);

use Symfony\UX\TwigComponent\Test\InteractsWithTwigComponents;

uses(InteractsWithTwigComponents::class);

it('renders the centred card shell around its content', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:SimpleLayout', content: '<p>Body</p>');

    expect($html)
        ->toContain('class="sy sy-pa-body sy-pa-simple-layout"')
        ->toContain('href="#sy-pa-main"')
        ->toContain('<main id="sy-pa-main" class="sy-pa-simple-main sy-width-lg">')
        ->toContain('<p>Body</p>')
        ->toMatch('/<div class="sy-pa-simple-layout-header">\s*.*sy-pa-user-menu.*<div class="sy-pa-simple-main-ctn">\s*<main id="sy-pa-main"/s');
});

it('drops the skip link and the header strip without a topbar', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:SimpleLayout', [
        'hasTopbar' => false,
    ], content: '<p>Body</p>');

    expect($html)
        ->not->toContain('sy-pa-skip-link')
        ->not->toContain('sy-pa-simple-layout-header')
        ->not->toContain('sy-pa-user-menu')
        ->toContain('<p>Body</p>');
});

it('carries the width class the document resolved on the simple main element', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:SimpleLayout', [
        'maxContentWidthClass' => 'sy-width-md',
    ], content: '<p>Body</p>');

    expect($html)->toContain('<main id="sy-pa-main" class="sy-pa-simple-main sy-width-md">');
});

it('renders the brand name as the simple header logo', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:SimpleHeader', [
        'brandName' => 'Demo Admin',
    ]);

    expect($html)
        ->toContain('<header class="sy sy-pa-simple-header">')
        ->toContain('<span class="sy sy-pa-logo">Demo Admin</span>');
});

it('renders a brand logo path as an image in the simple header', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:SimpleHeader', [
        'brandName' => 'Demo Admin',
        'brandLogo' => 'images/logo.svg',
        'brandLogoHeight' => '2rem',
    ]);

    expect($html)
        ->toContain('src="images/logo.svg"')
        ->toContain('alt="Demo Admin logo"')
        ->toContain('height: 2rem');
});

it('drops the logo when the page hides it', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:SimpleHeader', [
        'heading' => 'Sign in',
        'brandName' => 'Demo Admin',
        'logo' => false,
    ]);

    expect($html)
        ->not->toContain('sy-pa-logo')
        ->toContain('Sign in');
});

it('renders the heading and subheading only when they are filled', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:SimpleHeader', [
        'heading' => 'Sign in',
        'subheading' => 'to continue',
    ]);

    expect($html)
        ->toContain('<h1 class="sy-pa-simple-header-heading">Sign in</h1>')
        ->toContain('<p class="sy-pa-simple-header-subheading">to continue</p>');

    expect((string) $this->renderTwigComponent('SylamentPanels:SimpleHeader'))
        ->not->toContain('sy-pa-simple-header-heading')
        ->not->toContain('sy-pa-simple-header-subheading');
});
