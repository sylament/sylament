<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Components;

use Sylament\Schemas\Components\Text;
use Sylament\Support\Colors\Color;
use Sylament\Support\Enums\FontFamily;
use Sylament\Support\Enums\FontWeight;
use Sylament\Support\Enums\TextSize;
use Twig\Markup;

use function Sylament\Schemas\Tests\renderToHtml;

it('renders the content inside a text span', function (): void {
    $html = renderToHtml(Text::make('Hello world'));

    expect($html)->toContain('<span')
        ->toContain('sy-sc-text')
        ->toContain('Hello world');
});

it('escapes string content', function (): void {
    $html = renderToHtml(Text::make('<b>hi</b>'));

    expect($html)->toContain('&lt;b&gt;hi&lt;/b&gt;')
        ->not->toContain('<b>hi</b>');
});

it('renders Markup content raw', function (): void {
    $html = renderToHtml(Text::make(new Markup('<b>hi</b>', 'UTF-8')));

    expect($html)->toContain('<b>hi</b>');
});

it('renders the size, weight, and font-family classes', function (): void {
    $html = renderToHtml(
        Text::make('x')
            ->size(TextSize::Large)
            ->weight(FontWeight::Bold)
            ->fontFamily(FontFamily::Mono),
    );

    expect($html)->toContain('sy-size-lg')
        ->toContain('sy-font-bold')
        ->toContain('sy-font-mono');
});

it('renders extra attributes on the span', function (): void {
    $html = renderToHtml(Text::make('x')->extraAttributes(['data-role' => 'label']));

    expect($html)->toContain('data-role="label"');
});

it('renders the color palette classes with the contrast-resolved text shades', function (): void {
    expect(renderToHtml(Text::make('x')->color('primary')))
        ->toContain('class="sy sy-sc-text sy-color sy-color-primary sy-text-color-700 dark:sy-text-color-400"');
});

it('carries an arbitrary palette as inline variables instead of a swap class', function (): void {
    $html = renderToHtml(Text::make('x')->color(Color::Amber));

    expect($html)->toContain('class="sy sy-sc-text sy-color"')
        ->toContain('--sy-color-700: ' . Color::Amber[700])
        ->toContain('--sy-text: var(--sy-color-700); --sy-dark-text: var(--sy-color-400)');
});

it('omits color classes for the default gray', function (): void {
    expect(renderToHtml(Text::make('x')))->not->toContain('sy-color');
});

/**
 * `TextComponent` implements `HasDefaultGrayColor`, so gray asked for by name renders exactly as
 * no color at all — the base shades `text.css` gives `.sy-sc-text`, not a swap class.
 */
it('omits color classes for gray asked for by name', function (): void {
    expect(renderToHtml(Text::make('x')->color('gray')))->not->toContain('sy-color');
});
