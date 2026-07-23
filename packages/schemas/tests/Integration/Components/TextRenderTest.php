<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Components;

use Sylament\Schemas\Components\Text;
use Sylament\Support\Enum\FontFamily;
use Sylament\Support\Enum\FontWeight;
use Sylament\Support\Enum\TextSize;
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
