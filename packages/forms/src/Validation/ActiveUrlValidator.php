<?php

declare(strict_types=1);

namespace Sylament\Forms\Validation;

use ErrorException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ActiveUrlValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof ActiveUrl) {
            throw new UnexpectedTypeException($constraint, ActiveUrl::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        if (is_string($value)) {
            $host = parse_url($value, PHP_URL_HOST);

            if (is_string($host) && $host !== '' && $this->hasDnsRecord($host)) {
                return;
            }
        }

        $this->context->buildViolation($constraint->message)->addViolation();
    }

    protected function hasDnsRecord(string $host): bool
    {
        try {
            $records = dns_get_record($host . '.', DNS_A | DNS_AAAA);
        } catch (ErrorException) {
            return false;
        }

        return is_array($records) && $records !== [];
    }
}
