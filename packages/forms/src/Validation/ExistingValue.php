<?php

declare(strict_types=1);

namespace Sylament\Forms\Validation;

use Closure;
use Symfony\Component\Validator\Constraint;

/**
 * Laravel's `exists`, ported onto one DQL probe: some record of `$entityClass`
 * must hold this value at `$property` — every one of them, for an array value.
 * Named for what it asserts, the way {@see UniqueValue} is.
 */
final class ExistingValue extends Constraint
{
    public string $message = 'The selected value does not exist.';

    /**
     * @param class-string  $entityClass
     * @param string[]|null $groups
     */
    public function __construct(
        public readonly string $entityClass,
        public readonly string $property,
        public readonly ?Closure $modifyQueryUsing = null,
        ?string $message = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);

        $this->message = $message ?? $this->message;
    }
}
