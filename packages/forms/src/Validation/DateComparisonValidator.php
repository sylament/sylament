<?php

declare(strict_types=1);

namespace Sylament\Forms\Validation;

use DateTimeImmutable;
use Exception;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class DateComparisonValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof DateComparison) {
            throw new UnexpectedTypeException($constraint, DateComparison::class);
        }

        if (! is_string($value) || mb_trim($value) === '') {
            return;
        }

        try {
            $parsed = new DateTimeImmutable($value);
        } catch (Exception) {
            // An unparseable value is ValidDate's violation to report, not this one's.
            return;
        }

        $isValid = match ($constraint->operator) {
            DateComparison::AFTER => $parsed > $constraint->boundary,
            DateComparison::AFTER_OR_EQUAL => $parsed >= $constraint->boundary,
            DateComparison::BEFORE => $parsed < $constraint->boundary,
            DateComparison::BEFORE_OR_EQUAL => $parsed <= $constraint->boundary,
        };

        if ($isValid) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ boundary }}', $constraint->boundary->format('Y-m-d H:i:s'))
            ->addViolation();
    }
}
