<?php

declare(strict_types=1);

namespace Sylament\Support\DependencyInjection;

use Sylament\Support\DependencyInjection\Contracts\StaticInstance;

final readonly class StaticInstanceRegistry
{
    /**
     * @param iterable<StaticInstance> $services
     */
    public function __construct(
        private iterable $services,
    ) {
    }

    public function bootStaticInstances(): void
    {
        foreach ($this->services as $service) {
            $service::setInstance($service);
        }
    }

    public function unsetStaticInstances(): void
    {
        foreach ($this->services as $service) {
            $service::unsetInstance();
        }
    }
}
