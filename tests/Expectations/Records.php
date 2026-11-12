<?php

declare(strict_types=1);

expect()->extend('toBeRecord', function (mixed ...$fields) {
    if ($fields === [] || array_any($fields, static fn (mixed $value, int|string $field): bool => is_int($field))) {
        throw new InvalidArgumentException('toBeRecord() takes each field it compares by name, and at least one.');
    }

    return $this->toHaveProperties($fields);
});
