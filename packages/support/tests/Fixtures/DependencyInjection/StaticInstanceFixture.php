<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\DependencyInjection;

use Sylament\Support\DependencyInjection\Concerns\HasStaticInstance;
use Sylament\Support\DependencyInjection\Contracts\StaticInstance;

class StaticInstanceFixture implements StaticInstance
{
    use HasStaticInstance;
}
