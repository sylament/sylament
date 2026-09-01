<?php

declare(strict_types=1);

namespace Sylament\Forms\Validation;

use Sylament\Schemas\Validation\Contracts\ImplicitConstraint;
use Symfony\Component\Validator\Constraint;

/**
 * Laravel's `required`: the value must be present. Symfony's `NotBlank` is not
 * the same test — it violates on `false`, so a boolean field bound to a
 * non-nullable column could never submit while off.
 */
final class RequiredValue extends Constraint implements ImplicitConstraint
{
    public string $message = 'This value should not be blank.';

    /**
     * @param string[]|null $groups
     */
    public function __construct(
        ?string $message = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);

        $this->message = $message ?? $this->message;
    }
}
