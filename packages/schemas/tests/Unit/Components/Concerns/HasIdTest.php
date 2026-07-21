<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\Concerns;

use Sylament\Schemas\Components\Component;
use Sylament\Support\Service\Invoker;
use Symfony\Component\DependencyInjection\Container;

beforeEach(function (): void {
    Invoker::setInstance(new Invoker(new Container()));
});

afterEach(function (): void {
    Invoker::unsetInstance();
});

it('has no id by default', function (): void {
    $component = new Component();

    expect($component->getId())->toBeNull()
        ->and($component->getCustomId())->toBeNull();
});

it('returns the set id', function (): void {
    expect(new Component()->id('profile-section')->getId())->toBe('profile-section');
});

it('strips characters that could break out of an attribute or JS string', function (string $raw, string $sanitized): void {
    expect(new Component()->id($raw)->getId())->toBe($sanitized);
})->with([
    'angle brackets' => ['pro<file>', 'profile'],
    'double quote' => ['pro"file', 'profile'],
    'single quote' => ["pro'file", 'profile'],
    'backtick' => ['pro`file', 'profile'],
    'control characters' => ["pro\x00\x1ffile\x7f", 'profile'],
]);

it('passes the component to an id closure', function (): void {
    $component = new Component();

    $received = null;
    $component->id(function (Component $component) use (&$received): string {
        $received = $component;

        return 'computed-id';
    });

    expect($component->getId())->toBe('computed-id')
        ->and($received)->toBe($component);
});

it('resets the id with null', function (): void {
    expect(new Component()->id('profile')->id(null)->getId())->toBeNull();
});

it('matches getCustomId until the keys infrastructure adds a fallback', function (): void {
    $component = new Component()->id('profile');

    expect($component->getId())->toBe($component->getCustomId());
});
