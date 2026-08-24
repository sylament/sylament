<?php

declare(strict_types=1);

namespace Sylament\Forms\Validation;

use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\RegexValidator;

/**
 * Laravel's `alpha`: unicode letters and marks, anchored with `\A…\z` so a trailing
 * newline does not slip through `$`. Symfony's own `Type('alpha')` is byte-only
 * `ctype_alpha`. A subclass rather than a bare `Regex` buys a readable default
 * message and its own `validationMessages()` class key.
 */
final class Alpha extends Regex
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
            pattern: '/\A[\pL\pM]+\z/u',
            message: $message ?? 'This value may only contain letters.',
            groups: $groups,
            payload: $payload,
        );
    }

    public function validatedBy(): string
    {
        return RegexValidator::class;
    }
}
