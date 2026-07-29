<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\DependencyInjection;

use Sylament\Support\DependencyInjection\Concerns\HasStaticInstance;
use Sylament\Support\DependencyInjection\Contracts\StaticInstance;

class AnotherStaticInstanceFixture implements StaticInstance
{
    use HasStaticInstance;
}
