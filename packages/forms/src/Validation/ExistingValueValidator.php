<?php

declare(strict_types=1);

namespace Sylament\Forms\Validation;

use LogicException;
use Sylament\Forms\Validation\Concerns\ProbesRecords;
use Sylament\Schemas\State\AssociationReader;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class ExistingValueValidator extends ConstraintValidator
{
    use ProbesRecords;

    public function __construct(
        private readonly ?AssociationReader $associationReader = null,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof ExistingValue) {
            throw new UnexpectedTypeException($constraint, ExistingValue::class);
        }

        if (in_array($value, [null, '', []], true)) {
            return;
        }

        // A membership check that silently does nothing is a security-shaped failure,
        // so an unwired probe throws where the reader's other callers degrade.
        $reader = $this->associationReader ?? throw new LogicException(sprintf(
            '%s is a container service — SylamentFormsBundle registers it with its AssociationReader; a validator built with `new` cannot probe the database.',
            self::class,
        ));

        $query = $this->createProbeQuery($reader, $constraint->entityClass, $constraint->property, 'exists()');

        $wanted = is_array($value) ? array_values(array_unique($value, SORT_REGULAR)) : [$value];
        $parameter = $this->nextParameter($query);

        $query
            ->select("COUNT(DISTINCT record.{$constraint->property})")
            ->andWhere("record.{$constraint->property} IN (:{$parameter})")
            ->setParameter($parameter, $wanted);

        $this->applyModifier($query, $constraint->modifyQueryUsing);

        if ((int) $query->getQuery()->getSingleScalarResult() === count($wanted)) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $this->formatValue($value))
            ->addViolation();
    }
}
