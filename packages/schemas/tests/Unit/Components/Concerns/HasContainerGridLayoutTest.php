<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\Concerns;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Concerns\HasContainerGridLayout;

function gridLayoutComponent(): Component
{
    return new class extends Component {
        use HasContainerGridLayout;
    };
}

it('defaults to one column with every breakpoint unset', function (): void {
    expect(gridLayoutComponent()->getGridColumns())->toBe([
        'default' => 1, 'sm' => null, 'md' => null, 'lg' => null, 'xl' => null, '2xl' => null,
    ]);
});

it('treats a bare integer as the lg breakpoint', function (): void {
    expect(gridLayoutComponent()->grid(2)->getGridColumns())->toBe(['lg' => 2])
        ->and(gridLayoutComponent()->grid(3)->getGridColumns('lg'))->toBe(3)
        ->and(gridLayoutComponent()->grid(3)->getGridColumns('md'))->toBeNull();
});

it('merges breakpoint maps across calls and evaluates closure entries on read', function (): void {
    $component = gridLayoutComponent()
        ->grid(['default' => 1, 'md' => 2])
        ->grid(static fn (): array => ['xl' => 4])
        ->grid(static fn (): int => 3);

    expect($component->getGridColumns())->toBe(['default' => 1, 'md' => 2, 'xl' => 4, 'lg' => 3]);
});
