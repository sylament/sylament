<?php

declare(strict_types=1);

namespace Sylament\Forms\Validation;

use SplFileInfo;
use Stringable;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class MinFileSizeValidator extends ConstraintValidator
{
    private const int KIB_BYTES = 1024;

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof MinFileSize) {
            throw new UnexpectedTypeException($constraint, MinFileSize::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        if (! is_scalar($value) && ! $value instanceof SplFileInfo && ! $value instanceof Stringable) {
            throw new UnexpectedValueException($value, 'string');
        }

        $path = $value instanceof SplFileInfo ? $value->getPathname() : (string) $value;
        $sizeInBytes = is_file($path) && is_readable($path) ? filesize($path) : false;

        // A file we cannot measure cannot clear a floor, so it fails rather than passes; the
        // paired `File` constraint is what tells the user it is missing or unreadable.
        $sizeInBytes = $sizeInBytes === false ? 0 : $sizeInBytes;

        $limitInBytes = $constraint->kilobytes * self::KIB_BYTES;

        if ($sizeInBytes >= $limitInBytes) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ file }}', $this->formatValue($path))
            ->setParameter('{{ size }}', (string) round($sizeInBytes / self::KIB_BYTES, 2))
            ->setParameter('{{ limit }}', (string) $constraint->kilobytes)
            ->setParameter('{{ suffix }}', 'KiB')
            ->addViolation();
    }
}
