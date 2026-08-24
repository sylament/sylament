<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Components\Concerns;

use Sylament\Support\Components\Concerns\HasReorderAnimationDuration;
use Sylament\Support\Concerns\EvaluatesClosures;

function reorderAnimationComponent(): object
{
    return new class {
        use EvaluatesClosures;
        use HasReorderAnimationDuration;
    };
}

it('defaults the reorder animation to 300 ms and evaluates a closure', function (): void {
    $component = reorderAnimationComponent();

    expect($component->getReorderAnimationDuration())->toBe(300)
        ->and($component->reorderAnimationDuration(150)->getReorderAnimationDuration())->toBe(150)
        ->and($component->reorderAnimationDuration(static fn (): int => 0)->getReorderAnimationDuration())->toBe(0);
});
