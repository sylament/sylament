<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Concerns;

use Sylament\Support\Concerns\Configurable;

class ConfigurableWithSetUpFixture
{
    use Configurable;

    public int $setUpCalls = 0;

    protected function setUp(): void
    {
        $this->setUpCalls++;
    }
}
