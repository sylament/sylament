<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Tests\Fixtures\Concerns\HasIdFixture;

it('round-trips the id', function (): void {
    expect(new HasIdFixture()->name('status')->id('status-select')->getId())->toBe('status-select');
});

it('falls back to the name when no id is set', function (): void {
    expect(new HasIdFixture()->name('status')->getId())->toBe('status');
});
