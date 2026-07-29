<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Components;

use Closure;
use RuntimeException;
use Sylament\Support\Components\Component;
use Sylament\Support\Configuration\ComponentManager;
use Sylament\Support\Tests\Fixtures\Configuration\ConfigurableChildFixture;
use Sylament\Support\Tests\Fixtures\Configuration\ConfigurableParentFixture;

it('resolves the same lazily created instance on every call', function (): void {
    $manager = ComponentManager::resolve();

    expect(ComponentManager::resolve())->toBe($manager)
        ->and(ComponentManager::getInstance())->toBe($manager);
});

it('applies a registered closure after setUp() on the keyed class', function (): void {
    ConfigurableParentFixture::configureUsing(function (ConfigurableParentFixture $component): void {
        $component->events[] = 'config';
    });

    expect(ConfigurableParentFixture::make()->events)->toBe(['parent.setUp', 'config']);
});

it('does not apply closures keyed to a subclass', function (): void {
    ConfigurableChildFixture::configureUsing(function (ConfigurableChildFixture $component): void {
        $component->events[] = 'child.config';
    });

    expect(ConfigurableParentFixture::make()->events)->toBe(['parent.setUp']);
});

it('applies ancestor-keyed closures before the setUp() of a lower declaring class', function (): void {
    Component::configureUsing(function (ConfigurableParentFixture $component): void {
        $component->events[] = 'base.config';
    });

    expect(ConfigurableParentFixture::make()->events)->toBe(['base.config', 'parent.setUp']);
});

it('interleaves setUp() with closures in class hierarchy order', function (): void {
    ConfigurableParentFixture::configureUsing(function (ConfigurableParentFixture $component): void {
        $component->events[] = 'parent.config';
    });

    ConfigurableChildFixture::configureUsing(function (ConfigurableChildFixture $component): void {
        $component->events[] = 'child.config';
    });

    expect(ConfigurableChildFixture::make()->events)->toBe([
        'parent.config',
        'parent.setUp',
        'child.setUp',
        'child.config',
    ]);
});

it('runs closures for the same class in registration order', function (): void {
    ConfigurableParentFixture::configureUsing(function (ConfigurableParentFixture $component): void {
        $component->events[] = 'first';
    });

    ConfigurableParentFixture::configureUsing(function (ConfigurableParentFixture $component): void {
        $component->events[] = 'second';
    });

    expect(ConfigurableParentFixture::make()->events)->toBe(['parent.setUp', 'first', 'second']);
});

it('runs important closures after all normal ones, in hierarchy order', function (): void {
    ConfigurableChildFixture::configureUsing(function (ConfigurableChildFixture $component): void {
        $component->events[] = 'child.important';
    }, isImportant: true);

    ConfigurableParentFixture::configureUsing(function (ConfigurableParentFixture $component): void {
        $component->events[] = 'parent.important';
    }, isImportant: true);

    ConfigurableChildFixture::configureUsing(function (ConfigurableChildFixture $component): void {
        $component->events[] = 'child.config';
    });

    expect(ConfigurableChildFixture::make()->events)->toBe([
        'parent.setUp',
        'child.setUp',
        'child.config',
        'parent.important',
        'child.important',
    ]);
});

it('returns a removal closure that unregisters only its own entry', function (): void {
    $removeFirst = ConfigurableParentFixture::configureUsing(function (ConfigurableParentFixture $component): void {
        $component->events[] = 'first';
    });

    ConfigurableParentFixture::configureUsing(function (ConfigurableParentFixture $component): void {
        $component->events[] = 'second';
    });

    expect($removeFirst)->toBeInstanceOf(Closure::class);

    $removeFirst();

    expect(ConfigurableParentFixture::make()->events)->toBe(['parent.setUp', 'second']);
});

it('returns a removal closure for important registrations', function (): void {
    $remove = ConfigurableParentFixture::configureUsing(function (ConfigurableParentFixture $component): void {
        $component->events[] = 'important';
    }, isImportant: true);

    $remove();

    expect(ConfigurableParentFixture::make()->events)->toBe(['parent.setUp']);
});

it('scopes a registration to the during() closure and returns its result', function (): void {
    $result = ConfigurableParentFixture::configureUsing(function (ConfigurableParentFixture $component): void {
        $component->events[] = 'config';
    }, during: fn (): ConfigurableParentFixture => ConfigurableParentFixture::make());

    expect($result->events)->toBe(['parent.setUp', 'config'])
        ->and(ConfigurableParentFixture::make()->events)->toBe(['parent.setUp']);
});

it('unregisters a during() registration when the closure throws', function (): void {
    expect(fn (): mixed => ConfigurableParentFixture::configureUsing(function (ConfigurableParentFixture $component): void {
        $component->events[] = 'config';
    }, during: fn () => throw new RuntimeException('boom')))->toThrow(RuntimeException::class);

    expect(ConfigurableParentFixture::make()->events)->toBe(['parent.setUp']);
});
