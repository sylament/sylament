<?php

declare(strict_types=1);

namespace Sylament\Forms\Validation;

use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\RegexValidator;

/**
 * Laravel's `ascii`, whose test is `Str::isAscii()` → `ASCII::is_ascii()`. That
 * negates the class `[^\x09\x10\x13\x0A\x0D\x20-\x7E]`; this is its positive
 * complement, quirks included — NUL is refused while the control characters
 * `\x10` and `\x13` are accepted, which is what Laravel accepts.
 * Symfony's `Charset('ASCII')` is not the same test: it passes every control
 * character, and it is not an early-return constraint.
 */
final class Ascii extends Regex
{
    /**
     * @param string[]|null $groups
     */
    public function __construct(
        ?string $message = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(
            pattern: '/\A[\x09\x0A\x0D\x10\x13\x20-\x7E]*\z/',
            message: $message ?? 'This value may only contain ASCII characters.',
            groups: $groups,
            payload: $payload,
        );
    }

    public function validatedBy(): string
    {
        return RegexValidator::class;
    }
}
