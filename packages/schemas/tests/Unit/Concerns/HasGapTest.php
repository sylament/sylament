<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Concerns;

use Sylament\Schemas\Schema;

it('is not dense by default', function (): void {
    expect(Schema::make()->isDense())->toBeFalse();
});

it('is dense when inline', function (): void {
    expect(Schema::make()->inline()->isDense())->toBeTrue();
});

it('lets an explicit dense(false) beat the inline coupling', function (): void {
    expect(Schema::make()->inline()->dense(false)->isDense())->toBeFalse();
});
