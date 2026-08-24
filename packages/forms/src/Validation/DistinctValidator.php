<?php

declare(strict_types=1);

namespace Sylament\Forms\Validation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class DistinctValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof Distinct) {
            throw new UnexpectedTypeException($constraint, Distinct::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        match ($constraint->mode) {
            Distinct::BOOLEAN => $this->validateBoolean($value, $constraint),
            Distinct::ARRAY => $this->validateArray($value, $constraint),
            Distinct::SCALAR => $this->validateScalar($value, $constraint),
        };
    }

    private function validateBoolean(mixed $value, Distinct $constraint): void
    {
        if ($value && $constraint->isSiblingSelected) {
            $this->context->buildViolation($constraint->onlyOneMustBeSelectedMessage)->addViolation();

            return;
        }

        if ($value || $constraint->isSiblingSelected) {
            return;
        }

        if ($constraint->mustBeSelected) {
            $this->context->buildViolation($constraint->mustBeSelectedMessage)->addViolation();
        }
    }

    private function validateArray(mixed $value, Distinct $constraint): void
    {
        if (! is_array($value)) {
            return;
        }

        if (array_intersect($this->comparable($value), $this->comparable($constraint->siblingValues)) === []) {
            return;
        }

        $this->context->buildViolation($constraint->message)->addViolation();
    }

    /**
     * The values `array_intersect()` can compare — it casts to string, so anything
     * that cannot be cast is dropped rather than matched. Only wire scalars reach a
     * multi-value field's state, so nothing real is lost.
     *
     * @param  array<mixed>       $values
     * @return array<int, string>
     */
    private function comparable(array $values): array
    {
        return array_values(array_map(
            strval(...),
            array_filter($values, is_scalar(...)),
        ));
    }

    private function validateScalar(mixed $value, Distinct $constraint): void
    {
        if (! in_array($value, $constraint->siblingValues)) {
            return;
        }

        $this->context->buildViolation($constraint->message)->addViolation();
    }
}
