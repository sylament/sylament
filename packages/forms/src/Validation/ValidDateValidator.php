<?php

declare(strict_types=1);

namespace Sylament\Forms\Validation;

use DateTimeImmutable;
use Exception;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class ValidDateValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof ValidDate) {
            throw new UnexpectedTypeException($constraint, ValidDate::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        if (is_string($value) && $this->isDate($value)) {
            return;
        }

        $this->context->buildViolation($constraint->message)->addViolation();
    }

    private function isDate(string $value): bool
    {
        try {
            new DateTimeImmutable($value);
        } catch (Exception) {
            // The parse failing is the answer, not an error to report.
            return false;
        }

        return true;
    }
}
