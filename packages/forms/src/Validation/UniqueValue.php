<?php

declare(strict_types=1);

namespace Sylament\Forms\Validation;

use Closure;
use Symfony\Component\Validator\Constraint;

/**
 * Laravel's `unique`, ported onto one DQL probe: no record of `$entityClass`
 * other than `$ignoredIdentifier` may hold this value at `$property`.
 *
 * Named `UniqueValue` rather than `Unique` because Symfony ships its own
 * `Constraints\Unique` (which asserts an *array* has no duplicates); two classes
 * of that name in one import list would let an IDE auto-import the wrong one as a
 * `validationMessages()` key, where an unmatched key is silently ignored by design.
 */
final class UniqueValue extends Constraint
{
    public string $message = 'This value is already taken.';

    /**
     * @param class-string  $entityClass
     * @param string[]|null $groups
     */
    public function __construct(
        public readonly string $entityClass,
        public readonly string $property,
        public readonly string|int|null $ignoredIdentifier = null,
        public readonly ?Closure $modifyQueryUsing = null,
        ?string $message = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);

        $this->message = $message ?? $this->message;
    }
}
