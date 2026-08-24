<?php

declare(strict_types=1);

namespace Sylament\Forms\Validation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

/**
 * Filament's `distinct()`, which is not Laravel's `distinct` rule: it compares one
 * repeater item's field against the same field in every sibling item, and its
 * meaning depends on the value's PHP type. The field resolves the siblings at
 * build time and hands them over here as plain values.
 */
final class Distinct extends Constraint
{
    /** A plain value: no sibling may hold the same one. */
    public const string SCALAR = 'scalar';

    /** A list: no sibling's list may intersect it. */
    public const string ARRAY = 'array';

    /** A checkbox: at most one item may be ticked, and — when required — at least one must be. */
    public const string BOOLEAN = 'boolean';

    /**
     * @var self::SCALAR|self::ARRAY|self::BOOLEAN
     */
    public readonly string $mode;

    public string $message = 'This value is already used by another item.';

    public string $mustBeSelectedMessage = 'At least one item must be selected.';

    public string $onlyOneMustBeSelectedMessage = 'Only one item may be selected.';

    /**
     * @param list<mixed>   $siblingValues the same field's value in every other item
     * @param string[]|null $groups
     */
    public function __construct(
        string $mode,
        public readonly array $siblingValues = [],
        public readonly bool $isSiblingSelected = false,
        public readonly bool $mustBeSelected = false,
        ?string $message = null,
        ?string $mustBeSelectedMessage = null,
        ?string $onlyOneMustBeSelectedMessage = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);

        $modes = [self::SCALAR, self::ARRAY, self::BOOLEAN];

        if (! in_array($mode, $modes, true)) {
            throw new InvalidArgumentException(sprintf(
                'The distinct comparison mode [%s] is unknown. Use one of: [%s].',
                $mode,
                implode('], [', $modes),
            ));
        }

        $this->mode = $mode;
        $this->message = $message ?? $this->message;
        $this->mustBeSelectedMessage = $mustBeSelectedMessage ?? $this->mustBeSelectedMessage;
        $this->onlyOneMustBeSelectedMessage = $onlyOneMustBeSelectedMessage ?? $this->onlyOneMustBeSelectedMessage;
    }
}
