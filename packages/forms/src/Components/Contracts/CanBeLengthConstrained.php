<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Contracts;

use Symfony\Component\Validator\Constraint;

interface CanBeLengthConstrained
{
    /**
     * @return list<Constraint>
     */
    public function getLengthValidationConstraints(): array;
}
