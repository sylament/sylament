<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;
use LogicException;
use ReflectionObject;
use ReflectionProperty;
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
     * @var array<class-string<Constraint>, string|Closure|array<string, string|Closure>>
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
     * @param array<class-string<Constraint>, string|Closure|array<string, string|Closure>> $messages
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
     * Message options per constraint class, normalised: a string value becomes the
     * constraint's `message` option. A value resolving to '' is dropped so the
     * constraint keeps its own default.
     *
     * @return array<class-string<Constraint>, array<string, string>>
     */
    public function getValidationMessages(): array
    {
        $messages = [];

        foreach ($this->validationMessages as $constraintClass => $options) {
            $evaluated = array_filter(array_map(
                fn (string|Closure $message): string => $this->evaluate($message),
                is_array($options) ? $options : ['message' => $options],
            ));

            if ($evaluated !== []) {
                $messages[$constraintClass] = $evaluated;
            }
        }

        return $messages;
    }

    /**
     * @return list<Constraint>
     */
    public function getValidationConstraints(): array
    {
        return $this->applyValidationMessages($this->buildValidationConstraints());
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

    /**
     * @param  list<Constraint> $constraints
     * @return list<Constraint>
     */
    protected function applyValidationMessages(array $constraints): array
    {
        $messages = $this->getValidationMessages();

        if ($messages === []) {
            return $constraints;
        }

        foreach ($constraints as $index => $constraint) {
            $options = $messages[$constraint::class] ?? [];

            if ($options === []) {
                continue;
            }

            $available = $this->getValidationMessageOptions($constraint);
            $constraint = clone $constraint;
            $constraints[$index] = $constraint;

            foreach ($options as $option => $message) {
                if (! in_array($option, $available, strict: true)) {
                    throw new LogicException(sprintf(
                        'Sylament cannot apply a validation message to [%s] because %s has no [%s] message option. Use one of: [%s].',
                        $this->getStatePath(),
                        $constraint::class,
                        $option,
                        implode('], [', $available),
                    ));
                }

                $constraint->{$option} = $message;
            }
        }

        return $constraints;
    }

    /**
     * @return list<string>
     */
    private function getValidationMessageOptions(Constraint $constraint): array
    {
        $options = [];

        foreach (new ReflectionObject($constraint)->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();

            if ($name === 'message' || str_ends_with($name, 'Message')) {
                $options[] = $name;
            }
        }

        return $options;
    }

    protected function getRequiredConstraint(): Constraint
    {
        return new NotBlank();
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
