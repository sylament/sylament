<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\DependencyInjection;

use Sylament\Support\Components\Component;

class ResolvableComponentFixture extends Component
{
    public function __construct(
        public readonly ?ComponentDependencyFixture $dependency = null,
    ) {
    }

    public static function make(): static
    {
        $component = static::newInstance();

        $component->configure();

        return $component;
    }
}
