<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;
use Symfony\Component\Validator\Constraint;

/**
 * Constraints that apply to each *element* of a multi-value field's state. Laravel's
 * `"{$statePath}.*"` key has no per-path counterpart here, so the builder emits the
 * list as one `All` on the field's own path — which is where the wrapper puts a `.*`
 * message upstream anyway.
 */
trait HasNestedRecursiveValidationConstraints
{
    /**
     * @var array<array{Constraint|array<Constraint>|Closure|null, bool|Closure}>
     */
    protected array $nestedRecursiveValidationConstraints = [];

    public function nestedRecursiveConstraint(Constraint|Closure $constraint, bool|Closure $condition = true): static
    {
        $this->nestedRecursiveValidationConstraints[] = [$constraint, $condition];

        return $this;
    }

    /**
     * @param array<Constraint|Closure>|Closure $constraints
     */
    public function nestedRecursiveConstraints(array|Closure $constraints, bool|Closure $condition = true): static
    {
        if ($constraints instanceof Closure) {
            $this->nestedRecursiveValidationConstraints[] = [$constraints, $condition];

            return $this;
        }

        foreach ($constraints as $constraint) {
            $this->nestedRecursiveValidationConstraints[] = [$constraint, $condition];
        }

        return $this;
    }

    /**
     * @return list<Constraint>
     */
    public function getNestedRecursiveValidationConstraints(): array
    {
        $constraints = [];

        foreach ($this->nestedRecursiveValidationConstraints as [$constraint, $condition]) {
            if (! $this->evaluate($condition)) {
                continue;
            }

            $evaluated = $this->evaluate($constraint);

            if ($evaluated === null) {
                continue;
            }

            foreach ($evaluated instanceof Constraint ? [$evaluated] : $evaluated as $single) {
                $constraints[] = $single;
            }
        }

        return $constraints;
    }
}
