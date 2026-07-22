<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\Concerns;

use Sylament\Schemas\Components\Section;

it('returns the literal description', function (): void {
    expect(new Section()->description('All invoices')->getDescription())->toBe('All invoices');
});

it('clears the description when called with no arguments', function (): void {
    $section = new Section()->description('All invoices');

    $section->description();

    expect($section->getDescription())->toBeNull();
});
