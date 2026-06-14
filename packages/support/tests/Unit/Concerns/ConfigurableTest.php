<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Concerns;

use Sylament\Support\Tests\Fixtures\Concerns\ConfigurableWithSetUpFixture;

it('runs setUp() when configured', function (): void {
    $fixture = new ConfigurableWithSetUpFixture();
    $fixture->configure();

    expect($fixture->setUpCalls)->toBe(1);
});

it('runs setUp() again on each configure() call', function (): void {
    $fixture = new ConfigurableWithSetUpFixture();
    $fixture->configure();
    $fixture->configure();

    expect($fixture->setUpCalls)->toBe(2);
});

it('does not run setUp() until configure() is called', function (): void {
    expect(new ConfigurableWithSetUpFixture()->setUpCalls)->toBe(0);
});
