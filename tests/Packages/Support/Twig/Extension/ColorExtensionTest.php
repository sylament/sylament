<?php

declare(strict_types=1);

use Sylament\Support\Colors\Color;
use Sylament\Support\Colors\ColorManager;
use Sylament\Support\Colors\Components\BadgeComponent;
use Sylament\Support\Twig\Extension\ColorExtension;
use Symfony\Component\DomCrawler\Crawler;

it('emits nothing when nothing is registered over the shipped palettes', function (): void {
    expect($this::getContainer()->get('twig')->createTemplate('{{ sylamentStyles() }}')->render())->toBe('');
});

it('emits a :root palette and a swap class for a newly named palette', function (): void {
    $this::getContainer()->get(ColorManager::class)->register(['brand' => Color::Teal]);

    $styles = new Crawler($this::getContainer()->get('twig')->createTemplate('{{ sylamentStyles() }}')->render())->filter('style')->text();

    expect($styles)->toContain(
        ':root {',
        '--sy-brand-500: ' . Color::Teal[500] . ';',
        '.sy-color-brand {',
        '--sy-color-500: var(--sy-brand-500);',
    );
});

it('emits a :root override and no swap class for a redefined default, and nothing for an unchanged one', function (): void {
    $this::getContainer()->get(ColorManager::class)->register(['primary' => Color::Rose, 'warning' => Color::Amber]);

    $styles = new Crawler($this::getContainer()->get('twig')->createTemplate('{{ sylamentStyles() }}')->render())->filter('style')->text();

    expect($styles)->toContain('--sy-primary-500: ' . Color::Rose[500] . ';')
        ->not->toContain('.sy-color-primary')
        ->not->toContain('--sy-warning-');
});

it('strips a palette name to name characters and drops a value outside CSS colour syntax', function (): void {
    $this::getContainer()->get(ColorManager::class)->register([
        'bra"nd<' => array_replace(Color::Teal, [500 => 'oklch(0.5 0.1 20); } body { color: red']),
    ]);

    $styles = new Crawler($this::getContainer()->get('twig')->createTemplate('{{ sylamentStyles() }}')->render())->filter('style')->text();

    expect($styles)->toContain('.sy-color-brand {', '--sy-brand-400: ' . Color::Teal[400] . ';', '--sy-brand-500: ;')
        ->not->toContain('body')
        ->not->toContain('"')
        ->not->toContain('<');
});

it('gives a blank colour, an empty palette included, no classes and no styles', function (string|array|null $color): void {
    expect($this::getContainer()->get(ColorExtension::class)->colorAttributes(BadgeComponent::class, $color))->toBe(['class' => [], 'style' => []]);
})->with([
    'no colour' => [null],
    'an empty palette' => [[]],
]);
