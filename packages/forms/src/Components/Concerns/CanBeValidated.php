<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;
use Sylament\Forms\Components\Contracts\CanBeLengthConstrained;
use Sylament\Forms\Components\Field;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;

use function Sylament\Support\blank;

trait CanBeValidated
{
    protected bool|Closure $isRequired = false;

    /**
     * @var array<array{Constraint|array<Constraint>|Closure|null, bool|Closure}>
     */
    protected array $constraints = [];

    /**
     * @var array<string, string|Closure>
     */
    protected array $validationMessages = [];

    public function required(bool|Closure $condition = true): static
    {
        $this->isRequired = $condition;

        return $this;
    }

    public function nullable(bool|Closure $condition = true): static
    {
        $this->required(static fn (Field $component): bool => ! $component->evaluate($condition));

        return $this;
    }

    public function constraint(Constraint|Closure $constraint, bool|Closure $condition = true): static
    {
        $this->constraints[] = [$constraint, $condition];

        return $this;
    }

    /**
     * @param array<Constraint|Closure>|Closure $constraints
     */
    public function constraints(array|Closure $constraints, bool|Closure $condition = true): static
    {
        if ($constraints instanceof Closure) {
            $this->constraints[] = [$constraints, $condition];

            return $this;
        }

        foreach ($constraints as $constraint) {
            $this->constraints[] = [$constraint, $condition];
        }

        return $this;
    }

    /**
     * @param array<string, string|Closure> $messages
     */
    public function validationMessages(array $messages): static
    {
        $this->validationMessages = $messages;

        return $this;
    }

    public function isRequired(): bool
    {
        return (bool) $this->evaluate($this->isRequired);
    }

    /**
     * @return array<string, string>
     */
    public function getValidationMessages(): array
    {
        $messages = [];

        foreach ($this->validationMessages as $key => $message) {
            $messages[$key] = $this->evaluate($message);
        }

        return array_filter($messages);
    }

    /**
     * @return list<Constraint>
     */
    public function getValidationConstraints(): array
    {
        return $this->buildValidationConstraints();
    }

    /**
     * @return list<Constraint>
     */
    protected function buildValidationConstraints(): array
    {
        $constraints = [];

        if ($this->isRequired()) {
            $constraints[] = $this->getRequiredConstraint();
        }

        if ($this instanceof CanBeLengthConstrained) {
            $constraints = [...$constraints, ...$this->getLengthValidationConstraints()];
        }

        foreach ($this->constraints as [$constraint, $condition]) {
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

    protected function getRequiredConstraint(): Constraint
    {
        return new NotBlank(message: $this->getValidationMessages()['required'] ?? null);
    }

    /**
     * @param array<string, list<Constraint>> $constraints
     */
    public function dehydrateValidationConstraints(array &$constraints): void
    {
        $statePath = $this->getStatePath();

        if (blank($statePath)) {
            return;
        }

        $componentConstraints = $this->getValidationConstraints();

        if ($componentConstraints === []) {
            return;
        }

        $constraints[$statePath] = $componentConstraints;
    }
}
