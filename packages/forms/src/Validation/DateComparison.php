<?php

declare(strict_types=1);

namespace Sylament\Forms\Validation;

use DateTimeImmutable;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

final class DateComparison extends Constraint
{
    public const string AFTER = 'after';

    public const string AFTER_OR_EQUAL = 'after_or_equal';

    public const string BEFORE = 'before';

    public const string BEFORE_OR_EQUAL = 'before_or_equal';

    /**
     * @var self::AFTER|self::AFTER_OR_EQUAL|self::BEFORE|self::BEFORE_OR_EQUAL
     */
    public readonly string $operator;

    public string $message;

    /**
     * @param string[]|null $groups
     */
    public function __construct(
        string $operator,
        public readonly DateTimeImmutable $boundary,
        ?string $message = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);

        $operators = [self::AFTER, self::AFTER_OR_EQUAL, self::BEFORE, self::BEFORE_OR_EQUAL];

        if (! in_array($operator, $operators, true)) {
            throw new InvalidArgumentException(sprintf(
                'The date comparison operator [%s] is unknown. Use one of: [%s].',
                $operator,
                implode('], [', $operators),
            ));
        }

        $this->operator = $operator;
        $this->message = $message ?? match ($operator) {
            self::AFTER => 'This value must be after {{ boundary }}.',
            self::AFTER_OR_EQUAL => 'This value must be on or after {{ boundary }}.',
            self::BEFORE => 'This value must be before {{ boundary }}.',
            self::BEFORE_OR_EQUAL => 'This value must be on or before {{ boundary }}.',
        };
    }
}
