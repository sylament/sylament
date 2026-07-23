<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\State;

use Sylament\Schemas\State\PropertyAccessor;

it('boots the property accessor as a static instance', function (): void {
    $service = $this::getContainer()->get(PropertyAccessor::class);

    expect(PropertyAccessor::getInstance())->toBe($service);
});
