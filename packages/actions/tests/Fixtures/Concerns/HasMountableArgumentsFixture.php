<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Concerns;

use Sylament\Actions\Concerns\HasArguments;
use Sylament\Actions\Concerns\HasMountableArguments;

class HasMountableArgumentsFixture
{
    use HasArguments;
    use HasMountableArguments;
}
