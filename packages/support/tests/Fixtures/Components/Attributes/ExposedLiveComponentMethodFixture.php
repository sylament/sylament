<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Components\Attributes;

use Sylament\Support\Components\Attributes\ExposedLiveComponentMethod;

final class ExposedLiveComponentMethodFixture
{
    #[ExposedLiveComponentMethod]
    public function exposed(): string
    {
        return 'exposed';
    }

    public function notExposed(): string
    {
        return 'not-exposed';
    }
}
