<?php

declare(strict_types=1);

use Sylament\Support\ArrayRecord;

it('defaults the key name to __key', function (): void {
    expect(ArrayRecord::getKeyName())->toBe('__key');
});

it('lets an application override the key name globally', function (): void {
    ArrayRecord::keyName('uuid');

    try {
        expect(ArrayRecord::getKeyName())->toBe('uuid');
    } finally {
        ArrayRecord::keyName('__key');
    }
});
