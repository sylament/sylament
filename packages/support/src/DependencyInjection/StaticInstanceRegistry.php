<?php

declare(strict_types=1);

namespace Sylament\Support\DependencyInjection;

final readonly class StaticInstanceRegistry
{
    /**
     * @param iterable<StaticInstanceInterface> $services
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
