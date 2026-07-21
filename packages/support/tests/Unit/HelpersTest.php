<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit;

use stdClass;
use ArrayObject;
use Countable;
use Stringable;
use Sylament\Support\Enum\IconSize;
use Sylament\Support\Icons\IconManager;
use Sylament\Support\Tests\Fixtures\Icons\IconEnumFixture;
use Sylament\Support\Tests\Fixtures\Icons\IconRendererFixture;
use Sylament\Support\Tests\Fixtures\Icons\ScalableIconFixture;
use Sylament\Support\Twig\ComponentAttributeBag;
use Twig\Markup;

use function Sylament\Support\blank;
use function Sylament\Support\filled;
use function Sylament\Support\generate_icon_html;

it('treats valueless input as blank', function (mixed $value): void {
    expect(blank($value))->toBeTrue()
        ->and(filled($value))->toBeFalse();
})->with([
    'null' => [null],
    'empty string' => [''],
    'whitespace string' => ['   '],
    'unicode whitespace' => ["\u{00A0}\u{2007}"],
    'whitespace-only Stringable' => [new class implements Stringable {
        public function __toString(): string
        {
            return '  ';
        }
    }],
    'empty array' => [[]],
    'zero-count Countable' => [new ArrayObject()],
    'zero-count Countable with a debug __toString' => [new class implements Countable, Stringable {
        public function count(): int
        {
            return 0;
        }

        public function __toString(): string
        {
            return self::class . '@hash';
        }
    }],
]);

it('treats meaningful values as filled', function (mixed $value): void {
    expect(filled($value))->toBeTrue()
        ->and(blank($value))->toBeFalse();
})->with([
    'text' => ['Heading'],
    'zero int' => [0],
    'zero float' => [0.0],
    'zero string' => ['0'],
    'false' => [false],
    'Markup with content' => [new Markup('<em>Hi</em>', 'UTF-8')],
    'non-empty array' => [[0]],
    'non-empty Countable' => [new ArrayObject([1])],
    'plain object' => [new stdClass()],
]);

it('gives a Markup text semantics despite it being Countable', function (): void {
    // Twig\Markup counts its characters, so the count-first order every other
    // Countable gets (Doctrine-collection parity with Laravel) would report a
    // whitespace-only Markup as filled; it must keep the text semantics
    // Filament's Countable-free HtmlString gets.
    expect(blank(new Markup('   ', 'UTF-8')))->toBeTrue();
});

/**
 * Installs an IconManager backed by the recording renderer fixture and returns
 * the fixture; the Unit-suite afterEach hook unsets the static instance.
 */
function fakeIconRenderer(): IconRendererFixture
{
    $renderer = new IconRendererFixture();
    IconManager::setInstance(new IconManager($renderer));

    return $renderer;
}

it('generate_icon_html returns null for a blank icon', function (): void {
    fakeIconRenderer();

    expect(generate_icon_html(null))->toBeNull()
        ->and(generate_icon_html('   '))->toBeNull()
        ->and(generate_icon_html(new Markup('  ', 'UTF-8')))->toBeNull();
});

it('generate_icon_html renders an icon name through the renderer as Markup', function (): void {
    fakeIconRenderer();

    $html = generate_icon_html('heroicons:x-mark');

    expect($html)->toBeInstanceOf(Markup::class)
        ->and((string) $html)->toBe('<svg data-icon="heroicons:x-mark"></svg>');
});

it('generate_icon_html applies sy-icon and the medium size class by default', function (): void {
    $renderer = fakeIconRenderer();

    generate_icon_html('heroicons:x-mark');

    expect($renderer->lastAttributes)->toBe(['class' => 'sy-icon sy-size-md']);
});

it('generate_icon_html applies an explicit size class', function (): void {
    $renderer = fakeIconRenderer();

    generate_icon_html('heroicons:x-mark', size: IconSize::TwoExtraLarge);

    expect($renderer->lastAttributes)->toBe(['class' => 'sy-icon sy-size-2xl']);
});

it('generate_icon_html lets a resolvable alias win over the given icon', function (): void {
    $renderer = fakeIconRenderer();
    IconManager::getInstance()->register(['section.collapse-button' => 'heroicons:chevron-up']);

    generate_icon_html('heroicons:x-mark', 'section.collapse-button');

    expect($renderer->lastName)->toBe('heroicons:chevron-up');
});

it('generate_icon_html falls back to the given icon for an unresolvable alias', function (): void {
    $renderer = fakeIconRenderer();

    generate_icon_html('heroicons:x-mark', 'unknown.alias');

    expect($renderer->lastName)->toBe('heroicons:x-mark');
});

it('generate_icon_html resolves the first matching alias from a list', function (): void {
    $renderer = fakeIconRenderer();
    IconManager::getInstance()->register(['second.alias' => 'heroicons:user']);

    generate_icon_html('heroicons:x-mark', ['first.alias', 'second.alias']);

    expect($renderer->lastName)->toBe('heroicons:user');
});

it('generate_icon_html appends caller attributes after the defaults and drops null and false values', function (): void {
    $renderer = fakeIconRenderer();

    generate_icon_html('heroicons:x-mark', attributes: new ComponentAttributeBag([
        'class' => 'custom',
        'data-x' => '1',
        'data-none' => null,
        'data-off' => false,
        'data-on' => true,
    ]));

    expect($renderer->lastAttributes)->toBe([
        'class' => 'sy-icon sy-size-md custom',
        'data-x' => '1',
        'data-on' => true,
    ]);
});

it('generate_icon_html wraps a Markup icon raw inside a span', function (): void {
    fakeIconRenderer();

    $html = generate_icon_html(new Markup('<svg data-custom></svg>', 'UTF-8'));

    expect((string) $html)->toBe('<span class="sy-icon sy-size-md"><svg data-custom></svg></span>');
});

it('generate_icon_html renders a path icon as a decorative image', function (): void {
    fakeIconRenderer();

    $html = generate_icon_html('images/lo"go.svg');

    expect((string) $html)->toBe('<img src="images/lo&quot;go.svg" alt="" class="sy-icon sy-size-md" />');
});

it('generate_icon_html keeps a named path icon non-decorative', function (): void {
    fakeIconRenderer();

    $html = generate_icon_html('images/logo.svg', attributes: new ComponentAttributeBag(['aria-label' => 'Logo']));

    expect((string) $html)->toContain('aria-label="Logo"')
        ->not->toContain('alt=');
});

it('generate_icon_html scales a ScalableIcon with the effective size', function (): void {
    $renderer = fakeIconRenderer();

    generate_icon_html(ScalableIconFixture::Star);

    expect($renderer->lastName)->toBe('fixture:star-md');

    generate_icon_html(ScalableIconFixture::Star, size: IconSize::Small);

    expect($renderer->lastName)->toBe('fixture:star-sm');
});

it('generate_icon_html renders a plain backed enum by its value', function (): void {
    $renderer = fakeIconRenderer();

    generate_icon_html(IconEnumFixture::Star);

    expect($renderer->lastName)->toBe('heroicons:star');
});
