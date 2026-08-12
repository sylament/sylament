<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Panels\Resources\ResourceRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * Stands in for the `src/` consumers the registry gains with the resource routes
 * and the resource pages. Until one exists, the registry is a private service
 * nothing references, which the container removes at compile time — and a
 * removed service is unreachable from the test container too.
 */
#[Autoconfigure(public: true)]
final readonly class ResourceRegistryHost
{
    public function __construct(
        public ResourceRegistry $registry,
    ) {
    }
}
