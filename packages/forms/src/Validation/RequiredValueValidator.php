<?php

declare(strict_types=1);

namespace Sylament\Forms\Validation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use function Sylament\Support\filled;

final class RequiredValueValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof RequiredValue) {
            throw new UnexpectedTypeException($constraint, RequiredValue::class);
        }

        if (filled($value)) {
            return;
        }

        $this->context->buildViolation($constraint->message)->addViolation();
    }
}
