<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Components\Concerns;

use Sylament\Support\Components\Concerns\HasWidth;
use Sylament\Support\Concerns\EvaluatesClosures;

function widthComponent(): object
{
    return new class {
        use EvaluatesClosures;
        use HasWidth;
    };
}

it('renders an integer width in pixels and passes a string through', function (): void {
    $component = widthComponent();

    expect($component->getWidth())->toBeNull()
        ->and($component->width(160)->getWidth())->toBe('160px')
        ->and($component->width('12rem')->getWidth())->toBe('12rem')
        ->and($component->width(static fn (): int => 40)->getWidth())->toBe('40px');
});

it('clears the width with null', function (): void {
    expect(widthComponent()->width(160)->width(null)->getWidth())->toBeNull();
});
