<?php

declare(strict_types=1);

use Sylament\Support\Colors\Color;

it('converts a hex, rgb() or bare channel colour to oklch and leaves an oklch one as it is', function (string $color, string $oklch): void {
    expect(Color::convertToOklch($color))->toBe($oklch);
})->with([
    'hex' => ['#ff0000', 'oklch(0.628 0.258 29.234)'],
    'rgb()' => ['rgb(255, 0, 0)', 'oklch(0.628 0.258 29.234)'],
    'bare channels' => ['255, 0, 0', 'oklch(0.628 0.258 29.234)'],
    'oklch' => ['oklch(0.5 0.1 20)', 'oklch(0.5 0.1 20)'],
]);

it('converts a hex or oklch colour to rgb(), clamped to the sRGB gamut, and leaves an rgb() one as it is', function (string $color, string $rgb): void {
    expect(Color::convertToRgb($color))->toBe($rgb);
})->with([
    'hex' => ['#ff0000', 'rgb(255, 0, 0)'],
    'oklch' => ['oklch(0.628 0.258 29.234)', 'rgb(255, 0, 0)'],
    'oklch outside sRGB' => ['oklch(0.9 0.4 140)', 'rgb(0, 255, 0)'],
    'rgb()' => ['rgb(1, 2, 3)', 'rgb(1, 2, 3)'],
]);

it('converts an oklch colour to hex and lowercases a hex one', function (): void {
    expect(Color::convertToHex('oklch(0.628 0.258 29.234)'))->toBe('#ff0000')
        ->and(Color::convertToHex('#FF0000'))->toBe('#ff0000');
});

it('measures contrast between two colours', function (string $a, string $b, float $ratio): void {
    expect(Color::calculateContrastRatio($a, $b))->toEqualWithDelta($ratio, 0.01);
})->with([
    'white on black' => ['#ffffff', '#000000', 21.0],
    'a colour on itself' => ['#3b82f6', '#3b82f6', 1.0],
    'an oklch colour on a hex one, by the 2.2 power curve' => [Color::Red[500], '#ffffff', 3.851],
]);

it('judges text accessible from 4.5:1 and a non-text element from 3:1', function (): void {
    expect(Color::isTextContrastRatioAccessible(Color::Zinc[500], Color::Zinc[50]))->toBeTrue()
        ->and(Color::isTextContrastRatioAccessible(Color::Red[500], '#ffffff'))->toBeFalse()
        ->and(Color::isNonTextContrastRatioAccessible(Color::Red[500], '#ffffff'))->toBeTrue()
        ->and(Color::isNonTextContrastRatioAccessible(Color::Amber[500], '#ffffff'))->toBeFalse();
});

it('finds the first shade that meets the ratio against a surface, within the bounds, in the direction asked', function (array $arguments, ?int $shade): void {
    expect(Color::findShade(Color::Zinc, ...$arguments))->toBe($shade);
})->with([
    'lightest first' => [['surface' => '#ffffff'], 500],
    'darkest first' => [['surface' => '#ffffff', 'shouldStartFromDarkest' => true], 950],
    'from a minimum shade' => [['surface' => '#ffffff', 'minShade' => 700], 700],
    'up to a maximum shade' => [['surface' => '#000000', 'maxShade' => 300, 'shouldStartFromDarkest' => true], 300],
    'no shade meets the ratio' => [['surface' => '#ffffff', 'minRatio' => 30.0], null],
]);

it('generates an eleven-shade palette in the source hue, without chroma for a gray', function (string $color, string $shade500): void {
    $palette = Color::generatePalette($color);

    expect(array_keys($palette))->toBe([50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950])
        ->and($palette[500])->toBe($shade500);
})->with([
    'a chromatic colour' => ['#ff0000', 'oklch(0.68270588235294 0.17009090909091 29.234)'],
    'a gray' => ['#808080', 'oklch(0.68270588235294 0 89.876)'],
]);

it('generates the same palette through its hex() and rgb() aliases', function (): void {
    expect(Color::hex('#ff0000'))->toBe(Color::generatePalette('#ff0000'))
        ->and(Color::rgb('rgb(255, 0, 0)'))->toBe(Color::generatePalette('#ff0000'));
});

it('calls a colour light from oklch lightness 0.65', function (string $color, bool $isLight): void {
    expect(Color::isLight($color))->toBe($isLight);
})->with([
    'at the threshold' => ['oklch(0.65 0 0)', true],
    'just below it' => ['oklch(0.649 0 0)', false],
    'a hex colour' => ['#ffffff', true],
]);

it('names the 26 catalog palettes in lowercase, each on the eleven-shade grid', function (): void {
    $palettes = Color::all();

    expect($palettes)->toHaveCount(26)
        ->and($palettes['rose'])->toBe(Color::Rose)
        ->and(array_keys($palettes))->each->toMatch('/^[a-z]+$/')
        ->and(array_map(array_keys(...), $palettes))->each->toBe([50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950]);
});

it('refuses a colour it cannot parse, naming it', function (Closure $convert, string $color): void {
    expect(fn (): mixed => $convert($color))->toThrow(InvalidArgumentException::class, "Unable to parse the color \"{$color}\"");
})->with([
    'a bad hex' => [Color::convertToOklch(...), '#zzzzzz'],
    'a bad oklch' => [Color::convertToRgb(...), 'oklch(bright)'],
    'a bad rgb()' => [Color::convertToOklch(...), 'rgb(red)'],
]);
