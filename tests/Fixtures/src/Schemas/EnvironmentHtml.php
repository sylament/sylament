<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

use Sylament\Schemas\Components\Html;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * An `Html` whose constructor takes a service, so it can only be built by the container.
 */
class EnvironmentHtml extends Html
{
    public function __construct(
        private readonly KernelInterface $kernel,
    ) {
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->content("<p>Built in the {$this->kernel->getEnvironment()} environment</p>");
    }
}
