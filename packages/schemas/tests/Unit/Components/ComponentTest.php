<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components;

use Sylament\Schemas\Components\Component;
use Sylament\Support\Component\ViewComponent;

it('is a view component', function (): void {
    expect(new Component())->toBeInstanceOf(ViewComponent::class);
});
