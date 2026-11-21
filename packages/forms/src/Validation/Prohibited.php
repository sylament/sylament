<?php

declare(strict_types=1);

namespace Sylament\Forms\Validation;

use Symfony\Component\Validator\Constraint;

/**
 * Laravel's `prohibited`: the value must be absent. Symfony's `Blank` is not the
 * same test — it violates on an empty array, so a `CheckboxList` with nothing
 * ticked would read as present. A constraint naming the fields it rules out
 * through `$others` reads `{{ others }}` in its message, so the id stays fixed.
 */
final class Prohibited extends Constraint
{
    public string $message = 'This value is prohibited.';

    /**
     * @param string[]|null $groups
     * @param string|null   $others the labels of the fields this one rules out, for the message
     */
    public function __construct(
        ?string $message = null,
        ?array $groups = null,
        mixed $payload = null,
        public readonly ?string $others = null,
    ) {
        parent::__construct(null, $groups, $payload);

        // The default is a validators-domain id, translated by validators.en.yaml.
        $this->message = $message ?? ($others === null ? $this->message : 'This value cannot be combined with {{ others }}.');
    }
}
