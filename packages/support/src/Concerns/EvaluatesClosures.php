<?php

declare(strict_types=1);

namespace Sylament\Support\Concerns;

use Closure;
use Sylament\Support\Evaluation\ClosureParameter;
use Sylament\Support\Evaluation\Invoker;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Contracts\Service\Attribute\Required;

trait EvaluatesClosures
{
    protected string $evaluationIdentifier;

    private Invoker $invoker;

    #[Required]
    public function setInvoker(Invoker $invoker): void
    {
        $this->invoker = $invoker;
    }

    public function getInvoker(): Invoker
    {
        return $this->invoker ??= new Invoker(new Container());
    }

    /**
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

        return $this->getInvoker()->call(
            $value,
            $namedInjections,
            $typedInjections,
            $this->resolveClosureDependencyForEvaluation(...),
            $this::class,
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
