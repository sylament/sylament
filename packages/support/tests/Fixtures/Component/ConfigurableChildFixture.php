<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Component;

class ConfigurableChildFixture extends ConfigurableParentFixture
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->events[] = 'child.setUp';
    }
}
