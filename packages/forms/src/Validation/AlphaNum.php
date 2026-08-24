<?php

declare(strict_types=1);

namespace Sylament\Forms\Validation;

use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\RegexValidator;

/**
 * Laravel's `alpha_num`. See [`Alpha`](Alpha.php) for why this is a subclass.
 */
final class AlphaNum extends Regex
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
            pattern: '/\A[\pL\pM\pN]+\z/u',
            message: $message ?? 'This value may only contain letters and numbers.',
            groups: $groups,
            payload: $payload,
        );
    }

    public function validatedBy(): string
    {
        return RegexValidator::class;
    }
}
