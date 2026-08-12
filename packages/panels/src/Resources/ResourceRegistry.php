<?php

declare(strict_types=1);

namespace Sylament\Panels\Resources;

use LogicException;
use Psr\Container\ContainerInterface;

final readonly class ResourceRegistry
{
    public function __construct(
        private ContainerInterface $resources,
    ) {
    }

    /**
     * @param class-string<Resource> $class
     */
    public function get(string $class): Resource
    {
        if (! $this->resources->has($class)) {
            throw new LogicException(sprintf('The resource %s is not a registered service — register it in the application (or bundle) service configuration so the panels compiler pass can see it.', $class));
        }

        $resource = $this->resources->get($class);

        if (! $resource instanceof Resource) {
            throw new LogicException(sprintf('The resource locator returned a non-resource service for %s.', $class));
        }

        return $resource;
    }

    /**
     * @param class-string<Resource> $class
     */
    public function has(string $class): bool
    {
        return $this->resources->has($class);
    }
}
