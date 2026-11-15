<?php

declare(strict_types=1);

use Sylament\Support\Enums\IconSize;
use Sylament\Support\Icons\IconManager;
use Sylament\Tests\Fixtures\Support\Icons\Icon;
use Sylament\Tests\Fixtures\Support\Icons\SizedIcon;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;
use Twig\Markup;

it('renders nothing for a blank icon, and the icon once one is given', function (): void {
    $template = $this::getContainer()->get('twig')->createTemplate('<div>{{ generateIconHtml(icon) }}</div>');

    expect(new Crawler($template->render(['icon' => null]))->filter('div')->html())->toBe('')
        ->and(new Crawler($template->render(['icon' => ' ']))->filter('div')->html())->toBe('')
        ->and(new Crawler($template->render(['icon' => 'support:star'])))->toContainElement('div svg', 'Star');
});

it('wraps a Markup icon in a medium icon span', function (): void {
    $html = $this::getContainer()->get('twig')->createTemplate('{{ generateIconHtml(icon) }}')->render([
        'icon' => new Markup('<svg><title>Inline</title></svg>', 'UTF-8'),
    ]);

    expect(new Crawler($html))->toContainElement('span.sy-icon' . SupportSelectors::MEDIUM . ' svg', 'Inline');
});

it('renders an image path as a decorative image unless the caller names it', function (): void {
    $template = $this::getContainer()->get('twig')->createTemplate('{{ generateIconHtml("/images/logo.png", attributes: attributes) }}');

    expect(new Crawler($template->render(['attributes' => null]))->filter('img.sy-icon[src="/images/logo.png"]')->attr('alt'))->toBe('')
        ->and(new Crawler($template->render(['attributes' => ['alt' => 'Logo']]))->filter('img')->attr('alt'))->toBe('Logo')
        ->and(new Crawler($template->render(['attributes' => ['aria-label' => 'Logo']]))->filter('img[aria-label="Logo"]')->attr('alt'))->toBeNull();
});

it('draws a named icon at the medium size unless sized, an unknown size drawing it medium', function (): void {
    $template = $this::getContainer()->get('twig')->createTemplate('{{ generateIconHtml("support:star", size: size) }}');

    expect(new Crawler($template->render(['size' => null])))->toContainElement('svg.sy-icon' . SupportSelectors::MEDIUM, 'Star')
        ->and(new Crawler($template->render(['size' => IconSize::Large])))->toContainElement('svg.sy-icon.sy-size-lg', 'Star')
        ->and(new Crawler($template->render(['size' => 'xs'])))->toContainElement('svg.sy-icon.sy-size-xs', 'Star')
        ->and(new Crawler($template->render(['size' => 'huge'])))->toContainElement('svg.sy-icon' . SupportSelectors::MEDIUM, 'Star');
});

it('draws the icon an alias resolves to over the given one, and the given one for an unregistered alias', function (): void {
    $this::getContainer()->get(IconManager::class)->register(['support.favourite' => 'support:heart']);
    $template = $this::getContainer()->get('twig')->createTemplate('{{ generateIconHtml("support:star", alias) }}');

    expect(new Crawler($template->render(['alias' => ['support.missing', 'support.favourite']])))->toContainElement('svg', 'Heart')
        ->and(new Crawler($template->render(['alias' => 'support.missing'])))->toContainElement('svg', 'Star');
});

it('draws an enum by its value and a scalable icon by its name for the size', function (): void {
    $template = $this::getContainer()->get('twig')->createTemplate('{{ generateIconHtml(icon, size: "lg") }}');

    expect(new Crawler($template->render(['icon' => Icon::Star])))->toContainElement('svg', 'Star')
        ->and(new Crawler($template->render(['icon' => SizedIcon::Star])))->toContainElement('svg', 'Star, large');
});
