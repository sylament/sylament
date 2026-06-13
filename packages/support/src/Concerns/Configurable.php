<?php

declare(strict_types=1);

namespace Sylament\Support\Concerns;

trait Configurable
{
    public function configure(): static
    {
        $this->setUp();

        return $this;
    }

    protected function setUp(): void
    {
        //
    }
}
