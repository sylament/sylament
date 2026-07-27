<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Evaluation;

class InvokableFixture
{
    public function __invoke(string $value): string
    {
        return "invoked:{$value}";
    }
}
