<?php

declare(strict_types=1);

use Sylament\Testing\Doctrine\DriverValues;

it('turns every numeric string into the int or float it spells and leaves the rest alone', function (): void {
    expect(DriverValues::numeric([
        'sum' => '210',
        'mysql average' => '15.0000',
        'postgresql average' => '82.5000000000000000',
        'negative' => '-3',
        'native int' => 7,
        'native float' => 2.5,
        'text' => 'ten',
        'null' => null,
    ]))->toBe([
        'sum' => 210,
        'mysql average' => 15.0,
        'postgresql average' => 82.5,
        'negative' => -3,
        'native int' => 7,
        'native float' => 2.5,
        'text' => 'ten',
        'null' => null,
    ]);
});
