<?php

declare(strict_types=1);

namespace Sylament\Forms\Validation;

use Symfony\Component\Validator\Constraint;

/**
 * Laravel's `same` / `different`, and the constraint `confirmed()` emits. Symfony's
 * `IdenticalTo` cannot serve: `AbstractComparison::__construct()` throws when both
 * `value` and `propertyPath` are null, and a null partner is the normal state of a
 * confirmation field until someone types into it. Neither message prints the
 * compared value — a confirmed password would land in the DOM.
 */
final class MatchesField extends Constraint
{
    public string $message = 'This value must match {{ other }}.';

    public string $notMessage = 'This value must differ from {{ other }}.';

    /**
     * @param mixed         $value  the partner field's resolved raw state
     * @param string        $other  the partner's plain-text label, for the message
     * @param string[]|null $groups
     */
    public function __construct(
        public readonly mixed $value,
        public readonly string $other,
        public readonly bool $match = true,
        ?string $message = null,
        ?string $notMessage = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);

        $this->message = $message ?? $this->message;
        $this->notMessage = $notMessage ?? $this->notMessage;
    }
}
