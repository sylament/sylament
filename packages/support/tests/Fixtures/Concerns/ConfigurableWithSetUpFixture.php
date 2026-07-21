<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Concerns;

use Sylament\Support\Component\Component;

class ConfigurableWithSetUpFixture extends Component
{
    public int $setUpCalls = 0;

    protected function setUp(): void
    {
        $this->setUpCalls++;
    }
}
