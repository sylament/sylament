<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Contracts;

use Symfony\Component\Validator\Constraint;

interface HasNestedRecursiveValidationConstraints
{
    /**
     * @return list<Constraint>
     */
    public function getNestedRecursiveValidationConstraints(): array;
}
