<?php

declare(strict_types=1);

use Sylament\Support\Colors\Color;
use Sylament\Support\Colors\ColorManager;
use Sylament\Support\Colors\Components\BadgeComponent;
use Sylament\Support\Colors\Components\ButtonComponent;
use Sylament\Support\Colors\Components\ModalComponent\IconComponent;
use Sylament\Support\Colors\Components\OutlinedButtonComponent;
use Sylament\Support\DependencyInjection\StaticInstanceRegistry;
use Sylament\Tests\Fixtures\Support\Colors\FixedColorPolicy;
use Sylament\Tests\Fixtures\Support\Colors\OffSlotColorPolicy;

it('resolves the six default colours with nothing registered', function (): void {
    expect($this::getContainer()->get(ColorManager::class)->getColors())->toBe([
        'danger' => Color::Red,
        'gray' => Color::Zinc,
        'info' => Color::Indigo,
        'primary' => Color::Amber,
        'success' => Color::Green,
        'warning' => Color::Amber,
    ]);
});

it('merges registrations in order, generating a palette from a colour and converting a shade map to oklch', function (): void {
    $colors = $this::getContainer()->get(ColorManager::class)
        ->register(['primary' => Color::Rose, 'brand' => Color::Teal])
        ->register(fn (): array => ['brand' => '#ff0000', 'ink' => array_fill_keys(ColorManager::SHADES, '#000000')]);

    expect($colors->getColor('primary'))->toBe(Color::Rose)
        ->and($colors->getColor('brand'))->toBe(Color::generatePalette('#ff0000'))
        ->and($colors->getColor('ink'))->toBe(array_fill_keys(ColorManager::SHADES, 'oklch(0 0 0)'))
        ->and($colors->getColor('missing'))->toBeNull();
});

it('ignores a registration made after the palettes were first read', function (): void {
    $colors = $this::getContainer()->get(ColorManager::class);

    expect($colors->getColor('primary'))->toBe(Color::Amber);

    $colors->register(['primary' => Color::Rose, 'brand' => Color::Teal]);

    expect($colors->getColor('primary'))->toBe(Color::Amber)
        ->and($colors->getColor('brand'))->toBeNull();
});

it('refuses a shade map that does not fill the shade grid exactly, registered or inline, when it is first read', function (Closure $read, string $message): void {
    expect(fn (): mixed => $read($this::getContainer()->get(ColorManager::class)))->toThrow(InvalidArgumentException::class, $message);
})->with([
    'a registered palette missing a shade' => [
        fn (ColorManager $colors): array => $colors->register(['brand' => array_diff_key(Color::Teal, [950 => true])])->getColors(),
        'The color palette [brand] is missing the shades [950].',
    ],
    'a registered palette with a shade off the grid' => [
        fn (ColorManager $colors): array => $colors->register(['brand' => Color::Teal + [975 => Color::Teal[950]]])->getColors(),
        'The color palette [brand] carries the shade [975], which is outside the shade grid',
    ],
    'an inline palette missing a shade' => [
        fn (ColorManager $colors): array => $colors->getComponentCustomStyles(BadgeComponent::class, array_diff_key(Color::Teal, [50 => true])),
        'The color palette passed to [' . BadgeComponent::class . '] is missing the shades [50].',
    ],
    'an inline palette with a shade off the grid' => [
        fn (ColorManager $colors): array => $colors->getComponentCustomStyles(BadgeComponent::class, Color::Teal + [975 => Color::Teal[950]]),
        'The color palette passed to [' . BadgeComponent::class . '] carries the shade [975], which is outside the shade grid',
    ],
]);

it('emits the colour classes and one shade class per slot its policy resolves for a known colour', function (): void {
    expect($this::getContainer()->get(ColorManager::class)->getComponentClasses(new FixedColorPolicy(), 'primary'))->toBe([
        'sy-color',
        'sy-color-primary',
        'sy-bg-color-600',
        'hover:sy-bg-color-500',
        'dark:sy-bg-color-400',
        'dark:hover:sy-bg-color-300',
        'sy-text-color-0',
        'hover:sy-text-color-50',
        'dark:sy-text-color-900',
        'dark:hover:sy-text-color-950',
    ]);
});

it('emits nothing for a blank colour or for gray on a policy that paints gray by default', function (): void {
    $colors = $this::getContainer()->get(ColorManager::class);

    expect($colors->getComponentClasses(BadgeComponent::class, 'gray'))->toBe([])
        ->and($colors->getComponentClasses(BadgeComponent::class, ''))->toBe([])
        ->and($colors->getComponentClasses(BadgeComponent::class, null))->toBe([])
        ->and($colors->getComponentClasses(new FixedColorPolicy(), 'gray'))->toContain('sy-color', 'sy-color-gray', 'sy-bg-color-600');
});

it('emits only the two colour classes for an unregistered colour or a policy answering an empty map', function (): void {
    $colors = $this::getContainer()->get(ColorManager::class);

    expect($colors->getComponentClasses(new FixedColorPolicy(), 'brand'))->toBe(['sy-color', 'sy-color-brand'])
        ->and($colors->getComponentClasses(IconComponent::class, 'primary'))->toBe(['sy-color', 'sy-color-primary']);
});

it('refuses a map slot outside the eight for a named colour and writes it out unchecked for an inline palette', function (): void {
    $colors = $this::getContainer()->get(ColorManager::class);

    expect(fn (): array => $colors->getComponentClasses(new OffSlotColorPolicy(), 'primary'))->toThrow(LogicException::class, 'Invalid color mapping key [border].')
        ->and($colors->getComponentCustomStyles(new OffSlotColorPolicy(), Color::Rose))->toContain('--sy-border: var(--sy-color-600)');
});

it('refuses a class-string that resolves to no colour policy, naming it', function (): void {
    expect(fn (): array => $this::getContainer()->get(ColorManager::class)->getComponentClasses(StaticInstanceRegistry::class, 'primary'))
        ->toThrow(LogicException::class, 'The color component [' . StaticInstanceRegistry::class . '] must resolve to a service implementing');
});

it('caches a policy instance apart from a sibling policy sharing its dependencies', function (): void {
    $container = $this::getContainer();
    $colors = $container->get(ColorManager::class);

    expect($colors->getComponentClasses($container->get(ButtonComponent::class), 'primary'))->toContain('sy-bg-color-400')
        ->and($colors->getComponentClasses($container->get(OutlinedButtonComponent::class), 'primary'))
        ->toBe(['sy-color', 'sy-color-primary', 'sy-text-color-700', 'dark:sy-text-color-500']);
});

it('writes an inline palette and its policy slots as --sy- declarations, dropping an unsafe shade', function (): void {
    $styles = $this::getContainer()->get(ColorManager::class)->getComponentCustomStyles(
        new FixedColorPolicy(),
        array_replace(Color::Rose, [600 => 'red; background: url(x)']),
    );

    expect($styles)->toContain(
        '--sy-color-500: ' . Color::Rose[500],
        '--sy-color-600: ',
        '--sy-bg: var(--sy-color-600)',
        '--sy-dark-hover-bg: var(--sy-color-300)',
        '--sy-text: oklch(1 0 0)',
    );
});

it('passes a value written only in CSS colour syntax and drops any other whole', function (string $color, string $sanitized): void {
    expect($this::getContainer()->get(ColorManager::class)->sanitizeCssColor($color))->toBe($sanitized);
})->with([
    'oklch' => ['oklch(0.5 0.1 20 / 50%)', 'oklch(0.5 0.1 20 / 50%)'],
    'hex' => ['#ff0000', '#ff0000'],
    'a quote' => ['red" onclick="x', ''],
    'a semicolon' => ['red; background: red', ''],
    'a brace' => ['red} body {', ''],
    'an angle bracket' => ['red</style>', ''],
]);
