<?php

declare(strict_types=1);

namespace Sylament\Support\Concerns;

use Closure;
use Sylament\Support\Data\ClosureParameter;
use Sylament\Support\Service\Invoker;

trait EvaluatesClosures
{
    /**
     * @template T
     * @param T|callable(): T|null $value
     * @param array<string, mixed> $parameters
     * @return ($value is null ? null : T)
     */
    public function evaluate(mixed $value, array $parameters = []): mixed
    {
        if (!is_callable($value)) {
            return $value;
        }

        return Invoker::getInstance()->call($value, $parameters, $this->resolveDefaultClosureDependencyForEvaluationByName(...));
    }

    /**
     * @template T
     * @param T|callable():T|null $value
     * @return ($value is null ? null : T|Closure():T)
     */
    public function evaluatable(mixed $value): mixed
    {
        if (is_callable($value)) {
            return $value(...);
        }

        return $value;
    }

    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): ?ClosureParameter
    {
        return null;
    }
}
