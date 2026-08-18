<?php

declare(strict_types=1);

namespace Sylament\Forms\Validation;

use Symfony\Component\Validator\Constraint;

final class ValidDate extends Constraint
{
    public string $message = 'This value is not a valid date.';
}
