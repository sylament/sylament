<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Contracts;

use Symfony\Component\Validator\Constraint;

interface HasValidationConstraints
{
    /**
     * @return list<Constraint>
     */
    public function getValidationConstraints(): array;

    /**
     * @param array<string, list<Constraint>> $constraints
     */
    public function dehydrateValidationConstraints(array &$constraints): void;
}
