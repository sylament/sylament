<?php

declare(strict_types=1);

namespace Sylament\Forms\Validation;

use LogicException;
use Sylament\Forms\Validation\Concerns\ProbesRecords;
use Sylament\Schemas\Identity\IdentifierConstraint;
use Sylament\Schemas\State\AssociationReader;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class UniqueValueValidator extends ConstraintValidator
{
    use ProbesRecords;

    public function __construct(
        private readonly ?AssociationReader $associationReader = null,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof UniqueValue) {
            throw new UnexpectedTypeException($constraint, UniqueValue::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        // A uniqueness check that silently does nothing is a security-shaped failure,
        // so an unwired probe throws where the reader's other callers degrade.
        $reader = $this->associationReader ?? throw new LogicException(sprintf(
            '%s is a container service — SylamentFormsBundle registers it with its AssociationReader; a validator built with `new` cannot probe the database.',
            self::class,
        ));

        $query = $this->createProbeQuery($reader, $constraint->entityClass, $constraint->property, 'unique()');

        $valueParameter = $this->nextParameter($query);
        $query->andWhere("record.{$constraint->property} = :{$valueParameter}")->setParameter($valueParameter, $value);

        if ($constraint->ignoredIdentifier !== null) {
            $exclusion = IdentifierConstraint::notEquals($query, 'record', $constraint->entityClass, (string) $constraint->ignoredIdentifier, 'sylament_ignored_record');

            if ($exclusion !== null) {
                $query->andWhere($exclusion);
            }
        }

        $this->applyModifier($query, $constraint->modifyQueryUsing);

        if ($query->setMaxResults(1)->getQuery()->getOneOrNullResult() === null) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $this->formatValue($value))
            ->addViolation();
    }
}
