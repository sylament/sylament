<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration;

use SimpleXMLElement;
use Sylament\Support\Twig\Extension\IconExtension;
use Throwable;
use UnexpectedValueException;

use function dirname;

/**
 * The three glyphs the M3 · 9 sweep added to the fixture set are read straight off disk here,
 * not only through the icon extension: the fixture kernel caches compiled icons under
 * `packages/forms/var/cache/test/pools`, so a deleted or corrupted glyph keeps rendering on a
 * machine that has run the suite once, and a render-only assertion would tell that machine
 * everything is fine. The disk read is what makes the guard fail wherever it is run.
 */
function fixtureIconSvg(string $icon): string
{
    [$set, $name] = explode(':', $icon, 2);

    $path = dirname(__DIR__) . "/Fixtures/assets/icons/{$set}/{$name}.svg";

    $svg = @file_get_contents($path);

    if ($svg === false) {
        throw new UnexpectedValueException("No fixture glyph at {$path} — the icon set has lost a file the sweep's render tests need.");
    }

    return $svg;
}

it('ships the glyphs the sweep added to the fixture icon set', function (string $icon): void {
    $svg = fixtureIconSvg($icon);

    try {
        $element = new SimpleXMLElement($svg);
    } catch (Throwable $throwable) {
        throw new UnexpectedValueException("The fixture glyph \"{$icon}\" is not well-formed XML: {$throwable->getMessage()}", $throwable->getCode(), previous: $throwable);
    }

    expect($element->getName())->toBe('svg');
})->with([
    'heroicons:eye-slash-20-solid',
    'heroicons:clipboard-document-list-20-solid',
    'heroicons:x-mark-16-solid',
]);

it('renders the glyphs the sweep added to the fixture icon set', function (string $icon): void {
    $extension = $this::getContainer()->get('sylament_support.icon_extension');
    expect($extension)->toBeInstanceOf(IconExtension::class);

    expect((string) $extension->generateIconHtml($icon))->toContain('<svg');
})->with([
    'heroicons:eye-slash-20-solid',
    'heroicons:clipboard-document-list-20-solid',
    'heroicons:x-mark-16-solid',
]);
