<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Components;

use Sylament\Schemas\Components\Icon;
use Sylament\Support\Colors\Color;
use Sylament\Support\Enums\IconSize;

use function Sylament\Schemas\Tests\renderToHtml;

/**
 * Covers the schemas-level `Icon` view component (`Sylament\Schemas\Components\Icon`,
 * the `Filament\Schemas\Components\Icon` port), rendered through the ViewComponent
 * Twig path. The support-level `<twig:SylamentSupport:Icon>` anonymous component is
 * covered separately in IconRenderTest.
 */
it('renders an icon as an svg carrying the schema-component class', function (): void {
    $html = renderToHtml(Icon::make('heroicons:x-mark'));

    expect($html)->toContain('<svg')
        ->toContain('sy-sc-icon')
        ->toContain('sy-icon');
});

it('applies the color palette classes', function (): void {
    $html = renderToHtml(Icon::make('heroicons:x-mark')->color('danger'));

    expect($html)->toContain('class="sy sy-icon sy-size-md sy-sc-icon sy-color sy-color-danger"');
});

it('carries an arbitrary palette as inline variables instead of a swap class', function (): void {
    $html = renderToHtml(Icon::make('heroicons:x-mark')->color(Color::Amber));

    expect($html)->toContain('class="sy sy-icon sy-size-md sy-sc-icon sy-color"')
        ->toContain('--sy-color-500: ' . Color::Amber[500]);
});

it('omits color classes for the default gray', function (): void {
    expect(renderToHtml(Icon::make('heroicons:x-mark')))->not->toContain('sy-color');
});

it('renders the tooltip as the svg title attribute', function (): void {
    expect(renderToHtml(Icon::make('heroicons:x-mark')->tooltip('More info')))
        ->toContain('title="More info"');
});

it('applies an explicit size', function (): void {
    expect(renderToHtml(Icon::make('heroicons:x-mark')->size(IconSize::Large)))
        ->toContain('sy-size-lg');
});
