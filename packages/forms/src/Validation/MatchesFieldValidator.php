<?php

declare(strict_types=1);

namespace Sylament\Forms\Validation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class MatchesFieldValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof MatchesField) {
            throw new UnexpectedTypeException($constraint, MatchesField::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        if (($value === $constraint->value) === $constraint->match) {
            return;
        }

        $this->context->buildViolation($constraint->match ? $constraint->message : $constraint->notMessage)
            ->setParameter('{{ other }}', $constraint->other)
            ->addViolation();
    }
}
