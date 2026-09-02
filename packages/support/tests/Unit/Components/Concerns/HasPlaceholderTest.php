<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Components\Concerns;

use Sylament\Support\Tests\Fixtures\Components\Concerns\HasPlaceholderFixture;
use Twig\Markup;

it('has no placeholder by default', function (): void {
    expect(new HasPlaceholderFixture()->getPlaceholder())->toBeNull();
});

it('stores a placeholder string', function (): void {
    expect(new HasPlaceholderFixture()->placeholder('—')->getPlaceholder())->toBe('—');
});

it('keeps a Markup placeholder unflattened', function (): void {
    $markup = new Markup('<em>none</em>', 'UTF-8');

    expect(new HasPlaceholderFixture()->placeholder($markup)->getPlaceholder())->toBe($markup);
});

it('evaluates a placeholder closure', function (): void {
    expect(new HasPlaceholderFixture()->placeholder(fn (): string => 'empty')->getPlaceholder())->toBe('empty');
});

it('lets the last placeholder call win, null included', function (): void {
    expect(new HasPlaceholderFixture()->placeholder('—')->placeholder(null)->getPlaceholder())->toBeNull();
});
