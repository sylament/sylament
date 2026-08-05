<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Records;

/**
 * The outer value object of the nesting quadrants: a group whose `lines` member
 * holds the inner repeater's items, so one record slot can carry a cast outer
 * repeater over plain-array inner items as well as over cast ones.
 */
final class GroupItem
{
    /**
     * @param list<LineItem>|null $lines
     */
    public function __construct(
        public ?string $name = null,
        public ?array $lines = null,
    ) {
    }
}
