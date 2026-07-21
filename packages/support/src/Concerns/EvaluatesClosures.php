<?php

declare(strict_types=1);

namespace Sylament\Support\Concerns;

use Closure;
use Sylament\Support\Data\ClosureParameter;
use Sylament\Support\Service\Invoker;

trait EvaluatesClosures
{
    protected string $evaluationIdentifier;

    /**
     * @template T
     * @param T|callable(): T|null $value
     * @param array<string, mixed> $namedInjections
     * @param array<class-string, mixed> $typedInjections
     * @return ($value is null ? null : T)
     */
    public function evaluate(mixed $value, array $namedInjections = [], array $typedInjections = []): mixed
    {
        if (!is_callable($value)) {
            return $value;
        }

        return Invoker::getInstance()->call(
            $value,
            $namedInjections,
            $typedInjections,
            $this->resolveClosureDependencyForEvaluation(...),
        );
    }

    /**
     * @template T
     * @param T|callable():T|null $value
     * @return ($value is null ? null : ($value is callable ? (Closure(): T) : T))
     */
    public function evaluatable(mixed $value): mixed
    {
        if (is_callable($value)) {
            return $value(...);
        }

        return $value;
    }

    protected function resolveClosureDependencyForEvaluation(string $parameterName, ?string $parameterType): ?ClosureParameter
    {
        $resolvedByName = $this->resolveDefaultClosureDependencyForEvaluationByName($parameterName);

        if ($resolvedByName instanceof ClosureParameter) {
            return $resolvedByName;
        }

        if ($parameterType !== null) {
            $resolvedByType = $this->resolveDefaultClosureDependencyForEvaluationByType($parameterType);

            if ($resolvedByType instanceof ClosureParameter) {
                return $resolvedByType;
            }
        }

        if (
            (isset($this->evaluationIdentifier) && $parameterName === $this->evaluationIdentifier)
            || ($parameterType !== null && is_a($this, $parameterType))
        ) {
            return ClosureParameter::make($this);
        }

        return null;
    }

    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): ?ClosureParameter
    {
        return null;
    }

    protected function resolveDefaultClosureDependencyForEvaluationByType(string $parameterType): ?ClosureParameter
    {
        return null;
    }
}
