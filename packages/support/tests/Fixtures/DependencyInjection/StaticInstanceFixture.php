<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\DependencyInjection;

use Sylament\Support\DependencyInjection\HasStaticInstance;
use Sylament\Support\DependencyInjection\StaticInstanceInterface;

class StaticInstanceFixture implements StaticInstanceInterface
{
    use HasStaticInstance;
}
