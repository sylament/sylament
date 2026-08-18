<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Concerns;

use Sylament\Schemas\Schema;

it('carries no field wrapper view by default', function (): void {
    expect(Schema::make()->getFieldWrapperView())->toBeNull();
});

it('stores the field wrapper view it is handed', function (): void {
    expect(Schema::make()->fieldWrapperView('plain')->getFieldWrapperView())->toBe('plain');
});

it('clears the field wrapper view when handed null', function (): void {
    expect(Schema::make()->fieldWrapperView('plain')->fieldWrapperView(null)->getFieldWrapperView())
        ->toBeNull();
});
