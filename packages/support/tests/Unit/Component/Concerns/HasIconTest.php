<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Component\Concerns;

use Sylament\Support\Service\Invoker;
use Sylament\Support\Tests\Fixtures\Component\Concerns\HasIconFixture;
use Sylament\Support\Tests\Fixtures\Icons\IconEnumFixture;
use Symfony\Component\DependencyInjection\Container;
use Twig\Markup;

beforeEach(function (): void {
    Invoker::setInstance(new Invoker(new Container()));
});

afterEach(function (): void {
    Invoker::unsetInstance();
});

it('has no icon by default', function (): void {
    $fixture = new HasIconFixture();

    expect($fixture->getIcon())->toBeNull()
        ->and($fixture->hasIcon())->toBeFalse();
});

it('stores a string, enum, or Markup icon', function (): void {
    $markup = new Markup('<svg></svg>', 'UTF-8');

    expect(new HasIconFixture()->icon('heroicons:x-mark')->getIcon())->toBe('heroicons:x-mark')
        ->and(new HasIconFixture()->icon(IconEnumFixture::Star)->getIcon())->toBe(IconEnumFixture::Star)
        ->and(new HasIconFixture()->icon($markup)->getIcon())->toBe($markup);
});

it('falls back to the default only when the icon is unset', function (): void {
    expect(new HasIconFixture()->getIcon('heroicons:star'))->toBe('heroicons:star');
});

it('stores the disabled sentinel for a blank icon so the default does not apply', function (): void {
    $fixture = new HasIconFixture()->icon('');

    expect($fixture->getIcon('heroicons:star'))->toBeNull()
        ->and($fixture->hasIcon())->toBeFalse();
});

it('evaluates an icon closure', function (): void {
    expect(new HasIconFixture()->icon(fn (): string => 'heroicons:x-mark')->getIcon())
        ->toBe('heroicons:x-mark');
});

it('applies the default when an icon closure returns null', function (): void {
    expect(new HasIconFixture()->icon(fn (): ?string => null)->getIcon('heroicons:star'))
        ->toBe('heroicons:star');
});

it('reports an icon only for a set non-disabled value', function (): void {
    expect(new HasIconFixture()->icon('heroicons:x-mark')->hasIcon())->toBeTrue()
        ->and(new HasIconFixture()->icon(null)->hasIcon())->toBeFalse();
});
