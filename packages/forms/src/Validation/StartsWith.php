<?php

declare(strict_types=1);

namespace Sylament\Forms\Validation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

/**
 * Laravel's `starts_with` / `doesnt_start_with`. Symfony ships no counterpart, and a
 * `Regex` of quoted alternatives would share its class key — and so its
 * `validationMessages()` slot — with every other regex arm on the field.
 */
final class StartsWith extends Constraint
{
    public string $message = 'This value must start with one of: {{ values }}.';

    public string $notMessage = 'This value must not start with any of: {{ values }}.';

    /**
     * @param list<string>  $values
     * @param string[]|null $groups
     */
    public function __construct(
        public readonly array $values,
        public readonly bool $match = true,
        ?string $message = null,
        ?string $notMessage = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);

        if ($values === []) {
            throw new InvalidArgumentException(sprintf('[%s] needs at least one value to compare against.', self::class));
        }

        $this->message = $message ?? $this->message;
        $this->notMessage = $notMessage ?? $this->notMessage;
    }
}
