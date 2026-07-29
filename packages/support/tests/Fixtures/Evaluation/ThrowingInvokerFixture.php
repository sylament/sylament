<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Evaluation;

use LogicException;
use Sylament\Support\Evaluation\Invoker;
use Symfony\Component\DependencyInjection\Container;

class ThrowingInvokerFixture extends Invoker
{
    public function __construct()
    {
        parent::__construct(new Container());
    }

    /**
     * @param array<string, mixed> $namedInjections
     * @param array<class-string, mixed> $typedInjections
     */
    public function call(
        callable $callable,
        array $namedInjections = [],
        array $typedInjections = [],
        ?callable $resolveParameter = null,
        ?string $context = null,
    ): mixed {
        throw new LogicException('The Invoker was called for a value that is not a Closure.');
    }
}
