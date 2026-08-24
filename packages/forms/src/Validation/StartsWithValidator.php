<?php

declare(strict_types=1);

namespace Sylament\Forms\Validation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class StartsWithValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof StartsWith) {
            throw new UnexpectedTypeException($constraint, StartsWith::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        if (is_string($value) || is_numeric($value)) {
            $matches = array_any($constraint->values, fn (string $candidate): bool => str_starts_with((string) $value, $candidate));

            if ($matches === $constraint->match) {
                return;
            }
        }

        $this->context->buildViolation($constraint->match ? $constraint->message : $constraint->notMessage)
            ->setParameter('{{ values }}', implode(', ', $constraint->values))
            ->addViolation();
    }
}
