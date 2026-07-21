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
     * A Closure held in a T-typed slot must return T; its parameters carry no
     * signature contract — the Invoker resolves each one by name and type.
     *
     * @template T
     * @param T|Closure $value
     * @param array<string, mixed> $namedInjections
     * @param array<class-string, mixed> $typedInjections
     * @return T
     */
    public function evaluate(mixed $value, array $namedInjections = [], array $typedInjections = []): mixed
    {
        if (!$value instanceof Closure) {
            return $value;
        }

        return Invoker::getInstance()->call(
            $value,
            $namedInjections,
            $typedInjections,
            $this->resolveClosureDependencyForEvaluation(...),
        );
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
