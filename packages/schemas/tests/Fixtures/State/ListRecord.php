<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\State;

/**
 * The list-shaped record: a non-nullable `array` property, so a record-subtree
 * owner declaring `nullWhenBlank()` over it meets the non-nullable refusal the
 * branch pass raises for any other unnullable target.
 */
final class ListRecord
{
    /** @var array<array-key, mixed> */
    private array $steps = [];

    /** @return array<array-key, mixed> */
    public function getSteps(): array
    {
        return $this->steps;
    }

    /** @param array<array-key, mixed> $steps */
    public function setSteps(array $steps): void
    {
        $this->steps = $steps;
    }
}
