<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Twig\Extension;

use Sylament\Support\Icons\IconManager;
use Sylament\Support\Tests\Fixtures\Icons\IconRendererFixture;
use Sylament\Support\Twig\Extension\IconExtension;
use Twig\Markup;

function fakeExtensionIconRenderer(): IconRendererFixture
{
    $renderer = new IconRendererFixture();
    IconManager::setInstance(new IconManager($renderer));

    return $renderer;
}

it('registers the generateIconHtml function', function (): void {
    $functions = new IconExtension()->getFunctions();

    expect($functions)->toHaveCount(1)
        ->and($functions[0]->getName())->toBe('generateIconHtml');
});

it('delegates to the generate_icon_html helper', function (): void {
    fakeExtensionIconRenderer();

    $html = new IconExtension()->generateIconHtml('heroicons:x-mark');

    expect($html)->toBeInstanceOf(Markup::class)
        ->and((string) $html)->toBe('<svg data-icon="heroicons:x-mark"></svg>');
});

it('returns null for a null icon', function (): void {
    fakeExtensionIconRenderer();

    expect(new IconExtension()->generateIconHtml())->toBeNull();
});

it('wraps a plain attributes array', function (): void {
    $renderer = fakeExtensionIconRenderer();

    new IconExtension()->generateIconHtml('heroicons:x-mark', attributes: ['class' => 'custom', 'data-x' => '1']);

    expect($renderer->lastAttributes)->toBe([
        'class' => 'sy-icon sy-size-md custom',
        'data-x' => '1',
    ]);
});

it('normalizes a known size string to its enum case', function (): void {
    $renderer = fakeExtensionIconRenderer();

    new IconExtension()->generateIconHtml('heroicons:x-mark', size: 'lg');

    expect($renderer->lastAttributes)->toBe(['class' => 'sy-icon sy-size-lg']);
});

it('degrades an unknown size string to the medium default', function (): void {
    $renderer = fakeExtensionIconRenderer();

    new IconExtension()->generateIconHtml('heroicons:x-mark', size: 'huge');

    expect($renderer->lastAttributes)->toBe(['class' => 'sy-icon sy-size-md']);
});
