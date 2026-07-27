<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components;

use Sylament\Schemas\Components\Text;
use Sylament\Support\Enums\FontFamily;
use Sylament\Support\Enums\FontWeight;
use Sylament\Support\Enums\Size;
use Sylament\Support\Enums\TextSize;
use Twig\Markup;

it('returns the content', function (): void {
    expect(Text::make('Hello')->getContent())->toBe('Hello');
});

it('evaluates a content closure', function (): void {
    expect(Text::make(fn (): string => 'Computed')->getContent())->toBe('Computed');
});

it('passes the text component to a content closure as $component', function (): void {
    $text = Text::make(fn (Text $component): string => $component::class);

    expect($text->getContent())->toBe(Text::class);
});

it('returns a Markup content unchanged', function (): void {
    $markup = new Markup('<b>x</b>', 'UTF-8');

    expect(Text::make($markup)->getContent())->toBe($markup);
});

it('round-trips the color', function (): void {
    expect(Text::make('x')->color('primary')->getColor())->toBe('primary')
        ->and(Text::make('x')->getColor())->toBeNull()
        ->and(Text::make('x')->hasColor())->toBeFalse()
        ->and(Text::make('x')->color('danger')->hasColor())->toBeTrue();
});

it('normalizes a known size string to the TextSize enum', function (): void {
    expect(Text::make('x')->size('sm')->getSize())->toBe(TextSize::Small);
});

it('keeps a TextSize enum size', function (): void {
    expect(Text::make('x')->size(TextSize::Large)->getSize())->toBe(TextSize::Large);
});

it('keeps a Size enum size', function (): void {
    expect(Text::make('x')->size(Size::ExtraLarge)->getSize())->toBe(Size::ExtraLarge);
});

it('keeps an unknown size string verbatim', function (): void {
    expect(Text::make('x')->size('huge')->getSize())->toBe('huge');
});

it('returns null for a blank size', function (): void {
    expect(Text::make('x')->getSize())->toBeNull()
        ->and(Text::make('x')->size('')->getSize())->toBeNull();
});

it('normalizes a known weight string to the FontWeight enum', function (): void {
    expect(Text::make('x')->weight('bold')->getWeight())->toBe(FontWeight::Bold);
});

it('keeps an unknown weight string verbatim', function (): void {
    expect(Text::make('x')->weight('custom')->getWeight())->toBe('custom');
});

it('normalizes a known font-family string to the FontFamily enum', function (): void {
    expect(Text::make('x')->fontFamily('mono')->getFontFamily())->toBe(FontFamily::Mono);
});

it('derives the size, weight, and font-family class fragments', function (): void {
    $text = Text::make('x')
        ->size(TextSize::Large)
        ->weight(FontWeight::Bold)
        ->fontFamily(FontFamily::Serif);

    expect($text->getSizeClass())->toBe('sy-size-lg')
        ->and($text->getWeightClass())->toBe('sy-font-bold')
        ->and($text->getFontFamilyClass())->toBe('sy-font-serif');
});

it('leaves the class fragments null when unset', function (): void {
    $text = Text::make('x');

    expect($text->getSizeClass())->toBeNull()
        ->and($text->getWeightClass())->toBeNull()
        ->and($text->getFontFamilyClass())->toBeNull();
});

it('emits custom size and weight strings as class fragments verbatim', function (): void {
    $text = Text::make('x')->size('huge')->weight('custom-weight');

    expect($text->getSizeClass())->toBe('huge')
        ->and($text->getWeightClass())->toBe('custom-weight');
});
