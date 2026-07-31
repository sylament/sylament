<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Twig\Extension;

use RuntimeException;
use Sylament\Support\Twig\Extension\AttributeExtension;

it('registers the attrs, classes and styles functions', function (): void {
    $functions = new AttributeExtension()->getFunctions();

    expect($functions)->toHaveCount(3)
        ->and($functions[0]->getName())->toBe('attrs')
        ->and($functions[1]->getName())->toBe('classes')
        ->and($functions[2]->getName())->toBe('styles');
});

it('attrs renders a single source through the attribute bag', function (): void {
    $html = new AttributeExtension()->renderAttributes([
        'class' => ['sy sy-sc', ['sy-inline' => true, 'sy-off' => false]],
        'id' => 'main',
        'hidden' => false,
    ]);

    expect($html)->toBe('class="sy sy-sc sy-inline" id="main"');
});

it('attrs merges sources with later scalars winning and class tokens appending', function (): void {
    $html = new AttributeExtension()->renderAttributes(
        ['class' => 'sy-grid', 'id' => 'first', 'data-a' => '1'],
        ['class' => ['lg:sy-grid-cols'], 'id' => 'second'],
    );

    expect($html)->toBe('class="sy-grid lg:sy-grid-cols" id="second" data-a="1"');
});

it('attrs skips null, false, and empty-string sources', function (): void {
    $html = new AttributeExtension()->renderAttributes(null, ['id' => 'kept'], false, '');

    expect($html)->toBe('id="kept"');
});

it('attrs rejects non-empty string sources', function (): void {
    new AttributeExtension()->renderAttributes('class="raw"');
})->throws(RuntimeException::class, 'Only empty strings may be passed as string sources to attrs()');

it('attrs escapes attribute values', function (): void {
    $html = new AttributeExtension()->renderAttributes(['title' => 'a "quoted" <value>']);

    expect($html)->toBe('title="a &quot;quoted&quot; &lt;value&gt;"');
});

it('classes resolves strings and conditional maps into a joined class string', function (): void {
    $classes = new AttributeExtension()->getClassTokens('sy sy-sc', ['on' => true, 'off' => false], null);

    expect($classes)->toBe('sy sy-sc on');
});

it('styles joins declaration lists, property maps and raw strings in source order', function (): void {
    $styles = new AttributeExtension()->getStyleDeclarations(
        ['--sy-color-50: oklch(0.9 0.1 90)', '--sy-text: var(--sy-color-700)'],
        ['margin' => '0'],
        'padding: 1rem; gap: 2px',
        null,
    );

    expect($styles)->toBe('--sy-color-50: oklch(0.9 0.1 90); --sy-text: var(--sy-color-700); margin: 0; padding: 1rem; gap: 2px');
});

it('styles keeps a semicolon inside a function call out of the split', function (): void {
    $styles = new AttributeExtension()->getStyleDeclarations('background: url("a;b.png")');

    expect($styles)->toBe('background: url("a;b.png")');
});
