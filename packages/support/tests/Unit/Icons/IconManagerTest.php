<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Icons;

use LogicException;
use Sylament\Support\Icons\IconManager;
use Sylament\Support\Tests\Fixtures\Icons\IconRendererFixture;

it('resolves a registered alias', function (): void {
    $manager = new IconManager();
    $manager->register(['panels::pages.dashboard.navigation-item' => 'heroicons:home']);

    expect($manager->resolve('panels::pages.dashboard.navigation-item'))->toBe('heroicons:home');
});

it('returns null for an unknown alias', function (): void {
    expect(new IconManager()->resolve('unknown'))->toBeNull();
});

it('merges registrations with the last one winning', function (): void {
    $manager = new IconManager();
    $manager->register(['alias' => 'heroicons:home', 'other' => 'heroicons:user']);
    $manager->register(['alias' => 'heroicons:x-mark']);

    expect($manager->resolve('alias'))->toBe('heroicons:x-mark')
        ->and($manager->resolve('other'))->toBe('heroicons:user');
});

it('resolves the first matching alias from a list', function (): void {
    $manager = new IconManager();
    $manager->register(['second' => 'heroicons:user']);

    expect($manager->resolve(['first', 'second']))->toBe('heroicons:user');
});

it('delegates rendering to the renderer', function (): void {
    $renderer = new IconRendererFixture();
    $manager = new IconManager($renderer);

    $html = $manager->renderIcon('heroicons:x-mark', ['class' => 'sy-icon']);

    expect($html)->toBe('<svg data-icon="heroicons:x-mark"></svg>')
        ->and($renderer->lastName)->toBe('heroicons:x-mark')
        ->and($renderer->lastAttributes)->toBe(['class' => 'sy-icon']);
});

it('throws without a renderer', function (): void {
    expect(fn (): string => new IconManager()->renderIcon('heroicons:x-mark'))
        ->toThrow(LogicException::class, 'UXIconsBundle');
});
