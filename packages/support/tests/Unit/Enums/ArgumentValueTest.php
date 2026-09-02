<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Enums;

use BackedEnum;
use Sylament\Support\Enums\ArgumentValue;

it('carries one case, the not-passed sentinel', function (): void {
    expect(ArgumentValue::cases())->toBe([ArgumentValue::Default]);
});

it('is a pure enum, so no string argument can ever equal it', function (): void {
    expect(ArgumentValue::Default)->not->toBeInstanceOf(BackedEnum::class);
});
