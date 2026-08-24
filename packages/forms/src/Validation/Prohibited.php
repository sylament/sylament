<?php

declare(strict_types=1);

namespace Sylament\Forms\Validation;

use Symfony\Component\Validator\Constraint;

/**
 * Laravel's `prohibited`: the value must be absent. Symfony's `Blank` is not the
 * same test — it violates on an empty array, so a `CheckboxList` with nothing
 * ticked would read as present.
 */
final class Prohibited extends Constraint
{
    public string $message = 'This value is prohibited.';

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
