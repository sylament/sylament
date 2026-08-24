<?php

declare(strict_types=1);

namespace Sylament\Forms\Validation;

use Symfony\Component\Validator\Constraint;

/**
 * Laravel's `active_url`: the URL's host must resolve. No stock constraint does
 * DNS, so this is the one format arm that is more than unsugared syntax — and it
 * carries Laravel's network cost with it. A consumer wanting syntax only uses
 * `url()`.
 */
final class ActiveUrl extends Constraint
{
    public string $message = 'This value is not an active URL.';

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
