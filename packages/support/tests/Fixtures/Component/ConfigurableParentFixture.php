<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Component;

use Sylament\Support\Component\Component;

class ConfigurableParentFixture extends Component
{
    /**
     * @var list<string>
     */
    public array $events = [];

    public static function make(): static
    {
        $component = new static();

        $component->configure();

        return $component;
    }

    protected function setUp(): void
    {
        $this->events[] = 'parent.setUp';
    }
}
