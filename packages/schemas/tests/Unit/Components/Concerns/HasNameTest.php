<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\Concerns;

use Sylament\Schemas\Tests\Fixtures\Components\Concerns\HasNameFixture;

it('has a null name by default', function (): void {
    expect(new HasNameFixture()->getName())->toBeNull();
});

it('returns the set name', function (): void {
    expect(new HasNameFixture()->name('title')->getName())->toBe('title');
});

it('resets the name with null', function (): void {
    expect(new HasNameFixture()->name('title')->name(null)->getName())->toBeNull();
});
