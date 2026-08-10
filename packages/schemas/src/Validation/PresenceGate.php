<?php

declare(strict_types=1);

namespace Sylament\Schemas\Validation;

use Sylament\Schemas\Validation\Contracts\ImplicitConstraint;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\IsFalse;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

final class PresenceGate
{
    /**
     * @var list<class-string>
     */
    private const array IMPLICIT_CONSTRAINTS = [
        ImplicitConstraint::class,
        NotBlank::class,
        NotNull::class,
        IsTrue::class,
        IsFalse::class,
    ];

    /**
     * The constraints that may run against `$value` — every one of them when the
     * value is present, only the implicit ones when it is a blank string.
     *
     * @param  list<Constraint> $constraints
     * @return list<Constraint>
     */
    public static function apply(mixed $value, array $constraints): array
    {
        if (!is_string($value) || mb_trim($value) !== '') {
            return $constraints;
        }

        return array_values(array_filter($constraints, self::isImplicit(...)));
    }

    private static function isImplicit(Constraint $constraint): bool
    {
        return array_any(self::IMPLICIT_CONSTRAINTS, fn (string $implicit): bool => $constraint instanceof $implicit);
    }
}
