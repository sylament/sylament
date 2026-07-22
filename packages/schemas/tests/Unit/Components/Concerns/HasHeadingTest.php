<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\Concerns;

use Sylament\Schemas\Components\Section;

it('returns the literal heading', function (): void {
    expect(new Section('Billing')->getHeading())->toBe('Billing');
});

it('clears the heading when called with no arguments', function (): void {
    $section = new Section('Billing');

    $section->heading();

    expect($section->getHeading())->toBeNull();
});
