<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Integration\Twig\Extension;

use InvalidArgumentException;
use Sylament\Support\Colors\Color;
use Sylament\Support\Colors\ColorManager;
use Sylament\Support\Colors\Components\BadgeComponent;
use Sylament\Support\Twig\Extension\ColorExtension;
use Twig\Markup;

it('returns the resolved class list for a named colour', function (): void {
    $extension = $this::getContainer()->get(ColorExtension::class);

    expect($extension->colorAttributes(BadgeComponent::class, 'warning'))->toBe([
        'class' => ['sy-color', 'sy-color-warning', 'sy-text-color-700', 'dark:sy-text-color-400'],
        'style' => [],
    ]);
});

it('returns inline styles for an array palette', function (): void {
    $extension = $this::getContainer()->get(ColorExtension::class);

    $attributes = $extension->colorAttributes(BadgeComponent::class, Color::Amber);

    expect($attributes['class'])->toBe(['sy-color'])
        ->and(implode('; ', $attributes['style']))->toContain('--sy-color-50: ')
        ->and(implode('; ', $attributes['style']))->toContain('--sy-text: var(--sy-color-');
});

it('returns nothing for a null colour', function (): void {
    $extension = $this::getContainer()->get(ColorExtension::class);

    expect($extension->colorAttributes(BadgeComponent::class, null))
        ->toBe(['class' => [], 'style' => []]);
});

it('emits an empty style block when nothing is registered', function (): void {
    $extension = $this::getContainer()->get(ColorExtension::class);

    expect((string) $extension->sylamentStyles())->toBe('');
});

it('emits a root override and a swap class for an added palette', function (): void {
    $this::getContainer()->get(ColorManager::class)->register(['brand' => Color::Rose]);

    $rendered = (string) $this::getContainer()->get(ColorExtension::class)->sylamentStyles();

    expect($rendered)->toContain('--sy-brand-500: ' . Color::Rose[500])
        ->and($rendered)->toContain('.sy-color-brand {')
        ->and($rendered)->toContain('--sy-color-500: var(--sy-brand-500);');
});

it('emits a root override but no swap class for a redefined default palette', function (): void {
    $this::getContainer()->get(ColorManager::class)->register(['primary' => Color::Rose]);

    $rendered = (string) $this::getContainer()->get(ColorExtension::class)->sylamentStyles();

    expect($rendered)->toContain('--sy-primary-500: ' . Color::Rose[500])
        ->and($rendered)->not->toContain('.sy-color-primary');
});

it('refuses to emit a shade key that could escape the style element', function (): void {
    $this::getContainer()->get(ColorManager::class)->register(
        fn (): array => ['evil' => ['500;}</style><script>alert(1)</script>' => Color::Rose[500]]],
    );

    $extension = $this::getContainer()->get(ColorExtension::class);

    expect(fn (): Markup => $extension->sylamentStyles())->toThrow(InvalidArgumentException::class);
});

it('strips characters that could escape the style element', function (): void {
    $this::getContainer()->get(ColorManager::class)->register([
        'evil' => [500 => 'oklch(0 0 0);}</style><script>alert(1)</script>'],
    ]);

    $rendered = (string) $this::getContainer()->get(ColorExtension::class)->sylamentStyles();

    expect($rendered)->not->toContain('<script>')
        ->and($rendered)->toContain('--sy-evil-500: ;');
});
